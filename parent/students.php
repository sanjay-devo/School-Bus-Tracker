<?php
require_once '../config.php';
require_once '../includes/auth.php';

requireLogin();
requireRole('parent');

$userId = getUserId();
$error = '';
$success = '';

// Debug: Check if user ID exists
if (!$userId) {
    $error = 'Session error: User not logged in properly. Please login again.';
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_child') {
            $student_name = trim($_POST['student_name'] ?? '');
            $student_class = trim($_POST['student_class'] ?? '');
            $student_section = trim($_POST['student_section'] ?? '');
            $home_address = trim($_POST['home_address'] ?? '');
            $bus_id = $_POST['bus_id'] ?? '';
            $route_id = $_POST['route_id'] ?? '';
            
            if (empty($student_name) || empty($student_class)) {
                $error = 'Student name and class are required';
            } else {
                try {
                    // Insert the child first
                    $stmt = $pdo->prepare("
                        INSERT INTO students (parent_id, student_name, student_class, home_address, is_active, created_at) 
                        VALUES (?, ?, ?, ?, 1, NOW())
                    ");
                    $stmt->execute([$userId, $student_name, $student_class, $home_address]);
                    
                    // Get the inserted student ID
                    $student_id = $pdo->lastInsertId();
                    
                    // If bus and route are selected, create assignment
                    if (!empty($bus_id) && !empty($route_id)) {
                        $stmt = $pdo->prepare("
                            INSERT INTO student_bus_assignments (student_id, bus_id, route_id, is_active, created_at) 
                            VALUES (?, ?, ?, 1, NOW())
                        ");
                        $stmt->execute([$student_id, $bus_id, $route_id]);
                        $success = 'Child added and bus assigned successfully';
                    } else {
                        $success = 'Child added successfully. You can assign bus and route later.';
                    }
                } catch (PDOException $e) {
                    $error = 'Failed to add child. Error: ' . $e->getMessage();
                    error_log('Add Child Error: ' . $e->getMessage());
                }
            }
        } elseif ($_POST['action'] === 'assign_bus') {
            $student_id = $_POST['student_id'] ?? '';
            $bus_id = $_POST['bus_id'] ?? '';
            $route_id = $_POST['route_id'] ?? '';
            
            if (empty($student_id) || empty($bus_id) || empty($route_id)) {
                $error = 'Please select both bus and route';
            } else {
                try {
                    // Deactivate existing assignments
                    $stmt = $pdo->prepare("UPDATE student_bus_assignments SET is_active = 0 WHERE student_id = ?");
                    $stmt->execute([$student_id]);
                    
                    // Create new assignment
                    $stmt = $pdo->prepare("
                        INSERT INTO student_bus_assignments (student_id, bus_id, route_id, is_active, created_at) 
                        VALUES (?, ?, ?, 1, NOW())
                    ");
                    $stmt->execute([$student_id, $bus_id, $route_id]);
                    $success = 'Bus assignment updated successfully';
                } catch (PDOException $e) {
                    $error = 'Failed to assign bus. Please try again.';
                    error_log($e->getMessage());
                }
            }
        }
    }
}

// Get all children
$stmt = $pdo->prepare("
    SELECT 
        s.*,
        b.id as bus_id,
        b.bus_number,
        r.id as route_id,
        r.route_name,
        u.name as driver_name,
        u.mobile as driver_mobile
    FROM students s
    LEFT JOIN student_bus_assignments sba ON sba.student_id = s.id AND sba.is_active = 1
    LEFT JOIN buses b ON b.id = sba.bus_id
    LEFT JOIN routes r ON r.id = sba.route_id
    LEFT JOIN users u ON u.id = b.driver_id
    WHERE s.parent_id = ? AND s.is_active = 1
    ORDER BY s.student_name
");
$stmt->execute([$userId]);
$students = $stmt->fetchAll();

// Get available buses and routes for assignment
$buses_stmt = $pdo->prepare("SELECT b.*, u.name as driver_name FROM buses b LEFT JOIN users u ON u.id = b.driver_id WHERE b.is_active = 1 ORDER BY b.bus_number");
$buses_stmt->execute();
$buses = $buses_stmt->fetchAll();

$routes_stmt = $pdo->prepare("SELECT * FROM routes WHERE is_active = 1 ORDER BY route_name");
$routes_stmt->execute();
$routes = $routes_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Children - Parent</title>
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
                <a href="/parent/students.php" class="nav-item active">👨‍👩‍👧‍👦 Children</a>
                <a href="/parent/profile.php" class="nav-item">⚙️ Profile</a>
                <a href="/logout.php" class="nav-item">🚪 Logout</a>
            </nav>
        </aside>
        
        <main class="main-content">
            <header class="content-header">
                <h1>My Children</h1>
            </header>
            
            <div class="dashboard-content">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <!-- Add Child Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3>Add New Child</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="add_child">
                            
                            <!-- Child Information -->
                            <h4 style="margin-bottom: 15px; color: var(--primary-color);">👶 Child Information</h4>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="student_name">Child Name *</label>
                                    <input type="text" id="student_name" name="student_name" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="student_class">Class *</label>
                                    <input type="text" id="student_class" name="student_class" class="form-control" placeholder="e.g., 5th, 10th" required>
                                </div>
                                <!-- Section field removed as it's not supported by current database structure -->
                            </div>
                            <div class="form-group">
                                <label for="home_address">Home Address (Optional)</label>
                                <textarea id="home_address" name="home_address" class="form-control" rows="3" placeholder="Enter complete home address"></textarea>
                            </div>
                            
                            <!-- Bus Assignment -->
                            <h4 style="margin: 25px 0 15px 0; color: var(--primary-color);">🚌 Bus & Route Assignment (Optional)</h4>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="add_bus_id">Select Bus</label>
                                    <select name="bus_id" id="add_bus_id" class="form-control">
                                        <option value="">Choose Bus (Optional)</option>
                                        <?php foreach ($buses as $bus): ?>
                                            <option value="<?php echo $bus['id']; ?>">
                                                Bus <?php echo htmlspecialchars($bus['bus_number']); ?>
                                                <?php if ($bus['driver_name']): ?>
                                                    - Driver: <?php echo htmlspecialchars($bus['driver_name']); ?>
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="add_route_id">Select Route</label>
                                    <select name="route_id" id="add_route_id" class="form-control">
                                        <option value="">Choose Route (Optional)</option>
                                        <?php foreach ($routes as $route): ?>
                                            <option value="<?php echo $route['id']; ?>">
                                                <?php echo htmlspecialchars($route['route_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div style="background: #f8f9fa; padding: 10px; border-radius: 5px; margin: 15px 0; font-size: 14px; color: #666;">
                                <strong>Note:</strong> Bus and Route assignment is optional. You can assign them now or later from the child's card below.
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg">Add Child</button>
                        </form>
                    </div>
                </div>
                
                <!-- Children List -->
                <?php if (empty($students)): ?>
                    <div class="card">
                        <div class="card-body text-center">
                            <p>No children added yet. Use the form above to add your first child.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="stats-grid">
                        <?php foreach ($students as $student): ?>
                            <div class="card">
                                <div class="card-header">
                                    <h3><?php echo htmlspecialchars($student['student_name']); ?></h3>
                                </div>
                                <div class="card-body">
                                    <div class="info-grid">
                                        <div class="info-item">
                                            <label>Class:</label>
                                            <span><?php echo htmlspecialchars($student['student_class']); ?></span>
                                        </div>
                                        
                                        <!-- Section field not available in current database structure -->
                                        
                                        <div class="info-item">
                                            <label>Bus Number:</label>
                                            <span><?php echo htmlspecialchars($student['bus_number'] ?? 'Not assigned'); ?></span>
                                        </div>
                                        
                                        <div class="info-item">
                                            <label>Route:</label>
                                            <span><?php echo htmlspecialchars($student['route_name'] ?? 'Not assigned'); ?></span>
                                        </div>
                                        
                                        <div class="info-item">
                                            <label>Driver:</label>
                                            <span><?php echo htmlspecialchars($student['driver_name'] ?? 'Not assigned'); ?></span>
                                        </div>
                                        
                                        <div class="info-item">
                                            <label>Driver Contact:</label>
                                            <span><?php echo htmlspecialchars($student['driver_mobile'] ?? '-'); ?></span>
                                        </div>
                                    </div>
                                    
                                    <?php if ($student['home_address']): ?>
                                        <div class="mt-3">
                                            <label>Home Address:</label>
                                            <p><?php echo nl2br(htmlspecialchars($student['home_address'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Bus Assignment Form -->
                                    <div class="mt-3">
                                        <h4>Assign Bus & Route</h4>
                                        <form method="POST" class="mt-2">
                                            <input type="hidden" name="action" value="assign_bus">
                                            <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                            
                                            <div class="form-row">
                                                <div class="form-group">
                                                    <label for="bus_<?php echo $student['id']; ?>">Select Bus</label>
                                                    <select name="bus_id" id="bus_<?php echo $student['id']; ?>" class="form-control" required>
                                                        <option value="">Choose Bus</option>
                                                        <?php foreach ($buses as $bus): ?>
                                                            <option value="<?php echo $bus['id']; ?>" <?php echo ($bus['id'] == $student['bus_id']) ? 'selected' : ''; ?>>
                                                                Bus <?php echo htmlspecialchars($bus['bus_number']); ?>
                                                                <?php if ($bus['driver_name']): ?>
                                                                    - Driver: <?php echo htmlspecialchars($bus['driver_name']); ?>
                                                                <?php endif; ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="route_<?php echo $student['id']; ?>">Select Route</label>
                                                    <select name="route_id" id="route_<?php echo $student['id']; ?>" class="form-control" required>
                                                        <option value="">Choose Route</option>
                                                        <?php foreach ($routes as $route): ?>
                                                            <option value="<?php echo $route['id']; ?>" <?php echo ($route['id'] == $student['route_id']) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($route['route_name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <button type="submit" class="btn btn-success btn-sm">Update Assignment</button>
                                        </form>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <a href="/parent/dashboard.php" class="btn btn-primary btn-block">
                                            Track Bus Live
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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
        
        // Bus and Route validation for Add Child form
        document.addEventListener('DOMContentLoaded', function() {
            const addBusSelect = document.getElementById('add_bus_id');
            const addRouteSelect = document.getElementById('add_route_id');
            
            function validateBusRoute() {
                const busSelected = addBusSelect.value !== '';
                const routeSelected = addRouteSelect.value !== '';
                
                // If one is selected but not the other, show warning
                if ((busSelected && !routeSelected) || (!busSelected && routeSelected)) {
                    const warningDiv = document.getElementById('bus-route-warning');
                    if (!warningDiv) {
                        const warning = document.createElement('div');
                        warning.id = 'bus-route-warning';
                        warning.style.cssText = 'background: #fff3cd; color: #856404; padding: 8px; border-radius: 4px; margin-top: 10px; font-size: 14px;';
                        warning.innerHTML = '<strong>Note:</strong> Please select both Bus and Route, or leave both empty to assign later.';
                        addRouteSelect.parentNode.appendChild(warning);
                    }
                } else {
                    const warningDiv = document.getElementById('bus-route-warning');
                    if (warningDiv) {
                        warningDiv.remove();
                    }
                }
            }
            
            addBusSelect.addEventListener('change', validateBusRoute);
            addRouteSelect.addEventListener('change', validateBusRoute);
        });
    </script>
</body>
</html>
