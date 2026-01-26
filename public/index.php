<?php
// Define constants first
define('ROOT_PATH', dirname(__DIR__) . '/');
define('LUKIMAN_ROOT_PATH', ROOT_PATH);
chdir(ROOT_PATH);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/app.php';

// Framework bootstrapping
use \Lukiman\Cores\Controller;
use \Lukiman\Cores\Exception\Base as ExceptionBase;
use \Nyholm\Psr7\Response;

// Error handler
// Suppress E_DEPRECATED for smooth operation with legacy libraries on PHP 8.4
error_reporting(E_ALL & ~E_DEPRECATED);

function exception_error_handler($errno, $errstr, $errfile, $errline ) {
    if (!(error_reporting() & $errno)) {
        return;
    }
    throw new ExceptionBase($errstr . ' in ' . $errfile . ':' . $errline);
}
set_error_handler("exception_error_handler");

$fullPath = (!empty($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : (!empty($_SERVER['REQUEST_URI']) ? strtok($_SERVER['REQUEST_URI'], '?') : ''));

if (!empty($fullPath) && $fullPath !== '/') {
    
    $path = explode('/', $fullPath);
    if (empty($path[0])) array_shift($path);
    if (end($path) == '') array_pop($path);
    
    $_path = $path; // Copy for parameter extraction
    
    // Normalize path to StudlyCaps for class names
    foreach ($path as $k => $v) {
        $path[$k] = str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower($v))));
    }
    
    $class = implode('\\', $path); 
    
    $retVal = null;
    $action = '';
    $_param = '';
    $params = array();

    // Loop to find the controller class
    while (!Controller::exists($class) AND !empty($class)) {
            if (!empty($action)) array_unshift($params, $_param);
            $action = array_pop($path);
            
            if (is_array($_path) && count($_path) > 0) {
                 $_param = array_pop($_path);
            } else {
                 $_param = null;
            }
            
            $class = implode('\\', $path);
    }
    
    try {
        if (empty($class)) {
            if (!headers_sent()) header('HTTP/1.0 404 Not Found');
            echo 'Handler not found for ' . $fullPath;
            exit;
        }

        Controller\Base::set_action($action);
        $ctrl = Controller::load($class);

        $retVal = $ctrl->execute($action, $params);
        $ctrl->sendHeaders();
        
        if ($retVal instanceof Response) {
             foreach ($retVal->getHeaders() as $name => $values) {
                 foreach ($values as $value) {
                     header(sprintf('%s: %s', $name, $value), false);
                 }
             }
             header(sprintf('HTTP/%s %s %s', $retVal->getProtocolVersion(), $retVal->getStatusCode(), $retVal->getReasonPhrase()));
             echo $retVal->getBody();
        } else {
             echo $retVal;
        }

    } catch (\Throwable | ExceptionBase $e) {
        if (!headers_sent()) {
            $httpCode = 500;
            $reflection = new \ReflectionClass($e);
            
            if ($reflection->hasMethod('getHttpCode')) {
                $httpCode = $e->getHttpCode();
            }
            http_response_code($httpCode);
        }
        echo $e->getMessage();
    }
} else {
    echo "Welcome to Auth Server";
}
