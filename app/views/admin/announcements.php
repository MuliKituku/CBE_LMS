<?php
/* ── admin/announcements.php ──────────────────────────────────
   Send and manage system announcements
   Variables: $announcements (array)
   ─────────────────────────────────────────────────────────── */

$activeNav = 'announcements';
require BASE_PATH . '/app/views/admin/_sidebar.php';

$success = $_GET['success'] ?? '';
?>

<div style="max-width: 900px; margin: 0 auto; display: grid; grid-template-columns: 1fr 2fr; gap: 24px;">

    <!-- Left: Compose -->
    <div class="card">
        <div class="card-header">
            <h3>📢 Compose</h3>
        </div>
        <div class="card-body">
            <form action="/CBE_LMS/public/index.php?url=admin/announcements" method="POST">
                <div class="form-group mb-3">
                    <label>Title</label>
                    <input type="text" name="title" class="form-control" required placeholder="Announcement headline...">
                </div>
                <div class="form-group mb-3">
                    <label>Message</label>
                    <textarea name="message" class="form-control" rows="6" required placeholder="Write your announcement here..."></textarea>
                </div>
                <div class="form-group mb-3">
                    <label>Target Audience</label>
                    <select name="target" id="targetSelect" class="form-control" onchange="toggleUserSelection()">
                        <option value="all">All Users</option>
                        <option value="student">Students Only</option>
                        <option value="teacher">Teachers Only</option>
                        <option value="parent">Parents Only</option>
                        <option value="targeted">Specific Users</option>
                    </select>
                </div>

                <div id="userSelection" class="form-group mb-4" style="display:none;">
                    <label>Select Users</label>
                    <div style="max-height: 200px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px; background: #f8fafc;">
                        <input type="text" id="userSearchBar" class="form-control mb-2" placeholder="Search users..." onkeyup="filterUsers()">
                        <div id="userList">
                            <?php foreach ($users as $u): ?>
                                <?php if ($u['role'] === 'admin') continue; ?>
                                <label style="display: flex; align-items: center; gap: 8px; padding: 4px 0; cursor: pointer; font-size: 0.9rem;" class="user-item">
                                    <input type="checkbox" name="target_user_ids[]" value="<?= $u['id'] ?>">
                                    <span class="user-name"><?= htmlspecialchars($u['fullname']) ?></span>
                                    <span style="font-size: 0.7rem; color: #94a3b8;">(<?= ucfirst($u['role']) ?>)</span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-full">Post Announcement</button>
            </form>
        </div>
    </div>

    <!-- Right: History -->
    <div class="card">
        <div class="card-header">
            <h3>📜 Announcement History</h3>
        </div>
        <div class="card-body">
            <?php if (empty($announcements)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📭</div>
                    <p>No announcements yet.</p>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <?php foreach ($announcements as $a): ?>
                        <div style="padding: 16px; border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                                <h4 style="font-weight: 700; color: #1e293b;"><?= htmlspecialchars($a['title']) ?></h4>
                                <div style="text-align: right;">
                                    <span class="badge badge-info" style="font-size: .7rem;"><?= ucfirst($a['target_role']) ?></span>
                                    <?php if ($a['target_role'] === 'targeted' && !empty($a['recipients'])): ?>
                                        <div style="font-size: 0.7rem; color: #64748b; margin-top: 4px;">
                                            To: <?= implode(', ', array_map('htmlspecialchars', array_slice($a['recipients'], 0, 3))) ?>
                                            <?= count($a['recipients']) > 3 ? '...' : '' ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <p style="font-size: .9rem; color: #475569; margin-bottom: 12px; line-height: 1.5;"><?= nl2br(htmlspecialchars($a['message'])) ?></p>
                            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f1f5f9; padding-top: 10px; font-size: .8rem; color: #94a3b8;">
                                <span>Author: <strong><?= htmlspecialchars($a['author']) ?></strong></span>
                                <span><?= date('d M Y, H:i', strtotime($a['created_at'])) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
function toggleUserSelection() {
    const select = document.getElementById('targetSelect');
    const userDiv = document.getElementById('userSelection');
    userDiv.style.display = (select.value === 'targeted') ? 'block' : 'none';
}

function filterUsers() {
    const input = document.getElementById('userSearchBar');
    const filter = input.value.toLowerCase();
    const items = document.getElementsByClassName('user-item');
    
    for (let i = 0; i < items.length; i++) {
        const name = items[i].getElementsByClassName('user-name')[0].innerText;
        if (name.toLowerCase().indexOf(filter) > -1) {
            items[i].style.display = "";
        } else {
            items[i].style.display = "none";
        }
    }
}
</script>

</div><!-- .content -->
</div><!-- .main-wrapper -->
</body>
</html>
