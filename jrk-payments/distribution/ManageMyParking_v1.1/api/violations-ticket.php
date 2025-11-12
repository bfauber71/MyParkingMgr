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
    // Check if tag_number and plate_number columns exist (for backward compatibility)
    $columnsExist = false;
    try {
        $checkStmt = $db->query("SHOW COLUMNS FROM violation_tickets LIKE 'tag_number'");
        $columnsExist = $checkStmt->rowCount() > 0;
    } catch (PDOException $e) {
        // Assume columns don't exist
        $columnsExist = false;
    }
    
    // Check if ticket_type column exists (for backward compatibility)
    $hasTicketType = false;
    try {
        $checkStmt = $db->query("SHOW COLUMNS FROM violation_tickets LIKE 'ticket_type'");
        $hasTicketType = $checkStmt->rowCount() > 0;
    } catch (PDOException $e) {
        $hasTicketType = false;
    }
    
    // Build SELECT statement based on which columns exist
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
        error_log("Ticket not found: $ticketId");
        http_response_code(404);
        echo json_encode(['error' => 'Ticket not found']);
        exit;
    }
    
    // Add empty tag/plate fields if columns don't exist
    if (!$columnsExist) {
        $ticket['tag_number'] = null;
        $ticket['plate_number'] = null;
    }
    
    // Add default ticket_type if column doesn't exist
    if (!$hasTicketType) {
        $ticket['ticket_type'] = 'VIOLATION';
    }
    
    error_log("Ticket found, property value: " . $ticket['property']);
    
    // Check property access and get custom_ticket_text
    // Try by ID first (new tickets), then by name (old tickets for backward compatibility)
    $stmt = $db->prepare("SELECT id, name, custom_ticket_text FROM properties WHERE id = ? OR name = ?");
    $stmt->execute([$ticket['property'], $ticket['property']]);
    $propertyData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$propertyData) {
        error_log("Property not found for ticket property value: " . $ticket['property']);
        // Don't fail - just continue without custom text
        $ticket['property_custom_ticket_text'] = null;
    } else {
        error_log("Property found: " . $propertyData['name'] . " (ID: " . $propertyData['id'] . ")");
        
        if (!canAccessProperty($propertyData['id'])) {
            error_log("User does not have access to property: " . $propertyData['id']);
            http_response_code(403);
            echo json_encode(['error' => 'You do not have access to this ticket']);
            exit;
        }
        
        // Add custom ticket text to ticket data
        $ticket['property_custom_ticket_text'] = $propertyData['custom_ticket_text'];
    }
    
    // Fetch violation items with fine and tow information
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
    
    // Calculate total fine and minimum tow deadline
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
    
    $ticket['violations'] = $violations;
    $ticket['total_fine'] = $totalFine;
    $ticket['min_tow_deadline_hours'] = $minTowDeadline;
    
    echo json_encode(['ticket' => $ticket]);
} catch (PDOException $e) {
    error_log("Database error in violations-ticket.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("General error in violations-ticket.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
}
