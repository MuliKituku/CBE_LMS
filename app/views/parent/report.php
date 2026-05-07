<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Progress Report - <?= htmlspecialchars($child['student_name']) ?></title>
    <style>
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; color: #1e293b; line-height: 1.5; padding: 40px; margin: 0; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #e2e8f0; padding-bottom: 20px; margin-bottom: 30px; }
        .logo-area h1 { margin: 0; color: #4f46e5; font-size: 1.5rem; }
        .logo-area p { margin: 4px 0 0; font-size: 0.85rem; color: #64748b; }
        .report-info { text-align: right; }
        .report-info h2 { margin: 0; font-size: 1.2rem; }
        .report-info p { margin: 4px 0 0; font-size: 0.85rem; color: #64748b; }
        
        .student-details { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; }
        .detail-item strong { display: block; font-size: 0.75rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
        .detail-item span { font-size: 1rem; font-weight: 600; }

        .summary-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-box { padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; text-align: center; }
        .stat-box strong { display: block; font-size: 0.75rem; color: #64748b; text-transform: uppercase; margin-bottom: 5px; }
        .stat-box span { font-size: 1.5rem; font-weight: 800; color: #4f46e5; }

        h3 { border-left: 4px solid #4f46e5; padding-left: 10px; margin: 40px 0 20px; font-size: 1.1rem; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { text-align: left; background: #f8fafc; border-bottom: 2px solid #e2e8f0; padding: 10px; font-size: 0.85rem; color: #64748b; }
        td { padding: 12px 10px; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; }
        
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-pending { background: #fef9c3; color: #854d0e; }
        .badge-inactive { background: #f1f5f9; color: #475569; }

        .footer { margin-top: 50px; text-align: center; font-size: 0.8rem; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 20px; }
        
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
            .student-details { border: 1px solid #ccc; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #4f46e5; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
            🖨️ Print / Save as PDF
        </button>
        <button onclick="window.history.back()" style="padding: 10px 20px; background: #fff; color: #4f46e5; border: 1px solid #4f46e5; border-radius: 6px; cursor: pointer; font-weight: 600; margin-left: 10px;">
            Go Back
        </button>
    </div>

    <div class="header">
        <div class="logo-area">
            <h1>CBE Learning Management System</h1>
            <p>Empowering Students through Competency-Based Education</p>
        </div>
        <div class="report-info">
            <h2>STUDENT PROGRESS REPORT</h2>
            <p>Generated on: <?= date('d M Y') ?></p>
        </div>
    </div>

    <div class="student-details">
        <div class="detail-item">
            <strong>Student Name</strong>
            <span><?= htmlspecialchars($child['student_name']) ?></span>
        </div>
        <div class="detail-item">
            <strong>Registration Number</strong>
            <span><?= htmlspecialchars($child['student_reg_no']) ?></span>
        </div>
        <div class="detail-item">
            <strong>Grade / Level</strong>
            <span><?= htmlspecialchars($child['class_grade']) ?></span>
        </div>
        <div class="detail-item">
            <strong>Enrollment Status</strong>
            <span><?= ucfirst(htmlspecialchars($child['enrollment_status'])) ?></span>
        </div>
    </div>

    <div class="summary-stats">
        <div class="stat-box">
            <strong>Overall Mastery</strong>
            <span><?= $progressData['mastery']['total'] > 0 ? round(($progressData['mastery']['mastered'] / $progressData['mastery']['total']) * 100) : 0 ?>%</span>
        </div>
        <div class="stat-box">
            <strong>Competencies Mastered</strong>
            <span><?= (int)$progressData['mastery']['mastered'] ?> / <?= (int)$progressData['mastery']['total'] ?></span>
        </div>
        <div class="stat-box">
            <strong>Avg Assessment Score</strong>
            <span><?php 
                $totalScore = 0; $count = 0;
                foreach($assessments as $a) { if($a['status'] === 'graded') { $totalScore += $a['percentage']; $count++; } }
                echo $count > 0 ? round($totalScore / $count, 1) . '%' : 'N/A';
            ?></span>
        </div>
    </div>

    <h3>Detailed Competency Achievement</h3>
    <table>
        <thead>
            <tr>
                <th>Core Competency</th>
                <th>Status</th>
                <th>Rating</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($progressData['competencies'] as $c): ?>
            <tr>
                <td><?= htmlspecialchars($c['title']) ?></td>
                <td>
                    <?php if ($c['mastery_status'] === 'fully_mastered'): ?>
                        <span class="badge" style="background:#8b5cf6;color:#fff">Fully Mastered</span>
                    <?php elseif ($c['mastery_status'] === 'mastered'): ?>
                        <span class="badge badge-success">Mastered</span>
                    <?php elseif ($c['mastery_status'] === 'in_progress'): ?>
                        <span class="badge badge-pending">Developing</span>
                    <?php else: ?>
                        <span class="badge badge-inactive">Not Started</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?= $c['score'] ? Controller::getCbcPerformanceLevel((int)$c['score']) . ' (' . (int)$c['score'] . '%)' : '-' ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h3>Recent Assessment Results</h3>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Subject</th>
                <th>Assessment Title</th>
                <th>Score</th>
                <th>Teacher Remarks</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($assessments)): ?>
            <tr><td colspan="5" style="text-align:center">No assessment results available.</td></tr>
            <?php else: ?>
                <?php foreach (array_slice($assessments, 0, 10) as $a): ?>
                <tr>
                    <td><?= date('d M Y', strtotime($a['submitted_at'] ?? $a['created_at'])) ?></td>
                    <td><?= htmlspecialchars($a['subject']) ?></td>
                    <td><?= htmlspecialchars($a['title']) ?></td>
                    <td style="font-weight:700">
                        <?= $a['percentage'] ? Controller::getCbcPerformanceLevel((int)$a['percentage']) . ' (' . (int)$a['percentage'] . '%)' : 'Ungraded' ?>
                    </td>
                    <td style="font-size:0.8rem"><?= htmlspecialchars($a['teacher_remark'] ?? 'N/A') ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        <p>This is an officially generated digital progress report from the CBE LMS Portal.</p>
        <p>&copy; <?= date('Y') ?> CBE LMS. All Rights Reserved.</p>
    </div>

</body>
</html>
