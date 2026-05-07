<?php
/* ── student/dashboard.php ──────────────────────────────────
   Variables: $profile, $stats, $upcoming, $notices
   ─────────────────────────────────────────────────────────── */
$activeNav = 'dashboard';
require BASE_PATH . '/app/views/student/_sidebar.php';

$success = $_GET['success'] ?? '';
$msg     = $_GET['msg']     ?? '';

$dob     = $profile['date_of_birth'] ? date('d M Y', strtotime($profile['date_of_birth'])) : 'N/A';
$joined  = $profile['enrolled_at']   ? date('d M Y', strtotime($profile['enrolled_at']))   : 'N/A';
?>

<?php if ($success === 'submitted'): ?>
<div class="alert alert-success">
    ✅ Assessment submitted! Score: <strong><?= (int)$_GET['score'] ?>/<?= (int)$_GET['max'] ?></strong>
    (<?= (int)$_GET['pct'] ?>%)
</div>
<?php elseif ($msg === 'notifications_cleared'): ?>
<div class="alert alert-info">🔔 All notifications marked as read.</div>
<?php endif; ?>

<!-- STUDENT PROFILE CARD -->
<div class="card" style="margin-bottom:24px;background:linear-gradient(135deg,#0d9488 0%,#0891b2 100%);color:#fff;border:none;">
    <div class="card-body" style="display:flex;align-items:center;gap:24px;flex-wrap:wrap;">
        <div style="width:72px;height:72px;border-radius:50%;background:rgba(255,255,255,.2);
                    display:flex;align-items:center;justify-content:center;font-size:1.8rem;
                    font-weight:800;flex-shrink:0;border:3px solid rgba(255,255,255,.4)">
            <?= strtoupper(substr($profile['fullname'],0,2)) ?>
        </div>
        <div style="flex:1;min-width:0">
            <h2 style="color:#fff;font-size:1.3rem;margin-bottom:4px"><?= htmlspecialchars($profile['fullname']) ?></h2>
            <div style="display:flex;flex-wrap:wrap;gap:10px;font-size:.85rem;opacity:.9">
                <span>📋 <?= htmlspecialchars($profile['student_reg_no'] ?? 'Pending') ?></span>
                <span>🏫 <?= htmlspecialchars($profile['class_grade']) ?></span>
                <span>📧 <?= htmlspecialchars($profile['email']) ?></span>
                <span>🎂 <?= $dob ?></span>
            </div>
        </div>
        <div style="text-align:right;">
            <div style="background:rgba(255,255,255,.15);border-radius:8px;padding:8px 16px;font-size:.8rem;">
                Enrolled: <?= $joined ?>
            </div>
        </div>
    </div>
</div>

<!-- STAT CARDS -->
<div class="stats-grid">
    <div class="stat-card cyan">
        <div class="stat-icon">📈</div>
        <div class="stat-info">
            <div class="label">Overall Mastery Score</div>
            <div class="value"><?= $stats['overall_score'] ?>%</div>
            <div class="sub">Average Performance</div>
        </div>
    </div>
    <div class="stat-card blue">
        <div class="stat-icon">🏆</div>
        <div class="stat-info">
            <div class="label">Competencies Mastered</div>
            <div class="value"><?= $stats['mastered_competencies'] ?></div>
            <div class="sub"><a href="/CBE_LMS/public/index.php?url=student/progress" style="color:#0d9488">View progress →</a></div>
        </div>
    </div>
    <div class="stat-card orange">
        <div class="stat-icon">📝</div>
        <div class="stat-info">
            <div class="label">Upcoming Assessments</div>
            <div class="value"><?= $stats['upcoming_assessments'] ?></div>
            <div class="sub"><?= $stats['completed_assessments'] ?> completed</div>
        </div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon">📚</div>
        <div class="stat-info">
            <div class="label">Lessons Available</div>
            <div class="value"><?= $stats['lessons_available'] ?></div>
            <div class="sub"><a href="/CBE_LMS/public/index.php?url=student/lessons" style="color:#0d9488">Browse →</a></div>
        </div>
    </div>
</div>

<!-- DASHBOARD GRID -->
<div class="dashboard-grid">

    <!-- Recent Activity -->
    <div class="card">
        <div class="card-header">
            <h3>📊 Recent Activity</h3>
        </div>
        <?php if (empty($activity)): ?>
        <div class="empty-state" style="padding:30px 20px">
            <div class="empty-icon">⏳</div>
            <p>No recent activity recorded.</p>
        </div>
        <?php else: ?>
        <div style="padding:15px">
            <?php foreach ($activity as $act): ?>
            <div style="display:flex;gap:15px;padding:12px 0;border-bottom:1px solid #f1f5f9">
                <div style="width:40px;height:40px;border-radius:10px;background:<?= $act['type']==='assessment' ? '#ccfbf1':'#fef3c7' ?>;
                            display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0">
                    <?= $act['type']==='assessment' ? '📝':'💬' ?>
                </div>
                <div>
                    <div style="font-weight:600;font-size:.9rem"><?= htmlspecialchars($act['title']) ?></div>
                    <div style="font-size:.78rem;color:var(--muted)">
                        <?= ucfirst($act['type']) ?> • <?= $act['detail'] ?> 
                        <span style="margin-left:8px">• <?= date('d M, H:i', strtotime($act['date'])) ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- School Announcements (Admin) -->
    <?php if (!empty($schoolAnnouncements)): ?>
    <div class="card" style="border-top:4px solid #0891b2">
        <div class="card-header">
            <h3>🏫 School Announcements</h3>
        </div>
        <div style="padding:15px">
            <?php foreach ($schoolAnnouncements as $ann): ?>
            <div style="padding:15px;background:#f0f9ff;border-radius:12px;margin-bottom:12px;border-left:4px solid #0891b2">
                <div style="font-weight:700;margin-bottom:4px;color:#0c4a6e"><?= htmlspecialchars($ann['title']) ?></div>
                <p style="font-size:0.85rem;color:#075985;margin:0 0 8px"><?= nl2br(htmlspecialchars($ann['message'])) ?></p>
                <div style="display:flex;justify-content:space-between;font-size:0.75rem;color:#0284c7">
                    <span>Admin: <?= htmlspecialchars($ann['author']) ?></span>
                    <span><?= date('d M Y', strtotime($ann['created_at'])) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Class Announcements -->
    <div class="card">
        <div class="card-header">
            <h3>📢 Class Announcements</h3>
        </div>
        <?php if (empty($announcements)): ?>
        <div class="empty-state" style="padding:30px 20px">
            <div class="empty-icon">📡</div>
            <p>No class announcements.</p>
        </div>
        <?php else: ?>
        <div style="padding:15px">
            <?php foreach ($announcements as $ann): ?>
            <div style="padding:15px;background:#f8fafc;border-radius:12px;margin-bottom:12px;border-left:4px solid var(--primary)">
                <div style="font-weight:700;margin-bottom:4px;color:var(--text)"><?= htmlspecialchars($ann['title']) ?></div>
                <p style="font-size:0.85rem;color:#475569;margin:0 0 8px"><?= nl2br(htmlspecialchars($ann['message'])) ?></p>
                <div style="display:flex;justify-content:space-between;font-size:0.75rem;color:var(--muted)">
                    <span>By <?= htmlspecialchars($ann['teacher_name']) ?></span>
                    <span><?= date('d M Y', strtotime($ann['created_at'])) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Upcoming Assessments -->
    <div class="card">
        <div class="card-header">
            <h3>📝 Upcoming Assessments</h3>
            <a href="/CBE_LMS/public/index.php?url=student/assessments" class="btn btn-outline btn-sm">View all</a>
        </div>
        <?php if (empty($upcoming)): ?>
        <div class="empty-state" style="padding:30px 20px">
            <div class="empty-icon">🎉</div>
            <p>No pending assessments!</p>
        </div>
        <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Title</th><th>Subject</th><th>Due Date</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($upcoming as $a): ?>
                <tr>
                    <td style="font-weight:600"><?= htmlspecialchars($a['title']) ?></td>
                    <td><?= htmlspecialchars($a['subject']) ?></td>
                    <td style="font-size:.8rem;color:var(--muted)">
                        <?= $a['due_date'] ? date('d M', strtotime($a['due_date'])) : 'Open' ?>
                    </td>
                    <td>
                        <a href="/CBE_LMS/public/index.php?url=student/takeAssessment&id=<?= $a['id'] ?>"
                           class="btn btn-primary btn-sm">Attempt →</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Notifications -->
    <div class="card">
        <div class="card-header">
            <h3>🔔 System Notifications</h3>
            <?php if (!empty($notices)): ?>
            <a href="/CBE_LMS/public/index.php?url=student/markRead" class="btn btn-outline btn-sm">Mark all read</a>
            <?php endif; ?>
        </div>
        <?php if (empty($notices)): ?>
        <div class="empty-state" style="padding:30px 20px">
            <div class="empty-icon">📭</div>
            <p>No notifications yet.</p>
        </div>
        <?php else: ?>
        <div style="padding:8px 0">
            <?php foreach ($notices as $n): ?>
            <div style="padding:12px 20px;border-bottom:1px solid #f1f5f9;display:flex;gap:12px;align-items:flex-start">
                <div style="width:8px;height:8px;border-radius:50%;background:<?= $n['is_read'] ? '#e2e8f0' : '#0d9488' ?>;
                            flex-shrink:0;margin-top:6px"></div>
                <div style="flex:1">
                    <p style="font-size:.85rem;margin:0;<?= $n['is_read'] ? 'color:var(--muted)' : 'font-weight:600' ?>">
                        <?= htmlspecialchars($n['message']) ?>
                    </p>
                    <p style="font-size:.75rem;color:var(--muted);margin:2px 0 0">
                        <?= date('d M Y H:i', strtotime($n['created_at'])) ?>
                    </p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- QUICK ACTIONS -->
<div class="card" style="margin-top:24px">
    <div class="card-header"><h3>⚡ Quick Actions</h3></div>
    <div class="card-body" style="display:flex;flex-wrap:wrap;gap:12px">
        <a href="/CBE_LMS/public/index.php?url=student/lessons"     class="btn btn-primary btn-lg">📚 Browse Lessons</a>
        <a href="/CBE_LMS/public/index.php?url=student/assessments" class="btn btn-warning btn-lg">📝 Take a Quiz</a>
        <a href="/CBE_LMS/public/index.php?url=student/progress"    class="btn btn-success btn-lg">📊 View Progress</a>
        <a href="/CBE_LMS/public/index.php?url=student/discussions" class="btn btn-outline btn-lg">🗣 Discussions</a>
    </div>
</div>

</div></div>
</body></html>
