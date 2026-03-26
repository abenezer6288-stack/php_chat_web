<?php
require_once '../config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

// Get all users
if ($action === 'list') {
    $search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
    $query = "SELECT id, username, email, role, avatar, status FROM users WHERE id != {$_SESSION['user_id']}";
    
    if ($search) {
        $query .= " AND (username LIKE '%$search%' OR email LIKE '%$search%')";
    }
    
    $result = $conn->query($query);
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    echo json_encode($users);
}

// Get user by ID
if ($action === 'get' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $result = $conn->query("SELECT id, username, email, role, avatar, status FROM users WHERE id = $id");
    echo json_encode($result->fetch_assoc());
}

// Update user status
if ($action === 'status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $status = sanitize($data['status']);
    $user_id = $_SESSION['user_id'];
    
    $conn->query("UPDATE users SET status = '$status' WHERE id = $user_id");
    echo json_encode(['success' => true]);
}

// Update profile settings
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $user_id = $_SESSION['user_id'];
    $updates = [];

    if (!empty($data['username'])) {
        $username = sanitize($data['username']);
        // Check uniqueness
        $check = $conn->query("SELECT id FROM users WHERE username='$username' AND id != $user_id");
        if ($check->num_rows > 0) {
            echo json_encode(['error' => 'Username already taken']);
            exit;
        }
        $updates[] = "username='$username'";
        $_SESSION['username'] = $username;
    }

    if (!empty($data['email'])) {
        $email = sanitize($data['email']);
        $check = $conn->query("SELECT id FROM users WHERE email='$email' AND id != $user_id");
        if ($check->num_rows > 0) {
            echo json_encode(['error' => 'Email already in use']);
            exit;
        }
        $updates[] = "email='$email'";
    }

    if (!empty($data['status'])) {
        $status = sanitize($data['status']);
        $updates[] = "status='$status'";
    }

    if (!empty($data['new_password'])) {
        // Verify current password
        $result = $conn->query("SELECT password FROM users WHERE id=$user_id");
        $row = $result->fetch_assoc();
        if (!password_verify($data['current_password'], $row['password'])) {
            echo json_encode(['error' => 'Current password is incorrect']);
            exit;
        }
        if ($data['new_password'] !== $data['confirm_password']) {
            echo json_encode(['error' => 'New passwords do not match']);
            exit;
        }
        $hashed = password_hash($data['new_password'], PASSWORD_DEFAULT);
        $updates[] = "password='$hashed'";
    }

    if (!empty($updates)) {
        $conn->query("UPDATE users SET " . implode(',', $updates) . " WHERE id=$user_id");
    }

    echo json_encode(['success' => true]);
}
?>
