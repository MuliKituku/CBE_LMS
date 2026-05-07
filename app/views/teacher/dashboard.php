<?php
/* ── teacher/dashboard.php ─────────────────────────────────
   Variables: $profile, $stats, $classes, $pending, $notifications
   ─────────────────────────────────────────────────────────── */
$activeNav = 'dashboard';
require BASE_PATH . '/app/views/teacher/_sidebar.php';
?>

<!-- PROFILE SUMMARY CARD -->
<div class="card" style="margin-bottom:24px;background:linear-gradient(135deg, #10b981 0%, #059669 100%);color:white;border:none">
    <div class="card-body" style="display:flex;align-items:center;gap:20px;padding:30px">
        <div style="font-size:4rem;background:rgba(255,255,255,0.2);width:100px;height:100px;border-radius:50%;display:flex;align-items:center;justify-content:center">
            👨‍🏫
        </div>
        <div>
            <h2 style="color:white;margin:0 0 8px 0;font-size:1.8rem">Welcome back, <?= htmlspecialchars($profile['fullname']) ?>!</h2>
            <div style="display:flex;gap:15px;opacity:0.9;font-size:0.95rem">
                <span>📧 <?= htmlspecialchars($profile['email']) ?></span>
                <?php if (!empty($profile['phone'])): ?>
                <span>📱 <?= htmlspecialchars($profile['phone']) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- STATS GRID -->
<div class="stats-grid" style="margin-bottom:24px">
    <div class="stat-card">
        <div class="stat-icon" style="background:#d1fae5;color:#059669">📚</div>
        <div class="stat-details">
            <div class="stat-value"><?= $stats['assigned_classes'] ?></div>
            <div class="stat-label">Assigned Grades</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background:#e0e7ff;color:#4f46e5">👥</div>
        <div class="stat-details">
            <div class="stat-value"><?= $stats['total_students'] ?></div>
            <div class="stat-label">Total Students</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:#fef3c7;color:#d97706">✏️</div>
        <div class="stat-details">
            <div class="stat-value"><?= $stats['pending_grading'] ?></div>
            <div class="stat-label">Pending Grading</div>
        </div>
    </div>
</div>

<!-- SCHOOL ANNOUNCEMENTS (ADMIN) -->
<?php if (!empty($schoolAnnouncements)): ?>
<div class="card" style="margin-bottom:24px; border-top: 4px solid #059669">
    <div class="card-header" style="background:#f0fdf4">
        <h3 style="color:#166534">🏫 School Announcements</h3>
    </div>
    <div class="card-body" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap:16px; padding:20px">
        <?php foreach ($schoolAnnouncements as $ann): ?>
        <div style="padding:16px; background:#f8fafc; border-radius:12px; border-left:4px solid #10b981; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div style="font-weight:700; margin-bottom:6px; color:#1e293b;"><?= htmlspecialchars($ann['title']) ?></div>
            <p style="font-size:0.85rem; color:#475569; margin:0 0 12px; line-height:1.4;"><?= nl2br(htmlspecialchars($ann['message'])) ?></p>
            <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.7rem; color:#64748b; border-top:1px solid #f1f5f9; padding-top:8px">
                <span>By Admin: <?= htmlspecialchars($ann['author']) ?></span>
                <span><?= date('d M Y', strtotime($ann['created_at'])) ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- NEW: CHARTS & ALERTS SECTION -->
<div style="display:grid;grid-template-columns: 2fr 1fr; gap:24px; margin-bottom:24px">
    
    <!-- Progress Chart -->
    <div class="card">
        <div class="card-header">
            <h3>📈 Learning Progress (by Grade)</h3>
        </div>
        <div class="card-body">
            <canvas id="progressChart" height="280"></canvas>
        </div>
    </div>

    <!-- Performance Alerts -->
    <div class="card">
        <div class="card-header" style="background:#fff1f2">
            <h3 style="color:#e11d48">⚠️ Performance Alerts</h3>
        </div>
        <div class="card-body">
            <?php 
                $atRisk = array_filter($recentActivity, fn($a) => $a['type'] === 'submission' && isset($a['percentage']) && $a['percentage'] < 50);
                if (empty($atRisk)): 
            ?>
                <div style="text-align:center;padding:20px;color:var(--muted)">
                    <div style="font-size:1.5rem">✅</div>
                    No critical performance alerts.
                </div>
            <?php else: ?>
                <?php foreach(array_slice($atRisk, 0, 5) as $alert): ?>
                    <div style="padding:10px;border-bottom:1px solid #fee2e2;font-size:0.85rem">
                        <strong><?= htmlspecialchars($alert['label']) ?></strong> scored low on an assessment.
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            <div style="margin-top:15px">
                <a href="/CBE_LMS/public/index.php?url=teacher/students" class="btn btn-outline btn-sm w-full">View At-Risk Students</a>
            </div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px">

    <!-- RECENT ACTIVITY FEED -->
    <div class="card">
        <div class="card-header">
            <h3>🕒 Recent Activity</h3>
        </div>
        <div class="card-body" style="padding:0">
            <?php if (empty($recentActivity)): ?>
                <div style="padding:40px;text-align:center;color:var(--muted)">No recent activity.</div>
            <?php else: ?>
                <?php foreach ($recentActivity as $act): ?>
                <div style="padding:12px 20px;border-bottom:1px solid #f8fafc;display:flex;align-items:center;gap:12px">
                    <div style="font-size:1.2rem">
                        <?= $act['type'] === 'lesson' ? '📚' : ($act['type'] === 'submission' ? '📤' : '💬') ?>
                    </div>
                    <div>
                        <div style="font-size:0.85rem;font-weight:600">
                            <?= $act['type'] === 'lesson' ? 'New lesson: ' : ($act['type'] === 'submission' ? 'New submission from ' : 'Feedback for ') ?>
                            <?= htmlspecialchars($act['label']) ?>
                        </div>
                        <div style="font-size:0.75rem;color:var(--muted)">
                            <?= date('M d, H:i', strtotime($act['created_at'])) ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- PENDING GRADING QUICK LOOK -->
    <div class="card">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <h3 style="margin:0">Action Required</h3>
            <a href="/CBE_LMS/public/index.php?url=teacher/grading" class="btn btn-outline btn-sm">View All</a>
        </div>
        <div class="card-body" style="padding:0">
            <?php if (empty($pending)): ?>
                <div style="padding:30px;text-align:center;color:var(--muted)">
                    <div style="font-size:2rem;margin-bottom:10px">🎉</div>
                    All caught up! No manual grading pending.
                </div>
            <?php else: 
                $showPending = array_slice($pending, 0, 5);
            ?>
                <?php foreach ($showPending as $p): ?>
                <div style="padding:16px 20px;border-bottom:1px solid #f8fafc;display:flex;justify-content:space-between">
                    <div>
                        <div style="font-weight:600;color:var(--text);font-size:0.85rem"><?= htmlspecialchars($p['student_name']) ?></div>
                        <div style="font-size:0.75rem;color:var(--muted);margin-top:2px">
                            <?= htmlspecialchars($p['assessment_title']) ?>
                        </div>
                    </div>
                    <a href="/CBE_LMS/public/index.php?url=teacher/grading" class="btn btn-primary btn-sm" style="font-size:0.7rem;padding:4px 8px">Grade</a>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- CHART.JS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('progressChart').getContext('2d');
    const data = <?= json_encode($overallProgress) ?>;
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(i => 'Grade ' + i.class_grade),
            datasets: [
                {
                    label: 'Avg Score (%)',
                    data: data.map(i => i.avg_score),
                    backgroundColor: 'rgba(16, 185, 129, 0.6)',
                    borderColor: '#10b981',
                    borderWidth: 1
                },
                {
                    label: 'Mastery Level (%)',
                    data: data.map(i => i.mastery_pct),
                    backgroundColor: 'rgba(59, 130, 246, 0.6)',
                    borderColor: '#3b82f6',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, max: 100 }
            },
            plugins: {
                legend: { position: 'top' }
            }
        }
    });
});
</script>

</div><!-- .content -->
</div><!-- .main-wrapper -->
</body>
</html>
