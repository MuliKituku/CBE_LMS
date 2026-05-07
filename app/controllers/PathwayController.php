<?php
class PathwayController extends Controller {

    public function index(): void
    {
        $this->requireRole('student');
        
        $m = $this->model('Student');
        $profile = $m->getProfile((int)$_SESSION['user']['id']);
        if (!$profile) { $this->redirect('auth/login'); }

        $studentId = $profile['student_id'];
        $pathwayModel = $this->model('PathwayModel');

        $hasCompleted = $pathwayModel->hasCompletedSurvey($studentId);
        $recommendation = null;
        $questions = [];

        if ($hasCompleted) {
            $recommendation = $pathwayModel->getStudentRecommendation($studentId);
        } else {
            $questions = $pathwayModel->getSurveyQuestions();
        }

        $this->view('student/pathway', [
            'profile' => $profile,
            'hasCompleted' => $hasCompleted,
            'recommendation' => $recommendation,
            'questions' => $questions
        ]);
    }

    public function submitSurvey(): void
    {
        $this->requireRole('student');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('student/pathway'); return;
        }

        $m = $this->model('Student');
        $profile = $m->getProfile((int)$_SESSION['user']['id']);
        $studentId = $profile['student_id'];
        
        $responses = $_POST['responses'] ?? [];
        if (empty($responses)) {
            $this->redirect('pathway/index&error=empty'); return;
        }

        $pathwayModel = $this->model('PathwayModel');
        $pathwayModel->saveSurveyResponses($studentId, $responses);

        $this->redirect('pathway/index&success=1');
    }
}
