<?php

class ParentController extends Controller {

    /* ── Helpers ────────────────────────────────────────────── */
    private function parentModel(): ParentModel
    {
        return $this->model('ParentModel');
    }

    private function profile(): array
    {
        $userId  = (int)$_SESSION['user']['id'];
        $m       = $this->parentModel();
        $profile = $m->getProfile($userId);

        if (!$profile) {
            $this->redirect('auth/login');
            exit;
        }

        // Parent must have an array of children to view child-specific content
        if (!isset($profile['children'])) {
            $profile['children'] = [];
        }

        return $profile;
    }

    /* ================================================================
       DASHBOARD
    ================================================================*/
    public function dashboard(): void
    {
        $this->requireRole('parent');
        $m       = $this->parentModel();
        $profile = $this->profile();
        $userId  = (int)$_SESSION['user']['id'];

        $notices = $m->getNotifications($userId, 5);
        $schoolAnnouncements = $m->getAdminAnnouncements($userId, 'parent');
        $studentIds = array_column($profile['children'], 'student_id');
        $activities = $m->getRecentActivities($studentIds, 10);
        $upcoming   = $m->getUpcomingAssessments($studentIds, 5);

        // Fetch high-level stats for each child
        foreach ($profile['children'] as &$child) {
            $stats = $m->getChildOverview($child['student_id']);
            $child['mastered_competencies'] = $stats['mastered_competencies'];
            $child['average_score']         = $stats['average_score'];
        }

        $this->view('parent/dashboard', compact('profile', 'notices', 'schoolAnnouncements', 'activities', 'upcoming'));
    }

    public function announcements(): void
    {
        $this->requireRole('parent');
        $m = $this->model('ParentModel');
        $userId = (int)$_SESSION['user']['id'];
        $profile = $m->getProfile($userId);
        
        $announcements = $m->getAdminAnnouncements($userId, 'parent', 50);
        
        $this->view('parent/announcements', [
            'profile' => $profile,
            'activeNav' => 'school_announcements',
            'announcements' => $announcements
        ]);
    }

    /* ================================================================
       CHILD PROGRESS (Charts)
    ================================================================*/
    public function progress(int $studentId = 0): void
    {
        $this->requireRole('parent');
        $m       = $this->parentModel();
        $profile = $this->profile();

        $studentId = $studentId ?: (int)($_GET['child_id'] ?? 0);
        
        // If no student specified and parent has children, default to the first one
        if (!$studentId && !empty($profile['children'])) {
            $studentId = (int)$profile['children'][0]['student_id'];
        }

        // Validate that this student belongs to this parent
        $validChild = false;
        $childName = '';
        foreach ($profile['children'] as $child) {
            if ((int)$child['student_id'] === $studentId) {
                $validChild = true;
                $childName = $child['student_name'];
                break;
            }
        }

        if (!$validChild && $studentId > 0) {
            $this->redirect('parent/dashboard&error=unauthorized_child');
            return;
        }

        $progressData = [];
        if ($validChild) {
            $progressData = $m->getChildProgress($studentId);
        }

        $this->view('parent/progress', compact('profile', 'studentId', 'childName', 'progressData'));
    }

    /* ================================================================
       ASSESSMENTS & QUIZ RESULTS
    ================================================================*/
    public function assessments(int $studentId = 0): void
    {
        $this->requireRole('parent');
        $m       = $this->parentModel();
        $profile = $this->profile();

        $studentId = $studentId ?: (int)($_GET['child_id'] ?? 0);
        
        if (!$studentId && !empty($profile['children'])) {
            $studentId = (int)$profile['children'][0]['student_id'];
        }

        // Validate
        $validChild = false;
        $childName = '';
        foreach ($profile['children'] as $child) {
            if ((int)$child['student_id'] === $studentId) {
                $validChild = true;
                $childName = $child['student_name'];
                break;
            }
        }

        $assessments = [];
        if ($validChild) {
            $assessments = $m->getChildAssessments($studentId);
        }

        $this->view('parent/assessments', compact('profile', 'studentId', 'childName', 'assessments'));
    }

    /* ================================================================
       MESSAGES (Inbox / Chat)
    ================================================================*/
    public function messages(): void
    {
        $this->requireRole('parent');
        $m         = $this->parentModel();
        $profile   = $this->profile();
        $parentId  = (int)$profile['parent_id'];
        
        $teacherId = (int)($_GET['teacher_id'] ?? 0);
        $teachers  = $m->getMessages($parentId);

        // Mark messages as read if we're opening a specific thread
        if ($teacherId > 0) {
            $m->markMessagesRead($parentId, $teacherId);
            // Refresh unread counts after marking
            $teachers = $m->getMessages($parentId);
        }

        $this->view('parent/messages', compact('profile', 'teachers', 'teacherId'));
    }

    /* ================================================================
       SEND MESSAGE (POST)
    ================================================================*/
    public function sendMessage(): void
    {
        $this->requireRole('parent');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('parent/messages');
            return;
        }

        $tId  = (int)($_POST['teacher_id'] ?? 0);
        $sId  = (int)($_POST['student_id'] ?? 0);
        $msg  = trim($_POST['message'] ?? '');
        $m    = $this->parentModel();
        $p    = $this->profile();

        if ($tId && $sId && $msg) {
            $m->sendMessage((int)$p['parent_id'], $tId, $sId, $msg);
            $this->redirect("parent/messages&teacher_id={$tId}&success=sent");
        } else {
            $this->redirect("parent/messages&teacher_id={$tId}&error=empty");
        }
    }

    /* ================================================================
       MARK NOTIFICATIONS READ
    ================================================================*/
    public function markRead(): void
    {
        $this->requireRole('parent');
        $userId = (int)$_SESSION['user']['id'];
        $this->parentModel()->markAllNotificationsRead($userId);
        
        $this->redirect('parent/dashboard&msg=notifications_cleared');
    }

    /* ================================================================
       PRINTABLE PROGRESS REPORT
    ================================================================*/
    public function report(int $studentId = 0): void
    {
        $this->requireRole('parent');
        $m       = $this->parentModel();
        $profile = $this->profile();

        $studentId = $studentId ?: (int)($_GET['child_id'] ?? 0);
        
        // Validate
        $validChild = false;
        $child = null;
        foreach ($profile['children'] as $c) {
            if ((int)$c['student_id'] === $studentId) {
                $validChild = true;
                $child = $c;
                break;
            }
        }

        if (!$validChild) {
            $this->redirect('parent/dashboard&error=unauthorized_child');
            return;
        }

        $progressData = $m->getChildProgress($studentId);
        $assessments  = $m->getChildAssessments($studentId);

        // This view will be barebones (no sidebar) for clean printing
        $this->view('parent/report', compact('profile', 'child', 'progressData', 'assessments'));
    }
}
