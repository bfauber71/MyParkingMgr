<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/database.php';

requireAuth();

header('Content-Type: application/json');

$db = Database::getInstance();

if (!$db) {
    http_response_code(503);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $ticket_id = $data['ticket_id'] ?? null;
    $payment_url = $data['payment_url'] ?? null;
    
    if (!$ticket_id || !$payment_url) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    
    // Create QR codes directory if it doesn't exist
    $qr_dir = __DIR__ . '/../qrcodes';
    if (!file_exists($qr_dir)) {
        mkdir($qr_dir, 0755, true);
    }
    
    // Generate QR code filename
    $filename = "ticket_{$ticket_id}_payment_" . time() . ".png";
    $filepath = "{$qr_dir}/{$filename}";
    $relative_path = "qrcodes/{$filename}";
    
    // Generate QR code using Google Charts API (simple, no dependencies)
    $qr_url = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=" . urlencode($payment_url) . "&choe=UTF-8";
    
    // Download QR code image
    $qr_image = file_get_contents($qr_url);
    
    if ($qr_image === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to generate QR code']);
        exit;
    }
    
    file_put_contents($filepath, $qr_image);
    
    // Store QR code reference in database
    $stmt = $db->prepare("
        INSERT INTO qr_codes (ticket_id, file_path, payment_url)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$ticket_id, $relative_path, $payment_url]);
    
    // Update ticket with QR generation timestamp
    $stmt = $db->prepare("
        UPDATE violation_tickets
        SET qr_code_generated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$ticket_id]);
    
    echo json_encode([
        'success' => true,
        'qr_code_path' => $relative_path,
        'qr_code_url' => "/qrcodes/{$filename}",
        'payment_url' => $payment_url
    ]);
    
} catch (Exception $e) {
    error_log("QR code generation error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to generate QR code']);
}
