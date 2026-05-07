<?php
/* ── student/assessments.php ────────────────────────────────
   Variables: $profile, $upcoming (array), $completed (array)
   ─────────────────────────────────────────────────────────── */
$activeNav = 'assessments';
require BASE_PATH . '/app/views/student/_sidebar.php';

$successMsg = $_GET['success'] ?? '';
$errorMsg   = $_GET['error']   ?? '';
?>

<?php if ($successMsg === 'submitted'): ?>
<div class="alert alert-success">
    ✅ Quiz submitted! Your score: <strong><?= (int)$_GET['score'] ?>/<?= (int)$_GET['max'] ?></strong>
    — <?= (int)$_GET['pct'] ?>%
    <?= (int)$_GET['pct'] >= 50 ? '🎉 Well done!' : '💪 Keep practising!' ?>
</div>
<?php elseif ($errorMsg === 'already_submitted'): ?>
<div class="alert alert-info">ℹ️ You have already submitted this assessment.</div>
<?php elseif ($errorMsg === 'not_yet_available'): ?>
<div class="alert alert-warning">🔒 This assessment is locked until the opening time.</div>
<?php elseif ($errorMsg === 'closed'): ?>
<div class="alert alert-error">⌛ This assessment has closed and can no longer be attempted.</div>
<?php elseif ($errorMsg === 'invalid'): ?>
<div class="alert alert-error">❌ Invalid submission. Please try again.</div>
<?php endif; ?>

<!-- Tabs -->
<div class="tabs">
    <button class="tab-btn active" data-tab="upcoming">📋 Upcoming
        <?php if (count($upcoming) > 0): ?>
        <span class="nav-badge" style="position:relative;top:0;right:0;margin-left:6px"><?= count($upcoming) ?></span>
        <?php endif; ?>
    </button>
    <button class="tab-btn" data-tab="completed">✅ Completed (<?= count($completed) ?>)</button>
</div>

<!-- UPCOMING -->
<div class="tab-pane active" id="tab-upcoming">
    <?php if (empty($upcoming)): ?>
    <div class="card">
        <div class="empty-state">
            <div class="empty-icon">🎉</div>
            <h3>All Caught Up!</h3>
            <p>No pending assessments at the moment.</p>
        </div>
    </div>
    <?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px">
    <?php foreach ($upcoming as $a): ?>
    <?php
        $isOverdue = $a['due_date'] && strtotime($a['due_date']) < time();
        $dueStr    = $a['due_date'] ? date('d M Y, H:i', strtotime($a['due_date'])) : 'No deadline';
    ?>
    <div class="card">
        <div class="card-body">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:10px">
                <h3 style="font-size:.95rem;font-weight:700;margin:0"><?= htmlspecialchars($a['title']) ?></h3>
                <?php if ($a['timing_status'] === 'upcoming'): ?>
                    <span class="badge badge-info" style="background:#64748b;color:white">🔒 Locked</span>
                <?php elseif ($a['timing_status'] === 'expired'): ?>
                    <span class="badge badge-danger" style="background:#ef4444;color:white">⌛ Expired</span>
                <?php else: ?>
                    <span class="badge badge-success" style="background:#10b981;color:white">✅ Open</span>
                <?php endif; ?>
            </div>
            <div style="font-size:.8rem;color:var(--muted);margin-bottom:12px">
                <div>📖 <?= htmlspecialchars($a['subject']) ?></div>
                <div style="display:grid;grid-template-columns:1fr;gap:4px;margin-top:8px">
                    <div style="color:<?= $a['timing_status']==='upcoming'?'#64748b':'#059669' ?>">
                        📅 <strong>Opens:</strong> <?= date('d M, H:i', strtotime($a['available_from'])) ?>
                    </div>
                    <div style="color:<?= $a['timing_status']==='expired'?'#ef4444':'#64748b' ?>">
                        📅 <strong>Closes:</strong> <?= date('d M, H:i', strtotime($a['available_until'])) ?>
                    </div>
                </div>
            </div>
            <?php if ($a['instructions']): ?>
            <p style="font-size:.82rem;color:#374151;background:#f8fafc;padding:10px;
                      border-radius:8px;margin-bottom:12px;line-height:1.5">
                📋 <?= htmlspecialchars($a['instructions']) ?>
            </p>
            <?php endif; ?>
            <?php if ($a['timing_status'] === 'open'): ?>
            <a href="/CBE_LMS/public/index.php?url=student/takeAssessment&id=<?= $a['id'] ?>"
               class="btn btn-primary w-full" style="justify-content:center;background:var(--primary);border:none">
                ✏️ Start Quiz →
            </a>
            <?php elseif ($a['timing_status'] === 'upcoming'): ?>
            <button class="btn btn-outline w-full" disabled style="justify-content:center;opacity:0.6;cursor:not-allowed">
                🔒 Locked until <?= date('H:i', strtotime($a['available_from'])) ?>
            </button>
            <?php else: ?>
            <button class="btn btn-outline w-full" disabled style="justify-content:center;opacity:0.6;background:#fef2f2;color:#ef4444;border-color:#fee2e2">
                ⌛ Closed
            </button>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- COMPLETED -->
<div class="tab-pane" id="tab-completed">
    <?php if (empty($completed)): ?>
    <div class="card">
        <div class="empty-state">
            <div class="empty-icon">📝</div>
            <h3>No Completed Assessments</h3>
            <p>Complete a quiz above to see your results here.</p>
        </div>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr><th>Title</th><th>Subject</th><th>Score</th><th>%</th><th>Submitted</th><th>Status</th></tr>
                </thead>
                <tbody>
                <?php foreach ($completed as $a): ?>
                <?php
                    $pct        = (float)($a['percentage'] ?? 0);
                    $pctColor   = $pct >= 75 ? '#10b981' : ($pct >= 50 ? '#f59e0b' : '#ef4444');
                    $statusBadge = $a['attempt_status'] === 'graded' ? 'badge-success' : 'badge-pending';
                ?>
                <tr>
                    <td style="font-weight:600"><?= htmlspecialchars($a['title']) ?></td>
                    <td><?= htmlspecialchars($a['subject']) ?></td>
                    <td>
                        <strong><?= (int)$a['score'] ?></strong>/<?= (int)$a['max_score'] ?>
                    </td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px">
                            <div style="width:50px;height:6px;background:#f1f5f9;border-radius:3px;overflow:hidden">
                                <div style="height:100%;width:<?= min(100,$pct) ?>%;background:<?= $pctColor ?>;border-radius:3px"></div>
                            </div>
                            <span style="font-size:.82rem;font-weight:600;color:<?= $pctColor ?>"><?= number_format($pct,1) ?>%</span>
                        </div>
                    </td>
                    <td style="font-size:.8rem;color:var(--muted)">
                        <?= $a['submitted_at'] ? date('d M Y', strtotime($a['submitted_at'])) : '—' ?>
                    </td>
                    <td><span class="badge <?= $statusBadge ?>"><?= ucfirst($a['attempt_status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

</div></div>
<script>
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
        this.classList.add('active');
        document.getElementById('tab-' + this.dataset.tab).classList.add('active');
    });
});
</script>
</body></html>
