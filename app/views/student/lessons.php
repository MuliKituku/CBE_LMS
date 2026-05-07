<?php
/* ── student/lessons.php ────────────────────────────────────
   Variables: $profile, $lessons (array), $subjects (array), $subject (current filter)
   ─────────────────────────────────────────────────────────── */
$activeNav = 'lessons';
require BASE_PATH . '/app/views/student/_sidebar.php';

$typeIcons = ['video'=>'🎬','audio'=>'🎧','image'=>'🖼','slides'=>'📊','notes'=>'📄'];
$typeBadge = ['video'=>'badge-info','audio'=>'badge-success','image'=>'badge-pending',
              'slides'=>'badge-muted','notes'=>'badge-inactive'];
?>
<!-- Toolbar -->
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px">
    <h2 style="font-size:1.05rem;font-weight:700;">
        <?= $activeNav === 'lessons' ? '📚 My Enrolled Lessons' : '🖋️ Register for Lessons' ?>
        <span class="badge badge-info" style="margin-left:8px"><?= count($lessons) ?></span>
    </h2>
</div>

<?php if (isset($_GET['success']) && $_GET['success'] === 'registered'): ?>
<div class="alert alert-success">✅ Successfully registered for the lesson! You are now linked with the teacher.</div>
<?php elseif (isset($_GET['error'])): ?>
<div class="alert alert-danger">❌ Registration failed. Please try again.</div>
<?php endif; ?>

<!-- Subject filter -->
<form method="GET" action="/CBE_LMS/public/index.php" style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap">
    <input type="hidden" name="url" value="student/lessons">
    <select name="subject" class="form-control" style="max-width:220px" onchange="this.form.submit()">
        <option value="">📚 All Subjects</option>
        <?php foreach ($subjects as $s): ?>
        <option value="<?= htmlspecialchars($s) ?>" <?= $subject === $s ? 'selected' : '' ?>>
            <?= htmlspecialchars($s) ?>
        </option>
        <?php endforeach; ?>
    </select>
    <?php if ($subject): ?>
    <a href="/CBE_LMS/public/index.php?url=student/lessons" class="btn btn-outline">Clear Filter</a>
    <?php endif; ?>
</form>

<?php if (empty($lessons)): ?>
<div class="card">
    <div class="empty-state">
        <div class="empty-icon">📚</div>
        <h3><?= $activeNav === 'lessons' ? 'No Enrolled Lessons' : 'No Lessons Available' ?></h3>
        <p>
            <?= $activeNav === 'lessons' 
                ? 'You haven\'t registered for any lessons yet. Go to <a href="/CBE_LMS/public/index.php?url=student/lessons">Register Lessons</a> to get started!' 
                : 'Your teacher hasn\'t published any lessons for ' . htmlspecialchars($profile['class_grade']) . ' yet. Check back soon!' ?>
        </p>
    </div>
</div>
<?php else: ?>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px">
<?php foreach ($lessons as $l): ?>
<?php 
    $icon = $typeIcons[$l['type']] ?? '📄'; 
    $badge = $typeBadge[$l['type']] ?? 'badge-muted'; 
    $isRegistered = in_array($l['id'], $registeredIds);
?>
<div class="card" style="transition:transform .18s,box-shadow .18s; overflow:hidden; <?= $isRegistered ? '' : 'border-top:4px solid #f59e0b' ?>">
    <!-- Lesson type banner -->
    <div style="height:120px;background:<?= $isRegistered ? 'linear-gradient(135deg,#0d9488,#0891b2)' : 'linear-gradient(135deg,#f59e0b,#d97706)' ?>;
                display:flex;flex-direction:column;align-items:center;justify-content:center;font-size:3.5rem;position:relative">
        <?= $icon ?>
        <?php if ($isRegistered): ?>
            <div style="position:absolute;top:10px;right:10px;background:#fff;color:#0d9488;padding:4px 10px;border-radius:20px;font-size:0.7rem;font-weight:800;box-shadow:0 2px 4px rgba(0,0,0,0.1)">
                ✅ ENROLLED
            </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:8px">
            <h3 style="font-size:.95rem;font-weight:700;color:var(--text);margin:0">
                <?= htmlspecialchars($l['strand']) ?>
                <?php if (!empty($l['sub_strand'])): ?>
                    <span style="font-weight:400;color:var(--muted);font-size:.85rem">/ <?= htmlspecialchars($l['sub_strand']) ?></span>
                <?php endif; ?>
            </h3>
            <span class="badge <?= $badge ?>" style="flex-shrink:0"><?= strtoupper($l['type']) ?></span>
        </div>
        <p style="font-size:.8rem;color:var(--muted);margin-bottom:10px">
            📖 <?= htmlspecialchars($l['subject']) ?> &nbsp;|&nbsp;
            👨‍🏫 <?= htmlspecialchars($l['teacher_name']) ?>
        </p>
        <?php if ($l['description']): ?>
        <p style="font-size:.82rem;color:#475569;margin-bottom:12px;line-height:1.5;
                  display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">
            <?= htmlspecialchars($l['description']) ?>
        </p>
        <?php endif; ?>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-top:auto">
            <span style="font-size:.70rem;color:var(--muted)">
                🕐 <?= date('d M Y', strtotime($l['created_at'])) ?>
            </span>
            
            <?php if ($isRegistered): ?>
                <a href="/CBE_LMS/public/index.php?url=student/lesson&id=<?= $l['id'] ?>"
                   class="btn btn-primary btn-sm">Open Lesson →</a>
            <?php else: ?>
                <form method="POST" action="/CBE_LMS/public/index.php?url=student/registerLesson">
                    <input type="hidden" name="lesson_id" value="<?= $l['id'] ?>">
                    <button type="submit" class="btn btn-warning btn-sm">Register Now</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

</div></div>
</body></html>
