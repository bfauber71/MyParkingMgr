<?php
/**
 * Database Connection and Helper Functions
 * PDO-based database layer
 */

class Database {
    private static $pdo = null;
    
    /**
     * Get PDO connection (alias for connect)
     */
    public static function getPDO() {
        return self::connect();
    }
    
    /**
     * Get PDO connection
     */
    public static function connect() {
        if (self::$pdo !== null) {
            return self::$pdo;
        }
        
        require_once __DIR__ . '/config-loader.php';
        $config = ConfigLoader::load();
        $db = $config['db'];
        
        // Check if database is configured with placeholder values
        if ($db['username'] === 'your_db_username' || $db['password'] === 'your_db_password') {
            error_log("Database not configured - using placeholder credentials");
            
            // Whitelist endpoints that don't require database access
            $requestUri = $_SERVER['REQUEST_URI'] ?? '';
            $noDbRequired = [
                '/api/app-config',
                '/api/csrf-token',
                '/api/path-config'
            ];
            
            $isWhitelisted = false;
            foreach ($noDbRequired as $endpoint) {
                if (strpos($requestUri, $endpoint) !== false) {
                    $isWhitelisted = true;
                    break;
                }
            }
            
            // If this is an API request (not whitelisted), return JSON error
            if (!$isWhitelisted && strpos($requestUri, '/api/') !== false) {
                http_response_code(503);
                die(json_encode(['error' => 'Database not configured. Please run setup wizard.']));
            }
            
            // If whitelisted API endpoint, return null to allow it to proceed without database
            if ($isWhitelisted) {
                return null;
            }
            
            // Otherwise redirect to setup
            $basePath = ConfigLoader::getBasePath();
            $setupUrl = $basePath . '/setup.php';
            
            // Check if we're already on a setup-related page
            $isSetupPage = (strpos($requestUri, 'setup.php') !== false || 
                           strpos($requestUri, 'setup-wizard.php') !== false ||
                           strpos($requestUri, 'setup-test-db.php') !== false);
            
            if (!$isSetupPage) {
                header("Location: $setupUrl");
                exit;
            }
            
            // If we're already in setup, return null to allow setup to continue
            return null;
        }
        
        try {
            $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}";
            self::$pdo = new PDO($dsn, $db['username'], $db['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            return self::$pdo;
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            
            // Provide more helpful error messages
            $errorMessage = 'Database connection failed';
            
            if (strpos($e->getMessage(), 'No such file or directory') !== false || 
                strpos($e->getMessage(), 'Connection refused') !== false) {
                $errorMessage = 'MySQL server is not running. Please ensure MySQL is installed and running.';
            } elseif (strpos($e->getMessage(), 'Access denied') !== false) {
                $errorMessage = 'Invalid database credentials. Please check username and password.';
            } elseif (strpos($e->getMessage(), 'Unknown database') !== false) {
                $errorMessage = 'Database does not exist. Please create the database first.';
            }
            
            // If this is an API request, return JSON error
            if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
                http_response_code(500);
                die(json_encode(['error' => $errorMessage]));
            }
            
            // For non-API requests, show a user-friendly error page
            http_response_code(500);
            die('
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Database Connection Error</title>
                    <style>
                        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
                        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 4px; }
                        .details { background: #f8f9fa; padding: 10px; margin-top: 10px; border-radius: 4px; font-family: monospace; font-size: 12px; }
                        a { color: #007bff; text-decoration: none; }
                        a:hover { text-decoration: underline; }
                    </style>
                </head>
                <body>
                    <h1>Database Connection Error</h1>
                    <div class="error">
                        <strong>' . htmlspecialchars($errorMessage) . '</strong>
                        <div class="details">' . htmlspecialchars($e->getMessage()) . '</div>
                    </div>
                    <p>Please check your database configuration and ensure MySQL is running.</p>
                    <p><a href="' . htmlspecialchars(ConfigLoader::getBasePath() . '/setup.php') . '">Go to Setup Wizard</a></p>
                </body>
                </html>
            ');
        }
    }
    
    /**
     * Get PDO instance (alias for connect)
     */
    public static function getInstance() {
        return self::connect();
    }
    
    /**
     * Execute a SELECT query
     */
    public static function query($sql, $params = []) {
        $pdo = self::connect();
        if ($pdo === null) {
            return [];  // Return empty array if no database connection
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Execute a SELECT query and return single row
     */
    public static function queryOne($sql, $params = []) {
        $pdo = self::connect();
        if ($pdo === null) {
            return false;  // Return false if no database connection
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    /**
     * Execute INSERT/UPDATE/DELETE query
     */
    public static function execute($sql, $params = []) {
        $pdo = self::connect();
        if ($pdo === null) {
            return false;  // Return false if no database connection
        }
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Get last insert ID
     */
    public static function lastInsertId() {
        $pdo = self::connect();
        if ($pdo === null) {
            return false;
        }
        return $pdo->lastInsertId();
    }
    
    /**
     * Begin transaction
     */
    public static function beginTransaction() {
        $pdo = self::connect();
        if ($pdo === null) {
            return false;
        }
        return $pdo->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public static function commit() {
        $pdo = self::connect();
        if ($pdo === null) {
            return false;
        }
        return $pdo->commit();
    }
    
    /**
     * Rollback transaction
     */
    public static function rollback() {
        $pdo = self::connect();
        if ($pdo === null) {
            return false;
        }
        return $pdo->rollBack();
    }
    
    /**
     * Generate UUID v4
     */
    public static function uuid() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
