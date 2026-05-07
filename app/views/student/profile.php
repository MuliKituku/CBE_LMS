<?php
/* ── student/profile.php ────────────────────────────────────
   Variables: $profile
   ─────────────────────────────────────────────────────────── */
$activeNav = 'profile';
require BASE_PATH . '/app/views/student/_sidebar.php';

$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';
?>

<?php if ($success === 'updated'): ?>
<div class="alert alert-success">✅ Profile updated successfully!</div>
<?php elseif ($error === 'failed'): ?>
<div class="alert alert-error">❌ Failed to update profile. Please try again.</div>
<?php endif; ?>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header">
        <h3>👤 My Personal Profile</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="/CBE_LMS/public/index.php?url=student/updateProfile">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 0.9rem; margin-bottom: 8px; display: block;">Full Name</label>
                    <input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($profile['fullname']) ?>" required>
                </div>
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 0.9rem; margin-bottom: 8px; display: block;">Email Address</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($profile['email']) ?>" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 0.9rem; margin-bottom: 8px; display: block;">Gender</label>
                    <select name="gender" class="form-control">
                        <option value="Male" <?= ($profile['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= ($profile['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                        <option value="Other" <?= ($profile['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 0.9rem; margin-bottom: 8px; display: block;">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control" value="<?= $profile['date_of_birth'] ?>">
                </div>
            </div>

            <div style="padding: 15px; background: #f8fafc; border-radius: 8px; margin-bottom: 24px;">
                <div style="display: flex; gap: 20px;">
                    <div>
                        <span style="font-size: 0.8rem; color: var(--muted); display: block;">Registration Number</span>
                        <strong style="color: var(--primary);"><?= htmlspecialchars($profile['student_reg_no'] ?? 'N/A') ?></strong>
                    </div>
                    <div>
                        <span style="font-size: 0.8rem; color: var(--muted); display: block;">Current Grade</span>
                        <strong>Grade <?= htmlspecialchars($profile['class_grade']) ?></strong>
                    </div>
                    <div>
                        <span style="font-size: 0.8rem; color: var(--muted); display: block;">Account Status</span>
                        <span class="badge <?= $profile['status'] === 'active' ? 'badge-success' : 'badge-warning' ?>">
                            <?= ucfirst($profile['status']) ?>
                        </span>
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <button type="reset" class="btn btn-outline">Discard Changes</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

</div><!-- .content -->
</div><!-- .main-wrapper -->
</body>
</html>
