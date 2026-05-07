<?php

class User extends Model {

    /* ============================================================
       EMAIL / REG_NO EXISTENCE CHECKS
    ============================================================*/
    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->rowCount() > 0;
    }

    /* ============================================================
       CREATE USER (pending enrollment)
    ============================================================*/
    public function createUser(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO users (fullname, email, password, role, status, must_change_password)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['fullname'],
            $data['email'],
            $data['password'],
            $data['role'],
            $data['status']              ?? 'pending',
            $data['must_change_password'] ?? 1,
        ]);
        return (int)$this->db->lastInsertId();
    }

    /* ============================================================
       CREATE STUDENT RECORD
    ============================================================*/
    public function createStudent(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO students
              (user_id, class_grade, birth_id, birth_certificate_file, passport_photo, date_of_birth, gender)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['user_id'],
            $data['class_grade'],
            $data['birth_id'],
            $data['birth_certificate_file'],
            $data['passport_photo'],
            $data['date_of_birth'] ?? null,
            $data['gender']        ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    /* ============================================================
       CREATE PARENT RECORD
    ============================================================*/
    public function createParent(array $data): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO parents (user_id, phone, id_number, relationship)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['user_id'],
            $data['phone'],
            $data['id_number']    ?? null,
            $data['relationship'] ?? 'guardian',
        ]);
    }

    /* ============================================================
       LINK PARENT → STUDENT
    ============================================================*/
    public function linkParentStudent(int $parentUserId, int $studentId): void
    {
        $stmt = $this->db->prepare("
            INSERT IGNORE INTO parent_student (parent_id, student_id)
            VALUES (?, ?)
        ");
        $stmt->execute([$parentUserId, $studentId]);
    }

    /* ============================================================
       LOGIN  – Student / Parent
       Uses: reg_no + email + password
       Parent can log in with the student's reg_no + their own email
    ============================================================*/
    public function login(string $regNo, string $email, string $password): array|false
    {
        // --- Student login ---
        $stmt = $this->db->prepare("
            SELECT * FROM users
            WHERE student_reg_no = ?
              AND email = ?
              AND status = 'approved'
        ");
        $stmt->execute([$regNo, $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }

        // --- Parent login: reg_no belongs to child, email belongs to parent ---
        $stmt = $this->db->prepare("
            SELECT u.*
            FROM users u
            JOIN parent_student ps ON u.id       = ps.parent_id
            JOIN students       s  ON ps.student_id = s.id
            JOIN users          su ON s.user_id   = su.id
            WHERE su.student_reg_no = ?
              AND u.email = ?
              AND u.status = 'approved'
        ");
        $stmt->execute([$regNo, $email]);
        $parent = $stmt->fetch();

        if ($parent && password_verify($password, $parent['password'])) {
            return $parent;
        }

        return false;
    }

    /* ============================================================
       LOGIN BY EMAIL  – Teacher / Admin
    ============================================================*/
    public function loginByEmail(string $email, string $password): array|false
    {
        $stmt = $this->db->prepare("
            SELECT * FROM users
            WHERE email = ?
              AND status = 'active'
              AND role IN ('teacher','admin')
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }

    /* ============================================================
       UPDATE PASSWORD  (force change on first login)
    ============================================================*/
    public function updatePassword(int $userId, string $hashedPassword): void
    {
        $stmt = $this->db->prepare("
            UPDATE users
            SET password = ?, must_change_password = 0
            WHERE id = ?
        ");
        $stmt->execute([$hashedPassword, $userId]);
    }

    /* ============================================================
       GET PENDING ENROLLMENTS  (admin view)
    ============================================================*/
    public function getPendingEnrollments(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                u.id            AS student_user_id,
                u.fullname      AS student_name,
                u.email         AS student_email,
                u.created_at,
                s.id            AS student_id,
                s.class_grade,
                s.birth_id,
                s.birth_certificate_file,
                s.passport_photo,
                s.date_of_birth,
                s.gender,
                pu.id           AS parent_user_id,
                pu.fullname     AS parent_name,
                pu.email        AS parent_email,
                pr.phone        AS parent_phone,
                pr.relationship AS parent_relationship
            FROM users u
            JOIN students      s  ON u.id          = s.user_id
            JOIN parent_student ps ON s.id          = ps.student_id
            JOIN users         pu  ON ps.parent_id  = pu.id
            LEFT JOIN parents  pr  ON pu.id         = pr.user_id
            WHERE u.role   = 'student'
              AND u.status = 'pending'
            ORDER BY u.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /* ============================================================
       APPROVE ENROLLMENT
       Generates:  reg_no  = CBE / {student_db_id} / {grade_number} / {YY}
                   passwords (random 8-char hex) for student & parent
       Returns data for emailing
    ============================================================*/
    public function approveEnrollment(int $studentUserId): array
    {
        // Get student details
        $stmt = $this->db->prepare("
            SELECT u.fullname, u.email, s.id AS student_id, s.class_grade
            FROM users u
            JOIN students s ON u.id = s.user_id
            WHERE u.id = ?
        ");
        $stmt->execute([$studentUserId]);
        $student = $stmt->fetch();

        if (!$student) {
            throw new RuntimeException("Student not found: $studentUserId");
        }

        // Get parent details
        $stmt = $this->db->prepare("
            SELECT pu.id AS parent_user_id, pu.fullname, pu.email
            FROM parent_student ps
            JOIN users pu ON ps.parent_id = pu.id
            WHERE ps.student_id = ?
        ");
        $stmt->execute([$student['student_id']]);
        $parent = $stmt->fetch();

        // Build registration number:  CBE/{users.id}/{grade_digits}/{YY}
        // Extract numeric portion from grade (e.g. "Grade 4" → "4", "Form 2" → "2")
        preg_match('/\d+/', $student['class_grade'], $gradeMatch);
        $gradeNum = isset($gradeMatch[0]) ? str_pad($gradeMatch[0], 2, '0', STR_PAD_LEFT) : '00';
        $yy       = date('y');    // last two digits of current year
        $regNo    = 'CBE/' . $studentUserId . '/' . $gradeNum . '/' . $yy;

        // Random passwords
        $studentPassword = bin2hex(random_bytes(4));   // 8 hex chars
        $parentPassword  = bin2hex(random_bytes(4));

        // Update student user
        $stmt = $this->db->prepare("
            UPDATE users
            SET status = 'approved',
                password = ?,
                must_change_password = 1,
                student_reg_no = ?
            WHERE id = ?
        ");
        $stmt->execute([password_hash($studentPassword, PASSWORD_DEFAULT), $regNo, $studentUserId]);

        // Update parent user (if exists)
        if ($parent) {
            $stmt = $this->db->prepare("
                UPDATE users
                SET status = 'approved',
                    password = ?,
                    must_change_password = 1
                WHERE id = ?
            ");
            $stmt->execute([password_hash($parentPassword, PASSWORD_DEFAULT), $parent['parent_user_id']]);
        }

        return [
            'registration_no'  => $regNo,
            'student_name'     => $student['fullname'],
            'student_email'    => $student['email'],
            'student_password' => $studentPassword,
            'parent_name'      => $parent['fullname']  ?? '',
            'parent_email'     => $parent['email']     ?? '',
            'parent_password'  => $parentPassword,
        ];
    }

    /* ============================================================
       REJECT ENROLLMENT
       Marks both student + parent as rejected, returns emails
    ============================================================*/
    public function rejectEnrollment(int $studentUserId): array
    {
        // Get student
        $stmt = $this->db->prepare("
            SELECT u.fullname, u.email, s.id AS student_id
            FROM users u JOIN students s ON u.id = s.user_id
            WHERE u.id = ?
        ");
        $stmt->execute([$studentUserId]);
        $student = $stmt->fetch();

        // Get parent
        $stmt = $this->db->prepare("
            SELECT pu.id AS parent_user_id, pu.fullname, pu.email
            FROM parent_student ps
            JOIN users pu ON ps.parent_id = pu.id
            WHERE ps.student_id = ?
        ");
        $stmt->execute([$student['student_id'] ?? 0]);
        $parent = $stmt->fetch();

        // Reject both
        $stmt = $this->db->prepare("UPDATE users SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$studentUserId]);

        if ($parent) {
            $stmt->execute([$parent['parent_user_id']]);
        }

        return [
            'student_name'  => $student['fullname'] ?? '',
            'student_email' => $student['email']    ?? '',
            'parent_name'   => $parent['fullname']  ?? '',
            'parent_email'  => $parent['email']     ?? '',
        ];
    }

    /* ============================================================
       DASHBOARD STATS  (admin)
    ============================================================*/
    public function getDashboardStats(): array
    {
        $stats = [];

        $q = fn($sql) => (int) $this->db->query($sql)->fetch()['c'];

        $stats['total_students']       = $q("SELECT COUNT(*) AS c FROM users WHERE role = 'student'");
        $stats['active_teachers']      = $q("SELECT COUNT(*) AS c FROM users WHERE role = 'teacher' AND status IN ('active','approved')");
        $stats['total_parents']        = $q("SELECT COUNT(*) AS c FROM users WHERE role = 'parent'");
        $stats['pending_count']        = $q("SELECT COUNT(*) AS c FROM users WHERE role = 'student' AND status = 'pending'");
        $stats['approved_students']    = $q("SELECT COUNT(*) AS c FROM users WHERE role = 'student' AND status = 'approved'");

        return $stats;
    }

    /* ============================================================
       GET ALL USERS  (admin user management)
    ============================================================*/
    public function getAllUsers(string $search = '', string $role = ''): array
    {
        $sql    = "SELECT id, fullname, email, role, status, student_reg_no, created_at FROM users WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql    .= " AND (fullname LIKE ? OR email LIKE ? OR student_reg_no LIKE ?)";
            $params  = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
        }
        if (!empty($role)) {
            $sql    .= " AND role = ?";
            $params[] = $role;
        }
        $sql .= " ORDER BY id DESC LIMIT 200";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /* ============================================================
       GET USER BY ID
    ============================================================*/
    public function getUserById(int $id): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /* ============================================================
       MONTHLY ENROLLMENT TRENDS  (chart data)
    ============================================================*/
    public function getMonthlyEnrollmentTrends(): array
    {
        $months = $students = $teachers = [];

        for ($i = 5; $i >= 0; $i--) {
            $start     = date('Y-m-01', strtotime("-$i months"));
            $end       = date('Y-m-t',  strtotime("-$i months")) . ' 23:59:59';
            $months[]  = date('M Y', strtotime("-$i months"));

            $s = $this->db->prepare("SELECT COUNT(*) AS c FROM users WHERE role = 'student' AND created_at BETWEEN ? AND ?");
            $s->execute([$start, $end]);
            $students[] = (int)$s->fetch()['c'];

            $t = $this->db->prepare("SELECT COUNT(*) AS c FROM users WHERE role = 'teacher' AND created_at BETWEEN ? AND ?");
            $t->execute([$start, $end]);
            $teachers[] = (int)$t->fetch()['c'];
        }

        return ['labels' => $months, 'students' => $students, 'teachers' => $teachers];
    }

    /* ============================================================
       GET RECENTLY REGISTERED USERS  (for dashboard widget)
    ============================================================*/
    public function getRecentlyRegisteredUsers(int $limit = 5): array
    {
        $stmt = $this->db->prepare("
            SELECT id, fullname, email, role, status, created_at 
            FROM users 
            ORDER BY created_at DESC 
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /* ============================================================
       CREATE TEACHER
       Creates an active teacher account, returns plain password
    ============================================================*/
    public function createTeacher(array $data): array
    {
        $plainPassword = bin2hex(random_bytes(4));   // 8-char hex temp password

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("
                INSERT INTO users (fullname, email, password, role, status, must_change_password)
                VALUES (?, ?, ?, 'teacher', 'active', 1)
            ");
            $stmt->execute([
                $data['fullname'],
                $data['email'],
                password_hash($plainPassword, PASSWORD_DEFAULT),
            ]);
            
            $userId = (int)$this->db->lastInsertId();

            $stmt2 = $this->db->prepare("
                INSERT INTO teachers (user_id, tsc_number, specialization, phone)
                VALUES (?, ?, ?, ?)
            ");
            $stmt2->execute([
                $userId,
                $data['tsc_number'] ?? null,
                $data['specialization'] ?? null,
                $data['phone'] ?? null
            ]);

            $this->db->commit();

            return [
                'id'           => $userId,
                'fullname'     => $data['fullname'],
                'email'        => $data['email'],
                'subject'      => $data['specialization'] ?? '', // alias for email 
                'plain_password' => $plainPassword,
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /* ============================================================
       DEACTIVATE / REACTIVATE USER
    ============================================================*/
    public function deactivateUser(int $id, string $action = 'deactivate'): void
    {
        $newStatus = ($action === 'reactivate') ? 'active' : 'inactive';
        $stmt = $this->db->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $id]);
    }

    /* ============================================================
       RESET USER PASSWORD
       Generates a temp password, saves it, returns plain text + user info
    ============================================================*/
    public function resetUserPassword(int $id): array
    {
        $stmt = $this->db->prepare("SELECT fullname, email FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        if (!$user) {
            throw new RuntimeException("User not found: $id");
        }

        $plain = bin2hex(random_bytes(4));

        $stmt = $this->db->prepare("
            UPDATE users SET password = ?, must_change_password = 1 WHERE id = ?
        ");
        $stmt->execute([password_hash($plain, PASSWORD_DEFAULT), $id]);

        return [
            'fullname'       => $user['fullname'],
            'email'          => $user['email'],
            'plain_password' => $plain,
        ];
    }

    /* ============================================================
       STUDENT PERFORMANCE REPORT
    ============================================================*/
    public function getStudentReport(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                u.fullname     AS student_name,
                u.email        AS student_email,
                u.student_reg_no,
                u.status,
                u.created_at,
                s.class_grade,
                s.gender,
                s.date_of_birth
            FROM users u
            LEFT JOIN students s ON u.id = s.user_id
            WHERE u.role = 'student'
            ORDER BY u.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /* ============================================================
       TEACHER ACTIVITY REPORT
    ============================================================*/
    public function getTeacherReport(): array
    {
        $stmt = $this->db->prepare("
            SELECT id, fullname, email, status, created_at, updated_at
            FROM users
            WHERE role = 'teacher'
            ORDER BY created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /* ============================================================
       SYSTEM ANALYTICS
    ============================================================*/
    public function getSystemAnalytics(): array
    {
        $q = fn($sql) => (int) $this->db->query($sql)->fetch()['c'];

        return [
            'total_users'         => $q("SELECT COUNT(*) AS c FROM users"),
            'total_students'      => $q("SELECT COUNT(*) AS c FROM users WHERE role = 'student'"),
            'total_teachers'      => $q("SELECT COUNT(*) AS c FROM users WHERE role = 'teacher'"),
            'total_parents'       => $q("SELECT COUNT(*) AS c FROM users WHERE role = 'parent'"),
            'approved_students'   => $q("SELECT COUNT(*) AS c FROM users WHERE role = 'student' AND status = 'approved'"),
            'pending_enrollments' => $q("SELECT COUNT(*) AS c FROM users WHERE role = 'student' AND status = 'pending'"),
            'inactive_users'      => $q("SELECT COUNT(*) AS c FROM users WHERE status = 'inactive'"),
        ];
    }
}
