<?php

class AuthController extends Controller {

    /* ================================================================
       SHOW PAGES
    ================================================================*/
    public function login(): void    { $this->view('auth/login'); }
    public function login_at(): void { $this->view('auth/login_at'); }
    public function enroll(): void   { $this->view('auth/enroll'); }

    /* ================================================================
       STUDENT / PARENT LOGIN
    ================================================================*/
    public function authenticate(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('auth/login'); return;
        }

        $regNo    = trim($_POST['reg_no'] ?? '');
        $email    = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';

        if (empty($regNo) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($password)) {
            $this->redirect('auth/login&error=invalidinput'); return;
        }

        $userModel = $this->model('User');
        $user      = $userModel->login($regNo, $email, $password);

        if (!$user) {
            $this->redirect('auth/login&error=invalid'); return;
        }

        session_regenerate_id(true);
        $_SESSION['user'] = $user;

        if (!empty($user['must_change_password'])) {
            $this->redirect('auth/changePassword'); return;
        }

        $this->redirect($user['role'] . '/dashboard');
    }

    /* ================================================================
       TEACHER / ADMIN LOGIN
    ================================================================*/
    public function authenticate_at(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('auth/login_at'); return;
        }

        $email    = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || empty($password)) {
            $this->redirect('auth/login_at&error=invalidinput'); return;
        }

        $userModel = $this->model('User');
        $user      = $userModel->loginByEmail($email, $password);

        if (!$user) {
            $this->redirect('auth/login_at&error=invalid'); return;
        }

        session_regenerate_id(true);
        $_SESSION['user'] = $user;

        if (!empty($user['must_change_password'])) {
            $this->redirect('auth/changePassword'); return;
        }

        $this->redirect($user['role'] . '/dashboard');
    }

    /* ================================================================
       ENROLLMENT SUBMISSION
    ================================================================*/
    public function enrollStore(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('auth/enroll'); return;
        }

        $userModel = $this->model('User');

        /* ---- Student fields ---- */
        $firstName   = trim($_POST['first_name']  ?? '');
        $middleName  = trim($_POST['middle_name'] ?? '');
        $surname     = trim($_POST['surname']     ?? '');
        $fullname    = trim("$firstName $middleName $surname");
        $email       = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $birthId     = trim($_POST['birth_id']    ?? '');
        $classGrade  = trim($_POST['class_grade'] ?? '');
        $dob         = trim($_POST['date_of_birth'] ?? '') ?: null;
        $gender      = in_array($_POST['gender'] ?? '', ['male','female','other']) ? $_POST['gender'] : null;

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->redirect('auth/enroll&error=invalidemail'); return;
        }
        if ($userModel->emailExists($email)) {
            $this->redirect('auth/enroll&error=emailtaken'); return;
        }

        /* ---- Parent fields ---- */
        $parentName  = trim($_POST['parent_name']         ?? '');
        $parentEmail = filter_input(INPUT_POST, 'parent_email', FILTER_SANITIZE_EMAIL);
        $parentPhone = trim($_POST['parent_phone']        ?? '');
        $parentRel   = in_array($_POST['relationship'] ?? '', ['father','mother','guardian'])
                       ? $_POST['relationship'] : 'guardian';
        $parentId    = trim($_POST['parent_id_number']    ?? '') ?: null;

        if (!filter_var($parentEmail, FILTER_VALIDATE_EMAIL)) {
            $this->redirect('auth/enroll&error=invalidparentemail'); return;
        }
        if ($userModel->emailExists($parentEmail)) {
            $this->redirect('auth/enroll&error=parentemailtaken'); return;
        }

        /* ---- File uploads ---- */
        $uploadDir = BASE_PATH . '/public/uploads/enrollments/';
        if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }

        $allowedMime  = ['image/jpeg','image/png','application/pdf'];
        $photoMimeOnly = ['image/jpeg','image/png'];
        $finfo        = finfo_open(FILEINFO_MIME_TYPE);

        // Birth certificate
        if (empty($_FILES['birth_certificate_file']['tmp_name'])) {
            $this->redirect('auth/enroll&error=missingfiles'); return;
        }
        $birthMime = finfo_file($finfo, $_FILES['birth_certificate_file']['tmp_name']);
        if (!in_array($birthMime, $allowedMime)) {
            $this->redirect('auth/enroll&error=invalidbirth'); return;
        }
        $birthExt  = pathinfo($_FILES['birth_certificate_file']['name'], PATHINFO_EXTENSION);
        $birthFile = 'birth_' . time() . '_' . uniqid() . '.' . $birthExt;
        move_uploaded_file($_FILES['birth_certificate_file']['tmp_name'], $uploadDir . $birthFile);

        // Passport photo
        if (empty($_FILES['passport_photo']['tmp_name'])) {
            $this->redirect('auth/enroll&error=missingfiles'); return;
        }
        $photoMime = finfo_file($finfo, $_FILES['passport_photo']['tmp_name']);
        if (!in_array($photoMime, $photoMimeOnly)) {
            $this->redirect('auth/enroll&error=invalidphoto'); return;
        }
        $photoExt  = pathinfo($_FILES['passport_photo']['name'], PATHINFO_EXTENSION);
        $photoFile = 'photo_' . time() . '_' . uniqid() . '.' . $photoExt;
        move_uploaded_file($_FILES['passport_photo']['tmp_name'], $uploadDir . $photoFile);

        finfo_close($finfo);

        /* ---- Persist student ---- */
        $studentUserId = $userModel->createUser([
            'fullname'             => $fullname,
            'email'                => $email,
            'password'             => password_hash('temp_' . uniqid(), PASSWORD_DEFAULT),
            'role'                 => 'student',
            'status'               => 'pending',
            'must_change_password' => 1,
        ]);

        $studentId = $userModel->createStudent([
            'user_id'                => $studentUserId,
            'class_grade'            => $classGrade,
            'birth_id'               => $birthId,
            'birth_certificate_file' => $birthFile,
            'passport_photo'         => $photoFile,
            'date_of_birth'          => $dob,
            'gender'                 => $gender,
        ]);

        /* ---- Persist parent ---- */
        $parentUserId = $userModel->createUser([
            'fullname'             => $parentName,
            'email'                => $parentEmail,
            'password'             => password_hash('temp_' . uniqid(), PASSWORD_DEFAULT),
            'role'                 => 'parent',
            'status'               => 'pending',
            'must_change_password' => 1,
        ]);

        $userModel->createParent([
            'user_id'      => $parentUserId,
            'phone'        => $parentPhone,
            'id_number'    => $parentId,
            'relationship' => $parentRel,
        ]);

        $userModel->linkParentStudent($parentUserId, $studentId);

        $this->redirect('auth/enroll&success=1');
    }

    /* ================================================================
       FORCE PASSWORD CHANGE
    ================================================================*/
    public function changePassword(): void
    {
        $this->requireLogin();
        $this->view('auth/changePassword');
    }

    public function updatePassword(): void
    {
        $this->requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('auth/changePassword'); return;
        }

        $newPassword = $_POST['new_password']     ?? '';
        $confirm     = $_POST['confirm_password'] ?? '';

        if (strlen($newPassword) < 8 || $newPassword !== $confirm) {
            $this->redirect('auth/changePassword&error=weakpassword'); return;
        }

        $userModel = $this->model('User');
        $userModel->updatePassword((int)$_SESSION['user']['id'], password_hash($newPassword, PASSWORD_DEFAULT));

        $_SESSION['user']['must_change_password'] = 0;

        $this->redirect($_SESSION['user']['role'] . '/dashboard');
    }

    /* ================================================================
       LOGOUT
    ================================================================*/
    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
        session_start();
        session_regenerate_id(true);
        $this->redirect('auth/login');
    }
}
