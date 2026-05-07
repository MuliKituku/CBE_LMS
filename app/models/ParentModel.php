<?php

class ParentModel extends Model {

    /* ============================================================
       GET FULL PARENT PROFILE + LINKED CHILDREN
    ============================================================*/
    public function getProfile(int $userId)
    {
        $stmt = $this->db->prepare("
            SELECT u.id AS user_id, u.fullname, u.email, u.status, u.created_at,
                   p.id AS parent_id, p.phone
            FROM users u
            JOIN parents p ON u.id = p.user_id
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        $profile = $stmt->fetch();

        if ($profile) {
            $stmt = $this->db->prepare("
                SELECT s.id AS student_id, u.fullname AS student_name,
                       u.student_reg_no, s.class_grade, s.gender, s.date_of_birth,
                       u.status AS enrollment_status
                FROM parent_student ps
                JOIN students s ON ps.student_id = s.id
                JOIN users u ON s.user_id = u.id
                WHERE ps.parent_id = ?
            ");
            $stmt->execute([$profile['user_id']]);
            $profile['children'] = $stmt->fetchAll();
        }

        return $profile;
    }

    /* ============================================================
       GET HIGH-LEVEL OVERVIEW OF A CHILD (Dashboard Stats)
    ============================================================*/
    public function getChildOverview(int $studentId): array
    {
        // Total mastered competencies
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS mastered
            FROM student_core_competences
            WHERE student_id = ? AND status = 'mastered'
        ");
        $stmt->execute([$studentId]);
        $mastered = (int)$stmt->fetch()['mastered'];

        // Average assessment score
        $stmt = $this->db->prepare("
            SELECT ROUND(AVG(percentage), 1) as avg_score
            FROM student_assessments
            WHERE student_id = ? AND status IN ('submitted', 'graded')
        ");
        $stmt->execute([$studentId]);
        $avgScore = (float)$stmt->fetch()['avg_score'];

        return [
            'mastered_competencies' => $mastered,
            'average_score'         => $avgScore
        ];
    }

    /* ============================================================
       GET COMPETENCY DATA FOR A CHILD (Progress View)
    ============================================================*/
    public function getChildProgress(int $studentId): array
    {
        // 1. Get the child's grade
        $stmt = $this->db->prepare("SELECT class_grade FROM students WHERE id = ?");
        $stmt->execute([$studentId]);
        $grade = $stmt->fetch()['class_grade'] ?? '';

        if (!$grade) {
            return ['mastery' => [], 'competencies' => []];
        }

        // 2. Mastery sums
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

        // 3. Detailed list
        $stmt = $this->db->prepare("
            SELECT c.id, c.name AS title, 'Core Competence' AS subject, 'CBE Core' AS description,
                   COALESCE(sc.status, 'not_started') AS mastery_status,
                   sc.score
            FROM cbc_core_competences c
            LEFT JOIN student_core_competences sc ON sc.competence_id = c.id AND sc.student_id = ?
            ORDER BY c.id
        ");
        $stmt->execute([$studentId]);
        $competencies = $stmt->fetchAll();

        // 4. Bar chart data (scores per subject)
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
            'mastery'      => $mastery,
            'competencies' => $competencies,
            'avg_scores'   => $avgScores
        ];
    }

    /* ============================================================
       GET CHILD QUIZ RESULTS & REMARKS (Assessments View)
    ============================================================*/
    public function getChildAssessments(int $studentId): array
    {
        $stmt = $this->db->prepare("
            SELECT sa.*, a.title, a.subject, a.due_date, u.fullname AS teacher_name
            FROM student_assessments sa
            JOIN assessments a ON sa.assessment_id = a.id
            JOIN users u ON a.teacher_id = u.id
            WHERE sa.student_id = ?
            ORDER BY sa.submitted_at DESC, a.due_date ASC
        ");
        $stmt->execute([$studentId]);
        return $stmt->fetchAll();
    }

    /* ============================================================
       GET RECENT ACTIVITIES OF ALL CHILDREN (Dashboard Feed)
    ============================================================*/
    public function getRecentActivities(array $studentIds, int $limit = 5): array
    {
        if (empty($studentIds)) return [];

        $placeholders = str_repeat('?,', count($studentIds) - 1) . '?';
        
        // Let's get recently submitted assessments as "activity" for now
        $stmt = $this->db->prepare("
            SELECT sa.submitted_at AS activity_date,
                   CONCAT('Submitted assessment: ', a.title) AS description,
                   u.fullname AS student_name,
                   a.subject,
                   sa.percentage AS score
            FROM student_assessments sa
            JOIN assessments a ON sa.assessment_id = a.id
            JOIN students s ON sa.student_id = s.id
            JOIN users u ON s.user_id = u.id
            WHERE sa.student_id IN ($placeholders) AND sa.status IN ('submitted', 'graded')
            ORDER BY sa.submitted_at DESC
            LIMIT $limit
        ");
        $stmt->execute($studentIds);
        return $stmt->fetchAll();
    }

    /* ============================================================
       GET UPCOMING ASSESSMENTS FOR ALL CHILDREN (Dashboard Alert)
    ============================================================*/
    public function getUpcomingAssessments(array $studentIds, int $limit = 5): array
    {
        if (empty($studentIds)) return [];

        $placeholders = str_repeat('?,', count($studentIds) - 1) . '?';
        
        $stmt = $this->db->prepare("
            SELECT a.*, u.fullname AS student_name,
                   (SELECT u2.fullname FROM users u2 WHERE u2.id = a.teacher_id) AS teacher_name
            FROM assessments a
            JOIN students s ON a.class_grade = s.class_grade
            JOIN users u ON s.user_id = u.id
            LEFT JOIN student_assessments sa ON a.id = sa.assessment_id AND s.id = sa.student_id
            WHERE s.id IN ($placeholders)
              AND a.is_published = 1
              AND (sa.status IS NULL OR sa.status = 'pending')
              AND (a.due_date IS NULL OR a.due_date >= CURDATE())
            ORDER BY a.due_date ASC
            LIMIT $limit
        ");
        $stmt->execute($studentIds);
        return $stmt->fetchAll();
    }

    /* ============================================================
       MESSAGES
    ============================================================*/
    public function getMessages(int $parentId): array
    {
        // Get unique teachers the parent has messaged or received messages from
        $stmt = $this->db->prepare("
            SELECT DISTINCT u.id AS teacher_id, u.fullname AS teacher_name
            FROM users u
            JOIN parent_teacher_messages ptm ON u.id = ptm.teacher_id
            WHERE ptm.parent_id = ?
        ");
        $stmt->execute([$parentId]);
        $teachers = $stmt->fetchAll();

        // Also add teachers for all the parent's children (even if no messages yet)
        $stmt = $this->db->prepare("
            SELECT DISTINCT u.id AS teacher_id, u.fullname AS teacher_name
            FROM users u
            JOIN lessons l ON u.id = l.teacher_id
            JOIN students s ON l.class_grade = s.class_grade
            JOIN parent_student ps ON s.id = ps.student_id
            JOIN parents p ON ps.parent_id = p.user_id
            WHERE p.id = ? AND u.status = 'active'
        ");
        $stmt->execute([$parentId]);
        $allTeachers = $stmt->fetchAll();
        
        // Merge & deduplicate
        $mergedTeachers = [];
        foreach (array_merge($teachers, $allTeachers) as $t) {
            $mergedTeachers[(int)$t['teacher_id']] = $t;
        }

        // Fetch threads for each teacher
        foreach ($mergedTeachers as &$t) {
            $stmt = $this->db->prepare("
                SELECT ptm.*, stu.fullname AS student_name
                FROM parent_teacher_messages ptm
                LEFT JOIN students s ON ptm.student_id = s.id
                LEFT JOIN users stu ON s.user_id = stu.id
                WHERE ptm.parent_id = ? AND ptm.teacher_id = ?
                ORDER BY ptm.created_at ASC
            ");
            $stmt->execute([$parentId, $t['teacher_id']]);
            $t['messages'] = $stmt->fetchAll();
            
            // Unread count for this teacher
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as c FROM parent_teacher_messages
                WHERE parent_id = ? AND teacher_id = ? AND sender_type = 'teacher' AND is_read = 0
            ");
            $stmt->execute([$parentId, $t['teacher_id']]);
            $t['unread'] = (int)$stmt->fetch()['c'];
        }

        return array_values($mergedTeachers);
    }

    public function sendMessage(int $parentId, int $teacherId, int $studentId, string $message): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO parent_teacher_messages (parent_id, teacher_id, student_id, sender_type, message)
            VALUES (?, ?, ?, 'parent', ?)
        ");
        $stmt->execute([$parentId, $teacherId, $studentId ?: null, $message]);
    }
    
    public function markMessagesRead(int $parentId, int $teacherId): void
    {
        $stmt = $this->db->prepare("
            UPDATE parent_teacher_messages
            SET is_read = 1
            WHERE parent_id = ? AND teacher_id = ? AND sender_type = 'teacher'
        ");
        $stmt->execute([$parentId, $teacherId]);
    }

    public function getUnreadMessageCount(int $parentId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS c FROM parent_teacher_messages 
            WHERE parent_id = ? AND sender_type = 'teacher' AND is_read = 0
        ");
        $stmt->execute([$parentId]);
        return (int)$stmt->fetch()['c'];
    }

    /* ============================================================
       NOTIFICATIONS
    ============================================================*/
    public function getNotifications(int $userId, int $limit = 20): array
    {
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

    public function markAllNotificationsRead(int $userId): void
    {
        $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$userId]);
    }
}
