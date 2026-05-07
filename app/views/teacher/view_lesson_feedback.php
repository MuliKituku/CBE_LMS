<?php
/* ── teacher/view_lesson_feedback.php ─ Admin Moderation Feedback ── */
$activeNav = 'lessons';
require BASE_PATH . '/app/views/teacher/_sidebar.php';

$lessonId = (int)$lesson['id'];
$success  = $_GET['success'] ?? '';
$error    = $_GET['error'] ?? '';
?>

<div class="content-container" style="max-width: 800px; margin: 0 auto; padding: 24px;">
    
    <div style="margin-bottom: 24px;">
        <a href="?url=teacher/lessons" class="btn btn-outline btn-sm">← Back to Lessons</a>
    </div>

    <div class="card" style="margin-bottom: 24px; border-left: 5px solid #4f46e5;">
        <div class="card-body">
            <h1 style="font-size:1.25rem; font-weight:800; margin-bottom:4px">
                Admin Feedback: <?= htmlspecialchars($lesson['strand']) ?>
            </h1>
            <p style="color:var(--muted); font-size:.85rem; margin:0">
                <?= htmlspecialchars($lesson['subject']) ?> — <?= htmlspecialchars($lesson['class_grade']) ?>
            </p>
        </div>
    </div>

    <?php if ($success === 'feedback_posted'): ?>
        <div class="alert alert-success">✅ Reply sent to admin.</div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header" style="background:#f8fafc">
            <h3 style="font-size:1rem; font-weight:700">💬 Feedback History</h3>
        </div>
        <div class="card-body">
            <div style="max-height: 500px; overflow-y: auto; margin-bottom: 24px; padding-right: 10px;">
                <?php if (empty($feedback)): ?>
                    <div style="text-align:center; padding:40px; color:var(--muted)">
                        No feedback history yet for this lesson.
                    </div>
                <?php else: ?>
                    <?php foreach ($feedback as $fb): ?>
                        <div style="margin-bottom: 20px; display:flex; flex-direction: column; <?= $fb['role'] === 'teacher' ? 'align-items: flex-end' : 'align-items: flex-start' ?>">
                            <div style="max-width: 85%; padding: 14px 18px; border-radius: 12px; background: <?= $fb['role'] === 'teacher' ? 'var(--primary); color:#fff' : '#f1f5f9' ?>; box-shadow: 0 1px 2px rgba(0,0,0,0.05)">
                                <div style="font-size: .75rem; margin-bottom: 6px; opacity: 0.8; font-weight: 700">
                                    <?= htmlspecialchars($fb['fullname']) ?> (<?= ucfirst($fb['role']) ?>)
                                </div>
                                <div style="font-size: .95rem; line-height:1.5"><?= nl2br(htmlspecialchars($fb['message'])) ?></div>
                                <div style="font-size: .65rem; margin-top: 8px; text-align: right; opacity: 0.8">
                                    <?= date('H:i, d M Y', strtotime($fb['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <form action="?url=teacher/postLessonFeedback" method="POST" style="border-top: 1px solid #e2e8f0; pt-20">
                <input type="hidden" name="lesson_id" value="<?= $lessonId ?>">
                <label style="display:block; font-size:.85rem; font-weight:700; color:var(--muted); margin-bottom:8px">Reply to Admin</label>
                <textarea name="message" class="form-control" placeholder="Write your response to the admin..." required style="min-height: 100px; margin-bottom: 12px; border-radius: 12px; padding: 15px; border-color:#e2e8f0;"></textarea>
                <div style="display:flex; justify-content: flex-end">
                    <button type="submit" class="btn btn-primary" style="padding:10px 30px; border-radius:30px">Send Reply</button>
                </div>
            </form>
        </div>
    </div>

</div>

<style>
.card { border-radius: 16px; border: 1px solid #e2e8f0; background: #fff; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); overflow: hidden; }
.card-header { padding: 16px 24px; border-bottom: 1px solid #e2e8f0; }
.card-body { padding: 24px; }
.btn-outline { border: 1px solid #e2e8f0; color: #64748b; background: transparent; padding: 8px 16px; border-radius: 8px; text-decoration: none; display: inline-block; font-size: .85rem; }
.btn-primary { background: var(--primary); color: #fff; border: none; font-weight: 600; cursor: pointer; transition: opacity .2s; }
.btn-primary:hover { opacity: 0.9; }
.form-control { width: 100%; font-family: inherit; }
.alert-success { padding: 12px 20px; background: #d1fae5; color: #065f46; border-radius: 12px; margin-bottom: 20px; font-weight: 600; }
</style>
