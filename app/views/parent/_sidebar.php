<?php
/* ── parent/_sidebar.php ────────────────────────────────────
   Shared parent sidebar + HTML head opener.
   Include as the very first line in every parent view.
   ─────────────────────────────────────────────────────────── */

$activeNav = $activeNav ?? 'dashboard';

require_once BASE_PATH . '/app/models/ParentModel.php';
$_pm        = new ParentModel();
$_userId    = (int)($_SESSION['user']['id'] ?? 0);
$_parentId  = (int)($profile['parent_id'] ?? 0);

$_unreadNotif = 0;
$_unreadMsg   = 0;
if ($_userId) {
    $_unreadNotif = $_pm->getUnreadNotificationCount($_userId);
}
if ($_parentId) {
    $_unreadMsg   = $_pm->getUnreadMessageCount($_parentId);
}

$_name = $profile['fullname'] ?? 'Parent';
$_init = strtoupper(substr($_name, 0, 2));

$_pageLabels = [
    'dashboard'   => ['Parent Overview',    'Monitor your family\'s learning progress'],
    'progress'    => ['Child Progress',     'Subject mastery and completion charts'],
    'assessments' => ['Assessment Results', 'Quiz grades and feedback'],
    'messages'    => ['Teacher Messages',   'Direct communication with educators'],
    'school_announcements' => ['School Announcements', 'Official school notifications'],
];
$_pi = $_pageLabels[$activeNav] ?? ['Parent Portal',''];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($_pi[0]) ?> – CBE LMS</title>
    <meta name="description" content="CBE LMS Parent Portal">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/CBE_LMS/public/css/admin.css">
    <!-- Parent Indigo Theme -->
    <style>
        :root {
            --primary:       #6366f1; /* Indigo 500 */
            --primary-dark:  #4f46e5; /* Indigo 600 */
            --primary-light: #e0e7ff; /* Indigo 100 */
            --accent:        #0ea5e9; /* Sky 500 */
            --sidebar-bg:    #1e1b4b; /* Indigo 950 */
            --sidebar-active:#6366f1;
        }
        .nav-item.active { background:rgba(99,102,241,.18); color:#a5b4fc; border-right-color:#6366f1; }
        .nav-item.active .nav-icon { color:#a5b4fc; }
        .btn-primary { background:#6366f1; }
        .btn-primary:hover { background:#4f46e5; }
        .stat-card.blue .stat-icon { background:#e0e7ff; color:#6366f1; }
        .stat-card.green .stat-icon { background:#dcfce7; color:#10b981; }
        .analytics-card { background:linear-gradient(135deg,#6366f1,#8b5cf6); }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>

<!-- ═══════════════════════ SIDEBAR ═══════════════════════ -->
<aside class="sidebar" id="sidebar">

    <div class="sidebar-brand">
        <div class="brand-icon">👨‍👩‍👧‍👦</div>
        <div class="brand-text">
            <h2>CBE LMS</h2>
            <span>Parent Portal</span>
        </div>
    </div>

    <!-- Parent badge -->
    <div class="sidebar-admin">
        <div class="admin-avatar" style="background:linear-gradient(135deg,#6366f1,#8b5cf6)">
            <?= $_init ?>
        </div>
        <div class="admin-info">
            <div class="name" style="font-size:.78rem;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                <?= htmlspecialchars($_name) ?>
            </div>
            <div class="role-badge" style="background:#4f46e5">Parent</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Family Menu</div>

        <a href="/CBE_LMS/public/index.php?url=parent/dashboard"
           class="nav-item <?= $activeNav==='dashboard'   ? 'active':'' ?>">
            <span class="nav-icon">🏠</span> Dashboard
        </a>

        <a href="/CBE_LMS/public/index.php?url=parent/progress"
           class="nav-item <?= $activeNav==='progress'    ? 'active':'' ?>">
            <span class="nav-icon">📈</span> Child Progress
        </a>

        <a href="/CBE_LMS/public/index.php?url=parent/assessments"
           class="nav-item <?= $activeNav==='assessments' ? 'active':'' ?>">
            <span class="nav-icon">📝</span> Assessment Results
        </a>

        <div class="nav-section-label">Communication</div>

        <a href="/CBE_LMS/public/index.php?url=parent/messages"
           class="nav-item <?= $activeNav==='messages'    ? 'active':'' ?>">
            <span class="nav-icon">💬</span> Teacher Messages
            <?php if ($_unreadMsg > 0): ?>
                <span class="nav-badge" style="background:#ef4444;color:#fff"><?= $_unreadMsg ?></span>
            <?php endif; ?>
        </a>

        <a href="/CBE_LMS/public/index.php?url=parent/announcements"
           class="nav-item <?= $activeNav==='school_announcements' ? 'active':'' ?>">
            <span class="nav-icon">📢</span> School Announcements
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="/CBE_LMS/public/index.php?url=auth/logout" class="logout-btn">
            <span>🚪</span> Logout
        </a>
    </div>
</aside>

<!-- ═══════════════════════ MAIN ═══════════════════════ -->
<div class="main-wrapper">
    <header class="topbar">
        <div class="topbar-left">
            <button class="hamburger-btn" id="hamburgerBtn">☰</button>
            <div>
                <h1><?= htmlspecialchars($_pi[0]) ?></h1>
                <p><?= htmlspecialchars($_pi[1]) ?></p>
            </div>
        </div>
        <div class="topbar-right">
            <?php if ($_unreadNotif > 0): ?>
            <a href="/CBE_LMS/public/index.php?url=parent/markRead"
               style="background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;padding:6px 14px;border-radius:8px;font-size:.8rem;font-weight:600;display:flex;align-items:center;gap:6px;text-decoration:none">
                🔔 <?= $_unreadNotif ?> alert<?= $_unreadNotif > 1 ? 's':'' ?>
            </a>
            <?php endif; ?>
        </div>
    </header>

    <div class="content">

<script>
const _hamburgerBtn = document.getElementById('hamburgerBtn');
const _sidebar      = document.getElementById('sidebar');
if (_hamburgerBtn) {
    _hamburgerBtn.addEventListener('click', () => _sidebar.classList.toggle('open'));
    document.addEventListener('click', e => {
        if (!_sidebar.contains(e.target) && !_hamburgerBtn.contains(e.target))
            _sidebar.classList.remove('open');
    });
}
</script>
