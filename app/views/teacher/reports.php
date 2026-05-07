<?php
/* ── teacher/reports.php ────────────────────────────────
   Variables: $profile, $classes, $students
   ────────────────────────────────────────────────────────── */
$activeNav = 'reports';
require BASE_PATH . '/app/views/teacher/_sidebar.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <h2 style="color:var(--text);margin:0">Performance Reports</h2>
    <div class="btn-group">
        <button class="btn btn-outline btn-sm" onclick="window.print()">🖨️ Print Report</button>
        <button class="btn btn-success btn-sm">📥 Export CSV</button>
    </div>
</div>

<!-- CLASS SUMMARY -->
<div class="card" style="margin-bottom:24px">
    <div class="card-header">
        <h3>Class Mastery Overview</h3>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Class</th>
                    <th>Total Students</th>
                    <th>Average Score</th>
                    <th>Mastery %</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($classes as $c): 
                    // Calculate a quick grading status
                    $avg = 75; // Placeholder or calculate from students
                    $mastery = 60; // Placeholder
                    $status = 'Good';
                    if ($avg < 50) $status = 'Needs Attention';
                    if ($avg > 85) $status = 'Excellent';
                ?>
                <tr>
                    <td style="font-weight:600">Grade <?= htmlspecialchars($c['class_grade']) ?></td>
                    <td><?= $c['student_count'] ?></td>
                    <td>
                        <div style="font-weight:700;color:var(--text)"><?= Controller::getCbcPerformanceLevel($avg) ?></div>
                        <div style="font-size:0.75rem;color:var(--muted)">(<?= $avg ?>%)</div>
                    </td>
                    <td>
                        <div style="width:100px;background:#f1f5f9;height:8px;border-radius:4px;overflow:hidden;margin-top:4px">
                            <div style="width:<?= $mastery ?>%;background:var(--primary);height:100%"></div>
                        </div>
                        <span style="font-size:0.75rem;color:var(--muted)"><?= $mastery ?>% Mastered</span>
                    </td>
                    <td>
                        <?php 
                            $badgeClass = 'badge-success';
                            if ($avg < 26) $badgeClass = 'badge-danger';
                            elseif ($avg < 51) $badgeClass = 'badge-warning';
                            elseif ($avg < 76) $badgeClass = 'badge-info';
                        ?>
                        <span class="badge <?= $badgeClass ?>">
                            <?= Controller::getCbcPerformanceLevel($avg) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- INDIVIDUAL STUDENT TABLE -->
<div class="card">
    <div class="card-header">
        <h3>Individual Student Performance</h3>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Reg No</th>
                    <th>Grade</th>
                    <th>Avg. Score</th>
                    <th>Competency Mastery</th>
                    <th style="text-align:right">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $s): ?>
                <tr>
                    <td style="font-weight:600"><?= htmlspecialchars($s['fullname']) ?></td>
                    <td style="font-size:0.85rem;color:var(--muted)"><?= htmlspecialchars($s['student_reg_no']) ?></td>
                    <td>Grade <?= htmlspecialchars($s['class_grade']) ?></td>
                    <td>
                        <div style="font-weight:700;color:var(--text)"><?= Controller::getCbcPerformanceLevel((int)$s['avg_score']) ?></div>
                        <div style="font-size:0.75rem;color:var(--muted)">(<?= (float)$s['avg_score'] ?>%)</div>
                    </td>
                    <td>
                        <div style="width:100px;background:#f1f5f9;height:8px;border-radius:4px;overflow:hidden;margin-top:4px">
                            <div style="width:<?= $s['mastery_pct'] ?>%;background:var(--accent);height:100%"></div>
                        </div>
                        <span style="font-size:0.75rem;color:var(--muted)"><?= $s['mastery_pct'] ?>%</span>
                    </td>
                    <td style="text-align:right">
                        <a href="/CBE_LMS/public/index.php?url=teacher/studentProgress/<?= $s['student_id'] ?>" class="btn btn-outline btn-sm">Analytics</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</div><!-- .content -->
</div><!-- .main-wrapper -->
</body>
</html>
