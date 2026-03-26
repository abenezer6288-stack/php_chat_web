<?php
require_once 'config.php';

if (isLoggedIn()) {
    header('Location: chat.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Update status to online
            $conn->query("UPDATE users SET status = 'online' WHERE id = {$user['id']}");
            
            header('Location: chat.php');
            exit;
        }
    }
    $error = 'Invalid email or password';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Chat System - Login</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/theme.js"></script>
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h1>School Chat System</h1>
            <h2>Login</h2>
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Login</button>
            </form>
            <p>Don't have an account? <a href="register.php">Register here</a></p>

        </div>
    </div>
</body>
</html>
