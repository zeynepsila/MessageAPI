<?php

use Slim\App;
use App\Middleware\JwtMiddleware;

return function (App $app) {

    (require __DIR__ . '/auth.php')($app);
    (require __DIR__ . '/message.php')($app);
    (require __DIR__ . '/notification.php')($app); 
    (require __DIR__ . '/admin.php')($app); 

    $app->get('/me', function ($request, $response) {
        $jwt = $request->getAttribute('jwt');
    
        $response->getBody()->write(json_encode([
            'status' => 'ok',
            'user' => [
                'id' => $jwt->sub ?? null,
                'email' => $jwt->email ?? null
            ]
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    })->add(new JwtMiddleware());
};
