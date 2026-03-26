<?php
require_once '../config.php';

if ($_GET['action'] === 'logout') {
    if (isLoggedIn()) {
        $conn->query("UPDATE users SET status = 'offline' WHERE id = {$_SESSION['user_id']}");
    }
    session_destroy();
    header('Location: ../index.php');
    exit;
}

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get current user info
if ($_GET['action'] === 'me') {
    $user_id = $_SESSION['user_id'];
    $result = $conn->query("SELECT id, username, email, role, avatar, status FROM users WHERE id = $user_id");
    echo json_encode($result->fetch_assoc());
}
?>
