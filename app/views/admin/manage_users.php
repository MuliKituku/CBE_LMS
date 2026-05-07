<?php
/* ── admin/manage_users.php ─────────────────────────────────
   Variables: $users (array), $search (string), $roleFilter (string)
   ─────────────────────────────────────────────────────────── */

$activeNav = 'users';
require_once BASE_PATH . '/app/models/User.php';
require BASE_PATH . '/app/views/admin/_sidebar.php';

$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';

$search     = htmlspecialchars($_GET['search']     ?? '');
$roleFilter = htmlspecialchars($_GET['role_filter'] ?? '');

// Open Create Teacher modal if ?action=create_teacher
$openCreateModal = ($_GET['action'] ?? '') === 'create_teacher';

$roleBadgeMap = [
    'admin'   => 'badge-info',
    'teacher' => 'badge-success',
    'student' => 'badge-pending',
    'parent'  => 'badge-muted',
];
$statusBadgeMap = [
    'active'   => 'badge-success',
    'approved' => 'badge-success',
    'pending'  => 'badge-pending',
    'rejected' => 'badge-danger',
    'inactive' => 'badge-inactive',
];
?>

<?php if ($success === 'teacher_created'): ?>
<div class="alert alert-success">✅ Teacher account created and welcome email sent.</div>
<?php elseif ($success === 'deactivated'): ?>
<div class="alert alert-info">ℹ️ User has been deactivated.</div>
<?php elseif ($success === 'reactivated'): ?>
<div class="alert alert-success">✅ User has been reactivated.</div>
<?php elseif ($success === 'password_reset'): ?>
<div class="alert alert-success">🔑 Password reset. New credentials emailed to the user.</div>
<?php elseif ($error): ?>
<div class="alert alert-error">❌ An error occurred: <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Toolbar -->
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
    <h2 style="font-size:1.05rem;font-weight:700;">
        All Users <span class="badge badge-info" style="margin-left:6px;font-size:.8rem;"><?= count($users) ?></span>
    </h2>
    <button class="btn btn-primary" id="openCreateModal">➕ Create Teacher</button>
</div>

<!-- Search / Filter -->
<form method="GET" action="/CBE_LMS/public/index.php" class="search-bar">
    <input type="hidden" name="url" value="admin/manageUsers">
    <input type="text" name="search" class="form-control" placeholder="🔍  Search name, email or reg no…"
           value="<?= $search ?>">
    <select name="role_filter" class="form-control" style="max-width:160px;">
        <option value="">All Roles</option>
        <option value="admin"   <?= $roleFilter === 'admin'   ? 'selected' : '' ?>>Admin</option>
        <option value="teacher" <?= $roleFilter === 'teacher' ? 'selected' : '' ?>>Teacher</option>
        <option value="student" <?= $roleFilter === 'student' ? 'selected' : '' ?>>Student</option>
        <option value="parent"  <?= $roleFilter === 'parent'  ? 'selected' : '' ?>>Parent</option>
    </select>
    <button type="submit" class="btn btn-primary">Filter</button>
    <?php if ($search || $roleFilter): ?>
        <a href="/CBE_LMS/public/index.php?url=admin/manageUsers" class="btn btn-outline">Clear</a>
    <?php endif; ?>
</form>

<!-- Users Table -->
<div class="card">
    <?php if (empty($users)): ?>
        <div class="empty-state">
            <div class="empty-icon">🔍</div>
            <h3>No Users Found</h3>
            <p>Try adjusting your search or filter.</p>
        </div>
    <?php else: ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>User</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Reg No</th>
                    <th>Joined</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $i => $u): ?>
            <?php
                $roleClass   = $roleBadgeMap[$u['role']]     ?? 'badge-muted';
                $statusClass = $statusBadgeMap[$u['status']] ?? 'badge-muted';
                $joined      = $u['created_at'] ? date('d M Y', strtotime($u['created_at'])) : 'N/A';
                $isCurrentAdmin = (int)$u['id'] === (int)($_SESSION['user']['id'] ?? 0);
            ?>
            <tr>
                <td style="color:var(--muted);font-size:.8rem;"><?= $i + 1 ?></td>
                <td>
                    <div class="user-cell">
                        <div class="user-avatar" style="font-size:.75rem;">
                            <?= strtoupper(substr($u['fullname'], 0, 2)) ?>
                        </div>
                        <div>
                            <div class="name"><?= htmlspecialchars($u['fullname']) ?></div>
                            <div class="email"><?= htmlspecialchars($u['email']) ?></div>
                        </div>
                    </div>
                </td>
                <td><span class="badge <?= $roleClass ?>"><?= ucfirst($u['role']) ?></span></td>
                <td><span class="badge <?= $statusClass ?>"><?= ucfirst($u['status']) ?></span></td>
                <td style="font-family:monospace;font-size:.82rem;"><?= htmlspecialchars($u['student_reg_no'] ?? '—') ?></td>
                <td style="color:var(--muted);font-size:.82rem;"><?= $joined ?></td>
                <td>
                    <?php if (!$isCurrentAdmin): ?>
                    <div class="btn-group" style="justify-content:flex-end">

                        <!-- Reset Password -->
                        <form method="POST" action="/CBE_LMS/public/index.php?url=admin/resetPassword"
                              onsubmit="return confirm('Reset password for <?= addslashes(htmlspecialchars($u['fullname'])) ?>?\nNew credentials will be emailed.')">
                            <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                            <button type="submit" class="btn btn-warning btn-sm" title="Reset Password">🔑 Reset</button>
                        </form>

                        <!-- Deactivate / Reactivate -->
                        <?php if (in_array($u['status'], ['active', 'approved'])): ?>
                        <form method="POST" action="/CBE_LMS/public/index.php?url=admin/deactivateUser"
                              onsubmit="return confirm('Deactivate <?= addslashes(htmlspecialchars($u['fullname'])) ?>?')">
                            <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                            <input type="hidden" name="action"  value="deactivate">
                            <button type="submit" class="btn btn-danger btn-sm">⛔ Deactivate</button>
                        </form>
                        <?php elseif ($u['status'] === 'inactive'): ?>
                        <form method="POST" action="/CBE_LMS/public/index.php?url=admin/deactivateUser"
                              onsubmit="return confirm('Reactivate <?= addslashes(htmlspecialchars($u['fullname'])) ?>?')">
                            <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                            <input type="hidden" name="action"  value="reactivate">
                            <button type="submit" class="btn btn-success btn-sm">✅ Reactivate</button>
                        </form>
                        <?php endif; ?>

                    </div>
                    <?php else: ?>
                        <span class="text-muted text-sm">You (current admin)</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

</div><!-- .content -->
</div><!-- .main-wrapper -->

<!-- ═══════════ CREATE TEACHER MODAL ═══════════ -->
<div class="modal-overlay" id="createTeacherModal">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="modal-header">
            <h3 id="modalTitle">➕ Create Teacher Account</h3>
            <button class="modal-close" id="closeModal" aria-label="Close">✕</button>
        </div>
        <form method="POST" action="/CBE_LMS/public/index.php?url=admin/createTeacher">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="teacher_name">Full Name *</label>
                    <input type="text" id="teacher_name" name="fullname" class="form-control"
                           placeholder="e.g. Jane Wanjiru" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="teacher_email">Email Address *</label>
                    <input type="email" id="teacher_email" name="email" class="form-control"
                           placeholder="teacher@school.ac.ke" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="teacher_tsc">TSC Number</label>
                    <input type="text" id="teacher_tsc" name="tsc_number" class="form-control"
                           placeholder="e.g. 123456">
                </div>
                <div class="form-group">
                    <label class="form-label" for="teacher_spec">Specialization</label>
                    <input type="text" id="teacher_spec" name="specialization" class="form-control"
                           placeholder="e.g. Mathematics, Sciences">
                </div>
                <div class="form-group">
                    <label class="form-label" for="teacher_phone">Phone Number</label>
                    <input type="tel" id="teacher_phone" name="phone" class="form-control"
                           placeholder="e.g. 0712345678">
                </div>
                <div class="alert alert-info" style="margin-bottom:0">
                    ℹ️ A temporary password will be generated and emailed to the teacher. They will be required to change it on first login.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" id="cancelModal">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Teacher →</button>
            </div>
        </form>
    </div>
</div>

<script>
const modal       = document.getElementById('createTeacherModal');
const openBtn     = document.getElementById('openCreateModal');
const closeBtn    = document.getElementById('closeModal');
const cancelBtn   = document.getElementById('cancelModal');

function openModal()  { modal.classList.add('open'); }
function closeModal() { modal.classList.remove('open'); }

if (openBtn)   openBtn.addEventListener('click', openModal);
if (closeBtn)  closeBtn.addEventListener('click', closeModal);
if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });

// Auto-open if redirected with action=create_teacher
<?php if ($openCreateModal): ?>openModal();<?php endif; ?>
</script>
</body>
</html>
