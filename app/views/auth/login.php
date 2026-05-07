<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student / Parent Login – CBE LMS</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/CBE_LMS/public/css/auth.css">
</head>
<body>

<div class="auth-wrapper">

  <!-- Brand Panel -->
  <div class="auth-brand">
    <div class="logo">🎓 CBE LMS</div>
    <p class="tagline">Welcome back! Access your personalised learning dashboard.</p>
    <ul class="brand-bullets">
      <li><span class="bullet-icon">📘</span> Students: use your Reg No + Email</li>
      <li><span class="bullet-icon">👨‍👩‍👧</span> Parents: use child's Reg No + your Email</li>
      <li><span class="bullet-icon">🔒</span> Change your password on first login</li>
    </ul>
  </div>

  <!-- Form Card -->
  <div class="auth-card">
    <h2>Welcome Back</h2>
    <p class="subtitle">Sign in to your CBE LMS account</p>

    <?php
    $msgs = [
      'invalid'      => '⚠ Invalid credentials. Please check and try again.',
      'invalidinput' => '⚠ Please fill all fields correctly.',
      'pending'      => '⏳ Your account is awaiting admin approval.',
    ];
    $err = $_GET['error'] ?? '';
    if ($err && isset($msgs[$err])): ?>
    <div class="alert alert-error"><?= $msgs[$err] ?></div>
    <?php endif; ?>

    <form method="POST" action="/CBE_LMS/public/index.php?url=auth/authenticate">

      <div class="input-group">
        <label>Registration Number *</label>
        <input type="text" name="reg_no" placeholder="e.g. CBE/11/04/26" required autocomplete="username">
      </div>

      <div class="input-group">
        <label>Email Address *</label>
        <input type="email" name="email" placeholder="your@email.com" required autocomplete="email">
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
      New student? <a href="/CBE_LMS/public/index.php?url=auth/enroll">Enroll here</a>
      &nbsp;|&nbsp;
      <a href="/CBE_LMS/public/index.php?url=auth/login_at">Staff Login</a>
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
