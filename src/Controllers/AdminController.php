<?php

namespace App\Controllers;

use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AdminController
{

    public static function stats(Request $request, Response $response): Response
    {
        global $pdo;
        $user = $request->getAttribute('jwt');
    
        // Sadece admin erişebilir
        if ($user->role !== 'admin') {
            $response->getBody()->write(json_encode(['error' => 'Bu işlem için yetkiniz yok']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    
        // 1. Bugün gönderilen mesaj sayısı
        $stmt = $pdo->query("SELECT COUNT(*) FROM messages WHERE DATE(created_at) = CURDATE()");
        $dailyCount = $stmt->fetchColumn();
    
        // 2. Okunmamış / toplam oranı
        $stmt = $pdo->query("SELECT COUNT(*) FROM messages");
        $total = $stmt->fetchColumn();
        $stmt = $pdo->query("SELECT COUNT(*) FROM messages WHERE is_read = 0");
        $unread = $stmt->fetchColumn();
        $unreadRatio = $total > 0 ? round($unread / $total * 100, 2) . '%' : '0%';
    
        // 3. Silinen / toplam oranı
        $stmt = $pdo->query("SELECT COUNT(*) FROM messages WHERE is_deleted = 1");
        $deleted = $stmt->fetchColumn();
        $deletedRatio = $total > 0 ? round($deleted / $total * 100, 2) . '%' : '0%';
    
        // 4. En çok mesaj atan kullanıcılar
        $stmt = $pdo->query("
            SELECT u.id as user_id, u.username, COUNT(m.id) as sent_count
            FROM users u
            JOIN messages m ON u.id = m.sender_id
            GROUP BY u.id
            ORDER BY sent_count DESC
            LIMIT 5
        ");
        $topSenders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        // JSON yanıt
        $response->getBody()->write(json_encode([
            'status' => 'ok',
            'daily_message_count' => (int)$dailyCount,
            'unread_ratio' => $unreadRatio,
            'deleted_ratio' => $deletedRatio,
            'top_senders' => $topSenders
        ]));
    
        return $response->withHeader('Content-Type', 'application/json');
    }
    


}