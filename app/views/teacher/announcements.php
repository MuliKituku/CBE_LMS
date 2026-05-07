<?php
/* ── teacher/announcements.php ─────────────────────────────
   Variables: $profile, $announcements, $classes
   ─────────────────────────────────────────────────────────── */
$activeNav = 'announcements';
require BASE_PATH . '/app/views/teacher/_sidebar.php';

$success = $_GET['success'] ?? '';
$error   = $_GET['error'] ?? '';
?>

<?php if ($success === 'created'): ?>
<div class="alert alert-success">✔️ Announcement broadcasted to students.</div>
<?php elseif ($success === 'deleted'): ?>
<div class="alert alert-success">✔️ Announcement deleted.</div>
<?php elseif ($error === 'missing_fields'): ?>
<div class="alert alert-error">❌ Please fill in all fields.</div>
<?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <h2 style="color:var(--text);margin:0">Class Announcements</h2>
    <button class="btn btn-primary" onclick="document.getElementById('newAnnouncementModal').classList.add('open')">
        + New Broadcast
    </button>
</div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Target Class</th>
                    <th>Announcement</th>
                    <th>Date</th>
                    <th style="text-align:right">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($announcements)): ?>
                <tr>
                    <td colspan="4" style="text-align:center;padding:40px;color:var(--muted)">
                        You haven't posted any announcements yet.
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($announcements as $a): ?>
                    <tr>
                        <td style="font-weight:600">Grade <?= htmlspecialchars($a['class_grade']) ?></td>
                        <td>
                            <div style="font-weight:600"><?= htmlspecialchars($a['title']) ?></div>
                            <div style="font-size:0.85rem;color:var(--muted);margin-top:4px"><?= htmlspecialchars($a['message']) ?></div>
                        </td>
                        <td style="font-size:0.8rem"><?= date('M d, Y', strtotime($a['created_at'])) ?></td>
                        <td style="text-align:right">
                            <a href="/CBE_LMS/public/index.php?url=teacher/deleteAnnouncement/<?= $a['id'] ?>" 
                               class="btn btn-outline btn-sm" style="color:#ef4444" 
                               onclick="return confirm('Delete this announcement?');">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- New Announcement Modal -->
<div id="newAnnouncementModal" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h3>New Broadcast Announcement</h3>
            <button class="modal-close" onclick="document.getElementById('newAnnouncementModal').classList.remove('open')">×</button>
        </div>
        <form method="POST" action="/CBE_LMS/public/index.php?url=teacher/createAnnouncement">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Target Class Grade</label>
                    <select name="class_grade" class="form-control" required>
                        <option value="">Select Grade...</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?= htmlspecialchars($c['class_grade']) ?>">Grade <?= htmlspecialchars($c['class_grade']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Subject (Optional)</label>
                    <input type="text" name="subject" class="form-control" placeholder="e.g. Mathematics">
                </div>
                <div class="form-group">
                    <label class="form-label">Title / Heading</label>
                    <input type="text" name="title" class="form-control" required placeholder="e.g. Upcoming Test Reminder">
                </div>
                <div class="form-group">
                    <label class="form-label">Message Content</label>
                    <textarea name="message" class="form-control" style="height:120px" required placeholder="Type your message here..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('newAnnouncementModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary">Send to All Students</button>
            </div>
        </form>
    </div>
</div>

</div><!-- .content -->
</div><!-- .main-wrapper -->
</body>
</html>
