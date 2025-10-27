<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';

Session::start();

// Require authentication and edit permission for properties
requirePermission(MODULE_PROPERTIES, ACTION_EDIT);

$user = Session::user();

$input = json_decode(file_get_contents('php://input'), true);

$propertyId = trim($input['id'] ?? '');
$name = trim($input['name'] ?? '');
$address = trim($input['address'] ?? '');
$customTicketText = trim($input['custom_ticket_text'] ?? '');
$contacts = $input['contacts'] ?? [];

if (empty($propertyId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Property ID is required']);
    exit;
}

if (empty($name)) {
    http_response_code(400);
    echo json_encode(['error' => 'Property name is required']);
    exit;
}

if (empty($contacts) || !is_array($contacts)) {
    http_response_code(400);
    echo json_encode(['error' => 'At least one contact is required']);
    exit;
}

if (count($contacts) > 3) {
    http_response_code(400);
    echo json_encode(['error' => 'Maximum 3 contacts allowed']);
    exit;
}

foreach ($contacts as $idx => $contact) {
    $contactName = trim($contact['name'] ?? '');
    if (empty($contactName)) {
        http_response_code(400);
        echo json_encode(['error' => "Contact " . ($idx + 1) . " name is required"]);
        exit;
    }
}

$db = Database::getInstance();

try {
    $db->beginTransaction();
    
    $stmt = $db->prepare("SELECT name FROM properties WHERE id = ?");
    $stmt->execute([$propertyId]);
    $oldProperty = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$oldProperty) {
        $db->rollBack();
        http_response_code(404);
        echo json_encode(['error' => 'Property not found']);
        exit;
    }
    
    if ($name !== $oldProperty['name']) {
        $stmt = $db->prepare("SELECT id FROM properties WHERE name = ? AND id != ?");
        $stmt->execute([$name, $propertyId]);
        if ($stmt->fetch()) {
            $db->rollBack();
            http_response_code(400);
            echo json_encode(['error' => 'Property name already exists']);
            exit;
        }
    }
    
    $stmt = $db->prepare("
        UPDATE properties 
        SET name = ?, address = ?, custom_ticket_text = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$name, $address, $customTicketText ?: null, $propertyId]);
    
    if ($name !== $oldProperty['name']) {
        $stmt = $db->prepare("UPDATE vehicles SET property = ? WHERE property = ?");
        $stmt->execute([$name, $oldProperty['name']]);
    }
    
    $stmt = $db->prepare("DELETE FROM property_contacts WHERE property_id = ?");
    $stmt->execute([$propertyId]);
    
    $contactStmt = $db->prepare("
        INSERT INTO property_contacts (property_id, name, phone, email, position, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, NOW(), NOW())
    ");
    
    foreach ($contacts as $position => $contact) {
        $contactName = trim($contact['name'] ?? '');
        $contactPhone = trim($contact['phone'] ?? '');
        $contactEmail = trim($contact['email'] ?? '');
        
        $contactStmt->execute([
            $propertyId,
            $contactName,
            $contactPhone ?: null,
            $contactEmail ?: null,
            $position
        ]);
    }
    
    $db->commit();
    
    auditLog('update_property', 'properties', $propertyId, "Updated property: $name with " . count($contacts) . " contact(s)");
    
    echo json_encode([
        'success' => true,
        'message' => 'Property updated successfully'
    ]);
} catch (PDOException $e) {
    $db->rollBack();
    error_log("Property Update Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
