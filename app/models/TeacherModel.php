<?php
/* ── models/TeacherModel.php ──────────────────────────────
   Handles database interactions for Teacher Dashboard
   ─────────────────────────────────────────────────────────── */
class TeacherModel extends Model {

    /* ============================================================
       PROFILE AND STATS
    ============================================================*/
    public function getProfile(int $teacherId)
    {
        $stmt = $this->db->prepare("
            SELECT u.id, u.fullname, u.email, t.phone, u.status, u.created_at 
            FROM users u
            LEFT JOIN teachers t ON u.id = t.user_id
            WHERE u.id = ? AND u.role = 'teacher'
        ");
        $stmt->execute([$teacherId]);
        return $stmt->fetch();
    }

    public function getDashboardStats(int $teacherId): array
    {
        // Total classes assigned (distinct class grades from lessons)
        $stmt = $this->db->prepare("SELECT COUNT(DISTINCT class_grade) as c FROM lessons WHERE teacher_id = ?");
        $stmt->execute([$teacherId]);
        $classes = (int)$stmt->fetch()['c'];

        // Total students matching those grades
        $stmt = $this->db->prepare("
            SELECT COUNT(DISTINCT s.id) as c 
            FROM students s
            JOIN lessons l ON s.class_grade = l.class_grade
            WHERE l.teacher_id = ?
        ");
        $stmt->execute([$teacherId]);
        $students = (int)$stmt->fetch()['c'];

        // Pending assignments (student_assessments with status 'submitted' awaiting 'graded')
        $stmt = $this->db->prepare("
            SELECT COUNT(sa.student_id) as c 
            FROM student_assessments sa
            JOIN assessments a ON sa.assessment_id = a.id
            WHERE a.teacher_id = ? AND sa.status = 'submitted'
        ");
        $stmt->execute([$teacherId]);
        $pending = (int)$stmt->fetch()['c'];

        return [
            'assigned_classes' => $classes,
            'total_students'   => $students,
            'pending_grading'  => $pending
        ];
    }

    public function getClasses(int $teacherId): array
    {
        // Get list of distinct grades the teacher teaches, and count students
        $stmt = $this->db->prepare("
            SELECT l.class_grade, COUNT(DISTINCT s.id) as student_count
            FROM lessons l
            LEFT JOIN students s ON l.class_grade = s.class_grade
            WHERE l.teacher_id = ?
            GROUP BY l.class_grade
        ");
        $stmt->execute([$teacherId]);
        return $stmt->fetchAll();
    }

    /* ============================================================
       LESSONS (CRUD)
    ============================================================*/
    public function getLessons(int $teacherId): array
    {
        $stmt = $this->db->prepare("
            SELECT l.*,
                   (SELECT COUNT(*) FROM lesson_interactions li WHERE li.lesson_id = l.id) AS interaction_count,
                   (SELECT COUNT(*) FROM lesson_activities la WHERE la.lesson_id = l.id) AS has_activity,
                   (SELECT COUNT(*) FROM lesson_feedback lf WHERE lf.lesson_id = l.id) AS feedback_count
            FROM lessons l
            WHERE l.teacher_id = ? ORDER BY l.created_at DESC
        ");
        $stmt->execute([$teacherId]);
        return $stmt->fetchAll();
    }

    public function getLessonById(int $lessonId, int $teacherId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM lessons WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$lessonId, $teacherId]);
        $res = $stmt->fetch();
        if (!$res) return null;
        // Attach interactions and activity
        $res['interactions'] = $this->getInteractionsByLesson($lessonId);
        $res['activity']     = $this->getActivityByLesson($lessonId);
        // Attach core competences
        $compStmt = $this->db->prepare("SELECT competence_id FROM lesson_core_competences WHERE lesson_id = ?");
        $compStmt->execute([$lessonId]);
        $res['core_competences'] = $compStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Attach feedback count
        $fbStmt = $this->db->prepare("SELECT COUNT(*) FROM lesson_feedback WHERE lesson_id = ?");
        $fbStmt->execute([$lessonId]);
        $res['feedback_count'] = (int)$fbStmt->fetchColumn();

        return $res;
    }

    public function getLessonFeedback(int $lessonId): array
    {
        $stmt = $this->db->prepare("
            SELECT lf.*, u.fullname, u.role
            FROM lesson_feedback lf
            JOIN users u ON lf.user_id = u.id
            WHERE lf.lesson_id = ?
            ORDER BY lf.created_at ASC
        ");
        $stmt->execute([$lessonId]);
        return $stmt->fetchAll();
    }

    public function addLessonFeedback(int $lessonId, int $userId, string $message): void
    {
        $stmt = $this->db->prepare("INSERT INTO lesson_feedback (lesson_id, user_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$lessonId, $userId, $message]);
    }

    public function getCoreCompetencesList(): array
    {
        return $this->db->query("SELECT * FROM cbc_core_competences ORDER BY id ASC")->fetchAll();
    }

    public function createLesson(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO lessons (
                strand, subject, sub_strand, type, content_url, description, 
                introduction, content_delivery, summary, assignment,
                class_grade, education_level, learning_outcomes, interaction_method, activity_type, teacher_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['strand'],
            $data['subject'],
            $data['sub_strand'] ?? null,
            $data['type'],
            $data['content_url'] ?? '',
            $data['description'] ?? '',
            $data['introduction'] ?? null,
            $data['content_delivery'] ?? null,
            $data['summary'] ?? null,
            $data['assignment'] ?? null,
            $data['class_grade'],
            $data['education_level'] ?? 'primary',
            $data['learning_outcomes'] ?? '',
            $data['interaction_method'] ?? 'mcq',
            $data['activity_type'] ?? 'none',
            $data['teacher_id']
        ]);
        $lessonId = (int)$this->db->lastInsertId();

        if (!empty($data['core_competences']) && is_array($data['core_competences'])) {
            $stmtLCC = $this->db->prepare("INSERT INTO lesson_core_competences (lesson_id, competence_id) VALUES (?, ?)");
            foreach ($data['core_competences'] as $compId) {
                $stmtLCC->execute([$lessonId, (int)$compId]);
            }
        }
        
        return $lessonId;
    }

    public function updateLesson(int $lessonId, array $data): void
    {
        $stmt = $this->db->prepare("
            UPDATE lessons
            SET strand = ?, subject = ?, sub_strand = ?, type = ?, content_url = ?, description = ?,
                introduction = ?, content_delivery = ?, summary = ?, assignment = ?,
                class_grade = ?, education_level = ?, learning_outcomes = ?,
                interaction_method = ?, activity_type = ?
            WHERE id = ? AND teacher_id = ?
        ");
        $stmt->execute([
            $data['strand'],
            $data['subject'],
            $data['sub_strand'] ?? null,
            $data['type'],
            $data['content_url'] ?? '',
            $data['description'] ?? '',
            $data['introduction'] ?? null,
            $data['content_delivery'] ?? null,
            $data['summary'] ?? null,
            $data['assignment'] ?? null,
            $data['class_grade'],
            $data['education_level'] ?? 'primary',
            $data['learning_outcomes'] ?? '',
            $data['interaction_method'] ?? 'mcq',
            $data['activity_type'] ?? 'none',
            $lessonId,
            $data['teacher_id']
        ]);
        
        $this->db->prepare("DELETE FROM lesson_core_competences WHERE lesson_id = ?")->execute([$lessonId]);
        if (!empty($data['core_competences']) && is_array($data['core_competences'])) {
            $stmtLCC = $this->db->prepare("INSERT INTO lesson_core_competences (lesson_id, competence_id) VALUES (?, ?)");
            foreach ($data['core_competences'] as $compId) {
                $stmtLCC->execute([$lessonId, (int)$compId]);
            }
        }
    }

    public function deleteLesson(int $lessonId, int $teacherId): void
    {
        $stmt = $this->db->prepare("DELETE FROM lessons WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$lessonId, $teacherId]);
    }

    /* ============================================================
       INTERACTIONS & ACTIVITIES (CBE Multimedia)
    ============================================================*/
    public function saveInteractions(int $lessonId, array $interactions): void
    {
        // Clear old interactions for this lesson
        $this->db->prepare("DELETE FROM lesson_interactions WHERE lesson_id = ?")->execute([$lessonId]);

        $stmt = $this->db->prepare("
            INSERT INTO lesson_interactions
                (lesson_id, competency_id, sort_order, interaction_type, question, options, correct_answer, marks, hint)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        foreach ($interactions as $i => $item) {
            if (empty($item['question'])) continue;
            $options = !empty($item['options']) ? json_encode($item['options']) : null;
            $stmt->execute([
                $lessonId,
                $item['competency_id'] ?? null,
                $i,
                $item['interaction_type'] ?? 'mcq',
                $item['question'],
                $options,
                $item['correct_answer'] ?? '',
                $item['marks'] ?? 1,
                $item['hint'] ?? null
            ]);
        }
    }

    public function saveActivity(int $lessonId, array $activity): void
    {
        // Delete old activity first (one activity per lesson)
        $this->db->prepare("DELETE FROM lesson_activities WHERE lesson_id = ?")->execute([$lessonId]);

        if (empty($activity['description'])) return;

        $stmt = $this->db->prepare("
            INSERT INTO lesson_activities (lesson_id, title, description, submission_type, max_marks)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $lessonId,
            $activity['title'] ?? 'Activity Task',
            $activity['description'],
            $activity['submission_type'] ?? 'text',
            $activity['max_marks'] ?? 10
        ]);
    }

    public function getInteractionsByLesson(int $lessonId): array
    {
        $stmt = $this->db->prepare("
            SELECT li.*
            FROM lesson_interactions li
            WHERE li.lesson_id = ? ORDER BY li.sort_order ASC
        ");
        $stmt->execute([$lessonId]);
        return $stmt->fetchAll();
    }

    public function getActivityByLesson(int $lessonId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM lesson_activities WHERE lesson_id = ? LIMIT 1");
        $stmt->execute([$lessonId]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /* ============================================================
       STUDENTS & PROGRESS
    ============================================================*/
    public function getStudents(int $teacherId): array
    {
        // Get all students who are in grades taught by this teacher
        $stmt = $this->db->prepare("
            SELECT DISTINCT s.id AS student_id, u.fullname, u.student_reg_no, s.class_grade, s.gender, u.email
            FROM students s
            JOIN users u ON s.user_id = u.id
            JOIN lessons l ON s.class_grade = l.class_grade
            WHERE l.teacher_id = ?
            ORDER BY s.class_grade, u.fullname
        ");
        $stmt->execute([$teacherId]);
        $students = $stmt->fetchAll();
        
        // Append average performance
        foreach ($students as &$s) {
            $st = $this->db->prepare("
                SELECT ROUND(AVG(percentage),1) as avg_score 
                FROM student_assessments sa
                JOIN assessments a ON sa.assessment_id = a.id
                WHERE sa.student_id = ? AND sa.status IN ('submitted', 'graded') AND a.teacher_id = ?
            ");
            $st->execute([$s['student_id'], $teacherId]);
            $s['avg_score'] = $st->fetch()['avg_score'] ?? 0;
            
            // Mastery percentage roughly
            $st = $this->db->prepare("
                SELECT SUM(CASE WHEN sc.status='mastered' THEN 1 ELSE 0 END) as mastered, COUNT(c.id) as total
                FROM cbc_core_competences c
                LEFT JOIN student_core_competences sc ON c.id = sc.competence_id AND sc.student_id = ?
            ");
            $st->execute([$s['student_id']]);
            $comp = $st->fetch();
            $total = (int)$comp['total'];
            $s['mastery_pct'] = $total > 0 ? round(((int)$comp['mastered'] / $total) * 100) : 0;
        }
        return $students;
    }

    public function getStudentProgress(int $studentId, int $teacherId): array
    {
        // Fetch specific student data
        $stmt = $this->db->prepare("
            SELECT s.id as student_id, s.class_grade, u.fullname, u.student_reg_no 
            FROM students s JOIN users u ON s.user_id = u.id WHERE s.id = ?
        ");
        $stmt->execute([$studentId]);
        $student = $stmt->fetch();
        if (!$student) return [];

        // Fetch competencies
        $stmt = $this->db->prepare("
            SELECT c.id, c.name AS title, 'Core Competence' AS subject, 'CBE Core' AS description,
                   COALESCE(sc.status, 'not_started') AS status, sc.score
            FROM cbc_core_competences c
            LEFT JOIN student_core_competences sc ON c.id = sc.competence_id AND sc.student_id = ?
            ORDER BY c.id
        ");
        $stmt->execute([$studentId]);
        $student['competencies'] = $stmt->fetchAll();

        // Fetch assessments graded/submitted for this teacher
        $stmt = $this->db->prepare("
            SELECT a.title, a.subject, sa.percentage, sa.status, sa.submitted_at
            FROM student_assessments sa
            JOIN assessments a ON sa.assessment_id = a.id
            WHERE sa.student_id = ? AND a.teacher_id = ?
            ORDER BY sa.submitted_at DESC
        ");
        $stmt->execute([$studentId, $teacherId]);
        $student['assessments'] = $stmt->fetchAll();

        return $student;
    }

    public function updateStudentCompetency(int $studentId, int $competencyId, string $status, ?float $score): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO student_core_competences (student_id, competence_id, status, score, updated_at)
            VALUES (?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE status = VALUES(status), score = VALUES(score), updated_at = NOW()
        ");
        $stmt->execute([$studentId, $competencyId, $status, $score]);
    }

    public function addFeedback(int $studentId, int $teacherId, string $message): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO teacher_feedback (student_id, teacher_id, message)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$studentId, $teacherId, $message]);
    }

    /* ============================================================
       DISCUSSIONS (Threaded per lesson)
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
       REPORTING & ANALYTICS
    ============================================================*/
    public function getOverallClassProgress(int $teacherId): array
    {
        // Average score and mastery per grade for this teacher's classes
        $stmt = $this->db->prepare("
            SELECT l.class_grade, 
                   ROUND(AVG(sa.percentage), 1) as avg_score,
                   ROUND((SUM(CASE WHEN sc.status = 'mastered' THEN 1 ELSE 0 END) / NULLIF(COUNT(sc.id), 0)) * 100) as mastery_pct
            FROM lessons l
            LEFT JOIN students s ON l.class_grade = s.class_grade
            LEFT JOIN student_assessments sa ON s.id = sa.student_id
            LEFT JOIN assessments a ON sa.assessment_id = a.id AND a.teacher_id = l.teacher_id
            LEFT JOIN student_core_competences sc ON s.id = sc.student_id
            WHERE l.teacher_id = ?
            GROUP BY l.class_grade
        ");
        $stmt->execute([$teacherId]);
        return $stmt->fetchAll();
    }

    public function getRecentActivity(int $teacherId): array
    {
        // Combined list of lessons, submissions, and feedback
        $stmt = $this->db->prepare("
            (SELECT 'lesson' as type, strand as label, created_at, id FROM lessons WHERE teacher_id = ? )
            UNION ALL
            (SELECT 'submission' as type, u.fullname as label, sa.submitted_at as created_at, sa.assessment_id as id 
             FROM student_assessments sa 
             JOIN assessments a ON sa.assessment_id = a.id 
             JOIN students s ON sa.student_id = s.id
             JOIN users u ON s.user_id = u.id
             WHERE a.teacher_id = ? AND sa.status = 'submitted')
            UNION ALL
            (SELECT 'feedback' as type, u.fullname as label, tf.created_at, tf.id 
             FROM teacher_feedback tf 
             JOIN students s ON tf.student_id = s.id
             JOIN users u ON s.user_id = u.id
             WHERE tf.teacher_id = ?)
            ORDER BY created_at DESC LIMIT 10
        ");
        $stmt->execute([$teacherId, $teacherId, $teacherId]);
        return $stmt->fetchAll();
    }

    public function getFilteredStudents(int $teacherId, string $filter): array
    {
        $students = $this->getStudents($teacherId);
        if ($filter === 'at_risk') {
            return array_filter($students, fn($s) => $s['avg_score'] < 50 || $s['mastery_pct'] < 30);
        }
        if ($filter === 'top') {
            return array_filter($students, fn($s) => $s['avg_score'] > 80 && $s['mastery_pct'] > 70);
        }
        return $students;
    }

    /* ============================================================
       ASSESSMENTS & GRADING
    ============================================================*/
    public function getAssessments(int $teacherId): array
    {
        $stmt = $this->db->prepare("
            SELECT a.*, l.strand as lesson_strand 
            FROM assessments a 
            LEFT JOIN lessons l ON a.lesson_id = l.id
            WHERE a.teacher_id = ? 
            ORDER BY a.created_at DESC
        ");
        $stmt->execute([$teacherId]);
        return $stmt->fetchAll();
    }
    
    public function getAssessmentById(int $assessmentId, int $teacherId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM assessments WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$assessmentId, $teacherId]);
        $assessment = $stmt->fetch() ?: null;
        if ($assessment) {
            $stmtC = $this->db->prepare("SELECT competence_id FROM assessment_core_competences WHERE assessment_id = ?");
            $stmtC->execute([$assessmentId]);
            $assessment['core_competences'] = $stmtC->fetchAll(PDO::FETCH_COLUMN);
        }
        return $assessment;
    }

    public function updateAssessmentInfo(int $assessmentId, int $teacherId, array $data): void
    {
        $stmt = $this->db->prepare("
            UPDATE assessments
            SET title = ?, instructions = ?, due_date = ?, lesson_id = ?, class_grade = ?, subject = ?,
                available_from = ?, available_until = ?, duration_minutes = ?, max_attempts = ?
            WHERE id = ? AND teacher_id = ?
        ");
        $stmt->execute([
            $data['title'],
            $data['instructions'] ?? '',
            $data['due_date'] ?? null,
            $data['lesson_id'] ?: null,
            $data['class_grade'],
            $data['subject'],
            $data['available_from'],
            $data['available_until'],
            (int)($data['duration_minutes'] ?? 60),
            (int)($data['max_attempts'] ?? 1),
            $assessmentId,
            $teacherId
        ]);
        $this->db->prepare("DELETE FROM assessment_core_competences WHERE assessment_id = ?")->execute([$assessmentId]);
        if (!empty($data['core_competences']) && is_array($data['core_competences'])) {
            $ins = $this->db->prepare("INSERT INTO assessment_core_competences (assessment_id, competence_id) VALUES (?, ?)");
            foreach ($data['core_competences'] as $cId) { $ins->execute([$assessmentId, (int)$cId]); }
        }
    }

    public function getAssessmentInteractions(int $assessmentId): array
    {
        $stmt = $this->db->prepare("
            SELECT ai.*
            FROM assessment_interactions ai
            WHERE ai.assessment_id = ? ORDER BY ai.sort_order ASC
        ");
        $stmt->execute([$assessmentId]);
        return $stmt->fetchAll();
    }

    public function saveAssessmentInteractions(int $assessmentId, array $interactions): void
    {
        $this->db->prepare("DELETE FROM assessment_interactions WHERE assessment_id = ?")->execute([$assessmentId]);

        $stmt = $this->db->prepare("
            INSERT INTO assessment_interactions
                (assessment_id, sort_order, interaction_type, question, options, correct_answer, marks, hint)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        foreach ($interactions as $i => $item) {
            if (empty($item['question'])) continue;
            $options = !empty($item['options']) ? json_encode($item['options']) : null;
            $stmt->execute([
                $assessmentId,
                $i,
                $item['interaction_type'] ?? 'mcq',
                $item['question'],
                $options,
                $item['correct_answer'] ?? '',
                $item['marks'] ?? 1,
                $item['hint'] ?? null
            ]);
        }
    }

    
    public function createAssessment(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO assessments (lesson_id, teacher_id, title, instructions, class_grade, subject, duration_minutes, max_attempts, due_date, available_from, available_until)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['lesson_id'] ?: null,
            $data['teacher_id'],
            $data['title'],
            $data['instructions'] ?? '',
            $data['class_grade'],
            $data['subject'],
            (int)($data['duration_minutes'] ?? 60),
            (int)($data['max_attempts'] ?? 1),
            $data['due_date'] ?? null,
            $data['available_from'],
            $data['available_until']
        ]);
        $assessmentId = (int)$this->db->lastInsertId();

        // Map core competencies
        if (!empty($data['lesson_id'])) {
            $stmtL = $this->db->prepare("SELECT competence_id FROM lesson_core_competences WHERE lesson_id = ?");
            $stmtL->execute([$data['lesson_id']]);
            $comps = $stmtL->fetchAll(PDO::FETCH_COLUMN);
            if ($comps) {
                $ins = $this->db->prepare("INSERT INTO assessment_core_competences (assessment_id, competence_id) VALUES (?, ?)");
                foreach ($comps as $cId) { $ins->execute([$assessmentId, $cId]); }
            }
        } elseif (!empty($data['core_competences']) && is_array($data['core_competences'])) {
            $ins = $this->db->prepare("INSERT INTO assessment_core_competences (assessment_id, competence_id) VALUES (?, ?)");
            foreach ($data['core_competences'] as $cId) { $ins->execute([$assessmentId, (int)$cId]); }
        }

        return $assessmentId;
    }

    public function deleteAssessment(int $assessmentId, int $teacherId): void
    {
        $stmt = $this->db->prepare("DELETE FROM assessments WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$assessmentId, $teacherId]);
    }

    public function getPendingSubmissions(int $teacherId): array
    {
        // Students who submitted an assessment created by this teacher, awaiting manual review
        $stmt = $this->db->prepare("
            SELECT sa.student_id, sa.assessment_id, sa.score, sa.percentage, sa.submitted_at, 
                   u.fullname AS student_name, a.title AS assessment_title, 100 as max_score
            FROM student_assessments sa
            JOIN assessments a ON sa.assessment_id = a.id
            JOIN students s ON sa.student_id = s.id
            JOIN users u ON s.user_id = u.id
            WHERE a.teacher_id = ? AND sa.status = 'submitted'
            ORDER BY sa.submitted_at ASC
        ");
        $stmt->execute([$teacherId]);
        return $stmt->fetchAll();
    }
    
    public function gradeSubmission(int $studentId, int $assessmentId, int $score, int $percentage): void
    {
        $stmt = $this->db->prepare("
            UPDATE student_assessments 
            SET score = ?, percentage = ?, status = 'graded'
            WHERE student_id = ? AND assessment_id = ?
        ");
        $stmt->execute([$score, $percentage, $studentId, $assessmentId]);
        
        // Formative Map to CBE levels
        $cbeStatus = 'not_started';
        if ($percentage > 75) $cbeStatus = 'fully_mastered';
        elseif ($percentage > 50) $cbeStatus = 'mastered';
        elseif ($percentage > 25) $cbeStatus = 'in_progress';

        $stmtComp = $this->db->prepare("SELECT competence_id FROM assessment_core_competences WHERE assessment_id = ?");
        $stmtComp->execute([$assessmentId]);
        $competences = $stmtComp->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($competences)) {
            $stmtUpd = $this->db->prepare("
                INSERT INTO student_core_competences (student_id, competence_id, status, score, updated_at)
                VALUES (?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                    status = IF(VALUES(score) >= COALESCE(score, 0), VALUES(status), status), 
                    score = IF(VALUES(score) >= COALESCE(score, 0), VALUES(score), score),
                    updated_at = NOW()
            ");
            foreach ($competences as $cId) {
                $stmtUpd->execute([$studentId, $cId, $cbeStatus, $percentage]);
            }
        }
        
        // Provide simple system feedback notification
        $stmt = $this->db->prepare("SELECT user_id FROM students WHERE id = ?");
        $stmt->execute([$studentId]);
        $uId = $stmt->fetchColumn();
        if ($uId) {
            $msg = "Your assessment was graded. You scored " . $percentage . "%.";
            $this->db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")
                     ->execute([$uId, $msg]);
        }
    }
    public function getPendingSubmissionDetail(int $studentId, int $assessmentId): array
    {
        // Assessment info
        $stmt = $this->db->prepare("SELECT a.*, u.fullname AS teacher_name FROM assessments a JOIN users u ON a.teacher_id = u.id WHERE a.id = ?");
        $stmt->execute([$assessmentId]);
        $assessment = $stmt->fetch() ?: [];

        // Student info
        $stmt = $this->db->prepare("SELECT u.fullname, u.student_reg_no, s.class_grade FROM students s JOIN users u ON s.user_id = u.id WHERE s.id = ?");
        $stmt->execute([$studentId]);
        $student = $stmt->fetch() ?: [];

        // All interactions for this assessment
        $stmt = $this->db->prepare("
            SELECT ai.*,
                   saa.answer_given, saa.file_url, saa.score_achieved, saa.teacher_comment
            FROM assessment_interactions ai
            LEFT JOIN student_assessment_answers saa
                   ON saa.interaction_id = ai.id AND saa.student_id = ? AND saa.assessment_id = ?
            WHERE ai.assessment_id = ?
            ORDER BY ai.sort_order, ai.id
        ");
        $stmt->execute([$studentId, $assessmentId, $assessmentId]);
        $interactions = $stmt->fetchAll();

        return compact('assessment', 'student', 'interactions');
    }

    public function saveInteractionGrade(int $studentId, int $assessmentId, int $interactionId, float $score, string $comment = ''): void
    {
        $this->db->prepare("
            UPDATE student_assessment_answers
            SET score_achieved = ?, teacher_comment = ?
            WHERE student_id = ? AND assessment_id = ? AND interaction_id = ?
        ")->execute([$score, $comment, $studentId, $assessmentId, $interactionId]);
    }

    public function finaliseManualGrade(int $studentId, int $assessmentId): void
    {
        // Recalculate total from per-answer scores
        $stmt = $this->db->prepare("
            SELECT SUM(saa.score_achieved) AS earned,
                   SUM(ai.marks)           AS max_marks
            FROM student_assessment_answers saa
            JOIN assessment_interactions ai ON ai.id = saa.interaction_id
            WHERE saa.student_id = ? AND saa.assessment_id = ?
        ");
        $stmt->execute([$studentId, $assessmentId]);
        $row = $stmt->fetch();
        $earned = (float)($row['earned'] ?? 0);
        $max    = (int)($row['max_marks'] ?? 1);
        $pct    = $max > 0 ? round($earned / $max * 100) : 0;

        $this->db->prepare("
            UPDATE student_assessments SET score = ?, percentage = ?, status = 'graded'
            WHERE student_id = ? AND assessment_id = ?
        ")->execute([$earned, $pct, $studentId, $assessmentId]);

        // Run CBE mapping
        $cbeStatus = 'not_started';
        if ($pct > 75) $cbeStatus = 'fully_mastered';
        elseif ($pct > 50) $cbeStatus = 'mastered';
        elseif ($pct > 25) $cbeStatus = 'in_progress';

        $stmtC = $this->db->prepare("SELECT competence_id FROM assessment_core_competences WHERE assessment_id = ?");
        $stmtC->execute([$assessmentId]);
        $competences = $stmtC->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($competences)) {
            $upd = $this->db->prepare("
                INSERT INTO student_core_competences (student_id, competence_id, status, score, updated_at)
                VALUES (?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    status = IF(VALUES(score) >= COALESCE(score,0), VALUES(status), status),
                    score  = IF(VALUES(score) >= COALESCE(score,0), VALUES(score), score),
                    updated_at = NOW()
            ");
            foreach ($competences as $cId) { $upd->execute([$studentId, $cId, $cbeStatus, $pct]); }
        }

        // Notify student
        $uid = $this->db->prepare("SELECT user_id FROM students WHERE id = ?")->execute([$studentId]);
        $stmt = $this->db->prepare("SELECT user_id FROM students WHERE id = ?");
        $stmt->execute([$studentId]);
        $uId = $stmt->fetchColumn();
        if ($uId) {
            $this->db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")
                     ->execute([$uId, "Your assessment has been reviewed and graded. You scored {$pct}%." ]);
        }
    }

    /* ============================================================
       MESSAGES (Teacher <-> Parent)
    ============================================================*/
    public function getMessages(int $teacherId): array
    {
        // 1. Get all parents whose children are in this teacher's classes
        // Union with parents who have already messaged the teacher
        $stmt = $this->db->prepare("
            SELECT DISTINCT u.id AS parent_userId, u.fullname AS parent_name, p.id AS parent_id
            FROM users u
            JOIN parents p ON u.id = p.user_id
            JOIN parent_student ps ON u.id = ps.parent_id
            JOIN students s ON ps.student_id = s.id
            JOIN lessons l ON s.class_grade = l.class_grade
            WHERE l.teacher_id = ?
            
            UNION
            
            SELECT DISTINCT u.id AS parent_userId, u.fullname AS parent_name, p.id AS parent_id
            FROM users u
            JOIN parents p ON u.id = p.user_id
            JOIN parent_teacher_messages ptm ON p.id = ptm.parent_id
            WHERE ptm.teacher_id = ?
        ");
        $stmt->execute([$teacherId, $teacherId]);
        $parents = $stmt->fetchAll();

        // Fetch threads for each parent
        foreach ($parents as &$p) {
            $stmt = $this->db->prepare("
                SELECT ptm.*, stu.fullname AS student_name
                FROM parent_teacher_messages ptm
                LEFT JOIN students s ON ptm.student_id = s.id
                LEFT JOIN users stu ON s.user_id = stu.id
                WHERE ptm.parent_id = ? AND ptm.teacher_id = ?
                ORDER BY ptm.created_at ASC
            ");
            $stmt->execute([$p['parent_id'], $teacherId]);
            $p['messages'] = $stmt->fetchAll();
            
            // Unread count
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as c FROM parent_teacher_messages
                WHERE parent_id = ? AND teacher_id = ? AND sender_type = 'parent' AND is_read = 0
            ");
            $stmt->execute([$p['parent_id'], $teacherId]);
            $p['unread'] = (int)$stmt->fetch()['c'];

            // Also get the list of students this parent has that are taught by this teacher
            $stmt = $this->db->prepare("
                SELECT s.id AS student_id, u.fullname AS student_name
                FROM students s
                JOIN users u ON s.user_id = u.id
                JOIN parent_student ps ON s.id = ps.student_id
                JOIN lessons l ON s.class_grade = l.class_grade
                WHERE ps.parent_id = ? AND l.teacher_id = ?
            ");
            $stmt->execute([$p['parent_userId'], $teacherId]);
            $p['linked_students'] = $stmt->fetchAll();
        }

        return $parents;
    }

    public function markMessagesRead(int $teacherId, int $parentUserId): void
    {
        $stmt = $this->db->prepare("
            UPDATE parent_teacher_messages ptm
            JOIN parents p ON ptm.parent_id = p.id
            SET ptm.is_read = 1
            WHERE p.user_id = ? AND ptm.teacher_id = ? AND ptm.sender_type = 'parent'
        ");
        $stmt->execute([$parentUserId, $teacherId]);
    }

    public function sendMessage(int $teacherId, int $parentUserId, int $studentId, string $message): void
    {
        // Resolve parent.user_id to parents.id
        $stmt = $this->db->prepare("SELECT id FROM parents WHERE user_id = ?");
        $stmt->execute([$parentUserId]);
        $parentId = $stmt->fetchColumn();
        if (!$parentId) return;

        $stmt = $this->db->prepare("
            INSERT INTO parent_teacher_messages (parent_id, teacher_id, student_id, sender_type, message)
            VALUES (?, ?, ?, 'teacher', ?)
        ");
        $stmt->execute([$parentId, $teacherId, $studentId ?: null, $message]);
    }

    /* ============================================================
       ANNOUNCEMENTS (Teacher to Students)
    ============================================================*/
    public function getAnnouncements(int $teacherId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM teacher_announcements WHERE teacher_id = ? ORDER BY created_at DESC");
        $stmt->execute([$teacherId]);
        return $stmt->fetchAll();
    }

    public function broadcastAnnouncement(int $teacherId, string $grade, string $subject, string $title, string $message): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO teacher_announcements (teacher_id, class_grade, subject, title, message)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$teacherId, $grade, $subject, $title, $message]);
    }

    public function deleteAnnouncement(int $id, int $teacherId): void
    {
        $stmt = $this->db->prepare("DELETE FROM teacher_announcements WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$id, $teacherId]);
    }

    /* ============================================================
       LESSON ACTIVITY GRADING
    ============================================================*/

    public function getPendingLessonActivities(int $teacherId): array
    {
        $stmt = $this->db->prepare("
            SELECT las.*, u.fullname AS student_name, l.strand AS lesson_strand, la.title AS activity_title, la.max_marks
            FROM lesson_activity_submissions las
            JOIN lessons l ON las.lesson_id = l.id
            JOIN students s ON las.student_id = s.id
            JOIN users u ON s.user_id = u.id
            JOIN lesson_activities la ON las.activity_id = la.id
            WHERE l.teacher_id = ? AND (las.score IS NULL)
            ORDER BY las.submitted_at ASC
        ");
        $stmt->execute([$teacherId]);
        return $stmt->fetchAll();
    }

    public function getLessonSubmissionDetail(int $studentId, int $lessonId): array
    {
        // 1. Lesson basic info
        $stmt = $this->db->prepare("SELECT strand, subject, class_grade FROM lessons WHERE id = ?");
        $stmt->execute([$lessonId]);
        $lesson = $stmt->fetch();

        // 2. Student info
        $stmt = $this->db->prepare("SELECT u.fullname FROM users u JOIN students s ON s.user_id = u.id WHERE s.id = ?");
        $stmt->execute([$studentId]);
        $studentName = $stmt->fetchColumn();

        // 3. Interactions (Step 2)
        $stmt = $this->db->prepare("
            SELECT li.question, li.interaction_type, li.correct_answer, li.marks,
                   si.answer_given, si.is_correct
            FROM lesson_interactions li
            LEFT JOIN student_interactions si ON si.interaction_id = li.id AND si.student_id = ?
            WHERE li.lesson_id = ?
            ORDER BY li.sort_order ASC
        ");
        $stmt->execute([$studentId, $lessonId]);
        $interactions = $stmt->fetchAll();

        // 4. Activity (Step 3)
        $stmt = $this->db->prepare("
            SELECT la.title, la.description, la.max_marks, la.submission_type,
                   las.response_text, las.file_url, las.score, las.teacher_feedback, las.submitted_at
            FROM lesson_activities la
            LEFT JOIN lesson_activity_submissions las ON las.activity_id = la.id AND las.student_id = ?
            WHERE la.lesson_id = ?
            LIMIT 1
        ");
        $stmt->execute([$studentId, $lessonId]);
        $activity = $stmt->fetch();

        // 5. Overall Progress (Step 4 score)
        $stmt = $this->db->prepare("SELECT * FROM lesson_progress WHERE lesson_id = ? AND student_id = ?");
        $stmt->execute([$lessonId, $studentId]);
        $progress = $stmt->fetch();

        return [
            'lesson'       => $lesson,
            'student_name' => $studentName,
            'interactions' => $interactions,
            'activity'     => $activity,
            'progress'     => $progress
        ];
    }

    public function gradeLessonActivity(int $studentId, int $lessonId, float $score, string $comment): void
    {
        $stmt = $this->db->prepare("
            UPDATE lesson_activity_submissions
            SET score = ?, teacher_feedback = ?
            WHERE lesson_id = ? AND student_id = ?
        ");
        $stmt->execute([$score, $comment, $lessonId, $studentId]);
    }

    public function updateLessonInteractionScore(int $studentId, int $lessonId, int $newScore): void
    {
        $stmt = $this->db->prepare("
            UPDATE lesson_progress
            SET interaction_score = ?
            WHERE lesson_id = ? AND student_id = ?
        ");
        $stmt->execute([$newScore, $lessonId, $studentId]);
    }
}
