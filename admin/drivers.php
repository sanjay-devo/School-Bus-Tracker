<?php
require_once '../config.php';
require_once '../includes/auth.php';

requireLogin();
requireRole('admin');

$message = '';
$error = '';

// Handle form submissions
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
                // Generate unique driver code
                $driver_code = generateDriverCode();
                $hashedPassword = hashPassword($password);
                
                $stmt = $pdo->prepare("INSERT INTO users (name, email, mobile, password, driver_code, role) VALUES (?, ?, ?, ?, ?, 'driver')");
                $stmt->execute([$name, $email, $mobile, $hashedPassword, $driver_code]);
                
                $message = "Driver added successfully! Driver Code: $driver_code";
            } else {
                if (!empty($password)) {
                    $hashedPassword = hashPassword($password);
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, mobile = ?, password = ? WHERE id = ? AND role = 'driver'");
                    $stmt->execute([$name, $email, $mobile, $hashedPassword, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, mobile = ? WHERE id = ? AND role = 'driver'");
                    $stmt->execute([$name, $email, $mobile, $id]);
                }
                
                $message = "Driver updated successfully!";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? null;
        
        try {
            $stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = ? AND role = 'driver'");
            $stmt->execute([$id]);
            $message = "Driver deactivated successfully!";
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Get all drivers
$stmt = $pdo->query("
    SELECT u.*, b.bus_number
    FROM users u
    LEFT JOIN buses b ON b.driver_id = u.id
    WHERE u.role = 'driver'
    ORDER BY u.created_at DESC
");
$drivers = $stmt->fetchAll();

// Get driver for editing
$editDriver = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'driver'");
    $stmt->execute([$_GET['edit']]);
    $editDriver = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Drivers - Admin</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="dashboard">
        <aside class="sidebar">
            <div class="sidebar-header"><h2>Admin Panel</h2></div>
            <nav class="sidebar-nav">
                <a href="/admin/dashboard.php" class="nav-item">Dashboard</a>
                <a href="/admin/drivers.php" class="nav-item active">Drivers</a>
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
                <h1>Manage Drivers</h1>
                <button onclick="showAddForm()" class="btn btn-primary">Add New Driver</button>
            </header>
            
            <div class="dashboard-content">
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <!-- Add/Edit Form -->
                <div id="driver-form" class="card mb-4" style="<?php echo ($editDriver || isset($_GET['action'])) ? '' : 'display:none;'; ?>">
                    <div class="card-header">
                        <h3><?php echo $editDriver ? 'Edit Driver' : 'Add New Driver'; ?></h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="form">
                            <input type="hidden" name="action" value="<?php echo $editDriver ? 'edit' : 'add'; ?>">
                            <?php if ($editDriver): ?>
                                <input type="hidden" name="id" value="<?php echo $editDriver['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="name">Full Name *</label>
                                    <input type="text" id="name" name="name" class="form-control" 
                                           value="<?php echo htmlspecialchars($editDriver['name'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">Email *</label>
                                    <input type="email" id="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($editDriver['email'] ?? ''); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="mobile">Mobile Number *</label>
                                    <input type="tel" id="mobile" name="mobile" class="form-control" 
                                           value="<?php echo htmlspecialchars($editDriver['mobile'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="password">Password <?php echo $editDriver ? '(leave blank to keep current)' : '*'; ?></label>
                                    <input type="password" id="password" name="password" class="form-control" 
                                           <?php echo $editDriver ? '' : 'required'; ?>>
                                </div>
                            </div>
                            
                            <?php if ($editDriver): ?>
                                <div class="form-group">
                                    <label>Driver Code</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($editDriver['driver_code']); ?>" disabled>
                                </div>
                            <?php endif; ?>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <?php echo $editDriver ? 'Update Driver' : 'Add Driver'; ?>
                                </button>
                                <button type="button" onclick="hideForm()" class="btn btn-secondary">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Drivers List -->
                <div class="card">
                    <div class="card-header">
                        <h3>All Drivers</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Mobile</th>
                                        <th>Driver Code</th>
                                        <th>Bus</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($drivers as $driver): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($driver['name']); ?></td>
                                            <td><?php echo htmlspecialchars($driver['email']); ?></td>
                                            <td><?php echo htmlspecialchars($driver['mobile']); ?></td>
                                            <td><code><?php echo htmlspecialchars($driver['driver_code']); ?></code></td>
                                            <td><?php echo htmlspecialchars($driver['bus_number'] ?? 'Not assigned'); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $driver['is_active'] ? 'success' : 'danger'; ?>">
                                                    <?php echo $driver['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="?edit=<?php echo $driver['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                                <?php if ($driver['is_active']): ?>
                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Deactivate this driver?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?php echo $driver['id']; ?>">
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
            document.getElementById('driver-form').style.display = 'block';
        }
        
        function hideForm() {
            window.location.href = '/admin/drivers.php';
        }
    </script>
</body>
</html>
