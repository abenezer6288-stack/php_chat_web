<?php
require_once '../config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

// Get messages for a chat
if ($action === 'list' && isset($_GET['chat_id'])) {
    $chat_id = intval($_GET['chat_id']);
    
    // Verify user is participant
    $check = $conn->query("SELECT * FROM participants WHERE chat_id = $chat_id AND user_id = $user_id");
    if ($check->num_rows === 0) {
        echo json_encode(['error' => 'Access denied']);
        exit;
    }
    
    // Mark messages as read
    $conn->query("UPDATE messages SET is_read = 1 WHERE chat_id = $chat_id AND user_id != $user_id");
    
    // Get messages ordered by timestamp with microsecond precision, then by ID as fallback
    $query = "SELECT m.id, m.chat_id, m.user_id, m.content, m.attachment, m.is_read,
              DATE_FORMAT(m.timestamp, '%Y-%m-%d %H:%i:%s.%f') as timestamp,
              u.username, u.avatar 
              FROM messages m 
              JOIN users u ON m.user_id = u.id 
              WHERE m.chat_id = $chat_id 
              ORDER BY m.timestamp ASC, m.id ASC";
    
    $result = $conn->query($query);
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    echo json_encode($messages);
}

// Send a message
if ($action === 'send' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $chat_id = intval($data['chat_id']);
    $content = sanitize($data['content']);
    $attachment = $data['attachment'] ?? null;
    
    // Verify user is participant
    $check = $conn->query("SELECT * FROM participants WHERE chat_id = $chat_id AND user_id = $user_id");
    if ($check->num_rows === 0) {
        echo json_encode(['error' => 'Access denied']);
        exit;
    }
    
    $stmt = $conn->prepare("INSERT INTO messages (chat_id, user_id, content, attachment) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $chat_id, $user_id, $content, $attachment);
    $stmt->execute();
    
    $message_id = $conn->insert_id;
    
    // Get the created message with user info
    $result = $conn->query("SELECT m.*, u.username, u.avatar 
                           FROM messages m 
                           JOIN users u ON m.user_id = u.id 
                           WHERE m.id = $message_id");
    
    echo json_encode($result->fetch_assoc());
}

// Upload file
if ($action === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed)) {
        echo json_encode(['error' => 'File type not allowed']);
        exit;
    }
    
    if ($file['size'] > 5000000) { // 5MB limit
        echo json_encode(['error' => 'File too large']);
        exit;
    }
    
    $filename = uniqid() . '_' . basename($file['name']);
    $upload_path = '../uploads/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        echo json_encode(['success' => true, 'filename' => $filename]);
    } else {
        echo json_encode(['error' => 'Upload failed']);
    }
}
?>
