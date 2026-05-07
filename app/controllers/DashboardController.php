<?php

class DashboardController extends Controller {

    /**
     * Generic dashboard entry – redirects to the role-specific dashboard.
     */
    public function index(): void
    {
        $this->requireLogin();
        $user = $_SESSION['user'];

        // Force password change check
        if (!empty($user['must_change_password'])) {
            $this->redirect('auth/changePassword');
        }

        $this->redirect($user['role'] . '/dashboard');
    }
}
