<?php
/* ── teacher/lessons.php ─ Kenyan CBE Multimedia Lesson Builder ──
   Variables: $profile, $lessons
   ──────────────────────────────────────────────────────────────── */
$activeNav = 'lessons';
require BASE_PATH . '/app/views/teacher/_sidebar.php';

$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';

// CBE Kenya grade structure
$gradeGroups = [
    'Pre-Primary'  => ['PP1', 'PP2'],
    'Primary'      => ['Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6'],
    'Junior School'=> ['Grade 7','Grade 8','Grade 9'],
    'Senior School'=> ['Grade 10','Grade 11','Grade 12'],
];
$levelMap = [
    'PP1'=>'pre_primary','PP2'=>'pre_primary',
    'Grade 1'=>'primary','Grade 2'=>'primary','Grade 3'=>'primary',
    'Grade 4'=>'primary','Grade 5'=>'primary','Grade 6'=>'primary',
    'Grade 7'=>'junior','Grade 8'=>'junior','Grade 9'=>'junior',
    'Grade 10'=>'senior','Grade 11'=>'senior','Grade 12'=>'senior',
];
$levelHints = [
    'pre_primary' => ['icon'=>'🎨','label'=>'Pre-Primary (PP1–PP2)','color'=>'#f59e0b','tip'=>'Use short videos, bright images. Interactions: click-image, drag & drop. Keep it fun & visual!'],
    'primary'     => ['icon'=>'📚','label'=>'Primary (Grade 1–6)',   'color'=>'#10b981','tip'=>'Use demonstration videos + notes. Interactions: MCQ quiz, fill-in-the-blank, true/false.'],
    'junior'      => ['icon'=>'🔬','label'=>'Junior School (Grade 7–9)','color'=>'#6366f1','tip'=>'Use experiment videos & simulations. Interactions: scenario questions, group tasks.'],
    'senior'      => ['icon'=>'🎓','label'=>'Senior School (Grade 10–12)','color'=>'#ef4444','tip'=>'Use case studies & industry videos. Interactions: research tasks, report/presentation upload.'],
];
$interactionTypes = [
    'mcq'          => 'Multiple Choice (MCQ)',
    'true_false'   => 'True / False',
    'fill_blank'   => 'Fill in the Blank',
    'click_image'  => 'Click the Correct Image',
    'drag_drop'    => 'Drag & Drop Matching',
    'scenario'     => 'Scenario / Open Question',
];
$activitySubmissionTypes = [
    'text' => 'Text Response',
    'file' => 'File Upload',
    'both' => 'Text + File Upload',
];
?>
<?php if ($success === 'created'): ?>
<div class="alert alert-success">✔️ Lesson created with interactions &amp; activity.</div>
<?php elseif ($success === 'updated'): ?>
<div class="alert alert-success">✔️ Lesson updated.</div>
<?php elseif ($success === 'deleted'): ?>
<div class="alert alert-success">✔️ Lesson deleted.</div>
<?php elseif ($error === 'missing_fields'): ?>
<div class="alert alert-error">❌ Please fill in all required fields.</div>
<?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <div>
        <h2 style="color:var(--text);margin:0">Lesson Materials</h2>
        <p style="color:var(--muted);font-size:.85rem;margin:4px 0 0">Kenyan CBE — PP1 to Grade 12</p>
    </div>
    <button class="btn btn-primary" onclick="openCreateModal()" style="background:var(--primary);border:none">
        + Create New Lesson
    </button>
</div>

<!-- ══════════ LESSONS TABLE ══════════ -->
<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Title &amp; Outcomes</th>
                    <th>Level / Grade</th>
                    <th>Subject</th>
                    <th>Type</th>
                    <th style="text-align:center">🧠 Interactions</th>
                    <th style="text-align:center">📝 Activity</th>
                    <th style="width:100px;text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($lessons)): ?>
                <tr>
                    <td colspan="7" style="text-align:center;padding:50px;color:var(--muted)">
                        <div style="font-size:3rem;margin-bottom:12px">📖</div>
                        No lessons yet. Click <strong>+ Create New Lesson</strong> to start.
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($lessons as $l):
                        $level = $l['education_level'] ?? 'primary';
                        $hint  = $levelHints[$level] ?? $levelHints['primary'];
                        $badgeColor = '#64748b';
                        if ($l['type'] === 'video')  $badgeColor = '#ef4444';
                        if ($l['type'] === 'audio')  $badgeColor = '#f59e0b';
                        if ($l['type'] === 'slides') $badgeColor = '#8b5cf6';
                        if ($l['type'] === 'pdf')    $badgeColor = '#3b82f6';
                        if ($l['type'] === 'image')  $badgeColor = '#10b981';
                    ?>
                    <tr>
                        <td>
                            <div style="font-weight:600;color:var(--text)">
                                <?= htmlspecialchars($l['strand']) ?> 
                                <?php if (!empty($l['sub_strand'])): ?>
                                    <span style="font-weight:400;color:var(--muted);font-size:.85rem">/ <?= htmlspecialchars($l['sub_strand']) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if ($l['learning_outcomes']): ?>
                            <div style="font-size:.75rem;color:var(--muted);margin-top:3px">
                                🎯 <?= htmlspecialchars(substr($l['learning_outcomes'], 0, 80)) . (strlen($l['learning_outcomes']) > 80 ? '…' : '') ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span style="display:inline-block;padding:3px 8px;border-radius:20px;font-size:.72rem;font-weight:700;background:<?= $hint['color'] ?>22;color:<?= $hint['color'] ?>">
                                <?= $hint['icon'] ?> <?= htmlspecialchars($l['class_grade']) ?>
                            </span>
                        </td>
                        <td><span class="badge badge-info"><?= htmlspecialchars($l['subject']) ?></span></td>
                        <td>
                            <div style="display:inline-block;padding:3px 8px;border-radius:20px;font-size:.72rem;font-weight:700;color:white;background:<?= $badgeColor ?>">
                                <?= strtoupper($l['type']) ?>
                            </div>
                        </td>
                        <td style="text-align:center">
                            <?php $ic = (int)($l['interaction_count'] ?? 0); ?>
                            <span style="font-size:.85rem;font-weight:700;color:<?= $ic > 0 ? '#6366f1' : 'var(--muted)' ?>">
                                <?= $ic > 0 ? "✅ {$ic}" : '—' ?>
                            </span>
                        </td>
                        <td style="text-align:center">
                            <span style="font-size:.85rem;color:<?= ($l['has_activity'] ?? 0) ? '#10b981' : 'var(--muted)' ?>">
                                <?= ($l['has_activity'] ?? 0) ? '✅ Yes' : '—' ?>
                            </span>
                        </td>
                        <td style="text-align:right">
                            <div style="display:flex; flex-direction:column; gap:6px; align-items:flex-end">
                                <div class="btn-group">
                                    <a href="/CBE_LMS/public/index.php?url=teacher/viewDiscussions/<?= $l['id'] ?>" class="btn btn-outline btn-sm">🗣 Discussions</a>
                                    <button class="btn btn-outline btn-sm" onclick="openEditModal(<?= htmlspecialchars(json_encode($l)) ?>)">Edit</button>
                                    <a href="/CBE_LMS/public/index.php?url=teacher/deleteLesson/<?= $l['id'] ?>"
                                       class="btn btn-outline btn-sm" style="color:#ef4444;border-color:#ef4444"
                                       onclick="return confirm('Delete this lesson and all its interactions?')">Delete</a>
                                </div>
                                <?php if (($l['feedback_count'] ?? 0) > 0): ?>
                                    <a href="/CBE_LMS/public/index.php?url=teacher/viewLessonFeedback/<?= $l['id'] ?>" 
                                       style="font-size: .7rem; font-weight: 700; color: #4f46e5; text-decoration: none; display: flex; align-items: center; gap: 4px; background: #eef2ff; padding: 4px 8px; border-radius: 4px; border: 1px solid #c7d2fe;">
                                        💬 Admin Feedback <span style="background:#4f46e5; color:#fff; padding:1px 5px; border-radius:10px; font-size:.65rem"><?= $l['feedback_count'] ?></span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ══════════ CREATE LESSON MODAL ══════════ -->
<div id="createLessonModal" class="modal-overlay">
    <div class="modal" style="max-width:780px;width:95%">
        <div class="modal-header">
            <h3>📖 Create CBE Lesson</h3>
            <button class="modal-close" onclick="closeModal('createLessonModal')">×</button>
        </div>
        <form method="POST" action="/CBE_LMS/public/index.php?url=teacher/createLesson" enctype="multipart/form-data" id="createForm">
        <div class="modal-body" style="max-height:75vh;overflow-y:auto;padding:20px">

            <!-- STEP 1: GRADE & LEVEL -->
            <div style="background:linear-gradient(135deg,#0d9488,#6366f1);border-radius:12px;padding:16px;margin-bottom:20px;color:#fff">
                <div style="font-size:.75rem;font-weight:700;letter-spacing:.08em;opacity:.85;margin-bottom:8px">STEP 1 – KENYAN CBE GRADE</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div>
                        <label style="font-size:.8rem;opacity:.9;display:block;margin-bottom:4px">Education Level</label>
                        <select id="c_level" name="education_level" class="form-control" onchange="onLevelChange('c')" required>
                            <option value="">— Select Level —</option>
                            <option value="pre_primary">🎨 Pre-Primary (PP1–PP2)</option>
                            <option value="primary">📚 Primary (Grade 1–6)</option>
                            <option value="junior">🔬 Junior School (Grade 7–9)</option>
                            <option value="senior">🎓 Senior School (Grade 10–12)</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size:.8rem;opacity:.9;display:block;margin-bottom:4px">Grade</label>
                        <select id="c_grade" name="class_grade" class="form-control" required>
                            <option value="">— Select Grade —</option>
                        </select>
                    </div>
                </div>
                <div id="c_level_hint" style="background:rgba(255,255,255,.15);border-radius:8px;padding:10px;margin-top:10px;font-size:.8rem;display:none"></div>
            </div>

            <!-- STEP 2: LESSON BASICS & COMPETENCES -->
            <div style="font-size:.75rem;font-weight:700;letter-spacing:.08em;color:var(--muted);margin-bottom:10px">STEP 2 – LESSON DETAILS</div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:14px">
                <div class="form-group" style="margin:0">
                    <label class="form-label">Strand *</label>
                    <input type="text" name="strand" class="form-control" placeholder="e.g. Water Cycle" required>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Sub-strand</label>
                    <input type="text" name="sub_strand" class="form-control" placeholder="Optional">
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Subject *</label>
                    <input type="text" name="subject" class="form-control" placeholder="e.g. Science" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Learning Outcomes</label>
                <textarea name="learning_outcomes" class="form-control" rows="2"
                    placeholder="By the end of this lesson, students will be able to…"></textarea>
            </div>

            <div class="form-group" style="margin-top:14px">
                <label class="form-label" style="margin-bottom:8px;display:block">CBE Core Competences (Select at least 3) *</label>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;background:#f8fafc;padding:12px;border-radius:8px;border:1px solid #e2e8f0;">
                    <?php foreach ($coreCompetencesList as $cc): ?>
                        <label style="font-size:.85rem;display:flex;align-items:center;gap:6px;cursor:pointer">
                            <input type="checkbox" name="core_competences[]" value="<?= $cc['id'] ?>" class="c_comp_check">
                            <?= htmlspecialchars($cc['name']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- STEP 3: CBC LESSON STRUCTURE -->
            <div style="font-size:.75rem;font-weight:700;letter-spacing:.08em;color:var(--muted);margin:14px 0 10px">STEP 3 – LESSON STRUCTURE</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
                <div class="form-group" style="margin:0">
                    <label class="form-label">1. Introduction *</label>
                    <textarea name="introduction" class="form-control" rows="2" placeholder="Remind learners of a previous lesson..." required></textarea>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">2. Content Delivery *</label>
                    <textarea name="content_delivery" class="form-control" rows="2" placeholder="Teaching today's content..." required></textarea>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">5. Summary</label>
                    <textarea name="summary" class="form-control" rows="2" placeholder="Wrap up the lesson..."></textarea>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">6. Assignment</label>
                    <textarea name="assignment" class="form-control" rows="2" placeholder="Homework or further tasks..."></textarea>
                </div>
            </div>

            <!-- STEP 4: MULTIMEDIA -->
            <div style="font-size:.75rem;font-weight:700;letter-spacing:.08em;color:var(--muted);margin:14px 0 10px">STEP 4 – MULTIMEDIA CONTENT (MEDIA)</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
                <div class="form-group" style="margin:0">
                    <label class="form-label">Content Type</label>
                    <select name="type" class="form-control">
                        <option value="video">🎥 Video</option>
                        <option value="audio">🎧 Audio</option>
                        <option value="image">🖼️ Image</option>
                        <option value="slides">📊 Slides</option>
                        <option value="pdf">📄 PDF / Notes</option>
                        <option value="notes">📝 Text Notes</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Upload File</label>
                    <input type="file" name="lesson_file" class="form-control" style="font-size:.8rem">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">OR Paste External URL (YouTube, Drive, etc.)</label>
                <input type="text" name="content_url" class="form-control" placeholder="https://…">
            </div>
            <div class="form-group">
                <label class="form-label">Description / Notes</label>
                <textarea name="description" class="form-control" rows="2" placeholder="Brief overview…"></textarea>
            </div>

            <!-- STEP 5: ASSESSMENT INTERACTIONS -->
            <div style="font-size:.75rem;font-weight:700;letter-spacing:.08em;color:var(--muted);margin:14px 0 10px">STEP 5 – 4. ASSESSMENT (INTERACTIONS) 🧠</div>
            <div id="c_interactions_container"></div>
            <button type="button" onclick="addInteractionRow('c')" class="btn btn-outline btn-sm" style="margin-bottom:16px">
                + Add Interaction Question
            </button>

            <!-- STEP 6: ACTIVITY -->
            <div style="font-size:.75rem;font-weight:700;letter-spacing:.08em;color:var(--muted);margin:14px 0 10px">STEP 6 – 3. ACTIVITY TASK 📝</div>
            <div style="background:#f8fafc;border:1px solid var(--border);border-radius:10px;padding:14px">
                <div style="display:grid;grid-template-columns:2fr 1fr;gap:12px;margin-bottom:10px">
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Activity Title</label>
                        <input type="text" name="activity_title" class="form-control" value="Activity Task" placeholder="e.g. Home Experiment">
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Submission Type</label>
                        <select name="activity_submission_type" class="form-control">
                            <?php foreach ($activitySubmissionTypes as $v => $l): ?>
                            <option value="<?= $v ?>"><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:8px">
                    <label class="form-label">Task Description (what the student must do)</label>
                    <textarea name="activity_description" class="form-control" rows="2"
                        placeholder="e.g. Perform the experiment at home and record your observations…"></textarea>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Max Marks</label>
                    <input type="number" name="activity_max_marks" class="form-control" value="10" min="1" max="100" style="width:160px">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeModal('createLessonModal')">Cancel</button>
            <button type="submit" class="btn btn-primary">💾 Save Lesson</button>
        </div>
        </form>
    </div>
</div>

<!-- ══════════ EDIT LESSON MODAL ══════════ -->
<div id="editLessonModal" class="modal-overlay">
    <div class="modal" style="max-width:780px;width:95%">
        <div class="modal-header">
            <h3>✏️ Edit Lesson</h3>
            <button class="modal-close" onclick="closeModal('editLessonModal')">×</button>
        </div>
        <form method="POST" action="/CBE_LMS/public/index.php?url=teacher/updateLesson" enctype="multipart/form-data" id="editForm">
        <div class="modal-body" style="max-height:75vh;overflow-y:auto;padding:20px">
            <input type="hidden" name="id" id="edit_id">

            <div style="background:linear-gradient(135deg,#0d9488,#6366f1);border-radius:12px;padding:16px;margin-bottom:20px;color:#fff">
                <div style="font-size:.75rem;font-weight:700;letter-spacing:.08em;opacity:.85;margin-bottom:8px">KENYAN CBE GRADE</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div>
                        <label style="font-size:.8rem;opacity:.9;display:block;margin-bottom:4px">Education Level</label>
                        <select id="e_level" name="education_level" class="form-control" onchange="onLevelChange('e')">
                            <option value="pre_primary">🎨 Pre-Primary (PP1–PP2)</option>
                            <option value="primary">📚 Primary (Grade 1–6)</option>
                            <option value="junior">🔬 Junior School (Grade 7–9)</option>
                            <option value="senior">🎓 Senior School (Grade 10–12)</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size:.8rem;opacity:.9;display:block;margin-bottom:4px">Grade</label>
                        <select id="e_grade" name="class_grade" class="form-control"></select>
                    </div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:14px">
                <div class="form-group" style="margin:0">
                    <label class="form-label">Strand *</label>
                    <input type="text" name="strand" id="e_strand" class="form-control" required>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Sub-strand</label>
                    <input type="text" name="sub_strand" id="e_sub_strand" class="form-control">
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Subject *</label>
                    <input type="text" name="subject" id="e_subject" class="form-control" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Learning Outcomes</label>
                <textarea name="learning_outcomes" id="e_outcomes" class="form-control" rows="2"></textarea>
            </div>

            <div class="form-group" style="margin-top:14px">
                <label class="form-label" style="margin-bottom:8px;display:block">CBE Core Competences (Select at least 3) *</label>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;background:#f8fafc;padding:12px;border-radius:8px;border:1px solid #e2e8f0;">
                    <?php foreach ($coreCompetencesList as $cc): ?>
                        <label style="font-size:.85rem;display:flex;align-items:center;gap:6px;cursor:pointer">
                            <input type="checkbox" name="core_competences[]" value="<?= $cc['id'] ?>" class="e_comp_check">
                            <?= htmlspecialchars($cc['name']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="font-size:.75rem;font-weight:700;letter-spacing:.08em;color:var(--muted);margin:14px 0 10px">LESSON STRUCTURE</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
                <div class="form-group" style="margin:0">
                    <label class="form-label">1. Introduction *</label>
                    <textarea name="introduction" id="e_intro" class="form-control" rows="2" required></textarea>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">2. Content Delivery *</label>
                    <textarea name="content_delivery" id="e_content" class="form-control" rows="2" required></textarea>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">5. Summary</label>
                    <textarea name="summary" id="e_summary" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">6. Assignment</label>
                    <textarea name="assignment" id="e_assignment" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
                <div class="form-group" style="margin:0">
                    <label class="form-label">Content Type</label>
                    <select name="type" id="e_type" class="form-control">
                        <option value="video">🎥 Video</option>
                        <option value="audio">🎧 Audio</option>
                        <option value="image">🖼️ Image</option>
                        <option value="slides">📊 Slides</option>
                        <option value="pdf">📄 PDF</option>
                        <option value="notes">📝 Notes</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Replace File</label>
                    <input type="file" name="lesson_file" class="form-control" style="font-size:.8rem">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Content URL</label>
                <input type="text" name="content_url" id="e_url" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" id="e_description" class="form-control" rows="2"></textarea>
            </div>

            <div style="font-size:.75rem;font-weight:700;letter-spacing:.08em;color:var(--muted);margin:14px 0 10px">4. ASSESSMENT (INTERACTIONS) 🧠</div>
            <div id="e_interactions_container"></div>
            <button type="button" onclick="addInteractionRow('e')" class="btn btn-outline btn-sm" style="margin-bottom:16px">
                + Add Interaction
            </button>

            <div style="font-size:.75rem;font-weight:700;letter-spacing:.08em;color:var(--muted);margin:14px 0 10px">3. ACTIVITY TASK 📝</div>
            <div style="background:#f8fafc;border:1px solid var(--border);border-radius:10px;padding:14px">
                <div style="display:grid;grid-template-columns:2fr 1fr;gap:12px;margin-bottom:10px">
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Activity Title</label>
                        <input type="text" name="activity_title" id="e_act_title" class="form-control">
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Submission Type</label>
                        <select name="activity_submission_type" id="e_act_subtype" class="form-control">
                            <?php foreach ($activitySubmissionTypes as $v => $l): ?>
                            <option value="<?= $v ?>"><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:8px">
                    <label class="form-label">Task Description</label>
                    <textarea name="activity_description" id="e_act_desc" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Max Marks</label>
                    <input type="number" name="activity_max_marks" id="e_act_marks" class="form-control" value="10" style="width:160px">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeModal('editLessonModal')">Cancel</button>
            <button type="submit" class="btn btn-primary">💾 Update Lesson</button>
        </div>
        </form>
    </div>
</div>

<!-- Competencies JSON for JS -->
<script>
const ALL_COMPETENCIES = <?= json_encode($allCompetencies ?? []) ?>;
const GRADE_GROUPS = <?= json_encode($gradeGroups) ?>;
const LEVEL_HINTS  = <?= json_encode($levelHints) ?>;
const INTERACTION_TYPES = <?= json_encode($interactionTypes) ?>;

/* ─────────── Modal Helpers ─────────── */
function openCreateModal() {
    document.getElementById('createForm').reset();
    document.getElementById('c_interactions_container').innerHTML = '';
    document.getElementById('c_level_hint').style.display = 'none';
    document.getElementById('createLessonModal').classList.add('open');
}
function openEditModal(data) {
    document.getElementById('edit_id').value = data.id;
    document.getElementById('e_strand').value = data.strand;
    document.getElementById('e_sub_strand').value = data.sub_strand || '';
    document.getElementById('e_subject').value = data.subject;
    document.getElementById('e_outcomes').value = data.learning_outcomes || '';
    document.getElementById('e_type').value = data.type;
    document.getElementById('e_url').value = data.content_url || '';
    document.getElementById('e_description').value = data.description || '';
    
    document.getElementById('e_intro').value = data.introduction || '';
    document.getElementById('e_content').value = data.content_delivery || '';
    document.getElementById('e_summary').value = data.summary || '';
    document.getElementById('e_assignment').value = data.assignment || '';

    // Check core competences
    document.querySelectorAll('.e_comp_check').forEach(chk => {
        const val = parseInt(chk.value);
        chk.checked = data.core_competences && data.core_competences.includes(val);
    });

    const level = data.education_level || 'primary';
    document.getElementById('e_level').value = level;
    onLevelChange('e', data.class_grade);

    // Activity
    document.getElementById('e_act_title').value = '';
    document.getElementById('e_act_desc').value = '';
    document.getElementById('e_act_marks').value = 10;

    // Clear and reload interactions from server (fetched data already in lesson row)
    document.getElementById('e_interactions_container').innerHTML = '';
    if (data.interactions) {
        data.interactions.forEach(int => addInteractionRow('e', int));
    }
    if (data.activity) {
        document.getElementById('e_act_title').value = data.activity.title || '';
        document.getElementById('e_act_subtype').value = data.activity.submission_type || 'text';
        document.getElementById('e_act_desc').value = data.activity.description || '';
        document.getElementById('e_act_marks').value = data.activity.max_marks || 10;
    }

    document.getElementById('editLessonModal').classList.add('open');
}
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

document.getElementById('createForm').onsubmit = function(e) {
    if (document.querySelectorAll('.c_comp_check:checked').length < 3) {
        e.preventDefault();
        alert('Please select at least 3 CBE Core Competences.');
    }
};

document.getElementById('editForm').onsubmit = function(e) {
    if (document.querySelectorAll('.e_comp_check:checked').length < 3) {
        e.preventDefault();
        alert('Please select at least 3 CBE Core Competences.');
    }
};

/* ─────────── Grade Dropdown ─────────── */
function onLevelChange(prefix, selectedGrade = null) {
    const level = document.getElementById(prefix + '_level').value;
    const gradeSelect = document.getElementById(prefix + '_grade');
    gradeSelect.innerHTML = '<option value="">— Select Grade —</option>';

    // Map level to group name
    const groupMap = {
        pre_primary: 'Pre-Primary',
        primary: 'Primary',
        junior: 'Junior School',
        senior: 'Senior School'
    };
    const grades = GRADE_GROUPS[groupMap[level]] || [];

    grades.forEach(g => {
        const opt = document.createElement('option');
        opt.value = g; opt.textContent = g;
        if (g === selectedGrade) opt.selected = true;
        gradeSelect.appendChild(opt);
    });

    // Show hint
    if (prefix === 'c') {
        const hint = LEVEL_HINTS[level];
        const hintEl = document.getElementById('c_level_hint');
        if (hint && level) {
            hintEl.innerHTML = `<strong>${hint.icon} ${hint.label}</strong><br>${hint.tip}`;
            hintEl.style.display = 'block';
        } else {
            hintEl.style.display = 'none';
        }
    }
}

/* ─────────── Interaction Builder ─────────── */
let interactionCount = { c: 0, e: 0 };

function addInteractionRow(prefix, data = null) {
    const i = interactionCount[prefix]++;
    const container = document.getElementById(prefix + '_interactions_container');

    const typeOptions = Object.entries(INTERACTION_TYPES).map(([v, l]) =>
        `<option value="${v}" ${data && data.interaction_type === v ? 'selected' : ''}>${l}</option>`
    ).join('');

    const compOptions = '<option value="">— No Competency —</option>' +
        ALL_COMPETENCIES.map(c =>
            `<option value="${c.id}" ${data && data.competency_id == c.id ? 'selected' : ''}>${c.class_grade}: ${c.title} (${c.subject})</option>`
        ).join('');

    const optA = data?.options_arr?.[0]?.text || '';
    const optB = data?.options_arr?.[1]?.text || '';
    const optC = data?.options_arr?.[2]?.text || '';
    const optD = data?.options_arr?.[3]?.text || '';

    const html = `
    <div id="${prefix}_int_${i}" style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:10px;padding:14px;margin-bottom:12px;position:relative">
        <button type="button" onclick="this.parentElement.remove()"
            style="position:absolute;top:8px;right:10px;background:none;border:none;font-size:1.2rem;cursor:pointer;color:#94a3b8">✕</button>
        <div style="font-weight:700;font-size:.8rem;color:#0369a1;margin-bottom:10px">🧠 Interaction ${i + 1}</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:8px">
            <div>
                <label class="form-label" style="font-size:.75rem">Interaction Type</label>
                <select name="interaction_type[]" class="form-control" style="font-size:.82rem">${typeOptions}</select>
            </div>
            <div>
                <label class="form-label" style="font-size:.75rem">Maps to Competency</label>
                <select name="interaction_competency[]" class="form-control" style="font-size:.78rem">${compOptions}</select>
            </div>
        </div>
        <div class="form-group" style="margin-bottom:8px">
            <label class="form-label" style="font-size:.75rem">Question / Prompt *</label>
            <input type="text" name="interaction_question[]" class="form-control" value="${data ? escHtml(data.question) : ''}"
                placeholder="e.g. What is the capital of Kenya?" required>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px">
            <input type="text" name="interaction_option_a[]" class="form-control" value="${escHtml(optA)}" placeholder="Option A" style="font-size:.8rem">
            <input type="text" name="interaction_option_b[]" class="form-control" value="${escHtml(optB)}" placeholder="Option B" style="font-size:.8rem">
            <input type="text" name="interaction_option_c[]" class="form-control" value="${escHtml(optC)}" placeholder="Option C (optional)" style="font-size:.8rem">
            <input type="text" name="interaction_option_d[]" class="form-control" value="${escHtml(optD)}" placeholder="Option D (optional)" style="font-size:.8rem">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px">
            <div>
                <label class="form-label" style="font-size:.75rem">Correct Answer *</label>
                <input type="text" name="interaction_correct[]" class="form-control" value="${data ? escHtml(data.correct_answer) : ''}"
                    placeholder="e.g. a or Nairobi" required>
            </div>
            <div>
                <label class="form-label" style="font-size:.75rem">Marks</label>
                <input type="number" name="interaction_marks[]" class="form-control" value="${data?.marks || 1}" min="1" max="20">
            </div>
            <div>
                <label class="form-label" style="font-size:.75rem">Hint (optional)</label>
                <input type="text" name="interaction_hint[]" class="form-control" value="${data ? escHtml(data.hint || '') : ''}"
                    placeholder="Hint shown on wrong answer">
            </div>
        </div>
    </div>`;
    container.insertAdjacentHTML('beforeend', html);
}

function escHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>

</div><!-- .content -->
</div><!-- .main-wrapper -->
</body>
</html>
