<?php
/* ── _sidebar.php ──────────────────────────────────────────
   Shared admin sidebar + HTML head opener.
   Include as the very first thing in every admin view.

   Required variable (passed from controller):
     $activeNav  – one of: dashboard | enrollments | users | reports
   ──────────────────────────────────────────────────────── */

$activeNav = $activeNav ?? 'dashboard';

// Ensure User model is available for the sidebar badge
require_once BASE_PATH . '/app/models/User.php';

// Pending count badge
$userModel    = new User();
$pendingCount = $userModel->getDashboardStats()['pending_count'] ?? 0;

// Current admin name
$adminName = $_SESSION['user']['fullname'] ?? 'Administrator';
$adminInit = strtoupper(substr($adminName, 0, 1));

// Active page label for topbar
$pageLabels = [
    'dashboard'   => ['Dashboard',        'Welcome back, overview of your system'],
    'enrollments' => ['Pending Enrollments', 'Review and process student enrollment requests'],
    'users'       => ['Manage Users',     'Create, deactivate, and manage system users'],
    'reports'     => ['Reports & Analytics', 'Generate and view system reports'],
    'classes'     => ['Manage Classes',   'Assign teachers to classes and manage grades'],
    'content'     => ['Learning Content', 'Review and moderate lesson materials'],
    'grades'      => ['System-wide Grades', 'Audit and override student performance records'],
    'audit'       => ['Audit Logs',       'Track administrative actions and system security'],
    'pathways'    => ['Manage Pathways',   'Configure pathway surveys and recommendation logic'],
];
$pageInfo = $pageLabels[$activeNav] ?? ['Admin Panel', ''];

// Current time
$now = date('D, d M Y – H:i');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageInfo[0]) ?> – CBE LMS Admin</title>
    <meta name="description" content="CBE LMS Admin Panel – <?= htmlspecialchars($pageInfo[0]) ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/CBE_LMS/public/css/admin.css">
    <!-- Chart.js for analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>

<!-- ═══════════════════════ SIDEBAR ═══════════════════════ -->
<aside class="sidebar" id="sidebar" style="overflow-y: auto;">

    <!-- Brand -->
    <div class="sidebar-brand">
        <div class="brand-icon">🎓</div>
        <div class="brand-text">
            <h2>CBE LMS</h2>
            <span>Admin Portal</span>
        </div>
    </div>

    <!-- Admin info -->
    <div class="sidebar-admin">
        <div class="admin-avatar"><?= $adminInit ?></div>
        <div class="admin-info">
            <div class="name"><?= htmlspecialchars($adminName) ?></div>
            <div class="role-badge">Administrator</div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
        <div class="nav-section-label">Main Menu</div>

        <a href="/CBE_LMS/public/index.php?url=admin/dashboard"
           class="nav-item <?= $activeNav === 'dashboard' ? 'active' : '' ?>">
            <span class="nav-icon">📊</span>
            Dashboard
        </a>

        <a href="/CBE_LMS/public/index.php?url=admin/pendingEnrollments"
           class="nav-item <?= $activeNav === 'enrollments' ? 'active' : '' ?>">
            <span class="nav-icon">📋</span>
            Enrollments
            <?php if ($pendingCount > 0): ?>
                <span class="nav-badge"><?= $pendingCount ?></span>
            <?php endif; ?>
        </a>

        <div class="nav-section-label">Administration</div>

        <a href="/CBE_LMS/public/index.php?url=admin/manageUsers"
           class="nav-item <?= $activeNav === 'users' ? 'active' : '' ?>">
            <span class="nav-icon">👥</span>
            Manage Users
        </a>

        <a href="/CBE_LMS/public/index.php?url=admin/manageClasses"
           class="nav-item <?= $activeNav === 'classes' ? 'active' : '' ?>">
            <span class="nav-icon">🏫</span>
            Manage Classes
        </a>

        <a href="/CBE_LMS/public/index.php?url=admin/manageContent"
           class="nav-item <?= $activeNav === 'content' ? 'active' : '' ?>">
            <span class="nav-icon">📚</span>
            Learning Content
        </a>

        <a href="/CBE_LMS/public/index.php?url=admin/managePathways"
           class="nav-item <?= $activeNav === 'pathways' ? 'active' : '' ?>">
            <span class="nav-icon">🧭</span>
            Manage Pathways
        </a>


        <a href="/CBE_LMS/public/index.php?url=admin/reports"
           class="nav-item <?= $activeNav === 'reports' ? 'active' : '' ?>">
            <span class="nav-icon">📈</span>
            Reports
        </a>

        <div class="nav-section-label">Security & Auditing</div>

        <a href="/CBE_LMS/public/index.php?url=admin/viewGrades"
           class="nav-item <?= $activeNav === 'grades' ? 'active' : '' ?>">
            <span class="nav-icon">⭐</span>
            All Grades
        </a>

        <a href="/CBE_LMS/public/index.php?url=admin/auditLogs"
           class="nav-item <?= $activeNav === 'audit' ? 'active' : '' ?>">
            <span class="nav-icon">🕵️</span>
            Audit Logs
        </a>

        <div class="nav-section-label">System & Communication</div>

        <a href="/CBE_LMS/public/index.php?url=admin/announcements"
           class="nav-item <?= $activeNav === 'announcements' ? 'active' : '' ?>">
            <span class="nav-icon">📣</span>
            Announcements
        </a>

        <a href="/CBE_LMS/public/index.php?url=admin/settings"
           class="nav-item <?= $activeNav === 'settings' ? 'active' : '' ?>">
            <span class="nav-icon">⚙️</span>
            System Settings
        </a>
    </nav>

    <!-- Footer / Logout -->
    <div class="sidebar-footer">
        <a href="/CBE_LMS/public/index.php?url=auth/logout" class="logout-btn">
            <span>🚪</span> Logout
        </a>
    </div>
</aside>

<!-- ═══════════════════════ MAIN WRAPPER ═══════════════════════ -->
<div class="main-wrapper">

    <!-- Top Bar -->
    <header class="topbar">
        <div class="topbar-left">
            <button class="hamburger-btn" id="hamburgerBtn" aria-label="Toggle sidebar">☰</button>
            <div>
                <h1><?= htmlspecialchars($pageInfo[0]) ?></h1>
                <p><?= htmlspecialchars($pageInfo[1]) ?></p>
            </div>
        </div>
        <div class="topbar-right">
            <div class="topbar-time" id="liveClock"><?= $now ?></div>
        </div>
    </header>

    <!-- Content begins here – closed by each view's footer -->
    <div class="content">

<script>
/* Sidebar mobile toggle */
const hamburgerBtn = document.getElementById('hamburgerBtn');
const sidebar      = document.getElementById('sidebar');
if (hamburgerBtn) {
    hamburgerBtn.addEventListener('click', () => sidebar.classList.toggle('open'));
    document.addEventListener('click', e => {
        if (!sidebar.contains(e.target) && !hamburgerBtn.contains(e.target)) {
            sidebar.classList.remove('open');
        }
    });
}

/* Live clock */
function updateClock() {
    const el = document.getElementById('liveClock');
    if (!el) return;
    const now = new Date();
    el.textContent = now.toLocaleDateString('en-KE', {weekday:'short',day:'2-digit',month:'short',year:'numeric'})
        + ' – ' + now.toLocaleTimeString('en-KE',{hour:'2-digit',minute:'2-digit'});
}
setInterval(updateClock, 60000);
</script>
