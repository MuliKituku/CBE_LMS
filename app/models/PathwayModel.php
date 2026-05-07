<?php
class PathwayModel extends Model {

    public function getAllQuestions(): array
    {
        $stmt = $this->db->query("SELECT * FROM pathway_surveys ORDER BY target_pathway ASC, id ASC");
        return $stmt->fetchAll();
    }

    public function createPathwayQuestion(string $question, string $pathway): void
    {
        $stmt = $this->db->prepare("INSERT INTO pathway_surveys (question, target_pathway) VALUES (?, ?)");
        $stmt->execute([$question, $pathway]);
    }

    public function updatePathwayQuestion(int $id, string $question, string $pathway): void
    {
        $stmt = $this->db->prepare("UPDATE pathway_surveys SET question = ?, target_pathway = ? WHERE id = ?");
        $stmt->execute([$question, $pathway, $id]);
    }

    public function deletePathwayQuestion(int $id): void
    {
        $stmt = $this->db->prepare("DELETE FROM pathway_surveys WHERE id = ?");
        $stmt->execute([$id]);
    }

    public function getSurveyQuestions(): array
    {
        // Get count from settings or default to 3
        $stmt = $this->db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'pathway_questions_per_group'");
        $stmt->execute();
        $limit = (int)($stmt->fetchColumn() ?: 3);

        $pathways = ['stem', 'social_sciences', 'arts_sports'];
        $allQuestions = [];

        foreach ($pathways as $pw) {
            $stmt = $this->db->prepare("SELECT * FROM pathway_surveys WHERE target_pathway = ? ORDER BY RAND() LIMIT $limit");
            $stmt->execute([$pw]);
            $allQuestions = array_merge($allQuestions, $stmt->fetchAll());
        }

        shuffle($allQuestions);
        return $allQuestions;
    }

    public function hasCompletedSurvey(int $studentId): bool
    {
        // Check if student has a retake flag
        $stmt = $this->db->prepare("SELECT pathway_survey_retake FROM students WHERE id = ?");
        $stmt->execute([$studentId]);
        $retake = (int)$stmt->fetchColumn();
        if ($retake === 1) return false;

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM student_pathway_responses WHERE student_id = ?");
        $stmt->execute([$studentId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function saveSurveyResponses(int $studentId, array $responses): void
    {
        // Replace existing
        $this->db->prepare("DELETE FROM student_pathway_responses WHERE student_id = ?")->execute([$studentId]);
        
        $stmt = $this->db->prepare("
            INSERT INTO student_pathway_responses (student_id, survey_id, score) 
            VALUES (?, ?, ?)
        ");
        foreach ($responses as $surveyId => $score) {
            $stmt->execute([$studentId, (int)$surveyId, (int)$score]);
        }
        
        // After saving, calculate recommendation
        $this->calculateRecommendation($studentId);
    }

    public function getStudentRecommendation(int $studentId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM student_pathway_recommendations WHERE student_id = ?");
        $stmt->execute([$studentId]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    private function calculateRecommendation(int $studentId): void
    {
        // 1. Calculate NEW Interest Score (max 5 * limit questions) -> mapped to 100%
        $stmt = $this->db->prepare("
            SELECT ps.target_pathway, SUM(spr.score) as total_score, COUNT(ps.id) as q_count
            FROM student_pathway_responses spr
            JOIN pathway_surveys ps ON spr.survey_id = ps.id
            WHERE spr.student_id = ?
            GROUP BY ps.target_pathway
        ");
        $stmt->execute([$studentId]);
        $surveyRows = $stmt->fetchAll();
        
        $newInterestPct = ['stem' => 0, 'social_sciences' => 0, 'arts_sports' => 0];
        foreach ($surveyRows as $row) {
            $maxPossible = (int)$row['q_count'] * 5;
            if ($maxPossible > 0) {
                $newInterestPct[$row['target_pathway']] = ($row['total_score'] / $maxPossible) * 100;
            }
        }

        // 2. Fetch History & Blending Logic (65% New / 35% Old)
        $stmt = $this->db->prepare("SELECT interest_history FROM student_pathway_recommendations WHERE student_id = ?");
        $stmt->execute([$studentId]);
        $historyStr = $stmt->fetchColumn();
        $history = $historyStr ? json_decode($historyStr, true) : null;

        $interestPct = $newInterestPct;
        if ($history) {
            foreach (['stem', 'social_sciences', 'arts_sports'] as $pw) {
                $old = $history[$pw] ?? 0;
                $new = $newInterestPct[$pw] ?? 0;
                // Blending: 65% New, 35% Old
                $interestPct[$pw] = ($new * 0.65) + ($old * 0.35);
            }
        }

        // 3. Clear retake flag
        $this->db->prepare("UPDATE students SET pathway_survey_retake = 0 WHERE id = ?")->execute([$studentId]);

        // 4. Calculate Academic Score from assessments
        $stmt = $this->db->prepare("
            SELECT a.subject, ROUND(AVG(sa.percentage), 1) as avg_pct
            FROM student_assessments sa
            JOIN assessments a ON sa.assessment_id = a.id
            WHERE sa.student_id = ? AND sa.status IN ('submitted','graded')
            GROUP BY a.subject
        ");
        $stmt->execute([$studentId]);
        $academicSubjects = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

        // Define which subjects match which pathway (Kenyan CBE)
        $stemSubjects = [
            'Mathematics', 'Science', 'Computer Science', 'Computer Studies', 
            'Physics', 'Biology', 'Chemistry', 'Agriculture', 'Home Science',
            'Pre-Technical'
        ];
        $socialSubjects = [
            'Social Studies', 'History', 'Geography', 'English', 'Kiswahili', 
            'Literature', 'IRE', 'CRE', 'HRE', 'Religious Education',
            'Pre-Career'
        ];
        $artsSubjects = [
            'Creative Arts', 'Physical Education', 'Music', 'Art', 'Design', 
            'Performing Arts', 'Media Studies', 'Sports'
        ];

        $academicPct = [
            'stem' => $this->avgScoreForGroup($academicSubjects, $stemSubjects),
            'social_sciences' => $this->avgScoreForGroup($academicSubjects, $socialSubjects),
            'arts_sports' => $this->avgScoreForGroup($academicSubjects, $artsSubjects),
        ];

        // 5. Combine: 60% Academic, 40% Interest
        $finalScore = [
            'stem' => ($academicPct['stem'] * 0.6) + ($interestPct['stem'] * 0.4),
            'social_sciences' => ($academicPct['social_sciences'] * 0.6) + ($interestPct['social_sciences'] * 0.4),
            'arts_sports' => ($academicPct['arts_sports'] * 0.6) + ($interestPct['arts_sports'] * 0.4),
        ];

        arsort($finalScore);
        $recommended = array_key_first($finalScore);

        // Store
        $stmt = $this->db->prepare("
            INSERT INTO student_pathway_recommendations (student_id, stem_score, social_score, arts_score, recommended_pathway)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                stem_score = VALUES(stem_score), social_score = VALUES(social_score), arts_score = VALUES(arts_score),
                recommended_pathway = VALUES(recommended_pathway)
        ");
        $stmt->execute([
            $studentId, 
            $finalScore['stem'], 
            $finalScore['social_sciences'], 
            $finalScore['arts_sports'], 
            $recommended
        ]);
    }

    public function launchSurveyForGrade(string $grade): void
    {
        // 1. Get all students in grade
        $stmt = $this->db->prepare("SELECT id FROM students WHERE class_grade = ?");
        $stmt->execute([$grade]);
        $studentIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($studentIds as $sId) {
            // Archive current interest profile if they have one
            $stmtInt = $this->db->prepare("
                SELECT ps.target_pathway, SUM(spr.score) as total_score, COUNT(ps.id) as q_count
                FROM student_pathway_responses spr
                JOIN pathway_surveys ps ON spr.survey_id = ps.id
                WHERE spr.student_id = ?
                GROUP BY ps.target_pathway
            ");
            $stmtInt->execute([$sId]);
            $surveyRows = $stmtInt->fetchAll();
            
            if ($surveyRows) {
                $interestPct = ['stem' => 0, 'social_sciences' => 0, 'arts_sports' => 0];
                foreach ($surveyRows as $row) {
                    $maxPossible = (int)$row['q_count'] * 5;
                    if ($maxPossible > 0) {
                        $interestPct[$row['target_pathway']] = ($row['total_score'] / $maxPossible) * 100;
                    }
                }
                
                // Save to history column in recommendations table
                $stmtHistory = $this->db->prepare("
                    INSERT INTO student_pathway_recommendations (student_id, stem_score, social_score, arts_score, recommended_pathway, interest_history)
                    VALUES (?, 0, 0, 0, 'stem', ?)
                    ON DUPLICATE KEY UPDATE interest_history = VALUES(interest_history)
                ");
                $stmtHistory->execute([$sId, json_encode($interestPct)]);
            }

            // Set retake flag
            $this->db->prepare("UPDATE students SET pathway_survey_retake = 1 WHERE id = ?")->execute([$sId]);
        }
    }

    private function avgScoreForGroup(array $allScores, array $groupKeys): float
    {
        $sum = 0; $count = 0;
        foreach ($groupKeys as $key) {
            foreach($allScores as $subj => $score) {
                if (stripos($subj, $key) !== false) {
                    $sum += $score;
                    $count++;
                }
            }
        }
        return $count > 0 ? ($sum / $count) : 0;
    }
}
