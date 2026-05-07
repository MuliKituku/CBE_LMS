<?php

class Student extends Model {

    /* ============================================================
       GET FULL STUDENT PROFILE  (user + students row + grade)
    ============================================================*/
    public function getProfile(int $userId): array|false
    {
        $stmt = $this->db->prepare("
            SELECT
                u.id          AS user_id,
                u.fullname,
                u.email,
                u.student_reg_no,
                u.status,
                u.created_at  AS enrolled_at,
                s.id          AS student_id,
                s.class_grade,
                s.gender,
                s.date_of_birth,
                s.passport_photo
            FROM users u
            JOIN students s ON u.id = s.user_id
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    /* ============================================================
       DASHBOARD STATS  (counts for stat cards)
    ============================================================*/
    public function getDashboardStats(int $studentId): array
    {
        $q = fn(string $sql, array $p = []) => (function() use ($sql, $p) {
            $s = $this->db->prepare($sql);
            $s->execute($p);
            $res = $s->fetch();
            return (int)($res['c'] ?? 0);
        })();

        $overallScore = $this->getOverallScore($studentId);
        
        return [
            'lessons_available'       => $q("SELECT COUNT(*) AS c FROM lessons WHERE is_published = 1 AND class_grade = (SELECT class_grade FROM students WHERE id = ?)", [$studentId]),
            'upcoming_assessments'    => $q(
                "SELECT COUNT(*) AS c FROM assessments a
                 LEFT JOIN student_assessments sa ON sa.assessment_id = a.id AND sa.student_id = ?
                 WHERE a.is_published = 1 AND (sa.status IS NULL OR sa.status = 'pending')
                   AND a.class_grade = (SELECT class_grade FROM students WHERE id = ?)
                   AND (a.due_date IS NULL OR a.due_date >= NOW())",
                [$studentId, $studentId]
            ),
            'mastered_competencies'   => $q(
                "SELECT COUNT(*) AS c FROM student_core_competences
                 WHERE student_id = ? AND status = 'mastered'",
                [$studentId]
            ),
            'unread_notifications'    => $q(
                "SELECT COUNT(*) AS c FROM notifications
                 WHERE user_id = (SELECT user_id FROM students WHERE id = ?) AND is_read = 0",
                [$studentId]
            ),
            'completed_assessments'   => $q(
                "SELECT COUNT(*) AS c FROM student_assessments
                 WHERE student_id = ? AND status IN ('submitted','graded')",
                [$studentId]
            ),
            'overall_score'           => $overallScore
        ];
    }

    public function getOverallScore(int $studentId): int
    {
        $stmt = $this->db->prepare("
            SELECT AVG(percentage) as avg_score 
            FROM student_assessments 
            WHERE student_id = ? AND status IN ('submitted', 'graded')
        ");
        $stmt->execute([$studentId]);
        $res = $stmt->fetch();
        return (int)($res['avg_score'] ?? 0);
    }

    /* ============================================================
       GET LESSONS  (filtered by grade + optional subject)
    ============================================================*/
    public function getLessons(string $grade, string $subject = ''): array
    {
        $sql    = "
            SELECT l.*, u.fullname AS teacher_name
            FROM lessons l
            JOIN users u ON l.teacher_id = u.id
            WHERE l.is_published = 1 AND l.class_grade = ?
        ";
        $params = [$grade];

        if ($subject) {
            $sql    .= " AND l.subject = ?";
            $params[] = $subject;
        }

        $sql .= " ORDER BY l.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /* ============================================================
       GET LESSON DETAIL + RESOURCES
    ============================================================*/
    public function getLessonWithResources(int $lessonId): array|false
    {
        $stmt = $this->db->prepare("
            SELECT l.*, u.fullname AS teacher_name
            FROM lessons l
            JOIN users u ON l.teacher_id = u.id
            WHERE l.id = ? AND l.is_published = 1
        ");
        $stmt->execute([$lessonId]);
        $lesson = $stmt->fetch();

        if (!$lesson) return false;

        $stmt = $this->db->prepare("
            SELECT * FROM lesson_resources WHERE lesson_id = ? ORDER BY id
        ");
        $stmt->execute([$lessonId]);
        $lesson['resources'] = $stmt->fetchAll();

        return $lesson;
    }

    /* ============================================================
       GET ALL SUBJECTS FOR A GRADE  (for filter dropdown)
    ============================================================*/
    public function getSubjectsForGrade(string $grade): array
    {
        $stmt = $this->db->prepare("
            SELECT DISTINCT subject FROM lessons WHERE class_grade = ? AND is_published = 1 ORDER BY subject
        ");
        $stmt->execute([$grade]);
        return array_column($stmt->fetchAll(), 'subject');
    }

    /* ============================================================
       GET COMPETENCIES + MASTERY FOR STUDENT
    ============================================================*/
    public function getCompetencies(int $studentId, string $grade): array
    {
        $stmt = $this->db->prepare("
            SELECT c.id, c.name AS title, '' AS subject, 'Core CBE Competence' AS description,
                   COALESCE(sc.status, 'not_started') AS mastery_status,
                   sc.score
            FROM cbc_core_competences c
            LEFT JOIN student_core_competences sc ON sc.competence_id = c.id AND sc.student_id = ?
            ORDER BY c.id
        ");
        $stmt->execute([$studentId]);
        return $stmt->fetchAll();
    }

    /* ============================================================
       GET ASSESSMENTS FOR STUDENT  (upcoming + completed)
    ============================================================*/
    public function getAssessments(int $studentId, string $grade): array
    {
        $stmt = $this->db->prepare("
            SELECT a.*,
                   u.fullname          AS teacher_name,
                   sa.status           AS attempt_status,
                   sa.score,
                   sa.max_score,
                   sa.percentage,
                   sa.submitted_at,
                   sa.attempts_count
            FROM assessments a
            JOIN users u ON a.teacher_id = u.id
            LEFT JOIN student_assessments sa ON sa.assessment_id = a.id AND sa.student_id = ?
            WHERE a.is_published = 1 AND a.class_grade = ?
            ORDER BY a.available_until ASC, a.created_at DESC
        ");
        $stmt->execute([$studentId, $grade]);
        $assessments = $stmt->fetchAll();

        $now = time();
        foreach ($assessments as &$a) {
            $from  = strtotime($a['available_from']);
            $until = strtotime($a['available_until']);

            if ($now < $from) {
                $a['timing_status'] = 'upcoming';
            } elseif ($now > $until) {
                $a['timing_status'] = 'expired';
            } else {
                $a['timing_status'] = 'open';
            }
        }
        return $assessments;
    }

    /* ============================================================
       GET ASSESSMENT DETAIL + INTERACTIONS  (for quiz page)
    ============================================================*/
    public function getAssessmentDetail(int $assessmentId): array|false
    {
        $stmt = $this->db->prepare("
            SELECT a.*, u.fullname AS teacher_name
            FROM assessments a
            JOIN users u ON a.teacher_id = u.id
            WHERE a.id = ?
        ");
        $stmt->execute([$assessmentId]);
        $assessment = $stmt->fetch();
        if (!$assessment) return false;

        // Prefer new assessment_interactions table; fall back to legacy assessment_questions
        $stmt = $this->db->prepare(
            "SELECT id, interaction_type AS type, question, options, correct_answer, marks, hint
             FROM assessment_interactions WHERE assessment_id = ? ORDER BY sort_order, id"
        );
        $stmt->execute([$assessmentId]);
        $interactions = $stmt->fetchAll();

        if (empty($interactions)) {
            // Legacy fallback
            $stmt = $this->db->prepare(
                "SELECT id, 'mcq' AS type, question, option_a, option_b, option_c, option_d, correct AS correct_answer, marks, NULL AS options, NULL AS hint
                 FROM assessment_questions WHERE assessment_id = ? ORDER BY sort_order, id"
            );
            $stmt->execute([$assessmentId]);
            $legacyRows = $stmt->fetchAll();
            foreach ($legacyRows as &$lr) {
                $lr['options'] = json_encode([
                    ['id'=>'a','text'=>$lr['option_a']??''],
                    ['id'=>'b','text'=>$lr['option_b']??''],
                    ['id'=>'c','text'=>$lr['option_c']??''],
                    ['id'=>'d','text'=>$lr['option_d']??''],
                ]);
            }
            $interactions = $legacyRows;
        } else {
            // Decode JSON options for rendering
            foreach ($interactions as &$item) {
                if (!empty($item['options']) && is_string($item['options'])) {
                    // keep as string – rendered in view via json_decode
                }
            }
        }

        $assessment['questions'] = $interactions;
        return $assessment;
    }

    /* ============================================================
       SUBMIT ASSESSMENT  (auto-grade MCQ, flag essay for manual)
    ============================================================*/
    public function submitAssessment(int $studentId, int $assessmentId, array $answers): array
    {
        // Prefer new assessment_interactions; fall back to legacy assessment_questions
        $stmt = $this->db->prepare(
            "SELECT id, interaction_type AS type, correct_answer AS correct, marks FROM assessment_interactions WHERE assessment_id = ? ORDER BY sort_order, id"
        );
        $stmt->execute([$assessmentId]);
        $interactions = $stmt->fetchAll();

        $useLegacy = false;
        if (empty($interactions)) {
            $stmt = $this->db->prepare("SELECT id, 'mcq' AS type, correct AS correct, marks FROM assessment_questions WHERE assessment_id = ? ORDER BY sort_order, id");
            $stmt->execute([$assessmentId]);
            $interactions = $stmt->fetchAll();
            $useLegacy = true;
        }

        $maxScore   = array_sum(array_column($interactions, 'marks'));
        $score      = 0;
        $hasManual  = false;  // true if any text_submission question exists => teacher must review

        // Save per-question answers
        $this->db->prepare("DELETE FROM student_assessment_answers WHERE student_id = ? AND assessment_id = ?")->execute([$studentId, $assessmentId]);

        $insAns = $this->db->prepare(
            "INSERT INTO student_assessment_answers (student_id, assessment_id, interaction_id, answer_given, file_url, score_achieved)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE answer_given=VALUES(answer_given), file_url=VALUES(file_url), score_achieved=VALUES(score_achieved)"
        );

        foreach ($interactions as $q) {
            $givenRaw   = $answers[$q['id']] ?? '';
            $given      = is_array($givenRaw) ? implode('; ', $givenRaw) : trim((string)$givenRaw);
            $questionScore = 0;
            $fileUrl    = null;

            if ($q['type'] === 'mcq' || $q['type'] === 'true_false') {
                // Auto-grade: case-insensitive trim match
                if (strtolower(trim($given)) === strtolower(trim($q['correct'] ?? ''))) {
                    $questionScore = (int)$q['marks'];
                    $score += $questionScore;
                }
            } elseif ($q['type'] === 'fill_blank') {
                // Auto-grade fill-in-the-blank if correct answer set
                if (!empty($q['correct']) && strtolower(trim($given)) === strtolower(trim($q['correct']))) {
                    $questionScore = (int)$q['marks'];
                    $score += $questionScore;
                } else {
                    $hasManual = true; // Fallback to teacher review if mismatched or no key
                }
            } elseif ($q['type'] === 'file_upload') {
                $hasManual = true;
                // Handle uploaded file if present
                if (isset($_FILES['files']['name'][$q['id']]) && $_FILES['files']['error'][$q['id']] === UPLOAD_ERR_OK) {
                    $tmpName = $_FILES['files']['tmp_name'][$q['id']];
                    $name    = basename($_FILES['files']['name'][$q['id']]);
                    $ext     = pathinfo($name, PATHINFO_EXTENSION);
                    $newPath = 'uploads/assessments/' . uniqid('asn_', true) . '.' . $ext;
                    
                    if (!is_dir(BASE_PATH . '/public/uploads/assessments')) {
                        mkdir(BASE_PATH . '/public/uploads/assessments', 0777, true);
                    }
                    
                    if (move_uploaded_file($tmpName, BASE_PATH . '/public/' . $newPath)) {
                        $fileUrl = $newPath;
                    }
                }
            } else {
                // text_submission => teacher reviews
                $hasManual = true;
            }

            if (!$useLegacy) {
                $insAns->execute([$studentId, $assessmentId, $q['id'], $given, $fileUrl, $questionScore]);
            }
        }

        $percentage  = $maxScore > 0 ? round($score / $maxScore * 100, 2) : 0;
        // If manual questions exist, mark 'submitted' so teacher reviews; else mark 'graded'
        $finalStatus = $hasManual ? 'submitted' : 'graded';

        // Upsert student_assessments
        $stmt = $this->db->prepare("
            INSERT INTO student_assessments
                (student_id, assessment_id, score, max_score, percentage, status, attempts_count, submitted_at)
            VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
            ON DUPLICATE KEY UPDATE
                score = VALUES(score), max_score = VALUES(max_score),
                percentage = VALUES(percentage), status = VALUES(status), 
                attempts_count = attempts_count + 1, submitted_at = NOW()
        ");
        $stmt->execute([$studentId, $assessmentId, $score, $maxScore, $percentage, $finalStatus]);

        // If fully autograded, cascade CBE competency mapping immediately
        if (!$hasManual && $percentage > 0) {
            $this->mapCBECompetencies($studentId, $assessmentId, (int)$percentage);
        }

        return [
            'score'      => $score,
            'max_score'  => $maxScore,
            'percentage' => $percentage,
            'graded'     => !$hasManual,
        ];
    }

    private function mapCBECompetencies(int $studentId, int $assessmentId, int $pct): void
    {
        $cbeStatus = 'not_started';
        if ($pct > 75) $cbeStatus = 'fully_mastered';
        elseif ($pct > 50) $cbeStatus = 'mastered';
        elseif ($pct > 25) $cbeStatus = 'in_progress';

        $stmtC = $this->db->prepare("SELECT competence_id FROM assessment_core_competences WHERE assessment_id = ?");
        $stmtC->execute([$assessmentId]);
        $competences = $stmtC->fetchAll(\PDO::FETCH_COLUMN);

        if (!empty($competences)) {
            $upd = $this->db->prepare("
                INSERT INTO student_core_competences (student_id, competence_id, status, score, updated_at)
                VALUES (?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    status = IF(VALUES(score) >= COALESCE(score,0), VALUES(status), status),
                    score  = IF(VALUES(score) >= COALESCE(score,0), VALUES(score), score),
                    updated_at = NOW()
            ");
            foreach ($competences as $cId) {
                $upd->execute([$studentId, $cId, $cbeStatus, $pct]);
            }
        }
    }

    /* ============================================================
       PROGRESS CHART DATA
    ============================================================*/
    public function getProgressData(int $studentId, string $grade): array
    {
        // Competency mastery breakdown
        $stmt = $this->db->prepare("
            SELECT
                SUM(CASE WHEN sc.status = 'mastered'     THEN 1 ELSE 0 END) AS mastered,
                SUM(CASE WHEN sc.status = 'in_progress'  THEN 1 ELSE 0 END) AS in_progress,
                SUM(CASE WHEN sc.status = 'not_started' OR sc.status IS NULL THEN 1 ELSE 0 END) AS not_started,
                COUNT(c.id) AS total
            FROM cbc_core_competences c
            LEFT JOIN student_core_competences sc ON sc.competence_id = c.id AND sc.student_id = ?
        ");
        $stmt->execute([$studentId]);
        $mastery = $stmt->fetch();

        // Per-subject lesson count
        $stmt = $this->db->prepare("
            SELECT subject, COUNT(*) AS total
            FROM lessons WHERE class_grade = ? AND is_published = 1
            GROUP BY subject ORDER BY subject
        ");
        $stmt->execute([$grade]);
        $lessonsBySubject = $stmt->fetchAll();

        // Assessment average percentage per subject
        $stmt = $this->db->prepare("
            SELECT a.subject, ROUND(AVG(sa.percentage),1) AS avg_pct
            FROM student_assessments sa
            JOIN assessments a ON sa.assessment_id = a.id
            WHERE sa.student_id = ? AND sa.status IN ('submitted','graded')
            GROUP BY a.subject
        ");
        $stmt->execute([$studentId]);
        $avgScores = $stmt->fetchAll();

        return [
            'mastery'           => $mastery,
            'lessons_by_subject'=> $lessonsBySubject,
            'avg_scores'        => $avgScores,
        ];
    }

    /* ============================================================
       GET TEACHER FEEDBACK
    ============================================================*/
    public function getFeedback(int $studentId): array
    {
        $stmt = $this->db->prepare("
            SELECT tf.*, u.fullname AS teacher_name
            FROM teacher_feedback tf
            JOIN users u ON tf.teacher_id = u.id
            WHERE tf.student_id = ?
            ORDER BY tf.created_at DESC
        ");
        $stmt->execute([$studentId]);
        return $stmt->fetchAll();
    }

    /* ============================================================
       GET NOTIFICATIONS
    ============================================================*/
    public function getNotifications(int $userId, int $limit = 20): array
    {
        $limit = (int)$limit;
        $stmt = $this->db->prepare("
            SELECT * FROM notifications
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT $limit
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getUnreadNotificationCount(int $userId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        return (int)$stmt->fetch()['c'];
    }

    public function markNotificationRead(int $notifId, int $userId): void
    {
        $stmt = $this->db->prepare("
            UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$notifId, $userId]);
    }

    public function markAllNotificationsRead(int $userId): void
    {
        $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$userId]);
    }

    /* ============================================================
       DISCUSSIONS  (threaded per lesson)
    ============================================================*/
    public function getDiscussions(int $lessonId): array
    {
        // Top-level posts
        $stmt = $this->db->prepare("
            SELECT d.*, u.fullname, u.role
            FROM discussions d
            JOIN users u ON d.user_id = u.id
            WHERE d.lesson_id = ? AND d.parent_id IS NULL
            ORDER BY d.created_at ASC
        ");
        $stmt->execute([$lessonId]);
        $posts = $stmt->fetchAll();

        foreach ($posts as &$post) {
            $r = $this->db->prepare("
                SELECT d.*, u.fullname, u.role
                FROM discussions d
                JOIN users u ON d.user_id = u.id
                WHERE d.parent_id = ?
                ORDER BY d.created_at ASC
            ");
            $r->execute([$post['id']]);
            $post['replies'] = $r->fetchAll();
        }

        return $posts;
    }

    public function postDiscussion(int $userId, int $lessonId, ?int $parentId, string $message): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO discussions (lesson_id, user_id, parent_id, message)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$lessonId, $userId, $parentId, $message]);
        return (int)$this->db->lastInsertId();
    }

    /* ============================================================
       GET UPCOMING ASSESSMENTS  (for dashboard widget)
    ============================================================*/
    public function getUpcomingAssessments(int $studentId, string $grade, int $limit = 5): array
    {
        $limit = (int)$limit;
        $stmt  = $this->db->prepare("
            SELECT a.*, u.fullname AS teacher_name,
                   sa.status AS attempt_status
            FROM assessments a
            JOIN users u ON a.teacher_id = u.id
            LEFT JOIN student_assessments sa ON sa.assessment_id = a.id AND sa.student_id = ?
            WHERE a.is_published = 1 AND a.class_grade = ?
              AND (sa.status IS NULL OR sa.status = 'pending')
              AND (a.due_date IS NULL OR a.due_date >= NOW())
            ORDER BY a.due_date ASC
            LIMIT $limit
        ");
        $stmt->execute([$studentId, $grade]);
        return $stmt->fetchAll();
    }

    /* ============================================================
       RECENT ACTIVITY FEED
    ============================================================*/
    public function getRecentActivity(int $studentId, int $limit = 8): array
    {
        $limit = (int)$limit;
        $stmt = $this->db->prepare("
            (SELECT 'assessment' as type, a.title as title, sa.submitted_at as date, sa.status as detail
             FROM student_assessments sa
             JOIN assessments a ON sa.assessment_id = a.id
             WHERE sa.student_id = ? AND sa.status IN ('submitted', 'graded'))
            UNION ALL
            (SELECT 'feedback' as type, tf.message as title, tf.created_at as date, u.fullname as detail
             FROM teacher_feedback tf
             JOIN users u ON tf.teacher_id = u.id
             WHERE tf.student_id = ?)
            ORDER BY date DESC
            LIMIT $limit
        ");
        $stmt->execute([$studentId, $studentId]);
        return $stmt->fetchAll();
    }

    /* ============================================================
       CLASS ANNOUNCEMENTS
    ============================================================*/
    public function getClassAnnouncements(string $grade, int $limit = 5): array
    {
        $limit = (int)$limit;
        $stmt = $this->db->prepare("
            SELECT ta.*, u.fullname as teacher_name
            FROM teacher_announcements ta
            JOIN users u ON ta.teacher_id = u.id
            WHERE ta.class_grade = ?
            ORDER BY ta.created_at DESC
            LIMIT $limit
        ");
        $stmt->execute([$grade]);
        return $stmt->fetchAll();
    }

    /* ============================================================
       UPDATE PROFILE
    ============================================================*/
    public function updateProfile(int $userId, array $data): bool
    {
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare("UPDATE users SET fullname = ?, email = ? WHERE id = ?");
            $stmt->execute([$data['fullname'], $data['email'], $userId]);
            $stmt = $this->db->prepare("UPDATE students SET gender = ?, date_of_birth = ? WHERE user_id = ?");
            $stmt->execute([$data['gender'], $data['date_of_birth'], $userId]);
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return false;
        }
    }

    /* ============================================================
       LESSON REGISTRATION & TEACHER LINKING
    ============================================================*/

    /**
     * Registers a student for a lesson and links them with the teacher.
     */
    public function registerLesson(int $studentId, int $lessonId): bool
    {
        try {
            $this->db->beginTransaction();

            // 1. Get Teacher ID for this lesson
            $stmt = $this->db->prepare("SELECT teacher_id FROM lessons WHERE id = ?");
            $stmt->execute([$lessonId]);
            $teacherId = $stmt->fetchColumn();

            if (!$teacherId) {
                throw new Exception("Lesson not found.");
            }

            // 2. Insert into student_lessons
            $stmt = $this->db->prepare("
                INSERT IGNORE INTO student_lessons (student_id, lesson_id)
                VALUES (?, ?)
            ");
            $stmt->execute([$studentId, $lessonId]);

            // 3. Link with Teacher (insert into teacher_students)
            $stmt = $this->db->prepare("
                INSERT IGNORE INTO teacher_students (teacher_id, student_id)
                VALUES (?, ?)
            ");
            $stmt->execute([(int)$teacherId, $studentId]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log("Student::registerLesson Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Returns an array of lesson IDs the student is registered for.
     */
    public function getRegisteredLessonIds(int $studentId): array
    {
        $stmt = $this->db->prepare("SELECT lesson_id FROM student_lessons WHERE student_id = ?");
        $stmt->execute([$studentId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Checks if a student is already registered for a specific lesson.
     */
    public function isRegistered(int $studentId, int $lessonId): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM student_lessons WHERE student_id = ? AND lesson_id = ?");
        $stmt->execute([$studentId, $lessonId]);
        return (bool)$stmt->fetch();
    }

    /* ============================================================
       CBE MULTIMEDIA LESSON METHODS
    ============================================================*/

    /**
     * Returns lesson + interactions + activity + student's existing answers + current progress step.
     */
    public function getLessonWithInteractions(int $lessonId, int $studentId)
    {
        $stmt = $this->db->prepare("
            SELECT l.*, u.fullname AS teacher_name
            FROM lessons l
            JOIN users u ON l.teacher_id = u.id
            WHERE l.id = ? AND l.is_published = 1
        ");
        $stmt->execute([$lessonId]);
        $lesson = $stmt->fetch();
        if (!$lesson) return false;

        // Resources
        $stmt = $this->db->prepare("SELECT * FROM lesson_resources WHERE lesson_id = ? ORDER BY id");
        $stmt->execute([$lessonId]);
        $lesson['resources'] = $stmt->fetchAll();

        // Interactions with student's answers
        $stmt = $this->db->prepare("
            SELECT li.*, c.name AS competency_title,
                   si.answer_given, si.is_correct AS student_is_correct
            FROM lesson_interactions li
            LEFT JOIN student_interactions si
                   ON si.interaction_id = li.id AND si.student_id = ?
            LEFT JOIN cbc_core_competences c ON li.competency_id = c.id
            WHERE li.lesson_id = ?
            ORDER BY li.sort_order ASC
        ");
        $stmt->execute([$studentId, $lessonId]);
        $lesson['interactions'] = $stmt->fetchAll();

        // Decode options JSON for each interaction
        foreach ($lesson['interactions'] as &$inter) {
            $inter['options_arr'] = $inter['options'] ? json_decode($inter['options'], true) : [];
        }
        unset($inter);

        // Activity
        $stmt = $this->db->prepare("SELECT * FROM lesson_activities WHERE lesson_id = ? LIMIT 1");
        $stmt->execute([$lessonId]);
        $lesson['activity'] = $stmt->fetch() ?: null;

        // Student's activity submission (if any)
        if ($lesson['activity']) {
            $stmt = $this->db->prepare("
                SELECT * FROM lesson_activity_submissions
                WHERE lesson_id = ? AND student_id = ? LIMIT 1
            ");
            $stmt->execute([$lessonId, $studentId]);
            $lesson['activity_submission'] = $stmt->fetch() ?: null;
        }

        // Lesson progress
        $lesson['progress'] = $this->getLessonProgress($lessonId, $studentId);

        return $lesson;
    }

    /**
     * Get student progress for a lesson.
     */
    public function getLessonProgress(int $lessonId, int $studentId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM lesson_progress WHERE lesson_id = ? AND student_id = ? LIMIT 1");
        $stmt->execute([$lessonId, $studentId]);
        return $stmt->fetch() ?: ['step_reached' => 'media', 'interaction_score' => null, 'completed_at' => null];
    }

    /**
     * Submit a single interaction answer. Auto-grades MCQ, true_false, fill_blank.
     * Returns ['is_correct', 'correct_answer', 'score', 'hint'].
     */
    public function submitInteraction(int $lessonId, int $studentId, int $interactionId, string $answer): array
    {
        // Fetch interaction
        $stmt = $this->db->prepare("SELECT * FROM lesson_interactions WHERE id = ? AND lesson_id = ?");
        $stmt->execute([$interactionId, $lessonId]);
        $interaction = $stmt->fetch();
        if (!$interaction) return ['is_correct' => false, 'correct_answer' => '', 'score' => 0, 'hint' => ''];

        // Robust autograding (MCQ letters or text)
        $cleanAnswer  = strtolower(trim($answer));
        $cleanCorrect = strtolower(trim($interaction['correct_answer']));
        $isCorrect    = ($cleanAnswer === $cleanCorrect) ? 1 : 0;

        // Upsert student answer
        $stmt = $this->db->prepare("
            INSERT INTO student_interactions (lesson_id, student_id, interaction_id, answer_given, is_correct)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE answer_given = VALUES(answer_given), is_correct = VALUES(is_correct), answered_at = NOW()
        ");
        $stmt->execute([$lessonId, $studentId, $interactionId, $answer, $isCorrect]);

        // Progression of Mastery
        if ($isCorrect) {
            // Priority 1: Specific interaction competency
            $compIds = [];
            if ($interaction['competency_id']) {
                $compIds[] = (int)$interaction['competency_id'];
            } else {
                // Priority 2: Lesson-level competencies
                $lcc = $this->db->prepare("SELECT competence_id FROM lesson_core_competences WHERE lesson_id = ?");
                $lcc->execute([$lessonId]);
                $compIds = $lcc->fetchAll(PDO::FETCH_COLUMN);
            }

            foreach ($compIds as $cId) {
                $this->db->prepare("
                    INSERT INTO student_core_competences (student_id, competence_id, status)
                    VALUES (?, ?, 'in_progress')
                    ON DUPLICATE KEY UPDATE status = IF(status = 'mastered', 'mastered', 'in_progress')
                ")->execute([$studentId, $cId]);
            }
        }

        // Recalculate total score for this lesson context
        $newTotalScore = $this->calcInteractionScore($lessonId, $studentId);
        $this->db->prepare("UPDATE lesson_progress SET interaction_score = ? WHERE lesson_id = ? AND student_id = ?")
                 ->execute([$newTotalScore, $lessonId, $studentId]);

        return [
            'is_correct'     => (bool)$isCorrect,
            'correct_answer' => $interaction['correct_answer'],
            'score'          => $newTotalScore,
            'hint'           => $isCorrect ? '' : ($interaction['hint'] ?: '')
        ];
    }

    /**
     * Submit activity response. Handles text and/or file.
     */
    public function submitActivity(int $lessonId, int $studentId, int $activityId, string $text, ?string $fileUrl): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO lesson_activity_submissions (lesson_id, student_id, activity_id, response_text, file_url)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE response_text = VALUES(response_text), file_url = VALUES(file_url), submitted_at = NOW()
        ");
        $stmt->execute([$lessonId, $studentId, $activityId, $text ?: null, $fileUrl]);
    }

    /**
     * Advance (or set) the lesson progress step for a student.
     * Steps: media → interaction → activity → assessment → completed
     */
    public function advanceLessonProgress(int $lessonId, int $studentId, string $step, ?int $interactionScore = null): void
    {
        $stepOrder = ['media' => 1, 'interaction' => 2, 'activity' => 3, 'assessment' => 4, 'completed' => 5];
        $current   = $this->getLessonProgress($lessonId, $studentId);
        $currentOrd = $stepOrder[$current['step_reached']] ?? 0;
        $newOrd     = $stepOrder[$step] ?? 0;

        if ($newOrd < $currentOrd) return; // Only allow current or forward

        $stmt = $this->db->prepare("
            INSERT INTO lesson_progress (lesson_id, student_id, step_reached, interaction_score, completed_at)
            VALUES (?, ?, ?, ?, " . ($step === 'completed' ? 'NOW()' : 'NULL') . ")
            ON DUPLICATE KEY UPDATE
                step_reached      = IF($newOrd > $currentOrd, VALUES(step_reached), step_reached),
                interaction_score = COALESCE(VALUES(interaction_score), interaction_score),
                completed_at      = " . ($step === 'completed' ? 'NOW()' : 'completed_at') . "
        ");
        $stmt->execute([$lessonId, $studentId, $step, $interactionScore]);

        // Mark competencies 'mastered' on lesson completion
        if ($step === 'completed') {
            $lcc = $this->db->prepare("SELECT competence_id FROM lesson_core_competences WHERE lesson_id = ?");
            $lcc->execute([$lessonId]);
            $compIds = $lcc->fetchAll(PDO::FETCH_COLUMN);
            foreach ($compIds as $cId) {
                $this->db->prepare("
                    INSERT INTO student_core_competences (student_id, competence_id, status)
                    VALUES (?, ?, 'mastered')
                    ON DUPLICATE KEY UPDATE status = 'mastered'
                ")->execute([$studentId, $cId]);
            }
        }
    }

    /**
     * Calculate total interaction score for a lesson for a student (%).
     */
    public function calcInteractionScore(int $lessonId, int $studentId): int
    {
        $stmt = $this->db->prepare("
            SELECT SUM(li.marks) AS max_marks,
                   SUM(CASE WHEN si.is_correct = 1 THEN li.marks ELSE 0 END) AS scored
            FROM lesson_interactions li
            LEFT JOIN student_interactions si ON si.interaction_id = li.id AND si.student_id = ?
            WHERE li.lesson_id = ?
        ");
        $stmt->execute([$studentId, $lessonId]);
        $row = $stmt->fetch();
        if (!$row || !$row['max_marks']) return 0;
        return (int)round(($row['scored'] / $row['max_marks']) * 100);
    }
}

