<?php

class StudentController extends Controller {

    /* ── Helpers ────────────────────────────────────────────── */
    private function studentModel(): Student
    {
        return $this->model('Student');
    }

    private function profile(): array
    {
        $userId  = (int)$_SESSION['user']['id'];
        $m       = $this->studentModel();
        $profile = $m->getProfile($userId);
        if (!$profile) {
            $this->redirect('auth/login');
            exit;
        }
        return $profile;
    }

    /* ================================================================
       DASHBOARD
    ================================================================*/
    public function dashboard(): void
    {
        $this->requireRole('student');
        $m       = $this->studentModel();
        $profile = $this->profile();
        $stats   = $m->getDashboardStats($profile['student_id']);
        $upcoming = $m->getUpcomingAssessments($profile['student_id'], $profile['class_grade']);
        $notices  = $m->getNotifications((int)$_SESSION['user']['id'], 5);
        $announcements = $m->getClassAnnouncements($profile['class_grade']);
        $schoolAnnouncements = $m->getAdminAnnouncements((int)$_SESSION['user']['id'], 'student');
        $activity      = $m->getRecentActivity($profile['student_id']);

        $this->view('student/dashboard', compact('profile','stats','upcoming','notices','announcements','schoolAnnouncements','activity'));
    }

    public function announcements(): void
    {
        $this->requireRole('student');
        $m = $this->model('Student');
        $profile = $m->getProfile((int)$_SESSION['user']['id']);
        
        $announcements = $m->getAdminAnnouncements((int)$_SESSION['user']['id'], 'student', 50);
        
        $this->view('student/announcements', [
            'profile' => $profile,
            'activeNav' => 'school_announcements',
            'announcements' => $announcements
        ]);
    }

    /* ================================================================
       LESSONS LIST
    ================================================================*/
    public function lessons(): void
    {
        $this->requireRole('student');
        $m        = $this->studentModel();
        $profile  = $this->profile();
        $subject  = $_GET['subject'] ?? '';
        $registeredOnly = isset($_GET['registered']);

        $lessons  = $m->getLessons($profile['class_grade'], $subject);
        $subjects = $m->getSubjectsForGrade($profile['class_grade']);
        $registeredIds = $m->getRegisteredLessonIds($profile['student_id']);

        if ($registeredOnly) {
            $lessons = array_filter($lessons, function($l) use ($registeredIds) {
                return in_array($l['id'], $registeredIds);
            });
        }

        $activeNav = $registeredOnly ? 'lessons' : 'registration';

        $this->view('student/lessons', compact('profile','lessons','subjects','subject','registeredIds','activeNav'));
    }

    /* ================================================================
       REGISTER FOR A LESSON
    ================================================================*/
    public function registerLesson(): void
    {
        $this->requireRole('student');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('student/lessons');
        }

        $lessonId  = (int)($_POST['lesson_id'] ?? 0);
        $studentId = $this->profile()['student_id'];

        if ($lessonId > 0) {
            $success = $this->studentModel()->registerLesson($studentId, $lessonId);
            if ($success) {
                $this->redirect('student/lessons?success=registered');
            }
        }
        $this->redirect('student/lessons?error=registration_failed');
    }

    /* ================================================================
       SINGLE LESSON VIEW
    ================================================================*/
    public function lesson(int $id = 0): void
    {
        $this->requireRole('student');
        $id = $id ?: (int)($_GET['id'] ?? 0);
        if (!$id) { $this->redirect('student/lessons'); return; }

        $m       = $this->studentModel();
        $profile = $this->profile();
        $lesson  = $m->getLessonWithInteractions($id, $profile['student_id']);
        if (!$lesson) { $this->redirect('student/lessons'); return; }

        // Advance to 'media' step if first visit
        $m->advanceLessonProgress($id, $profile['student_id'], 'media');

        $discussions = $m->getDiscussions($id);

        $this->view('student/lesson_view', compact('profile','lesson','discussions'));
    }

    /* ================================================================
       SUBMIT INTERACTION (AJAX)
    ================================================================*/
    public function submitInteraction(): void
    {
        $this->requireRole('student');
        header('Content-Type: application/json');

        $lessonId      = (int)($_POST['lesson_id']      ?? 0);
        $interactionId = (int)($_POST['interaction_id'] ?? 0);
        $answer        = trim($_POST['answer']          ?? '');

        if (!$lessonId || !$interactionId) {
            echo json_encode(['error' => 'Invalid request']); exit;
        }

        $m       = $this->studentModel();
        $profile = $this->profile();
        $result  = $m->submitInteraction($lessonId, $profile['student_id'], $interactionId, $answer);
        echo json_encode($result);
        exit;
    }

    /* ================================================================
       ADVANCE LESSON STEP (AJAX)
    ================================================================*/
    public function advanceStep(): void
    {
        $this->requireRole('student');
        header('Content-Type: application/json');

        $lessonId = (int)($_POST['lesson_id'] ?? 0);
        $step     = trim($_POST['step']        ?? '');
        $allowed  = ['interaction', 'activity', 'assessment', 'completed'];

        if (!$lessonId || !in_array($step, $allowed)) {
            echo json_encode(['error' => 'Invalid']); exit;
        }

        $m       = $this->studentModel();
        $profile = $this->profile();

        $score = null;
        if (in_array($step, ['interaction', 'activity', 'assessment', 'completed'])) {
            $score = $m->calcInteractionScore($lessonId, $profile['student_id']);
        }
        $m->advanceLessonProgress($lessonId, $profile['student_id'], $step, $score);
        echo json_encode(['ok' => true, 'score' => $score]);
        exit;
    }

    /* ================================================================
       SUBMIT ACTIVITY (POST with optional file)
    ================================================================*/
    public function submitActivity(): void
    {
        $this->requireRole('student');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('student/lessons'); return;
        }

        $lessonId  = (int)($_POST['lesson_id']  ?? 0);
        $activityId = (int)($_POST['activity_id'] ?? 0);
        $text       = trim($_POST['response_text'] ?? '');
        $fileUrl    = null;

        if (!empty($_FILES['activity_file']['name'])) {
            $targetDir = BASE_PATH . '/public/uploads/activities/';
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
            $fileName = time() . '_' . basename($_FILES['activity_file']['name']);
            if (move_uploaded_file($_FILES['activity_file']['tmp_name'], $targetDir . $fileName)) {
                $fileUrl = '/CBE_LMS/public/uploads/activities/' . $fileName;
            }
        }

        $m       = $this->studentModel();
        $profile = $this->profile();
        $m->submitActivity($lessonId, $profile['student_id'], $activityId, $text, $fileUrl);
        // Advance to assessment step
        $m->advanceLessonProgress($lessonId, $profile['student_id'], 'assessment');

        $this->redirect("student/lesson&id={$lessonId}&success=activity_submitted");
    }

    /* ================================================================
       ASSESSMENTS LIST
    ================================================================*/
    public function assessments(): void
    {
        $this->requireRole('student');
        $m           = $this->studentModel();
        $profile     = $this->profile();
        $assessments = $m->getAssessments($profile['student_id'], $profile['class_grade']);

        $upcoming  = array_filter($assessments, fn($a) =>
            in_array($a['attempt_status'] ?? null, [null, 'pending']));
        $completed = array_filter($assessments, fn($a) =>
            in_array($a['attempt_status'] ?? '', ['submitted','graded']));

        $this->view('student/assessments', compact('profile','upcoming','completed'));
    }

    /* ================================================================
       TAKE ASSESSMENT  (quiz form)
    ================================================================*/
    public function takeAssessment(int $id = 0): void
    {
        $this->requireRole('student');
        $id = $id ?: (int)($_GET['id'] ?? 0);
        if (!$id) { $this->redirect('student/assessments'); return; }

        $m          = $this->studentModel();
        $profile    = $this->profile();
        $assessment = $m->getAssessmentDetail($id);

        if (!$assessment) { $this->redirect('student/assessments'); return; }

        // Access timing & attempt limit check
        $attempts = $m->getAssessments($profile['student_id'], $profile['class_grade']);
        foreach ($attempts as $a) {
            if ((int)$a['id'] === $id) {
                $maxPossible = (int)($a['max_attempts'] ?? 1);
                $doneCount   = (int)($a['attempts_count'] ?? 0);

                if ($doneCount >= $maxPossible) {
                    $this->redirect("student/assessments&error=already_submitted");
                    return;
                }
                
                if ($a['timing_status'] === 'upcoming') {
                    $this->redirect("student/assessments&error=not_yet_available");
                    return;
                }
                if ($a['timing_status'] === 'expired') {
                    $this->redirect("student/assessments&error=closed");
                    return;
                }
            }
        }

        $this->view('student/take_assessment', compact('profile','assessment'));
    }

    /* ================================================================
       SUBMIT ASSESSMENT  (POST)
    ================================================================*/
    public function submitAssessment(): void
    {
        $this->requireRole('student');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('student/assessments'); return;
        }

        $assessmentId = (int)($_POST['assessment_id'] ?? 0);
        $answers      = $_POST['answers'] ?? [];

        if (!$assessmentId || !is_array($answers)) {
            $this->redirect('student/assessments&error=invalid'); return;
        }

        $m       = $this->studentModel();
        $profile = $this->profile();
        $result  = $m->submitAssessment($profile['student_id'], $assessmentId, $answers);

        $pct     = (int)round($result['percentage']);
        $graded  = $result['graded'] ? 'yes' : 'no';
        $this->redirect("student/assessments&success=submitted&score={$result['score']}&max={$result['max_score']}&pct={$pct}&graded={$graded}");
    }

    /* ================================================================
       PROGRESS  (Chart.js charts)
    ================================================================*/
    public function progress(): void
    {
        $this->requireRole('student');
        $m            = $this->studentModel();
        $profile      = $this->profile();
        $competencies = $m->getCompetencies($profile['student_id'], $profile['class_grade']);
        $progressData = $m->getProgressData($profile['student_id'], $profile['class_grade']);

        $this->view('student/progress', compact('profile','competencies','progressData'));
    }

    /* ================================================================
       FEEDBACK
    ================================================================*/
    public function feedback(): void
    {
        $this->requireRole('student');
        $m        = $this->studentModel();
        $profile  = $this->profile();
        $feedback = $m->getFeedback($profile['student_id']);

        $this->view('student/feedback', compact('profile','feedback'));
    }

    /* ================================================================
       DISCUSSIONS  (per lesson forum)
    ================================================================*/
    public function discussions(int $lessonId = 0): void
    {
        $this->requireRole('student');
        $lessonId = $lessonId ?: (int)($_GET['lesson_id'] ?? 0);

        $m          = $this->studentModel();
        $profile    = $this->profile();
        $allLessons = $m->getLessons($profile['class_grade']);

        if ($lessonId) {
            $lesson      = $m->getLessonWithResources($lessonId);
            $discussions = $m->getDiscussions($lessonId);
        } else {
            $lesson      = null;
            $discussions = [];
        }

        $this->view('student/discussions', compact('profile','lesson','discussions','lessonId','allLessons'));
    }

    /* ================================================================
       POST DISCUSSION  (POST)
    ================================================================*/
    public function postDiscussion(): void
    {
        $this->requireRole('student');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('student/lessons'); return;
        }

        $lessonId = (int)($_POST['lesson_id'] ?? 0);
        $parentId = (int)($_POST['parent_id'] ?? 0) ?: null;
        $message  = trim($_POST['message'] ?? '');
        $userId   = (int)$_SESSION['user']['id'];

        if (!$lessonId || !$message) {
            $this->redirect("student/discussions&lesson_id={$lessonId}&error=empty"); return;
        }

        $m = $this->studentModel();
        $m->postDiscussion($userId, $lessonId, $parentId, $message);

        $this->redirect("student/lesson&id={$lessonId}&success=posted#discussions");
    }

    /* ================================================================
       MARK NOTIFICATIONS READ  (POST/AJAX-friendly)
    ================================================================*/
    public function markRead(): void
    {
        $this->requireRole('student');
        $m      = $this->studentModel();
        $userId = (int)$_SESSION['user']['id'];
        $m->markAllNotificationsRead($userId);
        $this->redirect('student/dashboard&msg=notifications_cleared');
    }

    /* ================================================================
       PROFILE
    ================================================================*/
    public function editProfile(): void
    {
        $this->requireRole('student');
        $profile = $this->profile();
        $this->view('student/profile', compact('profile'));
    }

    public function updateProfile(): void
    {
        $this->requireRole('student');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $m      = $this->studentModel();
            $userId = (int)$_SESSION['user']['id'];
            
            $data = [
                'fullname'      => trim($_POST['fullname'] ?? ''),
                'email'         => trim($_POST['email'] ?? ''),
                'gender'        => $_POST['gender'] ?? 'Other',
                'date_of_birth' => $_POST['date_of_birth'] ?? null
            ];

            if ($m->updateProfile($userId, $data)) {
                $this->redirect('student/editProfile&success=updated');
            } else {
                $this->redirect('student/editProfile&error=failed');
            }
        }
    }
}
