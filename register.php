<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$fullname = '';
$username = '';
$email = '';
$errors = [];
$success = false;

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        $errors[] = 'คำขอไม่ถูกต้อง กรุณารีเฟรชหน้าแล้วลองใหม่';
    }

    $fullname = trim((string)($_POST['fullname'] ?? ''));
    $username = trim((string)($_POST['username'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($fullname === '' || mb_strlen($fullname) > 150) {
        $errors[] = 'กรุณากรอกชื่อ-นามสกุลไม่เกิน 150 ตัวอักษร';
    }
    if (!preg_match('/\A[a-zA-Z0-9_]{4,50}\z/', $username)) {
        $errors[] = 'ชื่อผู้ใช้ต้องมี 4–50 ตัว ใช้ภาษาอังกฤษ ตัวเลข หรือ _';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $errors[] = 'กรุณากรอกอีเมลให้ถูกต้อง';
    }
    if (!is_string($password) || strlen($password) < 12 || strlen($password) > 72) {
        $errors[] = 'รหัสผ่านต้องมีความยาว 12–72 ไบต์';
    }
    if (!is_string($confirm) || $password !== $confirm) {
        $errors[] = 'รหัสผ่านยืนยันไม่ตรงกัน';
    }

    if (!$errors) {
        try {
            $check = $pdo->prepare(
                'SELECT id FROM members WHERE username = :username OR email = :email LIMIT 1'
            );
            $check->execute(['username' => $username, 'email' => $email]);

            if ($check->fetch()) {
                $errors[] = 'ชื่อผู้ใช้หรืออีเมลนี้ถูกใช้งานแล้ว';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare(
                    'INSERT INTO members (fullname, username, email, password)
                     VALUES (:fullname, :username, :email, :password)'
                );
                $stmt->execute([
                    'fullname' => $fullname,
                    'username' => $username,
                    'email' => $email,
                    'password' => $hash,
                ]);
                $success = true;
                $fullname = $username = $email = '';
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            if ($e->getCode() === '23000') {
                $errors[] = 'ชื่อผู้ใช้หรืออีเมลนี้ถูกใช้งานแล้ว';
            } else {
                $errors[] = 'บันทึกข้อมูลไม่สำเร็จ กรุณาลองใหม่อีกครั้ง';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#10152f">
    <title>สมัครสมาชิก | Nova Member</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="ambient ambient-one" aria-hidden="true"></div>
    <div class="ambient ambient-two" aria-hidden="true"></div>
    <div class="ambient ambient-three" aria-hidden="true"></div>
    <main class="page-shell">
        <section class="intro-panel">
            <div class="brand">
                <div class="brand-mark" aria-hidden="true">N</div>
                <span>NOVA<span class="brand-light"> MEMBER</span></span>
            </div>
            <div class="intro-copy">
                <p class="eyebrow"><span class="eyebrow-dot"></span> YOUR NEXT CHAPTER STARTS HERE</p>
                <h1>เริ่มต้นสิ่งดี ๆ<br><span>ในแบบของคุณ</span></h1>
                <p class="intro-description">สร้างบัญชีของคุณวันนี้ แล้วเริ่มต้นประสบการณ์ใหม่ที่เรียบง่าย ปลอดภัย และเป็นของคุณ</p>
                <div class="feature-list">
                    <div class="feature-item"><span class="feature-icon">✦</span><span><strong>เรียบง่าย</strong><small>สมัครสมาชิกได้ในไม่กี่ขั้นตอน</small></span></div>
                    <div class="feature-item"><span class="feature-icon">⌁</span><span><strong>ปลอดภัย</strong><small>ปกป้องรหัสผ่านด้วยการเข้ารหัส</small></span></div>
                    <div class="feature-item"><span class="feature-icon">◈</span><span><strong>ทุกอุปกรณ์</strong><small>รองรับทั้งมือถือ แท็บเล็ต และคอมพิวเตอร์</small></span></div>
                </div>
            </div>
            <div class="intro-bottom"><span class="status-dot"></span> MEMBER EXPERIENCE <span class="bottom-separator">/</span> DESIGNED FOR YOU</div>
        </section>

        <section class="form-panel" aria-labelledby="form-title">
            <div class="form-topline"><span class="step-label">CREATE ACCOUNT</span><span class="step-count">01 <i>/</i> 01</span></div>
            <div class="form-heading">
                <div class="mini-orb" aria-hidden="true">✧</div>
                <h2 id="form-title">สมัครสมาชิก</h2>
                <p>กรอกข้อมูลด้านล่างเพื่อสร้างบัญชีใหม่</p>
            </div>

            <?php if ($success): ?>
                <div class="message success" role="status"><span class="message-symbol">✓</span><div><strong>สมัครสมาชิกสำเร็จ!</strong><span>ข้อมูลของคุณถูกบันทึกแล้ว</span></div></div>
            <?php endif; ?>

            <?php if ($errors): ?>
                <div class="message error" role="alert"><strong>ตรวจสอบข้อมูลอีกครั้ง</strong><ul>
                    <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
                </ul></div>
            <?php endif; ?>

            <form method="POST" action="register.php" class="register-form">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                <div class="field">
                    <label for="fullname">ชื่อ-นามสกุล</label>
                    <div class="input-wrap"><span class="input-icon" aria-hidden="true">♙</span><input type="text" id="fullname" name="fullname" placeholder="เช่น สมชาย ใจดี" value="<?= e($fullname) ?>" maxlength="150" autocomplete="name" required></div>
                </div>
                <div class="field">
                    <label for="username">ชื่อผู้ใช้</label>
                    <div class="input-wrap"><span class="input-icon" aria-hidden="true">@</span><input type="text" id="username" name="username" placeholder="your_username" value="<?= e($username) ?>" minlength="4" maxlength="50" pattern="[a-zA-Z0-9_]{4,50}" autocomplete="username" required></div>
                    <small>ภาษาอังกฤษ ตัวเลข หรือ _ อย่างน้อย 4 ตัว</small>
                </div>
                <div class="field">
                    <label for="email">อีเมล</label>
                    <div class="input-wrap"><span class="input-icon" aria-hidden="true">✉</span><input type="email" id="email" name="email" placeholder="you@example.com" value="<?= e($email) ?>" maxlength="190" autocomplete="email" required></div>
                </div>
                <div class="field">
                    <label for="password">รหัสผ่าน</label>
                    <div class="input-wrap"><span class="input-icon" aria-hidden="true">⌑</span><input type="password" id="password" name="password" placeholder="อย่างน้อย 12 ตัวอักษร" minlength="12" maxlength="72" autocomplete="new-password" required><button class="toggle-password" type="button" data-target="password" aria-label="แสดงหรือซ่อนรหัสผ่าน">ดู</button></div>
                    <div class="password-meter"><span id="password-meter-fill"></span></div>
                    <small id="password-hint">ใช้รหัสผ่านที่คาดเดาได้ยากและไม่ซ้ำกับเว็บไซต์อื่น</small>
                </div>
                <div class="field">
                    <label for="confirm_password">ยืนยันรหัสผ่าน</label>
                    <div class="input-wrap"><span class="input-icon" aria-hidden="true">✓</span><input type="password" id="confirm_password" name="confirm_password" placeholder="กรอกรหัสผ่านอีกครั้ง" minlength="12" maxlength="72" autocomplete="new-password" required><button class="toggle-password" type="button" data-target="confirm_password" aria-label="แสดงหรือซ่อนรหัสผ่าน">ดู</button></div>
                    <small id="match-hint" aria-live="polite">กรอกรหัสผ่านให้ตรงกันทั้งสองช่อง</small>
                </div>
                <button class="submit-button" type="submit"><span>สร้างบัญชีของฉัน</span><span class="button-arrow" aria-hidden="true">↗</span></button>
                <p class="terms">เมื่อสมัครสมาชิก แสดงว่าคุณยอมรับเงื่อนไขการใช้งานของระบบตัวอย่างนี้</p>
            </form>
            <div class="form-footer"><span>© 2026 NOVA MEMBER</span><span><i class="footer-lock">⌑</i> SECURE SIGN UP</span></div>
        </section>
    </main>
    <script>
    (() => {
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const meter = document.getElementById('password-meter-fill');
        const hint = document.getElementById('password-hint');
        const matchHint = document.getElementById('match-hint');

        document.querySelectorAll('.toggle-password').forEach((button) => {
            button.addEventListener('click', () => {
                const input = document.getElementById(button.dataset.target);
                const showing = input.type === 'text';
                input.type = showing ? 'password' : 'text';
                button.textContent = showing ? 'ดู' : 'ซ่อน';
            });
        });

        password.addEventListener('input', () => {
            const value = password.value;
            let score = 0;
            if (value.length >= 12) score++;
            if (/[A-Z]/.test(value) && /[a-z]/.test(value)) score++;
            if (/[0-9]/.test(value)) score++;
            if (/[^A-Za-z0-9]/.test(value)) score++;
            meter.style.width = (score * 25) + '%';
            meter.dataset.level = score < 2 ? 'low' : score < 4 ? 'medium' : 'high';
            hint.textContent = value.length < 12 ? 'ต้องมีอย่างน้อย 12 ตัวอักษร' : score >= 3 ? 'รหัสผ่านมีความซับซ้อนดี' : 'ลองเพิ่มตัวเลข ตัวพิมพ์ใหญ่ หรือสัญลักษณ์';
            checkMatch();
        });

        confirmPassword.addEventListener('input', checkMatch);
        function checkMatch() {
            if (!confirmPassword.value) {
                matchHint.textContent = 'กรอกรหัสผ่านให้ตรงกันทั้งสองช่อง';
                matchHint.classList.remove('match-ok', 'match-error');
            } else if (password.value === confirmPassword.value) {
                matchHint.textContent = 'รหัสผ่านตรงกันแล้ว ✓';
                matchHint.classList.add('match-ok');
                matchHint.classList.remove('match-error');
            } else {
                matchHint.textContent = 'รหัสผ่านยังไม่ตรงกัน';
                matchHint.classList.add('match-error');
                matchHint.classList.remove('match-ok');
            }
        }
    })();
    </script>
</body>
</html>
