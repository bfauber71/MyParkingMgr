<?php
/**
 * Ticket Close API Endpoint
 * POST /api/ticket-close
 * Close a violation ticket by marking fine as collected or dismissed
 */

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';

requireAuth();
requirePermission(MODULE_DATABASE, ACTION_CREATE_DELETE);

$input = json_decode(file_get_contents('php://input'), true);
$ticketId = $input['ticketId'] ?? null;
$disposition = $input['disposition'] ?? null;

if (empty($ticketId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Ticket ID is required']);
    exit;
}

if (!in_array($disposition, ['collected', 'dismissed'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid disposition. Must be collected or dismissed']);
    exit;
}

$db = Database::getInstance();
$user = Session::user();
$userId = $user['id'];

try {
    // Check if status column exists
    $hasStatus = false;
    try {
        $checkStmt = $db->query("SHOW COLUMNS FROM violation_tickets LIKE 'status'");
        $hasStatus = $checkStmt->rowCount() > 0;
    } catch (PDOException $e) {
        $hasStatus = false;
    }
    
    if (!$hasStatus) {
        http_response_code(400);
        echo json_encode(['error' => 'Ticket status management not available. Please run database migrations.']);
        exit;
    }
    
    // Get current ticket status
    $stmt = $db->prepare("SELECT status, property FROM violation_tickets WHERE id = ?");
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['error' => 'Ticket not found']);
        exit;
    }
    
    // Check if already closed
    if ($ticket['status'] === 'closed') {
        http_response_code(400);
        echo json_encode(['error' => 'Ticket is already closed']);
        exit;
    }
    
    // Check property access
    $stmt = $db->prepare("SELECT id FROM properties WHERE name = ?");
    $stmt->execute([$ticket['property']]);
    $propertyData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($propertyData && !canAccessProperty($propertyData['id'])) {
        http_response_code(403);
        echo json_encode(['error' => 'You do not have access to this property']);
        exit;
    }
    
    // Update ticket status
    $stmt = $db->prepare("
        UPDATE violation_tickets 
        SET status = 'closed',
            fine_disposition = ?,
            closed_at = NOW(),
            closed_by_user_id = ?
        WHERE id = ?
    ");
    
    $stmt->execute([$disposition, $userId, $ticketId]);
    
    // Audit log
    if (function_exists('auditLog')) { 
        try { 
            auditLog('close_ticket', 'violation_tickets', $ticketId, "Closed ticket with disposition: $disposition"); 
        } catch (Exception $e) { 
            error_log("Audit log error: " . $e->getMessage()); 
        } 
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Ticket marked as $disposition"
    ]);
} catch (PDOException $e) {
    error_log("Ticket close error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
