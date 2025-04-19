<?php

namespace App\Controllers;

use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class MessageController
{
    public static function send(Request $request, Response $response): Response
{
    global $pdo;

    $user = $request->getAttribute('jwt');
    $params = (array)$request->getParsedBody();

    $receiverId = $params['receiver_id'] ?? null;
    $content = $params['content'] ?? null;

    if (!$receiverId || !$content) {
        $response->getBody()->write(json_encode(['error' => 'Alıcı ve içerik zorunlu']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    //  Alıcı kullanıcı gerçekten var mı kontrolü
    $checkUser = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $checkUser->execute([$receiverId]);
    if (!$checkUser->fetch()) {
        $response->getBody()->write(json_encode(['error' => 'Geçersiz alıcı ID']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    //  Mesajı veritabanına kaydet
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)");
    $stmt->execute([$user->sub, $receiverId, $content]);
    $messageId = $pdo->lastInsertId();

    // Loglama: mesaj gönderme
    $log = $pdo->prepare("INSERT INTO message_logs (message_id, user_id, action_type, created_at) VALUES (?, ?, ?, NOW())");
    $log->execute([$messageId, $user->sub, 'send']);

    $response->getBody()->write(json_encode([
        'status' => 'ok',
        'message' => 'Mesaj gönderildi',
        'message_id' => $messageId
    ]));
    return $response->withHeader('Content-Type', 'application/json');
}


    public static function inbox(Request $request, Response $response): Response
{
    global $pdo;
    $user = $request->getAttribute('jwt');

    $params = $request->getQueryParams();
    $page = isset($params['page']) ? max(1, (int)$params['page']) : 1;
    $limit = isset($params['limit']) ? max(1, (int)$params['limit']) : 10;
    $offset = ($page - 1) * $limit;

    $start = $params['start'] ?? null;
    $end = $params['end'] ?? null;
    $queryText = $params['query'] ?? null;

    // SQL base
    $query = "SELECT * FROM messages WHERE is_deleted = 0";
    $bindings = [];

    //  Eğer admin değilse, sadece kendi mesajlarını görsün
    if ($user->role !== 'admin') {
        $query .= " AND receiver_id = :receiver_id";
        $bindings['receiver_id'] = $user->sub;
    }

    //  Tarih filtresi
    if ($start && $end) {
        $query .= " AND created_at BETWEEN :start AND :end";
        $bindings['start'] = $start . ' 00:00:00';
        $bindings['end'] = $end . ' 23:59:59';
    }

    //  Arama filtresi
    if ($queryText) {
        $query .= " AND content LIKE :query";
        $bindings['query'] = '%' . $queryText . '%';
    }

    //  Sayfalama
    $query .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($query);

    // Bind values
    foreach ($bindings as $key => $value) {
        $paramType = str_contains($key, 'id') ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue(':' . $key, $value, $paramType);
    }

    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $messages = $stmt->fetchAll();

    $response->getBody()->write(json_encode([
        'status' => 'ok',
        'page' => $page,
        'limit' => $limit,
        'start' => $start,
        'end' => $end,
        'query' => $queryText,
        'is_admin' => $user->role === 'admin',
        'messages' => $messages
    ]));

    return $response->withHeader('Content-Type', 'application/json');
}

    


    public static function sent(Request $request, Response $response): Response
    {
        global $pdo;
        $user = $request->getAttribute('jwt');

        $params = $request->getQueryParams();
        $page = isset($params['page']) ? max(1, (int)$params['page']) : 1;
        $limit = isset($params['limit']) ? max(1, (int)$params['limit']) : 10;
        $offset = ($page - 1) * $limit;

        $start = $params['start'] ?? null;
        $end = $params['end'] ?? null;
        $queryText = $params['query'] ?? null;

        $query = "SELECT * FROM messages WHERE is_deleted = 0";
        $bindings = [];

        //  Admin değilse sadece kendi gönderdiği mesajlar
        if ($user->role !== 'admin') {
            $query .= " AND sender_id = :sender_id";
            $bindings['sender_id'] = $user->sub;
        }

        //  Tarih filtresi
        if ($start && $end) {
            $query .= " AND created_at BETWEEN :start AND :end";
            $bindings['start'] = $start . ' 00:00:00';
            $bindings['end'] = $end . ' 23:59:59';
        } elseif ($start) {
            $query .= " AND created_at >= :start";
            $bindings['start'] = $start . ' 00:00:00';
        } elseif ($end) {
            $query .= " AND created_at <= :end";
            $bindings['end'] = $end . ' 23:59:59';
        }

        //  Arama filtresi
        if ($queryText) {
            $query .= " AND content LIKE :query";
            $bindings['query'] = '%' . $queryText . '%';
        }

        $query .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($query);

        foreach ($bindings as $key => $value) {
            $paramType = str_contains($key, 'id') ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue(':' . $key, $value, $paramType);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        $messages = $stmt->fetchAll();

        $response->getBody()->write(json_encode([
            'status' => 'ok',
            'page' => $page,
            'limit' => $limit,
            'start' => $start,
            'end' => $end,
            'query' => $queryText,
            'is_admin' => $user->role === 'admin',
            'sent' => $messages
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }



    
    public static function markAsRead(Request $request, Response $response, $args): Response
{
    global $pdo;

    $user = $request->getAttribute('jwt');
    $messageId = $args['id'];

    // Bu mesaj gerçekten bu kullanıcıya mı ait?
    $stmt = $pdo->prepare("SELECT * FROM messages WHERE id = ? AND is_deleted = 0 AND receiver_id = ?");
    $stmt->execute([$messageId, $user->sub]);
    $message = $stmt->fetch();

    if (!$message) {
        $response->getBody()->write(json_encode(['error' => 'Mesaj bulunamadı']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    // Zaten okunmuşsa tekrar update yapma (isteğe bağlı)
    if ($message['is_read'] == 1) {
        $response->getBody()->write(json_encode([
            'status' => 'ok',
            'message' => 'Mesaj zaten okunmuş'
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // is_read = 1 yap
    $update = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id = ?");
    $update->execute([$messageId]);

    // ✅ Logla
    $log = $pdo->prepare("INSERT INTO message_logs (message_id, user_id, action_type, created_at) VALUES (?, ?, 'read', NOW())");
    $log->execute([$messageId, $user->sub]);

    $response->getBody()->write(json_encode([
        'status' => 'ok',
        'message' => 'Mesaj okundu olarak işaretlendi'
    ]));
    return $response->withHeader('Content-Type', 'application/json');
}


    public static function unread(Request $request, Response $response): Response
    {
        global $pdo;
        $user = $request->getAttribute('jwt');

        $params = $request->getQueryParams();
        $page = isset($params['page']) ? max(1, (int)$params['page']) : 1;
        $limit = isset($params['limit']) ? max(1, (int)$params['limit']) : 10;
        $offset = ($page - 1) * $limit;

        $start = $params['start'] ?? null;
        $end = $params['end'] ?? null;
        $queryText = $params['query'] ?? null;

        $query = "SELECT * FROM messages WHERE is_read = 0 AND is_deleted = 0";
        $bindings = [];

        //  Sadece admin tüm okunmamışları görebilir, diğerleri kendi gelen kutusunu
        if ($user->role !== 'admin') {
            $query .= " AND receiver_id = :receiver_id";
            $bindings['receiver_id'] = $user->sub;
        }

        //  Tarih filtresi
        if ($start && $end) {
            $query .= " AND created_at BETWEEN :start AND :end";
            $bindings['start'] = $start . ' 00:00:00';
            $bindings['end'] = $end . ' 23:59:59';
        } elseif ($start) {
            $query .= " AND created_at >= :start";
            $bindings['start'] = $start . ' 00:00:00';
        } elseif ($end) {
            $query .= " AND created_at <= :end";
            $bindings['end'] = $end . ' 23:59:59';
        }

        //  İçerik arama
        if ($queryText) {
            $query .= " AND content LIKE :query";
            $bindings['query'] = '%' . $queryText . '%';
        }

        //  Sayfalama
        $query .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($query);

        foreach ($bindings as $key => $value) {
            $paramType = str_contains($key, 'id') ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue(':' . $key, $value, $paramType);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        $messages = $stmt->fetchAll();

        $response->getBody()->write(json_encode([
            'status' => 'ok',
            'page' => $page,
            'limit' => $limit,
            'start' => $start,
            'end' => $end,
            'query' => $queryText,
            'is_admin' => $user->role === 'admin',
            'unread' => $messages
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }




    public static function delete(Request $request, Response $response, $args): Response
{
    global $pdo;

    $user = $request->getAttribute('jwt');
    $messageId = $args['id'];

    // Kullanıcının kendi gönderdiği ya da aldığı mesaj mı?
    $stmt = $pdo->prepare("SELECT * FROM messages WHERE id = ? AND (sender_id = ? OR receiver_id = ?) AND is_deleted = 0");
    $stmt->execute([$messageId, $user->sub, $user->sub]);
    $message = $stmt->fetch();

    if (!$message) {
        $response->getBody()->write(json_encode(['error' => 'Mesaj bulunamadı veya silinmiş']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    // Soft delete
    $update = $pdo->prepare("UPDATE messages SET is_deleted = 1 WHERE id = ?");
    $update->execute([$messageId]);

    //  Logla
    $log = $pdo->prepare("INSERT INTO message_logs (message_id, user_id, action_type, created_at) VALUES (?, ?, 'delete', NOW())");
    $log->execute([$messageId, $user->sub]);

    $response->getBody()->write(json_encode([
        'status' => 'ok',
        'message' => 'Mesaj silindi (soft delete)'
    ]));

    return $response->withHeader('Content-Type', 'application/json');
}

    
    public static function show(Request $request, Response $response, $args): Response
{
    global $pdo;

    $user = $request->getAttribute('jwt');
    $messageId = $args['id'];

    // admin → tüm mesajlara erişebilir
    if ($user->role === 'admin') {
        $stmt = $pdo->prepare("SELECT * FROM messages WHERE id = ? AND is_deleted = 0");
        $stmt->execute([$messageId]);
    } else {
        // sadece kendi mesajlarını görebilir
        $stmt = $pdo->prepare("SELECT * FROM messages WHERE id = ? AND is_deleted = 0 AND (sender_id = ? OR receiver_id = ?)");
        $stmt->execute([$messageId, $user->sub, $user->sub]);
    }

    $message = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$message) {
        $response->getBody()->write(json_encode(['error' => 'Mesaj bulunamadı']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    $response->getBody()->write(json_encode([
        'status' => 'ok',
        'is_admin' => $user->role === 'admin',
        'message' => $message
    ]));

    return $response->withHeader('Content-Type', 'application/json');
}

public static function markAllAsRead(Request $request, Response $response): Response
{
    global $pdo;

    $user = $request->getAttribute('jwt');

    // 1. Okunmamış mesajları al
    $stmt = $pdo->prepare("SELECT id FROM messages WHERE receiver_id = ? AND is_read = 0 AND is_deleted = 0");
    $stmt->execute([$user->sub]);
    $messages = $stmt->fetchAll(PDO::FETCH_COLUMN); // sadece ID'leri al

    if (empty($messages)) {
        $response->getBody()->write(json_encode([
            'status' => 'ok',
            'message' => 'Zaten tüm mesajlar okunmuştu'
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // 2. Okundu olarak güncelle
    $update = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id = ?");
    foreach ($messages as $messageId) {
        $update->execute([$messageId]);
    }

    // 3. Her biri için log kaydı
    $log = $pdo->prepare("INSERT INTO message_logs (message_id, user_id, action_type, created_at) VALUES (?, ?, 'read_all', NOW())");
    foreach ($messages as $messageId) {
        $log->execute([$messageId, $user->sub]);
    }

    $response->getBody()->write(json_encode([
        'status' => 'ok',
        'message' => count($messages) . ' mesaj okundu olarak işaretlendi'
    ]));

    return $response->withHeader('Content-Type', 'application/json');
}



}
