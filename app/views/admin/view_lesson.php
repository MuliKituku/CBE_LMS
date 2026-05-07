<?php
/* ── admin/view_lesson.php ─ Lesson Preview and Moderation ── */
$activeNav = 'content';
require BASE_PATH . '/app/views/admin/_sidebar.php';

$lessonId = (int)$lesson['id'];
$type     = $lesson['type'] ?? 'notes';
$url      = htmlspecialchars($lesson['content_url'] ?? '');
$success  = $_GET['success'] ?? '';
$error    = $_GET['error'] ?? '';

$levelConfig = [
    'pre_primary' => ['color'=>'#f59e0b','label'=>'Pre-Primary','icon'=>'🎨','badge_bg'=>'#fef3c7'],
    'primary'     => ['color'=>'#10b981','label'=>'Primary',    'icon'=>'📚','badge_bg'=>'#d1fae5'],
    'junior'      => ['color'=>'#6366f1','label'=>'Junior',     'icon'=>'🔬','badge_bg'=>'#ede9fe'],
    'senior'      => ['color'=>'#ef4444','label'=>'Senior',     'icon'=>'🎓','badge_bg'=>'#fee2e2'],
];
$lc = $levelConfig[$lesson['education_level']] ?? $levelConfig['primary'];
?>

<div class="admin-container" style="max-width: 1000px; margin: 0 auto; padding-bottom: 50px;">
    
    <!-- Top Action Bar -->
    <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <a href="?url=admin/manageContent" class="btn btn-outline" style="display:flex; align-items:center; gap:8px">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Content
        </a>
        <div style="display:flex; gap:12px">
            <?php if ($url): ?>
            <form action="?url=admin/clearMedia" method="POST" onsubmit="return confirm('Are you sure you want to remove this media content?')">
                <input type="hidden" name="lesson_id" value="<?= $lessonId ?>">
                <button type="submit" class="btn btn-outline" style="color:#dc2626; border-color:#fee2e2; background:#fff1f2">
                    🗑️ Delete Media Only
                </button>
            </form>
            <?php endif; ?>
            
            <form action="?url=admin/removeLesson" method="POST" onsubmit="return confirm('Delete entire lesson?')">
                <input type="hidden" name="lesson_id" value="<?= $lessonId ?>">
                <button type="submit" class="btn btn-danger">🗑️ Delete Lesson</button>
            </form>
        </div>
    </div>

    <?php if ($success === 'media_cleared'): ?>
        <div class="alert alert-success">✅ Media content has been removed from the lesson.</div>
    <?php elseif ($success === 'feedback_posted'): ?>
        <div class="alert alert-success">✅ Feedback sent to teacher.</div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger">❌ Error: <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Lesson Header -->
    <div class="card" style="margin-bottom:24px; border-left: 5px solid <?= $lc['color'] ?>">
        <div class="card-body">
            <div style="display:flex; justify-content: space-between; align-items: flex-start">
                <div>
                    <h1 style="font-size:1.5rem; font-weight:800; margin-bottom:8px">
                        <?= htmlspecialchars($lesson['strand']) ?>
                        <?php if ($lesson['sub_strand']): ?>
                        <span style="font-weight:400; color:var(--muted)">/ <?= htmlspecialchars($lesson['sub_strand']) ?></span>
                        <?php endif; ?>
                    </h1>
                    <div style="display:flex; gap:16px; font-size:.85rem; color:var(--muted)">
                        <span><strong>Subject:</strong> <?= htmlspecialchars($lesson['subject']) ?></span>
                        <span><strong>Grade:</strong> <?= htmlspecialchars($lesson['class_grade']) ?></span>
                        <span><strong>Teacher:</strong> <?= htmlspecialchars($lesson['teacher_name']) ?></span>
                    </div>
                </div>
                <span class="badge" style="background:<?= $lc['badge_bg'] ?>; color:<?= $lc['color'] ?>; padding:6px 14px; border-radius:30px; font-weight:700">
                    <?= $lc['icon'] ?> <?= $lc['label'] ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Step 1: Media Preview -->
    <div class="card" style="margin-bottom:24px">
        <div class="card-header" style="background: #f8fafc">
            <h3 style="font-size:1rem; font-weight:700">🎥 1. Media Content Preview</h3>
        </div>
        <div class="card-body">
            <?php if ($url): ?>
                <?php if ($type === 'video'): ?>
                    <?php 
                    $isYoutube = (preg_match('/(?:youtube\.com|youtu\.be)/', $url));
                    if ($isYoutube):
                        $embedUrl = $url;
                        if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $url, $match)) {
                            $embedUrl = "https://www.youtube.com/embed/" . $match[1];
                        }
                    ?>
                        <iframe width="100%" height="450" src="<?= $embedUrl ?>" frameborder="0" allowfullscreen style="border-radius:12px"></iframe>
                    <?php else: ?>
                        <video controls style="width:100%; border-radius:12px; background:#000">
                            <source src="<?= $url ?>" type="video/mp4">
                        </video>
                    <?php endif; ?>
                <?php elseif ($type === 'pdf' || $type === 'slides'): ?>
                    <iframe src="<?= $url ?>" style="width:100%; height:600px; border-radius:12px; border:1px solid #e2e8f0"></iframe>
                <?php elseif ($type === 'image'): ?>
                    <img src="<?= $url ?>" style="max-width:100%; border-radius:12px">
                <?php elseif ($type === 'audio'): ?>
                    <div style="padding:40px; background:#f1f5f9; border-radius:12px; text-align:center">
                        <audio controls><source src="<?= $url ?>"></audio>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">External Link: <a href="<?= $url ?>" target="_blank"><?= $url ?> ↗</a></div>
                <?php endif; ?>
            <?php else: ?>
                <div style="padding:40px; background:#fff1f2; color:#991b1b; border-radius:12px; text-align:center; border: 1px dashed #fecaca">
                    <div style="font-size:2rem; margin-bottom:10px">⚠️</div>
                    <strong>No media content found!</strong><br>The content might have been deleted as inappropriate.
                </div>
            <?php endif; ?>

            <?php if ($lesson['description']): ?>
                <div style="margin-top:20px; padding:16px; background:#f8fafc; border-radius:10px; border-left: 4px solid #cbd5e1">
                    <h4 style="font-size:.85rem; color:#64748b; text-transform:uppercase; margin-bottom:8px">Description / Intro</h4>
                    <p style="margin:0; font-size:.95rem; line-height:1.6"><?= nl2br(htmlspecialchars($lesson['description'])) ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Step 2: Interactions -->
    <?php if ($lesson['interactions']): ?>
    <div class="card" style="margin-bottom:24px">
        <div class="card-header" style="background: #f8fafc">
            <h3 style="font-size:1rem; font-weight:700">🧠 2. Interactive Questions (Answers Highlighted)</h3>
        </div>
        <div class="card-body">
            <div style="display:grid; gap:20px">
                <?php foreach ($lesson['interactions'] as $idx => $inter): ?>
                <div style="padding:20px; border:1px solid #e2e8f0; border-radius:12px; background:#fff">
                    <div style="display:flex; gap:12px; margin-bottom:12px">
                        <span style="background:#4f46e5; color:#fff; width:24px; height:24px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.8rem; font-weight:800 flex-shrink:0">
                            <?= $idx + 1 ?>
                        </span>
                        <div style="font-weight:600"><?= htmlspecialchars($inter['question']) ?></div>
                    </div>

                    <?php if ($inter['interaction_type'] === 'mcq' || $inter['interaction_type'] === 'true_false'): ?>
                        <div style="display:grid; gap:8px; margin-left:36px">
                            <?php 
                            $options = $inter['options_arr'] ?: ($inter['interaction_type'] === 'true_false' ? [['id'=>'true','text'=>'True'],['id'=>'false','text'=>'False']] : []);
                            foreach ($options as $opt): 
                                $isCorrect = (strtolower($opt['id']) === strtolower($inter['correct_answer']));
                            ?>
                                <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 16px; border-radius:8px; border: 1px solid <?= $isCorrect ? '#10b981' : '#e2e8f0' ?>; background: <?= $isCorrect ? '#f0fdf4' : '#fff' ?>">
                                    <span style="<?= $isCorrect ? 'font-weight:700; color:#166534' : '' ?>">
                                        <?= strtoupper($opt['id']) ?>. <?= htmlspecialchars($opt['text']) ?>
                                    </span>
                                    <?php if ($isCorrect): ?>
                                        <span style="font-size:.7rem; background:#10b981; color:#fff; padding:2px 8px; border-radius:20px; text-transform:uppercase; font-weight:800">Correct Answer</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="margin-left:36px; padding:10px 16px; background:#f0fdf4; border: 1px dashed #10b981; border-radius:8px; display:inline-block">
                            <span style="color:#166534; font-size:.9rem"><strong>Expected Answer:</strong> <?= htmlspecialchars($inter['correct_answer']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Step 3: Activity -->
    <?php if ($lesson['activity']): ?>
    <div class="card" style="margin-bottom:24px">
        <div class="card-header" style="background: #f8fafc">
            <h3 style="font-size:1rem; font-weight:700">📝 3. Student Activity Details</h3>
        </div>
        <div class="card-body">
            <div style="padding:24px; background:#fff7ed; border-radius:12px; border: 2px solid #ffedd5">
                <h4 style="margin-top:0; font-size:1.1rem; color:#9a3412"><?= htmlspecialchars($lesson['activity']['title']) ?></h4>
                <p style="margin-bottom:15px; color:#431407; line-height:1.6"><?= nl2br(htmlspecialchars($lesson['activity']['description'])) ?></p>
                <div style="font-size:.85rem; color:#9a3412">
                    <strong>Submission Type:</strong> <?= ucfirst($lesson['activity']['submission_type']) ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Feedback & Discussions Section -->
    <div style="display:grid; grid-template-columns: 1fr 350px; gap:24px">
        
        <!-- Private Feedback (Between Admin & Teacher) -->
        <div class="card">
            <div class="card-header">
                <h3 style="font-size:1rem; font-weight:700">💬 Feedback for Teacher</h3>
            </div>
            <div class="card-body">
                <div style="max-height: 400px; overflow-y: auto; margin-bottom: 20px; padding: 10px">
                    <?php if (empty($feedback)): ?>
                        <div style="text-align:center; padding:30px; color:var(--muted)">No feedback sent yet.</div>
                    <?php else: ?>
                        <?php foreach ($feedback as $fb): ?>
                            <div style="margin-bottom: 16px; display:flex; flex-direction: column; <?= $fb['role'] === 'admin' ? 'align-items: flex-end' : 'align-items: flex-start' ?>">
                                <div style="max-width: 85%; padding: 12px 16px; border-radius: 12px; background: <?= $fb['role'] === 'admin' ? '#4f46e5; color:#fff' : '#f1f5f9' ?>; box-shadow: 0 2px 4px rgba(0,0,0,0.05)">
                                    <div style="font-size: .75rem; margin-bottom: 4px; opacity: 0.8; font-weight: 700">
                                        <?= htmlspecialchars($fb['fullname']) ?> (<?= ucfirst($fb['role']) ?>)
                                    </div>
                                    <div style="font-size: .95rem"><?= nl2br(htmlspecialchars($fb['message'])) ?></div>
                                    <div style="font-size: .65rem; margin-top: 6px; text-align: right; opacity: 0.8">
                                        <?= date('H:i, d M', strtotime($fb['created_at'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <form action="?url=admin/postLessonFeedback" method="POST">
                    <input type="hidden" name="lesson_id" value="<?= $lessonId ?>">
                    <textarea name="message" class="form-control" placeholder="Leave descriptive feedback for the teacher here..." required style="min-height: 100px; margin-bottom: 12px"></textarea>
                    <button type="submit" class="btn btn-primary" style="width: 100%">Send Feedback to Teacher</button>
                    <p style="font-size:.75rem; color:var(--muted); margin-top:8px; text-align:center">
                        Teacher will be notified and can reply.
                    </p>
                </form>
            </div>
        </div>

        <!-- Public Discussions (Preview) -->
        <div class="card">
            <div class="card-header">
                <h3 style="font-size:1rem; font-weight:700">🌍 Student Discussions</h3>
            </div>
            <div class="card-body" style="padding:0">
                <?php if (empty($lesson['discussions'])): ?>
                    <div style="padding:40px; text-align:center; color:var(--muted)">No student comments yet.</div>
                <?php else: ?>
                    <div style="max-height: 600px; overflow-y: auto;">
                        <?php foreach ($lesson['discussions'] as $disc): ?>
                            <div style="padding:15px; border-bottom: 1px solid #f1f5f9">
                                <div style="display:flex; gap:10px; margin-bottom:6px">
                                    <div style="width:30px; height:30px; border-radius:50%; background:#e2e8f0; display:flex; align-items:center; justify-content:center; font-size:.8rem">👤</div>
                                    <div>
                                        <div style="font-size: .85rem; font-weight:700"><?= htmlspecialchars($disc['fullname']) ?></div>
                                        <div style="font-size: .7rem; color:var(--muted)"><?= date('d M', strtotime($disc['created_at'])) ?></div>
                                    </div>
                                </div>
                                <div style="font-size: .88rem; color:#475569; line-height:1.5"><?= htmlspecialchars($disc['message']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

</div>

<style>
.admin-container { padding: 20px; color: var(--text); }
.card { border-radius: 12px; border: 1px solid #e2e8f0; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; }
.card-header { padding: 15px 20px; border-bottom: 1px solid #e2e8f0; }
.card-body { padding: 20px; }
.btn { padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; border: 1px solid transparent; transition: all .2s; }
.btn-outline { border-color: #e2e8f0; background: #fff; color: #475569; }
.btn-outline:hover { background: #f8fafc; }
.btn-primary { background: #4f46e5; color: #fff; }
.btn-danger { background: #fee2e2; color: #dc2626; border-color: #fecaca; }
.form-control { width: 100%; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; font-family: inherit; }
.alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-weight: 600; }
.alert-success { background: #d1fae5; color: #065f46; }
.alert-danger { background: #fee2e2; color: #991b1b; }
.badge { font-size: .75rem; padding: 4px 10px; border-radius: 20px; }
</style>
