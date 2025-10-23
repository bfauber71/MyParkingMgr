<?php
/**
 * Import Vehicles API Endpoint
 * POST /api/vehicles-import
 */

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';

Session::start();

if (!Session::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user = Session::user();

// Only Admin and User roles can import vehicles (case-insensitive)
$role = strtolower($user['role']);
if ($role !== 'admin' && $role !== 'user') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden - Only Admin and User roles can import vehicles']);
    exit;
}

if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded or upload error']);
    exit;
}

$db = Database::getInstance();

try {
    $file = fopen($_FILES['csv']['tmp_name'], 'r');
    
    // Read and validate header row
    $headers = fgetcsv($file);
    if (!$headers || count($headers) < 1) {
        throw new Exception('Invalid CSV format');
    }
    
    $imported = 0;
    $errors = [];
    $row = 1; // Start at 1 (header row)
    
    while (($data = fgetcsv($file)) !== false) {
        $row++;
        
        // Skip empty rows
        if (empty(array_filter($data))) {
            continue;
        }
        
        // Map CSV columns to database fields (order from export)
        $vehicle = [
            'property' => $data[0] ?? '',
            'tag_number' => $data[1] ?? null,
            'plate_number' => $data[2] ?? null,
            'state' => $data[3] ?? null,
            'make' => $data[4] ?? null,
            'model' => $data[5] ?? null,
            'color' => $data[6] ?? null,
            'year' => $data[7] ?? null,
            'apt_number' => $data[8] ?? null,
            'owner_name' => $data[9] ?? null,
            'owner_phone' => $data[10] ?? null,
            'owner_email' => $data[11] ?? null,
            'reserved_space' => $data[12] ?? null
        ];
        
        // Validate required field
        if (empty($vehicle['property'])) {
            $errors[] = "Row $row: Property is required";
            continue;
        }
        
        // Check if property exists
        $propCheck = $db->prepare("SELECT name FROM properties WHERE name = ?");
        $propCheck->execute([$vehicle['property']]);
        if (!$propCheck->fetch()) {
            $errors[] = "Row $row: Property '{$vehicle['property']}' does not exist";
            continue;
        }
        
        // For non-Admin users, check if they have access to this property (case-insensitive)
        if (strcasecmp($user['role'], 'admin') !== 0) {
            $accessCheck = $db->prepare("
                SELECT 1 FROM user_assigned_properties uap
                INNER JOIN properties p ON uap.property_id = p.id
                WHERE uap.user_id = ? AND p.name = ?
            ");
            $accessCheck->execute([$user['id'], $vehicle['property']]);
            if (!$accessCheck->fetch()) {
                $errors[] = "Row $row: You don't have access to property '{$vehicle['property']}'";
                continue;
            }
        }
        
        // Insert vehicle
        $stmt = $db->prepare("
            INSERT INTO vehicles (
                id, property, tag_number, plate_number, state, make, model, color, year,
                apt_number, owner_name, owner_phone, owner_email, reserved_space
            ) VALUES (
                UUID(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ");
        
        $stmt->execute([
            $vehicle['property'],
            $vehicle['tag_number'],
            $vehicle['plate_number'],
            $vehicle['state'],
            $vehicle['make'],
            $vehicle['model'],
            $vehicle['color'],
            $vehicle['year'],
            $vehicle['apt_number'],
            $vehicle['owner_name'],
            $vehicle['owner_phone'],
            $vehicle['owner_email'],
            $vehicle['reserved_space']
        ]);
        
        $imported++;
    }
    
    fclose($file);
    
    echo json_encode([
        'success' => true,
        'imported' => $imported,
        'errors' => $errors,
        'message' => "$imported vehicles imported successfully" . (count($errors) > 0 ? " with " . count($errors) . " errors" : "")
    ]);
    
} catch (Exception $e) {
    error_log("Import Vehicles Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Import failed: ' . $e->getMessage()]);
}
