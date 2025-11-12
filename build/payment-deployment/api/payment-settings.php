<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../lib/CryptoHelper.php';

requireAuth();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$db = Database::getInstance();

if (!$db) {
    http_response_code(503);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

try {
    // Check if payment_settings table exists
    $stmt = $db->query("SHOW TABLES LIKE 'payment_settings'");
    if ($stmt->rowCount() === 0) {
        http_response_code(503);
        echo json_encode(['error' => 'Payment system not installed. Run migration first.']);
        exit;
    }
    
    switch ($method) {
        case 'GET':
            handleGet($db);
            break;
        case 'POST':
            handlePost($db);
            break;
        case 'PUT':
            handlePut($db);
            break;
        case 'DELETE':
            handleDelete($db);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    error_log("Payment settings error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}

function handleGet($db) {
    // Get payment settings for a specific property or all properties
    $property_id = $_GET['property_id'] ?? null;
    
    if ($property_id) {
        $stmt = $db->prepare("
            SELECT id, property_id, processor_type, publishable_key, is_live_mode,
                   enable_qr_codes, enable_online_payments, payment_description_template,
                   success_redirect_url, failure_redirect_url, allow_cash_payments,
                   allow_check_payments, allow_manual_card, require_check_number,
                   created_at, updated_at
            FROM payment_settings
            WHERE property_id = ?
        ");
        $stmt->execute([$property_id]);
        $settings = $stmt->fetch();
        
        if (!$settings) {
            // Return default settings if none exist
            echo json_encode([
                'processor_type' => 'disabled',
                'enable_qr_codes' => true,
                'enable_online_payments' => false,
                'allow_cash_payments' => true,
                'allow_check_payments' => true,
                'allow_manual_card' => true,
                'require_check_number' => true
            ]);
        } else {
            // Don't send encrypted secrets to frontend
            unset($settings['api_key_encrypted']);
            unset($settings['api_secret_encrypted']);
            unset($settings['webhook_secret_encrypted']);
            
            echo json_encode($settings);
        }
    } else {
        // Get all payment settings (admin only)
        if (!hasPermission('manage_users')) {
            http_response_code(403);
            echo json_encode(['error' => 'Admin access required']);
            exit;
        }
        
        $stmt = $db->query("
            SELECT ps.*, p.name as property_name
            FROM payment_settings ps
            JOIN properties p ON ps.property_id = p.id
            ORDER BY p.name
        ");
        $settings = $stmt->fetchAll();
        
        // Remove encrypted secrets
        foreach ($settings as &$setting) {
            unset($setting['api_key_encrypted']);
            unset($setting['api_secret_encrypted']);
            unset($setting['webhook_secret_encrypted']);
        }
        
        echo json_encode($settings);
    }
}

function handlePost($db) {
    if (!hasPermission('manage_users')) {
        http_response_code(403);
        echo json_encode(['error' => 'Admin access required']);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $required_fields = ['property_id', 'processor_type'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: $field"]);
            exit;
        }
    }
    
    // Encrypt sensitive API keys using Defuse PHP Encryption
    try {
        $api_key_encrypted = isset($data['api_key']) && !empty($data['api_key']) 
            ? CryptoHelper::encrypt($data['api_key']) 
            : null;
        $api_secret_encrypted = isset($data['api_secret']) && !empty($data['api_secret']) 
            ? CryptoHelper::encrypt($data['api_secret']) 
            : null;
        $webhook_secret_encrypted = isset($data['webhook_secret']) && !empty($data['webhook_secret']) 
            ? CryptoHelper::encrypt($data['webhook_secret']) 
            : null;
    } catch (Exception $e) {
        error_log("Encryption error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'error' => 'Encryption failed. Ensure encryption key is configured.',
            'details' => $e->getMessage()
        ]);
        exit;
    }
    
    $stmt = $db->prepare("
        INSERT INTO payment_settings (
            property_id, processor_type, api_key_encrypted, api_secret_encrypted,
            webhook_secret_encrypted, publishable_key, is_live_mode, enable_qr_codes,
            enable_online_payments, payment_description_template, success_redirect_url,
            failure_redirect_url, allow_cash_payments, allow_check_payments,
            allow_manual_card, require_check_number
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            processor_type = VALUES(processor_type),
            api_key_encrypted = VALUES(api_key_encrypted),
            api_secret_encrypted = VALUES(api_secret_encrypted),
            webhook_secret_encrypted = VALUES(webhook_secret_encrypted),
            publishable_key = VALUES(publishable_key),
            is_live_mode = VALUES(is_live_mode),
            enable_qr_codes = VALUES(enable_qr_codes),
            enable_online_payments = VALUES(enable_online_payments),
            payment_description_template = VALUES(payment_description_template),
            success_redirect_url = VALUES(success_redirect_url),
            failure_redirect_url = VALUES(failure_redirect_url),
            allow_cash_payments = VALUES(allow_cash_payments),
            allow_check_payments = VALUES(allow_check_payments),
            allow_manual_card = VALUES(allow_manual_card),
            require_check_number = VALUES(require_check_number)
    ");
    
    $stmt->execute([
        $data['property_id'],
        $data['processor_type'],
        $api_key_encrypted,
        $api_secret_encrypted,
        $webhook_secret_encrypted,
        $data['publishable_key'] ?? null,
        $data['is_live_mode'] ?? false,
        $data['enable_qr_codes'] ?? true,
        $data['enable_online_payments'] ?? false,
        $data['payment_description_template'] ?? 'Parking Violation - Ticket #{ticket_id}',
        $data['success_redirect_url'] ?? null,
        $data['failure_redirect_url'] ?? null,
        $data['allow_cash_payments'] ?? true,
        $data['allow_check_payments'] ?? true,
        $data['allow_manual_card'] ?? true,
        $data['require_check_number'] ?? true
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment settings saved successfully',
        'id' => $db->lastInsertId() ?: $data['property_id']
    ]);
}

function handlePut($db) {
    handlePost($db); // Same logic for update
}

function handleDelete($db) {
    if (!hasPermission('manage_users')) {
        http_response_code(403);
        echo json_encode(['error' => 'Admin access required']);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['property_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing property_id']);
        exit;
    }
    
    $stmt = $db->prepare("DELETE FROM payment_settings WHERE property_id = ?");
    $stmt->execute([$data['property_id']]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment settings deleted successfully'
    ]);
}
