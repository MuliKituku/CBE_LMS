<?php
/* ── teacher/messages.php ──────────────────────────────────
   Variables: $profile, $parents, $parentId
   ─────────────────────────────────────────────────────────── */
$activeNav = 'messages';
require BASE_PATH . '/app/views/teacher/_sidebar.php';

$success = $_GET['success'] ?? '';
$error   = $_GET['error'] ?? '';
?>

<?php if ($success === 'sent'): ?>
<div class="alert alert-success">✔️ Message sent successfully!</div>
<?php elseif ($error === 'empty'): ?>
<div class="alert alert-error">❌ Message or selection cannot be empty.</div>
<?php endif; ?>

<div class="card" style="margin-bottom:24px;border:none;background:linear-gradient(135deg,#064e3b,#10b981);color:#fff">
    <div class="card-body">
        <h2 style="color:#fff;margin-bottom:6px">Parent Communication Hub</h2>
        <p style="color:#d1fae5;margin:0;font-size:.9rem">
            Communicate with parents regarding their children's learning pathways and performance.
        </p>
    </div>
</div>

<div style="display:grid;grid-template-columns:320px 1fr;gap:24px;align-items:start">

    <!-- PARENTS LIST -->
    <div class="card" style="overflow:hidden">
        <div class="card-header" style="background:#f8fafc">
            <h3 style="font-size:1rem;color:var(--text)">Conversations & Contacts</h3>
        </div>
        <div style="max-height:600px;overflow-y:auto">
            <?php if (empty($parents)): ?>
            <div style="padding:40px 20px;text-align:center;color:var(--muted)">
                <div style="font-size:2rem;margin-bottom:10px">👥</div>
                <p>No parents found linked to your students.</p>
            </div>
            <?php else: ?>
                <?php foreach ($parents as $p): ?>
                <a href="/CBE_LMS/public/index.php?url=teacher/messages&parent_id=<?= $p['parent_userId'] ?>" 
                   style="display:block;padding:16px 20px;border-bottom:1px solid #f1f5f9;text-decoration:none;
                          background:<?= $p['parent_userId'] === (int)$parentId ? '#ecfdf5' : 'transparent' ?>;
                          border-left:<?= $p['parent_userId'] === (int)$parentId ? '4px solid #10b981' : '4px solid transparent' ?>;
                          color:var(--text);transition:all 0.2s">
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <div>
                            <div style="font-weight:700;font-size:.95rem;color:<?= $p['parent_userId'] === (int)$parentId ? '#065f46' : 'var(--text)' ?>">
                                <?= htmlspecialchars($p['parent_name']) ?>
                            </div>
                            <div style="font-size:.75rem;color:var(--muted);margin-top:2px">
                                <?= count($p['linked_students'] ?? []) ?> student(s) linked
                            </div>
                        </div>
                        <?php if (($p['unread'] ?? 0) > 0): ?>
                        <div style="background:#ef4444;color:#fff;font-size:.65rem;font-weight:800;padding:2px 8px;border-radius:10px;text-transform:uppercase">
                            New
                        </div>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- CONVERSATION WINDOW -->
    <div class="card" style="display:flex;flex-direction:column;height:650px;overflow:hidden">
        <?php if (!$parentId): ?>
        <div style="flex:1;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:16px;color:var(--muted);background:#fafafa">
            <div style="width:80px;height:80px;border-radius:50%;background:#f1f5f9;display:flex;align-items:center;justify-content:center;font-size:3rem">💬</div>
            <div style="text-align:center">
                <h3 style="margin:0;color:var(--text)">Start a Conversation</h3>
                <p style="margin:5px 0 0;font-size:.9rem">Select a parent from the left to view messages</p>
            </div>
        </div>
        <?php else: 
            // Find active parent's data
            $activeParent = null;
            foreach ($parents as $p) {
                if ($p['parent_userId'] === (int)$parentId) {
                    $activeParent = $p;
                    break;
                }
            }
            $activeThread = $activeParent['messages'] ?? [];
            $activeParentName = $activeParent['parent_name'] ?? 'Unknown Parent';
            $linkedStudents = $activeParent['linked_students'] ?? [];
        ?>
        <div class="card-header" style="background:#f8fafc;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;padding:16px 24px">
            <div>
                <h3 style="margin:0;font-size:1.1rem;color:#064e3b">Chat with <?= htmlspecialchars($activeParentName) ?></h3>
                <div style="font-size:.75rem;color:var(--muted)">Replying as Teacher</div>
            </div>
            <div style="display:flex;gap:8px">
                <?php foreach($linkedStudents as $ls): ?>
                    <span class="badge badge-info" style="font-size:.6rem"><?= htmlspecialchars($ls['student_name']) ?></span>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Chat History -->
        <div id="chatHistory" style="flex:1;overflow-y:auto;padding:24px;display:flex;flex-direction:column;gap:16px;background:#fff">
            <?php if (empty($activeThread)): ?>
                <div style="text-align:center;color:var(--muted);margin:40px 0;font-size:.9rem">
                    <p>No previous messages found.</p>
                    <p style="font-size:.8rem">Send a message to <?= htmlspecialchars($activeParentName) ?> to begin.</p>
                </div>
            <?php else: ?>
                <?php foreach ($activeThread as $m): ?>
                    <?php $isMe = ($m['sender_type'] === 'teacher'); ?>
                    <div style="display:flex;flex-direction:column;max-width:80%;<?= $isMe ? 'align-self:flex-end;align-items:flex-end' : 'align-self:flex-start;align-items:flex-start' ?>">
                        <div style="font-size:.7rem;color:var(--muted);margin-bottom:4px;padding:0 4px">
                            <?= !$isMe ? 'Parent' : 'You' ?>
                            <?php if ($m['student_name']): ?>
                                <span style="color:#059669;font-weight:600">• Student: <?= htmlspecialchars($m['student_name']) ?></span>
                            <?php endif; ?>
                            • <?= date('d M, H:i', strtotime($m['created_at'])) ?>
                        </div>
                        <div style="padding:12px 16px;border-radius:16px;line-height:1.5;font-size:.95rem;box-shadow:0 2px 4px rgba(0,0,0,0.05);
                                    <?= $isMe ? 'background:#10b981;color:#fff;border-bottom-right-radius:4px' 
                                               : 'background:#f1f5f9;color:#1e293b;border-bottom-left-radius:4px' ?>">
                            <?= nl2br(htmlspecialchars($m['message'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Reply Box -->
        <div style="padding:16px 24px;border-top:1px solid #e2e8f0;background:#f8fafc">
            <form method="POST" action="/CBE_LMS/public/index.php?url=teacher/sendMessage">
                <input type="hidden" name="parent_userId" value="<?= (int)$parentId ?>">
                
                <div style="display:flex;gap:12px;margin-bottom:12px;align-items:center">
                    <label style="font-size:.8rem;color:var(--muted);font-weight:600">Regarding Student:</label>
                    <select name="student_id" class="form-control" style="width:200px;font-size:.8rem;padding:4px 8px;height:auto" required>
                        <option value="0">All / General</option>
                        <?php foreach ($linkedStudents as $ls): ?>
                            <option value="<?= $ls['student_id'] ?>"><?= htmlspecialchars($ls['student_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display:flex;gap:12px">
                    <textarea name="message" class="form-control" rows="2" placeholder="Write your message here..." required
                               style="border-radius:12px;resize:none;flex:1;padding:12px;border-color:#e2e8f0"></textarea>
                    <button type="submit" class="btn btn-primary" style="height:auto;padding:0 24px;border-radius:12px;font-weight:800;letter-spacing:0.5px">
                        SEND ✉️
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
const chatHistory = document.getElementById('chatHistory');
if (chatHistory) {
    chatHistory.scrollTop = chatHistory.scrollHeight;
}
</script>
</body></html>
