<?php
require_once '../config.php';
require_once '../includes/auth.php';

requireLogin();
requireRole('admin');

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $password = $_POST['password'] ?? '';

    try {
        if ($action === 'add' || $action === 'edit') {
            if ($action === 'add') {
                if (empty($password)) {
                    throw new InvalidArgumentException('Password is required for new sub-admin');
                }

                $hashedPassword = hashPassword($password);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, mobile, password, role) VALUES (?, ?, ?, ?, 'sub_admin')");
                $stmt->execute([$name, $email, $mobile, $hashedPassword]);
                $message = 'Sub-admin created successfully!';
            } else {
                if (!empty($password)) {
                    $hashedPassword = hashPassword($password);
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, mobile = ?, password = ? WHERE id = ? AND role = 'sub_admin'");
                    $stmt->execute([$name, $email, $mobile, $hashedPassword, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, mobile = ? WHERE id = ? AND role = 'sub_admin'");
                    $stmt->execute([$name, $email, $mobile, $id]);
                }
                $message = 'Sub-admin updated successfully!';
            }
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = ? AND role = 'sub_admin'");
            $stmt->execute([$id]);
            $message = 'Sub-admin deactivated successfully!';
        }
    } catch (InvalidArgumentException $e) {
        $error = $e->getMessage();
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

$stmt = $pdo->query("SELECT * FROM users WHERE role = 'sub_admin' ORDER BY created_at DESC");
$subAdmins = $stmt->fetchAll();

$editSubAdmin = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'sub_admin'");
    $stmt->execute([$_GET['edit']]);
    $editSubAdmin = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Sub Admins - Admin</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="dashboard">
        <aside class="sidebar">
            <div class="sidebar-header"><h2>Admin Panel</h2></div>
            <nav class="sidebar-nav">
                <a href="/admin/dashboard.php" class="nav-item">Dashboard</a>
                <a href="/admin/drivers.php" class="nav-item">Drivers</a>
                <a href="/admin/subadmins.php" class="nav-item active">Sub Admins</a>
                <a href="/admin/parents.php" class="nav-item">Parents</a>
                <a href="/admin/students.php" class="nav-item">Students</a>
                <a href="/admin/buses.php" class="nav-item">Buses</a>
                <a href="/admin/routes.php" class="nav-item">Routes</a>
                <a href="/admin/trips.php" class="nav-item">Trip Reports</a>
                <a href="/admin/live-tracking.php" class="nav-item">Live Tracking</a>
                <a href="/admin/profile.php" class="nav-item">Profile</a>
                <a href="/logout.php" class="nav-item">Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="content-header">
                <h1>Manage Sub Admins</h1>
                <button onclick="showAddForm()" class="btn btn-primary">Create Sub Admin</button>
            </header>

            <div class="dashboard-content">
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <div class="card mb-4" id="subadmin-form" style="<?php echo ($editSubAdmin || isset($_GET['action'])) ? '' : 'display:none;'; ?>">
                    <div class="card-header">
                        <h3><?php echo $editSubAdmin ? 'Edit Sub Admin' : 'Create Sub Admin'; ?></h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="form">
                            <input type="hidden" name="action" value="<?php echo $editSubAdmin ? 'edit' : 'add'; ?>">
                            <?php if ($editSubAdmin): ?>
                                <input type="hidden" name="id" value="<?php echo $editSubAdmin['id']; ?>">
                            <?php endif; ?>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="name">Full Name *</label>
                                    <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($editSubAdmin['name'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="email">Email *</label>
                                    <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($editSubAdmin['email'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="mobile">Mobile Number *</label>
                                    <input type="tel" id="mobile" name="mobile" class="form-control" value="<?php echo htmlspecialchars($editSubAdmin['mobile'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="password">Password <?php echo $editSubAdmin ? '(leave blank to keep current)' : '*'; ?></label>
                                    <input type="password" id="password" name="password" class="form-control" <?php echo $editSubAdmin ? '' : 'required'; ?>>
                                </div>
                            </div>

                            <p class="text-muted">Sub admins get the same access as main admins. Share credentials responsibly.</p>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary"><?php echo $editSubAdmin ? 'Update Sub Admin' : 'Create Sub Admin'; ?></button>
                                <button type="button" onclick="hideForm()" class="btn btn-secondary">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>All Sub Admins</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($subAdmins)): ?>
                            <p class="text-muted">No sub admins created yet.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Mobile</th>
                                            <th>Status</th>
                                            <th>Last Updated</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($subAdmins as $subAdmin): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($subAdmin['name']); ?></td>
                                                <td><?php echo htmlspecialchars($subAdmin['email']); ?></td>
                                                <td><?php echo htmlspecialchars($subAdmin['mobile']); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $subAdmin['is_active'] ? 'success' : 'danger'; ?>">
                                                        <?php echo $subAdmin['is_active'] ? 'Active' : 'Inactive'; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('d M Y, h:i A', strtotime($subAdmin['updated_at'])); ?></td>
                                                <td>
                                                    <a href="?edit=<?php echo $subAdmin['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                                    <?php if ($subAdmin['is_active']): ?>
                                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Deactivate this sub admin?');">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="id" value="<?php echo $subAdmin['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger">Deactivate</button>
                                                        </form>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function showAddForm() {
            document.getElementById('subadmin-form').style.display = 'block';
        }

        function hideForm() {
            window.location.href = '/admin/subadmins.php';
        }
    </script>
</body>
</html>
