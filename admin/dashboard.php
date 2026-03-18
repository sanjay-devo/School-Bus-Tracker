<?php
require_once '../config.php';
require_once '../includes/auth.php';

requireLogin();
requireRole('admin');

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'driver' AND is_active = 1");
$driverCount = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'parent' AND is_active = 1");
$parentCount = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM students WHERE is_active = 1");
$studentCount = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM buses WHERE is_active = 1");
$busCount = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM trips WHERE status = 'started'");
$activeTrips = $stmt->fetch()['count'];

// Get active trips with details
$stmt = $pdo->query("
    SELECT 
        t.id,
        t.trip_type,
        t.start_time,
        b.bus_number,
        r.route_name,
        u.name as driver_name
    FROM trips t
    JOIN buses b ON b.id = t.bus_id
    JOIN routes r ON r.id = t.route_id
    JOIN users u ON u.id = t.driver_id
    WHERE t.status = 'started'
    ORDER BY t.start_time DESC
");
$activeTripsList = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - School Bus Tracker</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAxn47G2g9sT3WUEMFEnX_qZB3s8wXkoGM&libraries=geometry"></script>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Admin Panel</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="/admin/dashboard.php" class="nav-item active">
                    <span>Dashboard</span>
                </a>
                <a href="/admin/drivers.php" class="nav-item">
                    <span>Drivers</span>
                </a>
                <a href="/admin/subadmins.php" class="nav-item">
                    <span>Sub Admins</span>
                </a>
                <a href="/admin/parents.php" class="nav-item">
                    <span>Parents</span>
                </a>
                <a href="/admin/students.php" class="nav-item">
                    <span>Students</span>
                </a>
                <a href="/admin/buses.php" class="nav-item">
                    <span>Buses</span>
                </a>
                <a href="/admin/routes.php" class="nav-item">
                    <span>Routes</span>
                </a>
                <a href="/admin/trips.php" class="nav-item">
                    <span>Trip Reports</span>
                </a>
                <a href="/admin/live-tracking.php" class="nav-item">
                    <span>Live Tracking</span>
                </a>
                <a href="/logout.php" class="nav-item">
                    <span>Logout</span>
                </a>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <h1>Admin Dashboard</h1>
                <div class="header-actions">
                    <span>Welcome, <?php echo htmlspecialchars(getUserName()); ?></span>
                </div>
            </header>
            
            <div class="dashboard-content">
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">👤</div>
                        <div class="stat-content">
                            <h3><?php echo $driverCount; ?></h3>
                            <p>Active Drivers</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">👨‍👩‍👧</div>
                        <div class="stat-content">
                            <h3><?php echo $parentCount; ?></h3>
                            <p>Parents</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">🎓</div>
                        <div class="stat-content">
                            <h3><?php echo $studentCount; ?></h3>
                            <p>Students</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">🚌</div>
                        <div class="stat-content">
                            <h3><?php echo $busCount; ?></h3>
                            <p>Buses</p>
                        </div>
                    </div>
                    
                    <div class="stat-card highlight">
                        <div class="stat-icon">🔴</div>
                        <div class="stat-content">
                            <h3><?php echo $activeTrips; ?></h3>
                            <p>Active Trips</p>
                        </div>
                    </div>
                </div>
                
                <!-- Active Trips -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h3>Active Trips</h3>
                        <a href="/admin/live-tracking.php" class="btn btn-primary btn-sm">View on Map</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($activeTripsList)): ?>
                            <p class="text-muted">No active trips at the moment.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Trip ID</th>
                                            <th>Bus</th>
                                            <th>Route</th>
                                            <th>Driver</th>
                                            <th>Type</th>
                                            <th>Started</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($activeTripsList as $trip): ?>
                                            <tr>
                                                <td>#<?php echo $trip['id']; ?></td>
                                                <td><?php echo htmlspecialchars($trip['bus_number']); ?></td>
                                                <td><?php echo htmlspecialchars($trip['route_name']); ?></td>
                                                <td><?php echo htmlspecialchars($trip['driver_name']); ?></td>
                                                <td><span class="badge badge-info"><?php echo ucfirst($trip['trip_type']); ?></span></td>
                                                <td><?php echo date('h:i A', strtotime($trip['start_time'])); ?></td>
                                                <td>
                                                    <a href="/admin/live-tracking.php?trip_id=<?php echo $trip['id']; ?>" class="btn btn-sm btn-primary">Track</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h3>Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions">
                            <a href="/admin/drivers.php?action=add" class="btn btn-success">Add Driver</a>
                            <a href="/admin/subadmins.php?action=add" class="btn btn-success">Create Sub Admin</a>
                            <a href="/admin/parents.php?action=add" class="btn btn-success">Add Parent</a>
                            <a href="/admin/students.php?action=add" class="btn btn-success">Add Student</a>
                            <a href="/admin/buses.php?action=add" class="btn btn-success">Add Bus</a>
                            <a href="/admin/routes.php?action=add" class="btn btn-success">Add Route</a>
                            <a href="/admin/trips.php?download=csv" class="btn btn-info">Download Reports</a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
