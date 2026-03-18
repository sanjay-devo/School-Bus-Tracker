<?php
require_once '../config.php';
require_once '../includes/auth.php';

requireLogin();
requireRole('admin');

// Handle CSV download
if (isset($_GET['download']) && $_GET['download'] === 'csv') {
    $from_date = $_GET['from_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $to_date = $_GET['to_date'] ?? date('Y-m-d');
    
    $stmt = $pdo->prepare("
        SELECT 
            t.id,
            b.bus_number,
            r.route_name,
            u.name as driver_name,
            t.trip_type,
            t.start_time,
            t.end_time,
            t.status,
            TIMESTAMPDIFF(MINUTE, t.start_time, t.end_time) as duration_minutes
        FROM trips t
        JOIN buses b ON b.id = t.bus_id
        JOIN routes r ON r.id = t.route_id
        JOIN users u ON u.id = t.driver_id
        WHERE DATE(t.start_time) BETWEEN ? AND ?
        ORDER BY t.start_time DESC
    ");
    $stmt->execute([$from_date, $to_date]);
    $trips = $stmt->fetchAll();
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="trip_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Trip ID', 'Bus Number', 'Route', 'Driver', 'Type', 'Start Time', 'End Time', 'Duration (min)', 'Status']);
    
    foreach ($trips as $trip) {
        fputcsv($output, [
            $trip['id'],
            $trip['bus_number'],
            $trip['route_name'],
            $trip['driver_name'],
            ucfirst($trip['trip_type']),
            $trip['start_time'],
            $trip['end_time'] ?? 'N/A',
            $trip['duration_minutes'] ?? 'N/A',
            ucfirst($trip['status'])
        ]);
    }
    
    fclose($output);
    exit;
}

// Get trip history
$from_date = $_GET['from_date'] ?? date('Y-m-d', strtotime('-7 days'));
$to_date = $_GET['to_date'] ?? date('Y-m-d');

$stmt = $pdo->prepare("
    SELECT 
        t.*,
        b.bus_number,
        r.route_name,
        u.name as driver_name,
        TIMESTAMPDIFF(MINUTE, t.start_time, t.end_time) as duration_minutes,
        (SELECT COUNT(*) FROM locations WHERE trip_id = t.id) as location_count
    FROM trips t
    JOIN buses b ON b.id = t.bus_id
    JOIN routes r ON r.id = t.route_id
    JOIN users u ON u.id = t.driver_id
    WHERE DATE(t.start_time) BETWEEN ? AND ?
    ORDER BY t.start_time DESC
    LIMIT 100
");
$stmt->execute([$from_date, $to_date]);
$trips = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trip Reports - Admin</title>
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
                <a href="/admin/routes.php" class="nav-item">Routes</a>
                <a href="/admin/trips.php" class="nav-item active">Trip Reports</a>
                <a href="/admin/live-tracking.php" class="nav-item">Live Tracking</a>
                <a href="/logout.php" class="nav-item">Logout</a>
            </nav>
        </aside>
        
        <main class="main-content">
            <header class="content-header">
                <h1>Trip Reports</h1>
            </header>
            
            <div class="dashboard-content">
                <div class="card mb-4">
                    <div class="card-header">
                        <h3>Filter Trips</h3>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="from_date">From Date</label>
                                    <input type="date" id="from_date" name="from_date" class="form-control" 
                                           value="<?php echo $from_date; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="to_date">To Date</label>
                                    <input type="date" id="to_date" name="to_date" class="form-control" 
                                           value="<?php echo $to_date; ?>">
                                </div>
                                
                                <div class="form-group" style="display: flex; align-items: flex-end; gap: 10px;">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <a href="?download=csv&from_date=<?php echo $from_date; ?>&to_date=<?php echo $to_date; ?>" 
                                       class="btn btn-success">Download CSV</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3>Trip History (<?php echo count($trips); ?> trips)</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($trips)): ?>
                            <p class="text-muted">No trips found for the selected date range.</p>
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
                                                <td><?php echo htmlspecialchars($trip['driver_name']); ?></td>
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
