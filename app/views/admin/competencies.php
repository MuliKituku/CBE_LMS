<?php
$activeNav = 'competencies';
require_once BASE_PATH . '/app/views/admin/_sidebar.php';
?>

<div class="content-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
    <h2>🎯 Manage Kenyan CBE Competencies</h2>
    <button class="btn btn-primary" onclick="openCreateModal()">+ Add Competency</button>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success" style="padding:15px;background:#dcfce7;color:#166534;border-radius:8px;margin-bottom:20px;">
        ✅ Action completed successfully.
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger" style="padding:15px;background:#fee2e2;color:#991b1b;border-radius:8px;margin-bottom:20px;">
        ❌ <?= htmlspecialchars($_GET['error'] === 'invalid_data' ? 'Please fill all required fields.' : 'An error occurred.') ?>
    </div>
<?php endif; ?>

<div class="card" style="box-shadow:0 4px 6px -1px rgba(0,0,0,0.1);border-radius:12px;overflow:hidden">
    <div class="card-body" style="padding:0">
        <table style="width:100%;border-collapse:collapse;text-align:left">
            <thead style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                <tr>
                    <th style="padding:16px;font-size:.85rem;color:#64748b;font-weight:600">Level & Grade</th>
                    <th style="padding:16px;font-size:.85rem;color:#64748b;font-weight:600">Subject</th>
                    <th style="padding:16px;font-size:.85rem;color:#64748b;font-weight:600">Competency Title</th>
                    <th style="padding:16px;font-size:.85rem;color:#64748b;font-weight:600">Interaction</th>
                    <th style="padding:16px;font-size:.85rem;color:#64748b;font-weight:600;text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($competencies)): ?>
                    <tr><td colspan="5" style="padding:24px;text-align:center;color:#94a3b8">No competencies found in the database. Add one to get started.</td></tr>
                <?php else: ?>
                    <?php foreach ($competencies as $c): ?>
                    <tr style="border-bottom:1px solid #f1f5f9;transition:background 0.2s">
                        <td style="padding:16px;">
                            <span style="display:inline-block;padding:4px 8px;background:#e0e7ff;color:#4f46e5;border-radius:20px;font-size:.75rem;font-weight:600;margin-bottom:4px">
                                <?= htmlspecialchars(ucwords(str_replace('_', ' ', $c['education_level']))) ?>
                            </span>
                            <br><strong style="color:#1e293b"><?= htmlspecialchars($c['class_grade']) ?></strong>
                        </td>
                        <td style="padding:16px;font-weight:500;color:#334155"><?= htmlspecialchars($c['subject']) ?></td>
                        <td style="padding:16px;">
                            <div style="font-weight:600;color:#0f172a;margin-bottom:4px"><?= htmlspecialchars($c['title']) ?></div>
                            <div style="font-size:.85rem;color:#64748b;line-height:1.4"><?= htmlspecialchars($c['description']) ?></div>
                        </td>
                        <td style="padding:16px;">
                            <span style="font-family:monospace;background:#f1f5f9;color:#475569;padding:4px 6px;border-radius:4px;font-size:.8rem">
                                <?= htmlspecialchars($c['mapped_interaction']) ?>
                            </span>
                        </td>
                        <td style="padding:16px;text-align:right">
                            <button class="btn btn-outline btn-sm" onclick="openEditModal(<?= htmlspecialchars(json_encode($c)) ?>)">Edit</button>
                            <form method="POST" action="/CBE_LMS/public/index.php?url=admin/deleteCompetency" style="display:inline-block;margin-left:4px" onsubmit="return confirm('Delete this competency forever?')">
                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                <button type="submit" class="btn btn-outline btn-sm" style="color:#dc2626;border-color:#fca5a5">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- CREATE / EDIT MODAL -->
<div id="compModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.6);backdrop-filter:blur(4px);z-index:999;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:12px;width:100%;max-width:600px;box-shadow:0 20px 25px -5px rgba(0,0,0,0.1);overflow:hidden">
        <div style="padding:20px 24px;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center">
            <h3 id="modalTitle" style="margin:0;font-size:1.25rem;color:#0f172a">Add Competency</h3>
            <button onclick="closeModal()" style="background:none;border:none;font-size:1.5rem;color:#94a3b8;cursor:pointer;line-height:1">&times;</button>
        </div>
        <form id="compForm" method="POST" action="/CBE_LMS/public/index.php?url=admin/createCompetency" style="padding:24px">
            <input type="hidden" name="id" id="compId">
            
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
                <div>
                    <label style="display:block;margin-bottom:6px;font-size:.85rem;font-weight:600;color:#334155">Education Level *</label>
                    <select name="education_level" id="compLevel" style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:6px" required onchange="populateGrades()">
                        <option value="">-- Select Level --</option>
                        <option value="pre_primary">Pre-Primary (PP1 - PP2)</option>
                        <option value="primary">Primary (Grade 1 - 6)</option>
                        <option value="junior">Junior School (Grade 7 - 9)</option>
                        <option value="senior">Senior School (Grade 10 - 12)</option>
                    </select>
                </div>
                <div>
                    <label style="display:block;margin-bottom:6px;font-size:.85rem;font-weight:600;color:#334155">Grade *</label>
                    <select name="class_grade" id="compGrade" style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:6px" required>
                        <option value="">-- Select Grade --</option>
                    </select>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
                <div>
                    <label style="display:block;margin-bottom:6px;font-size:.85rem;font-weight:600;color:#334155">Subject *</label>
                    <input type="text" name="subject" id="compSubject" placeholder="e.g. Mathematics" style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:6px" required>
                </div>
                <div>
                    <label style="display:block;margin-bottom:6px;font-size:.85rem;font-weight:600;color:#334155">Mapped Interaction *</label>
                    <select name="mapped_interaction" id="compInteraction" style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:6px" required>
                        <option value="mcq">Multiple Choice</option>
                        <option value="click_image">Clickable Image</option>
                        <option value="drag_drop">Drag and Drop</option>
                        <option value="fill_blank">Fill in the Blank</option>
                        <option value="true_false">True / False</option>
                        <option value="scenario">Scenario / Problem Solving</option>
                        <option value="research_upload">Research & Upload</option>
                        <option value="group_task">Group Task</option>
                        <option value="presentation">Presentation</option>
                    </select>
                </div>
            </div>

            <div style="margin-bottom:16px">
                <label style="display:block;margin-bottom:6px;font-size:.85rem;font-weight:600;color:#334155">Competency Title *</label>
                <input type="text" name="title" id="compTitle" placeholder="e.g. Number Sense (1-10)" style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:6px" required>
            </div>

            <div style="margin-bottom:24px">
                <label style="display:block;margin-bottom:6px;font-size:.85rem;font-weight:600;color:#334155">Description</label>
                <textarea name="description" id="compDesc" rows="3" placeholder="Brief explanation of what the student should achieve..." style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:6px;resize:vertical"></textarea>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:12px">
                <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="compSubmitBtn">Save Competency</button>
            </div>
        </form>
    </div>
</div>

<script>
const GradeMap = {
    'pre_primary': ['PP1', 'PP2'],
    'primary': ['Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'],
    'junior': ['Grade 7', 'Grade 8', 'Grade 9'],
    'senior': ['Grade 10', 'Grade 11', 'Grade 12']
};

function populateGrades(selectedGrade = null) {
    const level = document.getElementById('compLevel').value;
    const gradeSelect = document.getElementById('compGrade');
    gradeSelect.innerHTML = '<option value="">-- Select Grade --</option>';
    
    if (GradeMap[level]) {
        GradeMap[level].forEach(g => {
            const opt = document.createElement('option');
            opt.value = g;
            opt.textContent = g;
            if (selectedGrade && g === selectedGrade) opt.selected = true;
            gradeSelect.appendChild(opt);
        });
    }
}

function openCreateModal() {
    document.getElementById('compForm').action = "/CBE_LMS/public/index.php?url=admin/createCompetency";
    document.getElementById('modalTitle').textContent = "Add Kenyan CBE Competency";
    document.getElementById('compSubmitBtn').textContent = "Save Competency";
    
    document.getElementById('compId').value = "";
    document.getElementById('compLevel').value = "";
    document.getElementById('compSubject').value = "";
    document.getElementById('compTitle').value = "";
    document.getElementById('compInteraction').value = "mcq";
    document.getElementById('compDesc').value = "";
    populateGrades();
    
    document.getElementById('compModal').style.display = "flex";
}

function openEditModal(c) {
    document.getElementById('compForm').action = "/CBE_LMS/public/index.php?url=admin/editCompetency";
    document.getElementById('modalTitle').textContent = "Edit Competency";
    document.getElementById('compSubmitBtn').textContent = "Update Competency";
    
    document.getElementById('compId').value = c.id;
    document.getElementById('compLevel').value = c.education_level;
    document.getElementById('compSubject').value = c.subject;
    document.getElementById('compTitle').value = c.title;
    document.getElementById('compInteraction').value = c.mapped_interaction;
    document.getElementById('compDesc').value = c.description;
    
    populateGrades(c.class_grade);
    
    document.getElementById('compModal').style.display = "flex";
}

function closeModal() {
    document.getElementById('compModal').style.display = "none";
}
</script>

<?php require_once BASE_PATH . '/app/views/admin/_footer.php'; ?>
