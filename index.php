<?php
require_once 'config.php';
require_once 'includes/auth.php';

// If already logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    $role = getUserRole();
    if ($role === 'admin' || $role === 'sub_admin') {
        header('Location: /admin/dashboard.php');
    } elseif ($role === 'driver') {
        header('Location: /driver/dashboard.php');
    } elseif ($role === 'parent') {
        header('Location: /parent/dashboard.php');
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $mobile = $_POST['mobile'] ?? '';
    $password = $_POST['password'] ?? '';
    $driver_code = $_POST['driver_code'] ?? '';
    
    try {
        // Try login with driver code first
        if (!empty($driver_code)) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE driver_code = ? AND role = 'driver' AND is_active = 1");
            $stmt->execute([$driver_code]);
            $user = $stmt->fetch();
            
            if ($user && verifyPassword($password, $user['password'])) {
                loginUser($user);
                header('Location: /driver/dashboard.php');
                exit;
            } else {
                $error = 'Invalid driver code or password';
            }
        } else {
            // Regular login with email and mobile
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND mobile = ? AND is_active = 1");
            $stmt->execute([$email, $mobile]);
            $user = $stmt->fetch();
            
            if ($user && verifyPassword($password, $user['password'])) {
                loginUser($user);
                
                // Redirect based on role
                if ($user['role'] === 'admin' || $user['role'] === 'sub_admin') {
                    header('Location: /admin/dashboard.php');
                } elseif ($user['role'] === 'driver') {
                    header('Location: /driver/dashboard.php');
                } elseif ($user['role'] === 'parent') {
                    header('Location: /parent/dashboard.php');
                }
                exit;
            } else {
                $error = 'Invalid credentials';
            }
        }
    } catch (PDOException $e) {
        $error = 'Login failed. Please try again.';
        error_log($e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - School Bus Tracker</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="logo">
                <img src="/assets/images/bus-logo.png" alt="School Bus Tracker" onerror="this.style.display='none'">
                <h1>School Bus Tracker</h1>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="login-tabs">
                <button class="tab-btn active" onclick="showTab('regular')">Regular Login</button>
                <button class="tab-btn" onclick="showTab('driver')">Driver Login</button>
            </div>
            
            <!-- Regular Login Form -->
            <form method="POST" id="regular-login" class="login-form">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="mobile">Mobile Number</label>
                    <input type="tel" id="mobile" name="mobile" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>
            
            <!-- Driver Login Form -->
            <form method="POST" id="driver-login" class="login-form" style="display: none;">
                <div class="form-group">
                    <label for="driver_code">Driver Code</label>
                    <input type="text" id="driver_code" name="driver_code" placeholder="Enter your unique driver code" required>
                </div>
                
                <div class="form-group">
                    <label for="driver_password">Password</label>
                    <input type="password" id="driver_password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Login as Driver</button>
            </form>
            
            <div class="login-footer">
                <p>New Parent? <a href="/parent_signup.php" style="color: var(--primary-color); text-decoration: none; font-weight: 600;">Sign up here</a></p>
                <p style="margin-top: 10px;">School Bus Tracker System<br>
                <small>Contact administrator for login credentials</small></p>
            </div>
        </div>
    </div>
    
    <script>
        function showTab(tab) {
            const tabs = document.querySelectorAll('.tab-btn');
            tabs.forEach(t => t.classList.remove('active'));
            
            if (tab === 'regular') {
                document.getElementById('regular-login').style.display = 'block';
                document.getElementById('driver-login').style.display = 'none';
                tabs[0].classList.add('active');
            } else {
                document.getElementById('regular-login').style.display = 'none';
                document.getElementById('driver-login').style.display = 'block';
                tabs[1].classList.add('active');
            }
        }
    </script>
</body>
</html>
