<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/Database/db.php';

$containerBuilder = new ContainerBuilder();
$container = $containerBuilder->build();

AppFactory::setContainer($container);
$app = AppFactory::create();

$app->addBodyParsingMiddleware();

// Route tanımları
(require __DIR__ . '/../src/Routes/routes.php')($app);

$app->run();
