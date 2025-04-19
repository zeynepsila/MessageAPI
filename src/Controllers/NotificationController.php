<?php

namespace App\Controllers;

use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class NotificationController
{
    public static function unreadCount(Request $request, Response $response): Response
    {
        global $pdo;

        $user = $request->getAttribute('jwt');

        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM messages WHERE receiver_id = ? AND is_read = 0");
        $stmt->execute([$user->sub]);
        $count = $stmt->fetch();

        $response->getBody()->write(json_encode([
            'status' => 'ok',
            'unread_count' => (int) $count['total']
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function status(Request $request, Response $response): Response
{
    global $pdo;
    $user = $request->getAttribute('jwt');

    // Okunmamış mesaj sayısı
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as unread_count 
        FROM messages 
        WHERE receiver_id = ? AND is_read = 0 AND is_deleted = 0
    ");
    $stmt->execute([$user->sub]);
    $result = $stmt->fetch();

    $hasNew = ((int)$result['unread_count']) > 0;

    $response->getBody()->write(json_encode([
        'status' => 'ok',
        'has_new_messages' => $hasNew,
        'unread_count' => (int) $result['unread_count']
    ]));

    return $response->withHeader('Content-Type', 'application/json');
}

}
