<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$db = Database::connect();

if (!$db) {
    http_response_code(503);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

try {
    $ticket_id = $_GET['ticket_id'] ?? null;
    
    if (!$ticket_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing ticket_id']);
        exit;
    }
    
    // Check if ticket_payments table exists
    $stmt = $db->query("SHOW TABLES LIKE 'ticket_payments'");
    if ($stmt->rowCount() === 0) {
        http_response_code(503);
        echo json_encode(['error' => 'Payment system not installed. Run migration first.']);
        exit;
    }
    
    // Get all payments for this ticket
    $stmt = $db->prepare("
        SELECT 
            tp.*,
            u.username as recorded_by_username
        FROM ticket_payments tp
        LEFT JOIN users u ON tp.recorded_by_user_id = u.id
        WHERE tp.ticket_id = ?
        ORDER BY tp.payment_date DESC
    ");
    $stmt->execute([$ticket_id]);
    $payments = $stmt->fetchAll();
    
    // Get ticket payment summary
    $stmt = $db->prepare("
        SELECT 
            COALESCE(SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) as total_paid,
            COALESCE(SUM(CASE WHEN status = 'refunded' THEN amount ELSE 0 END), 0) as total_refunded,
            COUNT(*) as payment_count
        FROM ticket_payments
        WHERE ticket_id = ?
    ");
    $stmt->execute([$ticket_id]);
    $summary = $stmt->fetch();
    
    // Get total fine amount
    $stmt = $db->prepare("
        SELECT SUM(COALESCE(v.fine_amount, 0)) as total_fine
        FROM violation_ticket_items vti
        LEFT JOIN violations v ON vti.violation_id = v.id
        WHERE vti.ticket_id = ?
    ");
    $stmt->execute([$ticket_id]);
    $result = $stmt->fetch();
    $total_fine = floatval($result['total_fine']);
    
    echo json_encode([
        'payments' => $payments,
        'summary' => [
            'total_fine' => $total_fine,
            'total_paid' => floatval($summary['total_paid']),
            'total_refunded' => floatval($summary['total_refunded']),
            'balance_due' => max(0, $total_fine - floatval($summary['total_paid'])),
            'payment_count' => intval($summary['payment_count'])
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Payment history error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch payment history']);
}
