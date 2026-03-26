<?php
require_once 'config.php';

echo "<h2>Test Message Ordering with Microseconds</h2>";
echo "<hr>";

// Get a chat to test with
$chat = $conn->query("SELECT * FROM chats LIMIT 1")->fetch_assoc();

if (!$chat) {
    echo "<p>No chats found. Create a chat first!</p>";
    exit;
}

$chat_id = $chat['id'];
echo "<h3>Testing with Chat: {$chat['name']} (ID: {$chat_id})</h3>";

// Show all messages with full timestamp
echo "<h3>Messages in Database (with microsecond precision):</h3>";

$result = $conn->query("SELECT m.id, u.username, m.content, 
                        DATE_FORMAT(m.timestamp, '%Y-%m-%d %H:%i:%s.%f') as full_timestamp,
                        UNIX_TIMESTAMP(m.timestamp) as unix_time
                        FROM messages m 
                        JOIN users u ON m.user_id = u.id 
                        WHERE m.chat_id = $chat_id
                        ORDER BY m.timestamp ASC, m.id ASC");

if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background:#f0f0f0;'>";
    echo "<th>Order</th><th>ID</th><th>User</th><th>Message</th><th>Full Timestamp</th><th>Unix Time</th>";
    echo "</tr>";
    
    $order = 1;
    $prevTime = null;
    
    while ($msg = $result->fetch_assoc()) {
        $highlight = '';
        if ($prevTime && $msg['full_timestamp'] === $prevTime) {
            $highlight = 'background:#ffe0e0;'; // Highlight if same timestamp
        }
        
        echo "<tr style='$highlight'>";
        echo "<td><strong>$order</strong></td>";
        echo "<td>{$msg['id']}</td>";
        echo "<td>{$msg['username']}</td>";
        echo "<td>" . htmlspecialchars(substr($msg['content'], 0, 40)) . "</td>";
        echo "<td style='font-family:monospace;'>{$msg['full_timestamp']}</td>";
        echo "<td>{$msg['unix_time']}</td>";
        echo "</tr>";
        
        $prevTime = $msg['full_timestamp'];
        $order++;
    }
    echo "</table>";
    
    echo "<p><small>* Red highlighted rows have the same timestamp (rare but possible)</small></p>";
} else {
    echo "<p>No messages in this chat yet.</p>";
}

echo "<hr>";
echo "<h3>How Message Ordering Works:</h3>";
echo "<ol>";
echo "<li><strong>Primary Sort:</strong> timestamp (with microsecond precision)</li>";
echo "<li><strong>Secondary Sort:</strong> message ID (if timestamps are identical)</li>";
echo "<li>This ensures messages always appear in the exact order they were sent</li>";
echo "<li>Even if two messages are sent in the same second, microseconds distinguish them</li>";
echo "</ol>";

echo "<hr>";
echo "<h3>Quick Actions:</h3>";
echo "<ul>";
echo "<li><a href='migrate-timestamp.php'>Run Migration (if not done)</a></li>";
echo "<li><a href='chat.php'>Go to Chat</a></li>";
echo "<li><a href='test-chat.php'>Test Page</a></li>";
echo "</ul>";
?>
