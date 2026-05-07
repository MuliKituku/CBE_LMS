<?php
/* ── models/AdminModel.php ──────────────────────────────
   Handles administrative management operations
   ─────────────────────────────────────────────────────────── */

class AdminModel extends Model {

    /* ============================================================
       CLASS MANAGEMENT
    ============================================================*/
    public function getAllClasses(): array
    {
        $stmt = $this->db->query("
            SELECT c.*, 
                   (SELECT COUNT(*) FROM students s WHERE s.class_grade = c.name) as student_count,
                   (SELECT GROUP_CONCAT(u.fullname) 
                    FROM class_teacher ct 
                    JOIN users u ON ct.teacher_id = u.id 
                    WHERE ct.class_id = c.id) as teachers
            FROM classes c 
            ORDER BY c.name ASC
        ");
        return $stmt->fetchAll();
    }

    public function createClass(string $name): void
    {
        $stmt = $this->db->prepare("INSERT INTO classes (name) VALUES (?)");
        $stmt->execute([$name]);
    }

    public function assignTeacherToClass(int $teacherId, int $classId): void
    {
        $stmt = $this->db->prepare("INSERT IGNORE INTO class_teacher (class_id, teacher_id) VALUES (?, ?)");
        $stmt->execute([$classId, $teacherId]);
    }

    public function removeTeacherFromClass(int $teacherId, int $classId): void
    {
        $stmt = $this->db->prepare("DELETE FROM class_teacher WHERE class_id = ? AND teacher_id = ?");
        $stmt->execute([$classId, $teacherId]);
    }

    /* ============================================================
       CONTENT MODERATION (LESSONS)
    ============================================================*/
    public function getAllLessons(): array
    {
        $stmt = $this->db->query("
            SELECT l.*, u.fullname as teacher_name 
            FROM lessons l 
            JOIN users u ON l.teacher_id = u.id 
            ORDER BY l.created_at DESC
        ");
        return $stmt->fetchAll();
    }

    public function toggleLessonVisibility(int $lessonId, int $isPublished): void
    {
        $stmt = $this->db->prepare("UPDATE lessons SET is_published = ? WHERE id = ?");
        $stmt->execute([$isPublished, $lessonId]);
        
        $this->logAction('TOGGLE_LESSON_VISIBILITY', 'lessons', $lessonId, "Status set to: " . ($isPublished ? 'Published' : 'Hidden'));
    }

    public function deleteLesson(int $lessonId): void
    {
        $stmt = $this->db->prepare("DELETE FROM lessons WHERE id = ?");
        $stmt->execute([$lessonId]);
        
        $this->logAction('DELETE_LESSON', 'lessons', $lessonId, "Lesson deleted by admin");
    }

    public function getLessonById(int $lessonId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT l.*, u.fullname as teacher_name 
            FROM lessons l 
            JOIN users u ON l.teacher_id = u.id 
            WHERE l.id = ?
        ");
        $stmt->execute([$lessonId]);
        $res = $stmt->fetch();
        if (!$res) return null;
        
        // Interactions
        $st = $this->db->prepare("SELECT * FROM lesson_interactions WHERE lesson_id = ? ORDER BY sort_order ASC");
        $st->execute([$lessonId]);
        $res['interactions'] = $st->fetchAll();
        foreach ($res['interactions'] as &$inter) {
            $inter['options_arr'] = $inter['options'] ? json_decode($inter['options'], true) : [];
        }
        
        // Activity
        $st = $this->db->prepare("SELECT * FROM lesson_activities WHERE lesson_id = ? LIMIT 1");
        $st->execute([$lessonId]);
        $res['activity'] = $st->fetch() ?: null;
        
        // Discussions 
        $st = $this->db->prepare("
            SELECT d.*, u.fullname, u.role
            FROM discussions d
            JOIN users u ON d.user_id = u.id
            WHERE d.lesson_id = ? AND d.parent_id IS NULL
            ORDER BY d.created_at DESC
        ");
        $st->execute([$lessonId]);
        $discussions = $st->fetchAll();
        
        foreach ($discussions as &$d) {
            $stR = $this->db->prepare("
                SELECT d.*, u.fullname, u.role
                FROM discussions d
                JOIN users u ON d.user_id = u.id
                WHERE d.parent_id = ?
                ORDER BY d.created_at ASC
            ");
            $stR->execute([$d['id']]);
            $d['replies'] = $stR->fetchAll();
        }
        $res['discussions'] = $discussions;
        
        return $res;
    }

    public function addLessonFeedback(int $lessonId, int $userId, string $message): void
    {
        $stmt = $this->db->prepare("INSERT INTO lesson_feedback (lesson_id, user_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$lessonId, $userId, $message]);
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

    public function clearLessonMedia(int $lessonId): void
    {
        $stmt = $this->db->prepare("UPDATE lessons SET content_url = NULL WHERE id = ?");
        $stmt->execute([$lessonId]);
        $this->logAction('CLEAR_LESSON_MEDIA', 'lessons', $lessonId, "Inappropriate media removed by admin");
    }

    public function getStudentUserIdsByGrade(string $grade): array
    {
        $stmt = $this->db->prepare("SELECT user_id FROM students WHERE class_grade = ?");
        $stmt->execute([$grade]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /* ============================================================
       GRADE AUDITING & OVERRIDING
    ============================================================*/
    public function getAllAssessmentsWithGrades(): array
    {
        $stmt = $this->db->query("
            SELECT sa.*, 
                   u_stu.fullname as student_name, 
                   u_stu.student_reg_no,
                   a.title as assessment_title,
                   a.subject,
                   u_teach.fullname as teacher_name,
                   u_admin.fullname as graded_by_name
            FROM student_assessments sa
            JOIN students s ON sa.student_id = s.id
            JOIN users u_stu ON s.user_id = u_stu.id
            JOIN assessments a ON sa.assessment_id = a.id
            JOIN users u_teach ON a.teacher_id = u_teach.id
            LEFT JOIN users u_admin ON sa.graded_by = u_admin.id
            ORDER BY sa.created_at DESC
        ");
        return $stmt->fetchAll();
    }

    public function overrideGrade(int $studentId, int $assessmentId, int $newScore, int $adminUserId): void
    {
        // Get old grade for logging
        $stmt = $this->db->prepare("SELECT score, percentage FROM student_assessments WHERE student_id = ? AND assessment_id = ?");
        $stmt->execute([$studentId, $assessmentId]);
        $old = $stmt->fetch();

        $percentage = $newScore; // Simplified: Assuming max_score is 100 for overrides, or we can fetch max_score

        $stmt = $this->db->prepare("
            UPDATE student_assessments 
            SET score = ?, percentage = ?, graded_by = ?, status = 'graded'
            WHERE student_id = ? AND assessment_id = ?
        ");
        $stmt->execute([$newScore, $percentage, $adminUserId, $studentId, $assessmentId]);

        $details = "Old: {$old['score']} ({$old['percentage']}%). New: $newScore ($percentage%)";
        $this->logAction('GRADE_OVERRIDE', 'student_assessments', $assessmentId, $details);
    }

    /* ============================================================
       AUDIT LOGS
    ============================================================*/
    public function getAuditLogs(): array
    {
        $stmt = $this->db->query("
            SELECT al.*, u.fullname as admin_name 
            FROM audit_logs al 
            JOIN users u ON al.user_id = u.id 
            ORDER BY al.created_at DESC 
            LIMIT 500
        ");
        return $stmt->fetchAll();
    }

    /* ============================================================
       EXTENDED DASHBOARD STATS
    ============================================================*/
    public function getExtendedStats(): array
    {
        $q = fn($sql) => (int) $this->db->query($sql)->fetch()['c'];
        
        return [
            'total_lessons'     => $q("SELECT COUNT(*) AS c FROM lessons"),
            'total_assessments' => $q("SELECT COUNT(*) AS c FROM assessments"),
            'active_classes'    => $q("SELECT COUNT(*) AS c FROM classes"),
            'pending_requests'  => $q("SELECT COUNT(*) AS c FROM teacher_requests WHERE status = 'pending'"),
        ];
    }

    public function getStudentsPerGrade(): array
    {
        $stmt = $this->db->query("
            SELECT class_grade as label, COUNT(*) as value 
            FROM students 
            GROUP BY class_grade 
            ORDER BY class_grade
        ");
        return $stmt->fetchAll();
    }

    /* ============================================================
       REPORTING & ANALYTICS (Extended)
    ============================================================*/
    public function getCompetencyStats(): array
    {
        return $this->db->query("
            SELECT status, COUNT(*) as count 
            FROM student_core_competences 
            GROUP BY status
        ")->fetchAll();
    }

    public function getAtRiskStudents(): array
    {
        // Students with BE (Below Expectation) status in any core competency
        return $this->db->query("
            SELECT u.fullname, u.student_reg_no, s.class_grade, c.name as competency, sc.score
            FROM student_core_competences sc
            JOIN students s ON sc.student_id = s.id
            JOIN users u ON s.user_id = u.id
            JOIN cbc_core_competences c ON sc.competence_id = c.id
            WHERE sc.score < 40 OR sc.status = 'not_started'
            LIMIT 100
        ")->fetchAll();
    }

    public function getSystemUsageStats(): array
    {
        $q = fn($sql) => (int) $this->db->query($sql)->fetch()['c'];
        return [
            'lesson_completions' => $q("SELECT COUNT(*) AS c FROM discussions"), // Placeholder for engagement
            'quiz_attempts'      => $q("SELECT COUNT(*) AS c FROM student_assessments WHERE status = 'submitted'"),
            'graded_quizzes'     => $q("SELECT COUNT(*) AS c FROM student_assessments WHERE status = 'graded'"),
        ];
    }

    /* ============================================================
       SYSTEM SETTINGS (Grading Scales)
    ============================================================*/
    public function getSystemSettings(): array
    {
        $stmt = $this->db->query("SELECT setting_key, setting_value FROM system_settings");
        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        foreach ($rows as $key => $val) {
            // Attempt to decode JSON if it looks like JSON
            if (in_array($key, ['grading_scales'])) {
                $rows[$key] = json_decode($val, true);
            }
        }
        return $rows;
    }

    public function updateSystemSetting(string $key, $value): void
    {
        if (is_array($value)) $value = json_encode($value);
        
        $stmt = $this->db->prepare("
            INSERT INTO system_settings (setting_key, setting_value) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");
        $stmt->execute([$key, $value]);
        $this->logAction('UPDATE_SETTINGS', 'system_settings', 0, "Updated setting: $key");
    }

    public function getGradingScales(): array
    {
        $settings = $this->getSystemSettings();
        return $settings['grading_scales'] ?? [];
    }

    public function updateGradingScales(array $scales): void
    {
        $this->updateSystemSetting('grading_scales', $scales);
    }

    /* ============================================================
       ANNOUNCEMENTS
    ============================================================*/
    public function getAnnouncements(): array
    {
        $stmt = $this->db->query("
            SELECT a.*, u.fullname as author 
            FROM announcements a 
            JOIN users u ON a.user_id = u.id 
            ORDER BY a.created_at DESC
        ");
        $announcements = $stmt->fetchAll();

        foreach ($announcements as &$a) {
            if ($a['target_role'] === 'targeted') {
                $st = $this->db->prepare("
                    SELECT u.fullname 
                    FROM announcement_recipients ar
                    JOIN users u ON ar.user_id = u.id
                    WHERE ar.announcement_id = ?
                ");
                $st->execute([$a['id']]);
                $a['recipients'] = array_column($st->fetchAll(), 'fullname');
            } else {
                $a['recipients'] = [];
            }
        }
        return $announcements;
    }

    public function createAnnouncement(int $adminId, string $title, string $message, string $target = 'all', array $targetUserIds = []): void
    {
        if ($target === 'targeted' && !empty($targetUserIds)) {
            // Already set to targeted or force set if user ids present
        } elseif (!empty($targetUserIds)) {
            $target = 'targeted';
        }

        $stmt = $this->db->prepare("INSERT INTO announcements (user_id, title, message, target_role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$adminId, $title, $message, $target]);
        $announcementId = (int)$this->db->lastInsertId();

        if ($target === 'targeted' && !empty($targetUserIds)) {
            $stmt = $this->db->prepare("INSERT INTO announcement_recipients (announcement_id, user_id) VALUES (?, ?)");
            foreach ($targetUserIds as $uId) {
                $stmt->execute([$announcementId, (int)$uId]);
            }
        }

        $this->logAction('CREATE_ANNOUNCEMENT', 'announcements', $announcementId, "Title: $title");
    }

    /* ============================================================
       TEACHER REQUESTS
    ============================================================*/
    public function getTeacherRequests(string $status = 'pending'): array
    {
        $stmt = $this->db->prepare("
            SELECT tr.*, u.fullname as teacher_name 
            FROM teacher_requests tr 
            JOIN users u ON tr.teacher_id = u.id 
            WHERE tr.status = ? 
            ORDER BY tr.created_at DESC
        ");
        $stmt->execute([$status]);
        return $stmt->fetchAll();
    }

    public function updateRequestStatus(int $requestId, string $status, string $notes = ''): void
    {
        $stmt = $this->db->prepare("UPDATE teacher_requests SET status = ?, admin_notes = ? WHERE id = ?");
        $stmt->execute([$status, $notes, $requestId]);
        $this->logAction('TEACHER_REQUEST_UPDATE', 'teacher_requests', $requestId, "Status set to: $status");
    }

    private function logAction(string $action, string $targetType, int $targetId, string $details): void
    {
        $adminId = $_SESSION['user']['id'] ?? 0;
        if (!$adminId) return;

        $stmt = $this->db->prepare("
            INSERT INTO audit_logs (user_id, action, target_type, target_id, details)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$adminId, $action, $targetType, $targetId, $details]);
    }
}
