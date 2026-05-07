<?php

class Controller {

    /**
     * Load and instantiate a model.
     */
    public function model(string $model)
    {
        $file = BASE_PATH . "/app/models/{$model}.php";
        if (!file_exists($file)) {
            throw new RuntimeException("Model file not found: $model");
        }
        require_once $file;
        return new $model();
    }

    /**
     * Render a view, extracting $data variables into scope.
     */
    public function view(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $file = BASE_PATH . "/app/views/{$view}.php";
        if (!file_exists($file)) {
            throw new RuntimeException("View file not found: $view");
        }
        require $file;
    }

    /**
     * Redirect helper.
     */
    protected function redirect(string $path): void
    {
        header("Location: /CBE_LMS/public/index.php?url={$path}");
        exit;
    }

    /**
     * Require the user to be logged in; redirect to login otherwise.
     */
    protected function requireLogin(string $loginUrl = 'auth/login'): void
    {
        if (empty($_SESSION['user'])) {
            $this->redirect($loginUrl);
        }
    }

    /**
     * Require a specific role; redirect to login if mismatch.
     */
    protected function requireRole(string $role): void
    {
        $this->requireLogin();
        if ($_SESSION['user']['role'] !== $role) {
            $this->redirect('auth/login');
        }
    }

    /**
     * Map a percentage to Kenyan CBC performance level
     */
    public static function getCbcPerformanceLevel(int $percentage): string
    {
        if ($percentage >= 76) return 'Exceeding Expectations';
        if ($percentage >= 51) return 'Meeting Expectations';
        if ($percentage >= 26) return 'Approaching Expectations';
        return 'Below Expectations';
    }
}
