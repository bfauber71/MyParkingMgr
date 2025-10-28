<?php
/**
 * Bulk Delete Vehicles by Property
 * DELETE /api/vehicles-bulk-delete
 * Requires: database module with create_delete permission
 */

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';

Session::start();

requirePermission(MODULE_DATABASE, ACTION_CREATE_DELETE);

$data = getJsonInput();

if (empty($data['property'])) {
    jsonResponse(['error' => 'Property name is required'], 400);
}

$property = sanitize($data['property']);

try {
    // Check if property exists
    $propertyCheck = Database::queryOne(
        "SELECT COUNT(*) as count FROM vehicles WHERE property = ?",
        [$property]
    );
    
    if (!$propertyCheck || $propertyCheck['count'] == 0) {
        jsonResponse(['error' => 'No vehicles found for this property'], 404);
    }
    
    $count = $propertyCheck['count'];
    
    // Delete all vehicles for this property
    $sql = "DELETE FROM vehicles WHERE property = ?";
    Database::query($sql, [$property]);
    
    // Audit log
    if (function_exists('auditLog')) { try { auditLog('vehicles_bulk_delete', 'vehicle', null, [
        'property' => $property,
        'count' => $count
    ]); } catch (Exception $e) { error_log("Audit log error: " . $e->getMessage()); } }
    
    jsonResponse([
        'success' => true,
        'message' => "Deleted {$count} vehicle(s) from {$property}",
        'count' => $count
    ]);
} catch (Exception $e) {
    error_log("Bulk delete error: " . $e->getMessage());
    jsonResponse(['error' => 'Failed to delete vehicles'], 500);
}
