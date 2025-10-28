<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/helpers.php';

Session::start();

if (!Session::isAuthenticated()) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Any authenticated user can view printer settings
    // Get printer settings
    try {
        $settings = [];
        $results = Database::query("SELECT setting_key, setting_value FROM printer_settings");
        
        foreach ($results as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        // Return default values if settings don't exist
        $settings['ticket_width'] = $settings['ticket_width'] ?? '2.5';
        $settings['ticket_height'] = $settings['ticket_height'] ?? '6';
        $settings['ticket_unit'] = $settings['ticket_unit'] ?? 'in';
        $settings['logo_top'] = $settings['logo_top'] ?? null;
        $settings['logo_bottom'] = $settings['logo_bottom'] ?? null;
        $settings['logo_top_enabled'] = $settings['logo_top_enabled'] ?? 'false';
        $settings['logo_bottom_enabled'] = $settings['logo_bottom_enabled'] ?? 'false';
        
        jsonResponse([
            'success' => true,
            'settings' => $settings
        ]);
    } catch (Exception $e) {
        error_log('Fetch printer settings error: ' . $e->getMessage());
        jsonResponse(['error' => 'Failed to fetch settings'], 500);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Only admins can update printer settings
    if (Session::user()['role'] !== 'admin') {
        jsonResponse(['error' => 'Admin access required to modify settings'], 403);
    }
    
    // CSRF validation (checks both headers and body)
    Security::validateRequest(['POST']);
    
    // Update printer settings
    // IMPORTANT: Use getJsonInput() instead of file_get_contents('php://input')
    // because CSRF validation already read the body
    $data = getJsonInput();
    
    try {
        $allowedSettings = [
            'ticket_width', 
            'ticket_height', 
            'ticket_unit',
            'logo_top', 
            'logo_bottom',
            'logo_top_enabled',
            'logo_bottom_enabled'
        ];
        
        foreach ($data['settings'] ?? [] as $key => $value) {
            if (!in_array($key, $allowedSettings)) {
                error_log("Printer settings: Skipped invalid key: $key");
                continue;
            }
            
            // Handle logo upload separately (base64 encoded images)
            if (($key === 'logo_top' || $key === 'logo_bottom') && $value !== null) {
                // Validate it's a valid image data URL
                if (!preg_match('/^data:image\/(png|jpg|jpeg|gif|webp);base64,/', $value)) {
                    error_log("Printer settings: Invalid logo format for $key");
                    continue;
                }
            }
            
            $sql = "INSERT INTO printer_settings (id, setting_key, setting_value) 
                    VALUES (UUID(), ?, ?)
                    ON DUPLICATE KEY UPDATE 
                        setting_value = ?,
                        updated_at = CURRENT_TIMESTAMP";
            
            Database::execute($sql, [$key, $value, $value]);
        }
        
        if (function_exists('auditLog')) {
            auditLog('UPDATE', 'printer_settings', null, [
                'settings' => array_keys($data['settings'] ?? [])
            ]);
        }
        
        jsonResponse([
            'success' => true,
            'message' => 'Printer settings updated successfully'
        ]);
        
    } catch (Exception $e) {
        error_log('Update printer settings error: ' . $e->getMessage());
        jsonResponse(['error' => 'Failed to update settings: ' . $e->getMessage()], 500);
    }
} else {
    jsonResponse(['error' => 'Method not allowed'], 405);
}