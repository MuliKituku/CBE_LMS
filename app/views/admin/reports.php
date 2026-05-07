<?php
/* ── admin/reports.php ───────────────────────────────────────
   Variables: $studentReport, $teacherReport, $analytics (arrays)
   ─────────────────────────────────────────────────────────── */

$activeNav = 'reports';
require_once BASE_PATH . '/app/models/User.php';
require BASE_PATH . '/app/views/admin/_sidebar.php';

$tab = $_GET['tab'] ?? 'students';
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
    <h2>System Reports</h2>
    <button onclick="printWholeSystem()" class="btn btn-primary">🖨️ Print Whole System Report</button>
</div>

<!-- Tab Navigation -->
<div class="tabs">
    <button class="tab-btn <?= $tab === 'students'  ? 'active' : '' ?>"
            data-tab="students">🎒 Student Performance</button>
    <button class="tab-btn <?= $tab === 'teachers'  ? 'active' : '' ?>"
            data-tab="teachers">🎓 Teacher Activity</button>
    <button class="tab-btn <?= $tab === 'analytics' ? 'active' : '' ?>"
            data-tab="analytics">📊 System Analytics</button>
    <button class="tab-btn <?= $tab === 'competency' ? 'active' : '' ?>"
            data-tab="competency">🎯 Competency Mastery</button>
    <button class="tab-btn <?= $tab === 'at-risk' ? 'active' : '' ?>"
            data-tab="at-risk">⚠️ At-Risk Students</button>
    <button class="tab-btn <?= $tab === 'usage' ? 'active' : '' ?>"
            data-tab="usage">📈 System Usage</button>
</div>

<!-- ── TAB: STUDENT PERFORMANCE ──────────────────────────── -->
<div class="tab-pane <?= $tab === 'students' ? 'active' : '' ?>" id="tab-students">

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
        <h3 style="font-size:1rem;font-weight:700;">📋 Student Performance Report</h3>
        <div>
            <button onclick="window.print()" class="btn btn-outline btn-sm">🖨️ Print</button>
            <button onclick="exportTable('studentsTable','Student_Performance_Report')" class="btn btn-outline btn-sm">📥 Export CSV</button>
        </div>
    </div>

    <div class="card">
        <?php if (empty($studentReport)): ?>
            <div class="empty-state">
                <div class="empty-icon">🎒</div>
                <h3>No Student Data</h3>
                <p>No approved students found in the system.</p>
            </div>
        <?php else: ?>
        <div class="table-wrapper">
            <table id="studentsTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student Name</th>
                        <th>Registration No</th>
                        <th>Class / Grade</th>
                        <th>Gender</th>
                        <th>Date of Birth</th>
                        <th>Status</th>
                        <th>Enrolled On</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($studentReport as $i => $s): ?>
                <tr>
                    <td style="color:var(--muted)"><?= $i + 1 ?></td>
                    <td>
                        <div class="user-cell">
                            <div class="user-avatar" style="font-size:.72rem;">
                                <?= strtoupper(substr($s['student_name'], 0, 2)) ?>
                            </div>
                            <div>
                                <div class="name"><?= htmlspecialchars($s['student_name']) ?></div>
                                <div class="email"><?= htmlspecialchars($s['student_email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td style="font-family:monospace;font-size:.82rem;"><?= htmlspecialchars($s['student_reg_no'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($s['class_grade'] ?? '—') ?></td>
                    <td><?= ucfirst(htmlspecialchars($s['gender'] ?? '—')) ?></td>
                    <td><?= $s['date_of_birth'] ? date('d M Y', strtotime($s['date_of_birth'])) : '—' ?></td>
                    <td>
                        <?php
                        $sc = ['approved'=>'badge-success','pending'=>'badge-pending','rejected'=>'badge-danger'];
                        $cls = $sc[$s['status'] ?? ''] ?? 'badge-muted';
                        ?>
                        <span class="badge <?= $cls ?>"><?= ucfirst($s['status'] ?? '—') ?></span>
                    </td>
                    <td style="color:var(--muted);font-size:.82rem;">
                        <?= $s['created_at'] ? date('d M Y', strtotime($s['created_at'])) : '—' ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ── TAB: TEACHER ACTIVITY ─────────────────────────────── -->
<div class="tab-pane <?= $tab === 'teachers' ? 'active' : '' ?>" id="tab-teachers">

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
        <h3 style="font-size:1rem;font-weight:700;">📋 Teacher Activity Report</h3>
        <div>
            <button onclick="window.print()" class="btn btn-outline btn-sm">🖨️ Print</button>
            <button onclick="exportTable('teachersTable','Teacher_Activity_Report')" class="btn btn-outline btn-sm">📥 Export CSV</button>
        </div>
    </div>

    <div class="card">
        <?php if (empty($teacherReport)): ?>
            <div class="empty-state">
                <div class="empty-icon">🎓</div>
                <h3>No Teacher Data</h3>
                <p>No teachers found in the system yet.</p>
            </div>
        <?php else: ?>
        <div class="table-wrapper">
            <table id="teachersTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Teacher Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Joined On</th>
                        <th>Last Updated</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($teacherReport as $i => $t): ?>
                <tr>
                    <td style="color:var(--muted)"><?= $i + 1 ?></td>
                    <td>
                        <div class="user-cell">
                            <div class="user-avatar" style="background:linear-gradient(135deg,#10b981,#059669);font-size:.72rem;">
                                <?= strtoupper(substr($t['fullname'], 0, 2)) ?>
                            </div>
                            <div class="name"><?= htmlspecialchars($t['fullname']) ?></div>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($t['email']) ?></td>
                    <td>
                        <?php $ts = in_array($t['status'],['active','approved']) ? 'badge-success' : 'badge-inactive'; ?>
                        <span class="badge <?= $ts ?>"><?= ucfirst($t['status']) ?></span>
                    </td>
                    <td style="color:var(--muted);font-size:.82rem;">
                        <?= $t['created_at'] ? date('d M Y', strtotime($t['created_at'])) : '—' ?>
                    </td>
                    <td style="color:var(--muted);font-size:.82rem;">
                        <?= (!empty($t['updated_at']) && $t['updated_at'] !== '0000-00-00 00:00:00')
                            ? date('d M Y', strtotime($t['updated_at'])) : '—' ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ── TAB: SYSTEM ANALYTICS ────────────────────────────── -->
<div class="tab-pane <?= $tab === 'analytics' ? 'active' : '' ?>" id="tab-analytics">

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
        <h3 style="font-size:1rem;font-weight:700;">📊 System Analytics</h3>
        <button onclick="window.print()" class="btn btn-outline btn-sm">🖨️ Print Analytics</button>
    </div>

    <!-- Key Metrics -->
    <div class="analytics-grid" style="margin-bottom:24px;">
        <div class="analytics-card">
            <div class="a-value"><?= $analytics['total_users'] ?? 0 ?></div>
            <div class="a-label">Total Users</div>
        </div>
        <div class="analytics-card green">
            <div class="a-value"><?= $analytics['total_students'] ?? 0 ?></div>
            <div class="a-label">Students</div>
        </div>
        <div class="analytics-card orange">
            <div class="a-value"><?= $analytics['total_teachers'] ?? 0 ?></div>
            <div class="a-label">Teachers</div>
        </div>
        <div class="analytics-card cyan">
            <div class="a-value"><?= $analytics['total_parents'] ?? 0 ?></div>
            <div class="a-label">Parents</div>
        </div>
        <div class="analytics-card red">
            <div class="a-value"><?= $analytics['pending_enrollments'] ?? 0 ?></div>
            <div class="a-label">Pending</div>
        </div>
    </div>

    <!-- Status Breakdown Charts -->
    <div class="dashboard-grid">
        <div class="card">
            <div class="card-header">
                <h3>👥 User Role Distribution</h3>
            </div>
            <div class="card-body">
                <div class="chart-container" style="height:220px;">
                    <canvas id="roleChart"></canvas>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>📊 Enrollment Status</h3>
            </div>
            <div class="card-body">
                <div class="chart-container" style="height:220px;">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Stats -->
    <div class="card mt-4">
        <div class="card-header"><h3>🔢 Detailed Breakdown</h3></div>
        <div class="card-body">
            <table>
                <thead>
                    <tr><th>Metric</th><th>Count</th><th>%</th></tr>
                </thead>
                <tbody>
                <?php
                $total = max(1, $analytics['total_users'] ?? 1);
                $rows  = [
                    ['Students',           $analytics['total_students']       ?? 0],
                    ['Teachers',           $analytics['total_teachers']       ?? 0],
                    ['Parents',            $analytics['total_parents']        ?? 0],
                    ['Active Students',    $analytics['approved_students']    ?? 0],
                    ['Pending Enrollment', $analytics['pending_enrollments']  ?? 0],
                    ['Inactive Users',     $analytics['inactive_users']       ?? 0],
                ];
                foreach ($rows as [$label, $count]): ?>
                <tr>
                    <td><?= $label ?></td>
                    <td><strong><?= $count ?></strong></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div style="flex:1;height:6px;background:#f1f5f9;border-radius:3px;overflow:hidden">
                                <div style="height:100%;width:<?= min(100, round($count/$total*100)) ?>%;background:var(--primary);border-radius:3px;"></div>
                            </div>
                            <span style="font-size:.8rem;color:var(--muted);min-width:36px;">
                                <?= round($count/$total*100) ?>%
                            </span>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- end tab-analytics -->

<!-- ── TAB: COMPETENCY MASTERY ────────────────────────── -->
<div class="tab-pane <?= $tab === 'competency' ? 'active' : '' ?>" id="tab-competency">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
        <h3 style="font-size:1rem;font-weight:700;">🎯 Core Competency Mastery (CBC)</h3>
        <div>
            <button onclick="window.print()" class="btn btn-outline btn-sm">🖨️ Print</button>
            <button onclick="exportTable('compTable','Competency_Mastery')" class="btn btn-outline btn-sm">📥 Export CSV</button>
        </div>
    </div>
    
    <div class="dashboard-grid">
        <div class="card">
            <div class="card-header"><h3>📊 Mastery Breakdown</h3></div>
            <div class="card-body">
                <table id="compTable">
                    <thead><tr><th>Status</th><th>Count</th></tr></thead>
                    <tbody>
                        <?php foreach($competencies as $c): ?>
                        <tr><td><?= ucfirst(str_replace('_',' ',$c['status'])) ?></td><td><strong><?= $c['count'] ?></strong></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3>ℹ️ Definition</h3></div>
            <div class="card-body">
                <p class="text-sm"><strong>EE:</strong> Exceeds Expectation (80-100%)</p>
                <p class="text-sm"><strong>ME:</strong> Meets Expectation (60-79%)</p>
                <p class="text-sm"><strong>AE:</strong> Approaching Expectation (40-59%)</p>
                <p class="text-sm"><strong>BE:</strong> Below Expectation (0-39%)</p>
            </div>
        </div>
    </div>
</div>

<!-- ── TAB: AT-RISK STUDENTS ──────────────────────────── -->
<div class="tab-pane <?= $tab === 'at-risk' ? 'active' : '' ?>" id="tab-at-risk">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
        <h3 style="font-size:1rem;font-weight:700;">⚠️ At-Risk Students (BE Level)</h3>
        <div>
            <button onclick="window.print()" class="btn btn-outline btn-sm">🖨️ Print</button>
            <button onclick="exportTable('atRiskTable','At_Risk_Students')" class="btn btn-outline btn-sm">📥 Export CSV</button>
        </div>
    </div>
    <div class="card">
        <div class="table-wrapper">
            <table id="atRiskTable">
                <thead>
                    <tr><th>Student</th><th>Reg No</th><th>Grade</th><th>Lowest Competency</th><th>Score</th></tr>
                </thead>
                <tbody>
                    <?php if(empty($atRisk)): ?>
                        <tr><td colspan="5" style="text-align:center;">No students currently at risk.</td></tr>
                    <?php else: ?>
                        <?php foreach($atRisk as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['fullname']) ?></td>
                            <td><?= htmlspecialchars($r['student_reg_no']) ?></td>
                            <td><?= htmlspecialchars($r['class_grade']) ?></td>
                            <td><?= htmlspecialchars($r['competency']) ?></td>
                            <td><span class="badge badge-danger"><?= $r['score'] ?>% (BE)</span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ── TAB: SYSTEM USAGE ──────────────────────────────── -->
<div class="tab-pane <?= $tab === 'usage' ? 'active' : '' ?>" id="tab-usage">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
        <h3 style="font-size:1rem;font-weight:700;">📈 System Usage Statistics</h3>
        <button onclick="window.print()" class="btn btn-outline btn-sm">🖨️ Print Statistics</button>
    </div>
    <div class="analytics-grid">
        <div class="analytics-card">
            <div class="a-value"><?= number_format($usage['quiz_attempts']) ?></div>
            <div class="a-label">Quiz Attempts</div>
        </div>
        <div class="analytics-card green">
            <div class="a-value"><?= number_format($usage['graded_quizzes']) ?></div>
            <div class="a-label">Graded Quizzes</div>
        </div>
        <div class="analytics-card orange">
            <div class="a-value"><?= number_format($usage['lesson_completions']) ?></div>
            <div class="a-label">User Engagement</div>
        </div>
    </div>
</div>

</div><!-- .content -->
</div><!-- .main-wrapper -->

<script>
/* ── Tab switching ────────────────────────────────────── */
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.tab-btn').forEach(b  => b.classList.remove('active'));
        document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
        this.classList.add('active');
        document.getElementById('tab-' + this.dataset.tab).classList.add('active');
    });
});

/* ── Role Distribution Doughnut ──────────────────────── */
(function() {
    const ctx = document.getElementById('roleChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Students', 'Teachers', 'Parents'],
            datasets: [{
                data: [
                    <?= (int)($analytics['total_students'] ?? 0) ?>,
                    <?= (int)($analytics['total_teachers'] ?? 0) ?>,
                    <?= (int)($analytics['total_parents']  ?? 0) ?>
                ],
                backgroundColor: ['#4f46e5','#10b981','#f59e0b'],
                borderWidth: 0,
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { padding: 16, font: { size: 12 } } }
            },
            cutout: '65%'
        }
    });
})();

/* ── Enrollment Status Bar Chart ─────────────────────── */
(function() {
    const ctx = document.getElementById('statusChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Approved', 'Pending', 'Inactive'],
            datasets: [{
                label: 'Students',
                data: [
                    <?= (int)($analytics['approved_students']   ?? 0) ?>,
                    <?= (int)($analytics['pending_enrollments'] ?? 0) ?>,
                    <?= (int)($analytics['inactive_users']      ?? 0) ?>
                ],
                backgroundColor: ['#10b981','#f59e0b','#94a3b8'],
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: {stepSize:1}, grid:{color:'#f1f5f9'} },
                x: { grid: { display: false } }
            }
        }
    });
})();

/* ── CSV Export ───────────────────────────────────────── */
function exportTable(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;
    let csv = [];
    table.querySelectorAll('tr').forEach(row => {
        const cols = [];
        row.querySelectorAll('th,td').forEach(cell => {
            // Clean text, remove badge formatting etc.
            let text = cell.innerText.replace(/\n+/g,' ').trim().replace(/,/g,';');
            cols.push('"' + text + '"');
        });
        csv.push(cols.join(','));
    });
    const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = filename + '_' + new Date().toISOString().slice(0,10) + '.csv';
    a.click();
}

/* ── Print Whole System ───────────────────────────────── */
function printWholeSystem() {
    document.body.classList.add('print-all-tabs');
    window.print();
    document.body.classList.remove('print-all-tabs');
}
</script>
</body>
</html>
