<?php

namespace App\Controllers;

use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;


class AuthController
{
    public static function register(Request $request, Response $response): Response
    {
        global $pdo;
        $params = (array)$request->getParsedBody();
        $username = $params['username'] ?? '';
        $email = $params['email'] ?? '';
        $password = $params['password'] ?? '';

        if (!$username || !$email || !$password) {
            $response->getBody()->write(json_encode(['error' => 'Eksik alan var']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $hashed]);

        $response->getBody()->write(json_encode(['status' => 'ok', 'message' => 'Kayıt başarılı']));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function login(Request $request, Response $response): Response
{
    global $pdo;

    $params = (array)$request->getParsedBody();
    $email = $params['email'] ?? '';
    $password = $params['password'] ?? '';

    if (!$email || !$password) {
        $response->getBody()->write(json_encode(['error' => 'Email ve şifre zorunlu']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        $response->getBody()->write(json_encode(['error' => 'Geçersiz giriş']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
    }

    $payload = [
        'sub' => $user['id'],         // kullanıcı ID
        'email' => $user['email'],   // email
        'role' => $user['role'],     // user / admin
        'iat' => time(),             // oluşturulma zamanı
        'exp' => time() + 3600       // 1 saatlik geçerlilik
    ];
    

    $jwt = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');

    $response->getBody()->write(json_encode([
        'status' => 'ok',
        'token' => $jwt
    ]));

    return $response->withHeader('Content-Type', 'application/json');
}


}
