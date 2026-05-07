<?php
/* ── student/_sidebar.php ────────────────────────────────────
   Shared student sidebar + HTML head opener.
   Include as the very first line in every student view.
   Required: $activeNav – one of dashboard|lessons|assessments|progress|feedback|discussions
   Required: $profile   – array from Student::getProfile()
   ─────────────────────────────────────────────────────────── */

$activeNav = $activeNav ?? 'dashboard';

require_once BASE_PATH . '/app/models/Student.php';
$_sm        = new Student();
$_userId    = (int)($_SESSION['user']['id'] ?? 0);
$_studentId = (int)($profile['student_id'] ?? 0);

// Unread notification badge
$_unreadCount = 0;
if ($_userId) {
    $_unreadCount = $_sm->getUnreadNotificationCount($_userId);
}

$_name  = $profile['fullname'] ?? 'Student';
$_init  = strtoupper(substr($_name, 0, 2));
$_grade = $profile['class_grade'] ?? '';
$_regNo = $profile['student_reg_no'] ?? '';

$_pageLabels = [
    'dashboard'   => ['Dashboard',          'Your learning overview'],
    'lessons'     => ['My Lessons',          'Lessons you are enrolled in'],
    'registration'=> ['Register Lessons',    'Enroll in new lessons for your grade'],
    'assessments' => ['Assessments',         'Quizzes and assignments'],
    'progress'    => ['Learning Progress',   'Track your competency mastery'],
    'feedback'    => ['Teacher Feedback',    'Messages from your teachers'],
    'discussions' => ['Discussions',         'Ask questions and engage'],
    'profile'     => ['My Profile',          'Update your personal details'],
    'school_announcements' => ['School Announcements', 'Important updates from administration'],
];
$_pi = $_pageLabels[$activeNav] ?? ['Student Portal',''];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($_pi[0]) ?> – CBE LMS</title>
    <meta name="description" content="CBE LMS Student Portal – <?= htmlspecialchars($_pi[0]) ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/CBE_LMS/public/css/admin.css">
    <!-- Student teal theme override -->
    <style>
        :root {
            --primary:       #0d9488;
            --primary-dark:  #0f766e;
            --primary-light: #ccfbf1;
            --accent:        #6366f1;
            --sidebar-bg:    #0f2027;
            --sidebar-active:#0d9488;
        }
        .nav-item.active { background:rgba(13,148,136,.18); color:#5eead4; border-right-color:#0d9488; }
        .nav-item.active .nav-icon { color:#0d9488; }
        .btn-primary { background:#0d9488; }
        .btn-primary:hover { background:#0f766e; }
        .stat-card.blue .stat-icon { background:#ccfbf1; }
        .analytics-card { background:linear-gradient(135deg,#0d9488,#0891b2); }
        .analytics-card.green  { background:linear-gradient(135deg,#10b981,#059669); }
        .analytics-card.orange { background:linear-gradient(135deg,#f59e0b,#d97706); }
        .analytics-card.red    { background:linear-gradient(135deg,#ef4444,#dc2626); }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>

<!-- ═══════════════════════ SIDEBAR ═══════════════════════ -->
<aside class="sidebar" id="sidebar">

    <div class="sidebar-brand">
        <div class="brand-icon">🎒</div>
        <div class="brand-text">
            <h2>CBE LMS</h2>
            <span>Student Portal</span>
        </div>
    </div>

    <!-- Student badge -->
    <div class="sidebar-admin">
        <div class="admin-avatar" style="background:linear-gradient(135deg,#0d9488,#6366f1)">
            <?= $_init ?>
        </div>
        <div class="admin-info">
            <div class="name" style="font-size:.78rem;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                <?= htmlspecialchars($_name) ?>
            </div>
            <div class="role-badge" style="background:#0d9488">
                <?= htmlspecialchars($_grade) ?>
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Student Menu</div>

        <a href="/CBE_LMS/public/index.php?url=student/dashboard"
           class="nav-item <?= $activeNav==='dashboard'   ? 'active':'' ?>">
            <span class="nav-icon">🏠</span> Dashboard
        </a>

        <a href="/CBE_LMS/public/index.php?url=student/announcements"
           class="nav-item <?= $activeNav==='school_announcements' ? 'active':'' ?>">
            <span class="nav-icon">📢</span> School Announcements
        </a>

        <a href="/CBE_LMS/public/index.php?url=student/lessons&registered=1"
           class="nav-item <?= $activeNav==='lessons'     ? 'active':'' ?>">
            <span class="nav-icon">📚</span> My Lessons
        </a>

        <a href="/CBE_LMS/public/index.php?url=student/lessons"
           class="nav-item <?= $activeNav==='registration' ? 'active':'' ?>">
            <span class="nav-icon">🖋️</span> Register Lessons
        </a>

        <a href="/CBE_LMS/public/index.php?url=student/assessments"
           class="nav-item <?= $activeNav==='assessments' ? 'active':'' ?>">
            <span class="nav-icon">📝</span> Assessments
        </a>

        <div class="nav-section-label">Growth</div>

        <a href="/CBE_LMS/public/index.php?url=student/progress"
           class="nav-item <?= $activeNav==='progress'    ? 'active':'' ?>">
            <span class="nav-icon">📊</span> Progress
        </a>

        <a href="/CBE_LMS/public/index.php?url=pathway/index"
           class="nav-item <?= $activeNav==='pathway'     ? 'active':'' ?>">
            <span class="nav-icon">🧭</span> Pathways
        </a>

        <a href="/CBE_LMS/public/index.php?url=student/feedback"
           class="nav-item <?= $activeNav==='feedback'    ? 'active':'' ?>">
            <span class="nav-icon">💬</span> Feedback
        </a>

        <a href="/CBE_LMS/public/index.php?url=student/discussions"
           class="nav-item <?= $activeNav==='discussions' ? 'active':'' ?>">
            <span class="nav-icon">🗣</span> Discussions
        </a>

        <a href="/CBE_LMS/public/index.php?url=student/editProfile"
           class="nav-item <?= $activeNav==='profile'     ? 'active':'' ?>">
            <span class="nav-icon">👤</span> My Profile
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
            <?php if ($_unreadCount > 0): ?>
            <a href="/CBE_LMS/public/index.php?url=student/markRead"
               style="background:#fef3c7;border:1px solid #fde68a;color:#92400e;padding:6px 14px;border-radius:8px;font-size:.8rem;font-weight:600;display:flex;align-items:center;gap:6px;text-decoration:none">
                🔔 <?= $_unreadCount ?> notification<?= $_unreadCount > 1 ? 's':'' ?>
            </a>
            <?php endif; ?>
            <?php if ($_regNo): ?>
            <div class="topbar-time">📋 <?= htmlspecialchars($_regNo) ?></div>
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
