<?php
/* ── student/feedback.php ──────────────────────────────────
   Variables: $profile, $feedback (array)
   ─────────────────────────────────────────────────────────── */
$activeNav = 'feedback';
require BASE_PATH . '/app/views/student/_sidebar.php';
?>

<div class="card" style="margin-bottom:24px;border:none;background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff">
    <div class="card-body" style="display:flex;align-items:center;gap:20px">
        <div style="font-size:3rem;line-height:1">💬</div>
        <div>
            <h2 style="color:#fff;margin-bottom:6px">Teacher Feedback</h2>
            <p style="color:rgba(255,255,255,.9);margin:0;font-size:.9rem">
                Direct messages and performance reviews from your teachers.
            </p>
        </div>
    </div>
</div>

<?php if (empty($feedback)): ?>
<div class="card">
    <div class="empty-state">
        <div class="empty-icon">📭</div>
        <h3>No Feedback Yet</h3>
        <p>You haven't received any direct feedback from your teachers yet. Keep up the good work!</p>
    </div>
</div>
<?php else: ?>

<div style="display:grid;grid-template-columns:1fr;gap:16px">
    <?php foreach ($feedback as $f): ?>
    <div class="card" style="border-left:4px solid #f59e0b">
        <div class="card-body">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px">
                <div style="display:flex;align-items:center;gap:10px">
                    <div style="width:40px;height:40px;border-radius:50%;background:#fef3c7;color:#d97706;
                                display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0">
                        <?= strtoupper(substr($f['teacher_name'],0,2)) ?>
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:.95rem"><?= htmlspecialchars($f['teacher_name']) ?></div>
                        <div style="font-size:.75rem;color:var(--muted)">
                            <?= htmlspecialchars($f['subject'] ?: 'General Feedback') ?>
                        </div>
                    </div>
                </div>
                <div style="font-size:.8rem;color:var(--muted);text-align:right">
                    📅 <?= date('d M Y', strtotime($f['created_at'])) ?><br>
                    <span style="font-size:.7rem"><?= date('H:i', strtotime($f['created_at'])) ?></span>
                </div>
            </div>
            
            <div style="background:#f8fafc;padding:16px;border-radius:10px;color:#334155;font-size:.9rem;line-height:1.6">
                <?= nl2br(htmlspecialchars($f['message'])) ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php endif; ?>

</div></div>
</body></html>
