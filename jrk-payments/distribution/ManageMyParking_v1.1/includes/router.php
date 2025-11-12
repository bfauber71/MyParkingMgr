<?php
/**
 * Simple Router
 * Routes requests to appropriate API endpoints
 */

class Router {
    private $routes = [];
    private $basePath;
    
    public function __construct($basePath = '') {
        $this->basePath = rtrim($basePath, '/');
    }
    
    /**
     * Add GET route
     */
    public function get($path, $handler) {
        $this->addRoute('GET', $path, $handler);
    }
    
    /**
     * Add POST route
     */
    public function post($path, $handler) {
        $this->addRoute('POST', $path, $handler);
    }
    
    /**
     * Add PATCH route
     */
    public function patch($path, $handler) {
        $this->addRoute('PATCH', $path, $handler);
    }
    
    /**
     * Add DELETE route
     */
    public function delete($path, $handler) {
        $this->addRoute('DELETE', $path, $handler);
    }
    
    /**
     * Add route
     */
    private function addRoute($method, $path, $handler) {
        $pattern = $this->pathToPattern($path);
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'pattern' => $pattern,
            'handler' => $handler
        ];
    }
    
    /**
     * Convert path to regex pattern
     */
    private function pathToPattern($path) {
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $this->basePath . $pattern . '$#';
    }
    
    /**
     * Dispatch request
     */
    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['pattern'], $uri, $matches)) {
                // Extract params from URL
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                // Call handler
                if (is_callable($route['handler'])) {
                    call_user_func($route['handler'], $params);
                } else if (file_exists($route['handler'])) {
                    $_ROUTE_PARAMS = $params;
                    require $route['handler'];
                }
                return;
            }
        }
        
        // No route found
        jsonResponse(['error' => 'Not found'], 404);
    }
}
