<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';

Session::start();

// Require authentication and create/delete permission for violations
requirePermission(MODULE_VIOLATIONS, ACTION_CREATE_DELETE);

$user = Session::user();

$input = json_decode(file_get_contents('php://input'), true);

$vehicleId = trim($input['vehicleId'] ?? '');
$violationIds = $input['violations'] ?? [];
$customNote = trim($input['customNote'] ?? '');

if (empty($vehicleId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Vehicle ID is required']);
    exit;
}

if (empty($violationIds) && empty($customNote)) {
    http_response_code(400);
    echo json_encode(['error' => 'At least one violation or custom note is required']);
    exit;
}

$db = Database::getInstance();

try {
    $db->beginTransaction();
    
    // Check if ticket_type column exists (for backward compatibility)
    $hasTicketType = false;
    try {
        $checkStmt = $db->query("SHOW COLUMNS FROM violation_tickets LIKE 'ticket_type'");
        $hasTicketType = $checkStmt->rowCount() > 0;
    } catch (PDOException $e) {
        $hasTicketType = false;
    }
    
    // Fetch vehicle data including tag and plate
    $stmt = $db->prepare("
        SELECT id, property, year, color, make, model, tag_number, plate_number
        FROM vehicles
        WHERE id = ?
    ");
    $stmt->execute([$vehicleId]);
    $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$vehicle) {
        $db->rollBack();
        http_response_code(404);
        echo json_encode(['error' => 'Vehicle not found']);
        exit;
    }
    
    // Check property access - handle both UUID and name
    $stmt = $db->prepare("SELECT id FROM properties WHERE id = ? OR name = ?");
    $stmt->execute([$vehicle['property'], $vehicle['property']]);
    $propertyData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$propertyData) {
        $db->rollBack();
        http_response_code(404);
        echo json_encode(['error' => 'Property not found']);
        exit;
    }
    
    $propertyId = $propertyData['id'];
    
    if (!canAccessProperty($propertyId)) {
        $db->rollBack();
        http_response_code(403);
        echo json_encode(['error' => 'You do not have access to this property']);
        exit;
    }
    
    // Fetch property details with first contact
    $stmt = $db->prepare("
        SELECT 
            p.id,
            p.name,
            p.address,
            pc.name AS contact_name,
            pc.phone AS contact_phone,
            pc.email AS contact_email
        FROM properties p
        LEFT JOIN property_contacts pc ON p.id = pc.property_id AND pc.position = 0
        WHERE p.id = ?
    ");
    $stmt->execute([$propertyId]);
    $property = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get configured timezone for timestamp
    $timezone = 'America/New_York'; // Default
    try {
        $stmt = $db->prepare("SELECT setting_value FROM printer_settings WHERE setting_key = 'timezone'");
        $stmt->execute();
        $tzResult = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($tzResult && !empty($tzResult['setting_value'])) {
            $timezone = $tzResult['setting_value'];
        }
    } catch (Exception $e) {
        error_log("Failed to fetch timezone setting: " . $e->getMessage());
    }
    
    // Create violation ticket with timezone-aware timestamp
    $ticketId = Database::uuid();
    $dt = new DateTime('now', new DateTimeZone($timezone));
    $issuedAt = $dt->format('Y-m-d H:i:s');
    
    // Get ticket type from input (default to VIOLATION)
    $ticketType = strtoupper(trim($input['ticketType'] ?? 'VIOLATION'));
    if (!in_array($ticketType, ['VIOLATION', 'WARNING'])) {
        $ticketType = 'VIOLATION';
    }
    
    if ($hasTicketType) {
        // New schema with ticket_type field
        $stmt = $db->prepare("
            INSERT INTO violation_tickets (
                id, vehicle_id, property, issued_by_user_id, issued_by_username, issued_at,
                custom_note, vehicle_year, vehicle_color, vehicle_make, vehicle_model,
                tag_number, plate_number,
                property_name, property_address, property_contact_name, property_contact_phone,
                property_contact_email, ticket_type
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $ticketId,
            $vehicleId,
            $vehicle['property'],
            $user['id'],
            $user['username'],
            $issuedAt,
            $customNote ?: null,
            $vehicle['year'],
            $vehicle['color'],
            $vehicle['make'],
            $vehicle['model'],
            $vehicle['tag_number'],
            $vehicle['plate_number'],
            $property['name'],
            $property['address'],
            $property['contact_name'],
            $property['contact_phone'],
            $property['contact_email'],
            $ticketType
        ]);
    } else {
        // Old schema without ticket_type field
        $stmt = $db->prepare("
            INSERT INTO violation_tickets (
                id, vehicle_id, property, issued_by_user_id, issued_by_username, issued_at,
                custom_note, vehicle_year, vehicle_color, vehicle_make, vehicle_model,
                tag_number, plate_number,
                property_name, property_address, property_contact_name, property_contact_phone,
                property_contact_email
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $ticketId,
            $vehicleId,
            $vehicle['property'],
            $user['id'],
            $user['username'],
            $issuedAt,
            $customNote ?: null,
            $vehicle['year'],
            $vehicle['color'],
            $vehicle['make'],
            $vehicle['model'],
            $vehicle['tag_number'],
            $vehicle['plate_number'],
            $property['name'],
            $property['address'],
            $property['contact_name'],
            $property['contact_phone'],
            $property['contact_email']
        ]);
    }
    
    // Insert violation items
    $displayOrder = 0;
    $validViolationsInserted = 0;
    
    foreach ($violationIds as $violationId) {
        // Fetch violation name
        $stmt = $db->prepare("SELECT name FROM violations WHERE id = ? AND is_active = 1");
        $stmt->execute([$violationId]);
        $violation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($violation) {
            $stmt = $db->prepare("
                INSERT INTO violation_ticket_items (ticket_id, violation_id, description, display_order)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$ticketId, $violationId, $violation['name'], $displayOrder++]);
            $validViolationsInserted++;
        }
    }
    
    // Insert custom note if provided
    if (!empty($customNote)) {
        $stmt = $db->prepare("
            INSERT INTO violation_ticket_items (ticket_id, violation_id, description, display_order)
            VALUES (?, NULL, ?, ?)
        ");
        $stmt->execute([$ticketId, $customNote, $displayOrder]);
        $validViolationsInserted++;
    }
    
    // Verify at least one valid violation was inserted
    if ($validViolationsInserted === 0) {
        $db->rollBack();
        http_response_code(400);
        echo json_encode(['error' => 'No valid violations selected']);
        exit;
    }
    
    $db->commit();
    
    // Audit log (safe - function may not exist)
    if (function_exists('auditLog')) {
        try {
            if (function_exists('auditLog')) {
                try {
                    auditLog('create_violation', 'violation_tickets', $ticketId, "Created violation ticket for vehicle: {$vehicle['make']} {$vehicle['model']}");
                } catch (Exception $e) {
                    error_log("Audit log error: " . $e->getMessage());
                }
            }
        } catch (Exception $e) {
            error_log("Audit log failed: " . $e->getMessage());
        }
    }
    
    echo json_encode([
        'success' => true,
        'ticketId' => $ticketId,
        'message' => 'Violation ticket created successfully'
    ]);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Violation create error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to create violation ticket']);
}
