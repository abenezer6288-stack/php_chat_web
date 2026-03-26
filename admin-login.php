<?php
require_once 'config.php';

if (isLoggedIn() && hasRole('admin')) {
    header('Location: admin.php');
    exit;
}

if (isLoggedIn() && !hasRole('admin')) {
    header('Location: chat.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE email = ? AND role = 'admin'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            $conn->query("UPDATE users SET status = 'online' WHERE id = {$user['id']}");
            
            header('Location: admin.php');
            exit;
        }
    }
    $error = 'Invalid admin credentials';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - School Chat System</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .admin-login-box {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
            margin: -40px -40px 20px -40px;
        }
        .admin-login-box h1 {
            color: white;
            margin: 0;
        }
        .admin-login-box p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="admin-login-box">
                <h1>🔐 Admin Access</h1>
                <p>Authorized Personnel Only</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <input type="email" name="email" placeholder="Admin Email" required autofocus>
                <input type="password" name="password" placeholder="Admin Password" required>
                <button type="submit">Login as Admin</button>
            </form>
            
            <p><a href="index.php">← Back to regular login</a></p>
        </div>
    </div>
</body>
</html>
