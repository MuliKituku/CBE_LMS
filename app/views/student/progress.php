<?php
/* ── student/progress.php ──────────────────────────────────
   Variables: $profile, $competencies, $progressData
   ─────────────────────────────────────────────────────────── */
$activeNav = 'progress';
require BASE_PATH . '/app/views/student/_sidebar.php';

$p = $progressData;
$cm = $p['mastery'];

// Doughnut chart data
$mast = (int)$cm['mastered'];
$prog = (int)$cm['in_progress'];
$not  = (int)$cm['not_started'];
$tot  = (int)$cm['total'];
$mastPct = $tot > 0 ? round(($mast / $tot) * 100) : 0;

// Bar chart data (scores per subject)
$subjLabels = [];
$subjScores = [];
foreach ($p['avg_scores'] as $s) {
    $subjLabels[] = $s['subject'];
    $subjScores[] = $s['avg_pct'];
}
?>

<!-- HEADER -->
<div class="card" style="margin-bottom:24px;border:none;background:linear-gradient(135deg,#0f172a,#1e293b);color:#fff">
    <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:20px">
        <div>
            <h2 style="color:#fff;margin-bottom:6px">Learning Progress</h2>
            <p style="color:#94a3b8;margin:0;font-size:.9rem">
                Track your competency mastery and assessment scores for <?= htmlspecialchars($profile['class_grade']) ?>.
            </p>
        </div>
        <div style="text-align:right">
            <button onclick="window.print()" class="btn btn-outline btn-sm" style="background:rgba(255,255,255,0.1);color:#fff;border-color:rgba(255,255,255,0.2);margin-bottom:10px">🖨️ Print Progress</button>
            <div style="font-size:.75rem;color:#94a3b8;margin-bottom:4px;text-transform:uppercase;letter-spacing:1px">Overall Mastery</div>
            <div style="font-size:2rem;font-weight:800;color:#5eead4"><?= $mastPct ?>%</div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(350px, 1fr));gap:20px;margin-bottom:24px">
    
    <!-- Mastery Doughnut Chart -->
    <div class="card">
        <div class="card-header"><h3>🎯 Competency Mastery</h3></div>
        <div class="card-body" style="position:relative;height:240px;display:flex;align-items:center;justify-content:center">
            <?php if ($tot === 0): ?>
            <p style="color:var(--muted)">No competencies defined yet.</p>
            <?php else: ?>
            <canvas id="masteryChart"></canvas>
            <!-- Center text -->
            <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;pointer-events:none">
                <div style="font-size:1.8rem;font-weight:800;color:#0d9488;line-height:1"><?= $mast ?></div>
                <div style="font-size:.7rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-top:2px">Mastered</div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Subject Scores Bar Chart -->
    <div class="card">
        <div class="card-header"><h3>📈 Average Scores by Subject</h3></div>
        <div class="card-body" style="position:relative;height:240px">
            <?php if (empty($subjLabels)): ?>
            <div class="empty-state" style="height:100%;justify-content:center"><p>No assessment data yet.</p></div>
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
            <p>No competencies available for this grade.</p>
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Core Competency</th>
                    <th>Status</th>
                    <th>Score</th>
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
                    <td style="font-weight:600"><?= htmlspecialchars($c['title']) ?></td>
                    <td><?= $badges[$c['mastery_status']] ?? $badges['not_started'] ?></td>
                    <td><?= htmlspecialchars($c['score'] ?? '-') ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

</div></div>

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
            backgroundColor: '#0ea5e9',
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

</body></html>
