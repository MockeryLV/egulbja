<?php

use Psr\Http\Server\RequestHandlerInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Slim\Factory\AppFactory;
use Slim\Middleware\ErrorMiddleware;
use DI\ContainerBuilder;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/config.php';

// Create and configure Slim app
$containerBuilder = new ContainerBuilder();
$container = $containerBuilder->build();
AppFactory::setContainer($container);
$app = AppFactory::create();

// Load API routes
require_once 'routes/api.php';

$app->run();