<?php
/* ── teacher/view_discussions.php ─ View and respond to student discussions
   Variables: $profile, $lesson, $discussions
   ─────────────────────────────────────────────────────────────────── */
$activeNav = 'lessons';
require BASE_PATH . '/app/views/teacher/_sidebar.php';

$success = $_GET['success'] ?? '';
$lessonId = (int)$lesson['id'];
?>

<div style="margin-bottom:16px">
    <a href="/CBE_LMS/public/index.php?url=teacher/lessons" class="btn btn-outline btn-sm">← Back to Lessons</a>
</div>

<?php if ($success === 'posted'): ?>
<div class="alert alert-success">✅ Comment posted!</div>
<?php endif; ?>

<!-- ══════════════ LESSON HEADER ══════════════ -->
<div class="card" style="margin-bottom:20px">
    <div class="card-header">
        <div>
            <h2 style="font-size:1.15rem;font-weight:700;margin-bottom:4px">
                <?= htmlspecialchars($lesson['strand']) ?>
                <?php if (!empty($lesson['sub_strand'])): ?>
                    <span style="font-weight:400;color:var(--muted);font-size:.95rem">/ <?= htmlspecialchars($lesson['sub_strand']) ?></span>
                <?php endif; ?>
            </h2>
            <div style="display:flex;gap:10px;flex-wrap:wrap;font-size:.8rem;color:var(--muted)">
                <span>📖 <?= htmlspecialchars($lesson['subject']) ?></span>
                <span>🏫 <?= htmlspecialchars($lesson['class_grade']) ?></span>
                <span>📅 <?= date('d M Y', strtotime($lesson['created_at'])) ?></span>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════ DISCUSSIONS ══════════════ -->
<div class="card" id="discussions">
    <div class="card-header"><h3>🗣 Discussion (<?= count($discussions) ?> posts)</h3></div>
    <div class="card-body">
        <?php if (empty($discussions)): ?>
        <p style="color:var(--muted);font-size:.9rem">No discussion posts yet.</p>
        <?php endif; ?>
        <?php foreach ($discussions as $post): ?>
        <div style="display:flex;gap:12px;margin-bottom:16px">
            <div style="width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#0d9488,#6366f1);
                        display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.8rem;flex-shrink:0">
                <?= strtoupper(substr($post['fullname'],0,2)) ?>
            </div>
            <div style="flex:1">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
                    <span style="font-weight:600;font-size:.875rem"><?= htmlspecialchars($post['fullname']) ?></span>
                    <span class="badge badge-<?= $post['role']==='teacher'?'success':'info' ?>" style="font-size:.65rem"><?= ucfirst($post['role']) ?></span>
                    <span style="font-size:.75rem;color:var(--muted)"><?= date('d M Y H:i', strtotime($post['created_at'])) ?></span>
                </div>
                <p style="font-size:.875rem;color:var(--text);margin:0 0 8px;line-height:1.5"><?= nl2br(htmlspecialchars($post['message'])) ?></p>
                <?php foreach ($post['replies'] as $reply): ?>
                <div style="display:flex;gap:10px;margin-left:20px;margin-bottom:8px;padding:10px 14px;background:#f8fafc;border-radius:8px;border-left:3px solid #0d9488">
                    <div style="width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#8b5cf6);
                                display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.72rem;flex-shrink:0">
                        <?= strtoupper(substr($reply['fullname'],0,2)) ?>
                    </div>
                    <div>
                        <span style="font-weight:600;font-size:.82rem"><?= htmlspecialchars($reply['fullname']) ?></span>
                        <span class="badge badge-<?= $reply['role']==='teacher'?'success':'info' ?>" style="font-size:.6rem;margin:0 6px"><?= ucfirst($reply['role']) ?></span>
                        <p style="font-size:.85rem;margin:4px 0 0"><?= nl2br(htmlspecialchars($reply['message'])) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
                <button onclick="toggleReply(<?= $post['id'] ?>)" class="btn btn-outline btn-sm" style="margin-top:4px">↩ Reply</button>
                <div id="reply-<?= $post['id'] ?>" style="display:none;margin-top:10px">
                    <form method="POST" action="/CBE_LMS/public/index.php?url=teacher/postDiscussion">
                        <input type="hidden" name="lesson_id" value="<?= $lessonId ?>">
                        <input type="hidden" name="parent_id" value="<?= $post['id'] ?>">
                        <textarea name="message" class="form-control" rows="2" placeholder="Write a reply…" required style="margin-bottom:8px"></textarea>
                        <button type="submit" class="btn btn-primary btn-sm">Post Reply</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <div style="border-top:1px solid var(--border);padding-top:16px;margin-top:8px">
            <h4 style="font-size:.9rem;font-weight:700;margin-bottom:12px">✏️ Start a Discussion Thread</h4>
            <form method="POST" action="/CBE_LMS/public/index.php?url=teacher/postDiscussion">
                <input type="hidden" name="lesson_id" value="<?= $lessonId ?>">
                <input type="hidden" name="parent_id" value="">
                <textarea name="message" class="form-control" rows="3" placeholder="Share a thought with your students…" required style="margin-bottom:10px"></textarea>
                <button type="submit" class="btn btn-primary">Post Comment 💬</button>
            </form>
        </div>
    </div>
</div>

</div></div>
<script>
function toggleReply(id) {
    const el = document.getElementById('reply-' + id);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>
</body></html>
