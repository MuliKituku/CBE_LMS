<?php
/* ── teacher/review_submission.php ─────────────────────────────
   Variables: $profile, $submissionData (assessment, student, interactions)
   ─────────────────────────────────────────────────────────── */
$activeNav = 'grading';
require BASE_PATH . '/app/views/teacher/_sidebar.php';

$assessment   = $submissionData['assessment'];
$student      = $submissionData['student'];
$interactions = $submissionData['interactions'];

$success = $_GET['success'] ?? '';
?>

<?php if ($success === 'graded'): ?>
<div class="alert alert-success">✔️ Grades saved! Submission is now fully reviewed.</div>
<?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <div style="display:flex;align-items:center;gap:16px">
        <a href="/CBE_LMS/public/index.php?url=teacher/grading" class="btn btn-outline" style="padding:6px 12px;border-radius:50%;width:36px;height:36px;display:flex;align-items:center;justify-content:center">←</a>
        <div>
            <h2 style="color:var(--text);margin:0">Review Submission</h2>
            <div style="font-size:0.85rem;color:var(--muted);margin-top:4px">
                <?= htmlspecialchars($student['fullname']) ?> · <?= htmlspecialchars($assessment['title']) ?>
            </div>
        </div>
    </div>
</div>

<form method="POST" action="/CBE_LMS/public/index.php?url=teacher/saveReviewGrades">
    <input type="hidden" name="student_id" value="<?= $assessment['student_id_param'] ?? 0 ?>">
    <input type="hidden" name="assessment_id" value="<?= $assessment['id'] ?>">

    <?php foreach ($interactions as $idx => $itr):
        $type     = $itr['interaction_type'];
        $isAuto   = ($type === 'mcq' || $type === 'true_false');
        $opts     = !empty($itr['options']) ? json_decode($itr['options'], true) : [];
        $qNum     = $idx + 1;
        $correct  = $itr['correct_answer'] ?? '';
        $given    = $itr['answer_given'] ?? '';
        $earned   = $itr['score_achieved'] ?? 0;
        $comment  = $itr['teacher_comment'] ?? '';

        // Check if auto-graded answer is correct
        $isCorrect = $isAuto && strtolower(trim($given)) === strtolower(trim($correct));
    ?>
    <div class="card" style="margin-bottom:20px;border-left:4px solid <?= $isAuto ? ($isCorrect?'#10b981':'#ef4444') : '#f59e0b' ?>">
        <div class="card-body">
            <!-- Header -->
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px">
                <div style="display:flex;align-items:flex-start;gap:10px;flex:1">
                    <div style="width:28px;height:28px;border-radius:50%;background:#1e293b;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;flex-shrink:0">
                        <?= $qNum ?>
                    </div>
                    <div>
                        <p style="font-weight:600;margin:0;font-size:.95rem"><?= htmlspecialchars($itr['question']) ?></p>
                        <span style="font-size:.72rem;background:<?= $isAuto?'#dbeafe':'#fef3c7' ?>;color:<?= $isAuto?'#1e40af':'#92400e' ?>;padding:2px 8px;border-radius:20px;font-weight:600;margin-top:4px;display:inline-block">
                            <?= $isAuto ? '⚡ Auto-Graded' : '✏️ Manual Review' ?>
                        </span>
                    </div>
                </div>
                <div style="text-align:right;font-size:.85rem;color:var(--muted)">
                    Max: <strong><?= (int)$itr['marks'] ?></strong> pt<?= $itr['marks']!=1?'s':'' ?>
                </div>
            </div>

            <!-- MCQ Options display -->
            <?php if ($isAuto && !empty($opts)): ?>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:12px">
                <?php foreach ($opts as $opt):
                    if (empty($opt['text'])) continue;
                    $oText = $opt['text'];
                    $isGiven   = strtolower(trim($given)) === strtolower(trim($oText));
                    $isCorrOpt = strtolower(trim($correct)) === strtolower(trim($oText));
                    $bgColor   = '#f8fafc'; $borderColor = '#e2e8f0'; $textColor = '#374151';
                    if ($isCorrOpt)  { $bgColor = '#dcfce7'; $borderColor = '#10b981'; $textColor = '#065f46'; }
                    if ($isGiven && !$isCorrOpt) { $bgColor = '#fee2e2'; $borderColor = '#ef4444'; $textColor = '#991b1b'; }
                ?>
                <div style="padding:8px 12px;background:<?= $bgColor ?>;border:2px solid <?= $borderColor ?>;border-radius:8px;font-size:.85rem;color:<?= $textColor ?>;font-weight:<?= $isCorrOpt||$isGiven?'600':'400' ?>">
                    <?= $isCorrOpt ? '✅ ' : ($isGiven ? '❌ ' : '') ?><?= htmlspecialchars($oText) ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Student's answer for text types -->
            <?php if (!$isAuto): ?>
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px;margin-bottom:12px">
                <div style="font-size:.75rem;color:var(--muted);font-weight:600;margin-bottom:6px">STUDENT'S ANSWER:</div>
                
                <?php if ($type === 'file_upload' && !empty($itr['file_url'])): ?>
                    <div style="display:flex;align-items:center;gap:15px;background:#fff;padding:12px;border-radius:8px;border:1px solid #cbd5e1">
                        <div style="font-size:1.5rem">📄</div>
                        <div style="flex:1">
                            <div style="font-size:.85rem;font-weight:600;color:var(--text)">Uploaded Submission</div>
                            <div style="font-size:.75rem;color:var(--muted)">Click to view or download</div>
                        </div>
                        <a href="/CBE_LMS/public/<?= htmlspecialchars($itr['file_url']) ?>" target="_blank" class="btn btn-primary btn-sm">View File</a>
                    </div>
                    <?php if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $itr['file_url'])): ?>
                    <div style="margin-top:10px;text-align:center">
                        <img src="/CBE_LMS/public/<?= htmlspecialchars($itr['file_url']) ?>" style="max-width:100%;max-height:300px;border-radius:8px;border:1px solid #e2e8f0">
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p style="margin:0;font-size:.9rem;color:#1e293b;white-space:pre-wrap"><?= htmlspecialchars($given ?: '(No answer provided)') ?></p>
                <?php endif; ?>

                <?php if ($type === 'fill_blank'): ?>
                <div style="margin-top:10px;font-size:.75rem;color:var(--muted)">
                    Correct Answer Key: <strong><?= htmlspecialchars($correct) ?></strong>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Score input -->
            <?php if ($isAuto): ?>
            <div style="display:flex;align-items:center;gap:12px">
                <span style="font-weight:600;font-size:.9rem;color:<?= $isCorrect?'#10b981':'#ef4444' ?>">
                    <?= $isCorrect ? '✅ Correct' : '❌ Incorrect' ?> — <?= $isCorrect ? (int)$itr['marks'] : 0 ?> / <?= (int)$itr['marks'] ?> pts
                </span>
                <span style="font-size:.8rem;color:var(--muted)">
                    Student answered: <em><?= htmlspecialchars($given ?: '—') ?></em>
                    · Correct: <em><?= htmlspecialchars($correct) ?></em>
                </span>
            </div>
            <!-- Hidden auto-grade value -->
            <input type="hidden" name="scores[<?= $itr['id'] ?>]" value="<?= $isCorrect ? (int)$itr['marks'] : 0 ?>">
            <input type="hidden" name="comments[<?= $itr['id'] ?>]" value="">
            <?php else: ?>
            <div style="display:flex;gap:12px;align-items:flex-start">
                <div>
                    <label style="font-size:.8rem;font-weight:600;display:block;margin-bottom:4px">Score <span style="color:#ef4444">*</span></label>
                    <div style="display:flex;align-items:center;gap:8px">
                        <input type="number" name="scores[<?= $itr['id'] ?>]" class="form-control"
                               value="<?= htmlspecialchars($earned) ?>"
                               min="0" max="<?= (int)$itr['marks'] ?>"
                               style="width:80px;padding:8px;font-size:1rem;font-weight:700"
                               required>
                        <span style="color:var(--muted);font-weight:600">/ <?= (int)$itr['marks'] ?></span>
                    </div>
                </div>
                <div style="flex:1">
                    <label style="font-size:.8rem;font-weight:600;display:block;margin-bottom:4px">Remark (Optional)</label>
                    <input type="text" name="comments[<?= $itr['id'] ?>]" class="form-control"
                           value="<?= htmlspecialchars($comment) ?>"
                           placeholder="e.g. Good analysis, but missing key points..."
                           style="font-size:.85rem;padding:8px">
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <div style="margin-top:8px;text-align:right">
        <button type="submit" class="btn btn-primary" style="background:#10b981;border:none;padding:12px 36px;font-size:1.05rem">
            ✅ Save Grades & Complete Review
        </button>
    </div>
</form>

</div><!-- .content -->
</div><!-- .main-wrapper -->
</body>
</html>
