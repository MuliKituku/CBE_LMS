<?php
/* ── teacher/review_lesson.php ─────────────────────────────
   Variables: $profile, $studentId, $lessonId, $submissionData
   ────────────────────────────────────────────────────────── */
$activeNav = 'grading';
require BASE_PATH . '/app/views/teacher/_sidebar.php';

$lesson    = $submissionData['lesson'];
$student   = $submissionData['student_name'];
$interns   = $submissionData['interactions'];
$activity  = $submissionData['activity'];
$progress  = $submissionData['progress'];
?>

<div style="margin-bottom:24px">
    <a href="/CBE_LMS/public/index.php?url=teacher/grading" style="color:var(--primary);text-decoration:none;font-weight:600">← Back to Pending List</a>
</div>

<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px">
    <div>
        <h2 style="color:var(--text);margin:0">Review Lesson Progress</h2>
        <p style="color:var(--muted);margin:8px 0 0 0">
            Student: <strong><?= htmlspecialchars($student) ?></strong> | 
            Lesson: <strong><?= htmlspecialchars($lesson['strand'] ?? 'Unknown') ?></strong>
        </p>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 350px;gap:24px;align-items:start">
    <div style="display:flex;flex-direction:column;gap:24px">
        
        <!-- STEP 2: Interactions -->
        <div class="card" style="padding:24px">
            <h3 style="margin-top:0;display:flex;align-items:center;gap:10px">
                <span style="background:var(--primary);color:#fff;width:28px;height:28px;border-radius:50%;display:flex;justify-content:center;align-items:center;font-size:0.9rem">2</span>
                Lesson Interactions
            </h3>
            <div style="display:flex;flex-direction:column;gap:16px;margin-top:16px">
                <?php if (empty($interns)): ?>
                    <p style="color:var(--muted)">No interactions for this lesson.</p>
                <?php else: ?>
                    <?php foreach ($interns as $i): ?>
                    <div style="padding:16px;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc">
                        <div style="font-weight:600;margin-bottom:8px"><?= htmlspecialchars($i['question']) ?></div>
                        <div style="display:flex;justify-content:space-between;align-items:center">
                            <div style="font-size:0.9rem">
                                Student Answer: <span style="font-weight:700;color:<?= $i['is_correct'] ? '#16a34a' : '#dc2626' ?>"><?= htmlspecialchars($i['answer_given'] ?: 'No answer') ?></span>
                            </div>
                            <div style="font-size:0.8rem;color:var(--muted)">
                                Correct: <?= htmlspecialchars($i['correct_answer']) ?> | Marks: <?= (int)$i['marks'] ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- STEP 3: Activity Submission -->
        <div class="card" style="padding:24px">
            <h3 style="margin-top:0;display:flex;align-items:center;gap:10px">
                <span style="background:#0ea5e9;color:#fff;width:28px;height:28px;border-radius:50%;display:flex;justify-content:center;align-items:center;font-size:0.9rem">3</span>
                Activity Submission
            </h3>
            <?php if (!$activity): ?>
                <p style="color:var(--muted);margin-top:16px">No activity submission found.</p>
            <?php else: ?>
                <div style="margin-top:16px;padding:20px;background:#f0f9ff;border:1px solid #bae6fd;border-radius:12px">
                    <h4 style="margin:0 0 8px 0;color:#0369a1"><?= htmlspecialchars($activity['title']) ?></h4>
                    <p style="font-size:0.9rem;color:#0c4a6e;margin-bottom:16px"><?= nl2br(htmlspecialchars($activity['description'])) ?></p>
                    
                    <div style="background:#fff;padding:16px;border-radius:8px;border:1px solid #e0f2fe">
                        <div style="font-size:0.8rem;color:#64748b;margin-bottom:8px;text-transform:uppercase;letter-spacing:0.05em">Student Response:</div>
                        <div style="white-space:pre-wrap;line-height:1.6"><?= htmlspecialchars($activity['response_text'] ?: 'No text provided.') ?></div>
                        
                        <?php if ($activity['file_url']): ?>
                        <div style="margin-top:16px;padding-top:16px;border-top:1px solid #f1f5f9">
                            <a href="<?= $activity['file_url'] ?>" target="_blank" class="btn btn-outline btn-sm" style="display:inline-flex;align-items:center;gap:8px">
                                📎 View Attached File
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- GRADING SIDEBAR -->
    <div style="position:sticky;top:24px">
        <form action="/CBE_LMS/public/index.php?url=teacher/submitLessonGrade" method="POST" class="card" style="padding:24px;border:2px solid var(--primary)">
            <input type="hidden" name="student_id" value="<?= $studentId ?>">
            <input type="hidden" name="lesson_id" value="<?= $lessonId ?>">
            
            <h3 style="margin-top:0">Grading Form</h3>

            <!-- Interaction Score Override -->
            <div style="margin-bottom:20px">
                <label style="display:block;margin-bottom:8px;font-weight:600">Interaction Score (%)</label>
                <div style="display:flex;align-items:center;gap:12px">
                    <input type="number" name="interaction_score" value="<?= (int)($progress['interaction_score'] ?? 0) ?>" 
                           min="0" max="100" class="form-control" style="width:100px">
                    <span style="color:var(--muted);font-size:0.85rem">Auto-calculated: <?= (int)($progress['interaction_score'] ?? 0) ?>%</span>
                </div>
                <small style="color:var(--muted);display:block;margin-top:4px">Override if needed.</small>
            </div>

            <!-- Activity Score -->
            <?php if ($activity): ?>
            <div style="margin-bottom:20px">
                <label style="display:block;margin-bottom:8px;font-weight:600">Activity Score (Max <?= (int)$activity['max_marks'] ?>)</label>
                <input type="number" name="activity_score" value="<?= (int)($activity['score'] ?? 0) ?>" 
                       min="0" max="<?= (int)$activity['max_marks'] ?>" class="form-control" required>
            </div>
            <?php endif; ?>

            <!-- Feedback -->
            <div style="margin-bottom:20px">
                <label style="display:block;margin-bottom:8px;font-weight:600">Feedback for Student</label>
                <textarea name="feedback" rows="4" class="form-control" placeholder="Well done! Keep it up..."><?= htmlspecialchars($activity['teacher_feedback'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;padding:14px;font-size:1rem;font-weight:700">
                ✅ Submit Grade
            </button>
        </form>
    </div>
</div>

</div><!-- .content -->
</div><!-- .main-wrapper -->
</body>
</html>
