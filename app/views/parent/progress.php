<?php
/* ── parent/progress.php ────────────────────────────────────
   Variables: $profile, $studentId, $childName, $progressData
   ─────────────────────────────────────────────────────────── */
$activeNav = 'progress';
require BASE_PATH . '/app/views/parent/_sidebar.php';

// Data setup
$p = $progressData;
$cm = $p['mastery'] ?? ['mastered'=>0,'in_progress'=>0,'not_started'=>0,'total'=>0];

$mast = (int)$cm['mastered'];
$prog = (int)$cm['in_progress'];
$not  = (int)$cm['not_started'];
$tot  = (int)$cm['total'];
$mastPct = $tot > 0 ? round(($mast / $tot) * 100) : 0;

$subjLabels = [];
$subjScores = [];
if (!empty($p['avg_scores'])) {
    foreach ($p['avg_scores'] as $s) {
        $subjLabels[] = $s['subject'];
        $subjScores[] = (float)$s['avg_pct'];
    }
}
$competencies = $p['competencies'] ?? [];
$children = $profile['children'] ?? [];
?>

<!-- Child Selector -->
<?php if (count($children) > 1): ?>
<div class="card" style="margin-bottom:20px;padding:12px 20px;background:#f8fafc;display:flex;align-items:center;gap:12px">
    <strong style="color:var(--text);font-size:.9rem">Viewing Progress For:</strong>
    <form method="GET" action="/CBE_LMS/public/index.php" style="margin:0">
        <input type="hidden" name="url" value="parent/progress">
        <select name="child_id" class="form-control" style="width:250px;padding:6px 10px" onchange="this.form.submit()">
            <?php foreach ($children as $c): ?>
            <option value="<?= $c['student_id'] ?>" <?= $studentId === (int)$c['student_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['student_name']) ?> (<?= htmlspecialchars($c['class_grade']) ?>)
            </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>
<?php endif; ?>

<!-- HEADER -->
<div class="card" style="margin-bottom:24px;border:none;background:linear-gradient(135deg,#1e1b4b,#312e81);color:#fff">
    <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:20px">
        <div>
            <h2 style="color:#fff;margin-bottom:6px"><?= htmlspecialchars($childName) ?>'s Progress</h2>
            <p style="color:#a5b4fc;margin:0;font-size:.9rem">
                An overview of competency mastery and assessment averages.
            </p>
        </div>
        <div style="display:flex;align-items:center;gap:24px">
            <button onclick="window.print()" class="btn btn-outline btn-sm" style="background:rgba(255,255,255,0.1);color:#fff;border-color:rgba(255,255,255,0.2)">🖨️ Print Progress</button>
            <a href="/CBE_LMS/public/index.php?url=parent/report&child_id=<?= (int)$studentId ?>" class="btn btn-primary" style="background:#fff;color:#1e1b4b;border:none">
                📄 Download Report
            </a>
            <div style="text-align:right">
                <div style="font-size:.75rem;color:#a5b4fc;margin-bottom:4px;text-transform:uppercase;letter-spacing:1px">Mastery Progress</div>
                <div style="font-size:2rem;font-weight:800;color:#10b981"><?= $mastPct ?>%</div>
            </div>
        </div>
    </div>
</div>

<?php if (!$studentId): ?>
<!-- Edge case: no children mapping -->
<div class="alert alert-error">No children accounts linked to this profile.</div>
<?php else: ?>

<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(350px, 1fr));gap:20px;margin-bottom:24px">
    
    <!-- Mastery Doughnut Chart -->
    <div class="card">
        <div class="card-header"><h3>🎯 Competency Mastery</h3></div>
        <div class="card-body" style="position:relative;height:240px;display:flex;align-items:center;justify-content:center">
            <?php if ($tot === 0): ?>
            <p style="color:var(--muted)">No competencies mapped for this grade yet.</p>
            <?php else: ?>
            <canvas id="masteryChart"></canvas>
            <!-- Center text -->
            <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;pointer-events:none">
                <div style="font-size:1.8rem;font-weight:800;color:#6366f1;line-height:1"><?= $mast ?></div>
                <div style="font-size:.7rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-top:2px">Mastered</div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Subject Scores Bar Chart -->
    <div class="card">
        <div class="card-header"><h3>📈 Quiz Averages by Subject</h3></div>
        <div class="card-body" style="position:relative;height:240px">
            <?php if (empty($subjLabels)): ?>
            <div class="empty-state" style="height:100%;justify-content:center"><p>No assessment data available yet.</p></div>
            <?php else: ?>
            <canvas id="scoresChart"></canvas>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- DETAILED COMPETENCIES TABLE -->
<div class="card">
    <div class="card-header">
        <h3>📋 Detailed Competency Breakdown</h3>
    </div>
    <div class="table-wrapper">
        <?php if (empty($competencies)): ?>
        <div class="empty-state" style="padding:40px 20px">
            <p>No competencies defined for this grade.</p>
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Competency</th>
                    <th>Teacher Description</th>
                    <th>Student Status</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $badges = [
                    'fully_mastered' => '<span class="badge" style="background:#8b5cf6;color:#fff">Fully Mastered</span>',
                    'mastered'    => '<span class="badge badge-success">Mastered</span>',
                    'in_progress' => '<span class="badge badge-pending">In Progress</span>',
                    'not_started' => '<span class="badge badge-inactive">Not Started</span>'
                ];
                foreach ($competencies as $c): 
                ?>
                <tr>
                    <td style="font-weight:600;color:#4f46e5"><?= htmlspecialchars($c['subject']) ?></td>
                    <td style="font-weight:600"><?= htmlspecialchars($c['title']) ?></td>
                    <td style="font-size:.85rem;color:var(--text);max-width:300px">
                        <?= htmlspecialchars($c['description']) ?>
                    </td>
                    <td><?= $badges[$c['mastery_status']] ?? $badges['not_started'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Chart initialization -->
<script>
<?php if ($tot > 0): ?>
new Chart(document.getElementById('masteryChart'), {
    type: 'doughnut',
    data: {
        labels: ['Mastered', 'In Progress', 'Not Started'],
        datasets: [{
            data: [<?= $mast ?>, <?= $prog ?>, <?= $not ?>],
            backgroundColor: ['#10b981', '#f59e0b', '#e2e8f0'],
            borderWidth: 0,
            cutout: '75%'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'right', labels: { boxWidth: 12, font: {family:'Inter'} } }
        }
    }
});
<?php endif; ?>

<?php if (!empty($subjLabels)): ?>
new Chart(document.getElementById('scoresChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($subjLabels) ?>,
        datasets: [{
            label: 'Average Score (%)',
            data: <?= json_encode($subjScores) ?>,
            backgroundColor: '#8b5cf6',
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } }
        },
        plugins: {
            legend: { display: false }
        }
    }
});
<?php endif; ?>
</script>

<?php endif; // End child data block ?>

</div></div>
</body></html>
