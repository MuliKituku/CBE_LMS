<?php
/* ── teacher/student_progress.php ──────────────────────────
   Variables: $profile, $student (contains assessments, competencies)
   ─────────────────────────────────────────────────────────── */
$activeNav = 'students';
require BASE_PATH . '/app/views/teacher/_sidebar.php';

$success = $_GET['success'] ?? '';
$error   = $_GET['error'] ?? '';
?>

<!-- Alerts -->
<?php if ($success === 'competency_updated'): ?>
<div class="alert alert-success">✔️ Competency status updated!</div>
<?php elseif ($success === 'feedback_added'): ?>
<div class="alert alert-success">✔️ Direct feedback sent to student!</div>
<?php elseif ($error === 'empty_message'): ?>
<div class="alert alert-error">❌ Feedback message cannot be empty.</div>
<?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <div style="display:flex;align-items:center;gap:16px">
        <a href="/CBE_LMS/public/index.php?url=teacher/students" class="btn btn-outline" style="padding:6px 12px;border-radius:50%;width:36px;height:36px;display:flex;align-items:center;justify-content:center">←</a>
        <div>
            <h2 style="color:var(--text);margin:0;font-size:1.5rem"><?= htmlspecialchars($student['fullname']) ?></h2>
            <div style="font-size:0.85rem;color:var(--muted);margin-top:4px">
                Reg No: <?= htmlspecialchars($student['student_reg_no']) ?> | Grade <?= htmlspecialchars($student['class_grade']) ?>
            </div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:24px">

    <!-- LEFT COLUMN: Competency Management -->
    <div class="card">
        <div class="card-header">
            <h3>Manage Competencies</h3>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Core Competency</th>
                        <th>Current Status</th>
                        <th>Update Status</th>
                        <th style="width:100px;text-align:right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($student['competencies'])): ?>
                    <tr>
                        <td colspan="4" style="text-align:center;padding:20px;color:var(--muted)">No competencies defined for this grade.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($student['competencies'] as $c): ?>
                        <tr>
                            <td>
                                <div style="font-weight:600;color:var(--text);margin-top:2px"><?= htmlspecialchars($c['title']) ?></div>
                            </td>
                            <td>
                                <?php
                                    $sClass = 'badge-danger'; $sLabel = 'Not Started'; $sStyle = '';
                                    if ($c['status'] === 'in_progress') { $sClass = 'badge-warning'; $sLabel = 'In Progress'; }
                                    if ($c['status'] === 'mastered') { $sClass = 'badge-success'; $sLabel = 'Mastered'; }
                                    if ($c['status'] === 'fully_mastered') { $sClass = ''; $sLabel = 'Fully Mastered'; $sStyle = 'background:#8b5cf6;color:#fff'; }
                                ?>
                                <span class="badge <?= $sClass ?>" style="<?= $sStyle ?>"><?= $sLabel ?></span>
                            </td>
                            <!-- Inline Form for Updating -->
                            <form method="POST" action="/CBE_LMS/public/index.php?url=teacher/updateCompetency">
                                <td style="padding:10px">
                                    <input type="hidden" name="student_id" value="<?= $student['student_id'] ?>">
                                    <input type="hidden" name="competency_id" value="<?= $c['id'] ?>">
                                    
                                    <select name="status" class="form-control" style="font-size:0.85rem;padding:6px;margin-bottom:6px" required>
                                        <option value="not_started" <?= $c['status'] === 'not_started' ? 'selected' : '' ?>>Not Started</option>
                                        <option value="in_progress" <?= $c['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                        <option value="mastered" <?= $c['status'] === 'mastered' ? 'selected' : '' ?>>Mastered</option>
                                        <option value="fully_mastered" <?= $c['status'] === 'fully_mastered' ? 'selected' : '' ?>>Fully Mastered</option>
                                    </select>
                                    
                                    <input type="number" name="score" class="form-control" placeholder="Score %" value="<?= $c['score'] ?? '' ?>" style="font-size:0.85rem;padding:6px">
                                </td>
                                <td style="text-align:right">
                                    <button type="submit" class="btn btn-outline btn-sm" style="background:#10b981;color:#fff;border:none">Update</button>
                                </td>
                            </form>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- RIGHT COLUMN: Direct Feedback & Assessment History -->
    <div style="display:flex;flex-direction:column;gap:24px">
        
        <!-- Direct Feedback -->
        <div class="card">
            <div class="card-header" style="background:#f0fdf4">
                <h3 style="color:#065f46">Post Direct Feedback</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="/CBE_LMS/public/index.php?url=teacher/addFeedback">
                    <input type="hidden" name="student_id" value="<?= $student['student_id'] ?>">
                    <textarea name="message" class="form-control" rows="4" placeholder="Write feedback directly to the student's dashboard..." required style="margin-bottom:12px;resize:none"></textarea>
                    <button type="submit" class="btn btn-primary" style="background:var(--primary);border:none;width:100%">Send Feedback</button>
                </form>
            </div>
        </div>

        <!-- Recent Assessments -->
        <div class="card">
            <div class="card-header">
                <h3>Recent Submissions</h3>
            </div>
            <div class="card-body" style="padding:0">
                <?php if (empty($student['assessments'])): ?>
                    <div style="padding:20px;text-align:center;color:var(--muted)">No assessments taken for your classes yet.</div>
                <?php else: ?>
                    <ul style="list-style:none;padding:0;margin:0">
                        <?php foreach ($student['assessments'] as $a): ?>
                        <li style="padding:15px 20px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center">
                            <div>
                                <div style="font-weight:600;color:var(--text);font-size:0.95rem"><?= htmlspecialchars($a['title']) ?></div>
                                <div style="font-size:0.75rem;color:var(--muted);margin-top:2px"><?= htmlspecialchars($a['subject']) ?></div>
                            </div>
                            <div style="text-align:right">
                                <?php if ($a['status'] === 'graded'): ?>
                                    <div style="font-weight:800;font-size:1.1rem;color:<?= $a['percentage'] >= 51 ? '#10b981' : '#ef4444' ?>">
                                        <?= Controller::getCbcPerformanceLevel((int)$a['percentage']) ?>
                                    </div>
                                    <div style="font-size:0.75rem;color:var(--muted)"><?= (int)$a['percentage'] ?>%</div>
                                <?php else: ?>
                                    <span class="badge badge-warning">Needs Grade</span>
                                <?php endif; ?>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
</div>

</div><!-- .content -->
</div><!-- .main-wrapper -->
</body>
</html>
