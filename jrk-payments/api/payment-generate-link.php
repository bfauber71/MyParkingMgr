<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../lib/CryptoHelper.php';

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
    $data = json_decode(file_get_contents('php://input'), true);
    
    $ticket_id = $data['ticket_id'] ?? null;
    
    if (!$ticket_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing ticket_id']);
        exit;
    }
    
    // Get ticket details
    $stmt = $db->prepare("
        SELECT vt.*, v.plate_number, v.tag_number, p.name as property_name
        FROM violation_tickets vt
        LEFT JOIN vehicles v ON vt.vehicle_id = v.id
        LEFT JOIN properties p ON vt.property = p.id
        WHERE vt.id = ?
    ");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch();
    
    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['error' => 'Ticket not found']);
        exit;
    }
    
    // Get property payment settings
    $stmt = $db->prepare("
        SELECT * FROM payment_settings
        WHERE property_id = ?
    ");
    $stmt->execute([$ticket['property']]);
    $settings = $stmt->fetch();
    
    if (!$settings || $settings['processor_type'] === 'disabled') {
        http_response_code(400);
        echo json_encode(['error' => 'Payment processing not enabled for this property']);
        exit;
    }
    
    // Calculate total fine
    $stmt = $db->prepare("
        SELECT SUM(COALESCE(v.fine_amount, 0)) as total_fine
        FROM violation_ticket_items vti
        LEFT JOIN violations v ON vti.violation_id = v.id
        WHERE vti.ticket_id = ?
    ");
    $stmt->execute([$ticket_id]);
    $result = $stmt->fetch();
    $total_fine = floatval($result['total_fine']);
    
    if ($total_fine <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'No fine amount for this ticket']);
        exit;
    }
    
    // Check payment status
    $stmt = $db->query("SHOW COLUMNS FROM violation_tickets LIKE 'payment_status'");
    $hasPaymentColumns = $stmt->rowCount() > 0;
    
    if ($hasPaymentColumns) {
        $stmt = $db->prepare("SELECT payment_status, amount_paid FROM violation_tickets WHERE id = ?");
        $stmt->execute([$ticket_id]);
        $payment_status = $stmt->fetch();
        
        if ($payment_status['payment_status'] === 'paid') {
            http_response_code(400);
            echo json_encode(['error' => 'Ticket already paid in full']);
            exit;
        }
        
        // Adjust amount if partial payment exists
        $total_fine -= floatval($payment_status['amount_paid']);
    }
    
    $payment_link_url = null;
    $payment_link_id = null;
    
    // Generate payment link based on processor type
    if ($settings['processor_type'] === 'stripe') {
        $payment_link_url = generateStripePaymentLink($settings, $ticket, $total_fine, $db);
        $payment_link_id = "stripe_" . substr(md5($payment_link_url), 0, 16);
        
    } elseif ($settings['processor_type'] === 'square') {
        $payment_link_url = generateSquarePaymentLink($settings, $ticket, $total_fine, $db);
        $payment_link_id = "square_" . substr(md5($payment_link_url), 0, 16);
        
    } elseif ($settings['processor_type'] === 'paypal') {
        $payment_link_url = generatePayPalPaymentLink($settings, $ticket, $total_fine, $db);
        $payment_link_id = "paypal_" . substr(md5($payment_link_url), 0, 16);
        
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Unsupported payment processor']);
        exit;
    }
    
    // Update ticket with payment link
    $stmt = $db->prepare("
        UPDATE violation_tickets
        SET payment_link_id = ?
        WHERE id = ?
    ");
    $stmt->execute([$payment_link_id, $ticket_id]);
    
    echo json_encode([
        'success' => true,
        'payment_link_url' => $payment_link_url,
        'payment_link_id' => $payment_link_id,
        'amount' => $total_fine,
        'processor' => $settings['processor_type']
    ]);
    
} catch (Exception $e) {
    error_log("Payment link generation error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to generate payment link: ' . $e->getMessage()]);
}

function generateStripePaymentLink($settings, $ticket, $amount, $db) {
    // Decrypt API keys using Defuse PHP Encryption
    try {
        $api_secret = CryptoHelper::decrypt($settings['api_secret_encrypted']);
    } catch (Exception $e) {
        throw new Exception('Failed to decrypt Stripe API key: ' . $e->getMessage());
    }
    
    // In production, you would use the Stripe PHP SDK:
    // \Stripe\Stripe::setApiKey($api_secret);
    // $paymentLink = \Stripe\PaymentLink::create([
    //     'line_items' => [[
    //         'price_data' => [
    //             'currency' => 'usd',
    //             'product_data' => ['name' => 'Parking Violation Fine'],
    //             'unit_amount' => $amount * 100, // Convert to cents
    //         ],
    //         'quantity' => 1,
    //     ]],
    //     'metadata' => ['ticket_id' => $ticket['id']],
    // ]);
    // return $paymentLink->url;
    
    $ticket_description = str_replace('{ticket_id}', $ticket['id'], $settings['payment_description_template'] ?? 'Parking Violation Fine');
    
    // For demonstration/testing, return a mock URL
    // In production, this would be the actual Stripe payment link
    $base_url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
    return $base_url . "/payment/stripe?ticket=" . $ticket['id'] . "&amount=" . ($amount * 100);
}

function generateSquarePaymentLink($settings, $ticket, $amount, $db) {
    // Decrypt API keys
    try {
        $api_key = CryptoHelper::decrypt($settings['api_key_encrypted']);
    } catch (Exception $e) {
        throw new Exception('Failed to decrypt Square API key: ' . $e->getMessage());
    }
    
    // In production, use Square PHP SDK
    // For demonstration purposes
    $base_url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
    return $base_url . "/payment/square?ticket=" . $ticket['id'] . "&amount=" . ($amount * 100);
}

function generatePayPalPaymentLink($settings, $ticket, $amount, $db) {
    // Decrypt API keys
    try {
        $api_key = CryptoHelper::decrypt($settings['api_key_encrypted']);
    } catch (Exception $e) {
        throw new Exception('Failed to decrypt PayPal API key: ' . $e->getMessage());
    }
    
    // In production, use PayPal SDK
    $base_url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
    return $base_url . "/payment/paypal?ticket=" . $ticket['id'] . "&amount=" . $amount;
}
