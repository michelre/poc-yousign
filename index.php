<?php

require_once 'vendor/autoload.php';
require_once 'src/router/Router.php';

$router = new \App\Router\Router();
$router->run();
