<?php
require_once '../config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

// Get all chats for current user
if ($action === 'list') {
    $query = "SELECT DISTINCT c.*, 
              (SELECT content FROM messages WHERE chat_id = c.id ORDER BY timestamp DESC LIMIT 1) as last_message,
              (SELECT timestamp FROM messages WHERE chat_id = c.id ORDER BY timestamp DESC LIMIT 1) as last_message_time,
              (SELECT COUNT(*) FROM messages WHERE chat_id = c.id AND user_id != $user_id AND is_read = 0) as unread_count
              FROM chats c
              JOIN participants p ON c.id = p.chat_id
              WHERE p.user_id = $user_id
              ORDER BY last_message_time DESC";
    
    $result = $conn->query($query);
    $chats = [];
    while ($row = $result->fetch_assoc()) {
        // Get participants
        $chat_id = $row['id'];
        $participants = $conn->query("SELECT u.id, u.username, u.avatar, u.status 
                                      FROM users u 
                                      JOIN participants p ON u.id = p.user_id 
                                      WHERE p.chat_id = $chat_id");
        $row['participants'] = [];
        while ($p = $participants->fetch_assoc()) {
            $row['participants'][] = $p;
        }
        $chats[] = $row;
    }
    echo json_encode($chats);
}

// Create new chat
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $type = sanitize($data['type']);
    $name = sanitize($data['name']);
    $participants = $data['participants'];
    
    // Create chat
    $stmt = $conn->prepare("INSERT INTO chats (name, type, created_by) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $name, $type, $user_id);
    $stmt->execute();
    $chat_id = $conn->insert_id;
    
    // Add participants
    $stmt = $conn->prepare("INSERT INTO participants (chat_id, user_id) VALUES (?, ?)");
    foreach ($participants as $participant_id) {
        $stmt->bind_param("ii", $chat_id, $participant_id);
        $stmt->execute();
    }
    
    // Add creator as participant
    $stmt->bind_param("ii", $chat_id, $user_id);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'chat_id' => $chat_id]);
}

// Get chat details
if ($action === 'get' && isset($_GET['id'])) {
    $chat_id = intval($_GET['id']);
    
    // Verify user is participant
    $check = $conn->query("SELECT * FROM participants WHERE chat_id = $chat_id AND user_id = $user_id");
    if ($check->num_rows === 0) {
        echo json_encode(['error' => 'Access denied']);
        exit;
    }
    
    $result = $conn->query("SELECT * FROM chats WHERE id = $chat_id");
    $chat = $result->fetch_assoc();
    
    // Get participants
    $participants = $conn->query("SELECT u.id, u.username, u.avatar, u.status 
                                  FROM users u 
                                  JOIN participants p ON u.id = p.user_id 
                                  WHERE p.chat_id = $chat_id");
    $chat['participants'] = [];
    while ($p = $participants->fetch_assoc()) {
        $chat['participants'][] = $p;
    }
    
    echo json_encode($chat);
}
?>
