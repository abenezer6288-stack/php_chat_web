let currentChatId = null;
let messagePolling = null;
let chatPolling = null;
let lastMessageId = 0;
let isUserScrolling = false;
let scrollTimeout = null;

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadChats();
    setupEventListeners();
    // startPolling(); // DISABLED - No auto-refresh
});

// Setup event listeners
function setupEventListeners() {
    document.getElementById('sendBtn').addEventListener('click', sendMessage);
    document.getElementById('messageText').addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
    
    document.getElementById('attachBtn').addEventListener('click', function() {
        document.getElementById('fileInput').click();
    });
    
    document.getElementById('fileInput').addEventListener('change', handleFileUpload);
    
    document.getElementById('searchUsers').addEventListener('input', function(e) {
        searchUsers(e.target.value);
    });
    
    document.getElementById('newChatBtn').addEventListener('click', showNewChatDialog);
    
    const newGroupBtn = document.getElementById('newGroupBtn');
    if (newGroupBtn) {
        newGroupBtn.addEventListener('click', showNewGroupDialog);
    }
    
    // Scroll to bottom button
    document.getElementById('scrollToBottom').addEventListener('click', scrollToBottom);
    
    // Settings
    document.getElementById('settingsBtn').addEventListener('click', openSettings);
    document.getElementById('closeSettings').addEventListener('click', closeSettings);
    document.getElementById('saveSettings').addEventListener('click', saveSettings);
    document.getElementById('settingsModal').addEventListener('click', function(e) {
        if (e.target === this) closeSettings();
    });

    // Mobile sidebar toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const sidebar = document.querySelector('.sidebar');

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            sidebarOverlay.classList.toggle('visible');
        });
    }
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('visible');
        });
    }
    
    // Monitor scroll position
    const messageArea = document.getElementById('messageArea');
    messageArea.addEventListener('scroll', handleScroll);
}

// Handle scroll events
function handleScroll() {
    const messageArea = document.getElementById('messageArea');
    const scrollBtn = document.getElementById('scrollToBottom');
    
    // Check if user is near bottom (within 100px)
    const isNearBottom = messageArea.scrollHeight - messageArea.scrollTop - messageArea.clientHeight < 100;
    
    if (isNearBottom) {
        scrollBtn.classList.remove('show');
        isUserScrolling = false;
    } else {
        scrollBtn.classList.add('show');
        isUserScrolling = true;
    }
    
    // Clear existing timeout
    clearTimeout(scrollTimeout);
    
    // Set timeout to detect when user stops scrolling
    scrollTimeout = setTimeout(() => {
        // User has stopped scrolling
    }, 150);
}

// Scroll to bottom smoothly
function scrollToBottom() {
    const messageArea = document.getElementById('messageArea');
    messageArea.scrollTo({
        top: messageArea.scrollHeight,
        behavior: 'smooth'
    });
}

// Load all chats
function loadChats() {
    fetch('api/chats.php?action=list')
        .then(res => res.json())
        .then(chats => {
            const chatList = document.getElementById('chatList');
            chatList.innerHTML = '';
            
            chats.forEach(chat => {
                const chatItem = createChatItem(chat);
                chatList.appendChild(chatItem);
            });
        });
}

// Create chat item element
function createChatItem(chat) {
    const div = document.createElement('div');
    div.className = 'chat-item';
    div.dataset.chatId = chat.id;
    
    let chatName = chat.name;
    if (chat.type === 'private') {
        const otherUser = chat.participants.find(p => p.id != userId);
        chatName = otherUser ? otherUser.username : 'Unknown';
    }
    
    const unreadBadge = chat.unread_count > 0 ? 
        `<span class="unread-badge">${chat.unread_count}</span>` : '';
    
    div.innerHTML = `
        <h4>${chatName} ${unreadBadge}</h4>
        <p>${chat.last_message || 'No messages yet'}</p>
    `;
    
    div.addEventListener('click', () => openChat(chat.id));
    
    return div;
}

// Open a chat
function openChat(chatId) {
    currentChatId = chatId;
    
    // Update active state
    document.querySelectorAll('.chat-item').forEach(item => {
        item.classList.remove('active');
    });
    document.querySelector(`[data-chat-id="${chatId}"]`).classList.add('active');
    
    // Load chat details
    fetch(`api/chats.php?action=get&id=${chatId}`)
        .then(res => res.json())
        .then(chat => {
            // Close sidebar on mobile after selecting a chat
            document.querySelector('.sidebar').classList.remove('open');
            const overlay = document.getElementById('sidebarOverlay');
            if (overlay) overlay.classList.remove('visible');
            let chatName = chat.name;
            if (chat.type === 'private') {
                const otherUser = chat.participants.find(p => p.id != userId);
                chatName = otherUser ? otherUser.username : 'Unknown';
            }
            
            document.getElementById('chatHeader').innerHTML = `
                <div class="chat-header-info">
                    <button class="mobile-menu-btn" onclick="document.querySelector('.sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('visible')" title="Menu">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                    </button>
                    <div class="chat-header-avatar">${chatName.charAt(0).toUpperCase()}</div>
                    <div>
                        <div class="chat-header-name">${chatName}</div>
                        <div class="chat-header-sub">${chat.type === 'group' ? 'Group' : 'Private'}</div>
                    </div>
                </div>
                <button class="theme-toggle chat-theme-toggle" onclick="toggleTheme()" title="Toggle theme"></button>
                <button id="settingsBtn" class="settings-btn" title="Settings" onclick="openSettings()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                    </svg>
                </button>
            `;
            if (typeof updateThemeIcons === 'function') updateThemeIcons();
            
            document.getElementById('messageText').disabled = false;
            document.getElementById('sendBtn').disabled = false;
        });
    
    loadMessages(chatId);
}

// Load messages for a chat
function loadMessages(chatId, preserveScroll = false) {
    const messageArea = document.getElementById('messageArea');
    
    fetch(`api/messages.php?action=list&chat_id=${chatId}`)
        .then(res => res.json())
        .then(messages => {
            if (messages.length === 0) {
                messageArea.innerHTML = '<div style="text-align:center;color:#999;padding:40px;">No messages yet. Start the conversation!</div>';
                return;
            }
            
            // Check if this is incremental update (only new messages)
            if (preserveScroll && lastMessageId > 0) {
                // Only add new messages
                const newMessages = messages.filter(msg => msg.id > lastMessageId);
                
                if (newMessages.length > 0) {
                    newMessages.forEach(msg => {
                        // Check if we need a new date separator
                        const lastDateSep = messageArea.querySelector('.date-separator:last-of-type');
                        const msgDate = getDateLabel(new Date(msg.timestamp));
                        
                        if (!lastDateSep || !lastDateSep.textContent.includes(msgDate)) {
                            const separator = document.createElement('div');
                            separator.className = 'date-separator';
                            separator.innerHTML = `<span>${msgDate}</span>`;
                            messageArea.appendChild(separator);
                        }
                        
                        // Check if we should show avatar (different user from last message)
                        const lastMessage = messageArea.querySelector('.message:last-of-type');
                        const lastUserId = lastMessage ? lastMessage.dataset.userId : null;
                        const showAvatar = msg.user_id != lastUserId;
                        
                        const messageEl = createMessageElement(msg, showAvatar);
                        messageArea.appendChild(messageEl);
                    });
                    
                    lastMessageId = messages[messages.length - 1].id;
                    
                    // NO AUTO-SCROLL - messages appear silently
                }
            } else {
                // Initial load - rebuild everything
                const groupedMessages = groupMessagesByDate(messages);
                
                if (messages.length > 0) {
                    lastMessageId = messages[messages.length - 1].id;
                }
                
                messageArea.innerHTML = '';
                
                Object.keys(groupedMessages).forEach(date => {
                    const separator = document.createElement('div');
                    separator.className = 'date-separator';
                    separator.innerHTML = `<span>${date}</span>`;
                    messageArea.appendChild(separator);
                    
                    let lastUserId = null;
                    groupedMessages[date].forEach((msg, index) => {
                        // Show avatar only if different user from previous message
                        const showAvatar = msg.user_id !== lastUserId;
                        const messageEl = createMessageElement(msg, showAvatar);
                        messageArea.appendChild(messageEl);
                        lastUserId = msg.user_id;
                    });
                });
                
                // Scroll to bottom ONLY on initial load
                setTimeout(() => {
                    messageArea.scrollTop = messageArea.scrollHeight;
                }, 100);
            }
        });
}

// Get date label for a message
function getDateLabel(msgDate) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);
    
    const lastWeek = new Date(today);
    lastWeek.setDate(lastWeek.getDate() - 7);
    
    const dateOnly = new Date(msgDate);
    dateOnly.setHours(0, 0, 0, 0);
    
    if (dateOnly.getTime() === today.getTime()) {
        return 'Today';
    } else if (dateOnly.getTime() === yesterday.getTime()) {
        return 'Yesterday';
    } else if (dateOnly > lastWeek) {
        return dateOnly.toLocaleDateString('en-US', { weekday: 'long' });
    } else {
        return dateOnly.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }
}

// Group messages by date
function groupMessagesByDate(messages) {
    const groups = {};
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);
    
    const lastWeek = new Date(today);
    lastWeek.setDate(lastWeek.getDate() - 7);
    
    messages.forEach(msg => {
        const msgDate = new Date(msg.timestamp);
        msgDate.setHours(0, 0, 0, 0);
        
        let dateLabel;
        if (msgDate.getTime() === today.getTime()) {
            dateLabel = 'Today';
        } else if (msgDate.getTime() === yesterday.getTime()) {
            dateLabel = 'Yesterday';
        } else if (msgDate > lastWeek) {
            dateLabel = msgDate.toLocaleDateString('en-US', { weekday: 'long' });
        } else {
            dateLabel = msgDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        }
        
        if (!groups[dateLabel]) {
            groups[dateLabel] = [];
        }
        groups[dateLabel].push(msg);
    });
    
    return groups;
}

// Create message element - WhatsApp style
function createMessageElement(msg, showAvatar = true) {
    const div = document.createElement('div');
    div.className = 'message' + (msg.user_id == userId ? ' own' : '');
    div.dataset.messageId = msg.id;
    div.dataset.userId = msg.user_id;
    div.dataset.timestamp = msg.timestamp; // Store full timestamp for ordering
    
    const initial = msg.username.charAt(0).toUpperCase();
    
    // Parse timestamp with microseconds
    const msgDate = new Date(msg.timestamp);
    const time = msgDate.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    
    // Better file display
    let attachmentHtml = '';
    if (msg.attachment) {
        const fileName = msg.attachment.split('_').slice(1).join('_'); // Remove unique prefix
        const fileExt = fileName.split('.').pop().toLowerCase();
        
        let fileIcon = '📎';
        if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExt)) {
            fileIcon = '🖼️';
        } else if (fileExt === 'pdf') {
            fileIcon = '📄';
        } else if (['doc', 'docx'].includes(fileExt)) {
            fileIcon = '📝';
        }
        
        attachmentHtml = `<div class="message-attachment"><a href="uploads/${msg.attachment}" target="_blank">${fileIcon} ${fileName}</a></div>`;
    }
    
    // Show avatar only for first message in a group
    const avatarHtml = (showAvatar && msg.user_id != userId) ? 
        `<div class="message-avatar">${initial}</div>` : 
        `<div class="message-avatar" style="visibility:hidden;"></div>`;
    
    div.innerHTML = `
        ${msg.user_id != userId ? avatarHtml : ''}
        <div class="message-content">
            ${(showAvatar && msg.user_id != userId) ? `<div class="message-sender">${msg.username}</div>` : ''}
            <div class="message-bubble">
                <div class="message-text">${escapeHtml(msg.content)}</div>
                ${attachmentHtml}
                <span class="message-time">${time}</span>
            </div>
        </div>
    `;
    
    return div;
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Send message
function sendMessage() {
    const input = document.getElementById('messageText');
    const content = input.value.trim();
    
    if (!content || !currentChatId) return;
    
    // Disable input while sending
    input.disabled = true;
    
    fetch('api/messages.php?action=send', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            chat_id: currentChatId,
            content: content
        })
    })
    .then(res => res.json())
    .then(msg => {
        input.value = '';
        input.disabled = false;
        input.focus();
        
        // Add message instantly without full reload
        const messageArea = document.getElementById('messageArea');
        const msgDate = getDateLabel(new Date(msg.timestamp));
        const lastDateSep = messageArea.querySelector('.date-separator:last-of-type');
        
        if (!lastDateSep || !lastDateSep.textContent.includes(msgDate)) {
            const separator = document.createElement('div');
            separator.className = 'date-separator';
            separator.innerHTML = `<span>${msgDate}</span>`;
            messageArea.appendChild(separator);
        }
        
        // Check if we should show avatar
        const lastMessage = messageArea.querySelector('.message:last-of-type');
        const lastUserId = lastMessage ? lastMessage.dataset.userId : null;
        const showAvatar = msg.user_id != lastUserId;
        
        const messageEl = createMessageElement(msg, showAvatar);
        messageArea.appendChild(messageEl);
        
        lastMessageId = msg.id;
        
        // NO AUTO-SCROLL - even when you send
        // User can manually scroll if they want
        
        loadChats(); // Refresh chat list only
    })
    .catch(err => {
        console.error('Error sending message:', err);
        input.disabled = false;
    });
}

// Handle file upload
function handleFileUpload(e) {
    const file = e.target.files[0];
    if (!file) return;
    
    // Show uploading indicator
    const input = document.getElementById('messageText');
    input.placeholder = 'Uploading file...';
    input.disabled = true;
    
    const formData = new FormData();
    formData.append('file', file);
    
    fetch('api/messages.php?action=upload', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const fileName = file.name;
            fetch('api/messages.php?action=send', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    chat_id: currentChatId,
                    content: fileName,
                    attachment: data.filename
                })
            })
            .then(res => res.json())
            .then(msg => {
                // Add message instantly
                const messageArea = document.getElementById('messageArea');
                const msgDate = getDateLabel(new Date(msg.timestamp));
                const lastDateSep = messageArea.querySelector('.date-separator:last-of-type');
                
                if (!lastDateSep || !lastDateSep.textContent.includes(msgDate)) {
                    const separator = document.createElement('div');
                    separator.className = 'date-separator';
                    separator.innerHTML = `<span>${msgDate}</span>`;
                    messageArea.appendChild(separator);
                }
                
                // Check if we should show avatar
                const lastMessage = messageArea.querySelector('.message:last-of-type');
                const lastUserId = lastMessage ? lastMessage.dataset.userId : null;
                const showAvatar = msg.user_id != lastUserId;
                
                const messageEl = createMessageElement(msg, showAvatar);
                messageArea.appendChild(messageEl);
                
                lastMessageId = msg.id;
                
                // NO AUTO-SCROLL
                
                loadChats();
                
                input.placeholder = 'Type a message...';
                input.disabled = false;
            });
        } else {
            alert('File upload failed: ' + (data.error || 'Unknown error'));
            input.placeholder = 'Type a message...';
            input.disabled = false;
        }
    })
    .catch(err => {
        console.error('Upload error:', err);
        alert('File upload failed');
        input.placeholder = 'Type a message...';
        input.disabled = false;
    });
    
    // Reset file input
    e.target.value = '';
}

// Search users
function searchUsers(query) {
    fetch(`api/users.php?action=list&search=${query}`)
        .then(res => res.json())
        .then(users => {
            // Display search results
            console.log('Search results:', users);
        });
}

// Show new chat dialog
function showNewChatDialog() {
    const username = prompt('Enter username to chat with:');
    if (!username) return;
    
    fetch(`api/users.php?action=list&search=${username}`)
        .then(res => res.json())
        .then(users => {
            if (users.length === 0) {
                alert('User not found');
                return;
            }
            
            const user = users[0];
            createChat('private', user.username, [user.id]);
        });
}

// Show new group dialog
function showNewGroupDialog() {
    const groupName = prompt('Enter group name:');
    if (!groupName) return;
    
    const userIds = prompt('Enter user IDs to add (comma separated):');
    if (!userIds) return;
    
    const participants = userIds.split(',').map(id => parseInt(id.trim()));
    createChat('group', groupName, participants);
}

// Create new chat
function createChat(type, name, participants) {
    fetch('api/chats.php?action=create', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            type: type,
            name: name,
            participants: participants
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            loadChats();
            openChat(data.chat_id);
        }
    });
}

// ── Settings ──────────────────────────────────────────────
function openSettings() {
    fetch('api/auth.php?action=me')
        .then(r => r.json())
        .then(user => {
            const initial = user.username.charAt(0).toUpperCase();
            document.getElementById('settingsAvatar').textContent = initial;
            document.getElementById('settingsName').textContent = user.username;
            document.getElementById('settingsEmail').textContent = user.email;
            document.getElementById('settingsUsername').value = user.username;
            document.getElementById('settingsEmailInput').value = user.email;
            document.getElementById('settingsStatus').value = user.status || 'online';
            document.getElementById('settingsCurrentPwd').value = '';
            document.getElementById('settingsNewPwd').value = '';
            document.getElementById('settingsConfirmPwd').value = '';
            // collapse password section
            document.getElementById('pwdFields').classList.add('hidden');
            document.getElementById('pwdToggleIcon').textContent = '▼';
            setSettingsMsg('', '');
        });
    document.getElementById('settingsModal').classList.remove('hidden');
}

function closeSettings() {
    document.getElementById('settingsModal').classList.add('hidden');
}

function togglePwdFields() {
    const fields = document.getElementById('pwdFields');
    const icon = document.getElementById('pwdToggleIcon');
    const hidden = fields.classList.toggle('hidden');
    icon.textContent = hidden ? '▼' : '▲';
}

function setSettingsMsg(text, type) {
    const el = document.getElementById('settingsMsg');
    el.textContent = text;
    el.className = 'settings-msg' + (text ? '' : ' hidden') + (type ? ' ' + type : '');
}

function saveSettings() {
    const username = document.getElementById('settingsUsername').value.trim();
    const email = document.getElementById('settingsEmailInput').value.trim();
    const status = document.getElementById('settingsStatus').value;
    const currentPwd = document.getElementById('settingsCurrentPwd').value;
    const newPwd = document.getElementById('settingsNewPwd').value;
    const confirmPwd = document.getElementById('settingsConfirmPwd').value;

    if (!username || !email) {
        setSettingsMsg('Username and email are required.', 'error');
        return;
    }

    if (newPwd && newPwd !== confirmPwd) {
        setSettingsMsg('New passwords do not match.', 'error');
        return;
    }

    const payload = { username, email, status };
    if (newPwd) {
        payload.current_password = currentPwd;
        payload.new_password = newPwd;
        payload.confirm_password = confirmPwd;
    }

    const btn = document.getElementById('saveSettings');
    btn.disabled = true;
    btn.textContent = 'Saving...';

    fetch('api/users.php?action=update', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.textContent = 'Save Changes';
        if (data.error) {
            setSettingsMsg(data.error, 'error');
        } else {
            setSettingsMsg('Settings saved successfully.', 'success');
            document.getElementById('settingsName').textContent = username;
            document.getElementById('settingsEmail').textContent = email;
            document.getElementById('settingsAvatar').textContent = username.charAt(0).toUpperCase();
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.textContent = 'Save Changes';
        setSettingsMsg('Something went wrong. Try again.', 'error');
    });
}

// Start polling for new messages
function startPolling() {
    // POLLING DISABLED - No auto-refresh
    // Messages only load when you open a chat or send a message
    // Use manual refresh if needed
}

// Update user status on page unload
window.addEventListener('beforeunload', function() {
    fetch('api/users.php?action=status', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({status: 'offline'}),
        keepalive: true
    });
});
