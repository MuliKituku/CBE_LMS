<?php
/* ── admin/dashboard.php ─────────────────────────────────────
   Admin Dashboard – stats, enrollment chart, quick actions
   Variables: $stats (array), $trends (array)
   ─────────────────────────────────────────────────────────── */

$activeNav = 'dashboard';

// Load sidebar dependency (User model already auto-loaded by controller)
require_once BASE_PATH . '/app/models/User.php';
require BASE_PATH . '/app/views/admin/_sidebar.php';

$totalStudents  = $stats['total_students']    ?? 0;
$activeTeachers = $stats['active_teachers']   ?? 0;
$totalParents   = $stats['total_parents']     ?? 0;
$pendingCount   = $stats['pending_count']     ?? 0;
$approvedCount  = $stats['approved_students'] ?? 0;
$totalUsers     = $totalStudents + $activeTeachers + $totalParents;

// Flash messages
$msg     = $_GET['msg']   ?? '';
$error   = $_GET['error'] ?? '';
?>

<?php if ($msg === 'approved'): ?>
<div class="alert alert-success">✅ Enrollment approved and credentials emailed successfully.</div>
<?php elseif ($msg === 'rejected'): ?>
<div class="alert alert-info">ℹ️ Enrollment request has been rejected and applicant notified.</div>
<?php elseif ($error): ?>
<div class="alert alert-error">❌ An error occurred. Please try again.</div>
<?php endif; ?>

<!-- ── STAT CARDS ─────────────────────────────────────── -->
<div class="stats-grid">

    <div class="stat-card blue">
        <div class="stat-icon">🎒</div>
        <div class="stat-info">
            <div class="label">Total Students</div>
            <div class="value"><?= number_format($totalStudents) ?></div>
            <div class="sub"><?= $approvedCount ?> approved</div>
        </div>
    </div>

    <div class="stat-card green">
        <div class="stat-icon">🎓</div>
        <div class="stat-info">
            <div class="label">Total Teachers</div>
            <div class="value"><?= number_format($activeTeachers) ?></div>
            <div class="sub">Active staff</div>
        </div>
    </div>

    <div class="stat-card orange">
        <div class="stat-icon">⏳</div>
        <div class="stat-info">
            <div class="label">Pending Enrollments</div>
            <div class="value"><?= number_format($pendingCount) ?></div>
            <div class="sub">
                <?php if ($pendingCount > 0): ?>
                    <a href="/CBE_LMS/public/index.php?url=admin/pendingEnrollments" style="color:#f59e0b;font-weight:600">Review now →</a>
                <?php else: ?>
                    All caught up!
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="stat-card cyan">
        <div class="stat-icon">📊</div>
        <div class="stat-info">
            <div class="label">System Overview</div>
            <div class="value"><?= number_format($stats['total_lessons'] ?? 0) ?></div>
            <div class="sub">Lessons uploaded</div>
        </div>
    </div>

</div>

<!-- ── MAIN DASHBOARD GRID ────────────────────────────── -->
<div class="dashboard-grid">

    <!-- 1. Pending Actions / Teacher Requests -->
    <div class="card">
        <div class="card-header">
            <h3>⏳ Pending Actions</h3>
            <span class="badge badge-warning"><?= count($pendingReqs ?? []) ?> Requests</span>
        </div>
        <div class="card-body">
            <div style="display:flex;flex-direction:column;gap:12px;">
                <?php if (empty($pendingReqs)): ?>
                    <p class="text-muted">No pending teacher requests.</p>
                <?php else: ?>
                    <?php foreach ($pendingReqs as $req): ?>
                        <div style="padding:12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;">
                            <div style="font-weight:600;font-size:.9rem;"><?= htmlspecialchars($req['teacher_name']) ?></div>
                            <div style="color:var(--muted);font-size:.8rem;"><?= ucfirst(str_replace('_',' ',$req['request_type'])) ?></div>
                            <p style="font-size:.85rem;margin:8px 0;"><?= htmlspecialchars($req['details']) ?></p>
                            <div style="display:flex;gap:8px;">
                                <form action="/CBE_LMS/public/index.php?url=admin/handleTeacherRequest" method="POST">
                                    <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                    <input type="hidden" name="status" value="approved">
                                    <button type="submit" class="btn btn-success btn-xs">Approve</button>
                                </form>
                                <form action="/CBE_LMS/public/index.php?url=admin/handleTeacherRequest" method="POST">
                                    <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                    <input type="hidden" name="status" value="rejected">
                                    <button type="submit" class="btn btn-danger btn-xs">Reject</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <hr style="border:none;border-top:1px dashed #e2e8f0;margin:10px 0;">
                
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <a href="/CBE_LMS/public/index.php?url=admin/pendingEnrollments" class="btn btn-outline btn-sm w-full">
                        Enrollment Approvals (<?= $pendingCount ?>)
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Enrollment Trend Chart -->
    <div class="card">
        <div class="card-header">
            <h3>📈 Enrollment Trends</h3>
        </div>
        <div class="card-body">
            <div class="chart-container" style="height:250px;">
                <canvas id="enrollmentChart"></canvas>
            </div>
        </div>
    </div>

    <!-- 3. Students Per Grade Distribution -->
    <div class="card">
        <div class="card-header">
            <h3>🏫 Students Per Grade</h3>
        </div>
        <div class="card-body">
            <div class="chart-container" style="height:250px;">
                <canvas id="gradeChart"></canvas>
            </div>
        </div>
    </div>

    <!-- 4. Recently Registered Users -->
    <div class="card" style="grid-column: span 2;">
        <div class="card-header">
            <h3>👥 Recently Registered Users</h3>
            <a href="/CBE_LMS/public/index.php?url=admin/manageUsers" class="btn btn-outline btn-sm">View All</a>
        </div>
        <div class="card-body">
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recentUsers as $ru): ?>
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <div class="user-avatar" style="font-size:.7rem;"><?= strtoupper(substr($ru['fullname'],0,2)) ?></div>
                                    <div>
                                        <div class="name"><?= htmlspecialchars($ru['fullname']) ?></div>
                                        <div class="email"><?= htmlspecialchars($ru['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge badge-muted"><?= ucfirst($ru['role']) ?></span></td>
                            <td>
                                <?php $cls = in_array($ru['status'],['active','approved']) ? 'badge-success' : 'badge-pending'; ?>
                                <span class="badge <?= $cls ?>"><?= ucfirst($ru['status']) ?></span>
                            </td>
                            <td class="text-sm text-muted"><?= date('d M, H:i', strtotime($ru['created_at'])) ?></td>
                            <td>
                                <a href="/CBE_LMS/public/index.php?url=admin/manageUsers&search=<?= urlencode($ru['email']) ?>" class="btn btn-outline btn-xs">Manage</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 5. Quick Actions & Alerts -->
    <div class="card">
        <div class="card-header">
            <h3>⚡ Quick Actions</h3>
        </div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:12px;">
            <a href="/CBE_LMS/public/index.php?url=admin/announcements" class="btn btn-primary btn-lg w-full" style="justify-content:center">
                📢 Send Announcement
            </a>
            <a href="/CBE_LMS/public/index.php?url=admin/manageUsers&action=create_teacher" class="btn btn-success btn-lg w-full" style="justify-content:center">
                ➕ Create Teacher
            </a>
            <a href="/CBE_LMS/public/index.php?url=admin/settings" class="btn btn-warning btn-lg w-full" style="justify-content:center">
                ⚙️ Grading Scales
            </a>
            <a href="/CBE_LMS/public/index.php?url=admin/reports" class="btn btn-outline btn-lg w-full" style="justify-content:center">
                📊 Detailed Reports
            </a>
        </div>
    </div>

</div>

<!-- ── SYSTEM OVERVIEW ROW ────────────────────────────── -->
<div class="card">
    <div class="card-header">
        <h3>📊 System Analytics Summary</h3>
    </div>
    <div class="card-body">
        <div class="analytics-grid">
            <div class="analytics-card">
                <div class="a-value"><?= number_format($stats['total_lessons'] ?? 0) ?></div>
                <div class="a-label">Lessons</div>
            </div>
            <div class="analytics-card green">
                <div class="a-value"><?= number_format($stats['total_assessments'] ?? 0) ?></div>
                <div class="a-label">Assessments</div>
            </div>
            <div class="analytics-card orange">
                <div class="a-value"><?= number_format($stats['active_classes'] ?? 0) ?></div>
                <div class="a-label">Active Classes</div>
            </div>
            <div class="analytics-card cyan">
                <div class="a-value"><?= number_format($approvedCount) ?></div>
                <div class="a-label">Active Students</div>
            </div>
            <div class="analytics-card red">
                <div class="a-value"><?= count($pendingReqs ?? []) ?></div>
                <div class="a-label">Pending Req</div>
            </div>
        </div>
    </div>
</div>

</div><!-- .content -->
</div><!-- .main-wrapper -->

<script>
/* ── Charts ───────────────────────────── */
(function () {
    // Enrollment Trend
    const trendCtx = document.getElementById('enrollmentChart');
    if (trendCtx) {
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($trends['labels'] ?? []) ?>,
                datasets: [
                    {
                        label: 'Students',
                        data: <?= json_encode($trends['students'] ?? []) ?>,
                        borderColor: '#4f46e5',
                        backgroundColor: 'rgba(79,70,229,.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Teachers',
                        data: <?= json_encode($trends['teachers'] ?? []) ?>,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16,185,129,.1)',
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
        });
    }

    // Grade Distribution
    const gradeCtx = document.getElementById('gradeChart');
    if (gradeCtx) {
        const gradeData = <?= json_encode($gradeDist ?? []) ?>;
        new Chart(gradeCtx, {
            type: 'bar',
            data: {
                labels: gradeData.map(d => d.label),
                datasets: [{
                    label: 'Students',
                    data: gradeData.map(d => d.value),
                    backgroundColor: '#6366f1',
                    borderRadius: 4
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
        });
    }
})();
</script>

</body>
</html>
