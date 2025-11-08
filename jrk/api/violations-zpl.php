<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';

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
    
    // Force download with proper headers for iOS
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="violation-ticket-' . $ticketId . '.zpl"');
    header('Content-Length: ' . strlen($zpl));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
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
    
    // Ticket type header (WARNING or VIOLATION) - use safe centered width
    $ticketType = $ticket['ticket_type'] ?? 'VIOLATION';
    $zpl .= "^FO20," . $yPos . "^FB536,1,0,C,0^A0N,40,40^FD" . strtoupper($ticketType) . "^FS\n";
    $yPos += 50;
    
    // Border line
    $zpl .= "^FO20," . $yPos . "^GB536,3,3^FS\n";
    $yPos += 15;
    
    // Vehicle info - use FB for wrapping
    $vehicleInfo = trim(
        ($ticket['vehicle_year'] ?? '') . ' ' . 
        ($ticket['vehicle_color'] ?? '') . ' ' . 
        ($ticket['vehicle_make'] ?? '') . ' ' . 
        ($ticket['vehicle_model'] ?? '')
    );
    
    if (!empty($vehicleInfo)) {
        $zpl .= "^FO20," . $yPos . "^FB536,2,0,L,0^A0N,22,22^FD" . escapeZPL($vehicleInfo) . "^FS\n";
        $yPos += 50;
    }
    
    // Tag/Plate info - use FB for wrapping
    $tagPlateInfo = [];
    if (!empty($ticket['tag_number'])) {
        $tagPlateInfo[] = "Tag: " . $ticket['tag_number'];
    }
    if (!empty($ticket['plate_number'])) {
        $tagPlateInfo[] = "Plate: " . $ticket['plate_number'];
    }
    if (!empty($tagPlateInfo)) {
        $zpl .= "^FO20," . $yPos . "^FB536,2,0,L,0^A0N,20,20^FD" . escapeZPL(implode(' / ', $tagPlateInfo)) . "^FS\n";
        $yPos += 44;
    }
    
    // Border line
    $zpl .= "^FO20," . $yPos . "^GB536,2,2^FS\n";
    $yPos += 15;
    
    // Violation intro text - use FB for wrapping
    $zpl .= "^FO20," . $yPos . "^FB536,2,0,L,0^A0N,20,20^FDThe following violations have been observed on this vehicle:^FS\n";
    $yPos += 48;
    
    // Violation list - use FB for wrapping long descriptions (allow up to 5 lines)
    foreach ($violations as $index => $violation) {
        $violationText = ($index + 1) . ". " . $violation['description'];
        $zpl .= "^FO30," . $yPos . "^FB506,5,0,L,0^A0N,18,18^FD" . escapeZPL($violationText) . "^FS\n";
        // Assume up to 5 lines per violation
        $yPos += 96;
    }
    
    $yPos += 10;
    
    // Date and time
    $issuedDate = new DateTime($ticket['issued_at']);
    $zpl .= "^FO20," . $yPos . "^A0N,18,18^FDDate: " . $issuedDate->format('m/d/Y') . "^FS\n";
    $yPos += 22;
    $zpl .= "^FO20," . $yPos . "^A0N,18,18^FDTime: " . $issuedDate->format('h:i A') . "^FS\n";
    $yPos += 30;
    
    // Fine amount (if any) - use safe centered width
    if ($totalFine > 0) {
        $zpl .= "^FO20," . $yPos . "^FB536,1,0,C,0^A0N,28,28^FDFINE: $" . number_format($totalFine, 2) . "^FS\n";
        $yPos += 40;
    }
    
    // Custom property text - use FB for wrapping (allow up to 4 lines per custom line)
    if (!empty($ticket['property_custom_ticket_text'])) {
        $customLines = explode("\n", $ticket['property_custom_ticket_text']);
        foreach ($customLines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                $zpl .= "^FO20," . $yPos . "^FB536,4,0,C,0^A0N,18,18^FD" . escapeZPL($line) . "^FS\n";
                $yPos += 76;
            }
        }
        $yPos += 10;
    }
    
    // Tow warning - use FB for wrapping with safe centered width
    if ($minTowDeadline !== null && $minTowDeadline > 0) {
        $zpl .= "^FO20," . $yPos . "^FB536,1,0,C,0^A0N,16,16^FD** TOW WARNING **^FS\n";
        $yPos += 20;
        $zpl .= "^FO20," . $yPos . "^FB536,2,0,C,0^A0N,16,16^FDVehicle subject to tow in " . $minTowDeadline . " hours^FS\n";
        $yPos += 40;
    }
    
    // Border line
    $zpl .= "^FO20," . $yPos . "^GB536,2,2^FS\n";
    $yPos += 15;
    
    // Property info - use FB for wrapping
    if (!empty($ticket['property_name'])) {
        $zpl .= "^FO20," . $yPos . "^FB536,2,0,L,0^A0N,16,16^FDProperty: " . escapeZPL($ticket['property_name']) . "^FS\n";
        $yPos += 36;
    }
    if (!empty($ticket['property_address'])) {
        $zpl .= "^FO20," . $yPos . "^FB536,3,0,L,0^A0N,14,14^FD" . escapeZPL($ticket['property_address']) . "^FS\n";
        $yPos += 48;
    }
    if (!empty($ticket['property_contact_phone'])) {
        $zpl .= "^FO20," . $yPos . "^FB536,1,0,L,0^A0N,14,14^FDPhone: " . escapeZPL($ticket['property_contact_phone']) . "^FS\n";
        $yPos += 18;
    }
    
    // QR code with ticket ID (optional - for tracking)
    $yPos += 10;
    $zpl .= "^FO200," . $yPos . "^BQN,2,4^FDQA,TICKET-" . $ticket['id'] . "^FS\n";
    
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
