<?php
/**
 * Export Vehicles API Endpoint
 * GET /api/vehicles-export
 */

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/session.php';


Session::start();

// Require authentication and view permission for vehicles
requirePermission(MODULE_VEHICLES, ACTION_VIEW);

$db = Database::getInstance();
$user = Session::user();

// Get property filter if specified
$propertyFilter = $_GET['property'] ?? null;
$propertyFilter = $propertyFilter ? trim($propertyFilter) : null;

// If property filter is specified, validate access
if ($propertyFilter) {
    // Check if property exists
    $propCheck = $db->prepare("SELECT id, name FROM properties WHERE name = ?");
    $propCheck->execute([$propertyFilter]);
    $filterProperty = $propCheck->fetch(PDO::FETCH_ASSOC);
    
    if (!$filterProperty) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Property does not exist']);
        exit;
    }
    
    // For non-Admin users, check if they have access to this property
    $role = strtolower($user['role']);
    if ($role !== 'admin' && $role !== 'operator') {
        $accessCheck = $db->prepare("
            SELECT 1 FROM user_assigned_properties uap
            INNER JOIN properties p ON uap.property_id = p.id
            WHERE uap.user_id = ? AND p.name = ?
        ");
        $accessCheck->execute([$user['id'], $propertyFilter]);
        if (!$accessCheck->fetch()) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Access denied to property']);
            exit;
        }
    }
}

try {
    // Get accessible vehicles based on role and property filter
    $role = strtolower($user['role']);
    
    if ($propertyFilter) {
        // Export vehicles from specific property
        $stmt = $db->prepare("SELECT * FROM vehicles WHERE property = ? ORDER BY created_at DESC");
        $stmt->execute([$propertyFilter]);
    } else if ($role === 'admin' || $role === 'operator') {
        // Admin and Operator can export all vehicles
        $stmt = $db->prepare("SELECT * FROM vehicles ORDER BY property, created_at DESC");
        $stmt->execute();
    } else {
        // Regular users only export vehicles from assigned properties
        $stmt = $db->prepare("
            SELECT v.* 
            FROM vehicles v
            INNER JOIN user_assigned_properties uap ON v.property = (
                SELECT name FROM properties WHERE id = uap.property_id
            )
            WHERE uap.user_id = ?
            ORDER BY v.property, v.created_at DESC
        ");
        $stmt->execute([$user['id']]);
    }
    
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generate CSV filename (include property name if filtering)
    if ($propertyFilter) {
        // Sanitize property name for filename (remove special chars)
        $safePropertyName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $propertyFilter);
        $filename = 'vehicles_' . $safePropertyName . '_' . date('Y-m-d_His') . '.csv';
    } else {
        $filename = 'vehicles_' . date('Y-m-d_His') . '.csv';
    }
    
    // iOS Safari-compatible headers - use octet-stream with nosniff
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // Write header row (includes guest pass fields)
    fputcsv($output, [
        'Property', 'Tag Number', 'Plate Number', 'State', 'Make', 'Model', 
        'Color', 'Year', 'Apt Number', 'Owner Name', 'Owner Phone', 
        'Owner Email', 'Reserved Space', 'Resident', 'Guest', 'Guest Of', 'Expiration Date'
    ], ',', '"', '\\');
    
    // Write data rows
    foreach ($vehicles as $vehicle) {
        fputcsv($output, [
            $vehicle['property'] ?? '',
            $vehicle['tag_number'] ?? '',
            $vehicle['plate_number'] ?? '',
            $vehicle['state'] ?? '',
            $vehicle['make'] ?? '',
            $vehicle['model'] ?? '',
            $vehicle['color'] ?? '',
            $vehicle['year'] ?? '',
            $vehicle['apt_number'] ?? '',
            $vehicle['owner_name'] ?? '',
            $vehicle['owner_phone'] ?? '',
            $vehicle['owner_email'] ?? '',
            $vehicle['reserved_space'] ?? '',
            $vehicle['resident'] ?? '1',
            $vehicle['guest'] ?? '0',
            $vehicle['guest_of'] ?? '',
            $vehicle['expiration_date'] ?? ''
        ], ',', '"', '\\');
    }
    
    fclose($output);
    
} catch (PDOException $e) {
    error_log("Export Vehicles Error: " . $e->getMessage());
    http_response_code(500);
    echo "Error exporting vehicles";
}
