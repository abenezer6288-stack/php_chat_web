<?php
require_once '../config.php';
header('Content-Type: application/json');

if (!isLoggedIn() || !hasRole('admin')) {
    echo json_encode(['error' => 'Unauthorized']); exit;
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// ── List users ───────────────────────────────────────────────
if ($action === 'users' && $method === 'GET') {
    $result = $conn->query("SELECT id, username, email, role, status, created_at FROM users ORDER BY created_at DESC");
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $row['is_self'] = ($row['id'] == $_SESSION['user_id']);
        $users[] = $row;
    }
    echo json_encode($users); exit;
}

// ── Add user ─────────────────────────────────────────────────
if ($action === 'add_user' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $username = sanitize($data['username'] ?? '');
    $email    = sanitize($data['email'] ?? '');
    $role     = sanitize($data['role'] ?? 'student');
    $password = $data['password'] ?? '';

    if (!$username || !$email || !$password) { echo json_encode(['error'=>'All fields required']); exit; }

    $check = $conn->query("SELECT id FROM users WHERE username='$username' OR email='$email'");
    if ($check->num_rows > 0) { echo json_encode(['error'=>'Username or email already exists']); exit; }

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?,?,?,?)");
    $stmt->bind_param('ssss', $username, $email, $hashed, $role);
    $stmt->execute();
    echo json_encode(['success' => true]); exit;
}

// ── Update user ──────────────────────────────────────────────
if ($action === 'update_user' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id       = intval($data['id']);
    $username = sanitize($data['username'] ?? '');
    $email    = sanitize($data['email'] ?? '');
    $role     = sanitize($data['role'] ?? 'student');
    $password = $data['password'] ?? '';

    if (!$id || !$username || !$email) { echo json_encode(['error'=>'Required fields missing']); exit; }

    $check = $conn->query("SELECT id FROM users WHERE (username='$username' OR email='$email') AND id != $id");
    if ($check->num_rows > 0) { echo json_encode(['error'=>'Username or email already taken']); exit; }

    if ($password) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET username='$username', email='$email', role='$role', password='$hashed' WHERE id=$id");
    } else {
        $conn->query("UPDATE users SET username='$username', email='$email', role='$role' WHERE id=$id");
    }
    echo json_encode(['success' => true]); exit;
}

// ── Delete user ──────────────────────────────────────────────
if ($action === 'delete_user' && $method === 'DELETE') {
    $id = intval($_GET['id']);
    if ($id == $_SESSION['user_id']) { echo json_encode(['error'=>'Cannot delete yourself']); exit; }
    $conn->query("DELETE FROM users WHERE id=$id");
    echo json_encode(['success' => true]); exit;
}

// ── List chats ───────────────────────────────────────────────
if ($action === 'chats' && $method === 'GET') {
    $result = $conn->query("
        SELECT c.id, c.name, c.type, c.created_at,
               u.username AS creator,
               (SELECT COUNT(*) FROM messages WHERE chat_id = c.id) AS message_count,
               (SELECT COUNT(*) FROM participants WHERE chat_id = c.id) AS participant_count
        FROM chats c
        LEFT JOIN users u ON c.created_by = u.id
        ORDER BY c.created_at DESC
    ");
    $chats = [];
    while ($row = $result->fetch_assoc()) $chats[] = $row;
    echo json_encode($chats); exit;
}

// ── Delete chat ──────────────────────────────────────────────
if ($action === 'delete_chat' && $method === 'DELETE') {
    $id = intval($_GET['id']);
    $conn->query("DELETE FROM chats WHERE id=$id");
    echo json_encode(['success' => true]); exit;
}

// ── List messages ────────────────────────────────────────────
if ($action === 'messages' && $method === 'GET') {
    // Return conversations (grouped by chat)
    $result = $conn->query("
        SELECT c.id AS chat_id, c.name AS chat_name, c.type AS chat_type,
               (SELECT COUNT(*) FROM messages WHERE chat_id = c.id) AS msg_count,
               (SELECT timestamp FROM messages WHERE chat_id = c.id ORDER BY timestamp DESC LIMIT 1) AS last_time,
               (SELECT content FROM messages WHERE chat_id = c.id ORDER BY timestamp DESC LIMIT 1) AS last_message,
               GROUP_CONCAT(DISTINCT u.username ORDER BY u.username SEPARATOR '|||') AS participants
        FROM chats c
        JOIN participants p ON p.chat_id = c.id
        JOIN users u ON u.id = p.user_id
        GROUP BY c.id
        ORDER BY last_time DESC
    ");
    $convs = [];
    while ($row = $result->fetch_assoc()) {
        $row['participants'] = explode('|||', $row['participants']);
        $convs[] = $row;
    }
    echo json_encode($convs); exit;
}

// ── Messages inside a conversation ───────────────────────────
if ($action === 'conversation' && $method === 'GET') {
    $chat_id = intval($_GET['chat_id']);
    $result = $conn->query("
        SELECT m.id, m.content, m.attachment, m.timestamp,
               u.username AS sender,
               (
                   SELECT u2.username FROM participants p2
                   JOIN users u2 ON p2.user_id = u2.id
                   WHERE p2.chat_id = m.chat_id AND p2.user_id != m.user_id
                   LIMIT 1
               ) AS recipient,
               c.type AS chat_type
        FROM messages m
        JOIN users u ON m.user_id = u.id
        JOIN chats c ON m.chat_id = c.id
        WHERE m.chat_id = $chat_id
        ORDER BY m.timestamp ASC
    ");
    $msgs = [];
    while ($row = $result->fetch_assoc()) $msgs[] = $row;
    echo json_encode($msgs); exit;
}

// ── Delete message ───────────────────────────────────────────
if ($action === 'delete_message' && $method === 'DELETE') {
    $id = intval($_GET['id']);
    $conn->query("DELETE FROM messages WHERE id=$id");
    echo json_encode(['success' => true]); exit;
}

echo json_encode(['error' => 'Unknown action']);
