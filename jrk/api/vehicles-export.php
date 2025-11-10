<?php
/**
 * Export Vehicles API Endpoint
 * GET /api/vehicles-export
 */

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';

Session::start();

// Require authentication and view permission for vehicles
requirePermission(MODULE_VEHICLES, ACTION_VIEW);

$db = Database::getInstance();
$user = Session::user();

try {
    // Get accessible vehicles based on role (case-insensitive)
    $role = strtolower($user['role']);
    if ($role === 'admin' || $role === 'operator') {
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
    
    // Generate CSV
    $filename = 'vehicles_' . date('Y-m-d_His') . '.csv';
    
    // iOS Safari-compatible headers - use octet-stream with nosniff
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // Write header row
    fputcsv($output, [
        'Property', 'Tag Number', 'Plate Number', 'State', 'Make', 'Model', 
        'Color', 'Year', 'Apt Number', 'Owner Name', 'Owner Phone', 
        'Owner Email', 'Reserved Space'
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
            $vehicle['reserved_space'] ?? ''
        ], ',', '"', '\\');
    }
    
    fclose($output);
    
} catch (PDOException $e) {
    error_log("Export Vehicles Error: " . $e->getMessage());
    http_response_code(500);
    echo "Error exporting vehicles";
}
