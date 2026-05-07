<?php
/* ── admin/pending_enrollments.php ──────────────────────────
   Variables: $enrollments (array from User::getPendingEnrollments)
   ─────────────────────────────────────────────────────────── */

$activeNav = 'enrollments';
require_once BASE_PATH . '/app/models/User.php';
require BASE_PATH . '/app/views/admin/_sidebar.php';

$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';
?>

<?php if ($success === 'approved'): ?>
<div class="alert alert-success">✅ Enrollment approved! Login credentials have been emailed to the student and parent.</div>
<?php elseif ($success === 'rejected'): ?>
<div class="alert alert-info">ℹ️ Enrollment rejected. The applicant has been notified by email.</div>
<?php elseif ($error === 'invalid'): ?>
<div class="alert alert-error">❌ Invalid request. Please try again.</div>
<?php elseif ($error === 'failed'): ?>
<div class="alert alert-error">❌ Approval failed. Check the error log and try again.</div>
<?php endif; ?>

<!-- Summary bar -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px;">
    <div>
        <span class="badge badge-pending" style="font-size:.85rem;padding:6px 14px;">
            ⏳ <?= count($enrollments) ?> Pending <?= count($enrollments) === 1 ? 'Request' : 'Requests' ?>
        </span>
    </div>
    <a href="/CBE_LMS/public/index.php?url=admin/dashboard" class="btn btn-outline btn-sm">
        ← Back to Dashboard
    </a>
</div>

<?php if (empty($enrollments)): ?>
    <div class="card">
        <div class="empty-state">
            <div class="empty-icon">🎉</div>
            <h3>No Pending Enrollments</h3>
            <p>All enrollment requests have been processed. Check back later.</p>
        </div>
    </div>

<?php else: ?>

    <?php foreach ($enrollments as $e): ?>
    <?php
        $gender  = ucfirst($e['gender'] ?? 'N/A');
        $dob     = $e['date_of_birth'] ? date('d M Y', strtotime($e['date_of_birth'])) : 'N/A';
        $applied = $e['created_at']    ? date('d M Y', strtotime($e['created_at']))    : 'N/A';
    ?>
    <div class="card" style="margin-bottom:18px;">
        <div class="card-header">
            <div class="user-cell">
                <div class="user-avatar"><?= strtoupper(substr($e['student_name'], 0, 1)) ?></div>
                <div>
                    <div class="name"><?= htmlspecialchars($e['student_name']) ?></div>
                    <div class="email"><?= htmlspecialchars($e['student_email']) ?></div>
                </div>
            </div>
            <span class="badge badge-pending">Pending Review</span>
        </div>

        <div class="card-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">

                <!-- Student Info -->
                <div>
                    <p style="font-size:.72rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px;">Student Details</p>
                    <table style="font-size:.85rem;width:100%;">
                        <tr><td style="padding:5px 10px 5px 0;color:var(--muted);width:130px;">Class / Grade</td>
                            <td style="padding:5px 0;font-weight:600"><?= htmlspecialchars($e['class_grade'] ?? 'N/A') ?></td></tr>
                        <tr><td style="padding:5px 10px 5px 0;color:var(--muted);">Gender</td>
                            <td style="padding:5px 0"><?= htmlspecialchars($gender) ?></td></tr>
                        <tr><td style="padding:5px 10px 5px 0;color:var(--muted);">Date of Birth</td>
                            <td style="padding:5px 0"><?= $dob ?></td></tr>
                        <tr><td style="padding:5px 10px 5px 0;color:var(--muted);">Birth ID / NEMIS</td>
                            <td style="padding:5px 0"><?= htmlspecialchars($e['birth_id'] ?? 'N/A') ?></td></tr>
                        <tr><td style="padding:5px 10px 5px 0;color:var(--muted);">Applied On</td>
                            <td style="padding:5px 0"><?= $applied ?></td></tr>
                    </table>
                </div>

                <!-- Parent Info -->
                <div>
                    <p style="font-size:.72rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px;">Parent / Guardian</p>
                    <table style="font-size:.85rem;width:100%;">
                        <tr><td style="padding:5px 10px 5px 0;color:var(--muted);width:130px;">Full Name</td>
                            <td style="padding:5px 0;font-weight:600"><?= htmlspecialchars($e['parent_name'] ?? 'N/A') ?></td></tr>
                        <tr><td style="padding:5px 10px 5px 0;color:var(--muted);">Email</td>
                            <td style="padding:5px 0"><?= htmlspecialchars($e['parent_email'] ?? 'N/A') ?></td></tr>
                        <tr><td style="padding:5px 10px 5px 0;color:var(--muted);">Phone</td>
                            <td style="padding:5px 0"><?= htmlspecialchars($e['parent_phone'] ?? 'N/A') ?></td></tr>
                        <tr><td style="padding:5px 10px 5px 0;color:var(--muted);">Relationship</td>
                            <td style="padding:5px 0"><?= htmlspecialchars(ucfirst($e['parent_relationship'] ?? 'N/A')) ?></td></tr>
                    </table>
                </div>
            </div>

            <!-- Document Links -->
            <?php if (!empty($e['birth_certificate_file']) || !empty($e['passport_photo'])): ?>
            <div style="border-top:1px solid var(--border);padding-top:14px;margin-bottom:16px;">
                <p style="font-size:.72rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;">Documents Submitted</p>
                <div class="btn-group" style="flex-wrap:wrap">
                    <?php if (!empty($e['birth_certificate_file'])): ?>
                    <a href="/CBE_LMS/public/<?= htmlspecialchars($e['birth_certificate_file']) ?>"
                       target="_blank" class="btn btn-outline btn-sm">📄 Birth Certificate</a>
                    <?php endif; ?>
                    <?php if (!empty($e['passport_photo'])): ?>
                    <a href="/CBE_LMS/public/<?= htmlspecialchars($e['passport_photo']) ?>"
                       target="_blank" class="btn btn-outline btn-sm">🖼 Passport Photo</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div style="display:flex;gap:10px;justify-content:flex-end;border-top:1px solid var(--border);padding-top:14px;">

                <!-- Approve -->
                <form method="POST" action="/CBE_LMS/public/index.php?url=admin/approveEnrollment"
                      onsubmit="return confirm('Approve enrollment for <?= htmlspecialchars(addslashes($e['student_name'])) ?>?\nThis will generate credentials and send emails.')">
                    <input type="hidden" name="student_user_id" value="<?= (int)$e['student_user_id'] ?>">
                    <button type="submit" class="btn btn-success">
                        ✅ Approve Enrollment
                    </button>
                </form>

                <!-- Reject -->
                <form method="POST" action="/CBE_LMS/public/index.php?url=admin/rejectEnrollment"
                      onsubmit="return confirm('Reject enrollment for <?= htmlspecialchars(addslashes($e['student_name'])) ?>?\nThe student and parent will be notified by email.')">
                    <input type="hidden" name="student_user_id" value="<?= (int)$e['student_user_id'] ?>">
                    <button type="submit" class="btn btn-danger">
                        ❌ Reject
                    </button>
                </form>

            </div>
        </div>
    </div>
    <?php endforeach; ?>

<?php endif; ?>

</div><!-- .content -->
</div><!-- .main-wrapper -->
</body>
</html>
