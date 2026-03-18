<?php
require_once '../config.php';
require_once '../includes/auth.php';

requireLogin();
requireRole('driver');

$userId = getUserId();

// Get driver's trip history
$stmt = $pdo->prepare("
    SELECT 
        t.*,
        b.bus_number,
        r.route_name,
        TIMESTAMPDIFF(MINUTE, t.start_time, t.end_time) as duration_minutes,
        (SELECT COUNT(*) FROM locations WHERE trip_id = t.id) as location_count
    FROM trips t
    JOIN buses b ON b.id = t.bus_id
    JOIN routes r ON r.id = t.route_id
    WHERE t.driver_id = ?
    ORDER BY t.start_time DESC
    LIMIT 50
");
$stmt->execute([$userId]);
$trips = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trip History - Driver</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="dashboard">
        <aside class="sidebar">
            <div class="sidebar-header"><h2>Driver Panel</h2></div>
            <nav class="sidebar-nav">
                <a href="/driver/dashboard.php" class="nav-item">Dashboard</a>
                <a href="/driver/trips.php" class="nav-item active">Trip History</a>
                <a href="/driver/profile.php" class="nav-item">Profile</a>
                <a href="/logout.php" class="nav-item">Logout</a>
            </nav>
        </aside>
        
        <main class="main-content">
            <header class="content-header">
                <h1>My Trip History</h1>
            </header>
            
            <div class="dashboard-content">
                <div class="card">
                    <div class="card-header">
                        <h3>All Trips (<?php echo count($trips); ?>)</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($trips)): ?>
                            <p class="text-muted">No trips recorded yet.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Trip ID</th>
                                            <th>Bus</th>
                                            <th>Route</th>
                                            <th>Type</th>
                                            <th>Start Time</th>
                                            <th>End Time</th>
                                            <th>Duration</th>
                                            <th>Locations</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($trips as $trip): ?>
                                            <tr>
                                                <td>#<?php echo $trip['id']; ?></td>
                                                <td><?php echo htmlspecialchars($trip['bus_number']); ?></td>
                                                <td><?php echo htmlspecialchars($trip['route_name']); ?></td>
                                                <td><span class="badge badge-info"><?php echo ucfirst($trip['trip_type']); ?></span></td>
                                                <td><?php echo date('M d, h:i A', strtotime($trip['start_time'])); ?></td>
                                                <td><?php echo $trip['end_time'] ? date('M d, h:i A', strtotime($trip['end_time'])) : '-'; ?></td>
                                                <td><?php echo $trip['duration_minutes'] ? $trip['duration_minutes'] . ' min' : '-'; ?></td>
                                                <td><?php echo $trip['location_count']; ?> points</td>
                                                <td>
                                                    <span class="badge badge-<?php 
                                                        echo $trip['status'] === 'completed' ? 'success' : 
                                                            ($trip['status'] === 'started' ? 'warning' : 'secondary'); 
                                                    ?>">
                                                        <?php echo ucfirst($trip['status']); ?>
                                                    </span>
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
</body>
</html>
