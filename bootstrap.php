<?php
// bootstrap.php MUST be located in project root
define('APP_ROOT', dirname(__FILE__));

include APP_ROOT.'/config.php';

function autoload($className) {
    $className = preg_replace("/^App\\\\/i",'', $className);
    $className = str_replace('\\', '/', $className);
    $classFile = APP_ROOT.'/'.$className.'.php';

    if(file_exists($classFile))
        require_once $classFile;
}

spl_autoload_register("autoload");


function getRequestParam($name, $default = null) {
    return isset($_REQUEST[$name]) ? $_REQUEST[$name] : $default;
}