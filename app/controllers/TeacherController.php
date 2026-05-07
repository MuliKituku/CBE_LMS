<?php

class TeacherController extends Controller {

    private TeacherModel $teacherModel;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // Ensure user is logged in as teacher
        $this->requireRole('teacher');
        $this->teacherModel = $this->model('TeacherModel');
    }

    /* ============================================================
       DASHBOARD
    ============================================================*/
    public function dashboard()
    {
        $userId = $_SESSION['user']['id'];
        $profile = $this->teacherModel->getProfile($userId);
        
        if (!$profile) {
            $this->redirect('auth/logout');
        }

        $stats = $this->teacherModel->getDashboardStats($userId);
        $classes = $this->teacherModel->getClasses($userId);
        $pending = $this->teacherModel->getPendingSubmissions($userId);
        
        // New dashboard data
        $overallProgress = $this->teacherModel->getOverallClassProgress($userId);
        $recentActivity  = $this->teacherModel->getRecentActivity($userId);

        // Fetch user notifications
        $db   = (new Database())->connect();
        $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$userId]);
        $notifications = $stmt->fetchAll();

        // Admin announcements
        $schoolAnnouncements = $this->teacherModel->getAdminAnnouncements($userId, 'teacher');

        // Get unread messages count
        $stmt = $db->prepare("SELECT COUNT(*) FROM parent_teacher_messages WHERE teacher_id = ? AND sender_type = 'parent' AND is_read = 0");
        $stmt->execute([$userId]);
        $unreadMessages = (int)$stmt->fetchColumn();

        $this->view('teacher/dashboard', [
            'profile' => $profile,
            'stats' => $stats,
            'classes' => $classes,
            'pending' => $pending,
            'notifications' => $notifications,
            'schoolAnnouncements' => $schoolAnnouncements,
            'unreadMessages' => $unreadMessages,
            'overallProgress' => $overallProgress,
            'recentActivity' => $recentActivity
        ]);
    }

    /* ============================================================
       LESSONS
    ============================================================*/
    public function lessons()
    {
        $userId = $_SESSION['user']['id'];
        $profile = $this->teacherModel->getProfile($userId);
        $lessons = $this->teacherModel->getLessons($userId);
        $coreCompetencesList = $this->teacherModel->getCoreCompetencesList();

        $this->view('teacher/lessons', [
            'profile'             => $profile,
            'lessons'             => $lessons,
            'coreCompetencesList' => $coreCompetencesList
        ]);
    }

    public function createLesson()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $contentUrl = $_POST['content_url'] ?? '';

            // Handle File Upload if present
            if (!empty($_FILES['lesson_file']['name'])) {
                $targetDir = BASE_PATH . "/public/uploads/lessons/";
                if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
                $fileName = time() . '_' . basename($_FILES['lesson_file']['name']);
                $targetFile = $targetDir . $fileName;
                if (move_uploaded_file($_FILES['lesson_file']['tmp_name'], $targetFile)) {
                    $contentUrl = BASE_URL . "/public/uploads/lessons/" . $fileName;
                }
            }

            $grade = trim($_POST['class_grade'] ?? '');

            // Resolve education_level from grade
            $educationLevel = $this->resolveEducationLevel($grade);

            $data = [
                'strand'             => trim($_POST['strand'] ?? ''),
                'sub_strand'         => trim($_POST['sub_strand'] ?? ''),
                'subject'            => trim($_POST['subject'] ?? ''),
                'type'               => trim($_POST['type'] ?? 'video'),
                'content_url'        => $contentUrl,
                'description'        => trim($_POST['description'] ?? ''),
                'introduction'       => trim($_POST['introduction'] ?? ''),
                'content_delivery'   => trim($_POST['content_delivery'] ?? ''),
                'summary'            => trim($_POST['summary'] ?? ''),
                'assignment'         => trim($_POST['assignment'] ?? ''),
                'core_competences'   => $_POST['core_competences'] ?? [],
                'class_grade'        => $grade,
                'education_level'    => $educationLevel,
                'learning_outcomes'  => trim($_POST['learning_outcomes'] ?? ''),
                'interaction_method' => trim($_POST['interaction_method'] ?? 'mcq'),
                'activity_type'      => trim($_POST['activity_type'] ?? 'none'),
                'teacher_id'         => $_SESSION['user']['id']
            ];

            if ($data['strand'] && $data['subject'] && $data['class_grade']) {
                $lessonId = $this->teacherModel->createLesson($data);

                // Save interactions (if any)
                $interactions = $this->parseInteractionsFromPost();
                if (!empty($interactions)) {
                    $this->teacherModel->saveInteractions($lessonId, $interactions);
                }

                // Save activity
                $activity = [
                    'title'           => trim($_POST['activity_title'] ?? 'Activity Task'),
                    'description'     => trim($_POST['activity_description'] ?? ''),
                    'submission_type' => trim($_POST['activity_submission_type'] ?? 'text'),
                    'max_marks'       => (int)($_POST['activity_max_marks'] ?? 10)
                ];
                $this->teacherModel->saveActivity($lessonId, $activity);

                $this->redirect('teacher/lessons&success=created');
            } else {
                $this->redirect('teacher/lessons&error=missing_fields');
            }
        }
    }

    public function updateLesson()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $lessonId   = (int)$_POST['id'];
            $contentUrl = $_POST['content_url'] ?? '';
            $oldUrl = '';

            $existing = $this->teacherModel->getLessonById($lessonId, $_SESSION['user']['id']);
            if ($existing) { $oldUrl = $existing['content_url']; }

            if (!empty($_FILES['lesson_file']['name'])) {
                $targetDir = BASE_PATH . "/public/uploads/lessons/";
                if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
                $fileName = time() . '_' . basename($_FILES['lesson_file']['name']);
                if (move_uploaded_file($_FILES['lesson_file']['tmp_name'], $targetDir . $fileName)) {
                    $contentUrl = BASE_URL . "/public/uploads/lessons/" . $fileName;
                    if ($oldUrl && stripos($oldUrl, '/public/uploads/lessons/') !== false) {
                        $oldFilePath = BASE_PATH . str_replace(BASE_URL, '', $oldUrl);
                        if (file_exists($oldFilePath)) @unlink($oldFilePath);
                    }
                }
            } else {
                if (empty($contentUrl) && $existing) { $contentUrl = $existing['content_url']; }
                if ($oldUrl && $contentUrl !== $oldUrl && stripos($oldUrl, '/public/uploads/lessons/') !== false) {
                    $oldFilePath = BASE_PATH . str_replace(BASE_URL, '', $oldUrl);
                    if (file_exists($oldFilePath)) @unlink($oldFilePath);
                }
            }

            $grade = trim($_POST['class_grade'] ?? '');
            $educationLevel = $this->resolveEducationLevel($grade);

            $data = [
                'strand'             => trim($_POST['strand'] ?? ''),
                'sub_strand'         => trim($_POST['sub_strand'] ?? ''),
                'subject'            => trim($_POST['subject'] ?? ''),
                'type'               => trim($_POST['type'] ?? 'video'),
                'content_url'        => $contentUrl,
                'description'        => trim($_POST['description'] ?? ''),
                'introduction'       => trim($_POST['introduction'] ?? ''),
                'content_delivery'   => trim($_POST['content_delivery'] ?? ''),
                'summary'            => trim($_POST['summary'] ?? ''),
                'assignment'         => trim($_POST['assignment'] ?? ''),
                'core_competences'   => $_POST['core_competences'] ?? [],
                'class_grade'        => $grade,
                'education_level'    => $educationLevel,
                'learning_outcomes'  => trim($_POST['learning_outcomes'] ?? ''),
                'interaction_method' => trim($_POST['interaction_method'] ?? 'mcq'),
                'activity_type'      => trim($_POST['activity_type'] ?? 'none'),
                'teacher_id'         => $_SESSION['user']['id']
            ];

            $this->teacherModel->updateLesson($lessonId, $data);

            // Save interactions
            $interactions = $this->parseInteractionsFromPost();
            $this->teacherModel->saveInteractions($lessonId, $interactions);

            // Save activity
            $activity = [
                'title'           => trim($_POST['activity_title'] ?? 'Activity Task'),
                'description'     => trim($_POST['activity_description'] ?? ''),
                'submission_type' => trim($_POST['activity_submission_type'] ?? 'text'),
                'max_marks'       => (int)($_POST['activity_max_marks'] ?? 10)
            ];
            $this->teacherModel->saveActivity($lessonId, $activity);

            $this->redirect('teacher/lessons&success=updated');
        }
    }

    /* ── helper: parse dynamic interaction rows from POST ─── */
    private function parseInteractionsFromPost(): array
    {
        $interactions = [];
        $questions = $_POST['interaction_question'] ?? [];
        $types     = $_POST['interaction_type']     ?? [];
        $corrects  = $_POST['interaction_correct']  ?? [];
        $marks     = $_POST['interaction_marks']    ?? [];
        $hints     = $_POST['interaction_hint']     ?? [];
        $optA = $_POST['interaction_option_a'] ?? [];
        $optB = $_POST['interaction_option_b'] ?? [];
        $optC = $_POST['interaction_option_c'] ?? [];
        $optD = $_POST['interaction_option_d'] ?? [];

        $competencies = $_POST['interaction_competency'] ?? [];

        foreach ($questions as $i => $q) {
            if (empty(trim($q))) continue;
            $options = [];
            if (!empty($optA[$i])) $options[] = ['id' => 'a', 'text' => $optA[$i]];
            if (!empty($optB[$i])) $options[] = ['id' => 'b', 'text' => $optB[$i]];
            if (!empty($optC[$i])) $options[] = ['id' => 'c', 'text' => $optC[$i]];
            if (!empty($optD[$i])) $options[] = ['id' => 'd', 'text' => $optD[$i]];

            $interactions[] = [
                'interaction_type' => $types[$i]   ?? 'mcq',
                'competency_id'    => !empty($competencies[$i]) ? (int)$competencies[$i] : null,
                'question'         => trim($q),
                'options'          => $options,
                'correct_answer'   => trim($corrects[$i] ?? ''),
                'marks'            => (int)($marks[$i] ?? 1),
                'hint'             => trim($hints[$i] ?? ''),
            ];
        }
        return $interactions;
    }

    /* ── helper: derive education_level from grade string ─── */
    private function resolveEducationLevel(string $grade): string
    {
        $grade = strtolower(trim($grade));
        if (in_array($grade, ['pp1', 'pp2'])) return 'pre_primary';
        if (preg_match('/grade\s*([1-6])$/', $grade)) return 'primary';
        if (preg_match('/grade\s*([7-9])$/', $grade)) return 'junior';
        if (preg_match('/grade\s*(1[0-2])$/', $grade)) return 'senior';
        return 'primary';
    }

    public function deleteLesson(int $id)
    {
        // Cleanup file before deleting record
        $existing = $this->teacherModel->getLessonById($id, $_SESSION['user']['id']);
        if ($existing && $existing['content_url'] && stripos($existing['content_url'], '/public/uploads/lessons/') !== false) {
            $filePath = BASE_PATH . str_replace(BASE_URL, '', $existing['content_url']);
            if (file_exists($filePath)) @unlink($filePath);
        }

        $this->teacherModel->deleteLesson($id, $_SESSION['user']['id']);
        $this->redirect('teacher/lessons&success=deleted');
    }

    /* ============================================================
       ASSESSMENTS & GRADING
    ============================================================*/
    public function assessments()
    {
        $userId = $_SESSION['user']['id'];
        $profile = $this->teacherModel->getProfile($userId);
        $assessments = $this->teacherModel->getAssessments($userId);
        
        // Need lessons to attach assessments to
        $lessons = $this->teacherModel->getLessons($userId);
        $coreCompetencesList = $this->teacherModel->getCoreCompetencesList();
        
        $this->view('teacher/assessments', [
            'profile' => $profile,
            'assessments' => $assessments,
            'lessons' => $lessons,
            'coreCompetencesList' => $coreCompetencesList
        ]);
    }

    public function createAssessment()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $grade = trim($_POST['class_grade'] ?? '');
            if (is_numeric($grade)) {
                $grade = 'Grade ' . $grade;
            } elseif ($grade !== '' && stripos($grade, 'Grade') !== 0) {
                 $grade = 'Grade ' . $grade;
            }

            $data = [
                'lesson_id' => !empty($_POST['lesson_id']) ? (int)$_POST['lesson_id'] : null,
                'title' => trim($_POST['title'] ?? ''),
                'instructions' => trim($_POST['instructions'] ?? ''),
                'class_grade' => $grade,
                'subject' => trim($_POST['subject'] ?? ''),
                'due_date' => !empty($_POST['due_date']) ? $_POST['due_date'] : null,
                'available_from' => trim($_POST['available_from'] ?? ''),
                'available_until' => trim($_POST['available_until'] ?? ''),
                'duration_minutes' => (int)($_POST['duration_minutes'] ?? 60),
                'max_attempts' => (int)($_POST['max_attempts'] ?? 1),
                'teacher_id' => $_SESSION['user']['id'],
                'core_competences' => $_POST['core_competences'] ?? []
            ];
            
            if ($data['title'] && $data['available_from'] && $data['available_until']) {
                $this->teacherModel->createAssessment($data);
                $this->redirect('teacher/assessments&success=created');
            } else {
                $this->redirect('teacher/assessments&error=missing_fields');
            }
        }
    }

    public function deleteAssessment(int $id)
    {
        $this->teacherModel->deleteAssessment($id, $_SESSION['user']['id']);
        $this->redirect('teacher/assessments?success=deleted');
    }

    public function editAssessment(int $id)
    {
        $userId = $_SESSION['user']['id'];
        $profile = $this->teacherModel->getProfile($userId);
        $assessment = $this->teacherModel->getAssessmentById($id, $userId);
        if (!$assessment) { $this->redirect('teacher/assessments'); }

        $lessons = $this->teacherModel->getLessons($userId);
        $coreCompetencesList = $this->teacherModel->getCoreCompetencesList();

        $this->view('teacher/edit_assessment', [
            'profile' => $profile,
            'assessment' => $assessment,
            'lessons' => $lessons,
            'coreCompetencesList' => $coreCompetencesList
        ]);
    }

    public function updateAssessment()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)$_POST['id'];
            $grade = trim($_POST['class_grade'] ?? '');
            if (is_numeric($grade)) { $grade = 'Grade ' . $grade; }
            elseif ($grade !== '' && stripos($grade, 'Grade') !== 0) { $grade = 'Grade ' . $grade; }

            $data = [
                'lesson_id' => !empty($_POST['lesson_id']) ? (int)$_POST['lesson_id'] : null,
                'title' => trim($_POST['title'] ?? ''),
                'instructions' => trim($_POST['instructions'] ?? ''),
                'class_grade' => $grade,
                'subject' => trim($_POST['subject'] ?? ''),
                'due_date' => !empty($_POST['due_date']) ? $_POST['due_date'] : null,
                'available_from' => trim($_POST['available_from'] ?? ''),
                'available_until' => trim($_POST['available_until'] ?? ''),
                'duration_minutes' => (int)($_POST['duration_minutes'] ?? 60),
                'max_attempts' => (int)($_POST['max_attempts'] ?? 1),
                'core_competences' => $_POST['core_competences'] ?? []
            ];

            if ($data['title'] && $data['available_from'] && $data['available_until']) {
                $this->teacherModel->updateAssessmentInfo($id, $_SESSION['user']['id'], $data);
                $this->redirect('teacher/assessments&success=updated');
            } else {
                $this->redirect("teacher/editAssessment&id=$id&error=missing_fields");
            }
        }
    }

    public function buildAssessment(int $id)
    {
        $userId = $_SESSION['user']['id'];
        $profile = $this->teacherModel->getProfile($userId);
        $assessment = $this->teacherModel->getAssessmentById($id, $userId);
        if (!$assessment) { $this->redirect('teacher/assessments'); }

        $interactions = $this->teacherModel->getAssessmentInteractions($id);

        $this->view('teacher/assessment_builder', [
            'profile'      => $profile,
            'assessment'   => $assessment,
            'interactions' => $interactions
        ]);
    }

    public function saveAssessmentInteractions()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)$_POST['id'];
            $interactions = $this->parseInteractionsFromPost();
            $this->teacherModel->saveAssessmentInteractions($id, $interactions);
            $this->redirect('teacher/assessments?success=interactions_saved');
        }
    }

    public function grading()
    {
        $userId = $_SESSION['user']['id'];
        $profile = $this->teacherModel->getProfile($userId);
        $pending = $this->teacherModel->getPendingSubmissions($userId);
        $pendingActivities = $this->teacherModel->getPendingLessonActivities($userId);
        
        $this->view('teacher/grading', [
            'profile' => $profile,
            'pending' => $pending,
            'pendingActivities' => $pendingActivities
        ]);
    }

    public function reviewLessonSubmission(int $studentId = 0, int $lessonId = 0)
    {
        $userId = $_SESSION['user']['id'];
        $profile = $this->teacherModel->getProfile($userId);

        $studentId = $studentId ?: (int)($_GET['student_id'] ?? 0);
        $lessonId  = $lessonId  ?: (int)($_GET['lesson_id']  ?? 0);

        if (!$studentId || !$lessonId) {
            $this->redirect('teacher/grading');
        }

        $submissionData = $this->teacherModel->getLessonSubmissionDetail($studentId, $lessonId);
        
        $this->view('teacher/review_lesson', [
            'profile'        => $profile,
            'studentId'      => $studentId, // for form
            'lessonId'       => $lessonId,  // for form
            'submissionData' => $submissionData
        ]);
    }

    public function submitLessonGrade()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $studentId = (int)$_POST['student_id'];
            $lessonId  = (int)$_POST['lesson_id'];
            $actScore  = (float)($_POST['activity_score'] ?? 0);
            $feedback  = trim($_POST['feedback'] ?? '');
            $intScore  = isset($_POST['interaction_score']) ? (int)$_POST['interaction_score'] : null;

            // 1. Grade the activity
            $this->teacherModel->gradeLessonActivity($studentId, $lessonId, $actScore, $feedback);

            // 2. Override interaction score if provided
            if ($intScore !== null) {
                $this->teacherModel->updateLessonInteractionScore($studentId, $lessonId, $intScore);
            }

            $this->redirect('teacher/grading?success=graded');
        }
    }

    public function submitGrade()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $studentId = (int)$_POST['student_id'];
            $assessmentId = (int)$_POST['assessment_id'];
            $score = (int)$_POST['score'];
            $maxScore = (int)$_POST['max_score'];
            
            if ($maxScore > 0) {
                $percentage = (int)ROUND(($score / $maxScore) * 100);
            } else {
                $percentage = 100;
            }

            $this->teacherModel->gradeSubmission($studentId, $assessmentId, $score, $percentage);
            
            // Add optional comment as feedback
            if (!empty($_POST['feedback'])) {
                $this->teacherModel->addFeedback($studentId, $_SESSION['user']['id'], trim($_POST['feedback']));
            }
            
            $this->redirect('teacher/grading?success=graded');
        }
    }

    public function reviewSubmission(int $studentId = 0, int $assessmentId = 0)
    {
        $userId = $_SESSION['user']['id'];
        $profile = $this->teacherModel->getProfile($userId);

        $studentId    = $studentId    ?: (int)($_GET['student_id']    ?? 0);
        $assessmentId = $assessmentId ?: (int)($_GET['assessment_id'] ?? 0);

        if (!$studentId || !$assessmentId) {
            $this->redirect('teacher/grading');
        }

        $submissionData = $this->teacherModel->getPendingSubmissionDetail($studentId, $assessmentId);
        // Pass student_id into assessment so the view can embed it in the form
        $submissionData['assessment']['student_id_param'] = $studentId;

        $this->view('teacher/review_submission', [
            'profile'        => $profile,
            'submissionData' => $submissionData
        ]);
    }

    public function saveReviewGrades()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $studentId    = (int)$_POST['student_id'];
            $assessmentId = (int)$_POST['assessment_id'];
            $scores       = $_POST['scores']   ?? [];
            $comments     = $_POST['comments'] ?? [];

            foreach ($scores as $interactionId => $score) {
                $this->teacherModel->saveInteractionGrade(
                    $studentId,
                    $assessmentId,
                    (int)$interactionId,
                    (float)$score,
                    trim($comments[$interactionId] ?? '')
                );
            }

            // Recalculate total and cascade CBE
            $this->teacherModel->finaliseManualGrade($studentId, $assessmentId);

            $this->redirect('teacher/grading?success=graded');
        }
    }

    /* ============================================================
       STUDENTS & PROGRESS
    ============================================================*/
    public function students()
    {
        $userId = $_SESSION['user']['id'];
        $profile = $this->teacherModel->getProfile($userId);
        
        $filter = $_GET['filter'] ?? 'all';
        $students = $this->teacherModel->getFilteredStudents($userId, $filter);
        
        $this->view('teacher/students', [
            'profile' => $profile,
            'students' => $students,
            'currentFilter' => $filter
        ]);
    }

    public function studentProgress(int $studentId)
    {
        $userId = $_SESSION['user']['id'];
        $profile = $this->teacherModel->getProfile($userId);
        $student = $this->teacherModel->getStudentProgress($studentId, $userId);
        
        if (empty($student)) {
            $this->redirect('teacher/students?error=not_found');
        }

        $this->view('teacher/student_progress', [
            'profile' => $profile,
            'student' => $student
        ]);
    }

    public function updateCompetency()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $studentId = (int)$_POST['student_id'];
            $competencyId = (int)$_POST['competency_id'];
            $status = $_POST['status'];
            $score = is_numeric($_POST['score']) ? (float)$_POST['score'] : null;
            
            $this->teacherModel->updateStudentCompetency($studentId, $competencyId, $status, $score);
            $this->redirect('teacher/studentProgress/' . $studentId . '?success=competency_updated');
        }
    }

    public function addFeedback()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $studentId = (int)$_POST['student_id'];
            $message = trim($_POST['message'] ?? '');
            
            if ($message) {
                $this->teacherModel->addFeedback($studentId, $_SESSION['user']['id'], $message);
                $this->redirect('teacher/studentProgress/' . $studentId . '?success=feedback_added');
            } else {
                $this->redirect('teacher/studentProgress/' . $studentId . '?error=empty_message');
            }
        }
    }

    /* ============================================================
       MESSAGES
    ============================================================*/
    public function messages()
    {
        $userId = $_SESSION['user']['id'];
        $profile = $this->teacherModel->getProfile($userId);
        $parents = $this->teacherModel->getMessages($userId);
        
        $parentId = isset($_GET['parent_id']) ? (int)$_GET['parent_id'] : null;
        
        // Mark as read if a parent thread is open
        if ($parentId) {
            $this->teacherModel->markMessagesRead($userId, $parentId);
        }

        $this->view('teacher/messages', [
            'profile' => $profile,
            'parents' => $parents,
            'parentId' => $parentId
        ]);
    }

    public function sendMessage()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $parentUserId = (int)$_POST['parent_userId'];
            $studentId = (int)$_POST['student_id'];
            $message = trim($_POST['message'] ?? '');
            
            if ($parentUserId && $message) {
                $this->teacherModel->sendMessage($_SESSION['user']['id'], $parentUserId, $studentId, $message);
                $this->redirect('teacher/messages&parent_id=' . $parentUserId . '&success=sent');
            } else {
                $this->redirect('teacher/messages&parent_id=' . $parentUserId . '&error=empty');
            }
        }
    }

    /* ============================================================
       ANNOUNCEMENTS
    ============================================================*/
    public function announcements()
    {
        $userId = $_SESSION['user']['id'];
        $profile = $this->teacherModel->getProfile($userId);
        $announcements = $this->teacherModel->getAnnouncements($userId);
        $classes = $this->teacherModel->getClasses($userId);
        
        $this->view('teacher/announcements', [
            'profile' => $profile,
            'announcements' => $announcements,
            'classes' => $classes
        ]);
    }

    public function createAnnouncement()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $grade = $_POST['class_grade'] ?? '';
            $subject = $_POST['subject'] ?? '';
            $title = trim($_POST['title'] ?? '');
            $message = trim($_POST['message'] ?? '');
            
            if ($grade && $title && $message) {
                $this->teacherModel->broadcastAnnouncement($_SESSION['user']['id'], $grade, $subject, $title, $message);
                $this->redirect('teacher/announcements&success=created');
            } else {
                $this->redirect('teacher/announcements&error=missing_fields');
            }
        }
    }

    public function deleteAnnouncement(int $id)
    {
        $this->teacherModel->deleteAnnouncement($id, $_SESSION['user']['id']);
        $this->redirect('teacher/announcements&success=deleted');
    }

    public function schoolAnnouncements(): void
    {
        $this->requireRole('teacher');
        $userId = (int)$_SESSION['user']['id'];
        $profile = $this->teacherModel->getProfile($userId);
        
        $announcements = $this->teacherModel->getAdminAnnouncements($userId, 'teacher', 50);
        
        $this->view('teacher/school_announcements', [
            'profile' => $profile,
            'activeNav' => 'school_announcements',
            'announcements' => $announcements
        ]);
    }

    /* ============================================================
       REPORTS
    ============================================================*/
    public function viewDiscussions(int $lessonId)
    {
        $userId = $_SESSION['user']['id'];
        $profile = $this->teacherModel->getProfile($userId);
        $lesson = $this->teacherModel->getLessonById($lessonId, $userId);

        if (!$lesson) {
            $this->redirect('teacher/lessons');
        }

        $discussions = $this->teacherModel->getDiscussions($lessonId);

        $this->view('teacher/view_discussions', [
            'profile'     => $profile,
            'lesson'      => $lesson,
            'discussions' => $discussions
        ]);
    }

    public function postDiscussion()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $lessonId = (int)$_POST['lesson_id'];
            $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
            $message  = trim($_POST['message'] ?? '');

            if ($lessonId && $message) {
                // Ensure teacher has access to this lesson
                $lesson = $this->teacherModel->getLessonById($lessonId, $_SESSION['user']['id']);
                if ($lesson) {
                    $this->teacherModel->postDiscussion($_SESSION['user']['id'], $lessonId, $parentId, $message);
                    $this->redirect("teacher/viewDiscussions/$lessonId&success=posted");
                    return;
                }
            }
            $this->redirect('teacher/lessons');
        }
    }

    /* ============================================================
       REPORTS
    ============================================================*/
    public function reports()
    {
        $userId = $_SESSION['user']['id'];
        $profile = $this->teacherModel->getProfile($userId);
        $classes = $this->teacherModel->getClasses($userId);
        $students = $this->teacherModel->getStudents($userId);
        
        $this->view('teacher/reports', [
            'profile' => $profile,
            'classes' => $classes,
            'students' => $students
        ]);
    }

    public function viewLessonFeedback(int $id): void
    {
        $this->requireRole('teacher');
        $lesson = $this->teacherModel->getLessonById($id, $_SESSION['user']['id']);
        if (!$lesson) { $this->redirect('teacher/lessons'); return; }

        $feedback = $this->teacherModel->getLessonFeedback($id);
        $profile = $this->teacherModel->getProfile($_SESSION['user']['id']);

        $this->view('teacher/view_lesson_feedback', [
            'profile' => $profile,
            'lesson'  => $lesson,
            'feedback'=> $feedback
        ]);
    }

    public function postLessonFeedback(): void
    {
        $this->requireRole('teacher');
        $lessonId = (int)($_POST['lesson_id'] ?? 0);
        $message  = trim($_POST['message'] ?? '');
        
        if ($lessonId && $message) {
            $this->teacherModel->addLessonFeedback($lessonId, $_SESSION['user']['id'], $message);
            $this->redirect("teacher/viewLessonFeedback/$lessonId&success=feedback_posted");
        } else {
            $this->redirect("teacher/lessons&error=empty_message");
        }
    }
}
