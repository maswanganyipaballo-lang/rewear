<?php
session_start();
include 'db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error   = '';
$success = '';

// Allowed email domains for regular users
$allowed_domains = ['gmail.com', 'outlook.com', 'hotmail.com', 'yahoo.com', 'icloud.com', 'live.com', 'protonmail.com', 'me.com'];

if (isset($_POST['register'])) {
    $full_name = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $password  = $_POST['password'];
    $confirm   = $_POST['confirm_password'];
    $role      = in_array($_POST['role'], ['buyer', 'seller']) ? $_POST['role'] : 'buyer';

    // --- Full name validation: no numbers ---
    if (strlen($full_name) < 2) {
        $error = "Please enter your full name.";
    } elseif (preg_match('/[0-9]/', $full_name)) {
        $error = "Full name must not contain numbers.";
    } elseif (!preg_match('/^[a-zA-Z\s\'\-\.]+$/', $full_name)) {
        $error = "Full name may only contain letters, spaces, hyphens, and apostrophes.";
    }
    // --- Email: must be valid and from an allowed domain ---
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $email_domain = strtolower(substr(strrchr($email, '@'), 1));
        if (!in_array($email_domain, $allowed_domains)) {
            $error = "Please use a personal email address (e.g. Gmail, Outlook, Yahoo, Hotmail, iCloud).";
        }
        // --- Password strength: cybersecurity standards ---
        elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long.";
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $error = "Password must contain at least one uppercase letter.";
        } elseif (!preg_match('/[a-z]/', $password)) {
            $error = "Password must contain at least one lowercase letter.";
        } elseif (!preg_match('/[0-9]/', $password)) {
            $error = "Password must contain at least one number.";
        } elseif (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
            $error = "Password must contain at least one special character (e.g. !@#\$%^&*).";
        } elseif ($password !== $confirm) {
            $error = "Passwords do not match.";
        } else {
            $full_name_safe = mysqli_real_escape_string($conn, $full_name);
            $email_safe     = mysqli_real_escape_string($conn, $email);
            $check = mysqli_query($conn, "SELECT user_id FROM users WHERE email='$email_safe'");
            if (mysqli_num_rows($check) > 0) {
                $error = "An account with that email already exists.";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $sql    = "INSERT INTO users (full_name, email, password, role) VALUES ('$full_name_safe', '$email_safe', '$hashed', '$role')";
                if (mysqli_query($conn, $sql)) {
                    $success = "Account created! You can now log in.";
                } else {
                    $error = "Something went wrong. Please try again.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — ReWear</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        .password-strength { margin-top: -0.6rem; margin-bottom: 0.8rem; font-size: 0.75rem; }
        .req { color: var(--light-mid); display: flex; align-items: center; gap: 0.3rem; margin: 2px 0; }
        .req.met { color: #2d6a4f; }
        .req::before { content: '✗'; font-weight: 700; color: #c0392b; }
        .req.met::before { content: '✓'; color: #2d6a4f; }
        .strength-bar { height: 4px; border-radius: 2px; background: #eee; margin-bottom: 6px; overflow: hidden; }
        .strength-fill { height: 100%; border-radius: 2px; transition: width 0.3s, background 0.3s; width: 0; }
    </style>
</head>
<body class="auth-page" style="padding-top:0;">

<div class="container py-5">
    <div class="auth-card">
        <div class="auth-logo">Re<span>Wear</span></div>
        <p class="auth-sub">Create your account and start shopping or selling</p>

        <?php if ($error): ?>
            <div class="alert-custom alert-error-custom"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert-custom alert-success-custom"><?php echo htmlspecialchars($success); ?> <a href="login.php">Sign in now →</a></div>
        <?php endif; ?>

        <form method="POST" id="registerForm">
            <label class="form-label-custom">Full Name</label>
            <input type="text" name="full_name" id="fullName" class="form-control-custom" placeholder="e.g. Jane Smith" required
                   value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
            <p id="nameError" style="font-size:0.75rem;color:#c0392b;margin-top:-0.6rem;margin-bottom:0.8rem;display:none;"></p>

            <label class="form-label-custom">Email Address</label>
            <input type="email" name="email" id="emailInput" class="form-control-custom" placeholder="you@gmail.com" required
                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            <p id="emailWarn" style="font-size:0.72rem;color:#c0392b;margin-top:-0.6rem;margin-bottom:0.2rem;display:none;"></p>
            <p style="font-size:0.72rem;color:var(--light-mid);margin-top:0;margin-bottom:0.8rem;">
                Accepted: Gmail, Outlook, Hotmail, Yahoo, iCloud, Live, ProtonMail
            </p>

            <label class="form-label-custom">Password</label>
            <div style="position:relative;">
                <input type="password" name="password" id="passwordInput" class="form-control-custom" placeholder="Min. 8 chars with uppercase, number & symbol" required style="padding-right:2.8rem;">
                <button type="button" onclick="togglePw('passwordInput','eyePw')" style="position:absolute;right:0.75rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:1rem;color:var(--light-mid);" id="eyePw">👁</button>
            </div>

            <div class="password-strength">
                <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                <div class="req" id="req-len">At least 8 characters</div>
                <div class="req" id="req-upper">At least one uppercase letter</div>
                <div class="req" id="req-lower">At least one lowercase letter</div>
                <div class="req" id="req-num">At least one number</div>
                <div class="req" id="req-special">At least one special character (!@#$%^&*...)</div>
            </div>

            <label class="form-label-custom">Confirm Password</label>
            <div style="position:relative;">
                <input type="password" name="confirm_password" id="confirmInput" class="form-control-custom" placeholder="Repeat password" required style="padding-right:2.8rem;">
                <button type="button" onclick="togglePw('confirmInput','eyeConfirm')" style="position:absolute;right:0.75rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:1rem;color:var(--light-mid);" id="eyeConfirm">👁</button>
            </div>
            <p id="matchError" style="font-size:0.75rem;color:#c0392b;margin-top:-0.6rem;margin-bottom:0.8rem;display:none;">Passwords do not match.</p>

            <label class="form-label-custom">I want to</label>
            <div class="role-select">
                <label class="role-btn" id="buyer-btn">
                    <input type="radio" name="role" value="buyer" checked onchange="selectRole(this)">
                    🛍 Buy clothes
                </label>
                <label class="role-btn" id="seller-btn">
                    <input type="radio" name="role" value="seller" onchange="selectRole(this)">
                    🏷 Sell clothes
                </label>
            </div>

            <button type="submit" name="register" class="btn-primary-custom">Create Account →</button>
        </form>

        <div class="auth-footer-link">
            Already have an account? <a href="login.php">Sign in</a>
        </div>
        <div style="text-align:center;margin-top:0.75rem;">
            <a href="index.php" style="font-size:0.8rem;color:var(--light-mid);text-decoration:none;">← Back to shop</a>
        </div>
    </div>
</div>

<script>
function togglePw(fieldId, btnId) {
    const field = document.getElementById(fieldId);
    const btn   = document.getElementById(btnId);
    if (field.type === 'password') {
        field.type = 'text';
        btn.textContent = '🙈';
    } else {
        field.type = 'password';
        btn.textContent = '👁';
    }
}

function selectRole(radio) {
    document.querySelectorAll('.role-btn').forEach(b => b.classList.remove('selected'));
    radio.closest('.role-btn').classList.add('selected');
}
document.querySelector('input[name=role]:checked').closest('.role-btn').classList.add('selected');

// Common domain typo detection
const domainFixes = {
    'gmail.co': 'gmail.com', 'gmail.cm': 'gmail.com', 'gmal.com': 'gmail.com',
    'gmial.com': 'gmail.com', 'gnail.com': 'gmail.com',
    'outlok.com': 'outlook.com', 'outloook.com': 'outlook.com',
    'hotmal.com': 'hotmail.com', 'hotmai.com': 'hotmail.com',
    'yahooo.com': 'yahoo.com', 'yaho.com': 'yahoo.com',
    'iclod.com': 'icloud.com', 'icoud.com': 'icloud.com',
};
const allowedDomains = ['gmail.com','outlook.com','hotmail.com','yahoo.com','icloud.com','live.com','protonmail.com','me.com'];

document.getElementById('emailInput').addEventListener('input', function() {
    const val = this.value;
    const warn = document.getElementById('emailWarn');
    const atIdx = val.lastIndexOf('@');
    if (atIdx === -1) { warn.style.display = 'none'; return; }
    const domain = val.slice(atIdx + 1).toLowerCase();
    if (!domain) { warn.style.display = 'none'; return; }
    if (domainFixes[domain]) {
        warn.textContent = '⚠ Did you mean @' + domainFixes[domain] + '?';
        warn.style.display = 'block';
    } else if (domain.length > 2 && !allowedDomains.includes(domain)) {
        warn.textContent = '⚠ Invalid or unsupported email domain.';
        warn.style.display = 'block';
    } else {
        warn.style.display = 'none';
    }
});

// Full name: block numbers on input
document.getElementById('fullName').addEventListener('input', function() {
    const val = this.value;
    const errEl = document.getElementById('nameError');
    if (/[0-9]/.test(val)) {
        errEl.textContent = 'Full name must not contain numbers.';
        errEl.style.display = 'block';
        this.style.borderColor = '#c0392b';
    } else {
        errEl.style.display = 'none';
        this.style.borderColor = '';
    }
});

// Password strength checker
const pwInput = document.getElementById('passwordInput');
const confirmInput = document.getElementById('confirmInput');

function checkPassword(pw) {
    const checks = {
        'req-len':     pw.length >= 8,
        'req-upper':   /[A-Z]/.test(pw),
        'req-lower':   /[a-z]/.test(pw),
        'req-num':     /[0-9]/.test(pw),
        'req-special': /[!@#$%^&*()\-_=+\[\]{};':"\\|,.<>\/?]/.test(pw)
    };
    let score = Object.values(checks).filter(Boolean).length;
    Object.entries(checks).forEach(([id, met]) => {
        document.getElementById(id).classList.toggle('met', met);
    });
    const fill = document.getElementById('strengthFill');
    const colors = ['#c0392b','#e67e22','#f1c40f','#27ae60','#1a7a4a'];
    fill.style.width = (score * 20) + '%';
    fill.style.background = colors[score - 1] || '#eee';
}

pwInput.addEventListener('input', () => checkPassword(pwInput.value));
confirmInput.addEventListener('input', function() {
    const err = document.getElementById('matchError');
    if (this.value && this.value !== pwInput.value) {
        err.style.display = 'block';
    } else {
        err.style.display = 'none';
    }
});
</script>
</body>
</html>
