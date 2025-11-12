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
$vehicleId = $_GET['vehicle_id'] ?? '';

if (empty($vehicleId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Vehicle ID is required']);
    exit;
}

$db = Database::getInstance();

try {
    // Get vehicle and check property access
    $stmt = $db->prepare("SELECT property FROM vehicles WHERE id = ?");
    $stmt->execute([$vehicleId]);
    $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$vehicle) {
        http_response_code(404);
        echo json_encode(['error' => 'Vehicle not found']);
        exit;
    }
    
    // Check property access
    $stmt = $db->prepare("SELECT id FROM properties WHERE name = ?");
    $stmt->execute([$vehicle['property']]);
    $propertyData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($propertyData && !canAccessProperty($propertyData['id'])) {
        http_response_code(403);
        echo json_encode(['error' => 'You do not have access to this vehicle\'s violations']);
        exit;
    }
    
    // Check if violation_tickets table exists
    $tableCheck = $db->query("SHOW TABLES LIKE 'violation_tickets'");
    $tableExists = $tableCheck->fetch() !== false;
    
    if (!$tableExists) {
        // Table doesn't exist yet - return empty array with helpful message
        echo json_encode([
            'success' => true,
            'count' => 0,
            'tickets' => [],
            'message' => 'Violation tracking not yet set up. Run migration script to enable.'
        ]);
        exit;
    }
    
    // Check if status columns exist (for backward compatibility)
    $hasStatus = false;
    try {
        $checkStmt = $db->query("SHOW COLUMNS FROM violation_tickets LIKE 'status'");
        $hasStatus = $checkStmt->rowCount() > 0;
    } catch (PDOException $e) {
        $hasStatus = false;
    }
    
    // Build SELECT fields with backward compatibility
    $statusField = $hasStatus ? "status," : "'active' as status,";
    $dispositionField = $hasStatus ? "fine_disposition," : "NULL as fine_disposition,";
    $closedAtField = $hasStatus ? "closed_at," : "NULL as closed_at,";
    $closedByField = $hasStatus ? "closed_by_user_id" : "NULL as closed_by_user_id";
    
    // Fetch violation tickets for this vehicle (limit to 100 most recent)
    $stmt = $db->prepare("
        SELECT 
            id,
            issued_at,
            issued_by_username,
            custom_note,
            vehicle_year,
            vehicle_color,
            vehicle_make,
            vehicle_model,
            $statusField
            $dispositionField
            $closedAtField
            $closedByField
        FROM violation_tickets
        WHERE vehicle_id = ?
        ORDER BY issued_at DESC
        LIMIT 100
    ");
    $stmt->execute([$vehicleId]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // For each ticket, fetch the violations
    foreach ($tickets as &$ticket) {
        $stmt = $db->prepare("
            SELECT description
            FROM violation_ticket_items
            WHERE ticket_id = ?
            ORDER BY display_order ASC
        ");
        $stmt->execute([$ticket['id']]);
        $violations = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $ticket['violations'] = $violations;
    }
    unset($ticket); // Break reference
    
    echo json_encode([
        'success' => true,
        'count' => count($tickets),
        'tickets' => $tickets
    ]);
} catch (PDOException $e) {
    error_log("Violation history error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'details' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
}
