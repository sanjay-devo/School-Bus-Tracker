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
        $bus_number = $_POST['bus_number'] ?? '';
        $driver_id = $_POST['driver_id'] ?? null;
        $capacity = $_POST['capacity'] ?? 0;
        $model = $_POST['model'] ?? '';
        $registration_number = $_POST['registration_number'] ?? '';
        
        try {
            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO buses (bus_number, driver_id, capacity, model, registration_number) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$bus_number, $driver_id ?: null, $capacity, $model, $registration_number]);
                $message = "Bus added successfully!";
            } else {
                $stmt = $pdo->prepare("UPDATE buses SET bus_number = ?, driver_id = ?, capacity = ?, model = ?, registration_number = ? WHERE id = ?");
                $stmt->execute([$bus_number, $driver_id ?: null, $capacity, $model, $registration_number, $id]);
                $message = "Bus updated successfully!";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? null;
        try {
            $stmt = $pdo->prepare("UPDATE buses SET is_active = 0 WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Bus deactivated successfully!";
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Get all buses
$stmt = $pdo->query("
    SELECT b.*, u.name as driver_name, r.route_name
    FROM buses b
    LEFT JOIN users u ON u.id = b.driver_id
    LEFT JOIN routes r ON r.bus_id = b.id
    ORDER BY b.bus_number
");
$buses = $stmt->fetchAll();

// Get available drivers
$stmt = $pdo->query("SELECT id, name FROM users WHERE role = 'driver' AND is_active = 1");
$drivers = $stmt->fetchAll();

$editBus = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM buses WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editBus = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Buses - Admin</title>
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
                <a href="/admin/buses.php" class="nav-item active">Buses</a>
                <a href="/admin/routes.php" class="nav-item">Routes</a>
                <a href="/admin/trips.php" class="nav-item">Trip Reports</a>
                <a href="/admin/live-tracking.php" class="nav-item">Live Tracking</a>
                <a href="/logout.php" class="nav-item">Logout</a>
            </nav>
        </aside>
        
        <main class="main-content">
            <header class="content-header">
                <h1>Manage Buses</h1>
                <button onclick="showAddForm()" class="btn btn-primary">Add New Bus</button>
            </header>
            
            <div class="dashboard-content">
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <div id="bus-form" class="card mb-4" style="<?php echo ($editBus || isset($_GET['action'])) ? '' : 'display:none;'; ?>">
                    <div class="card-header">
                        <h3><?php echo $editBus ? 'Edit Bus' : 'Add New Bus'; ?></h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="form">
                            <input type="hidden" name="action" value="<?php echo $editBus ? 'edit' : 'add'; ?>">
                            <?php if ($editBus): ?>
                                <input type="hidden" name="id" value="<?php echo $editBus['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="bus_number">Bus Number *</label>
                                    <input type="text" id="bus_number" name="bus_number" class="form-control" 
                                           value="<?php echo htmlspecialchars($editBus['bus_number'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="registration_number">Registration Number</label>
                                    <input type="text" id="registration_number" name="registration_number" class="form-control" 
                                           value="<?php echo htmlspecialchars($editBus['registration_number'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="driver_id">Assign Driver</label>
                                    <select id="driver_id" name="driver_id" class="form-control">
                                        <option value="">-- Select Driver --</option>
                                        <?php foreach ($drivers as $driver): ?>
                                            <option value="<?php echo $driver['id']; ?>" 
                                                <?php echo ($editBus && $editBus['driver_id'] == $driver['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($driver['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="capacity">Capacity *</label>
                                    <input type="number" id="capacity" name="capacity" class="form-control" 
                                           value="<?php echo htmlspecialchars($editBus['capacity'] ?? ''); ?>" required min="1">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="model">Bus Model</label>
                                <input type="text" id="model" name="model" class="form-control" 
                                       value="<?php echo htmlspecialchars($editBus['model'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <?php echo $editBus ? 'Update Bus' : 'Add Bus'; ?>
                                </button>
                                <button type="button" onclick="hideForm()" class="btn btn-secondary">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3>All Buses</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Bus Number</th>
                                        <th>Registration</th>
                                        <th>Driver</th>
                                        <th>Route</th>
                                        <th>Capacity</th>
                                        <th>Model</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($buses as $bus): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($bus['bus_number']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($bus['registration_number']); ?></td>
                                            <td><?php echo htmlspecialchars($bus['driver_name'] ?? 'Not assigned'); ?></td>
                                            <td><?php echo htmlspecialchars($bus['route_name'] ?? 'Not assigned'); ?></td>
                                            <td><?php echo htmlspecialchars($bus['capacity']); ?></td>
                                            <td><?php echo htmlspecialchars($bus['model']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $bus['is_active'] ? 'success' : 'danger'; ?>">
                                                    <?php echo $bus['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="?edit=<?php echo $bus['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                                <?php if ($bus['is_active']): ?>
                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Deactivate this bus?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?php echo $bus['id']; ?>">
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
            document.getElementById('bus-form').style.display = 'block';
        }
        
        function hideForm() {
            window.location.href = '/admin/buses.php';
        }
    </script>
</body>
</html>
