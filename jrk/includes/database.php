<?php
/**
 * Database Connection and Helper Functions
 * PDO-based database layer
 */

class Database {
    private static $pdo = null;
    
    /**
     * Get PDO connection
     */
    public static function connect() {
        if (self::$pdo !== null) {
            return self::$pdo;
        }
        
        $config = require __DIR__ . '/../config.php';
        $db = $config['db'];
        
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
            http_response_code(500);
            die(json_encode(['error' => 'Database connection failed']));
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
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Execute a SELECT query and return single row
     */
    public static function queryOne($sql, $params = []) {
        $pdo = self::connect();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    /**
     * Execute INSERT/UPDATE/DELETE query
     */
    public static function execute($sql, $params = []) {
        $pdo = self::connect();
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Get last insert ID
     */
    public static function lastInsertId() {
        return self::connect()->lastInsertId();
    }
    
    /**
     * Begin transaction
     */
    public static function beginTransaction() {
        return self::connect()->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public static function commit() {
        return self::connect()->commit();
    }
    
    /**
     * Rollback transaction
     */
    public static function rollback() {
        return self::connect()->rollBack();
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
