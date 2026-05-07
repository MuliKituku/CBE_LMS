<?php

class AdminController extends Controller {

    /* ================================================================
       ADMIN DASHBOARD
    ================================================================*/
    public function dashboard(): void
    {
        $this->requireRole('admin');
        $userModel  = $this->model('User');
        $adminModel = $this->model('AdminModel');

        $stats      = $userModel->getDashboardStats();
        $extStats   = $adminModel->getExtendedStats();
        $trends     = $userModel->getMonthlyEnrollmentTrends();
        $recentUsers = $userModel->getRecentlyRegisteredUsers(8);
        $pendingReqs = $adminModel->getTeacherRequests('pending');
        
        $data = [
            'stats'        => array_merge($stats, $extStats),
            'trends'       => $trends,
            'recentUsers'  => $recentUsers,
            'pendingReqs'  => $pendingReqs,
            'gradeDist'    => $adminModel->getStudentsPerGrade()
        ];

        $this->view('admin/dashboard', $data);
    }

    /* ================================================================
       PENDING ENROLLMENTS LIST
    ================================================================*/
    public function pendingEnrollments(): void
    {
        $this->requireRole('admin');
        $userModel   = $this->model('User');
        $enrollments = $userModel->getPendingEnrollments();
        $this->view('admin/pending_enrollments', ['enrollments' => $enrollments]);
    }

    /* ================================================================
       APPROVE ENROLLMENT
    ================================================================*/
    public function approveEnrollment(): void
    {
        $this->requireRole('admin');

        $studentUserId = (int)($_POST['student_user_id'] ?? 0);
        if (!$studentUserId) {
            $this->redirect('admin/pendingEnrollments&error=invalid'); return;
        }

        $userModel = $this->model('User');

        try {
            $data = $userModel->approveEnrollment($studentUserId);
        } catch (Exception $e) {
            error_log($e->getMessage());
            $this->redirect('admin/pendingEnrollments&error=failed'); return;
        }

        /* ---- Email Student ---- */
        $studentBody = "
        <div style='font-family:Inter,sans-serif;max-width:600px;margin:auto;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden'>
          <div style='background:#4f46e5;padding:30px;text-align:center'>
            <h1 style='color:#fff;margin:0;font-size:1.6rem'>🎓 CBE LMS</h1>
          </div>
          <div style='padding:30px'>
            <h2 style='color:#1e293b'>Enrollment Approved!</h2>
            <p>Dear <strong>{$data['student_name']}</strong>,</p>
            <p>We are pleased to inform you that your enrollment has been <strong>approved</strong>. Below are your login credentials:</p>
            <table style='width:100%;border-collapse:collapse;margin:20px 0'>
              <tr><td style='padding:10px;background:#f8fafc;border:1px solid #e2e8f0;font-weight:600'>Registration Number</td>
                  <td style='padding:10px;border:1px solid #e2e8f0'><strong>{$data['registration_no']}</strong></td></tr>
              <tr><td style='padding:10px;background:#f8fafc;border:1px solid #e2e8f0;font-weight:600'>Email</td>
                  <td style='padding:10px;border:1px solid #e2e8f0'>{$data['student_email']}</td></tr>
              <tr><td style='padding:10px;background:#f8fafc;border:1px solid #e2e8f0;font-weight:600'>Temporary Password</td>
                  <td style='padding:10px;border:1px solid #e2e8f0'><code>{$data['student_password']}</code></td></tr>
            </table>
            <p style='color:#dc2626;font-weight:600'>⚠ You will be required to change your password upon first login.</p>
            <p>Login at: <a href='" . APP_URL . BASE_URL . "/public/index.php?url=auth/login'>CBE LMS Portal</a></p>
            <hr style='border:none;border-top:1px solid #e2e8f0;margin:20px 0'>
            <p style='color:#64748b;font-size:.85rem'>CBE Learning Management System &mdash; Kenya</p>
          </div>
        </div>";

        Mailer::send($data['student_email'], $data['student_name'], 'Enrollment Approved – CBE LMS', $studentBody);

        /* ---- Email Parent ---- */
        if (!empty($data['parent_email'])) {
            $parentBody = "
            <div style='font-family:Inter,sans-serif;max-width:600px;margin:auto;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden'>
              <div style='background:#4f46e5;padding:30px;text-align:center'>
                <h1 style='color:#fff;margin:0;font-size:1.6rem'>🎓 CBE LMS</h1>
              </div>
              <div style='padding:30px'>
                <h2 style='color:#1e293b'>Student Enrollment Approved</h2>
                <p>Dear <strong>{$data['parent_name']}</strong>,</p>
                <p>Your child <strong>{$data['student_name']}</strong> has been successfully enrolled. Here are your parent portal login credentials:</p>
                <table style='width:100%;border-collapse:collapse;margin:20px 0'>
                  <tr><td style='padding:10px;background:#f8fafc;border:1px solid #e2e8f0;font-weight:600'>Student Reg No (use to login)</td>
                      <td style='padding:10px;border:1px solid #e2e8f0'><strong>{$data['registration_no']}</strong></td></tr>
                  <tr><td style='padding:10px;background:#f8fafc;border:1px solid #e2e8f0;font-weight:600'>Your Email</td>
                      <td style='padding:10px;border:1px solid #e2e8f0'>{$data['parent_email']}</td></tr>
                  <tr><td style='padding:10px;background:#f8fafc;border:1px solid #e2e8f0;font-weight:600'>Temporary Password</td>
                      <td style='padding:10px;border:1px solid #e2e8f0'><code>{$data['parent_password']}</code></td></tr>
                </table>
                <p style='color:#dc2626;font-weight:600'>⚠ You will be required to change your password upon first login.</p>
                <p>Login at: <a href='" . APP_URL . BASE_URL . "/public/index.php?url=auth/login'>CBE LMS Portal</a></p>
                <hr style='border:none;border-top:1px solid #e2e8f0;margin:20px 0'>
                <p style='color:#64748b;font-size:.85rem'>CBE Learning Management System &mdash; Kenya</p>
              </div>
            </div>";

            Mailer::send($data['parent_email'], $data['parent_name'], 'Child Enrollment Approved – CBE LMS', $parentBody);
        }

        $this->redirect('admin/pendingEnrollments&success=approved');
    }

    /* ================================================================
       REJECT ENROLLMENT
    ================================================================*/
    public function rejectEnrollment(): void
    {
        $this->requireRole('admin');

        $studentUserId = (int)($_POST['student_user_id'] ?? 0);
        if (!$studentUserId) {
            $this->redirect('admin/pendingEnrollments&error=invalid'); return;
        }

        $userModel = $this->model('User');
        $data      = $userModel->rejectEnrollment($studentUserId);

        /* ---- Email Student ---- */
        $studentBody = "
        <div style='font-family:Inter,sans-serif;max-width:600px;margin:auto;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden'>
          <div style='background:#dc2626;padding:30px;text-align:center'>
            <h1 style='color:#fff;margin:0;font-size:1.6rem'>🎓 CBE LMS</h1>
          </div>
          <div style='padding:30px'>
            <h2 style='color:#1e293b'>Enrollment Update</h2>
            <p>Dear <strong>{$data['student_name']}</strong>,</p>
            <p>We regret to inform you that your enrollment application has been <strong>declined</strong> at this time.</p>
            <p>For further information, please <strong>visit the Administration Block</strong> with the original copies of your documents.</p>
            <p style='color:#64748b'>Office hours: Monday–Friday, 8:00 AM – 5:00 PM</p>
            <hr style='border:none;border-top:1px solid #e2e8f0;margin:20px 0'>
            <p style='color:#64748b;font-size:.85rem'>CBE Learning Management System &mdash; Kenya</p>
          </div>
        </div>";

        Mailer::send($data['student_email'], $data['student_name'], 'Enrollment Status – CBE LMS', $studentBody);

        /* ---- Email Parent ---- */
        if (!empty($data['parent_email'])) {
            $parentBody = "
            <div style='font-family:Inter,sans-serif;max-width:600px;margin:auto;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden'>
              <div style='background:#dc2626;padding:30px;text-align:center'>
                <h1 style='color:#fff;margin:0;font-size:1.6rem'>🎓 CBE LMS</h1>
              </div>
              <div style='padding:30px'>
                <h2 style='color:#1e293b'>Enrollment Declined</h2>
                <p>Dear <strong>{$data['parent_name']}</strong>,</p>
                <p>We regret to inform you that the enrollment application for <strong>{$data['student_name']}</strong> has been <strong>declined</strong>.</p>
                <p>For further information, please <strong>visit the Administration Block</strong> with the original copies of the student's documents.</p>
                <p style='color:#64748b'>Office hours: Monday–Friday, 8:00 AM – 5:00 PM</p>
                <hr style='border:none;border-top:1px solid #e2e8f0;margin:20px 0'>
                <p style='color:#64748b;font-size:.85rem'>CBE Learning Management System &mdash; Kenya</p>
              </div>
            </div>";

            Mailer::send($data['parent_email'], $data['parent_name'], 'Child Enrollment Declined – CBE LMS', $parentBody);
        }

        $this->redirect('admin/pendingEnrollments&success=rejected');
    }

    /* ================================================================
       MANAGE USERS
    ================================================================*/
    public function manageUsers(): void
    {
        $this->requireRole('admin');
        $search     = $_GET['search']      ?? '';
        $roleFilter = $_GET['role_filter'] ?? '';

        $userModel = $this->model('User');
        $users     = $userModel->getAllUsers($search, $roleFilter);

        $this->view('admin/manage_users', [
            'users'      => $users,
            'search'     => $search,
            'roleFilter' => $roleFilter,
        ]);
    }

    /* ================================================================
       CREATE TEACHER
    ================================================================*/
    public function createTeacher(): void
    {
        $this->requireRole('admin');

        $fullname       = trim($_POST['fullname'] ?? '');
        $email          = trim($_POST['email']    ?? '');
        $tscNumber      = trim($_POST['tsc_number'] ?? '');
        $specialization = trim($_POST['specialization'] ?? '');
        $phone          = trim($_POST['phone'] ?? '');

        if (!$fullname || !$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->redirect('admin/manageUsers&error=invalid_input'); return;
        }

        $userModel = $this->model('User');

        if ($userModel->emailExists($email)) {
            $this->redirect('admin/manageUsers&error=email_exists'); return;
        }

        $data = $userModel->createTeacher([
            'fullname'       => $fullname,
            'email'          => $email,
            'tsc_number'     => $tscNumber,
            'specialization' => $specialization,
            'phone'          => $phone
        ]);

        /* ---- Welcome Email ---- */
        $subjectLine = $specialization ? " | $specialization Department" : '';
        $body = "
        <div style='font-family:Inter,sans-serif;max-width:600px;margin:auto;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden'>
          <div style='background:#4f46e5;padding:30px;text-align:center'>
            <h1 style='color:#fff;margin:0;font-size:1.6rem'>🎓 CBE LMS</h1>
          </div>
          <div style='padding:30px'>
            <h2 style='color:#1e293b'>Welcome to CBE LMS{$subjectLine}</h2>
            <p>Dear <strong>{$data['fullname']}</strong>,</p>
            <p>A teacher account has been created for you on the CBE Learning Management System. Below are your login credentials:</p>
            <table style='width:100%;border-collapse:collapse;margin:20px 0'>
              <tr><td style='padding:10px;background:#f8fafc;border:1px solid #e2e8f0;font-weight:600'>Email</td>
                  <td style='padding:10px;border:1px solid #e2e8f0'>{$data['email']}</td></tr>
              <tr><td style='padding:10px;background:#f8fafc;border:1px solid #e2e8f0;font-weight:600'>Temporary Password</td>
                  <td style='padding:10px;border:1px solid #e2e8f0'><code style='background:#f1f5f9;padding:2px 6px;border-radius:4px'>{$data['plain_password']}</code></td></tr>
            </table>
            <p style='color:#dc2626;font-weight:600'>⚠ You will be required to change your password upon first login.</p>
            <p>Login at: <a href='" . APP_URL . BASE_URL . "/public/index.php?url=auth/login_at'>CBE LMS Staff Portal</a></p>
            <hr style='border:none;border-top:1px solid #e2e8f0;margin:20px 0'>
            <p style='color:#64748b;font-size:.85rem'>CBE Learning Management System &mdash; Kenya</p>
          </div>
        </div>";

        Mailer::send($data['email'], $data['fullname'], 'Your CBE LMS Teacher Account', $body);

        $this->redirect('admin/manageUsers&success=teacher_created');
    }

    /* ================================================================
       DEACTIVATE / REACTIVATE USER
    ================================================================*/
    public function deactivateUser(): void
    {
        $this->requireRole('admin');

        $userId = (int)($_POST['user_id'] ?? 0);
        $action = in_array($_POST['action'] ?? '', ['deactivate','reactivate'])
                  ? $_POST['action'] : 'deactivate';

        if (!$userId) {
            $this->redirect('admin/manageUsers&error=invalid'); return;
        }

        // Prevent admin from deactivating themselves
        if ($userId === (int)($_SESSION['user']['id'] ?? 0)) {
            $this->redirect('admin/manageUsers&error=cannot_self_deactivate'); return;
        }

        $userModel = $this->model('User');
        $userModel->deactivateUser($userId, $action);

        $successMsg = ($action === 'reactivate') ? 'reactivated' : 'deactivated';
        $this->redirect("admin/manageUsers&success={$successMsg}");
    }

    /* ================================================================
       RESET PASSWORD
    ================================================================*/
    public function resetPassword(): void
    {
        $this->requireRole('admin');

        $userId = (int)($_POST['user_id'] ?? 0);
        if (!$userId) {
            $this->redirect('admin/manageUsers&error=invalid'); return;
        }

        $userModel = $this->model('User');

        try {
            $data = $userModel->resetUserPassword($userId);
        } catch (Exception $e) {
            error_log($e->getMessage());
            $this->redirect('admin/manageUsers&error=reset_failed'); return;
        }

        /* ---- Email new credentials ---- */
        $body = "
        <div style='font-family:Inter,sans-serif;max-width:600px;margin:auto;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden'>
          <div style='background:#f59e0b;padding:30px;text-align:center'>
            <h1 style='color:#fff;margin:0;font-size:1.6rem'>🎓 CBE LMS</h1>
          </div>
          <div style='padding:30px'>
            <h2 style='color:#1e293b'>🔑 Password Reset</h2>
            <p>Dear <strong>{$data['fullname']}</strong>,</p>
            <p>Your password has been reset by an administrator. Your new temporary password is:</p>
            <table style='width:100%;border-collapse:collapse;margin:20px 0'>
              <tr><td style='padding:10px;background:#f8fafc;border:1px solid #e2e8f0;font-weight:600'>Email</td>
                  <td style='padding:10px;border:1px solid #e2e8f0'>{$data['email']}</td></tr>
              <tr><td style='padding:10px;background:#f8fafc;border:1px solid #e2e8f0;font-weight:600'>New Temporary Password</td>
                  <td style='padding:10px;border:1px solid #e2e8f0'><code style='background:#fef3c7;padding:2px 8px;border-radius:4px;font-size:1.1em'>{$data['plain_password']}</code></td></tr>
            </table>
            <p style='color:#dc2626;font-weight:600'>⚠ You will be required to change this password on your next login.</p>
            <p>Login at: <a href='" . APP_URL . BASE_URL . "/public/index.php?url=auth/login_at'>CBE LMS Portal</a></p>
            <hr style='border:none;border-top:1px solid #e2e8f0;margin:20px 0'>
            <p style='color:#64748b;font-size:.85rem'>CBE Learning Management System &mdash; Kenya</p>
          </div>
        </div>";

        Mailer::send($data['email'], $data['fullname'], 'CBE LMS – Password Reset', $body);

        $this->redirect('admin/manageUsers&success=password_reset');
    }

    /* ================================================================
       REPORTS
    ================================================================*/
    public function reports(): void
    {
        $this->requireRole('admin');
        $userModel  = $this->model('User');
        $adminModel = $this->model('AdminModel');

        $this->view('admin/reports', [
            'studentReport'   => $userModel->getStudentReport(),
            'teacherReport'   => $userModel->getTeacherReport(),
            'analytics'       => $userModel->getSystemAnalytics(),
            'competencies'    => $adminModel->getCompetencyStats(),
            'atRisk'          => $adminModel->getAtRiskStudents(),
            'usage'           => $adminModel->getSystemUsageStats(),
            'gradingScales'   => $adminModel->getGradingScales()
        ]);
    }

    /* ================================================================
       ANNOUNCEMENTS management
    ================================================================*/
    public function announcements(): void
    {
        $this->requireRole('admin');
        $adminModel = $this->model('AdminModel');
        $userModel  = $this->model('User');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title   = trim($_POST['title']   ?? '');
            $message = trim($_POST['message'] ?? '');
            $target  = $_POST['target'] ?? 'all';
            $selectedUsers = $_POST['target_user_ids'] ?? [];

            if ($title && $message) {
                $adminModel->createAnnouncement($_SESSION['user']['id'], $title, $message, $target, $selectedUsers);
                $this->redirect('admin/announcements&success=posted'); return;
            }
        }

        $this->view('admin/announcements', [
            'announcements' => $adminModel->getAnnouncements(),
            'users'         => $userModel->getAllUsers('', '')
        ]);
    }

    /* ================================================================
       SYSTEM SETTINGS (Grading Scales)
    ================================================================*/
    public function settings(): void
    {
        $this->requireRole('admin');
        $adminModel = $this->model('AdminModel');

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['scales'])) {
            $adminModel->updateGradingScales($_POST['scales']);
            $this->redirect('admin/settings&success=updated'); return;
        }

        $this->view('admin/settings', [
            'scales' => $adminModel->getGradingScales()
        ]);
    }

    /* ================================================================
       HANDLE TEACHER REQUESTS
    ================================================================*/
    public function handleTeacherRequest(): void
    {
        $this->requireRole('admin');
        $adminModel = $this->model('AdminModel');

        $id     = (int)($_POST['request_id'] ?? 0);
        $status = $_POST['status'] ?? 'pending';
        $notes  = trim($_POST['notes'] ?? '');

        if ($id && in_array($status, ['approved', 'rejected'])) {
            $adminModel->updateRequestStatus($id, $status, $notes);
            $this->redirect('admin/dashboard&success=request_handled');
        } else {
            $this->redirect('admin/dashboard&error=invalid_request');
        }
    }

    /* ================================================================
       MANAGE CLASSES / GRADES (Formalized)
    ================================================================*/
    public function manageClasses(): void
    {
        $this->requireRole('admin');
        $adminModel = $this->model('AdminModel');
        $userModel  = $this->model('User');
        
        $this->view('admin/manage_classes', [
            'classes'  => $adminModel->getAllClasses(),
            'teachers' => $userModel->getAllUsers('', 'teacher')
        ]);
    }

    public function addClass(): void
    {
        $this->requireRole('admin');
        $name = trim($_POST['name'] ?? '');
        if ($name) {
            $this->model('AdminModel')->createClass($name);
            $this->redirect('admin/manageClasses&success=class_added');
        } else {
            $this->redirect('admin/manageClasses&error=invalid_name');
        }
    }

    public function assignTeacher(): void
    {
        $this->requireRole('admin');
        $teacherId = (int)($_POST['teacher_id'] ?? 0);
        $classId   = (int)($_POST['class_id']   ?? 0);
        
        if ($teacherId && $classId) {
            $this->model('AdminModel')->assignTeacherToClass($teacherId, $classId);
            $this->redirect('admin/manageClasses&success=assigned');
        } else {
            $this->redirect('admin/manageClasses&error=invalid_selection');
        }
    }

    public function removeTeacherMapping(): void
    {
        $this->requireRole('admin');
        $teacherId = (int)($_POST['teacher_id'] ?? 0);
        $classId   = (int)($_POST['class_id']   ?? 0);
        
        if ($teacherId && $classId) {
            $this->model('AdminModel')->removeTeacherFromClass($teacherId, $classId);
            $this->redirect('admin/manageClasses&success=removed');
        }
    }

    /* ================================================================
       MANAGE LEARNING CONTENT (Moderation)
    ================================================================*/
    public function manageContent(): void
    {
        $this->requireRole('admin');
        $adminModel = $this->model('AdminModel');
        $this->view('admin/manage_content', [
            'lessons' => $adminModel->getAllLessons()
        ]);
    }

    public function toggleLessonStatus(): void
    {
        $this->requireRole('admin');
        $id     = (int)($_POST['lesson_id'] ?? 0);
        $status = (int)($_POST['status']    ?? 1);
        
        if ($id) {
            $this->model('AdminModel')->toggleLessonVisibility($id, $status);
            $this->redirect('admin/manageContent&success=updated');
        }
    }

    public function removeLesson(): void
    {
        $this->requireRole('admin');
        $id = (int)($_POST['lesson_id'] ?? 0);
        if ($id) {
            $this->model('AdminModel')->deleteLesson($id);
            $this->redirect('admin/manageContent&success=deleted');
        }
    }

    /* ================================================================
       VIEW ALL GRADES & OVERRIDE
    ================================================================*/
    public function viewGrades(): void
    {
        $this->requireRole('admin');
        $adminModel = $this->model('AdminModel');
        $this->view('admin/view_grades', [
            'grades' => $adminModel->getAllAssessmentsWithGrades()
        ]);
    }

    public function overrideGrade(): void
    {
        $this->requireRole('admin');
        $studentUserId = (int)($_POST['student_id']    ?? 0);
        $assessmentId  = (int)($_POST['assessment_id'] ?? 0);
        $newScore      = (int)($_POST['new_score']      ?? 0);
        
        if ($studentUserId && $assessmentId) {
            $this->model('AdminModel')->overrideGrade($studentUserId, $assessmentId, $newScore, $_SESSION['user']['id']);
            $this->redirect('admin/viewGrades&success=overridden');
        } else {
            $this->redirect('admin/viewGrades&error=invalid');
        }
    }

    /* ================================================================
       AUDIT LOGS
    ================================================================*/
    public function auditLogs(): void
    {
        $this->requireRole('admin');
        $adminModel = $this->model('AdminModel');
        $this->view('admin/audit_logs', [
            'logs' => $adminModel->getAuditLogs()
        ]);
    }

    public function viewLesson(int $id = 0): void
    {
        $this->requireRole('admin');
        $id = $id ?: (int)($_GET['id'] ?? 0);
        
        if (!$id) {
            $this->redirect('admin/manageContent&error=not_found'); return;
        }

        $adminModel = $this->model('AdminModel');
        $lesson = $adminModel->getLessonById($id);
        
        if (!$lesson) {
            $this->redirect('admin/manageContent&error=not_found'); return;
        }

        $feedback = $adminModel->getLessonFeedback($id);

        $this->view('admin/view_lesson', [
            'lesson'   => $lesson,
            'feedback' => $feedback
        ]);
    }

    public function clearMedia(): void
    {
        $this->requireRole('admin');
        $lessonId = (int)($_POST['lesson_id']    ?? 0);
        
        if ($lessonId) {
            $this->model('AdminModel')->clearLessonMedia($lessonId);
            $this->redirect("admin/viewLesson&id=$lessonId&success=media_cleared");
        } else {
            $this->redirect('admin/manageContent&error=invalid');
        }
    }

    public function postLessonFeedback(): void
    {
        $this->requireRole('admin');
        $lessonId = (int)($_POST['lesson_id'] ?? 0);
        $message  = trim($_POST['message'] ?? '');
        
        if ($lessonId && $message) {
            $this->model('AdminModel')->addLessonFeedback($lessonId, $_SESSION['user']['id'], $message);
            $this->redirect("admin/viewLesson&id=$lessonId&success=feedback_posted");
        } else {
            $this->redirect("admin/viewLesson&id=$lessonId&error=empty_message");
        }
    }
    /* ================================================================
       PATHWAY RECOMMENDATION MANAGEMENT
    ================================================================*/
    public function managePathways(): void
    {
        $this->requireRole('admin');
        $pwModel = $this->model('PathwayModel');
        $adminModel = $this->model('AdminModel');

        $questions = $pwModel->getAllQuestions();
        $settings  = $adminModel->getSystemSettings();
        $grades    = $adminModel->getStudentsPerGrade();
        
        $this->view('admin/manage_pathways', [
            'questions' => $questions,
            'settings'  => $settings,
            'grades'    => $grades
        ]);
    }

    public function savePathwayQuestion(): void
    {
        $this->requireRole('admin');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/managePathways'); return;
        }

        $id = (int)($_POST['id'] ?? 0);
        $question = trim($_POST['question'] ?? '');
        $pathway = $_POST['target_pathway'] ?? '';

        if (!$question || !$pathway) {
            $this->redirect('admin/managePathways&error=missing_fields'); return;
        }

        $pwModel = $this->model('PathwayModel');
        
        if ($id > 0) {
            $pwModel->updatePathwayQuestion($id, $question, $pathway);
        } else {
            $pwModel->createPathwayQuestion($question, $pathway);
        }

        $this->redirect('admin/managePathways&success=saved');
    }

    public function removePathwayQuestion(): void
    {
        $this->requireRole('admin');
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id > 0) {
            $pwModel = $this->model('PathwayModel');
            $pwModel->deletePathwayQuestion($id);
            $this->redirect('admin/managePathways&success=deleted');
        } else {
            $this->redirect('admin/managePathways&error=invalid');
        }
    }

    public function updatePathwaySettings(): void
    {
        $this->requireRole('admin');
        $count = (int)($_POST['questions_per_group'] ?? 3);
        
        $adminModel = $this->model('AdminModel');
        $adminModel->updateSystemSetting('pathway_questions_per_group', $count);

        $this->redirect('admin/managePathways&success=settings_updated');
    }

    public function launchPathwaySurvey(): void
    {
        $this->requireRole('admin');
        $grade = $_POST['grade'] ?? '';
        
        if (!$grade) {
            $this->redirect('admin/managePathways&error=invalid_grade'); return;
        }

        $pwModel = $this->model('PathwayModel');
        $adminModel = $this->model('AdminModel');

        // 1. Reset/Archive for students in grade
        $pwModel->launchSurveyForGrade($grade);

        // 2. Automated Announcement
        $studentUserIds = $adminModel->getStudentUserIdsByGrade($grade);
        if (!empty($studentUserIds)) {
            $title = "Action Required: Pathway Interest Survey Update";
            $msg = "A new Pathway Interest Survey has been launched for your grade ($grade). Plase take a few minutes to complete it to help us refine your Senior School recommendations. Note: Your results will be blended with your previous survey for better accuracy.";
            $adminModel->createAnnouncement($_SESSION['user']['id'], $title, $msg, 'targeted', $studentUserIds);
        }

        $this->redirect('admin/managePathways&success=survey_launched');
    }
}
