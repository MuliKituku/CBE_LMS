<?php
$activeNav = 'audit';
require_once BASE_PATH . '/app/views/admin/_sidebar.php';
?>

<div class="stat-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 24px;">
    <div class="stat-card">
        <div class="label">Total Logs</div>
        <div class="value"><?= count($logs) ?></div>
        <div class="trend text-success">Last 30 days</div>
    </div>
    <div class="stat-card">
        <div class="label">Security Alerts</div>
        <div class="value">0</div>
        <div class="trend">All system clear</div>
    </div>
    <div class="stat-card">
        <div class="label">Admin Sessions</div>
        <div class="value">Active</div>
        <div class="trend text-success">Secure Connection</div>
    </div>
</div>

<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>Admin</th>
                <th>Action</th>
                <th>Target</th>
                <th>Details</th>
                <th>Timestamp</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): 
                $actionColor = match($log['action']) {
                    'GRADE_OVERRIDE' => '#f59e0b',
                    'DELETE_LESSON'  => '#dc2626',
                    'TOGGLE_LESSON_VISIBILITY' => '#3b82f6',
                    default => '#64748b'
                };
            ?>
                <tr>
                    <td>
                        <div style="font-weight: 600; color: #1e293b;"><?= htmlspecialchars($log['admin_name']) ?></div>
                    </td>
                    <td>
                        <span style="font-size: 0.75rem; font-weight: 800; padding: 4px 8px; border-radius: 4px; background: <?= $actionColor ?>15; color: <?= $actionColor ?>; border: 1px solid <?= $actionColor ?>30;">
                            <?= str_replace('_', ' ', $log['action']) ?>
                        </span>
                    </td>
                    <td>
                        <div style="font-size: 0.85rem; font-weight: 600;"><?= strtoupper($log['target_type']) ?></div>
                        <div style="font-size: 0.75rem; color: #94a3b8;">ID: #<?= $log['target_id'] ?></div>
                    </td>
                    <td style="max-width: 300px;">
                        <div style="font-size: 0.85rem; color: #475569; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($log['details']) ?>">
                            <?= htmlspecialchars($log['details']) ?>
                        </div>
                    </td>
                    <td>
                        <div style="font-size: 0.9rem; color: #1e293b;"><?= date('d M Y', strtotime($log['created_at'])) ?></div>
                        <div style="font-size: 0.75rem; color: #94a3b8;"><?= date('H:i:s', strtotime($log['created_at'])) ?></div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/_footer.php'; ?>
