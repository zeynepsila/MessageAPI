<?php

use App\Controllers\AdminController;

use App\Middleware\JwtMiddleware;
use Slim\App;

return function (App $app) {
    $app->group('/admin', function ($group) {
        $group->get('/stats', [AdminController::class, 'stats']);


    })->add(new JwtMiddleware());
};
