<?php
$activeNav = 'classes';
require_once BASE_PATH . '/app/views/admin/_sidebar.php';
?>

<!-- Action Bar -->
<div class="action-bar">
    <div class="search-box">
        <input type="text" id="classSearch" placeholder="Search classes..." onkeyup="filterClasses()">
    </div>
    <button class="btn btn-primary" onclick="toggleModal('addClassModal')">+ Add New Class</button>
</div>

<!-- Main Grid -->
<div style="display: flex; flex-wrap: wrap; gap: 24px; margin-bottom: 30px;">
    <?php foreach ($classes as $class): ?>
        <div class="card class-card" style="flex: 1 1 380px; max-width: 500px; display: flex; flex-direction: column; border: none; box-shadow: var(--shadow); transition: transform 0.2s; border-radius: 16px;">
            <div style="padding: 24px; flex: 1;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
                            <span style="font-size: 1.5rem;">🏫</span>
                            <h3 style="margin: 0; font-size: 1.25rem; font-weight: 800; color: var(--text);">
                                <?= htmlspecialchars($class['name']) ?>
                            </h3>
                        </div>
                        <span style="font-size: 0.75rem; color: var(--muted); font-weight: 500; text-transform: uppercase; letter-spacing: 0.05em;">
                            ID: #<?= str_pad($class['id'], 3, '0', STR_PAD_LEFT) ?> • Created <?= date('M Y', strtotime($class['created_at'])) ?>
                        </span>
                    </div>
                    <div class="badge badge-info" style="padding: 6px 12px; font-size: 0.8rem; border-radius: 8px;">
                        <?= $class['student_count'] ?> Students
                    </div>
                </div>

                <div style="background: #f8fafc; border-radius: 12px; padding: 16px; border: 1px solid #f1f5f9;">
                    <label style="display: block; font-size: 0.72rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 12px; letter-spacing: 0.05em;">
                        Assigned Teachers
                    </label>
                    <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                        <?php if ($class['teachers']): 
                            $teachersList = explode(',', $class['teachers']);
                            foreach ($teachersList as $t): ?>
                                <div style="background: #fff; color: var(--text); padding: 6px 12px; border-radius: 8px; font-size: 0.82rem; font-weight: 500; border: 1px solid var(--border); box-shadow: 0 1px 2px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 6px;">
                                    <div style="width: 8px; height: 8px; background: var(--success); border-radius: 50%;"></div>
                                    <?= htmlspecialchars($t) ?>
                                </div>
                            <?php endforeach; 
                        else: ?>
                            <div style="color: #94a3b8; font-style: italic; font-size: 0.85rem; padding: 4px 0;">No teachers assigned yet</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div style="padding: 16px 24px; background: #f8fafc; border-top: 1px solid #f1f5f9; display: flex; gap: 12px;">
                <button class="btn btn-primary" style="flex: 1; justify-content: center; box-shadow: 0 4px 6px -1px rgba(79,70,229,0.2);" 
                        onclick="openAssignModal(<?= $class['id'] ?>, '<?= htmlspecialchars($class['name']) ?>')">
                    Assign Teacher
                </button>
                <button class="btn btn-outline" style="padding: 8px 12px; border-color: #e2e8f0; background: #fff;">
                    <span>⚙️</span>
                </button>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Add Class Modal -->
<div id="addClassModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add New Class / Grade</h3>
            <span class="close" onclick="toggleModal('addClassModal')">&times;</span>
        </div>
        <form action="/CBE_LMS/public/index.php?url=admin/addClass" method="POST" style="margin-top: 20px;">
            <div class="form-group">
                <label>Class Name</label>
                <input type="text" name="name" placeholder="e.g. Grade 4 West" required style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
            </div>
            <div style="margin-top: 24px; display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="toggleModal('addClassModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Class</button>
            </div>
        </form>
    </div>
</div>

<!-- Assign Teacher Modal -->
<div id="assignTeacherModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Assign Teacher to <span id="targetClassName"></span></h3>
            <span class="close" onclick="toggleModal('assignTeacherModal')">&times;</span>
        </div>
        <form action="/CBE_LMS/public/index.php?url=admin/assignTeacher" method="POST" style="margin-top: 20px;">
            <input type="hidden" name="class_id" id="targetClassId">
            <div class="form-group">
                <label>Select Teacher</label>
                <select name="teacher_id" required style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff;">
                    <option value="">-- Choose a teacher --</option>
                    <?php foreach ($teachers as $teacher): ?>
                        <option value="<?= $teacher['id'] ?>"><?= htmlspecialchars($teacher['fullname']) ?> (<?= htmlspecialchars($teacher['email']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="margin-top: 24px; display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="toggleModal('assignTeacherModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Assignment</button>
            </div>
        </form>
    </div>
</div>

<style>
.modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); }
.modal-content { background: #fff; margin: 10% auto; padding: 24px; border-radius: 16px; width: 400px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
.modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; }
.close { cursor: pointer; font-size: 1.5rem; color: #94a3b8; }
.btn-outline { background: transparent; border: 1px solid #e2e8f0; color: #64748b; border-radius: 8px; cursor: pointer; transition: all 0.2s; }
.btn-outline:hover { background: #f8fafc; color: #1e293b; }
</style>

<script>
function toggleModal(id) {
    const m = document.getElementById(id);
    m.style.display = (m.style.display === 'block') ? 'none' : 'block';
}

function openAssignModal(id, name) {
    document.getElementById('targetClassId').value = id;
    document.getElementById('targetClassName').textContent = name;
    toggleModal('assignTeacherModal');
}

function filterClasses() {
    const input = document.getElementById('classSearch').value.toLowerCase();
    const cards = document.querySelectorAll('.class-card');
    cards.forEach(card => {
        const text = card.textContent.toLowerCase();
        card.style.display = text.includes(input) ? 'block' : 'none';
    });
}
</script>

<?php require_once __DIR__ . '/_footer.php'; ?>
