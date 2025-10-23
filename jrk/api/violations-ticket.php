<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/session.php';

Session::start();

if (!Session::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$ticketId = $_GET['id'] ?? '';

if (empty($ticketId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Ticket ID is required']);
    exit;
}

$db = Database::getInstance();

try {
    // Fetch ticket data
    $stmt = $db->prepare("
        SELECT 
            id, vehicle_id, property, issued_by_username, issued_at,
            custom_note, vehicle_year, vehicle_color, vehicle_make, vehicle_model,
            property_name, property_address, property_contact_name, 
            property_contact_phone, property_contact_email
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
    
    // Fetch violation items
    $stmt = $db->prepare("
        SELECT description, display_order
        FROM violation_ticket_items
        WHERE ticket_id = ?
        ORDER BY display_order ASC
    ");
    $stmt->execute([$ticketId]);
    $violations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $ticket['violations'] = $violations;
    
    echo json_encode(['ticket' => $ticket]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
