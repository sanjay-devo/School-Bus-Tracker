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
        $parent_id = $_POST['parent_id'] ?? '';
        $student_name = $_POST['student_name'] ?? '';
        $student_class = $_POST['student_class'] ?? '';
        $home_address = $_POST['home_address'] ?? '';
        $home_latitude = $_POST['home_latitude'] ?? null;
        $home_longitude = $_POST['home_longitude'] ?? null;
        $emergency_contact = $_POST['emergency_contact'] ?? '';
        $bus_id = $_POST['bus_id'] ?? null;
        $route_id = $_POST['route_id'] ?? null;
        
        try {
            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO students (parent_id, student_name, student_class, home_address, home_latitude, home_longitude, emergency_contact) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$parent_id, $student_name, $student_class, $home_address, $home_latitude ?: null, $home_longitude ?: null, $emergency_contact]);
                
                $student_id = $pdo->lastInsertId();
                
                // Assign to bus if selected
                if ($bus_id && $route_id) {
                    $stmt = $pdo->prepare("INSERT INTO student_bus_assignments (student_id, bus_id, route_id, assigned_date) VALUES (?, ?, ?, CURDATE())");
                    $stmt->execute([$student_id, $bus_id, $route_id]);
                }
                
                $message = "Student added successfully!";
            } else {
                $stmt = $pdo->prepare("UPDATE students SET parent_id = ?, student_name = ?, student_class = ?, home_address = ?, home_latitude = ?, home_longitude = ?, emergency_contact = ? WHERE id = ?");
                $stmt->execute([$parent_id, $student_name, $student_class, $home_address, $home_latitude ?: null, $home_longitude ?: null, $emergency_contact, $id]);
                
                // Update bus assignment
                $stmt = $pdo->prepare("UPDATE student_bus_assignments SET is_active = 0 WHERE student_id = ?");
                $stmt->execute([$id]);
                
                if ($bus_id && $route_id) {
                    $stmt = $pdo->prepare("INSERT INTO student_bus_assignments (student_id, bus_id, route_id, assigned_date) VALUES (?, ?, ?, CURDATE())");
                    $stmt->execute([$id, $bus_id, $route_id]);
                }
                
                $message = "Student updated successfully!";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

$stmt = $pdo->query("
    SELECT s.*, u.name as parent_name, u.email as parent_email, 
           b.bus_number, r.route_name
    FROM students s
    JOIN users u ON u.id = s.parent_id
    LEFT JOIN student_bus_assignments sba ON sba.student_id = s.id AND sba.is_active = 1
    LEFT JOIN buses b ON b.id = sba.bus_id
    LEFT JOIN routes r ON r.id = sba.route_id
    ORDER BY s.student_name
");
$students = $stmt->fetchAll();

$stmt = $pdo->query("SELECT id, name FROM users WHERE role = 'parent' AND is_active = 1");
$parents = $stmt->fetchAll();

$stmt = $pdo->query("SELECT id, bus_number FROM buses WHERE is_active = 1");
$buses = $stmt->fetchAll();

$stmt = $pdo->query("SELECT id, route_name FROM routes WHERE is_active = 1");
$routes = $stmt->fetchAll();

$editStudent = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("
        SELECT s.*, sba.bus_id, sba.route_id
        FROM students s
        LEFT JOIN student_bus_assignments sba ON sba.student_id = s.id AND sba.is_active = 1
        WHERE s.id = ?
    ");
    $stmt->execute([$_GET['edit']]);
    $editStudent = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - Admin</title>
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
                <a href="/admin/students.php" class="nav-item active">Students</a>
                <a href="/admin/buses.php" class="nav-item">Buses</a>
                <a href="/admin/routes.php" class="nav-item">Routes</a>
                <a href="/admin/trips.php" class="nav-item">Trip Reports</a>
                <a href="/admin/live-tracking.php" class="nav-item">Live Tracking</a>
                <a href="/logout.php" class="nav-item">Logout</a>
            </nav>
        </aside>
        
        <main class="main-content">
            <header class="content-header">
                <h1>Manage Students</h1>
                <button onclick="showAddForm()" class="btn btn-primary">Add New Student</button>
            </header>
            
            <div class="dashboard-content">
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <div id="student-form" class="card mb-4" style="<?php echo ($editStudent || isset($_GET['action'])) ? '' : 'display:none;'; ?>">
                    <div class="card-header">
                        <h3><?php echo $editStudent ? 'Edit Student' : 'Add New Student'; ?></h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="form">
                            <input type="hidden" name="action" value="<?php echo $editStudent ? 'edit' : 'add'; ?>">
                            <?php if ($editStudent): ?>
                                <input type="hidden" name="id" value="<?php echo $editStudent['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="student_name">Student Name *</label>
                                    <input type="text" id="student_name" name="student_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($editStudent['student_name'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="student_class">Class *</label>
                                    <input type="text" id="student_class" name="student_class" class="form-control" 
                                           value="<?php echo htmlspecialchars($editStudent['student_class'] ?? ''); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="parent_id">Parent *</label>
                                    <select id="parent_id" name="parent_id" class="form-control" required>
                                        <option value="">-- Select Parent --</option>
                                        <?php foreach ($parents as $parent): ?>
                                            <option value="<?php echo $parent['id']; ?>" 
                                                <?php echo ($editStudent && $editStudent['parent_id'] == $parent['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($parent['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="emergency_contact">Emergency Contact</label>
                                    <input type="tel" id="emergency_contact" name="emergency_contact" class="form-control" 
                                           value="<?php echo htmlspecialchars($editStudent['emergency_contact'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="home_address">Home Address</label>
                                <textarea id="home_address" name="home_address" class="form-control" rows="2"><?php echo htmlspecialchars($editStudent['home_address'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="home_latitude">Home Latitude</label>
                                    <input type="number" step="0.000001" id="home_latitude" name="home_latitude" class="form-control" 
                                           value="<?php echo htmlspecialchars($editStudent['home_latitude'] ?? ''); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="home_longitude">Home Longitude</label>
                                    <input type="number" step="0.000001" id="home_longitude" name="home_longitude" class="form-control" 
                                           value="<?php echo htmlspecialchars($editStudent['home_longitude'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="bus_id">Assign Bus</label>
                                    <select id="bus_id" name="bus_id" class="form-control">
                                        <option value="">-- Select Bus --</option>
                                        <?php foreach ($buses as $bus): ?>
                                            <option value="<?php echo $bus['id']; ?>" 
                                                <?php echo ($editStudent && $editStudent['bus_id'] == $bus['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($bus['bus_number']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="route_id">Assign Route</label>
                                    <select id="route_id" name="route_id" class="form-control">
                                        <option value="">-- Select Route --</option>
                                        <?php foreach ($routes as $route): ?>
                                            <option value="<?php echo $route['id']; ?>" 
                                                <?php echo ($editStudent && $editStudent['route_id'] == $route['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($route['route_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <?php echo $editStudent ? 'Update Student' : 'Add Student'; ?>
                                </button>
                                <button type="button" onclick="hideForm()" class="btn btn-secondary">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3>All Students</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Student Name</th>
                                        <th>Class</th>
                                        <th>Parent</th>
                                        <th>Bus</th>
                                        <th>Route</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($student['student_name']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($student['student_class']); ?></td>
                                            <td><?php echo htmlspecialchars($student['parent_name']); ?></td>
                                            <td><?php echo htmlspecialchars($student['bus_number'] ?? 'Not assigned'); ?></td>
                                            <td><?php echo htmlspecialchars($student['route_name'] ?? 'Not assigned'); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $student['is_active'] ? 'success' : 'danger'; ?>">
                                                    <?php echo $student['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="?edit=<?php echo $student['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
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
            document.getElementById('student-form').style.display = 'block';
        }
        function hideForm() {
            window.location.href = '/admin/students.php';
        }
    </script>
</body>
</html>
