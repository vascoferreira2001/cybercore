<?php
/**
 * CyberCore Router
 * Simple but powerful routing system for clean URLs
 * 
 * @package CyberCore
 * @author Senior Architect
 * @version 1.0.0
 */

class Router
{
    private static $routes = [];
    private static $groupPrefix = '';
    private static $groupMiddleware = [];
    
    /**
     * Add a GET route
     */
    public static function get($uri, $action)
    {
        self::addRoute('GET', $uri, $action);
    }
    
    /**
     * Add a POST route
     */
    public static function post($uri, $action)
    {
        self::addRoute('POST', $uri, $action);
    }
    
    /**
     * Add route for any HTTP method
     */
    public static function any($uri, $action)
    {
        foreach (['GET', 'POST', 'PUT', 'DELETE', 'PATCH'] as $method) {
            self::addRoute($method, $uri, $action);
        }
    }
    
    /**
     * Create a route group with shared attributes
     */
    public static function group($attributes, $callback)
    {
        $previousPrefix = self::$groupPrefix;
        $previousMiddleware = self::$groupMiddleware;
        
        self::$groupPrefix = $previousPrefix . ($attributes['prefix'] ?? '');
        self::$groupMiddleware = array_merge(
            $previousMiddleware,
            $attributes['middleware'] ?? []
        );
        
        $callback();
        
        self::$groupPrefix = $previousPrefix;
        self::$groupMiddleware = $previousMiddleware;
    }
    
    /**
     * Add a route to the collection
     */
    private static function addRoute($method, $uri, $action)
    {
        $uri = self::$groupPrefix . $uri;
        $uri = '/' . trim($uri, '/');
        
        self::$routes[$method][$uri] = [
            'action' => $action,
            'middleware' => self::$groupMiddleware
        ];
    }
    
    /**
     * Dispatch the current request
     */
    public static function dispatch()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = '/' . trim($uri, '/');
        
        // Check for exact match
        if (isset(self::$routes[$method][$uri])) {
            return self::executeRoute(self::$routes[$method][$uri]);
        }
        
        // Check for pattern matches
        foreach (self::$routes[$method] ?? [] as $routeUri => $route) {
            if ($params = self::matchRoute($routeUri, $uri)) {
                return self::executeRoute($route, $params);
            }
        }
        
        // No route found
        self::handleNotFound();
    }
    
    /**
     * Match a route pattern to a URI
     */
    private static function matchRoute($pattern, $uri)
    {
        // Convert route pattern to regex
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $pattern);
        $pattern = '#^' . $pattern . '$#';
        
        if (preg_match($pattern, $uri, $matches)) {
            // Filter out numeric keys
            return array_filter($matches, function($key) {
                return !is_numeric($key);
            }, ARRAY_FILTER_USE_KEY);
        }
        
        return false;
    }
    
    /**
     * Execute a route
     */
    private static function executeRoute($route, $params = [])
    {
        // Run middleware
        foreach ($route['middleware'] as $middleware) {
            $middlewareInstance = new $middleware();
            $middlewareInstance->handle();
        }
        
        $action = $route['action'];
        
        // Handle closure
        if (is_callable($action)) {
            return call_user_func_array($action, $params);
        }
        
        // Handle Controller@method
        if (is_string($action)) {
            list($controller, $method) = explode('@', $action);
            $controllerClass = "App\\Controllers\\{$controller}";
            
            if (!class_exists($controllerClass)) {
                throw new Exception("Controller {$controllerClass} not found");
            }
            
            $controllerInstance = new $controllerClass();
            
            if (!method_exists($controllerInstance, $method)) {
                throw new Exception("Method {$method} not found in {$controllerClass}");
            }
            
            return call_user_func_array(
                [$controllerInstance, $method],
                $params
            );
        }
    }
    
    /**
     * Handle 404 Not Found
     */
    private static function handleNotFound()
    {
        http_response_code(404);
        
        if (file_exists(__DIR__ . '/../404.php')) {
            require __DIR__ . '/../404.php';
        } else {
            echo '<h1>404 - Página não encontrada</h1>';
        }
        
        exit;
    }
    
    /**
     * Generate URL for a named route
     */
    public static function url($name, $params = [])
    {
        // TODO: Implement named routes
        return '#';
    }
}
