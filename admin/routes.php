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
        $route_name = $_POST['route_name'] ?? '';
        $bus_id = $_POST['bus_id'] ?? null;
        $start_location = $_POST['start_location'] ?? '';
        $end_location = $_POST['end_location'] ?? '';
        
        try {
            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO routes (route_name, bus_id, start_location, end_location) VALUES (?, ?, ?, ?)");
                $stmt->execute([$route_name, $bus_id ?: null, $start_location, $end_location]);
                $message = "Route added successfully!";
            } else {
                $stmt = $pdo->prepare("UPDATE routes SET route_name = ?, bus_id = ?, start_location = ?, end_location = ? WHERE id = ?");
                $stmt->execute([$route_name, $bus_id ?: null, $start_location, $end_location, $id]);
                $message = "Route updated successfully!";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? null;
        try {
            $stmt = $pdo->prepare("UPDATE routes SET is_active = 0 WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Route deactivated successfully!";
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

$stmt = $pdo->query("
    SELECT r.*, b.bus_number,
           (SELECT COUNT(*) FROM student_bus_assignments WHERE route_id = r.id AND is_active = 1) as student_count
    FROM routes r
    LEFT JOIN buses b ON b.id = r.bus_id
    ORDER BY r.route_name
");
$routes = $stmt->fetchAll();

$stmt = $pdo->query("SELECT id, bus_number FROM buses WHERE is_active = 1");
$buses = $stmt->fetchAll();

$editRoute = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM routes WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editRoute = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Routes - Admin</title>
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
                <a href="/admin/routes.php" class="nav-item active">Routes</a>
                <a href="/admin/trips.php" class="nav-item">Trip Reports</a>
                <a href="/admin/live-tracking.php" class="nav-item">Live Tracking</a>
                <a href="/logout.php" class="nav-item">Logout</a>
            </nav>
        </aside>
        
        <main class="main-content">
            <header class="content-header">
                <h1>Manage Routes</h1>
                <button onclick="showAddForm()" class="btn btn-primary">Add New Route</button>
            </header>
            
            <div class="dashboard-content">
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <div id="route-form" class="card mb-4" style="<?php echo ($editRoute || isset($_GET['action'])) ? '' : 'display:none;'; ?>">
                    <div class="card-header">
                        <h3><?php echo $editRoute ? 'Edit Route' : 'Add New Route'; ?></h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="form">
                            <input type="hidden" name="action" value="<?php echo $editRoute ? 'edit' : 'add'; ?>">
                            <?php if ($editRoute): ?>
                                <input type="hidden" name="id" value="<?php echo $editRoute['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="route_name">Route Name *</label>
                                    <input type="text" id="route_name" name="route_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($editRoute['route_name'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="bus_id">Assign Bus</label>
                                    <select id="bus_id" name="bus_id" class="form-control">
                                        <option value="">-- Select Bus --</option>
                                        <?php foreach ($buses as $bus): ?>
                                            <option value="<?php echo $bus['id']; ?>" 
                                                <?php echo ($editRoute && $editRoute['bus_id'] == $bus['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($bus['bus_number']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="start_location">Start Location</label>
                                    <input type="text" id="start_location" name="start_location" class="form-control" 
                                           value="<?php echo htmlspecialchars($editRoute['start_location'] ?? ''); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="end_location">End Location</label>
                                    <input type="text" id="end_location" name="end_location" class="form-control" 
                                           value="<?php echo htmlspecialchars($editRoute['end_location'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <?php echo $editRoute ? 'Update Route' : 'Add Route'; ?>
                                </button>
                                <button type="button" onclick="hideForm()" class="btn btn-secondary">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3>All Routes</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Route Name</th>
                                        <th>Bus</th>
                                        <th>Start Location</th>
                                        <th>End Location</th>
                                        <th>Students</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($routes as $route): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($route['route_name']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($route['bus_number'] ?? 'Not assigned'); ?></td>
                                            <td><?php echo htmlspecialchars($route['start_location']); ?></td>
                                            <td><?php echo htmlspecialchars($route['end_location']); ?></td>
                                            <td><?php echo $route['student_count']; ?> student(s)</td>
                                            <td>
                                                <span class="badge badge-<?php echo $route['is_active'] ? 'success' : 'danger'; ?>">
                                                    <?php echo $route['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="?edit=<?php echo $route['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                                <?php if ($route['is_active']): ?>
                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Deactivate this route?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?php echo $route['id']; ?>">
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
            document.getElementById('route-form').style.display = 'block';
        }
        function hideForm() {
            window.location.href = '/admin/routes.php';
        }
    </script>
</body>
</html>
