<?php
require_once 'config.php';

// Quick test to verify chat functionality
echo "<h2>Chat System Test</h2>";
echo "<hr>";

// Test 1: Check users
echo "<h3>1. Users in Database:</h3>";
$users = $conn->query("SELECT id, username, email, role FROM users");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th></tr>";
while ($user = $users->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$user['id']}</td>";
    echo "<td>{$user['username']}</td>";
    echo "<td>{$user['email']}</td>";
    echo "<td>{$user['role']}</td>";
    echo "</tr>";
}
echo "</table>";

// Test 2: Check chats
echo "<h3>2. Existing Chats:</h3>";
$chats = $conn->query("SELECT c.*, u.username as creator FROM chats c LEFT JOIN users u ON c.created_by = u.id");
if ($chats->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th><th>Type</th><th>Created By</th><th>Participants</th></tr>";
    while ($chat = $chats->fetch_assoc()) {
        $chat_id = $chat['id'];
        $participants = $conn->query("SELECT u.username FROM users u JOIN participants p ON u.id = p.user_id WHERE p.chat_id = $chat_id");
        $participant_names = [];
        while ($p = $participants->fetch_assoc()) {
            $participant_names[] = $p['username'];
        }
        
        echo "<tr>";
        echo "<td>{$chat['id']}</td>";
        echo "<td>{$chat['name']}</td>";
        echo "<td>{$chat['type']}</td>";
        echo "<td>{$chat['creator']}</td>";
        echo "<td>" . implode(', ', $participant_names) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No chats found. Create one from the chat interface!</p>";
}

// Test 3: Check messages
echo "<h3>3. Recent Messages:</h3>";
$messages = $conn->query("SELECT m.*, u.username, c.name as chat_name FROM messages m 
                          JOIN users u ON m.user_id = u.id 
                          JOIN chats c ON m.chat_id = c.id 
                          ORDER BY m.timestamp DESC LIMIT 20");
if ($messages->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Chat</th><th>User</th><th>Message</th><th>Time</th></tr>";
    while ($msg = $messages->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$msg['chat_name']}</td>";
        echo "<td>{$msg['username']}</td>";
        echo "<td>" . htmlspecialchars($msg['content']) . "</td>";
        echo "<td>{$msg['timestamp']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No messages yet. Start chatting!</p>";
}

echo "<hr>";
echo "<h3>Quick Actions:</h3>";
echo "<ul>";
echo "<li><a href='index.php'>Login</a></li>";
echo "<li><a href='register.php'>Register New User</a></li>";
echo "<li><a href='chat.php'>Go to Chat</a></li>";
echo "</ul>";

echo "<hr>";
echo "<h3>How to Test Chat Between Users:</h3>";
echo "<ol>";
echo "<li>Register two users (or use admin + create a new user)</li>";
echo "<li>Login as User 1</li>";
echo "<li>Click 'New Chat' and enter User 2's username</li>";
echo "<li>Send a message</li>";
echo "<li>Open another browser (or incognito window)</li>";
echo "<li>Login as User 2</li>";
echo "<li>You should see the chat in the list</li>";
echo "<li>Click the chat to see messages</li>";
echo "<li>Click the 🔄 button to refresh and see new messages</li>";
echo "</ol>";
?>
