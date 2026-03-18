<?php
require_once '../config.php';
require_once '../includes/auth.php';

requireLogin();
requireRole('parent');

$userId = getUserId();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $mobile = $_POST['mobile'] ?? '';
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    
    try {
        if (!empty($new_password)) {
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!verifyPassword($current_password, $user['password'])) {
                $error = "Current password is incorrect";
            } else {
                $hashedPassword = hashPassword($new_password);
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, mobile = ?, password = ? WHERE id = ?");
                $stmt->execute([$name, $email, $mobile, $hashedPassword, $userId]);
                $message = "Profile updated successfully (including password)";
            }
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, mobile = ? WHERE id = ?");
            $stmt->execute([$name, $email, $mobile, $userId]);
            $message = "Profile updated successfully";
        }
        
        if (empty($error)) {
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
        }
    } catch (PDOException $e) {
        $error = "Update failed: " . $e->getMessage();
    }
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$parent = $stmt->fetch();

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM students WHERE parent_id = ? AND is_active = 1");
$stmt->execute([$userId]);
$childCount = $stmt->fetch()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Parent</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <!-- Mobile Navigation Toggle -->
    <button class="mobile-nav-toggle" onclick="toggleMobileNav()">☰</button>
    
    <div class="dashboard">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header"><h2>Parent Panel</h2></div>
            <nav class="sidebar-nav">
                <a href="/parent/dashboard.php" class="nav-item">📍 Live Tracking</a>
                <a href="/parent/students.php" class="nav-item">👨‍👩‍👧‍👦 Children</a>
                <a href="/parent/profile.php" class="nav-item active">⚙️ Profile</a>
                <a href="/logout.php" class="nav-item">🚪 Logout</a>
            </nav>
        </aside>
        
        <main class="main-content">
            <header class="content-header">
                <h1>My Profile</h1>
            </header>
            
            <div class="dashboard-content">
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h3>Parent Information</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="name">Full Name</label>
                                    <input type="text" id="name" name="name" class="form-control" 
                                           value="<?php echo htmlspecialchars($parent['name']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($parent['email']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="mobile">Mobile Number</label>
                                    <input type="tel" id="mobile" name="mobile" class="form-control" 
                                           value="<?php echo htmlspecialchars($parent['mobile']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Number of Children</label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo $childCount; ?>" disabled>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <h4>Change Password (Optional)</h4>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="current_password">Current Password</label>
                                    <input type="password" id="current_password" name="current_password" class="form-control">
                                </div>
                                
                                <div class="form-group">
                                    <label for="new_password">New Password</label>
                                    <input type="password" id="new_password" name="new_password" class="form-control">
                                </div>
                            </div>
                            
                            <p class="text-muted">Leave password fields empty to keep current password</p>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Update Profile</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Mobile Navigation
        function toggleMobileNav() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('mobile-visible');
        }
        
        // Close mobile nav when clicking outside
        document.addEventListener('click', (e) => {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.querySelector('.mobile-nav-toggle');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(e.target) && 
                !toggle.contains(e.target) && 
                sidebar.classList.contains('mobile-visible')) {
                sidebar.classList.remove('mobile-visible');
            }
        });
        
        // Handle window resize
        window.addEventListener('resize', () => {
            const sidebar = document.getElementById('sidebar');
            if (window.innerWidth > 768) {
                sidebar.classList.remove('mobile-visible');
            }
        });
    </script>
</body>
</html>
