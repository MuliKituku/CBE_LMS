<?php
$activeNav = 'pathway';
require BASE_PATH . '/app/views/student/_sidebar.php';
?>

<div class="card" style="margin-bottom:24px;border:none;background:linear-gradient(135deg,#6366f1,#4338ca);color:#fff">
    <div class="card-body" style="display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h2 style="color:#fff;margin-bottom:6px">Senior School Pathway Recommendation</h2>
            <p style="color:#e0e7ff;margin:0;font-size:.9rem">
                Transition smoothly into Grade 10 by matching your academic performance and personal interests to the right Kenyan CBE Pathway.
            </p>
        </div>
        <button onclick="window.print()" class="btn btn-outline btn-sm" style="background:rgba(255,255,255,0.1);color:#fff;border-color:rgba(255,255,255,0.2);">🖨️ Print Pathway</button>
    </div>
</div>

<?php if (!$hasCompleted && !empty($questions)): ?>
    <div class="card">
        <div class="card-header">
            <h3>📝 Pathway Interest Survey</h3>
            <p style="color:var(--muted);font-size:0.85rem;margin:0">Rate how strongly you agree with following statements to help us calculate your interest profile.</p>
        </div>
        <div class="card-body">
            <form action="<?= BASE_URL ?>/public/index.php?url=pathway/submitSurvey" method="POST">
                
                <div style="display:grid;grid-template-columns:1fr;gap:20px;margin-bottom:20px">
                <?php foreach ($questions as $q): ?>
                    <div style="background:#f8fafc;padding:16px;border-radius:8px;border:1px solid #e2e8f0;transition: border-color 0.2s" class="survey-card">
                        <div style="font-weight:600;margin-bottom:12px;color:var(--text)"><?= htmlspecialchars($q['question']) ?></div>
                        <div style="display:flex;gap:12px;flex-wrap:wrap">
                            <?php for ($i=1; $i<=5; $i++): ?>
                                <?php 
                                    $labels = [1=>"Strongly Disagree", 2=>"Disagree", 3=>"Neutral", 4=>"Agree", 5=>"Strongly Agree"];
                                ?>
                                <label style="cursor:pointer;display:flex;align-items:center;gap:6px;background:#fff;padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:0.85rem;transition: all 0.2s" class="radio-label">
                                    <input type="radio" name="responses[<?= $q['id'] ?>]" value="<?= $i ?>" required>
                                    <?= $labels[$i] ?>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>

                <style>
                    .radio-label:hover { border-color: #6366f1; background: #e0e7ff; }
                    input[type="radio"]:checked + .radio-label { border-color: #4f46e5; background: #e0e7ff; font-weight:600; }
                </style>
                <script>
                    document.querySelectorAll('input[type="radio"]').forEach(radio => {
                        radio.addEventListener('change', function() {
                            let parentDiv = this.closest('.survey-card');
                            parentDiv.style.borderColor = '#10b981';
                        });
                    });
                </script>

                <button type="submit" class="btn btn-primary" style="width:100%;font-size:1.1rem;padding:12px;background:#4f46e5;border:none;border-radius:8px;cursor:pointer;color:white;font-weight:600">Submit Survey & Generate Recommendation</button>
            </form>
        </div>
    </div>
<?php elseif ($recommendation): ?>

    <?php
        $r = $recommendation;
        $labels = [
            'stem' => 'STEM (Science, Tech, Engineering & Math)',
            'social_sciences' => 'Social Sciences',
            'arts_sports' => 'Arts & Sports Science'
        ];
        $best = $labels[$r['recommended_pathway']];
        $history = !empty($r['interest_history']) ? json_decode($r['interest_history'], true) : null;

        // Calculate a basic shift if history exists
        $hasSignificantShift = false;
        $shiftType = '';
        if ($history) {
            // Find which pathway grew the most
            // Since we don't have the NEW interest pct directly here (only the blended final score), 
            // the view logic might be a bit loose, but we can show they have a history.
        }
    ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
        <div class="card" style="border:2px solid #10b981;box-shadow:0 10px 25px -5px rgba(16,185,129,0.15)">
            <div class="card-header" style="background:#ecfdf5;border-bottom:1px solid #a7f3d0">
                <h3 style="color:#065f46;text-align:center;width:100%;margin:0">🌟 Your Recommended Pathway</h3>
            </div>
            <div class="card-body" style="text-align:center;padding:40px 20px">
                <div style="font-size:1rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:10px">Best Match For You</div>
                <h1 style="color:#10b981;font-size:2.2rem;margin:0;line-height:1.2"><?= $best ?></h1>
                <p style="margin-top:20px;color:var(--text);font-size:0.95rem;line-height:1.6">
                    Based on our smart Engine <strong>(60% Academic Performance / 40% Interest Survey)</strong>, this pathway offers the strongest alignment for your transition to Senior School.
                    <?php if ($history): ?>
                        <br><span style="display:inline-block; margin-top:10px; padding:4px 12px; border-radius:12px; background:#f0fdf4; color:#15803d; font-size:.8rem; font-weight:700">🔄 Refined based on 65% new survey and 35% previous interest data.</span>
                    <?php endif; ?>
                </p>
                <div style="margin-top:40px;display:flex;gap:16px;justify-content:space-around">
                    <div style="text-align:center">
                        <div style="font-size:1.8rem;font-weight:800;color:var(--text)"><?= (float)$r['stem_score'] ?>%</div>
                        <div style="font-size:0.8rem;color:var(--muted);font-weight:600">STEM Score</div>
                    </div>
                    <div style="text-align:center">
                        <div style="font-size:1.8rem;font-weight:800;color:var(--text)"><?= (float)$r['social_score'] ?>%</div>
                        <div style="font-size:0.8rem;color:var(--muted);font-weight:600">Social Sc.</div>
                    </div>
                    <div style="text-align:center">
                        <div style="font-size:1.8rem;font-weight:800;color:var(--text)"><?= (float)$r['arts_score'] ?>%</div>
                        <div style="font-size:0.8rem;color:var(--muted);font-weight:600">Arts & Sports</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Affinity Radar</h3>
            </div>
            <div class="card-body" style="position:relative;height:350px;display:flex;align-items:center;justify-content:center">
                <canvas id="pathwayRadar"></canvas>
            </div>
        </div>
    </div>

    <!-- Chart initialization -->
    <script>
    new Chart(document.getElementById('pathwayRadar'), {
        type: 'radar',
        data: {
            labels: ['STEM', 'Social Sciences', 'Arts & Sports Science'],
            datasets: [{
                label: 'Pathway Match (%)',
                data: [<?= (float)$r['stem_score'] ?>, <?= (float)$r['social_score'] ?>, <?= (float)$r['arts_score'] ?>],
                backgroundColor: 'rgba(99, 102, 241, 0.2)',
                borderColor: 'rgba(99, 102, 241, 1)',
                pointBackgroundColor: 'rgba(99, 102, 241, 1)',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: 'rgba(99, 102, 241, 1)',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                r: {
                    angleLines: { color: 'rgba(0,0,0,0.1)' },
                    grid: { color: 'rgba(0,0,0,0.1)' },
                    pointLabels: { font: {family:'Inter', size:12, weight:'600'} },
                    ticks: { beginAtZero: true, max: 100, stepSize: 20 }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
    </script>

<?php endif; ?>

</div></div>
</body></html>
