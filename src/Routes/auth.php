<?php

use App\Controllers\AuthController;
use Slim\App;


return function (App $app) {
    
    $app->post('/auth/register', [AuthController::class, 'register']);
    $app->post('/auth/login', [AuthController::class, 'login']);
};
