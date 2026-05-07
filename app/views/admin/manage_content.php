<?php
$activeNav = 'content';
require_once BASE_PATH . '/app/views/admin/_sidebar.php';
?>

<div class="action-bar" style="margin-bottom: 24px;">
    <div class="search-box" style="flex: 1;">
        <input type="text" id="lessonSearch" placeholder="Search lessons by strand, subject or teacher..." onkeyup="filterLessons()" style="width: 100%;">
    </div>
</div>

<div class="table-container">
    <table class="data-table" id="lessonsTable">
        <thead>
            <tr>
                <th>Lesson Strand</th>
                <th>Subject</th>
                <th>Class</th>
                <th>Teacher</th>
                <th>Status</th>
                <th>Posted On</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lessons as $lesson): ?>
                <tr>
                    <td>
                        <div style="font-weight: 600; color: #1e293b;"><?= htmlspecialchars($lesson['strand']) ?></div>
                        <div style="font-size: 0.75rem; color: #94a3b8;"><?= strtoupper($lesson['type']) ?></div>
                    </td>
                    <td><?= htmlspecialchars($lesson['subject']) ?></td>
                    <td><span class="badge" style="background:#f1f5f9; color:#475569;"><?= htmlspecialchars($lesson['class_grade']) ?></span></td>
                    <td><?= htmlspecialchars($lesson['teacher_name']) ?></td>
                    <td>
                        <?php if ($lesson['is_published']): ?>
                            <span style="display:flex; align-items:center; gap:6px; color:#10b981; font-weight:600; font-size:0.85rem;">
                                <span style="width:8px; height:8px; background:#10b981; border-radius:50%;"></span> Published
                            </span>
                        <?php else: ?>
                            <span style="display:flex; align-items:center; gap:6px; color:#f59e0b; font-weight:600; font-size:0.85rem;">
                                <span style="width:8px; height:8px; background:#f59e0b; border-radius:50%;"></span> Hidden
                            </span>
                        <?php endif; ?>
                    </td>
                    <td style="color: #64748b; font-size: 0.9rem;"><?= date('d M Y', strtotime($lesson['created_at'])) ?></td>
                    <td>
                            <a href="/CBE_LMS/public/index.php?url=admin/viewLesson&id=<?= $lesson['id'] ?>" class="btn btn-primary" style="padding: 6px 10px; font-size: 0.8rem; text-decoration: none; display: flex; align-items: center; justify-content: center;">
                                View
                            </a>
                            <form action="/CBE_LMS/public/index.php?url=admin/toggleLessonStatus" method="POST" style="display:inline;">
                                <input type="hidden" name="lesson_id" value="<?= $lesson['id'] ?>">
                                <input type="hidden" name="status" value="<?= $lesson['is_published'] ? 0 : 1 ?>">
                                <button type="submit" class="btn btn-outline" style="padding: 6px 10px; font-size: 0.8rem; width: 100%;">
                                    <?= $lesson['is_published'] ? 'Hide' : 'Publish' ?>
                                </button>
                            </form>
                            <button class="btn btn-danger" onclick="confirmDelete(<?= $lesson['id'] ?>)" style="padding: 6px 10px; font-size: 0.8rem; background: #fee2e2; color: #dc2626; border: none; width: 100%;">
                                Delete
                            </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal" style="display: none;">
    <div class="modal-content" style="text-align: center;">
        <div style="font-size: 3rem; margin-bottom: 16px;">⚠️</div>
        <h3>Delete Lesson?</h3>
        <p style="color: #64748b; margin: 12px 0 24px;">This action cannot be undone. The lesson and all associated discussion posts will be removed permanently.</p>
        <form id="deleteForm" action="/CBE_LMS/public/index.php?url=admin/removeLesson" method="POST">
            <input type="hidden" name="lesson_id" id="deleteLessonId">
            <div style="display: flex; gap: 12px; justify-content: center;">
                <button type="button" class="btn btn-secondary" onclick="toggleModal('deleteModal')">Cancel</button>
                <button type="submit" class="btn btn-danger" style="background: #dc2626;">Yes, Delete</button>
            </div>
        </form>
    </div>
</div>

<script>
function filterLessons() {
    const input = document.getElementById('lessonSearch').value.toLowerCase();
    const rows = document.querySelectorAll('#lessonsTable tbody tr');
    rows.forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(input) ? '' : 'none';
    });
}

function confirmDelete(id) {
    document.getElementById('deleteLessonId').value = id;
    toggleModal('deleteModal');
}

function toggleModal(id) {
    const m = document.getElementById(id);
    m.style.display = (m.style.display === 'block') ? 'none' : 'block';
}
</script>

<?php require_once __DIR__ . '/_footer.php'; ?>
