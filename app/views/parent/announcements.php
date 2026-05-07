<?php
/* ── parent/announcements.php ──────────────────────────────
   Variables: $profile, $announcements
   ─────────────────────────────────────────────────────────── */
$activeNav = 'school_announcements';
require BASE_PATH . '/app/views/parent/_sidebar.php';
?>

<div class="card" style="margin-bottom:30px;background:linear-gradient(135deg,#6366f1 0%,#4f46e5 100%);color:#fff;border:none;">
    <div class="card-body" style="padding:40px;text-align:center">
        <h2 style="color:#fff;margin-bottom:10px;font-weight:800">🏛️ Official School Notices</h2>
        <p style="opacity:.85;font-size:1.1rem">Keep track of school holidays, events, and key administrative updates.</p>
    </div>
</div>

<?php if (empty($announcements)): ?>
    <div class="card">
        <div class="card-body" style="padding:80px;text-align:center">
            <div style="font-size:4rem;margin-bottom:20px">📣</div>
            <h3 style="color:var(--text);margin-bottom:10px;font-weight:700">No School Notices</h3>
            <p style="color:var(--muted)">Official announcements will appear here when posted by the administration.</p>
        </div>
    </div>
<?php else: ?>
    <div style="display:flex;flex-direction:column;gap:24px">
        <?php foreach ($announcements as $ann): ?>
            <div class="card" style="border:none;box-shadow:0 4px 15px rgba(0,0,0,0.05)">
                <div class="card-header" style="background:#fff;border-bottom:1px solid #f1f5f9;padding:20px 24px;display:flex;justify-content:space-between;align-items:center">
                    <h3 style="margin:0;font-size:1.25rem;color:#1e1b4b;font-weight:700"><?= htmlspecialchars($ann['title']) ?></h3>
                    <div class="badge badge-primary" style="background:#eef2ff;color:#4f46e5;font-weight:700">
                        <?= date('M d, Y', strtotime($ann['created_at'])) ?>
                    </div>
                </div>
                <div class="card-body" style="padding:24px">
                    <p style="font-size:1.05rem;line-height:1.7;color:#334155;margin-bottom:24px">
                        <?= nl2br(htmlspecialchars($ann['message'])) ?>
                    </p>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding-top:20px;border-top:1px dashed #e2e8f0;font-size:0.8rem;color:#64748b">
                        <span style="display:flex;align-items:center;gap:6px">
                            <span style="font-size:1.2rem">🏛️</span> 
                            By: <strong><?= htmlspecialchars($ann['author']) ?></strong> (Administration)
                        </span>
                        <span><?= date('H:i', strtotime($ann['created_at'])) ?></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

</div></div>
</body></html>
