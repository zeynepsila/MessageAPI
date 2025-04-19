<?php

use App\Controllers\MessageController;
use App\Middleware\JwtMiddleware;
use Slim\App;

return function (App $app) {
    $app->group('/messages', function ($group) {
        $group->post('/send', [MessageController::class, 'send']);
        $group->get('/inbox', [MessageController::class, 'inbox']);
        $group->get('/sent', [MessageController::class, 'sent']);
        $group->patch('/read/{id}', [MessageController::class, 'markAsRead']);
        $group->get('/unread', [MessageController::class, 'unread']);
        $group->delete('/{id}', [MessageController::class, 'delete']);
        $group->get('/{id}', [MessageController::class, 'show']);
        $group->patch('/read_all', [MessageController::class, 'markAllAsRead']);


    })->add(new JwtMiddleware());
};
