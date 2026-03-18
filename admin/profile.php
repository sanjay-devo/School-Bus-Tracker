<?php
require_once '../config.php';
require_once '../includes/auth.php';

requireLogin();
requireRole('admin');

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
$admin = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Admin</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="dashboard">
        <aside class="sidebar">
            <div class="sidebar-header"><h2>Admin Panel</h2></div>
            <nav class="sidebar-nav">
                <a href="/admin/dashboard.php" class="nav-item">Dashboard</a>
                <a href="/admin/drivers.php" class="nav-item">Drivers</a>
                <a href="/admin/parents.php" class="nav-item">Parents</a>
                <a href="/admin/students.php" class="nav-item">Students</a>
                <a href="/admin/buses.php" class="nav-item">Buses</a>
                <a href="/admin/routes.php" class="nav-item">Routes</a>
                <a href="/admin/trips.php" class="nav-item">Trip Reports</a>
                <a href="/admin/live-tracking.php" class="nav-item">Live Tracking</a>
                <a href="/logout.php" class="nav-item">Logout</a>
            </nav>
        </aside>
        
        <main class="main-content">
            <header class="content-header">
                <h1>Admin Profile</h1>
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
                        <h3>Administrator Information</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="name">Full Name</label>
                                    <input type="text" id="name" name="name" class="form-control" 
                                           value="<?php echo htmlspecialchars($admin['name']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="mobile">Mobile Number</label>
                                <input type="tel" id="mobile" name="mobile" class="form-control" 
                                       value="<?php echo htmlspecialchars($admin['mobile']); ?>" required>
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
</body>
</html>
