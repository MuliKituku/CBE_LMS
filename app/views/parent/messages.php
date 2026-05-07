<?php
/* ── parent/messages.php ────────────────────────────────────
   Variables: $profile, $teachers, $teacherId
   ─────────────────────────────────────────────────────────── */
$activeNav = 'messages';
require BASE_PATH . '/app/views/parent/_sidebar.php';

$success = $_GET['success'] ?? '';
$error   = $_GET['error'] ?? '';
$children = $profile['children'] ?? [];
?>

<?php if ($success === 'sent'): ?>
<div class="alert alert-success">✔️ Message sent to teacher successfully!</div>
<?php elseif ($error === 'empty'): ?>
<div class="alert alert-error">❌ Message or selection cannot be empty.</div>
<?php endif; ?>

<div class="card" style="margin-bottom:24px;border:none;background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff">
    <div class="card-body">
        <h2 style="color:#fff;margin-bottom:6px">Parent-Teacher Communication</h2>
        <p style="color:#e0e7ff;margin:0;font-size:.9rem">
            Select a teacher to view your conversation thread or start a new message.
        </p>
    </div>
</div>

<div style="display:grid;grid-template-columns:300px 1fr;gap:24px;align-items:start">

    <!-- TEACHERS LIST -->
    <div class="card">
        <div class="card-header">
            <h3 style="font-size:1.1rem">Educators</h3>
        </div>
        <div style="padding:0">
            <?php if (empty($teachers)): ?>
            <div style="padding:24px;text-align:center;color:var(--muted)">
                No teachers found linked to your children.
            </div>
            <?php else: ?>
                <?php foreach ($teachers as $t): ?>
                <a href="/CBE_LMS/public/index.php?url=parent/messages&teacher_id=<?= $t['teacher_id'] ?>" 
                   style="display:block;padding:16px 20px;border-bottom:1px solid #f1f5f9;text-decoration:none;
                          background:<?= $t['teacher_id'] === $teacherId ? '#f8fafc' : 'transparent' ?>;
                          border-left:<?= $t['teacher_id'] === $teacherId ? '4px solid #6366f1' : '4px solid transparent' ?>;
                          color:var(--text);transition:background 0.2s">
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <div style="font-weight:600;font-size:.95rem">
                            <?= htmlspecialchars($t['teacher_name']) ?>
                        </div>
                        <?php if (($t['unread'] ?? 0) > 0): ?>
                        <div style="background:#ef4444;color:#fff;font-size:.7rem;font-weight:700;padding:2px 8px;border-radius:10px">
                            <?= $t['unread'] ?> New
                        </div>
                        <?php endif; ?>
                    </div>
                    <!-- Preview of last message if exists -->
                    <?php if (!empty($t['messages'])): 
                        $lastMsg = end($t['messages']);
                    ?>
                    <div style="font-size:.8rem;color:var(--muted);margin-top:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                        <?= $lastMsg['sender_type']==='parent' ? 'You: ' : 'Teacher: ' ?>
                        <?= htmlspecialchars($lastMsg['message']) ?>
                    </div>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- CONVERSATION WINDOW -->
    <div class="card" style="display:flex;flex-direction:column;height:600px">
        <?php if (!$teacherId): ?>
        <div style="flex:1;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:12px;color:var(--muted)">
            <div style="font-size:3rem">💬</div>
            <p>Select a teacher from the list to start messaging.</p>
        </div>
        <?php else: 
            // Find active teacher's array
            $activeThread = null;
            $activeTeacherName = 'Unknown Teacher';
            foreach ($teachers as $t) {
                if ($t['teacher_id'] === $teacherId) {
                    $activeThread = $t['messages'];
                    $activeTeacherName = $t['teacher_name'];
                    break;
                }
            }
        ?>
        <div class="card-header" style="background:#f8fafc;border-bottom:1px solid #e2e8f0">
            <h3 style="margin:0;font-size:1.1rem;color:#1e293b">Chat with <?= htmlspecialchars($activeTeacherName) ?></h3>
        </div>

        <!-- Chat History -->
        <div style="flex:1;overflow-y:auto;padding:24px;display:flex;flex-direction:column;gap:16px;background:#fff">
            <?php if (empty($activeThread)): ?>
                <div style="text-align:center;color:var(--muted);margin:20px 0;font-size:.9rem">
                    This represents the start of your conversation with <?= htmlspecialchars($activeTeacherName) ?>.
                </div>
            <?php else: ?>
                <?php foreach ($activeThread as $m): ?>
                    <?php $isMe = ($m['sender_type'] === 'parent'); ?>
                    <div style="display:flex;flex-direction:column;max-width:80%;<?= $isMe ? 'align-self:flex-end;align-items:flex-end' : 'align-self:flex-start;align-items:flex-start' ?>">
                        <div style="font-size:.7rem;color:var(--muted);margin-bottom:4px;padding:0 4px">
                            <?= !$isMe ? htmlspecialchars($activeTeacherName) : 'You' ?>
                            <?php if ($m['student_name']): ?>
                                (Re: <?= htmlspecialchars($m['student_name']) ?>)
                            <?php endif; ?>
                            • <?= date('d M Y, H:i', strtotime($m['created_at'])) ?>
                        </div>
                        <div style="padding:12px 16px;border-radius:12px;line-height:1.5;font-size:.95rem;
                                    <?= $isMe ? 'background:#6366f1;color:#fff;border-bottom-right-radius:4px' 
                                              : 'background:#f1f5f9;color:#1e293b;border-bottom-left-radius:4px' ?>">
                            <?= nl2br(htmlspecialchars($m['message'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Reply Box -->
        <div style="padding:16px 20px;border-top:1px solid #e2e8f0;background:#f8fafc">
            <form method="POST" action="/CBE_LMS/public/index.php?url=parent/sendMessage">
                <input type="hidden" name="teacher_id" value="<?= (int)$teacherId ?>">
                
                <?php if (count($children) > 1): ?>
                <div style="margin-bottom:8px">
                    <select name="student_id" class="form-control" style="width:200px;font-size:.85rem;padding:6px;border-radius:6px" required>
                        <option value="">-- Regarding which child? --</option>
                        <?php foreach ($children as $c): ?>
                            <option value="<?= $c['student_id'] ?>"><?= htmlspecialchars($c['student_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php elseif (count($children) === 1): ?>
                    <input type="hidden" name="student_id" value="<?= $children[0]['student_id'] ?>">
                <?php endif; ?>

                <div style="display:flex;gap:12px">
                    <textarea name="message" class="form-control" rows="2" placeholder="Write a message..." required
                              style="border-radius:10px;resize:none;flex:1"></textarea>
                    <button type="submit" class="btn btn-primary" style="height:auto;padding:0 24px;border-radius:10px;font-weight:700">
                        Send ✉️
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>

</div>

</div></div>
<script>
// Auto scroll chat to bottom
const chatWindow = document.querySelector('.card > div[style*="overflow-y:auto"]');
if (chatWindow) {
    chatWindow.scrollTop = chatWindow.scrollHeight;
}
</script>
</body></html>
