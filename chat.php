<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - Library Chat System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="chat-container">
        <div class="sidebar">
            <div class="user-info">
                <div class="user-info-top">
                    <h3><?php echo htmlspecialchars($username); ?></h3>
                    <span class="role-badge"><?php echo ucfirst($role); ?></span>
                </div>
                <div class="user-info-actions">
                    <?php if ($role === 'admin'): ?>
                        <a href="admin.php" class="admin-link">⚙ Admin</a>
                    <?php endif; ?>
                    <a href="api/auth.php?action=logout" class="logout-btn">↩ Logout</a>
                </div>
            </div>

            <div class="chat-controls">
                <button id="newChatBtn" class="btn-primary">+ New Chat</button>
                <?php if ($role === 'teacher' || $role === 'admin'): ?>
                    <button id="newGroupBtn" class="btn-secondary">👥 Group</button>
                <?php endif; ?>
            </div>

            <div class="search-box">
                <input type="text" id="searchUsers" placeholder="Search conversations...">
            </div>

            <div id="chatList" class="chat-list"></div>
        </div>

        <div class="main-chat">
            <div id="chatHeader" class="chat-header">
                <div class="chat-header-info">
                    <h3>Select a conversation</h3>
                </div>
                <button id="settingsBtn" class="settings-btn" title="Settings">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                    </svg>
                </button>
            </div>

            <!-- Settings Modal -->
            <div id="settingsModal" class="settings-modal hidden">
                <div class="settings-panel">
                    <div class="settings-header">
                        <div class="settings-header-left">
                            <div id="settingsAvatar" class="settings-avatar-sm"></div>
                            <div>
                                <div id="settingsName" class="settings-display-name"></div>
                                <div id="settingsEmail" class="settings-display-email"></div>
                            </div>
                        </div>
                        <button id="closeSettings" class="close-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>

                    <div class="settings-body">
                        <div class="settings-row">
                            <div class="settings-field">
                                <label>Username</label>
                                <input type="text" id="settingsUsername" placeholder="Username">
                            </div>
                            <div class="settings-field">
                                <label>Email</label>
                                <input type="email" id="settingsEmailInput" placeholder="Email">
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="settings-field" style="flex:1">
                                <label>Status</label>
                                <select id="settingsStatus">
                                    <option value="online">🟢 Online</option>
                                    <option value="away">🟡 Away</option>
                                    <option value="offline">⚫ Offline</option>
                                </select>
                            </div>
                        </div>

                        <div class="settings-pwd-toggle" onclick="togglePwdFields()">
                            <span id="pwdToggleLabel">🔒 Change Password</span>
                            <span id="pwdToggleIcon">▼</span>
                        </div>

                        <div id="pwdFields" class="settings-pwd-fields hidden">
                            <div class="settings-row">
                                <div class="settings-field">
                                    <label>Current</label>
                                    <input type="password" id="settingsCurrentPwd" placeholder="Current password">
                                </div>
                                <div class="settings-field">
                                    <label>New</label>
                                    <input type="password" id="settingsNewPwd" placeholder="New password">
                                </div>
                            </div>
                            <div class="settings-field">
                                <label>Confirm</label>
                                <input type="password" id="settingsConfirmPwd" placeholder="Confirm new password">
                            </div>
                        </div>

                        <div id="settingsMsg" class="settings-msg hidden"></div>

                        <button id="saveSettings" class="settings-save-btn">Save</button>
                    </div>
                </div>
            </div>

            <div id="messageArea" class="message-area"></div>

            <button id="scrollToBottom" class="scroll-to-bottom">↓</button>

            <div class="message-input">
                <input type="file" id="fileInput" style="display:none" accept="image/*,.pdf,.doc,.docx">
                <button id="attachBtn" class="attach-btn" title="Attach file">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>
                    </svg>
                </button>
                <input type="text" id="messageText" placeholder="Type a message..." disabled>
                <button id="sendBtn" class="send-btn" disabled title="Send">➤</button>
            </div>
        </div>
    </div>
    
    <script>
        const userId = <?php echo $user_id; ?>;
        const userRole = '<?php echo $role; ?>';
    </script>
    <script src="js/app.js"></script>
</body>
</html>
