<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Student Enrollment – CBE LMS</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/CBE_LMS/public/css/auth.css">
</head>

<body>

  <div class="auth-wrapper">

    <!-- ===== Brand Panel ===== -->
    <div class="auth-brand">
      <div class="logo">🎓 CBE LMS</div>
      <p class="tagline">Kenya's Competency-Based Education Learning Management System. Enroll your child today to start
        their learning journey.</p>
      <ul class="brand-bullets">
        <li><span class="bullet-icon">📋</span> Complete the 3-step enrollment form</li>
        <li><span class="bullet-icon">⏳</span> Await admin review and approval</li>
        <li><span class="bullet-icon">📧</span> Receive credentials via email</li>
        <li><span class="bullet-icon">🚀</span> Start the CBE learning journey</li>
      </ul>
    </div>

    <!-- ===== Form Card ===== -->
    <div class="auth-card">

      <h2>Student Enrollment</h2>
      <p class="subtitle">Fill in all details carefully. All fields marked * are required.</p>

      <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
          ✅ Enrollment submitted! Your application is pending admin approval. Check your email for updates.
        </div>
      <?php endif; ?>

      <?php
      $errors = [
        'invalidemail' => '⚠ Invalid student email address.',
        'emailtaken' => '⚠ That student email is already registered.',
        'invalidparentemail' => '⚠ Invalid parent email address.',
        'parentemailtaken' => '⚠ That parent email is already registered.',
        'missingfiles' => '⚠ Please upload both required documents.',
        'invalidbirth' => '⚠ Birth certificate must be JPG, PNG or PDF.',
        'invalidphoto' => '⚠ Passport photo must be JPG or PNG.',
      ];
      $err = $_GET['error'] ?? '';
      if ($err && isset($errors[$err])): ?>
        <div class="alert alert-error"><?= $errors[$err] ?></div>
      <?php endif; ?>

      <!-- Step Indicators -->
      <div class="step-progress" id="stepProgress">
        <div class="step-dot active" id="dot0">1</div>
        <div class="step-line" id="line0"></div>
        <div class="step-dot" id="dot1">2</div>
        <div class="step-line" id="line1"></div>
        <div class="step-dot" id="dot2">3</div>
      </div>

      <form method="POST" action="/CBE_LMS/public/index.php?url=auth/enrollStore" enctype="multipart/form-data"
        id="enrollForm">

        <!-- ===== STEP 1: Student Information ===== -->
        <div class="form-step active" id="step0">
          <div class="step-title">📘 Step 1 – Student Information</div>

          <div class="form-row">
            <div class="input-group">
              <label>First Name *</label>
              <input type="text" name="first_name" placeholder="e.g. Amara" required>
            </div>
            <div class="input-group">
              <label>Middle Name</label>
              <input type="text" name="middle_name" placeholder="Optional">
            </div>
          </div>

          <div class="form-row">
            <div class="input-group">
              <label>Surname *</label>
              <input type="text" name="surname" placeholder="e.g. Otieno" required>
            </div>
            <div class="input-group">
              <label>Gender *</label>
              <select name="gender" required>
                <option value="">-- Select --</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
                <option value="other">Other</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="input-group">
              <label>Date of Birth</label>
              <input type="date" name="date_of_birth">
            </div>
            <div class="input-group">
              <label>Student Email *</label>
              <input type="email" name="email" placeholder="student@email.com" required>
            </div>
          </div>

          <div class="input-group">
            <label>Class / Grade *</label>
            <select name="class_grade" required>
              <option value="">-- Select Grade --</option>
              <option>PP1</option>
              <option>PP2</option>
              <option>Grade 1</option>
              <option>Grade 2</option>
              <option>Grade 3</option>
              <option>Grade 4</option>
              <option>Grade 5</option>
              <option>Grade 6</option>
              <option>Grade 7</option>
              <option>Grade 8</option>
              <option>Grade 9</option>
              <option>Grade 10</option>
              <option>Grade 11</option>
              <option>Grade 12</option>
            </select>
          </div>

          <div class="btn-row single">
            <button type="button" class="btn-login" onclick="goNext(0)">Next: Parent Details →</button>
          </div>
        </div>

        <!-- ===== STEP 2: Parent / Guardian ===== -->
        <div class="form-step" id="step1">
          <div class="step-title">👨‍👩‍👧 Step 2 – Parent / Guardian Information</div>

          <div class="form-row">
            <div class="input-group">
              <label>Parent / Guardian Full Name *</label>
              <input type="text" name="parent_name" placeholder="Full name" required>
            </div>
            <div class="input-group">
              <label>Relationship *</label>
              <select name="relationship" required>
                <option value="guardian">Guardian</option>
                <option value="father">Father</option>
                <option value="mother">Mother</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="input-group">
              <label>Parent Email *</label>
              <input type="email" name="parent_email" placeholder="parent@email.com" required>
            </div>
            <div class="input-group">
              <label>Parent Phone *</label>
              <input type="tel" name="parent_phone" placeholder="e.g. 0712 345 678" required>
            </div>
          </div>

          <div class="input-group">
            <label>Parent National ID / Passport No.</label>
            <input type="text" name="parent_id_number" placeholder="Optional – for verification">
          </div>

          <div class="btn-row">
            <button type="button" class="btn-back" onclick="goPrev(1)">← Back</button>
            <button type="button" class="btn-login" onclick="goNext(1)">Next: Documents →</button>
          </div>
        </div>

        <!-- ===== STEP 3: Documents ===== -->
        <div class="form-step" id="step2">
          <div class="step-title">📎 Step 3 – Documents</div>

          <div class="input-group">
            <label>Birth Certificate / NEMIS Number *</label>
            <input type="text" name="birth_id" placeholder="e.g. BC/2015/04567 or NEMIS ID" required>
          </div>

          <div class="input-group">
            <label>Upload Birth Certificate * <small>(JPG, PNG or PDF – max 5MB)</small></label>
            <div class="file-zone" onclick="document.getElementById('birthFile').click()">
              <label class="file-label">
                <span>📄</span>
                <span>Click to upload birth certificate</span>
              </label>
              <div class="file-name" id="birthFileName">No file selected</div>
              <input type="file" id="birthFile" name="birth_certificate_file" accept=".jpg,.jpeg,.png,.pdf" required
                onchange="document.getElementById('birthFileName').textContent = this.files[0]?.name || 'No file selected'">
            </div>
          </div>

          <div class="input-group">
            <label>Upload Passport Photo * <small>(JPG or PNG – max 2MB)</small></label>
            <div class="file-zone" onclick="document.getElementById('photoFile').click()">
              <label class="file-label">
                <span>🖼</span>
                <span>Click to upload passport photo</span>
              </label>
              <div class="file-name" id="photoFileName">No file selected</div>
              <input type="file" id="photoFile" name="passport_photo" accept=".jpg,.jpeg,.png" required
                onchange="document.getElementById('photoFileName').textContent = this.files[0]?.name || 'No file selected'">
            </div>
          </div>

          <div class="btn-row">
            <button type="button" class="btn-back" onclick="goPrev(2)">← Back</button>
            <button type="submit" class="btn-login">✅ Submit Enrollment</button>
          </div>
        </div>

      </form>

      <div class="auth-footer">
        Already have an account? <a href="/CBE_LMS/public/index.php?url=auth/login">Login here</a>
      </div>
    </div>

  </div>

  <script>
    const steps = document.querySelectorAll('.form-step');
    const dots = document.querySelectorAll('.step-dot');
    const lines = document.querySelectorAll('.step-line');

    function showStep(n) {
      steps.forEach((s, i) => s.classList.toggle('active', i === n));
      dots.forEach((d, i) => {
        d.classList.toggle('active', i === n);
        d.classList.toggle('done', i < n);
      });
      lines.forEach((l, i) => l.classList.toggle('done', i < n));
    }

    function goNext(current) {
      // Simple required-field validation for current step
      const stepEl = document.getElementById('step' + current);
      const required = stepEl.querySelectorAll('[required]');
      for (const el of required) {
        if (!el.value.trim()) {
          el.focus();
          el.style.borderColor = '#dc2626';
          setTimeout(() => el.style.borderColor = '', 2000);
          return;
        }
      }
      showStep(current + 1);
    }

    function goPrev(current) {
      showStep(current - 1);
    }
  </script>

</body>

</html>