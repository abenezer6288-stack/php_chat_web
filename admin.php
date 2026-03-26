<?php
require_once 'config.php';
if (!isLoggedIn() || !hasRole('admin')) { header('Location: index.php'); exit; }

$total_users    = $conn->query("SELECT COUNT(*) c FROM users")->fetch_assoc()['c'];
$total_chats    = $conn->query("SELECT COUNT(*) c FROM chats")->fetch_assoc()['c'];
$total_messages = $conn->query("SELECT COUNT(*) c FROM messages")->fetch_assoc()['c'];
$online_users   = $conn->query("SELECT COUNT(*) c FROM users WHERE status='online'")->fetch_assoc()['c'];
$total_groups   = $conn->query("SELECT COUNT(*) c FROM chats WHERE type='group'")->fetch_assoc()['c'];
$new_users_week = $conn->query("SELECT COUNT(*) c FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['c'];

$roles = $conn->query("SELECT role, COUNT(*) c FROM users GROUP BY role");
$role_data = ['student'=>0,'teacher'=>0,'admin'=>0];
while ($r = $roles->fetch_assoc()) $role_data[$r['role']] = (int)$r['c'];

$msg_trend = $conn->query("SELECT DATE(timestamp) d, COUNT(*) c FROM messages WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(timestamp) ORDER BY d ASC");
$trend_labels = []; $trend_values = [];
while ($r = $msg_trend->fetch_assoc()) { $trend_labels[] = $r['d']; $trend_values[] = (int)$r['c']; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="css/admin.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body class="admin-body">
<div class="adm-layout">

  <!-- Sidebar -->
  <aside class="adm-nav">
    <div class="adm-nav-logo">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
      <span>Library Admin</span>
    </div>
    <nav>
      <a href="#" class="adm-nav-item active" data-tab="dashboard">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        Dashboard
      </a>
      <a href="#" class="adm-nav-item" data-tab="users">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Users
      </a>
      <a href="#" class="adm-nav-item" data-tab="chats">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        Chats
      </a>
      <a href="#" class="adm-nav-item" data-tab="messages">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
        Messages
      </a>
    </nav>
    <div class="adm-nav-footer">
      <a href="chat.php" class="adm-nav-item">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        Back to Chat
      </a>
      <a href="api/auth.php?action=logout" class="adm-nav-item adm-logout">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Logout
      </a>
    </div>
  </aside>

  <!-- Main -->
  <main class="adm-main">
    <div class="adm-topbar">
      <div id="adm-page-title" class="adm-page-title">Dashboard</div>
      <div class="adm-topbar-user">
        <div class="adm-topbar-avatar"><?php echo strtoupper(substr($_SESSION['username'],0,1)); ?></div>
        <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
      </div>
    </div>

    <!-- DASHBOARD TAB -->
    <div id="tab-dashboard" class="adm-tab active">
      <div class="adm-stats-grid">
        <div class="adm-stat-card"><div class="adm-stat-icon purple"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div><div><div class="adm-stat-num"><?php echo $total_users; ?></div><div class="adm-stat-label">Total Users</div></div></div>
        <div class="adm-stat-card"><div class="adm-stat-icon blue"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></div><div><div class="adm-stat-num"><?php echo $total_chats; ?></div><div class="adm-stat-label">Total Chats</div></div></div>
        <div class="adm-stat-card"><div class="adm-stat-icon green"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg></div><div><div class="adm-stat-num"><?php echo $total_messages; ?></div><div class="adm-stat-label">Total Messages</div></div></div>
        <div class="adm-stat-card"><div class="adm-stat-icon orange"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div><div><div class="adm-stat-num"><?php echo $online_users; ?></div><div class="adm-stat-label">Online Now</div></div></div>
        <div class="adm-stat-card"><div class="adm-stat-icon teal"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg></div><div><div class="adm-stat-num"><?php echo $new_users_week; ?></div><div class="adm-stat-label">New This Week</div></div></div>
        <div class="adm-stat-card"><div class="adm-stat-icon pink"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><line x1="9" y1="10" x2="15" y2="10"/></svg></div><div><div class="adm-stat-num"><?php echo $total_groups; ?></div><div class="adm-stat-label">Group Chats</div></div></div>
      </div>
      <div class="adm-charts-row">
        <div class="adm-card adm-chart-card"><div class="adm-card-title">Messages — Last 7 Days</div><canvas id="msgTrendChart"></canvas></div>
        <div class="adm-card adm-chart-card"><div class="adm-card-title">Users by Role</div><canvas id="roleChart"></canvas></div>
      </div>
    </div>

    <!-- USERS TAB -->
    <div id="tab-users" class="adm-tab">
      <div class="adm-card">
        <div class="adm-card-header">
          <div class="adm-card-title">User Management</div>
          <div class="adm-card-actions">
            <input type="text" id="userSearch" class="adm-search" placeholder="Search users...">
            <button class="adm-btn adm-btn-primary" onclick="openAddUser()">+ Add User</button>
          </div>
        </div>
        <div class="adm-table-wrap">
          <table class="adm-table">
            <thead><tr><th>#</th><th>User</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
            <tbody id="usersBody"><tr><td colspan="7" class="adm-loading">Loading...</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- CHATS TAB -->
    <div id="tab-chats" class="adm-tab">
      <div class="adm-card">
        <div class="adm-card-header">
          <div class="adm-card-title">Chat Management</div>
          <input type="text" id="chatSearch" class="adm-search" placeholder="Search chats...">
        </div>
        <div class="adm-table-wrap">
          <table class="adm-table">
            <thead><tr><th>#</th><th>Name</th><th>Type</th><th>Created By</th><th>Messages</th><th>Participants</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody id="chatsBody"><tr><td colspan="8" class="adm-loading">Loading...</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- MESSAGES TAB -->
    <div id="tab-messages" class="adm-tab">
      <div class="adm-card">
        <div class="adm-card-header">
          <div class="adm-card-title">Message Logs</div>
          <input type="text" id="msgSearch" class="adm-search" placeholder="Search messages...">
        </div>
        <div class="adm-table-wrap">
          <table class="adm-table">
            <thead><tr><th></th><th>Conversation</th><th>Type</th><th>Participants</th><th>Messages</th><th>Last Activity</th><th>Actions</th></tr></thead>
            <tbody id="msgsBody"><tr><td colspan="7" class="adm-loading">Loading...</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>

  </main>
</div>

<!-- User Modal -->
<div id="userModal" class="adm-modal-overlay hidden">
  <div class="adm-modal">
    <div class="adm-modal-header">
      <h3 id="userModalTitle">Add User</h3>
      <button class="adm-modal-close" onclick="closeUserModal()">&times;</button>
    </div>
    <div class="adm-modal-body">
      <input type="hidden" id="editUserId">
      <div class="adm-form-group"><label>Username</label><input type="text" id="editUsername" placeholder="Username"></div>
      <div class="adm-form-group"><label>Email</label><input type="email" id="editEmail" placeholder="Email"></div>
      <div class="adm-form-group"><label>Role</label>
        <select id="editRole">
          <option value="student">Student</option>
          <option value="teacher">Teacher</option>
          <option value="admin">Admin</option>
        </select>
      </div>
      <div class="adm-form-group" id="passwordGroup"><label>Password <span id="pwdHint" style="color:#8696a0;font-weight:400">(leave blank to keep)</span></label><input type="password" id="editPassword" placeholder="Password"></div>
      <div id="userModalMsg" class="adm-modal-msg hidden"></div>
    </div>
    <div class="adm-modal-footer">
      <button class="adm-btn" onclick="closeUserModal()">Cancel</button>
      <button class="adm-btn adm-btn-primary" onclick="saveUser()">Save</button>
    </div>
  </div>
</div>

<!-- Confirm Modal -->
<div id="confirmModal" class="adm-modal-overlay hidden">
  <div class="adm-modal adm-modal-sm">
    <div class="adm-modal-header"><h3>Confirm</h3><button class="adm-modal-close" onclick="closeConfirm()">&times;</button></div>
    <div class="adm-modal-body"><p id="confirmText"></p></div>
    <div class="adm-modal-footer">
      <button class="adm-btn" onclick="closeConfirm()">Cancel</button>
      <button class="adm-btn adm-btn-danger" id="confirmOkBtn">Delete</button>
    </div>
  </div>
</div>

<script>
const TREND_LABELS = <?php echo json_encode($trend_labels); ?>;
const TREND_VALUES = <?php echo json_encode($trend_values); ?>;
const ROLE_DATA    = <?php echo json_encode($role_data); ?>;
</script>
<script src="js/admin.js"></script>
</body>
</html>