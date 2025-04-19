<?php

use App\Controllers\NotificationController;
use App\Middleware\JwtMiddleware;
use Slim\App;

return function (App $app) {
    $app->group('/notifications', function ($group) {
        $group->get('/unread_count', [NotificationController::class, 'unreadCount']);
        $group->get('/status', [NotificationController::class, 'status']);

    })->add(new JwtMiddleware());

    
};
