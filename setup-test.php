<?php
// Test database connection and setup

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'library_chat');

echo "<h2>Library Chat System - Setup Test</h2>";
echo "<hr>";

// Test 1: Database Connection
echo "<h3>1. Testing Database Connection...</h3>";
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

if ($conn->connect_error) {
    echo "❌ <strong>Connection failed:</strong> " . $conn->connect_error . "<br>";
    echo "Please check your database credentials in config.php<br>";
    exit;
} else {
    echo "✅ Connected to MySQL successfully<br>";
}

// Test 2: Check if database exists
echo "<h3>2. Checking Database...</h3>";
$result = $conn->query("SHOW DATABASES LIKE 'library_chat'");
if ($result->num_rows == 0) {
    echo "⚠️ Database 'library_chat' does not exist. Creating it...<br>";
    if ($conn->query("CREATE DATABASE library_chat")) {
        echo "✅ Database created successfully<br>";
    } else {
        echo "❌ Error creating database: " . $conn->error . "<br>";
        exit;
    }
} else {
    echo "✅ Database 'library_chat' exists<br>";
}

// Select the database
$conn->select_db(DB_NAME);

// Test 3: Check if tables exist
echo "<h3>3. Checking Tables...</h3>";
$tables = ['users', 'chats', 'messages', 'participants'];
$missing_tables = [];

foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows == 0) {
        echo "⚠️ Table '$table' does not exist<br>";
        $missing_tables[] = $table;
    } else {
        echo "✅ Table '$table' exists<br>";
    }
}

if (!empty($missing_tables)) {
    echo "<br><strong>Creating missing tables...</strong><br>";
    
    // Create users table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('student', 'teacher', 'admin') DEFAULT 'student',
        avatar VARCHAR(255) DEFAULT 'default.png',
        status ENUM('online', 'offline', 'away') DEFAULT 'offline',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    if ($conn->query($sql)) {
        echo "✅ Users table created<br>";
    }
    
    // Create chats table
    $sql = "CREATE TABLE IF NOT EXISTS chats (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        type ENUM('private', 'group') DEFAULT 'private',
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    )";
    if ($conn->query($sql)) {
        echo "✅ Chats table created<br>";
    }
    
    // Create messages table
    $sql = "CREATE TABLE IF NOT EXISTS messages (
        id INT PRIMARY KEY AUTO_INCREMENT,
        chat_id INT NOT NULL,
        user_id INT NOT NULL,
        content TEXT NOT NULL,
        attachment VARCHAR(255),
        is_read BOOLEAN DEFAULT FALSE,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (chat_id) REFERENCES chats(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    if ($conn->query($sql)) {
        echo "✅ Messages table created<br>";
    }
    
    // Create participants table
    $sql = "CREATE TABLE IF NOT EXISTS participants (
        id INT PRIMARY KEY AUTO_INCREMENT,
        chat_id INT NOT NULL,
        user_id INT NOT NULL,
        joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (chat_id) REFERENCES chats(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_participant (chat_id, user_id)
    )";
    if ($conn->query($sql)) {
        echo "✅ Participants table created<br>";
    }
}

// Test 4: Check for admin user
echo "<h3>4. Checking Admin User...</h3>";
$result = $conn->query("SELECT * FROM users WHERE email = 'admin@library.com'");

if ($result->num_rows == 0) {
    echo "⚠️ Admin user does not exist. Creating it...<br>";
    
    // Create new password hash for 'admin123'
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    $username = 'admin';
    $email = 'admin@library.com';
    $role = 'admin';
    $stmt->bind_param("ssss", $username, $email, $password, $role);
    
    if ($stmt->execute()) {
        echo "✅ Admin user created successfully<br>";
        echo "<strong>Email:</strong> admin@library.com<br>";
        echo "<strong>Password:</strong> admin123<br>";
    } else {
        echo "❌ Error creating admin user: " . $conn->error . "<br>";
    }
} else {
    $admin = $result->fetch_assoc();
    echo "✅ Admin user exists<br>";
    echo "<strong>Username:</strong> " . $admin['username'] . "<br>";
    echo "<strong>Email:</strong> " . $admin['email'] . "<br>";
    echo "<strong>Role:</strong> " . $admin['role'] . "<br>";
    
    // Test password verification
    echo "<br><strong>Testing password 'admin123'...</strong><br>";
    if (password_verify('admin123', $admin['password'])) {
        echo "✅ Password verification successful<br>";
    } else {
        echo "❌ Password verification failed. Resetting password...<br>";
        
        $new_password = password_hash('admin123', PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password = '$new_password' WHERE email = 'admin@library.com'");
        echo "✅ Password reset to 'admin123'<br>";
    }
}

// Test 5: Count users
echo "<h3>5. User Statistics...</h3>";
$result = $conn->query("SELECT COUNT(*) as count FROM users");
$count = $result->fetch_assoc()['count'];
echo "Total users in database: <strong>$count</strong><br>";

$result = $conn->query("SELECT username, email, role FROM users");
echo "<br><strong>All users:</strong><br>";
echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>Username</th><th>Email</th><th>Role</th></tr>";
while ($user = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $user['username'] . "</td>";
    echo "<td>" . $user['email'] . "</td>";
    echo "<td>" . $user['role'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";
echo "<h3>✅ Setup Complete!</h3>";
echo "<p>You can now try logging in:</p>";
echo "<ul>";
echo "<li><a href='index.php'>Regular Login</a></li>";
echo "<li><a href='admin-login.php'>Admin Login</a></li>";
echo "<li><a href='register.php'>Register New User</a></li>";
echo "</ul>";
echo "<p><strong>Admin Credentials:</strong><br>Email: admin@library.com<br>Password: admin123</p>";

$conn->close();
?>
