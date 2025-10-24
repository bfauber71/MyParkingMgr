<?php
/**
 * Login API Endpoint with Attempt Limiting
 * POST /api/login
 * 
 * Security Features:
 * - 5 failed attempts max
 * - 10-minute lockout after max attempts
 * - Automatic reset after successful login or 1 hour
 */

$data = getJsonInput();

// Validate input
$missing = validateRequired($data, ['username', 'password']);
if (!empty($missing)) {
    jsonResponse(['error' => 'Missing required fields: ' . implode(', ', $missing)], 400);
}

$username = sanitize($data['username']);
$password = $data['password'];
$ipAddress = getClientIp();

// Check login attempts and lockout status
try {
    // Check if login_attempts table exists
    $tableExists = Database::queryOne("SHOW TABLES LIKE 'login_attempts'");
    
    if ($tableExists) {
        // Check current attempt record
        $attemptRecord = Database::queryOne(
            "SELECT * FROM login_attempts WHERE username = ? AND ip_address = ? LIMIT 1",
            [$username, $ipAddress]
        );
        
        if ($attemptRecord) {
            $now = new DateTime();
            $lockedUntil = $attemptRecord['locked_until'] ? new DateTime($attemptRecord['locked_until']) : null;
            $lastAttempt = new DateTime($attemptRecord['last_attempt']);
            
            // Check if still locked out
            if ($lockedUntil && $now < $lockedUntil) {
                $remainingSeconds = $lockedUntil->getTimestamp() - $now->getTimestamp();
                $remainingMinutes = ceil($remainingSeconds / 60);
                
                auditLog('login_locked', 'user', null, [
                    'username' => $username,
                    'ip' => $ipAddress,
                    'remaining_seconds' => $remainingSeconds
                ]);
                
                jsonResponse([
                    'error' => 'Account temporarily locked',
                    'locked' => true,
                    'locked_until' => $lockedUntil->format('Y-m-d H:i:s'),
                    'remaining_seconds' => $remainingSeconds,
                    'message' => "Too many failed attempts. Please try again in {$remainingMinutes} minute(s)."
                ], 429);
            }
            
            // Check if attempts should be reset (1 hour since last attempt)
            $hourAgo = (clone $now)->modify('-1 hour');
            if ($lastAttempt < $hourAgo) {
                // Reset attempts
                Database::query(
                    "UPDATE login_attempts SET attempt_count = 0, locked_until = NULL WHERE username = ? AND ip_address = ?",
                    [$username, $ipAddress]
                );
            }
        }
    }
} catch (Exception $e) {
    // If login_attempts table doesn't exist, continue without limiting
    error_log("Login attempts check error: " . $e->getMessage());
}

// Find user
$sql = "SELECT * FROM users WHERE username = ? LIMIT 1";
$user = Database::queryOne($sql, [$username]);

if (!$user || !password_verify($password, $user['password'])) {
    // FAILED LOGIN: Record attempt
    try {
        if ($tableExists) {
            $attemptRecord = Database::queryOne(
                "SELECT * FROM login_attempts WHERE username = ? AND ip_address = ?",
                [$username, $ipAddress]
            );
            
            if ($attemptRecord) {
                $newCount = $attemptRecord['attempt_count'] + 1;
                $lockedUntil = null;
                
                // Lock out if reached 5 attempts
                if ($newCount >= 5) {
                    $lockedUntil = (new DateTime())->modify('+10 minutes')->format('Y-m-d H:i:s');
                }
                
                Database::query(
                    "UPDATE login_attempts 
                     SET attempt_count = ?, locked_until = ?, last_attempt = NOW() 
                     WHERE username = ? AND ip_address = ?",
                    [$newCount, $lockedUntil, $username, $ipAddress]
                );
            } else {
                // Create new attempt record
                Database::query(
                    "INSERT INTO login_attempts (username, ip_address, attempt_count, last_attempt, created_at) 
                     VALUES (?, ?, 1, NOW(), NOW())",
                    [$username, $ipAddress]
                );
            }
        }
    } catch (Exception $e) {
        error_log("Login attempt recording error: " . $e->getMessage());
    }
    
    auditLog('login_failed', 'user', null, [
        'username' => $username,
        'ip' => $ipAddress
    ]);
    
    jsonResponse(['error' => 'Invalid credentials'], 401);
}

// SUCCESSFUL LOGIN: Reset attempts
try {
    if ($tableExists) {
        Database::query(
            "DELETE FROM login_attempts WHERE username = ? AND ip_address = ?",
            [$username, $ipAddress]
        );
    }
} catch (Exception $e) {
    error_log("Login attempt reset error: " . $e->getMessage());
}

// Load user permissions
try {
    $user['permissions'] = loadUserPermissions($user['id']);
} catch (Exception $e) {
    // If permissions table doesn't exist, set empty permissions
    $user['permissions'] = [];
}

// Login successful
Session::login($user);

auditLog('login', 'user', $user['id']);

jsonResponse([
    'user' => [
        'id' => $user['id'],
        'username' => $user['username'],
        'role' => $user['role'],
        'permissions' => $user['permissions']
    ]
]);
