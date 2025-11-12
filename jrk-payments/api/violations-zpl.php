<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/session.php';


Session::start();

if (!Session::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user = Session::user();
$ticketId = $_GET['id'] ?? '';

if (empty($ticketId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Ticket ID is required']);
    exit;
}

$db = Database::getInstance();

try {
    // Check if tag_number and plate_number columns exist
    $columnsExist = false;
    try {
        $checkStmt = $db->query("SHOW COLUMNS FROM violation_tickets LIKE 'tag_number'");
        $columnsExist = $checkStmt->rowCount() > 0;
    } catch (PDOException $e) {
        $columnsExist = false;
    }
    
    // Check if ticket_type column exists
    $hasTicketType = false;
    try {
        $checkStmt = $db->query("SHOW COLUMNS FROM violation_tickets LIKE 'ticket_type'");
        $hasTicketType = $checkStmt->rowCount() > 0;
    } catch (PDOException $e) {
        $hasTicketType = false;
    }
    
    $selectFields = "id, vehicle_id, property, issued_by_username, issued_at,
                custom_note, vehicle_year, vehicle_color, vehicle_make, vehicle_model,
                property_name, property_address, property_contact_name, 
                property_contact_phone, property_contact_email";
    
    if ($columnsExist) {
        $selectFields .= ", tag_number, plate_number";
    }
    
    if ($hasTicketType) {
        $selectFields .= ", ticket_type";
    }
    
    $stmt = $db->prepare("
        SELECT $selectFields
        FROM violation_tickets
        WHERE id = ?
    ");
    
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['error' => 'Ticket not found']);
        exit;
    }
    
    if (!$columnsExist) {
        $ticket['tag_number'] = null;
        $ticket['plate_number'] = null;
    }
    
    if (!$hasTicketType) {
        $ticket['ticket_type'] = 'VIOLATION';
    }
    
    // Check property access
    $stmt = $db->prepare("SELECT id, name, custom_ticket_text FROM properties WHERE id = ? OR name = ?");
    $stmt->execute([$ticket['property'], $ticket['property']]);
    $propertyData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($propertyData && !canAccessProperty($propertyData['id'])) {
        http_response_code(403);
        echo json_encode(['error' => 'You do not have access to this ticket']);
        exit;
    }
    
    $ticket['property_custom_ticket_text'] = $propertyData['custom_ticket_text'] ?? null;
    
    // Fetch guest information from vehicles table if vehicle_id exists
    $ticket['guest'] = false;
    $ticket['guest_of'] = null;
    if (!empty($ticket['vehicle_id'])) {
        try {
            // Check if guest and guest_of columns exist in vehicles table
            $guestColumnsExist = false;
            try {
                $checkStmt = $db->query("SHOW COLUMNS FROM vehicles LIKE 'guest'");
                $guestColumnsExist = $checkStmt->rowCount() > 0;
            } catch (PDOException $e) {
                $guestColumnsExist = false;
            }
            
            if ($guestColumnsExist) {
                $stmt = $db->prepare("SELECT guest, guest_of FROM vehicles WHERE id = ?");
                $stmt->execute([$ticket['vehicle_id']]);
                $vehicleData = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($vehicleData) {
                    $ticket['guest'] = (bool)($vehicleData['guest'] ?? false);
                    $ticket['guest_of'] = $vehicleData['guest_of'] ?? null;
                }
            }
        } catch (Exception $e) {
            // If guest data fetch fails, continue without it
        }
    }
    
    // Fetch violation items
    $stmt = $db->prepare("
        SELECT 
            vti.description, 
            vti.display_order,
            v.fine_amount,
            v.tow_deadline_hours
        FROM violation_ticket_items vti
        LEFT JOIN violations v ON vti.violation_id = v.id
        WHERE vti.ticket_id = ?
        ORDER BY vti.display_order ASC
    ");
    $stmt->execute([$ticketId]);
    $violations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate totals
    $totalFine = 0;
    $minTowDeadline = null;
    foreach ($violations as $violation) {
        if ($violation['fine_amount'] !== null) {
            $totalFine += floatval($violation['fine_amount']);
        }
        if ($violation['tow_deadline_hours'] !== null) {
            $hours = intval($violation['tow_deadline_hours']);
            if ($minTowDeadline === null || $hours < $minTowDeadline) {
                $minTowDeadline = $hours;
            }
        }
    }
    
    // Generate ZPL code
    $zpl = generateZPL($ticket, $violations, $totalFine, $minTowDeadline);
    
    // iOS Safari-compatible headers - use octet-stream with nosniff to prevent .txt suffix
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="ticket-' . $ticketId . '.zpl"');
    header('Content-Length: ' . strlen($zpl));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Expires: 0');
    echo $zpl;
    
} catch (Exception $e) {
    error_log("Error in violations-zpl.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error generating ZPL: ' . $e->getMessage()]);
}

function generateZPL($ticket, $violations, $totalFine, $minTowDeadline) {
    // ZQ510 is 3" width = 576 dots at 203 dpi
    $zpl = "^XA\n";
    
    // Set printer to ZPL mode (important for ZQ510)
    $zpl .= "! U1 setvar \"device.languages\" \"zpl\"\n";
    
    // Calibrate media
    $zpl .= "~jc^xa^jus^xz\n";
    $zpl .= "^XA\n";
    
    $yPos = 20;
    
    // Include header logo if enabled and ZPL version exists
    try {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT setting_value FROM printer_settings WHERE setting_key = 'logo_top_enabled'");
        $stmt->execute();
        $logoEnabled = $stmt->fetchColumn();
        
        if ($logoEnabled === 'true') {
            $stmt = $db->prepare("SELECT setting_value FROM printer_settings WHERE setting_key = 'logo_top_zpl'");
            $stmt->execute();
            $logoZpl = $stmt->fetchColumn();
            
            // Get logo height for proper spacing
            $stmt = $db->prepare("SELECT setting_value FROM printer_settings WHERE setting_key = 'logo_top_zpl_height'");
            $stmt->execute();
            $logoHeight = $stmt->fetchColumn();
            
            if (!empty($logoZpl)) {
                // Position logo centered horizontally
                $zpl .= "^FO20," . $yPos . $logoZpl . "^FS\n";
                
                // Use actual logo height for spacing, fallback to 100 dots if not found
                $spacing = !empty($logoHeight) ? (int)$logoHeight + 20 : 100;
                $yPos += $spacing;
            }
        }
    } catch (Exception $e) {
        // If logo loading fails, continue without logo
        error_log("ZPL logo loading error: " . $e->getMessage());
    }
    
    // Ticket type header (WARNING or VIOLATION) - use safe centered width
    // Font size increased 162.5%: 40 -> 70 -> 105 (50% larger)
    $ticketType = $ticket['ticket_type'] ?? 'VIOLATION';
    $zpl .= "^FO20," . $yPos . "^FB536,1,0,C,0^A0N,105,105^FD" . strtoupper($ticketType) . "^FS\n";
    $yPos += 120;
    
    // Border line
    $zpl .= "^FO20," . $yPos . "^GB536,3,3^FS\n";
    $yPos += 10;
    
    // Vehicle info - use FB for wrapping (tighter spacing)
    // Font size reduced 10%: 39 -> 35
    $vehicleInfo = trim(
        ($ticket['vehicle_year'] ?? '') . ' ' . 
        ($ticket['vehicle_color'] ?? '') . ' ' . 
        ($ticket['vehicle_make'] ?? '') . ' ' . 
        ($ticket['vehicle_model'] ?? '')
    );
    
    if (!empty($vehicleInfo)) {
        $zpl .= "^FO20," . $yPos . "^FB536,2,0,L,0^A0N,35,35^FD" . escapeZPL($vehicleInfo) . "^FS\n";
        $yPos += 60;
    }
    
    // Tag/Plate info - use FB for wrapping (tighter spacing)
    // Font size reduced 10%: 35 -> 32
    $tagPlateInfo = [];
    if (!empty($ticket['tag_number'])) {
        $tagPlateInfo[] = "Tag: " . $ticket['tag_number'];
    }
    if (!empty($ticket['plate_number'])) {
        $tagPlateInfo[] = "Plate: " . $ticket['plate_number'];
    }
    if (!empty($tagPlateInfo)) {
        $zpl .= "^FO20," . $yPos . "^FB536,1,0,L,0^A0N,32,32^FD" . escapeZPL(implode(' / ', $tagPlateInfo)) . "^FS\n";
        $yPos += 50;
    }
    
    // Guest Pass indicator (if guest vehicle)
    // Font size reduced 10%: 35 -> 32
    if ($ticket['guest']) {
        $guestText = "Guest Pass";
        if (!empty($ticket['guest_of'])) {
            $guestText .= " - APT " . $ticket['guest_of'];
        }
        $zpl .= "^FO20," . $yPos . "^FB536,1,0,L,0^A0N,32,32^FD" . escapeZPL($guestText) . "^FS\n";
        $yPos += 50;
    }
    
    // Border line
    $zpl .= "^FO20," . $yPos . "^GB536,2,2^FS\n";
    $yPos += 10;
    
    // Violation intro text - use FB for wrapping
    // Font size reduced 10%: 35 -> 32
    $zpl .= "^FO20," . $yPos . "^FB536,2,0,L,0^A0N,32,32^FDThe following violations have been observed on this vehicle:^FS\n";
    $yPos += 84;
    
    // Violation list - use FB for wrapping long descriptions (allow up to 5 lines)
    // Font size increased 75%: 18 -> 32 (31.5 rounded up)
    foreach ($violations as $index => $violation) {
        $violationText = ($index + 1) . ". " . $violation['description'];
        $zpl .= "^FO30," . $yPos . "^FB506,5,0,L,0^A0N,32,32^FD" . escapeZPL($violationText) . "^FS\n";
        // Minimal spacing between violations (just one line height)
        $yPos += 40;
    }
    
    // Single space after the last violation
    $yPos += 15;
    
    // Date and time on ONE LINE
    // Font size increased 75%: 18 -> 32 (31.5 rounded up)
    $issuedDate = new DateTime($ticket['issued_at']);
    $dateTimeStr = "Date: " . $issuedDate->format('m/d/Y') . "  |  Time: " . $issuedDate->format('h:i A');
    $zpl .= "^FO20," . $yPos . "^FB536,1,0,L,0^A0N,32,32^FD" . escapeZPL($dateTimeStr) . "^FS\n";
    $yPos += 45;
    
    // Fine amount (if any) - use safe centered width
    // Font size increased 75%: 28 -> 49
    if ($totalFine > 0) {
        $zpl .= "^FO20," . $yPos . "^FB536,1,0,C,0^A0N,49,49^FDFINE: $" . number_format($totalFine, 2) . "^FS\n";
        $yPos += 70;
    }
    
    // Custom property text - use FB for wrapping (allow up to 4 lines per custom line)
    // Font size increased 75%: 18 -> 32 (31.5 rounded up)
    if (!empty($ticket['property_custom_ticket_text'])) {
        $customLines = explode("\n", $ticket['property_custom_ticket_text']);
        foreach ($customLines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                $zpl .= "^FO20," . $yPos . "^FB536,4,0,C,0^A0N,32,32^FD" . escapeZPL($line) . "^FS\n";
                $yPos += 133;
            }
        }
        // NO extra space after custom text (removed the +10)
    }
    
    // Tow warning - use FB for wrapping with safe centered width
    // Font size increased 75%: 16 -> 28
    if ($minTowDeadline !== null && $minTowDeadline > 0) {
        $zpl .= "^FO20," . $yPos . "^FB536,1,0,C,0^A0N,28,28^FD** TOW WARNING **^FS\n";
        $yPos += 35;
        $zpl .= "^FO20," . $yPos . "^FB536,2,0,C,0^A0N,28,28^FDVehicle subject to tow in " . $minTowDeadline . " hours^FS\n";
        $yPos += 70;
    }
    
    // Border line
    $zpl .= "^FO20," . $yPos . "^GB536,2,2^FS\n";
    $yPos += 10;
    
    // Property info - use FB for wrapping (reduced spacing)
    // Font size increased 75%: 16 -> 28 (exactly 75%)
    if (!empty($ticket['property_name'])) {
        $zpl .= "^FO20," . $yPos . "^FB536,2,0,L,0^A0N,28,28^FDProperty: " . escapeZPL($ticket['property_name']) . "^FS\n";
        $yPos += 45;
    }
    // Font size increased 75%: 14 -> 25 (24.5 rounded up)
    if (!empty($ticket['property_address'])) {
        $zpl .= "^FO20," . $yPos . "^FB536,2,0,L,0^A0N,25,25^FD" . escapeZPL($ticket['property_address']) . "^FS\n";
        $yPos += 60;
    }
    if (!empty($ticket['property_contact_phone'])) {
        $zpl .= "^FO20," . $yPos . "^FB536,1,0,L,0^A0N,25,25^FDPhone: " . escapeZPL($ticket['property_contact_phone']) . "^FS\n";
        $yPos += 35;
    }
    
    // End ZPL
    $zpl .= "^XZ\n";
    
    return $zpl;
}

function escapeZPL($text) {
    // Remove or replace characters that could cause ZPL issues
    $text = str_replace(['^', '~', '\\'], ['', '', ''], $text);
    // Limit to printable ASCII characters
    $text = preg_replace('/[^\x20-\x7E]/', '', $text);
    return $text;
}
