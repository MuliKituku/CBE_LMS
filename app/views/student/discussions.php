<?php
/* -- student/discussions.php --
   Variables: $profile, $lesson, $discussions (array), $lessonId, $allLessons (array)
   ------------------------------- */
$activeNav = 'discussions';
require BASE_PATH . '/app/views/student/_sidebar.php';

$error = $_GET['error'] ?? '';
?>

<div style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start">
    
    <!-- LEFT COLUMN: Conversation -->
    <div class="discussion-main">
        <?php if ($error === 'empty'): ?>
        <div class="alert alert-error">Message cannot be empty.</div>
        <?php endif; ?>

        <?php if (!$lessonId): ?>
        <div class="card">
            <div class="empty-state">
                <div class="empty-icon">Select</div>
                <h3>Select a Lesson</h3>
                <p>Choose a lesson from the sidebar to view or join its discussion thread.</p>
            </div>
        </div>
        <?php else: ?>
        
        <!-- Thread Status Card -->
        <div class="card" style="margin-bottom:20px;border-left:4px solid #6366f1">
            <div class="card-body" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;padding:16px 24px">
                <div>
                    <h3 style="font-size:1.1rem;margin:0 0 4px">Thread: <?= htmlspecialchars($lesson['strand'] ?? 'Unknown Lesson') ?></h3>
                    <div style="font-size:.85rem;color:var(--muted)">
                        <?= count($discussions) ?> top-level post(s)
                    </div>
                </div>
                <div style="display:flex;gap:10px">
                    <a href="/CBE_LMS/public/index.php?url=student/lesson&id=<?= (int)$lessonId ?>" class="btn btn-outline btn-sm">
                        Open Lesson
                    </a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <?php if (empty($discussions)): ?>
                <div style="text-align:center;padding:40px 20px;color:var(--muted)">
                    <div style="font-size:2.5rem;margin-bottom:12px">Chat</div>
                    <p>No discussion posts yet. Be the first to ask a question!</p>
                </div>
                <?php endif; ?>

                <?php foreach ($discussions as $post): ?>
                <div class="post-item" style="display:flex;gap:15px;margin-bottom:24px;padding-bottom:20px;border-bottom:1px solid #f1f5f9">
                    <div class="post-avatar" style="width:45px;height:45px;border-radius:12px;background:linear-gradient(135deg,#6366f1,#4f46e5);
                                display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;
                                font-size:.95rem;flex-shrink:0;box-shadow:0 4px 10px rgba(99,102,241,0.2)">
                        <?= strtoupper(substr($post['fullname'],0,2)) ?>
                    </div>
                    <div style="flex:1">
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
                            <span style="font-weight:700;font-size:1rem;color:var(--text)"><?= htmlspecialchars($post['fullname']) ?></span>
                            <?php if ($post['role']==='teacher'): ?>
                                <span class="badge badge-success" style="font-size:.65rem;border-radius:4px">TEACHER</span>
                            <?php endif; ?>
                            <span style="font-size:.8rem;color:var(--muted)">
                                <?= date('d M Y, H:i', strtotime($post['created_at'])) ?>
                            </span>
                        </div>
                        <p style="font-size:.92rem;color:#334155;margin:0 0 12px;line-height:1.6">
                            <?= nl2br(htmlspecialchars($post['message'])) ?>
                        </p>

                        <!-- Replies -->
                        <?php if (!empty($post['replies'])): ?>
                        <div style="background:#f8fafc;border-radius:10px;padding:15px;margin-bottom:15px;border-left:3px solid #6366f1">
                            <?php foreach ($post['replies'] as $reply): ?>
                            <div style="display:flex;gap:12px;margin-bottom:15px">
                                <div style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#0d9488,#0891b2);
                                            display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;
                                            font-size:.75rem;flex-shrink:0">
                                    <?= strtoupper(substr($reply['fullname'],0,2)) ?>
                                </div>
                                <div>
                                    <div style="display:flex;align-items:center;gap:6px">
                                        <span style="font-weight:600;font-size:.88rem;color:var(--text)"><?= htmlspecialchars($reply['fullname']) ?></span>
                                        <?php if ($reply['role']==='teacher'): ?>
                                            <span class="badge badge-success" style="font-size:.6rem;border-radius:4px">TEACHER</span>
                                        <?php endif; ?>
                                        <span style="font-size:.75rem;color:var(--muted)"><?= date('d M, H:i', strtotime($reply['created_at'])) ?></span>
                                    </div>
                                    <p style="font-size:.88rem;margin:4px 0 0;color:#475569;line-height:1.5">
                                        <?= nl2br(htmlspecialchars($reply['message'])) ?>
                                    </p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Reply action -->
                        <button onclick="toggleReply(<?= $post['id'] ?>)" class="btn btn-outline btn-sm" style="border-radius:20px;padding:4px 12px">
                            Reply
                        </button>
                        <div id="reply-<?= $post['id'] ?>" style="display:none;margin-top:12px">
                            <form method="POST" action="/CBE_LMS/public/index.php?url=student/postDiscussion">
                                <input type="hidden" name="lesson_id"  value="<?= (int)$lessonId ?>">
                                <input type="hidden" name="parent_id"  value="<?= (int)$post['id'] ?>">
                                <textarea name="message" class="form-control" rows="2"
                                          placeholder="Write a reply..." required style="margin-bottom:8px;border-radius:10px"></textarea>
                                <button type="submit" class="btn btn-primary btn-sm">Post Reply</button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- New top-level post -->
                <div style="background:#f0fdfa;border:1px solid #99f6e4;padding:24px;border-radius:16px;margin-top:20px">
                    <h4 style="font-size:1rem;font-weight:700;margin-bottom:12px;color:#0f766e">Ask a New Question</h4>
                    <form method="POST" action="/CBE_LMS/public/index.php?url=student/postDiscussion">
                        <input type="hidden" name="lesson_id" value="<?= (int)$lessonId ?>">
                        <input type="hidden" name="parent_id" value="">
                        <textarea name="message" class="form-control" rows="3"
                                  placeholder="What would you like to discuss about this lesson?"
                                  required style="margin-bottom:12px;border-color:#ccfbf1;border-radius:12px"></textarea>
                        <button type="submit" class="btn btn-primary" style="box-shadow:0 4px 12px rgba(13,148,136,0.2)">Post to Thread</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- RIGHT COLUMN: Sidebar -->
    <div class="discussion-sidebar">
        <!-- Forum Info Card -->
        <div class="card" style="margin-bottom:24px;border:none;background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;box-shadow:0 10px 25px rgba(99,102,241,0.3)">
            <div class="card-body" style="padding:24px">
                <div style="font-size:2.5rem;margin-bottom:12px">Talk</div>
                <h2 style="color:#fff;margin-bottom:10px;font-size:1.25rem">Lesson Forums</h2>
                <p style="color:rgba(255,255,255,.9);margin-bottom:20px;font-size:.88rem;line-height:1.5">
                    Ask questions, share insights, and engage with your teachers and fellow students.
                </p>
                
                <?php if (!empty($allLessons)): ?>
                <div style="background:rgba(255,255,255,.1);padding:15px;border-radius:12px;border:1px solid rgba(255,255,255,.2)">
                    <form method="GET" action="/CBE_LMS/public/index.php">
                        <input type="hidden" name="url" value="student/discussions">
                        <label style="font-size:.7rem;color:#e0e7ff;margin-bottom:8px;font-weight:700;display:block;text-transform:uppercase">Jump to Lesson</label>
                        <select name="lesson_id" class="form-control" style="background:#fff;color:var(--text);border:none" onchange="this.form.submit()">
                            <option value="">-- Choose Lesson --</option>
                            <?php foreach ($allLessons as $l): ?>
                            <option value="<?= $l['id'] ?>" <?= $lessonId === (int)$l['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($l['strand']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Community Guidelines Card -->
        <div class="card">
            <div class="card-header"><h3>Guidelines</h3></div>
            <div class="card-body" style="padding:16px;font-size:.8rem;color:var(--muted);line-height:1.6">
                <ul style="list-style:none;padding:0">
                    <li style="margin-bottom:12px;display:flex;gap:8px"><span>*</span> <span>Be respectful to everyone.</span></li>
                    <li style="margin-bottom:12px;display:flex;gap:8px"><span>*</span> <span>Search before asking.</span></li>
                    <li style="margin-bottom:12px;display:flex;gap:8px"><span>*</span> <span>Share helpful insights.</span></li>
                    <li style="display:flex;gap:8px"><span>*</span> <span>No spam or off-topic posts.</span></li>
                </ul>
            </div>
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
