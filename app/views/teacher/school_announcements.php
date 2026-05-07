<?php
/* ── teacher/school_announcements.php ──────────────────────
   Variables: $profile, $announcements
   ─────────────────────────────────────────────────────────── */
$activeNav = 'school_announcements';
require BASE_PATH . '/app/views/teacher/_sidebar.php';
?>

<div class="card" style="margin-bottom:24px;background:linear-gradient(135deg,#10b981 0%,#059669 100%);color:#fff;border:none;">
    <div class="card-body" style="padding:40px;text-align:center">
        <h2 style="color:#fff;margin-bottom:10px">🏫 School-Wide Announcements</h2>
        <p style="opacity:.9">Important notices and updates from the school administration for the teaching staff.</p>
    </div>
</div>

<?php if (empty($announcements)): ?>
    <div class="card">
        <div class="card-body" style="padding:60px;text-align:center">
            <div style="font-size:4rem;margin-bottom:20px">📡</div>
            <h3 style="color:var(--text);margin-bottom:10px">All Quiet!</h3>
            <p style="color:var(--muted)">No central announcements have been posted for your role yet.</p>
        </div>
    </div>
<?php else: ?>
    <div style="display:grid;gap:24px">
        <?php foreach ($announcements as $ann): ?>
            <div class="card" style="border-top:5px solid #10b981">
                <div class="card-header" style="display:flex;justify-content:space-between;align-items:baseline">
                    <h3 style="font-size:1.3rem;color:#111827"><?= htmlspecialchars($ann['title']) ?></h3>
                    <div style="text-align:right">
                        <div style="font-weight:700;color:#10b981"><?= date('d F', strtotime($ann['created_at'])) ?></div>
                        <div style="font-size:.75rem;color:var(--muted)"><?= date('Y, H:i', strtotime($ann['created_at'])) ?></div>
                    </div>
                </div>
                <div class="card-body">
                    <p style="font-size:1rem;line-height:1.7;color:#374151;margin-bottom:20px"><?= nl2br(htmlspecialchars($ann['message'])) ?></p>
                    <div style="display:flex;align-items:center;background:#f8fafc;padding:12px 20px;border-radius:8px">
                        <div style="font-size:0.85rem;color:#64748b">
                            Official notice from <strong><?= htmlspecialchars($ann['author']) ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

</div></div>
</body></html>
