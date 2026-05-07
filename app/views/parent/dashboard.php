<?php
/* ── parent/dashboard.php ──────────────────────────────────
   Variables: $profile, $notices, $activities, $upcoming
   ─────────────────────────────────────────────────────────── */
$activeNav = 'dashboard';
require BASE_PATH . '/app/views/parent/_sidebar.php';

$msg = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';
?>

<?php if ($msg === 'notifications_cleared'): ?>
<div class="alert alert-info">🔔 All alerts marked as read.</div>
<?php elseif ($error === 'unauthorized_child'): ?>
<div class="alert alert-error">❌ You do not have permission to view that student.</div>
<?php endif; ?>

<!-- PARENT WELCOME BANNER -->
<div class="card" style="margin-bottom:24px;background:linear-gradient(135deg,#4f46e5 0%,#6366f1 100%);color:#fff;border:none;box-shadow:0 10px 25px rgba(79,70,229,0.2)">
    <div class="card-body" style="padding:32px;display:flex;align-items:center;gap:28px;flex-wrap:wrap;">
        <div style="width:80px;height:80px;border-radius:24px;background:rgba(255,255,255,0.15);
                    display:flex;align-items:center;justify-content:center;font-size:2.5rem;
                    backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,0.2)">
            👋
        </div>
        <div style="flex:1;min-width:0">
            <h2 style="color:#fff;font-size:1.6rem;margin-bottom:6px;font-weight:800">Hello, <?= htmlspecialchars($profile['fullname']) ?>!</h2>
            <p style="color:rgba(255,255,255,0.85);margin:0;font-size:0.95rem;max-width:600px">
                Monitor your children's learning journey, track their competency mastery, and stay updated with teacher feedback.
            </p>
        </div>
        <div style="display:flex;gap:15px">
            <div style="background:rgba(0,0,0,0.1);padding:10px 20px;border-radius:12px;text-align:center">
                <div style="font-size:1.2rem;font-weight:700"><?= count($profile['children']) ?></div>
                <div style="font-size:0.75rem;opacity:0.8;text-transform:uppercase">Children</div>
            </div>
        </div>
    </div>
</div>

<div class="dashboard-grid">

    <!-- LEFT COLUMN: Children & Upcoming -->
    <div style="display:flex;flex-direction:column;gap:24px">
        
        <!-- Linked Children -->
        <div class="card">
            <div class="card-header" style="padding:20px 24px">
                <h3 style="font-size:1.1rem">👨‍👩‍👧‍👦 Your Children</h3>
            </div>
            <div class="card-body" style="padding:0">
                <?php if (empty($profile['children'])): ?>
                <div class="empty-state" style="padding:40px">
                    <div class="empty-icon">🤷</div>
                    <p>No children linked to your account.</p>
                </div>
                <?php else: ?>
                    <?php foreach ($profile['children'] as $child): ?>
                    <div style="padding:20px 24px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
                        <div style="display:flex;align-items:center;gap:16px">
                            <div style="width:56px;height:56px;border-radius:16px;background:linear-gradient(135deg,#6366f1,#8b5cf6);
                                        color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1.2rem;box-shadow:0 4px 12px rgba(99,102,241,0.2)">
                                <?= strtoupper(substr($child['student_name'], 0, 1)) . strtoupper(substr(strrchr($child['student_name'], ' '), 1, 1)) ?>
                            </div>
                            <div>
                                <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
                                    <span style="font-weight:700;font-size:1.1rem;color:var(--text)"><?= htmlspecialchars($child['student_name']) ?></span>
                                    <span class="badge badge-<?= $child['enrollment_status'] === 'active' ? 'success' : 'pending' ?>" style="font-size:0.65rem;text-transform:uppercase">
                                        <?= htmlspecialchars($child['enrollment_status']) ?>
                                    </span>
                                </div>
                                <div style="font-size:0.85rem;color:var(--muted)">
                                    <span style="background:#f1f5f9;padding:2px 8px;border-radius:4px;font-weight:600;color:#475569"><?= htmlspecialchars($child['class_grade']) ?></span>
                                    <span style="margin:0 8px">|</span>
                                    Reg: <span style="font-family:'JetBrains Mono',monospace;color:var(--text)"><?= htmlspecialchars($child['student_reg_no']) ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div style="display:flex;gap:20px;align-items:center">
                            <div style="text-align:right">
                                <div style="color:#10b981;font-weight:700;font-size:0.95rem">🏆 <?= $child['mastered_competencies'] ?> Mastered</div>
                                <div style="color:#6366f1;font-weight:700;font-size:0.95rem">📊 <?= (int)$child['average_score'] ?>% Average</div>
                            </div>
                            <a href="/CBE_LMS/public/index.php?url=parent/progress&child_id=<?= $child['student_id'] ?>" 
                               class="btn btn-primary btn-sm" style="padding:8px 16px;border-radius:8px">Full View →</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Upcoming Assessments Section -->
        <div class="card" style="border-top:4px solid #f59e0b">
            <div class="card-header" style="padding:20px 24px;display:flex;justify-content:space-between;align-items:center">
                <h3 style="font-size:1.1rem">⏳ Upcoming & Due Assessments</h3>
                <span class="badge badge-warning" style="font-size:0.7rem"><?= count($upcoming) ?> ATTENTION REQUIRED</span>
            </div>
            <div class="card-body" style="padding:0">
                <?php if (empty($upcoming)): ?>
                <div style="padding:30px;text-align:center;color:var(--muted)">
                    <p style="font-size:0.9rem">Great news! No pending assessments or tests for your children.</p>
                </div>
                <?php else: ?>
                    <?php foreach ($upcoming as $u): ?>
                    <div style="padding:16px 24px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;gap:12px">
                        <div style="display:flex;gap:12px;align-items:center">
                            <div style="font-size:1.5rem">📝</div>
                            <div>
                                <div style="font-weight:700;font-size:0.95rem;color:var(--text)"><?= htmlspecialchars($u['title']) ?></div>
                                <div style="font-size:0.8rem;color:var(--muted)">
                                    Child: <strong><?= htmlspecialchars($u['student_name']) ?></strong> | Subject: <?= htmlspecialchars($u['subject']) ?>
                                </div>
                            </div>
                        </div>
                        <div style="text-align:right">
                            <div style="font-size:0.8rem;color:var(--muted);margin-bottom:4px">Due Date</div>
                            <div style="font-weight:700;color:<?= (strtotime($u['due_date']) < time()) ? '#ef4444' : '#f59e0b' ?>">
                                <?= date('d M, Y', strtotime($u['due_date'])) ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="card-footer" style="padding:12px 24px;background:#fffaf0;text-align:center">
                <p style="font-size:0.8rem;color:#92400e;margin:0">Encourage your children to complete their assessments before the deadline.</p>
            </div>
        </div>
    </div>

    <!-- RIGHT COLUMN: Activity & Notifications -->
    <div style="display:flex;flex-direction:column;gap:24px">
        
        <!-- School Announcements -->
        <?php if (!empty($schoolAnnouncements)): ?>
        <div class="card" style="border-top:4px solid #4f46e5; margin-bottom: 24px;">
            <div class="card-header" style="background: #f5f3ff;">
                <h3 style="color: #4338ca;">🏫 School Announcements</h3>
            </div>
            <div class="card-body" style="padding: 20px;">
                <?php foreach ($schoolAnnouncements as $ann): ?>
                <div style="padding: 16px; background: #fafafa; border-radius: 12px; margin-bottom: 12px; border-left: 4px solid #4f46e5;">
                    <div style="font-weight: 700; margin-bottom: 4px; color: #1e293b;"><?= htmlspecialchars($ann['title']) ?></div>
                    <p style="font-size: 0.88rem; color: #475569; margin: 0 0 8px;"><?= nl2br(htmlspecialchars($ann['message'])) ?></p>
                    <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: #64748b;">
                        <span>Admin: <?= htmlspecialchars($ann['author']) ?></span>
                        <span><?= date('d M Y', strtotime($ann['created_at'])) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recent Activity -->
        <div class="card">
            <div class="card-header">
                <h3>⚡ Recent Activity</h3>
            </div>
            <div class="card-body" style="padding:20px">
                <?php if (empty($activities)): ?>
                <p style="color:var(--muted);text-align:center;padding:20px">No recent activity.</p>
                <?php else: ?>
                <div style="display:flex;flex-direction:column;gap:20px">
                    <?php foreach ($activities as $act): ?>
                    <div style="display:flex;gap:12px">
                        <div style="width:32px;height:32px;border-radius:10px;background:#eef2ff;display:flex;align-items:center;justify-content:center;color:#4f46e5;font-weight:800;font-size:0.8rem">
                            <?= strtoupper(substr($act['student_name'], 0, 1)) ?>
                        </div>
                        <div style="flex:1;min-width:0">
                            <p style="font-size:0.88rem;margin:0;line-height:1.4">
                                <strong><?= htmlspecialchars($act['student_name']) ?></strong> <?= htmlspecialchars($act['description']) ?>
                            </p>
                            <p style="font-size:0.75rem;color:var(--muted);margin-top:2px">
                                <?= date('d M, H:i', strtotime($act['activity_date'])) ?>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Notifications -->
        <div class="card" style="border-top:4px solid #ef4444">
            <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
                <h3>🔔 Announcements</h3>
                <?php if (!empty($notices)): ?>
                <a href="/CBE_LMS/public/index.php?url=parent/markRead" style="font-size:0.75rem;color:#ef4444;text-decoration:none;font-weight:600">Mark all read</a>
                <?php endif; ?>
            </div>
            <div class="card-body" style="padding:0">
                <?php if (empty($notices)): ?>
                <div style="padding:20px;text-align:center;color:var(--muted)">No new announcements.</div>
                <?php else: ?>
                    <?php foreach ($notices as $n): ?>
                    <div style="padding:14px 20px;border-bottom:1px solid #f1f5f9;display:flex;gap:12px">
                        <div style="width:6px;height:6px;border-radius:50%;background:<?= $n['is_read'] ? '#e2e8f0' : '#ef4444' ?>;margin-top:6px"></div>
                        <div style="flex:1">
                            <p style="font-size:0.85rem;margin:0;<?= $n['is_read'] ? 'color:var(--muted)' : 'font-weight:600;color:#1e293b' ?>">
                                <?= htmlspecialchars($n['message']) ?>
                            </p>
                            <p style="font-size:0.72rem;color:var(--muted);margin-top:4px"><?= date('d M Y, H:i', strtotime($n['created_at'])) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

</div></div>
</body></html>
