<?php
require_once '../config.php';
require_once '../includes/auth.php';

requireLogin();
requireRole('driver');

$userId = getUserId();
$message = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $mobile = $_POST['mobile'] ?? '';
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    
    try {
        // Verify current password if changing password
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
        
        // Update session
        if (empty($error)) {
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
        }
    } catch (PDOException $e) {
        $error = "Update failed: " . $e->getMessage();
    }
}

// Get driver info
$stmt = $pdo->prepare("
    SELECT u.*, b.bus_number
    FROM users u
    LEFT JOIN buses b ON b.driver_id = u.id
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$driver = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Driver</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="dashboard">
        <aside class="sidebar">
            <div class="sidebar-header"><h2>Driver Panel</h2></div>
            <nav class="sidebar-nav">
                <a href="/driver/dashboard.php" class="nav-item">Dashboard</a>
                <a href="/driver/trips.php" class="nav-item">Trip History</a>
                <a href="/driver/profile.php" class="nav-item active">Profile</a>
                <a href="/logout.php" class="nav-item">Logout</a>
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
                        <h3>Driver Information</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="name">Full Name</label>
                                    <input type="text" id="name" name="name" class="form-control" 
                                           value="<?php echo htmlspecialchars($driver['name']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($driver['email']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="mobile">Mobile Number</label>
                                    <input type="tel" id="mobile" name="mobile" class="form-control" 
                                           value="<?php echo htmlspecialchars($driver['mobile']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Driver Code</label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo htmlspecialchars($driver['driver_code']); ?>" disabled>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Assigned Bus</label>
                                <input type="text" class="form-control" 
                                       value="<?php echo htmlspecialchars($driver['bus_number'] ?? 'Not assigned'); ?>" disabled>
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
