<?php
/**
 * Configuration Loader
 * Handles dynamic path resolution and configuration management
 */

class ConfigLoader {
    private static $config = null;
    private static $configFile = null;
    
    /**
     * Load configuration from file
     */
    public static function load($configFile = null) {
        if (self::$config !== null && $configFile === self::$configFile) {
            return self::$config;
        }
        
        // Determine config file location
        if ($configFile === null) {
            $configFile = self::findConfigFile();
        }
        
        if (!file_exists($configFile)) {
            // Return defaults if no config file exists
            return self::getDefaultConfig();
        }
        
        self::$configFile = $configFile;
        self::$config = require $configFile;
        
        // Ensure all required keys exist
        self::$config = array_merge(self::getDefaultConfig(), self::$config);
        
        return self::$config;
    }
    
    /**
     * Find the configuration file
     */
    private static function findConfigFile() {
        // Check for config in current directory
        $localConfig = __DIR__ . '/../config.php';
        if (file_exists($localConfig)) {
            return $localConfig;
        }
        
        // Check for config in parent directory (for flexible installations)
        $parentConfig = dirname(__DIR__, 2) . '/config.php';
        if (file_exists($parentConfig)) {
            return $parentConfig;
        }
        
        return $localConfig; // Default location
    }
    
    /**
     * Get default configuration
     */
    private static function getDefaultConfig() {
        return [
            'app_name' => 'MyParkingManager',
            'app_url' => '',
            'base_path' => '',
            'install_path' => '',
            'db' => [
                'host' => 'localhost',
                'port' => '3306',
                'database' => 'myparkingmanager',
                'username' => '',
                'password' => '',
                'charset' => 'utf8mb4',
            ],
            'session' => [
                'name' => 'myparkingmanager_session',
                'lifetime' => 1440,
                'secure' => false,
                'httponly' => true,
            ],
            'password_cost' => 10,
            'setup_token' => 'setup',
            'max_upload_size' => 52428800,
            'max_csv_rows' => 10000,
        ];
    }
    
    /**
     * Get a configuration value
     */
    public static function get($key, $default = null) {
        if (self::$config === null) {
            self::load();
        }
        
        // Handle nested keys (e.g., 'db.host')
        $keys = explode('.', $key);
        $value = self::$config;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }
    
    /**
     * Get the base path for the application
     */
    public static function getBasePath() {
        $basePath = self::get('base_path', '');
        
        // If explicitly set in config, use that
        if (!empty($basePath)) {
            return $basePath;
        }
        
        // Try to auto-detect if not set
        if (isset($_SERVER['SCRIPT_NAME'])) {
            $scriptName = $_SERVER['SCRIPT_NAME'];
            $basePath = dirname($scriptName);
            
            // Remove /api if we're being called from an API script
            if (substr($basePath, -4) === '/api') {
                $basePath = substr($basePath, 0, -4);
            }
            
            // Remove /public if we're being called from public directory
            if (substr($basePath, -7) === '/public') {
                $basePath = substr($basePath, 0, -7);
            }
            
            // Remove /admin if we're being called from admin directory
            if (substr($basePath, -6) === '/admin') {
                $basePath = substr($basePath, 0, -6);
            }
            
            // Remove /includes if we're being called from includes directory
            if (substr($basePath, -9) === '/includes') {
                $basePath = substr($basePath, 0, -9);
            }
            
            if ($basePath === '/' || $basePath === '\\') {
                $basePath = '';
            }
        }
        
        return $basePath;
    }
    
    /**
     * Get the install path (filesystem path)
     */
    public static function getInstallPath() {
        $installPath = self::get('install_path', '');
        
        if (empty($installPath)) {
            // Use the directory containing the config file
            if (self::$configFile) {
                $installPath = dirname(self::$configFile);
            } else {
                // Default to parent of includes directory
                $installPath = dirname(__DIR__);
            }
        }
        
        return $installPath;
    }
    
    /**
     * Get the API base URL for JavaScript
     */
    public static function getApiBase() {
        $basePath = self::getBasePath();
        return $basePath . '/api';
    }
    
    /**
     * Update configuration value
     */
    public static function update($key, $value) {
        if (self::$config === null) {
            self::load();
        }
        
        // Handle nested keys
        $keys = explode('.', $key);
        $config = &self::$config;
        
        for ($i = 0; $i < count($keys) - 1; $i++) {
            if (!isset($config[$keys[$i]])) {
                $config[$keys[$i]] = [];
            }
            $config = &$config[$keys[$i]];
        }
        
        $config[$keys[count($keys) - 1]] = $value;
    }
    
    /**
     * Save configuration to file
     */
    public static function save($configFile = null) {
        if ($configFile === null) {
            $configFile = self::$configFile ?: __DIR__ . '/../config.php';
        }
        
        $config = self::$config ?: self::getDefaultConfig();
        
        $content = "<?php\n";
        $content .= "/**\n";
        $content .= " * MyParkingManager Configuration\n";
        $content .= " * Generated: " . date('Y-m-d H:i:s') . "\n";
        $content .= " */\n\n";
        $content .= "return " . var_export($config, true) . ";\n";
        
        return file_put_contents($configFile, $content) !== false;
    }
}