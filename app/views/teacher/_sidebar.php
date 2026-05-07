<?php
/* ── teacher/_sidebar.php ────────────────────────────────────
   Shared teacher sidebar + HTML head opener.
   Include as the very first line in every teacher view.
   Required: $activeNav
   Required: $profile
   ─────────────────────────────────────────────────────────── */

$activeNav = $activeNav ?? 'dashboard';

// Unread notification badge for messages
$unreadMessages = $unreadMessages ?? 0;

$_name  = $profile['fullname'] ?? 'Teacher';
$_init  = strtoupper(substr($_name, 0, 2));
$_spec  = $profile['email'] ?? '';

$_pageLabels = [
    'dashboard'   => ['Dashboard',          'Your teaching overview'],
    'lessons'     => ['Lesson Materials',   'Upload videos, PDFs & slides'],
    'assessments' => ['Quizzes & Tests',    'Manage assessments'],
    'grading'     => ['Grading',            'Evaluate student submissions'],
    'students'    => ['Student Roster',     'Track class performance'],
    'messages'    => ['Parent Chat',        'Communicate with parents'],
    'announcements'=> ['System Announcements','Broadcast to your classes'],
    'school_announcements' => ['School Announcements', 'Updates from the school admin'],
    'reports'     => ['Performance Reports', 'Class & student analytics'],
];
$_pi = $_pageLabels[$activeNav] ?? ['Teacher Portal',''];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($_pi[0]) ?> – CBE LMS</title>
    <meta name="description" content="CBE LMS Teacher Portal – <?= htmlspecialchars($_pi[0]) ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/CBE_LMS/public/css/admin.css">
    
    <!-- Teacher emerald theme override -->
    <style>
        :root {
            --primary:       #10b981;
            --primary-dark:  #059669;
            --primary-light: #d1fae5;
            --accent:        #3b82f6;
            --sidebar-bg:    #064e3b;
            --sidebar-active:#10b981;
        }
        .nav-item.active { background:rgba(16,185,129,.18); color:#6ee7b7; border-right-color:#10b981; }
        .nav-item.active .nav-icon { color:#10b981; }
        .btn-primary { background:#10b981; }
        .btn-primary:hover { background:#059669; }
        
        .stat-card.blue .stat-icon { background:#d1fae5; color: #059669; }
        
        /* Analytics Cards overriding to fit theme */
        .analytics-card { background:linear-gradient(135deg,#10b981,#059669); }
        .analytics-card.green  { background:linear-gradient(135deg,#34d399,#10b981); }
        .analytics-card.orange { background:linear-gradient(135deg,#f59e0b,#d97706); }
        .analytics-card.red    { background:linear-gradient(135deg,#ef4444,#dc2626); }
        
        .action-badge {
            background: #ef4444; color: white; font-size: 0.7rem;
            padding: 2px 8px; border-radius: 10px; margin-left: auto; font-weight: bold;
        }
    </style>
</head>
<body>

<!-- ═══════════════════════ SIDEBAR ═══════════════════════ -->
<aside class="sidebar" id="sidebar">

    <div class="sidebar-brand">
        <div class="brand-icon">👨‍🏫</div>
        <div class="brand-text">
            <h2>CBE LMS</h2>
            <span>Teacher Portal</span>
        </div>
    </div>

    <!-- Teacher badge -->
    <div class="sidebar-admin">
        <div class="admin-avatar" style="background:linear-gradient(135deg,#10b981,#3b82f6)">
            <?= $_init ?>
        </div>
        <div class="admin-info">
            <div class="name" style="font-size:.78rem;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                <?= htmlspecialchars($_name) ?>
            </div>
            <div class="role-badge" style="background:#10b981">
                TEACHER
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Core</div>

        <a href="/CBE_LMS/public/index.php?url=teacher/dashboard"
           class="nav-item <?= $activeNav==='dashboard'   ? 'active':'' ?>">
            <span class="nav-icon">📊</span> Overview
        </a>

        <a href="/CBE_LMS/public/index.php?url=teacher/lessons"
           class="nav-item <?= $activeNav==='lessons'     ? 'active':'' ?>">
            <span class="nav-icon">📚</span> Lesson Materials
        </a>

        <a href="/CBE_LMS/public/index.php?url=teacher/assessments"
           class="nav-item <?= $activeNav==='assessments' ? 'active':'' ?>">
            <span class="nav-icon">📝</span> Quizzes & Tests
        </a>

        <div class="nav-section-label">Evaluation</div>

        <a href="/CBE_LMS/public/index.php?url=teacher/grading"
           class="nav-item <?= $activeNav==='grading'    ? 'active':'' ?>">
            <span class="nav-icon">🎓</span> Grading
        </a>

        <a href="/CBE_LMS/public/index.php?url=teacher/students"
           class="nav-item <?= $activeNav==='students'    ? 'active':'' ?>">
            <span class="nav-icon">👥</span> Student Roster
        </a>

        <a href="/CBE_LMS/public/index.php?url=teacher/messages"
           class="nav-item <?= $activeNav==='messages' ? 'active':'' ?>">
            <span class="nav-icon">💬</span> Parent Chat
            <?php if (!empty($unreadMessages)): ?>
                <span class="action-badge"><?= $unreadMessages ?></span>
            <?php endif; ?>
        </a>

        <div class="nav-section-label">Communication</div>

        <a href="/CBE_LMS/public/index.php?url=teacher/announcements"
           class="nav-item <?= $activeNav==='announcements' ? 'active':'' ?>">
            <span class="nav-icon">📢</span> Announcements
        </a>

        <a href="/CBE_LMS/public/index.php?url=teacher/schoolAnnouncements"
           class="nav-item <?= $activeNav==='school_announcements' ? 'active':'' ?>">
            <span class="nav-icon">🏫</span> School Announcements
        </a>

        <a href="/CBE_LMS/public/index.php?url=teacher/reports"
           class="nav-item <?= $activeNav==='reports' ? 'active':'' ?>">
            <span class="nav-icon">📈</span> Reports
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
            <?php if (!empty($unreadMessages)): ?>
            <a href="/CBE_LMS/public/index.php?url=teacher/messages"
               style="background:#fef3c7;border:1px solid #fde68a;color:#92400e;padding:6px 14px;border-radius:8px;font-size:.8rem;font-weight:600;display:flex;align-items:center;gap:6px;text-decoration:none">
                🔔 <?= $unreadMessages ?> new message<?= $unreadMessages > 1 ? 's':'' ?>
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
