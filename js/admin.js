// ── Tab navigation ──────────────────────────────────────────
const PAGE_TITLES = { dashboard:'Dashboard', users:'User Management', chats:'Chat Management', messages:'Message Logs' };

document.querySelectorAll('.adm-nav-item[data-tab]').forEach(link => {
  link.addEventListener('click', e => {
    e.preventDefault();
    const tab = link.dataset.tab;
    document.querySelectorAll('.adm-nav-item').forEach(l => l.classList.remove('active'));
    link.classList.add('active');
    document.querySelectorAll('.adm-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    document.getElementById('adm-page-title').textContent = PAGE_TITLES[tab];
    if (tab === 'users')    loadUsers();
    if (tab === 'chats')    loadChats();
    if (tab === 'messages') loadMessages();
  });
});

// ── Charts ──────────────────────────────────────────────────
new Chart(document.getElementById('msgTrendChart'), {
  type: 'line',
  data: {
    labels: TREND_LABELS.length ? TREND_LABELS : ['No data'],
    datasets: [{
      label: 'Messages',
      data: TREND_VALUES.length ? TREND_VALUES : [0],
      borderColor: '#667eea',
      backgroundColor: 'rgba(102,126,234,0.1)',
      borderWidth: 2.5,
      pointBackgroundColor: '#667eea',
      pointRadius: 4,
      fill: true,
      tension: 0.4
    }]
  },
  options: { plugins:{ legend:{ display:false } }, scales:{ y:{ beginAtZero:true, ticks:{ precision:0 } } } }
});

new Chart(document.getElementById('roleChart'), {
  type: 'doughnut',
  data: {
    labels: ['Students','Teachers','Admins'],
    datasets: [{
      data: [ROLE_DATA.student, ROLE_DATA.teacher, ROLE_DATA.admin],
      backgroundColor: ['#667eea','#10b981','#f59e0b'],
      borderWidth: 0
    }]
  },
  options: { plugins:{ legend:{ position:'bottom', labels:{ padding:16, font:{ size:12 } } } }, cutout:'65%' }
});

// ── Helpers ─────────────────────────────────────────────────
function esc(str) {
  const d = document.createElement('div');
  d.textContent = str ?? '';
  return d.innerHTML;
}

function roleBadge(role) {
  return `<span class="adm-badge adm-badge-${role}">${role}</span>`;
}

function statusBadge(status) {
  return `<span class="adm-badge adm-badge-${status}">${status}</span>`;
}

function showModalMsg(id, text, type) {
  const el = document.getElementById(id);
  el.textContent = text;
  el.className = 'adm-modal-msg ' + type;
}

// ── Users ────────────────────────────────────────────────────
let allUsers = [];

function loadUsers() {
  fetch('api/admin.php?action=users')
    .then(r => r.json())
    .then(users => {
      allUsers = users;
      renderUsers(users);
    });
}

function renderUsers(users) {
  const tbody = document.getElementById('usersBody');
  if (!users.length) { tbody.innerHTML = '<tr><td colspan="7" class="adm-loading">No users found.</td></tr>'; return; }
  tbody.innerHTML = users.map(u => `
    <tr>
      <td>${u.id}</td>
      <td><div class="adm-user-cell"><div class="adm-user-avatar">${esc(u.username.charAt(0).toUpperCase())}</div><span class="adm-user-name">${esc(u.username)}</span></div></td>
      <td>${esc(u.email)}</td>
      <td>${roleBadge(u.role)}</td>
      <td>${statusBadge(u.status)}</td>
      <td>${u.created_at ? u.created_at.split(' ')[0] : '—'}</td>
      <td><div class="adm-actions">
        <button class="adm-btn adm-btn-sm" onclick="openEditUser(${u.id})">Edit</button>
        ${u.is_self ? '' : `<button class="adm-btn adm-btn-sm adm-btn-danger" onclick="confirmDelete('user',${u.id},'${esc(u.username)}')">Delete</button>`}
      </div></td>
    </tr>`).join('');
}

document.getElementById('userSearch').addEventListener('input', function() {
  const q = this.value.toLowerCase();
  renderUsers(allUsers.filter(u => u.username.toLowerCase().includes(q) || u.email.toLowerCase().includes(q)));
});

// Add user
function openAddUser() {
  document.getElementById('userModalTitle').textContent = 'Add User';
  document.getElementById('editUserId').value = '';
  document.getElementById('editUsername').value = '';
  document.getElementById('editEmail').value = '';
  document.getElementById('editRole').value = 'student';
  document.getElementById('editPassword').value = '';
  document.getElementById('pwdHint').style.display = 'none';
  document.getElementById('userModalMsg').className = 'adm-modal-msg hidden';
  document.getElementById('userModal').classList.remove('hidden');
}

function openEditUser(id) {
  const u = allUsers.find(x => x.id == id);
  if (!u) return;
  document.getElementById('userModalTitle').textContent = 'Edit User';
  document.getElementById('editUserId').value = u.id;
  document.getElementById('editUsername').value = u.username;
  document.getElementById('editEmail').value = u.email;
  document.getElementById('editRole').value = u.role;
  document.getElementById('editPassword').value = '';
  document.getElementById('pwdHint').style.display = '';
  document.getElementById('userModalMsg').className = 'adm-modal-msg hidden';
  document.getElementById('userModal').classList.remove('hidden');
}

function closeUserModal() { document.getElementById('userModal').classList.add('hidden'); }

function saveUser() {
  const id       = document.getElementById('editUserId').value;
  const username = document.getElementById('editUsername').value.trim();
  const email    = document.getElementById('editEmail').value.trim();
  const role     = document.getElementById('editRole').value;
  const password = document.getElementById('editPassword').value;

  if (!username || !email) { showModalMsg('userModalMsg','Username and email are required.','error'); return; }
  if (!id && !password)    { showModalMsg('userModalMsg','Password is required for new users.','error'); return; }

  const payload = { id, username, email, role, password };

  fetch('api/admin.php?action=' + (id ? 'update_user' : 'add_user'), {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
  .then(r => r.json())
  .then(data => {
    if (data.error) { showModalMsg('userModalMsg', data.error, 'error'); return; }
    showModalMsg('userModalMsg', 'Saved successfully.', 'success');
    setTimeout(() => { closeUserModal(); loadUsers(); }, 800);
  });
}

// ── Chats ────────────────────────────────────────────────────
let allChats = [];

function loadChats() {
  fetch('api/admin.php?action=chats')
    .then(r => r.json())
    .then(chats => {
      allChats = chats;
      renderChats(chats);
    });
}

function renderChats(chats) {
  const tbody = document.getElementById('chatsBody');
  if (!chats.length) { tbody.innerHTML = '<tr><td colspan="8" class="adm-loading">No chats found.</td></tr>'; return; }
  tbody.innerHTML = chats.map(c => `
    <tr>
      <td>${c.id}</td>
      <td><strong>${esc(c.name)}</strong></td>
      <td><span class="adm-badge adm-badge-${c.type}">${c.type}</span></td>
      <td>${esc(c.creator || '—')}</td>
      <td>${c.message_count}</td>
      <td>${c.participant_count}</td>
      <td>${c.created_at ? c.created_at.split(' ')[0] : '—'}</td>
      <td><div class="adm-actions">
        <button class="adm-btn adm-btn-sm adm-btn-danger" onclick="confirmDelete('chat',${c.id},'${esc(c.name)}')">Delete</button>
      </div></td>
    </tr>`).join('');
}

document.getElementById('chatSearch').addEventListener('input', function() {
  const q = this.value.toLowerCase();
  renderChats(allChats.filter(c => c.name.toLowerCase().includes(q)));
});

// ── Messages ─────────────────────────────────────────────────
let allConvs = [];

function loadMessages() {
  fetch('api/admin.php?action=messages')
    .then(r => r.json())
    .then(convs => {
      allConvs = convs;
      renderConversations(convs);
    });
}

function renderConversations(convs) {
  const tbody = document.getElementById('msgsBody');
  if (!convs.length) { tbody.innerHTML = '<tr><td colspan="7" class="adm-loading">No conversations found.</td></tr>'; return; }

  tbody.innerHTML = convs.map(c => {
    const isGroup = c.chat_type === 'group';
    const label = isGroup
      ? `<span class="adm-conv-group">📢 ${esc(c.chat_name)}</span>`
      : `<span class="adm-conv-private">${c.participants.map(p => `<span class="adm-conv-person">${esc(p)}</span>`).join('<span class="adm-conv-sep">↔</span>')}</span>`;

    const typeBadge = `<span class="adm-badge adm-badge-${c.chat_type}">${c.chat_type}</span>`;
    const parts = c.participants.map(p => `<span class="adm-pill">${esc(p)}</span>`).join(' ');
    const time = c.last_time ? c.last_time.substring(0, 16) : '—';
    const preview = c.last_message ? esc(c.last_message).substring(0, 50) + (c.last_message.length > 50 ? '…' : '') : '—';

    return `
      <tr class="adm-conv-row" data-chat-id="${c.chat_id}" onclick="toggleConversation(${c.chat_id}, this)">
        <td class="adm-conv-toggle"><span class="adm-chevron">▶</span></td>
        <td><div class="adm-conv-label">${label}</div><div class="adm-conv-preview">${preview}</div></td>
        <td>${typeBadge}</td>
        <td>${parts}</td>
        <td><span class="adm-badge adm-badge-count">${c.msg_count}</span></td>
        <td style="white-space:nowrap;color:#8696a0;font-size:13px">${time}</td>
        <td><button class="adm-btn adm-btn-sm adm-btn-danger" onclick="event.stopPropagation();confirmDelete('chat',${c.chat_id},'${esc(c.chat_name)}')">Delete</button></td>
      </tr>
      <tr class="adm-conv-messages hidden" id="conv-msgs-${c.chat_id}">
        <td colspan="7" class="adm-conv-messages-cell">
          <div class="adm-conv-messages-inner" id="conv-msgs-inner-${c.chat_id}">
            <div class="adm-loading">Loading messages...</div>
          </div>
        </td>
      </tr>`;
  }).join('');
}

function toggleConversation(chatId, row) {
  const msgRow = document.getElementById(`conv-msgs-${chatId}`);
  const chevron = row.querySelector('.adm-chevron');
  const isOpen = !msgRow.classList.contains('hidden');

  if (isOpen) {
    msgRow.classList.add('hidden');
    chevron.textContent = '▶';
    chevron.classList.remove('open');
    return;
  }

  msgRow.classList.remove('hidden');
  chevron.textContent = '▼';
  chevron.classList.add('open');

  fetch(`api/admin.php?action=conversation&chat_id=${chatId}`)
    .then(r => r.json())
    .then(msgs => {
      const inner = document.getElementById(`conv-msgs-inner-${chatId}`);
      if (!msgs.length) { inner.innerHTML = '<div class="adm-loading">No messages.</div>'; return; }
      inner.innerHTML = msgs.map(m => `
        <div class="adm-msg-item">
          <div class="adm-msg-item-header">
            <div class="adm-user-cell">
              <div class="adm-user-avatar" style="width:26px;height:26px;font-size:11px">${esc((m.sender||'?').charAt(0).toUpperCase())}</div>
              <strong>${esc(m.sender)}</strong>
            </div>
            ${m.chat_type === 'private' && m.recipient ? `<span class="adm-msg-arrow">→</span><div class="adm-user-cell"><div class="adm-user-avatar adm-avatar-recipient" style="width:26px;height:26px;font-size:11px">${esc(m.recipient.charAt(0).toUpperCase())}</div><strong>${esc(m.recipient)}</strong></div>` : ''}
            <span class="adm-msg-time">${m.timestamp ? m.timestamp.substring(0,16) : ''}</span>
            <button class="adm-btn adm-btn-sm adm-btn-danger" style="margin-left:auto" onclick="confirmDelete('message',${m.id},'this message')">Delete</button>
          </div>
          <div class="adm-msg-item-body">${esc(m.content)}${m.attachment ? ` <a href="uploads/${esc(m.attachment)}" target="_blank">📎</a>` : ''}</div>
        </div>`).join('');
    });
}

document.getElementById('msgSearch').addEventListener('input', function() {
  const q = this.value.toLowerCase();
  renderConversations(allConvs.filter(c =>
    c.chat_name.toLowerCase().includes(q) ||
    c.participants.some(p => p.toLowerCase().includes(q))
  ));
});

// ── Confirm / Delete ─────────────────────────────────────────
let pendingDelete = null;

function confirmDelete(type, id, name) {
  pendingDelete = { type, id };
  document.getElementById('confirmText').textContent = `Delete ${type} "${name}"? This cannot be undone.`;
  document.getElementById('confirmModal').classList.remove('hidden');
}

function closeConfirm() {
  pendingDelete = null;
  document.getElementById('confirmModal').classList.add('hidden');
}

document.getElementById('confirmOkBtn').addEventListener('click', () => {
  if (!pendingDelete) return;
  const { type, id } = pendingDelete;
  fetch(`api/admin.php?action=delete_${type}&id=${id}`, { method: 'DELETE' })
    .then(r => r.json())
    .then(() => {
      closeConfirm();
      if (type === 'user')    loadUsers();
      if (type === 'chat')    loadChats();
      if (type === 'message') loadMessages();
    });
});
