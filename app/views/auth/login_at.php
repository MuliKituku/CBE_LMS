<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Staff Login – CBE LMS</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/CBE_LMS/public/css/auth.css">
</head>
<body>

<div class="auth-wrapper">

  <!-- Brand Panel -->
  <div class="auth-brand">
    <div class="logo">🎓 CBE LMS</div>
    <p class="tagline">Staff portal for Teachers and Administrators.</p>
    <ul class="brand-bullets">
      <li><span class="bullet-icon">🎓</span> Teachers: manage classes & assessments</li>
      <li><span class="bullet-icon">🛠</span> Admins: manage users & enrollments</li>
      <li><span class="bullet-icon">📊</span> View real-time analytics</li>
    </ul>
  </div>

  <!-- Form Card -->
  <div class="auth-card">
    <h2>Staff Login</h2>
    <p class="subtitle">For teachers and administrators only</p>

    <?php
    $msgs = [
      'invalid'      => '⚠ Invalid email or password.',
      'invalidinput' => '⚠ Please fill all fields correctly.',
    ];
    $err = $_GET['error'] ?? '';
    if ($err && isset($msgs[$err])): ?>
    <div class="alert alert-error"><?= $msgs[$err] ?></div>
    <?php endif; ?>

    <form method="POST" action="/CBE_LMS/public/index.php?url=auth/authenticate_at">

      <div class="input-group">
        <label>Email Address *</label>
        <input type="email" name="email" placeholder="staff@cbelms.ac.ke" required autocomplete="email">
      </div>

      <div class="input-group">
        <label>Password *</label>
        <div class="pwd-wrapper">
          <input type="password" name="password" id="pwd" placeholder="••••••••" required autocomplete="current-password">
          <button type="button" class="pwd-toggle" onclick="togglePwd('pwd', this)" aria-label="Show password" title="Show / hide password">👁</button>
        </div>
      </div>

      <button type="submit" class="btn-login">Sign In →</button>
    </form>

    <div class="auth-footer" style="margin-top:16px">
      Not staff? <a href="/CBE_LMS/public/index.php?url=auth/login">Student / Parent Login</a>
    </div>
  </div>

</div>

<script>
function togglePwd(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    btn.textContent = isHidden ? '🙈' : '👁';
    btn.title = isHidden ? 'Hide password' : 'Show password';
}
</script>
</body>
</html>
