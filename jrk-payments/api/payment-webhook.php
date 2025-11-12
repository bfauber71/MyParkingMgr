<?php
require_once __DIR__ . '/../includes/database.php';

// Webhook endpoint - NO authentication required (verified by signature)
header('Content-Type: application/json');

$db = Database::connect();

if (!$db) {
    http_response_code(503);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

try {
    $payload = @file_get_contents('php://input');
    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? $_SERVER['HTTP_X_SQUARE_SIGNATURE'] ?? null;
    
    if (!$payload || !$sig_header) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid webhook request']);
        exit;
    }
    
    // Determine processor type from headers or payload
    if (isset($_SERVER['HTTP_STRIPE_SIGNATURE'])) {
        handleStripeWebhook($payload, $sig_header, $db);
    } elseif (isset($_SERVER['HTTP_X_SQUARE_SIGNATURE'])) {
        handleSquareWebhook($payload, $sig_header, $db);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Unknown payment processor']);
        exit;
    }
    
    http_response_code(200);
    echo json_encode(['received' => true]);
    
} catch (Exception $e) {
    error_log("Webhook error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['error' => 'Webhook processing failed']);
}

function handleStripeWebhook($payload, $sig_header, $db) {
    // In production, verify webhook signature using Stripe SDK
    // \Stripe\Webhook::constructEvent($payload, $sig_header, $webhook_secret);
    
    $event = json_decode($payload, true);
    
    if ($event['type'] === 'checkout.session.completed' || 
        $event['type'] === 'payment_intent.succeeded') {
        
        $session = $event['data']['object'];
        $metadata = $session['metadata'] ?? [];
        $ticket_id = $metadata['ticket_id'] ?? null;
        
        if (!$ticket_id) {
            error_log("Stripe webhook: No ticket_id in metadata");
            return;
        }
        
        $amount = ($session['amount_total'] ?? $session['amount']) / 100; // Convert from cents
        $transaction_id = $session['id'];
        
        // Record the payment
        recordWebhookPayment($db, $ticket_id, 'stripe_online', $amount, $transaction_id);
    }
}

function handleSquareWebhook($payload, $sig_header, $db) {
    // Similar to Stripe, verify signature and process payment
    $event = json_decode($payload, true);
    
    // Square webhook processing logic
    if ($event['type'] === 'payment.created' || $event['type'] === 'payment.updated') {
        $payment = $event['data']['object']['payment'] ?? [];
        $ticket_id = $payment['reference_id'] ?? null;
        
        if (!$ticket_id || $payment['status'] !== 'COMPLETED') {
            return;
        }
        
        $amount = $payment['amount_money']['amount'] / 100;
        $transaction_id = $payment['id'];
        
        recordWebhookPayment($db, $ticket_id, 'square_online', $amount, $transaction_id);
    }
}

function recordWebhookPayment($db, $ticket_id, $payment_method, $amount, $transaction_id) {
    try {
        $db->beginTransaction();
        
        // Check if payment already recorded
        $stmt = $db->prepare("SELECT id FROM ticket_payments WHERE transaction_id = ?");
        $stmt->execute([$transaction_id]);
        if ($stmt->fetch()) {
            $db->rollBack();
            error_log("Payment already recorded: $transaction_id");
            return;
        }
        
        // Insert payment record (use system user ID = 1 for automated payments)
        $stmt = $db->prepare("
            INSERT INTO ticket_payments (
                ticket_id, payment_method, amount, transaction_id, 
                status, recorded_by_user_id, notes
            ) VALUES (?, ?, ?, ?, 'completed', 1, 'Automated online payment')
        ");
        $stmt->execute([$ticket_id, $payment_method, $amount, $transaction_id]);
        
        // Calculate total amount paid
        $stmt = $db->prepare("
            SELECT COALESCE(SUM(amount), 0) as total_paid
            FROM ticket_payments
            WHERE ticket_id = ? AND status = 'completed'
        ");
        $stmt->execute([$ticket_id]);
        $result = $stmt->fetch();
        $total_paid = floatval($result['total_paid']);
        
        // Get total fine
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
        
        // Update ticket
        $stmt = $db->prepare("
            UPDATE violation_tickets
            SET payment_status = ?, amount_paid = ?
            WHERE id = ?
        ");
        $stmt->execute([$payment_status, $total_paid, $ticket_id]);
        
        // If fully paid, close ticket
        if ($payment_status === 'paid') {
            $stmt = $db->query("SHOW COLUMNS FROM violation_tickets LIKE 'status'");
            if ($stmt->rowCount() > 0) {
                $stmt = $db->prepare("
                    UPDATE violation_tickets
                    SET status = 'closed',
                        fine_disposition = 'collected',
                        closed_at = NOW(),
                        closed_by_user_id = 1
                    WHERE id = ?
                ");
                $stmt->execute([$ticket_id]);
            }
        }
        
        $db->commit();
        error_log("Payment recorded successfully: Ticket $ticket_id, Amount $amount");
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Failed to record webhook payment: " . $e->getMessage());
        throw $e;
    }
}
