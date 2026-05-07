<?php
/* ── parent/assessments.php ────────────────────────────────
   Variables: $profile, $studentId, $childName, $assessments
   ─────────────────────────────────────────────────────────── */
$activeNav = 'assessments';
require BASE_PATH . '/app/views/parent/_sidebar.php';

$children = $profile['children'] ?? [];
?>

<!-- Child Selector -->
<?php if (count($children) > 1): ?>
<div class="card" style="margin-bottom:20px;padding:12px 20px;background:#f8fafc;display:flex;align-items:center;gap:12px">
    <strong style="color:var(--text);font-size:.9rem">Select Child:</strong>
    <form method="GET" action="/CBE_LMS/public/index.php" style="margin:0">
        <input type="hidden" name="url" value="parent/assessments">
        <select name="child_id" class="form-control" style="width:250px;padding:6px 10px" onchange="this.form.submit()">
            <?php foreach ($children as $c): ?>
            <option value="<?= $c['student_id'] ?>" <?= $studentId === (int)$c['student_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['student_name']) ?> (<?= htmlspecialchars($c['class_grade']) ?>)
            </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>
<?php endif; ?>

<!-- HEADER -->
<div class="card" style="margin-bottom:24px;border:none;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff">
    <div class="card-body" style="display:flex; justify-content:space-between; align-items:center; gap:20px; flex-wrap:wrap;">
        <div>
            <h2 style="color:#fff;margin-bottom:6px"><?= htmlspecialchars($childName) ?>'s Assessment Results</h2>
            <p style="color:#e0e7ff;margin:0;font-size:.9rem">
                View recent quiz scores, grades, and teacher remarks.
            </p>
        </div>
        <?php if ($studentId): ?>
        <button onclick="window.print()" class="btn btn-outline btn-sm" style="background:rgba(255,255,255,0.1);color:#fff;border-color:rgba(255,255,255,0.2);">🖨️ Print Results</button>
        <?php endif; ?>
    </div>
</div>

<?php if (!$studentId): ?>
<!-- Edge case: no children mapping -->
<div class="alert alert-error">No children accounts linked to this profile.</div>
<?php else: ?>

<div class="card">
    <div class="table-wrapper">
        <?php if (empty($assessments)): ?>
        <div class="empty-state" style="padding:40px 20px">
            <div class="empty-icon">📝</div>
            <p>No assessments have been taken by this student yet.</p>
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Assessment Title & Date</th>
                    <th>Subject</th>
                    <th>Teacher</th>
                    <th style="width:120px;text-align:center">Score</th>
                    <th style="width:250px">Performance</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($assessments as $a): ?>
                <tr>
                    <td>
                        <div style="font-weight:700;color:var(--text)"><?= htmlspecialchars($a['title']) ?></div>
                        <div style="font-size:.75rem;color:var(--muted);margin-top:2px">
                            Taken: <?= date('d M Y, h:i A', strtotime($a['submitted_at'])) ?>
                        </div>
                    </td>
                    <td><span class="badge badge-info"><?= htmlspecialchars($a['subject']) ?></span></td>
                    <td style="font-size:.9rem;color:#475569"><?= htmlspecialchars($a['teacher_name']) ?></td>
                    <td style="text-align:center">
                        <?php if ($a['status'] === 'graded' || $a['status'] === 'submitted'): ?>
                            <span style="font-size:1.1rem;font-weight:800;color:#0f766e"><?= (int)$a['percentage'] ?>%</span>
                        <?php else: ?>
                            <span class="badge badge-pending">Pending</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($a['status'] === 'graded' || $a['status'] === 'submitted'): ?>
                        <div style="width:100%;background:#e2e8f0;border-radius:10px;height:8px;overflow:hidden">
                            <?php
                                $c = '#ef4444'; // red
                                if ($a['percentage'] >= 50) $c = '#f59e0b'; // orange
                                if ($a['percentage'] >= 75) $c = '#10b981'; // green
                            ?>
                            <div style="height:100%;width:<?= (int)$a['percentage'] ?>%;background:<?= $c ?>"></div>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:6px">
                            <span style="font-size:.75rem;color:var(--muted)"><?= $a['score'] ?> correct</span>
                            <?php if (!empty($a['teacher_remark'])): ?>
                            <span class="badge badge-success" style="font-size:.65rem;cursor:help" title="<?= htmlspecialchars($a['teacher_remark']) ?>">💬 Feedback</span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($a['teacher_remark'])): ?>
                        <div style="margin-top:8px;padding:8px;background:#f0fdf4;border-radius:6px;font-size:0.8rem;color:#166534;border:1px solid #dcfce7">
                            <strong>Feedback:</strong> <?= htmlspecialchars($a['teacher_remark']) ?>
                        </div>
                        <?php endif; ?>
                        <?php else: ?>
                        <span style="font-size:.85rem;color:var(--muted)">Awaiting grading</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php endif; ?>
</div></div>
</body></html>
