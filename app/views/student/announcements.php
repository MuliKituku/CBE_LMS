<?php
/* ── student/announcements.php ──────────────────────────────
   Variables: $profile, $announcements
   ─────────────────────────────────────────────────────────── */
$activeNav = 'school_announcements';
require BASE_PATH . '/app/views/student/_sidebar.php';
?>

<div class="card" style="margin-bottom:24px;background:linear-gradient(135deg,#0d9488 0%,#0891b2 100%);color:#fff;border:none;">
    <div class="card-body" style="padding:40px;text-align:center">
        <h2 style="color:#fff;margin-bottom:10px">📢 School Announcements</h2>
        <p style="opacity:.9">Stay updated with the latest news and notifications from the school administration.</p>
    </div>
</div>

<?php if (empty($announcements)): ?>
    <div class="card">
        <div class="card-body" style="padding:60px;text-align:center">
            <div style="font-size:4rem;margin-bottom:20px">📭</div>
            <h3 style="color:var(--text);margin-bottom:10px">No Announcements Yet</h3>
            <p style="color:var(--muted)">Check back later for official school updates.</p>
        </div>
    </div>
<?php else: ?>
    <div style="display:grid;gap:20px">
        <?php foreach ($announcements as $ann): ?>
            <div class="card" style="border-left:5px solid var(--primary)">
                <div class="card-header" style="display:flex;justify-content:space-between;align-items:flex-start">
                    <h3 style="color:var(--text);font-size:1.2rem"><?= htmlspecialchars($ann['title']) ?></h3>
                    <span class="badge badge-primary" style="font-size:.75rem"><?= date('d M Y', strtotime($ann['created_at'])) ?></span>
                </div>
                <div class="card-body">
                    <p style="line-height:1.6;color:#475569;margin-bottom:15px"><?= nl2br(htmlspecialchars($ann['message'])) ?></p>
                    <div style="display:flex;align-items:center;gap:10px;padding-top:15px;border-top:1px solid #f1f5f9;font-size:0.85rem;color:var(--muted)">
                        <div style="width:32px;height:32px;border-radius:50%;background:#f1f5f9;display:flex;align-items:center;justify-content:center">👤</div>
                        <span>Posted by <strong><?= htmlspecialchars($ann['author']) ?></strong></span>
                        <span style="margin-left:auto"><?= date('H:i', strtotime($ann['created_at'])) ?></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

</div></div>
</body></html>
