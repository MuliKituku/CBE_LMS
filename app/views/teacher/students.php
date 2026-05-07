<?php
/* ── teacher/students.php ──────────────────────────────────
   Variables: $profile, $students
   ─────────────────────────────────────────────────────────── */
$activeNav = 'students';
require BASE_PATH . '/app/views/teacher/_sidebar.php';

$error = $_GET['error'] ?? '';
?>

<?php if ($error === 'not_found'): ?>
<div class="alert alert-error">❌ Student not found or not in your current classes.</div>
<?php endif; ?>

<div class="card" style="margin-bottom:24px;border:none;background:linear-gradient(135deg,#047857,#059669);color:#fff">
    <div class="card-body">
        <h2 style="color:#fff;margin-bottom:6px">Class Roster & Analytics</h2>
        <p style="color:#a7f3d0;margin:0;font-size:.9rem">
            View student performance, manage competencies, and track overall progress across your assigned grades.
        </p>
    </div>
</div>

<div class="search-bar" style="margin-bottom:24px">
    <div style="display:flex;gap:15px;align-items:center">
        <label style="font-size:0.85rem;font-weight:600;color:var(--muted)">Filter Students:</label>
        <a href="?url=teacher/students&filter=all" class="btn btn-sm <?= ($currentFilter ?? 'all') === 'all' ? 'btn-primary' : 'btn-outline' ?>">All Students</a>
        <a href="?url=teacher/students&filter=top" class="btn btn-sm <?= ($currentFilter ?? '') === 'top' ? 'btn-primary' : 'btn-outline' ?>" style="<?= ($currentFilter ?? '') === 'top' ? '' : 'color:#059669;border-color:#059669' ?>">Top Performers</a>
        <a href="?url=teacher/students&filter=at_risk" class="btn btn-sm <?= ($currentFilter ?? '') === 'at_risk' ? 'btn-primary' : 'btn-outline' ?>" style="<?= ($currentFilter ?? '') === 'at_risk' ? '' : 'color:#ef4444;border-color:#ef4444' ?>">At-Risk Students</a>
    </div>
</div>

<div class="card">
    <div class="table-wrapper">
        <?php if (empty($students)): ?>
        <div style="padding:40px;text-align:center;color:var(--muted)">
            <p>No students found for the classes you teach.</p>
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Student Name & Email</th>
                    <th>Reg No</th>
                    <th>Grade</th>
                    <th style="text-align:center">Avg Score</th>
                    <th style="width:200px">Competency Mastery</th>
                    <th style="width:100px;text-align:right">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $s): ?>
                <tr>
                    <td>
                        <div style="font-weight:600;color:var(--text)"><?= htmlspecialchars($s['fullname']) ?></div>
                        <div style="font-size:0.8rem;color:var(--muted);margin-top:4px"><?= htmlspecialchars($s['email']) ?></div>
                    </td>
                    <td><span class="badge badge-info"><?= htmlspecialchars($s['student_reg_no']) ?></span></td>
                    <td style="font-weight:600;color:var(--primary)">Grade <?= htmlspecialchars($s['class_grade']) ?></td>
                    
                    <td style="text-align:center;font-weight:700;font-size:1.1rem;color:<?= $s['avg_score'] >= 50 ? '#10b981' : '#ef4444' ?>">
                        <?= $s['avg_score'] ?>%
                    </td>

                    <td>
                        <div style="width:100%;background:#e2e8f0;border-radius:10px;height:8px;overflow:hidden">
                            <?php
                                $c = '#ef4444'; // red
                                if ($s['mastery_pct'] >= 50) $c = '#f59e0b'; // orange
                                if ($s['mastery_pct'] >= 75) $c = '#10b981'; // green
                            ?>
                            <div style="height:100%;width:<?= $s['mastery_pct'] ?>%;background:<?= $c ?>"></div>
                        </div>
                        <div style="font-size:.75rem;color:var(--muted);margin-top:4px;text-align:right">
                            <?= $s['mastery_pct'] ?>% completed
                        </div>
                    </td>
                    
                    <td style="text-align:right">
                        <a href="/CBE_LMS/public/index.php?url=teacher/studentProgress/<?= $s['student_id'] ?>" class="btn btn-outline btn-sm">Manage</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

</div><!-- .content -->
</div><!-- .main-wrapper -->
</body>
</html>
