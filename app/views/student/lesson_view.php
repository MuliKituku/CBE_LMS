<?php
/* ── student/lesson_view.php ─ Kenyan CBE 5-Step Multimedia Lesson
   Variables: $profile, $lesson (with interactions, activity, progress), $discussions
   ──────────────────────────────────────────────────────────────────────────────── */
$activeNav = 'lessons';
require BASE_PATH . '/app/views/student/_sidebar.php';

$type    = $lesson['type'] ?? 'notes';
$url     = htmlspecialchars($lesson['content_url'] ?? '');
$success = $_GET['success'] ?? '';
$lessonId = (int)$lesson['id'];

/* ── Progress step logic ── */
$stepOrder   = ['media'=>1,'interaction'=>2,'activity'=>3,'assessment'=>4,'completed'=>5];
$currentStep = $lesson['progress']['step_reached'] ?? 'media';
$currentOrd  = $stepOrder[$currentStep] ?? 1;

$hasInteractions = !empty($lesson['interactions']);
$hasActivity     = !empty($lesson['activity']);
$level           = $lesson['education_level'] ?? 'primary';

$levelConfig = [
    'pre_primary' => ['color'=>'#f59e0b','label'=>'Pre-Primary','icon'=>'🎨','badge_bg'=>'#fef3c7'],
    'primary'     => ['color'=>'#10b981','label'=>'Primary',    'icon'=>'📚','badge_bg'=>'#d1fae5'],
    'junior'      => ['color'=>'#6366f1','label'=>'Junior',     'icon'=>'🔬','badge_bg'=>'#ede9fe'],
    'senior'      => ['color'=>'#ef4444','label'=>'Senior',     'icon'=>'🎓','badge_bg'=>'#fee2e2'],
];
$lc = $levelConfig[$level] ?? $levelConfig['primary'];

$steps = ['media'=>'🎥 Media','interaction'=>'🧠 Interact','activity'=>'📝 Activity','assessment'=>'📊 Score','completed'=>'🏆 Done'];
?>

<!-- Back button -->
<div style="margin-bottom:16px">
    <a href="/CBE_LMS/public/index.php?url=student/lessons" class="btn btn-outline btn-sm">← Back to Lessons</a>
</div>

<?php if ($success === 'activity_submitted'): ?>
<div class="alert alert-success">✅ Activity submitted! Continue to view your assessment score.</div>
<?php elseif ($success === 'posted'): ?>
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
                <span>👨‍🏫 <?= htmlspecialchars($lesson['teacher_name']) ?></span>
                <span>📅 <?= date('d M Y', strtotime($lesson['created_at'])) ?></span>
            </div>
        </div>
        <div style="text-align:right">
            <span style="display:inline-block;padding:5px 12px;border-radius:20px;font-size:.75rem;font-weight:700;background:<?= $lc['badge_bg'] ?>;color:<?= $lc['color'] ?>">
                <?= $lc['icon'] ?> <?= $lc['label'] ?>
            </span>
        </div>
    </div>
</div>

<!-- ══════════════ PROGRESS STEPPER ══════════════ -->
<div class="card" style="margin-bottom:20px;padding:18px 20px">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
        <?php $stepKeys = array_keys($steps); $totalSteps = count($stepKeys); ?>
        <?php foreach ($steps as $key => $label): ?>
        <?php
            $ord   = $stepOrder[$key];
            $done  = ($ord <= $currentOrd);
            $active= ($key === $currentStep);
            $bg    = $done  ? $lc['color'] : ($active ? $lc['color'] : '#e2e8f0');
            $fc    = $done || $active ? '#fff' : '#94a3b8';
            $sz    = $active ? '1rem' : '.85rem';
        ?>
        <div style="display:flex;align-items:center;gap:8px;flex:1">
            <div style="display:flex;align-items:center;gap:8px">
                <div style="width:34px;height:34px;border-radius:50%;background:<?= $bg ?>;color:<?= $fc ?>;
                            display:flex;align-items:center;justify-content:center;font-size:.85rem;font-weight:700;
                            box-shadow:<?= $active ? "0 0 0 4px {$lc['color']}33" : 'none' ?>;flex-shrink:0">
                    <?= $done && !$active ? '✓' : $stepOrder[$key] ?>
                </div>
                <span style="font-size:<?= $sz ?>;font-weight:<?= $active ? '700' : '500' ?>;color:<?= $done || $active ? 'var(--text)' : 'var(--muted)' ?>">
                    <?= $label ?>
                </span>
            </div>
            <?php if ($key !== 'completed'): ?>
            <div style="flex:1;height:2px;background:<?= ($ord < $currentOrd) ? $lc['color'] : '#e2e8f0' ?>;margin:0 8px;min-width:20px;border-radius:2px"></div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ══════════════ STEP 1: MEDIA ══════════════ -->
<div class="card" style="margin-bottom:20px" id="step-media">
    <div class="card-header">
        <h3 style="font-size:1rem">🎥 Step 1: Media</h3>
        <?php if ($currentOrd > 1): ?>
        <span style="background:#d1fae5;color:#065f46;padding:3px 10px;border-radius:20px;font-size:.75rem;font-weight:700">✓ Viewed</span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if ($type === 'video' && $url): ?>
        <?php 
        $isYoutube = (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false);
        if ($isYoutube):
            $embedUrl = $url;
            if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $url, $match)) {
                $embedUrl = "https://www.youtube.com/embed/" . $match[1];
            }
        ?>
        <div style="border-radius:10px;overflow:hidden;margin-bottom:16px;background:#000;aspect-ratio:16/9">
            <iframe src="<?= $embedUrl ?>" style="width:100%;height:100%;border:none" allowfullscreen></iframe>
        </div>
        <?php else: ?>
        <div style="background:#000;border-radius:10px;overflow:hidden;margin-bottom:16px">
            <video controls style="width:100%;max-height:420px;display:block" preload="metadata" id="lessonVideo"
                <?= $level === 'pre_primary' ? 'autoplay muted' : '' ?>>
                <source src="<?= $url ?>" type="video/mp4">
                <source src="<?= $url ?>" type="video/webm">
            </video>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
            <button onclick="document.getElementById('lessonVideo').requestFullscreen()" class="btn btn-outline btn-sm">⛶ Fullscreen</button>
            <?php if (strpos($url,'http') !== 0): ?>
            <a href="<?= $url ?>" download class="btn btn-outline btn-sm">⬇ Download</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php elseif ($type === 'audio' && $url): ?>
        <div style="background:linear-gradient(135deg,#0d9488,#0891b2);border-radius:12px;padding:30px;text-align:center">
            <div style="font-size:4rem;margin-bottom:12px">🎧</div>
            <audio controls style="width:100%;max-width:460px" preload="metadata">
                <source src="<?= $url ?>" type="audio/mpeg">
            </audio>
        </div>

        <?php elseif ($type === 'image' && $url): ?>
        <div style="text-align:center">
            <img src="<?= $url ?>" alt="<?= htmlspecialchars($lesson['strand']) ?>"
                 style="max-width:100%;max-height:500px;border-radius:10px;box-shadow:var(--shadow)">
        </div>

        <?php elseif (in_array($type,['slides','pdf']) && $url && strpos($url,'http')!==0): ?>
        <div style="border-radius:10px;overflow:hidden;border:1px solid var(--border)">
            <iframe src="<?= $url ?>" style="width:100%;height:550px;display:block;border:none"></iframe>
        </div>
        <a href="<?= $url ?>" download class="btn btn-primary btn-sm" style="margin-top:10px">⬇ Download File</a>

        <?php elseif ($url && strpos($url,'http')===0): ?>
        <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:12px;padding:40px;text-align:center">
            <div style="font-size:3.5rem;margin-bottom:15px">🔗</div>
            <h3 style="color:#0369a1;margin-bottom:10px">External Resource</h3>
            <a href="<?= $url ?>" target="_blank" class="btn btn-primary" style="padding:12px 30px">Open Lesson ↗</a>
        </div>

        <?php else: ?>
        <div style="background:#f8fafc;border-radius:10px;padding:24px;text-align:center;border:1px solid var(--border)">
            <div style="font-size:3rem;margin-bottom:10px">📄</div>
            <p style="color:var(--muted)">Text/Notes lesson. Read the description below.</p>
        </div>
        <?php endif; ?>

        <?php if ($lesson['learning_outcomes']): ?>
        <div style="background:#f0fdfa;border:1px solid #99f6e4;border-radius:10px;padding:14px;margin-top:16px">
            <div style="font-weight:700;color:#0d9488;font-size:.85rem;margin-bottom:6px">🎯 Learning Outcomes</div>
            <p style="font-size:.9rem;color:#374151;margin:0;line-height:1.6"><?= nl2br(htmlspecialchars($lesson['learning_outcomes'])) ?></p>
        </div>
        <?php endif; ?>

        <?php if ($lesson['description']): ?>
        <div style="background:#f8fafc;border-radius:10px;padding:14px;margin-top:12px;border:1px solid var(--border)">
            <div style="font-weight:700;font-size:.85rem;margin-bottom:6px">📝 About This Lesson</div>
            <p style="font-size:.9rem;color:#374151;margin:0;line-height:1.6"><?= nl2br(htmlspecialchars($lesson['description'])) ?></p>
        </div>
        <?php endif; ?>

        <?php if ($currentOrd === 1 && $hasInteractions): ?>
        <div style="margin-top:20px;text-align:center">
            <button class="btn btn-primary" style="padding:12px 32px;font-size:1rem" onclick="advanceStep('interaction')">
                I've watched the lesson → Start Interactions 🧠
            </button>
        </div>
        <?php elseif ($currentOrd === 1 && $hasActivity): ?>
        <div style="margin-top:20px;text-align:center">
            <button class="btn btn-primary" style="padding:12px 32px" onclick="advanceStep('activity')">
                Continue to Activity 📝
            </button>
        </div>
        <?php elseif ($currentOrd === 1): ?>
        <div style="margin-top:20px;text-align:center">
            <button class="btn btn-primary" style="padding:12px 32px" onclick="advanceStep('completed')">
                Mark Lesson Complete 🏆
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ══════════════ STEP 2: INTERACTIONS ══════════════ -->
<?php if ($hasInteractions): ?>
<div class="card" style="margin-bottom:20px" id="step-interaction"
     <?= $currentOrd < 2 ? 'style="opacity:.5;pointer-events:none;margin-bottom:20px"' : '' ?>>
    <div class="card-header">
        <h3 style="font-size:1rem">🧠 Step 2: Interactive Questions</h3>
        <div style="display:flex;align-items:center;gap:10px">
            <span id="interaction-score-badge" class="badge" style="background:#d1fae5;color:#065f46;padding:3px 10px;border-radius:20px;font-size:.75rem;font-weight:700;<?= ($lesson['progress']['interaction_score'] === null) ? 'display:none' : '' ?>">
                Score: <span id="live-int-score"><?= (int)($lesson['progress']['interaction_score'] ?? 0) ?></span>%
            </span>
            <?php if ($currentOrd > 2): ?>
            <span style="background:#d1fae5;color:#065f46;padding:3px 10px;border-radius:20px;font-size:.75rem;font-weight:700">✓ Completed</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        <div id="interactions-wrapper">
        <?php foreach ($lesson['interactions'] as $idx => $inter):
            $optionsArr = $inter['options_arr'] ?? [];
            $answered   = ($inter['answer_given'] !== null && $inter['answer_given'] !== '');
            $wasCorrect = (bool)($inter['student_is_correct'] ?? false);
        ?>
        <div class="interaction-card" id="int-card-<?= $inter['id'] ?>"
             style="border:2px solid <?= $answered ? ($wasCorrect ? '#10b981' : '#ef4444') : 'var(--border)' ?>;
                    border-radius:12px;padding:18px;margin-bottom:16px;transition:border-color .3s">
            <div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:14px">
                <div style="width:32px;height:32px;border-radius:50%;background:<?= $lc['color'] ?>;color:#fff;
                            display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;flex-shrink:0">
                    <?= $idx + 1 ?>
                </div>
                <div style="flex:1">
                    <div style="font-weight:600;font-size:.95rem;margin-bottom:4px"><?= htmlspecialchars($inter['question']) ?></div>
                    <?php if (!empty($inter['competency_title'])): ?>
                    <div style="font-size:.72rem;color:var(--muted)">🎯 Maps to: <?= htmlspecialchars($inter['competency_title']) ?></div>
                    <?php endif; ?>
                </div>
                <?php if ($answered): ?>
                <span style="font-size:1.3rem"><?= $wasCorrect ? '✅' : '❌' ?></span>
                <?php endif; ?>
            </div>

            <?php
            $iType = $inter['interaction_type'];

            /* ─ MCQ / True-False ─ */
            if (in_array($iType, ['mcq','true_false'])): ?>
            <div class="mcq-options" style="display:grid;gap:8px">
                <?php
                $options = $optionsArr;
                if ($iType === 'true_false') $options = [['id'=>'true','text'=>'True'],['id'=>'false','text'=>'False']];
                foreach ($options as $opt): ?>
                <?php
                $isChosen  = ($answered && strtolower($inter['answer_given']) === strtolower($opt['id']));
                $isCorrect = (strtolower($inter['correct_answer']) === strtolower($opt['id']));
                $btnColor  = '#f8fafc'; $border = '#e2e8f0'; $textColor = 'var(--text)';
                if ($answered) {
                    if ($isCorrect) { $btnColor='#d1fae5';$border='#10b981';$textColor='#065f46'; }
                    if ($isChosen && !$isCorrect) { $btnColor='#fee2e2';$border='#ef4444';$textColor='#991b1b'; }
                }
                ?>
                <button type="button"
                    onclick="submitInteraction(<?= $inter['id'] ?>, <?= $lessonId ?>, '<?= htmlspecialchars($opt['id'], ENT_QUOTES) ?>')"
                    <?= $answered ? 'disabled' : '' ?>
                    style="text-align:left;padding:10px 16px;border-radius:8px;border:2px solid <?= $border ?>;
                           background:<?= $btnColor ?>;color:<?= $textColor ?>;font-size:.88rem;cursor:<?= $answered ? 'default' : 'pointer' ?>;
                           transition:all .2s;width:100%">
                    <strong><?= strtoupper($opt['id']) ?></strong> — <?= htmlspecialchars($opt['text']) ?>
                    <?php if ($answered && $isCorrect): ?> ✓<?php endif; ?>
                </button>
                <?php endforeach; ?>
            </div>

            <?php /* ─ Fill Blank / Scenario ─ */
            elseif (in_array($iType, ['fill_blank','scenario'])): ?>
            <div style="display:flex;gap:10px;align-items:flex-end">
                <input type="text" id="fill-<?= $inter['id'] ?>" class="form-control"
                    value="<?= $answered ? htmlspecialchars($inter['answer_given']) : '' ?>"
                    <?= $answered ? 'disabled' : '' ?>
                    placeholder="Type your answer…" style="font-size:.9rem">
                <?php if (!$answered): ?>
                <button type="button" onclick="submitInteraction(<?= $inter['id'] ?>, <?= $lessonId ?>, document.getElementById('fill-<?= $inter['id'] ?>').value)"
                    class="btn btn-primary btn-sm" style="flex-shrink:0">Submit</button>
                <?php endif; ?>
            </div>

            <?php /* ─ Click Image / Drag Drop (rendered as MCQ with text for now) ─ */
            else: ?>
            <?php if (!empty($optionsArr)): ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px">
                <?php foreach ($optionsArr as $opt):
                    $isChosen  = ($answered && strtolower($inter['answer_given']) === strtolower($opt['id']));
                    $isCorrect = (strtolower($inter['correct_answer']) === strtolower($opt['id']));
                    $bg = '#f8fafc'; $border = '#e2e8f0';
                    if ($answered) {
                        if ($isCorrect) { $bg='#d1fae5';$border='#10b981'; }
                        if ($isChosen && !$isCorrect) { $bg='#fee2e2';$border='#ef4444'; }
                    }
                ?>
                <button type="button"
                    onclick="submitInteraction(<?= $inter['id'] ?>, <?= $lessonId ?>, '<?= htmlspecialchars($opt['id'], ENT_QUOTES) ?>')"
                    <?= $answered ? 'disabled' : '' ?>
                    style="padding:14px 10px;border-radius:10px;border:2px solid <?= $border ?>;background:<?= $bg ?>;
                           font-size:.85rem;font-weight:600;cursor:<?= $answered ? 'default' : 'pointer' ?>;transition:all .2s">
                    <?= htmlspecialchars($opt['text']) ?>
                    <?php if ($answered && $isCorrect): ?>&nbsp;✓<?php endif; ?>
                </button>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div style="display:flex;gap:10px;align-items:flex-end">
                <input type="text" id="fill-<?= $inter['id'] ?>" class="form-control"
                    value="<?= $answered ? htmlspecialchars($inter['answer_given']) : '' ?>"
                    <?= $answered ? 'disabled' : '' ?>
                    placeholder="Your answer…">
                <?php if (!$answered): ?>
                <button type="button" onclick="submitInteraction(<?= $inter['id'] ?>, <?= $lessonId ?>, document.getElementById('fill-<?= $inter['id'] ?>').value)"
                    class="btn btn-primary btn-sm" style="flex-shrink:0">Submit</button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <!-- Feedback area -->
            <div id="feedback-<?= $inter['id'] ?>" style="margin-top:10px;display:none"></div>
            <?php if ($answered && $inter['hint'] && !$wasCorrect): ?>
            <div style="background:#fef9c3;border:1px solid #fde68a;border-radius:8px;padding:10px;margin-top:10px;font-size:.85rem">
                💡 Hint: <?= htmlspecialchars($inter['hint']) ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        </div>

        <!-- Proceed to Activity -->
        <?php
        $answeredCount = count(array_filter($lesson['interactions'], fn($i) => $i['answer_given'] !== null && $i['answer_given'] !== ''));
        $totalInter    = count($lesson['interactions']);
        ?>
        <div id="proceed-activity" style="text-align:center;margin-top:16px;<?= ($answeredCount < $totalInter && $currentOrd <= 2) ? 'opacity:.5;pointer-events:none' : '' ?>">
            <p style="color:var(--muted);font-size:.85rem;margin-bottom:12px">
                <?= $answeredCount ?>/<?= $totalInter ?> questions answered
            </p>
            <?php if ($hasActivity && $currentOrd <= 3): ?>
            <button class="btn btn-primary" style="padding:12px 32px" onclick="proceedAfterInteractions()">
                Continue to Activity 📝
            </button>
            <?php elseif (!$hasActivity && $currentOrd <= 3): ?>
            <button class="btn btn-primary" style="padding:12px 32px" onclick="advanceStep('completed')">
                Complete Lesson 🏆
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ══════════════ STEP 3: ACTIVITY ══════════════ -->
<?php if ($hasActivity): ?>
<?php $act = $lesson['activity']; $actSub = $lesson['activity_submission'] ?? null; ?>
<div class="card" style="margin-bottom:20px" id="step-activity"
     <?= $currentOrd < 3 ? 'style="opacity:.5;pointer-events:none;margin-bottom:20px"' : '' ?>>
    <div class="card-header">
        <h3 style="font-size:1rem">📝 Step 3: Activity — <?= htmlspecialchars($act['title']) ?></h3>
        <?php if ($actSub): ?>
        <span style="background:#d1fae5;color:#065f46;padding:3px 10px;border-radius:20px;font-size:.75rem;font-weight:700">✓ Submitted</span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <div style="background:linear-gradient(135deg,<?= $lc['color'] ?>22,<?= $lc['color'] ?>11);border:1px solid <?= $lc['color'] ?>44;border-radius:10px;padding:16px;margin-bottom:16px">
            <div style="font-weight:700;color:<?= $lc['color'] ?>;margin-bottom:8px"><?= $lc['icon'] ?> Your Task</div>
            <p style="font-size:.9rem;color:var(--text);margin:0;line-height:1.7"><?= nl2br(htmlspecialchars($act['description'])) ?></p>
        </div>

        <?php if ($actSub): ?>
        <!-- Already submitted -->
        <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:10px;padding:14px">
            <div style="font-weight:700;color:#15803d;margin-bottom:8px">✅ Your Submission</div>
            <?php if ($actSub['response_text']): ?>
            <p style="font-size:.9rem;color:var(--text);margin-bottom:8px"><?= nl2br(htmlspecialchars($actSub['response_text'])) ?></p>
            <?php endif; ?>
            <?php if ($actSub['file_url']): ?>
            <a href="<?= htmlspecialchars($actSub['file_url']) ?>" download class="btn btn-outline btn-sm">📎 View Submitted File</a>
            <?php endif; ?>
            <?php if ($actSub['teacher_comment']): ?>
            <div style="margin-top:12px;padding:10px;background:#fff;border-radius:8px;border:1px solid #d1fae5">
                <strong style="font-size:.8rem">Teacher Comment:</strong>
                <p style="font-size:.88rem;margin:4px 0 0"><?= nl2br(htmlspecialchars($actSub['teacher_comment'])) ?></p>
            </div>
            <?php endif; ?>
            <?php if ($actSub['score'] !== null): ?>
            <div style="margin-top:8px;font-size:.9rem;font-weight:700;color:#15803d">
                Score: <?= $actSub['score'] ?>/<?= $act['max_marks'] ?> marks
            </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <!-- Submission form -->
        <form method="POST" action="/CBE_LMS/public/index.php?url=student/submitActivity" enctype="multipart/form-data">
            <input type="hidden" name="lesson_id" value="<?= $lessonId ?>">
            <input type="hidden" name="activity_id" value="<?= $act['id'] ?>">

            <?php if (in_array($act['submission_type'], ['text','both'])): ?>
            <div class="form-group">
                <label class="form-label">Your Response</label>
                <textarea name="response_text" class="form-control" rows="4"
                    placeholder="Write your response here…"></textarea>
            </div>
            <?php endif; ?>

            <?php if (in_array($act['submission_type'], ['file','both'])): ?>
            <div class="form-group">
                <label class="form-label">Upload File (PDF, image, document, etc.)</label>
                <input type="file" name="activity_file" class="form-control">
            </div>
            <?php endif; ?>

            <div style="display:flex;gap:12px;align-items:center;margin-top:8px">
                <button type="submit" class="btn btn-primary">📤 Submit Response</button>
                <span style="font-size:.8rem;color:var(--muted)">Max marks: <?= $act['max_marks'] ?></span>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- ══════════════ STEP 4 & 5: ASSESSMENT / COMPETENCY ══════════════ -->
<?php if ($currentOrd >= 4): ?>
<div class="card" style="margin-bottom:20px">
    <div class="card-header">
        <h3 style="font-size:1rem">📊 Step 4: Assessment Summary</h3>
    </div>
    <div class="card-body">
        <?php
        $score = (int)($lesson['progress']['interaction_score'] ?? 0);
        $scoreColor = $score >= 70 ? '#10b981' : ($score >= 50 ? '#f59e0b' : '#ef4444');
        $scoreLabel = $score >= 70 ? 'Excellent! Well done 🏆' : ($score >= 50 ? 'Good effort! Keep going 💪' : 'Keep practising — you can do it! 📚');
        ?>
        <div style="display:grid;grid-template-columns:auto 1fr;gap:20px;align-items:center">
            <div style="width:90px;height:90px;border-radius:50%;background:<?= $scoreColor ?>22;border:4px solid <?= $scoreColor ?>;
                        display:flex;align-items:center;justify-content:center;font-size:1.6rem;font-weight:700;color:<?= $scoreColor ?>">
                <?= $score ?>%
            </div>
            <div>
                <div style="font-size:1.1rem;font-weight:700;color:var(--text);margin-bottom:4px"><?= $scoreLabel ?></div>
                <div style="font-size:.85rem;color:var(--muted)">Interaction score for this lesson</div>
                <!-- Sub-list of interactions -->
                <?php foreach ($lesson['interactions'] as $inter): ?>
                <div style="display:flex;align-items:center;gap:8px;margin-top:8px;font-size:.85rem">
                    <span><?= $inter['student_is_correct'] ? '✅' : '❌' ?></span>
                    <span style="color:var(--text)"><?= htmlspecialchars(substr($inter['question'], 0, 60)) . (strlen($inter['question'])>60?'…':'') ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($currentOrd < 5): ?>
        <div style="margin-top:20px;text-align:center">
            <button class="btn btn-primary" style="padding:12px 32px;font-size:1rem" onclick="advanceStep('completed')">
                🏆 Complete Lesson &amp; Unlock Competency
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php if ($currentOrd >= 5): ?>
<div class="card" style="margin-bottom:20px;background:linear-gradient(135deg,<?= $lc['color'] ?>15,<?= $lc['color'] ?>05)">
    <div class="card-body" style="text-align:center;padding:32px">
            <div style="font-size:3rem;margin-bottom:10px">🏆</div>
            <h3 style="font-weight:700;margin-bottom:8px">Lesson Completed!</h3>
            <p style="color:#1e293b;font-size:.95rem;margin-bottom:16px">
            You have completed <strong><?= htmlspecialchars($lesson['strand']) ?></strong>.<br>
            Competencies linked to your correct answers have been updated.
        </p>
        <a href="/CBE_LMS/public/index.php?url=student/progress" class="btn btn-outline btn-sm">
            📊 View My Competency Progress
        </a>
        <a href="/CBE_LMS/public/index.php?url=student/lessons" class="btn btn-primary btn-sm" style="margin-left:10px">
            📚 Back to Lessons
        </a>
    </div>
</div>
<?php endif; ?>

<!-- Resources -->
<?php if (!empty($lesson['resources'])): ?>
<div class="card" style="margin-bottom:20px">
    <div class="card-header"><h3>📂 Resources</h3></div>
    <div class="card-body" style="padding:0">
        <?php foreach ($lesson['resources'] as $res): ?>
        <a href="<?= htmlspecialchars($res['file_url']) ?>" download
           style="display:flex;align-items:center;gap:12px;padding:12px 20px;text-decoration:none;border-bottom:1px solid #f1f5f9;transition:background .2s"
           onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
            <div style="font-size:1.2rem">📎</div>
            <div style="flex:1">
                <div style="font-weight:600;font-size:.85rem"><?= htmlspecialchars($res['label']) ?></div>
                <div style="font-size:.75rem;color:var(--muted)">Download</div>
            </div>
            <div style="color:var(--primary)">⬇️</div>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ══════════════ DISCUSSIONS ══════════════ -->
<div class="card" id="discussions">
    <div class="card-header"><h3>🗣 Discussion (<?= count($discussions) ?> posts)</h3></div>
    <div class="card-body">
        <?php if (empty($discussions)): ?>
        <p style="color:var(--muted);font-size:.9rem">No discussion posts yet. Be the first to ask a question!</p>
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
                    <form method="POST" action="/CBE_LMS/public/index.php?url=student/postDiscussion">
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
            <h4 style="font-size:.9rem;font-weight:700;margin-bottom:12px">✏️ Start a Discussion</h4>
            <form method="POST" action="/CBE_LMS/public/index.php?url=student/postDiscussion">
                <input type="hidden" name="lesson_id" value="<?= $lessonId ?>">
                <input type="hidden" name="parent_id" value="">
                <textarea name="message" class="form-control" rows="3" placeholder="Ask a question or share a thought…" required style="margin-bottom:10px"></textarea>
                <button type="submit" class="btn btn-primary">Post Comment 💬</button>
            </form>
        </div>
    </div>
</div>

</div></div>
<script>
const LESSON_ID = <?= $lessonId ?>;
let pendingAnswers = 0;
let totalInteractions = <?= count($lesson['interactions']) ?>;

/* ─── AJAX: Submit Interaction ─── */
function submitInteraction(interactionId, lessonId, answer) {
    if (!answer || String(answer).trim() === '') {
        alert('Please enter an answer before submitting.');
        return;
    }
    const fd = new FormData();
    fd.append('lesson_id', lessonId);
    fd.append('interaction_id', interactionId);
    fd.append('answer', answer);

    fetch('/CBE_LMS/public/index.php?url=student/submitInteraction', { method:'POST', body:fd })
        .then(r => r.json())
        .then(data => {
            const card  = document.getElementById('int-card-' + interactionId);
            const fbDiv = document.getElementById('feedback-' + interactionId);

            // Style card border
            card.style.borderColor = data.is_correct ? '#10b981' : '#ef4444';

            // Update total score badge
            const badge = document.getElementById('interaction-score-badge');
            const scoreVal = document.getElementById('live-int-score');
            if (badge && scoreVal) {
                badge.style.display = 'inline-block';
                scoreVal.textContent = data.score;
            }

            // Show feedback
            fbDiv.style.display = 'block';
            fbDiv.innerHTML = data.is_correct
                ? `<div style="background:#d1fae5;border-radius:8px;padding:10px;font-size:.88rem;color:#065f46">✅ <strong>Correct!</strong></div>`
                : `<div style="background:#fee2e2;border-radius:8px;padding:10px;font-size:.88rem;color:#991b1b">
                     ❌ <strong>Incorrect.</strong> Correct answer: <em>${escHtml(String(data.correct_answer))}</em>
                     ${data.hint ? '<br>💡 Hint: ' + escHtml(String(data.hint)) : ''}
                   </div>`;

            // Disable all buttons in this card
            card.querySelectorAll('button').forEach(b => { b.disabled = true; b.style.cursor = 'default'; });
            card.querySelectorAll('input').forEach(inp => inp.disabled = true);

            // Count answered and maybe unlock proceed button
            checkAllAnswered();
        })
        .catch(() => alert('Network error. Please try again.'));
}

function checkAllAnswered() {
    const answered = document.querySelectorAll('.interaction-card').length -
                     document.querySelectorAll('.interaction-card button:not([disabled])').length;
    // If all have at least one disabled button group, show proceed btn
    let allDone = true;
    document.querySelectorAll('.interaction-card').forEach(card => {
        const hasDoneBtn = card.querySelector('button[disabled]') || card.querySelector('input[disabled]');
        if (!hasDoneBtn) allDone = false;
    });
    if (allDone) {
        const btn = document.querySelector('#proceed-activity');
        if (btn) { btn.style.opacity = '1'; btn.style.pointerEvents = 'auto'; }
    }
}

/* ─── AJAX: Advance Step ─── */
function advanceStep(step) {
    const fd = new FormData();
    fd.append('lesson_id', LESSON_ID);
    fd.append('step', step);

    fetch('/CBE_LMS/public/index.php?url=student/advanceStep', { method:'POST', body:fd })
        .then(r => r.json())
        .then(() => location.reload())
        .catch(() => location.reload());
}

function proceedAfterInteractions() { 
    const hasActivity = `<?= $hasActivity ? '1' : '' ?>`;
    if (hasActivity) {
        advanceStep('activity');
    } else {
        advanceStep('completed');
    }
}

function toggleReply(id) {
    const el = document.getElementById('reply-' + id);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}

function escHtml(s) {
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// On load: re-check if locked sections should still be locked
document.addEventListener('DOMContentLoaded', () => {
    const currentOrd = <?= $currentOrd ?>;
    if (currentOrd >= 2) {
        const intSection = document.getElementById('step-interaction');
        if (intSection) { intSection.style.opacity='1'; intSection.style.pointerEvents='auto'; }
    }
    if (currentOrd >= 3) {
        const actSection = document.getElementById('step-activity');
        if (actSection) { actSection.style.opacity='1'; actSection.style.pointerEvents='auto'; }
    }
});
</script>
</body></html>
