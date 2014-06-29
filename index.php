<?php

// конфиг
require_once('config.php');

// роутинг
$url = explode('/', trim($_SERVER['REDIRECT_URL'], '/'));
$cName = 'Controllers\\' . ($url[0] ? array_shift($url) : 'main');
if (class_exists($cName)) {
    $mName = $url[0] ? array_shift($url) : 'index';
    $c = new $cName;
    if (in_array($mName, get_class_methods($c))) {
        call_user_func_array(array($c, $mName), $url);
    } else {
        echo '404';
    }
} else {
    echo '404';
}


// автозагрузка классов
function __autoload($className)
{
    $fileName = 'app/' . str_replace('\\', '/', $className) . '.php';
    $fileName = strtolower($fileName);
    if (file_exists($fileName)) {
        require_once($fileName);
    }
}