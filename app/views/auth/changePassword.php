<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Set New Password – CBE LMS</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/CBE_LMS/public/css/auth.css">
</head>
<body>

<div class="auth-wrapper">

  <div class="auth-brand">
    <div class="logo">🎓 CBE LMS</div>
    <p class="tagline">Time to secure your account! Create a strong password to protect your learning data.</p>
    <ul class="brand-bullets">
      <li><span class="bullet-icon">🔒</span> Minimum 8 characters</li>
      <li><span class="bullet-icon">💡</span> Mix letters, numbers and symbols</li>
      <li><span class="bullet-icon">✅</span> Confirm to proceed to dashboard</li>
    </ul>
  </div>

  <div class="auth-card">
    <h2>Change Your Password</h2>
    <p class="subtitle">You are required to set a new password before continuing.</p>

    <?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error">⚠ Passwords do not match or are too short (min 8 characters).</div>
    <?php endif; ?>

    <div class="alert alert-info">
      👤 Logged in as: <strong><?= htmlspecialchars($_SESSION['user']['fullname'] ?? 'User') ?></strong>
      &nbsp;|&nbsp; Role: <?= ucfirst($_SESSION['user']['role'] ?? '') ?>
    </div>

    <form method="POST" action="/CBE_LMS/public/index.php?url=auth/updatePassword">

      <div class="input-group">
        <label>New Password *</label>
        <div class="pwd-wrapper">
          <input type="password" name="new_password" id="newPwd"
                 placeholder="Minimum 8 characters" required minlength="8" autocomplete="new-password">
          <button type="button" class="pwd-toggle" onclick="togglePwd('newPwd', this)" aria-label="Show password" title="Show / hide password">👁</button>
        </div>
      </div>

      <div class="input-group">
        <label>Confirm Password *</label>
        <div class="pwd-wrapper">
          <input type="password" name="confirm_password" id="confirmPwd"
                 placeholder="Re-enter password" required minlength="8" autocomplete="new-password">
          <button type="button" class="pwd-toggle" onclick="togglePwd('confirmPwd', this)" aria-label="Show confirm password" title="Show / hide password">👁</button>
        </div>
      </div>

      <div id="pwdMatch" style="font-size:.8rem;margin-bottom:14px;display:none;"></div>

      <button type="submit" class="btn-login">Set Password & Continue →</button>
    </form>
  </div>

</div>

<script>
/* Show / hide password toggle */
function togglePwd(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    btn.textContent = isHidden ? '🙈' : '👁';
    btn.title = isHidden ? 'Hide password' : 'Show password';
}

/* Password match indicator */
const np = document.getElementById('newPwd');
const cp = document.getElementById('confirmPwd');
const m  = document.getElementById('pwdMatch');

function checkMatch() {
    if (!cp.value) { m.style.display = 'none'; return; }
    m.style.display = 'block';
    if (cp.value === np.value) {
        m.textContent = '✅ Passwords match';
        m.style.color = '#16a34a';
    } else {
        m.textContent = '❌ Passwords do not match';
        m.style.color = '#dc2626';
    }
}

cp.addEventListener('input', checkMatch);
np.addEventListener('input', checkMatch);
</script>

</body>
</html>
