<?php
/**
 * Find and Optionally Delete Duplicate Vehicles
 * POST /api/vehicles-duplicates
 * Requires: database module with view permission (find) or create_delete (delete)
 * 
 * Body:
 * {
 *   "action": "find" | "delete",
 *   "criteria": "plate" | "tag",
 *   "vehicle_ids": [] // required for delete action
 * }
 */

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';

Session::start();

$data = getJsonInput();

$action = $data['action'] ?? 'find';
$criteria = $data['criteria'] ?? 'plate';

if ($action === 'delete') {
    requirePermission(MODULE_DATABASE, ACTION_CREATE_DELETE);
} else {
    requirePermission(MODULE_DATABASE, ACTION_VIEW);
}

if (!in_array($criteria, ['plate', 'tag'])) {
    jsonResponse(['error' => 'Invalid criteria. Must be "plate" or "tag"'], 400);
}

try {
    if ($action === 'find') {
        // Find duplicates based on criteria
        $column = $criteria === 'plate' ? 'plate_number' : 'tag_number';
        
        $sql = "SELECT {$column} as value, COUNT(*) as count, 
                       GROUP_CONCAT(id) as vehicle_ids,
                       GROUP_CONCAT(property) as properties,
                       GROUP_CONCAT(tag_number) as tag_numbers,
                       GROUP_CONCAT(plate_number) as plate_numbers,
                       GROUP_CONCAT(CONCAT(make, ' ', model)) as vehicles
                FROM vehicles 
                WHERE {$column} IS NOT NULL AND {$column} != ''
                GROUP BY {$column}
                HAVING count > 1
                ORDER BY count DESC";
        
        $duplicates = Database::query($sql);
        
        // Format results for frontend
        $formatted = [];
        foreach ($duplicates as $dup) {
            $ids = explode(',', $dup['vehicle_ids']);
            $properties = explode(',', $dup['properties']);
            $tagNumbers = explode(',', $dup['tag_numbers']);
            $plateNumbers = explode(',', $dup['plate_numbers']);
            $vehicles = explode(',', $dup['vehicles']);
            
            $items = [];
            for ($i = 0; $i < count($ids); $i++) {
                $items[] = [
                    'id' => $ids[$i],
                    'property' => $properties[$i] ?? '',
                    'tag_number' => $tagNumbers[$i] ?? '',
                    'plate_number' => $plateNumbers[$i] ?? '',
                    'vehicle' => $vehicles[$i] ?? ''
                ];
            }
            
            $formatted[] = [
                'value' => $dup['value'],
                'count' => $dup['count'],
                'items' => $items
            ];
        }
        
        jsonResponse([
            'duplicates' => $formatted,
            'total_groups' => count($formatted),
            'criteria' => $criteria
        ]);
        
    } elseif ($action === 'delete') {
        // Delete selected duplicate vehicles
        if (empty($data['vehicle_ids']) || !is_array($data['vehicle_ids'])) {
            jsonResponse(['error' => 'vehicle_ids array is required for delete action'], 400);
        }
        
        $vehicleIds = $data['vehicle_ids'];
        $placeholders = implode(',', array_fill(0, count($vehicleIds), '?'));
        
        $sql = "DELETE FROM vehicles WHERE id IN ($placeholders)";
        Database::query($sql, $vehicleIds);
        
        if (function_exists('auditLog')) {
            try {
                auditLog('vehicles_duplicates_delete', 'vehicle', null, [
                    'criteria' => $criteria,
                    'count' => count($vehicleIds),
                    'vehicle_ids' => $vehicleIds
                ]);
            } catch (Exception $e) {
                error_log("Audit log error: " . $e->getMessage());
            }
        }
        
        jsonResponse([
            'success' => true,
            'message' => 'Deleted ' . count($vehicleIds) . ' duplicate vehicle(s)',
            'count' => count($vehicleIds)
        ]);
    } else {
        jsonResponse(['error' => 'Invalid action. Must be "find" or "delete"'], 400);
    }
} catch (Exception $e) {
    error_log("Duplicates error: " . $e->getMessage());
    jsonResponse(['error' => 'Operation failed'], 500);
}
