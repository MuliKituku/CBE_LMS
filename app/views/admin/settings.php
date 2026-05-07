<?php
/* ── admin/settings.php ───────────────────────────────────────
   Manage system settings like grading scales
   Variables: $scales (array)
   ─────────────────────────────────────────────────────────── */

$activeNav = 'settings';
require BASE_PATH . '/app/views/admin/_sidebar.php';

$success = $_GET['success'] ?? '';
?>

<div style="max-width: 800px; margin: 0 auto;">

    <?php if ($success === 'updated'): ?>
        <div class="alert alert-success">✅ System settings updated successfully.</div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h3>⚙️ System Settings</h3>
            <p class="text-sm text-muted">Configure global system behaviors and definitions.</p>
        </div>
        <div class="card-body">
            
            <form action="/CBE_LMS/public/index.php?url=admin/settings" method="POST">
                
                <h4 style="margin-bottom:16px;border-bottom:1px solid #e2e8f0;padding-bottom:8px;">🎯 CBE Grading Scales</h4>
                <p class="text-sm text-muted mb-4">Define the percentage ranges for competency mastery levels. These will be used across all reports and student dashboards.</p>

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:24px;">
                    <div style="font-weight:600;font-size:.85rem;color:var(--muted);">Level</div>
                    <div style="font-weight:600;font-size:.85rem;color:var(--muted);">Min Score (%)</div>
                    <div style="font-weight:600;font-size:.85rem;color:var(--muted);">Max Score (%)</div>

                    <?php foreach(['EE','ME','AE','BE'] as $key): ?>
                        <?php $s = $scales[$key] ?? ['min'=>0,'max'=>0,'label'=>'']; ?>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span class="badge badge-outline" style="min-width:40px;text-align:center;"><?= $key ?></span>
                            <span class="text-xs text-muted"><?= $s['label'] ?></span>
                        </div>
                        <input type="number" name="scales[<?= $key ?>][min]" value="<?= $s['min'] ?>" class="form-control" min="0" max="100">
                        <input type="number" name="scales[<?= $key ?>][max]" value="<?= $s['max'] ?>" class="form-control" min="0" max="100">
                        <!-- Hidden label to preserve it -->
                        <input type="hidden" name="scales[<?= $key ?>][label]" value="<?= $s['label'] ?>">
                    <?php endforeach; ?>
                </div>

                <div class="mt-4" style="text-align:right;">
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </div>

            </form>

        </div>
    </div>

</div>

</div><!-- .content -->
</div><!-- .main-wrapper -->
</body>
</html>
