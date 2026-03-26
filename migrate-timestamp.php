<?php
require_once 'config.php';

echo "<h2>Database Migration - Add Microsecond Precision</h2>";
echo "<hr>";

// Check current timestamp column type
$result = $conn->query("SHOW COLUMNS FROM messages LIKE 'timestamp'");
$column = $result->fetch_assoc();

echo "<h3>Current timestamp column:</h3>";
echo "<pre>";
print_r($column);
echo "</pre>";

// Modify the timestamp column to support microseconds
echo "<h3>Updating timestamp column to support microseconds...</h3>";

$sql = "ALTER TABLE messages MODIFY COLUMN timestamp TIMESTAMP(6) DEFAULT CURRENT_TIMESTAMP(6)";

if ($conn->query($sql)) {
    echo "✅ <strong>Success!</strong> Timestamp column now supports microsecond precision<br>";
} else {
    echo "❌ <strong>Error:</strong> " . $conn->error . "<br>";
}

// Add index for better performance
echo "<h3>Adding index for better query performance...</h3>";

$sql = "ALTER TABLE messages ADD INDEX IF NOT EXISTS idx_chat_timestamp (chat_id, timestamp)";

if ($conn->query($sql)) {
    echo "✅ <strong>Success!</strong> Index added<br>";
} else {
    // Index might already exist
    if (strpos($conn->error, 'Duplicate key name') !== false) {
        echo "ℹ️ Index already exists<br>";
    } else {
        echo "❌ <strong>Error:</strong> " . $conn->error . "<br>";
    }
}

// Verify the change
echo "<h3>Verifying changes...</h3>";
$result = $conn->query("SHOW COLUMNS FROM messages LIKE 'timestamp'");
$column = $result->fetch_assoc();

echo "<pre>";
print_r($column);
echo "</pre>";

if (strpos($column['Type'], '(6)') !== false) {
    echo "✅ <strong>Migration successful!</strong> Messages will now be ordered with microsecond precision.<br>";
} else {
    echo "⚠️ <strong>Warning:</strong> Timestamp column might not have microsecond precision.<br>";
}

echo "<hr>";
echo "<h3>Test Message Ordering:</h3>";

// Show recent messages with full timestamp
$result = $conn->query("SELECT m.id, u.username, m.content, 
                        DATE_FORMAT(m.timestamp, '%Y-%m-%d %H:%i:%s.%f') as full_timestamp
                        FROM messages m 
                        JOIN users u ON m.user_id = u.id 
                        ORDER BY m.timestamp DESC, m.id DESC 
                        LIMIT 10");

if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>User</th><th>Message</th><th>Timestamp (with microseconds)</th></tr>";
    while ($msg = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$msg['id']}</td>";
        echo "<td>{$msg['username']}</td>";
        echo "<td>" . htmlspecialchars(substr($msg['content'], 0, 50)) . "</td>";
        echo "<td>{$msg['full_timestamp']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No messages found. Send some messages to test!</p>";
}

echo "<hr>";
echo "<p><a href='chat.php'>Go to Chat</a> | <a href='test-chat.php'>Test Page</a></p>";
?>
