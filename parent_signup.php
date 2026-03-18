<?php
require_once 'config.php';
require_once 'includes/auth.php';

// If already logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    $role = getUserRole();
    if ($role === 'admin') {
        header('Location: /admin/dashboard.php');
    } elseif ($role === 'driver') {
        header('Location: /driver/dashboard.php');
    } elseif ($role === 'parent') {
        header('Location: /parent/dashboard.php');
    }
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($fullname) || empty($email) || empty($mobile) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $error = 'Mobile number must be 10 digits';
    } else {
        try {
            // Check if email or mobile already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR mobile = ?");
            $stmt->execute([$email, $mobile]);
            if ($stmt->fetch()) {
                $error = 'Email or mobile number already registered';
            } else {
                // Insert new parent
                $password_hash = hashPassword($password);
                $stmt = $pdo->prepare("
                    INSERT INTO users (name, email, mobile, password, role, is_active, created_at) 
                    VALUES (?, ?, ?, ?, 'parent', 1, NOW())
                ");
                $stmt->execute([$fullname, $email, $mobile, $password_hash]);
                
                // Get the new user
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND mobile = ?");
                $stmt->execute([$email, $mobile]);
                $user = $stmt->fetch();
                
                if ($user) {
                    // Auto login the user
                    loginUser($user);
                    header('Location: /parent/dashboard.php');
                    exit;
                } else {
                    $error = 'Registration successful but login failed. Please try logging in.';
                }
            }
        } catch (PDOException $e) {
            $error = 'Registration failed. Please try again.';
            error_log($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Signup - School Bus Tracker</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="logo">
                <img src="/assets/images/bus-logo.png" alt="School Bus Tracker" onerror="this.style.display='none'">
                <h1>School Bus Tracker</h1>
                <p style="margin-top: 10px; color: #666;">Parent Registration</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="fullname">Full Name</label>
                    <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($_POST['fullname'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="mobile">Mobile Number</label>
                    <input type="tel" id="mobile" name="mobile" value="<?php echo htmlspecialchars($_POST['mobile'] ?? ''); ?>" placeholder="10-digit mobile number" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Minimum 6 characters" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Create Account</button>
            </form>
            
            <div class="login-footer">
                <p>Already have an account? <a href="/index.php" style="color: var(--primary-color); text-decoration: none;">Login here</a></p>
                <p style="margin-top: 10px;">School Bus Tracker System</p>
            </div>
        </div>
    </div>
</body>
</html>
