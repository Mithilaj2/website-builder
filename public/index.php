<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;

App\Core\Migrations::run();
Session::start();
Response::securityHeaders();

$router = new Router();
(require APP_PATH . '/routes.php')($router);
$router->dispatch(new Request());
