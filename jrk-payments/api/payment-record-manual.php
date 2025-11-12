<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$db = Database::connect();

if (!$db) {
    http_response_code(503);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required_fields = ['ticket_id', 'payment_method', 'amount'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: $field"]);
            exit;
        }
    }
    
    $ticket_id = $data['ticket_id'];
    $payment_method = $data['payment_method'];
    $amount = floatval($data['amount']);
    $check_number = $data['check_number'] ?? null;
    $notes = $data['notes'] ?? null;
    $user_id = $_SESSION['user_id'];
    
    // Validate payment method
    $valid_methods = ['cash', 'check', 'card_manual', 'stripe_online', 'square_online', 'paypal_online'];
    if (!in_array($payment_method, $valid_methods)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid payment method']);
        exit;
    }
    
    // Validate amount is positive
    if ($amount <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Amount must be greater than 0']);
        exit;
    }
    
    // Get ticket details
    $stmt = $db->prepare("SELECT id FROM violation_tickets WHERE id = ?");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch();
    
    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['error' => 'Ticket not found']);
        exit;
    }
    
    // Check if payment_status column exists
    $stmt = $db->query("SHOW COLUMNS FROM violation_tickets LIKE 'payment_status'");
    $hasPaymentColumns = $stmt->rowCount() > 0;
    
    if (!$hasPaymentColumns) {
        http_response_code(503);
        echo json_encode(['error' => 'Payment system not installed. Run migration first.']);
        exit;
    }
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // Insert payment record
        $stmt = $db->prepare("
            INSERT INTO ticket_payments (
                ticket_id, payment_method, amount, check_number, 
                status, recorded_by_user_id, notes
            ) VALUES (?, ?, ?, ?, 'completed', ?, ?)
        ");
        $stmt->execute([
            $ticket_id,
            $payment_method,
            $amount,
            $check_number,
            $user_id,
            $notes
        ]);
        
        $payment_id = $db->lastInsertId();
        
        // Calculate total amount paid for this ticket
        $stmt = $db->prepare("
            SELECT COALESCE(SUM(amount), 0) as total_paid
            FROM ticket_payments
            WHERE ticket_id = ? AND status = 'completed'
        ");
        $stmt->execute([$ticket_id]);
        $result = $stmt->fetch();
        $total_paid = floatval($result['total_paid']);
        
        // Get total fine amount (from violation items)
        $stmt = $db->prepare("
            SELECT SUM(COALESCE(v.fine_amount, 0)) as total_fine
            FROM violation_ticket_items vti
            LEFT JOIN violations v ON vti.violation_id = v.id
            WHERE vti.ticket_id = ?
        ");
        $stmt->execute([$ticket_id]);
        $result = $stmt->fetch();
        $total_fine = floatval($result['total_fine']);
        
        // Determine payment status
        if ($total_paid >= $total_fine) {
            $payment_status = 'paid';
        } elseif ($total_paid > 0) {
            $payment_status = 'partial';
        } else {
            $payment_status = 'unpaid';
        }
        
        // Update ticket payment status
        $stmt = $db->prepare("
            UPDATE violation_tickets
            SET payment_status = ?, amount_paid = ?
            WHERE id = ?
        ");
        $stmt->execute([$payment_status, $total_paid, $ticket_id]);
        
        // If fully paid, close the ticket
        if ($payment_status === 'paid') {
            // Check if status column exists
            $stmt = $db->query("SHOW COLUMNS FROM violation_tickets LIKE 'status'");
            $hasStatus = $stmt->rowCount() > 0;
            
            if ($hasStatus) {
                $stmt = $db->prepare("
                    UPDATE violation_tickets
                    SET status = 'closed',
                        fine_disposition = 'collected',
                        closed_at = NOW(),
                        closed_by_user_id = ?
                    WHERE id = ?
                ");
                $stmt->execute([$user_id, $ticket_id]);
            }
        }
        
        // Log audit trail
        $stmt = $db->prepare("
            INSERT INTO audit_logs (user_id, action_type, action_details, ip_address)
            VALUES (?, 'payment_recorded', ?, ?)
        ");
        $stmt->execute([
            $user_id,
            json_encode([
                'ticket_id' => $ticket_id,
                'payment_id' => $payment_id,
                'amount' => $amount,
                'method' => $payment_method,
                'payment_status' => $payment_status
            ]),
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment recorded successfully',
            'payment_id' => $payment_id,
            'total_paid' => $total_paid,
            'total_fine' => $total_fine,
            'payment_status' => $payment_status,
            'ticket_closed' => $payment_status === 'paid'
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Payment record error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to record payment']);
}
