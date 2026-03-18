<?php
require_once '../config.php';
require_once '../includes/auth.php';

requireLogin();
requireRole('admin');

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $id = $_POST['id'] ?? null;
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $mobile = $_POST['mobile'] ?? '';
        $password = $_POST['password'] ?? '';
        
        try {
            if ($action === 'add') {
                $hashedPassword = hashPassword($password);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, mobile, password, role) VALUES (?, ?, ?, ?, 'parent')");
                $stmt->execute([$name, $email, $mobile, $hashedPassword]);
                $message = "Parent added successfully!";
            } else {
                if (!empty($password)) {
                    $hashedPassword = hashPassword($password);
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, mobile = ?, password = ? WHERE id = ? AND role = 'parent'");
                    $stmt->execute([$name, $email, $mobile, $hashedPassword, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, mobile = ? WHERE id = ? AND role = 'parent'");
                    $stmt->execute([$name, $email, $mobile, $id]);
                }
                $message = "Parent updated successfully!";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? null;
        try {
            $stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = ? AND role = 'parent'");
            $stmt->execute([$id]);
            $message = "Parent deactivated successfully!";
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

$stmt = $pdo->query("
    SELECT u.*, 
           (SELECT COUNT(*) FROM students WHERE parent_id = u.id AND is_active = 1) as student_count
    FROM users u
    WHERE u.role = 'parent'
    ORDER BY u.created_at DESC
");
$parents = $stmt->fetchAll();

$editParent = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'parent'");
    $stmt->execute([$_GET['edit']]);
    $editParent = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Parents - Admin</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="dashboard">
        <aside class="sidebar">
            <div class="sidebar-header"><h2>Admin Panel</h2></div>
            <nav class="sidebar-nav">
                <a href="/admin/dashboard.php" class="nav-item">Dashboard</a>
                <a href="/admin/drivers.php" class="nav-item">Drivers</a>
                <a href="/admin/parents.php" class="nav-item active">Parents</a>
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
                <h1>Manage Parents</h1>
                <button onclick="showAddForm()" class="btn btn-primary">Add New Parent</button>
            </header>
            
            <div class="dashboard-content">
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <div id="parent-form" class="card mb-4" style="<?php echo ($editParent || isset($_GET['action'])) ? '' : 'display:none;'; ?>">
                    <div class="card-header">
                        <h3><?php echo $editParent ? 'Edit Parent' : 'Add New Parent'; ?></h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="form">
                            <input type="hidden" name="action" value="<?php echo $editParent ? 'edit' : 'add'; ?>">
                            <?php if ($editParent): ?>
                                <input type="hidden" name="id" value="<?php echo $editParent['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="name">Full Name *</label>
                                    <input type="text" id="name" name="name" class="form-control" 
                                           value="<?php echo htmlspecialchars($editParent['name'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">Email *</label>
                                    <input type="email" id="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($editParent['email'] ?? ''); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="mobile">Mobile Number *</label>
                                    <input type="tel" id="mobile" name="mobile" class="form-control" 
                                           value="<?php echo htmlspecialchars($editParent['mobile'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="password">Password <?php echo $editParent ? '(leave blank to keep current)' : '*'; ?></label>
                                    <input type="password" id="password" name="password" class="form-control" 
                                           <?php echo $editParent ? '' : 'required'; ?>>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <?php echo $editParent ? 'Update Parent' : 'Add Parent'; ?>
                                </button>
                                <button type="button" onclick="hideForm()" class="btn btn-secondary">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3>All Parents</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Mobile</th>
                                        <th>Children</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($parents as $parent): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($parent['name']); ?></td>
                                            <td><?php echo htmlspecialchars($parent['email']); ?></td>
                                            <td><?php echo htmlspecialchars($parent['mobile']); ?></td>
                                            <td><?php echo $parent['student_count']; ?> child(ren)</td>
                                            <td>
                                                <span class="badge badge-<?php echo $parent['is_active'] ? 'success' : 'danger'; ?>">
                                                    <?php echo $parent['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="?edit=<?php echo $parent['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                                <?php if ($parent['is_active']): ?>
                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Deactivate this parent?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?php echo $parent['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">Deactivate</button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        function showAddForm() {
            document.getElementById('parent-form').style.display = 'block';
        }
        function hideForm() {
            window.location.href = '/admin/parents.php';
        }
    </script>
</body>
</html>
