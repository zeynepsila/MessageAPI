<?php

namespace App\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class JwtMiddleware
{
    public function __invoke(Request $request, $handler): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');
        $response = new \Slim\Psr7\Response();

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            $response->getBody()->write(json_encode(['error' => 'Token eksik']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $token = str_replace('Bearer ', '', $authHeader);

        try {
            $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));
            $request = $request->withAttribute('jwt', $decoded);
            return $handler->handle($request);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Token geçersiz', 'details' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }
    }
}
