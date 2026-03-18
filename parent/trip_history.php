<?php
require_once '../config.php';
require_once '../includes/auth.php';

requireLogin();
requireRole('parent');

$userId = getUserId();

// Get all completed trips for parent's children
$stmt = $pdo->prepare("
    SELECT 
        t.*,
        s.student_name,
        b.bus_number,
        r.route_name,
        u.name as driver_name,
        (SELECT COUNT(*) FROM locations WHERE trip_id = t.id) as location_count
    FROM trips t
    JOIN buses b ON b.id = t.bus_id
    JOIN routes r ON r.id = t.route_id
    JOIN users u ON u.id = t.driver_id
    JOIN student_bus_assignments sba ON sba.bus_id = b.id AND sba.is_active = 1
    JOIN students s ON s.id = sba.student_id
    WHERE s.parent_id = ? 
    AND t.status = 'completed'
    ORDER BY t.start_time DESC
    LIMIT 100
");
$stmt->execute([$userId]);
$trips = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trip History - School Bus Tracker</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .trip-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        
        .trip-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .trip-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .trip-id {
            font-size: 18px;
            font-weight: bold;
            color: #2196F3;
        }
        
        .trip-date {
            color: #666;
            font-size: 14px;
        }
        
        .trip-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        
        .detail-label {
            font-size: 12px;
            color: #888;
            margin-bottom: 5px;
        }
        
        .detail-value {
            font-size: 14px;
            font-weight: 500;
            color: #333;
        }
        
        .trip-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-view {
            padding: 8px 16px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-view:hover {
            background: #45a049;
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 1000px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #000;
        }
    </style>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAxn47G2g9sT3WUEMFEnX_qZB3s8wXkoGM&libraries=geometry"></script>
</head>
<body>
    <!-- Mobile Navigation Toggle -->
    <button class="mobile-nav-toggle" onclick="toggleMobileNav()">☰</button>
    
    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>Parent Panel</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="/parent/dashboard.php" class="nav-item">
                    <span>📍 Live Tracking</span>
                </a>
                <a href="/parent/trip_history.php" class="nav-item active">
                    <span>📋 Trip History</span>
                </a>
                <a href="/parent/students.php" class="nav-item">
                    <span>👨‍👩‍👧‍👦 Children</span>
                </a>
                <a href="/parent/profile.php" class="nav-item">
                    <span>⚙️ Profile</span>
                </a>
                <a href="/logout.php" class="nav-item">
                    <span>🚪 Logout</span>
                </a>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <h1>📋 Trip History</h1>
            </header>
            
            <?php if (empty($trips)): ?>
                <div class="card">
                    <div class="card-body text-center">
                        <p>No trip history available yet.</p>
                    </div>
                </div>
            <?php else: ?>
                <!-- Statistics Card -->
                <div class="stats-card">
                    <h3>📊 Trip Statistics</h3>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo count($trips); ?></div>
                            <div class="stat-label">Total Trips</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">
                                <?php 
                                $totalDist = array_sum(array_column($trips, 'total_distance'));
                                echo number_format($totalDist, 1);
                                ?>
                            </div>
                            <div class="stat-label">Total KM</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">
                                <?php 
                                $maxSpeed = max(array_column($trips, 'max_speed'));
                                echo number_format($maxSpeed * 3.6, 0);
                                ?>
                            </div>
                            <div class="stat-label">Max Speed (km/h)</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">
                                <?php 
                                $totalDuration = 0;
                                foreach ($trips as $trip) {
                                    if ($trip['end_time']) {
                                        $start = strtotime($trip['start_time']);
                                        $end = strtotime($trip['end_time']);
                                        $totalDuration += ($end - $start);
                                    }
                                }
                                echo number_format($totalDuration / 3600, 1);
                                ?>
                            </div>
                            <div class="stat-label">Total Hours</div>
                        </div>
                    </div>
                </div>
                
                <!-- Trip Cards -->
                <?php foreach ($trips as $trip): ?>
                <div class="trip-card">
                    <div class="trip-header">
                        <div>
                            <div class="trip-id">Trip #<?php echo $trip['id']; ?></div>
                            <div class="trip-date">
                                <?php echo date('F d, Y', strtotime($trip['start_time'])); ?>
                            </div>
                        </div>
                        <div>
                            <span class="badge" style="background: <?php echo $trip['trip_type'] === 'pickup' ? '#4CAF50' : '#2196F3'; ?>; color: white; padding: 5px 15px; border-radius: 20px;">
                                <?php echo ucfirst($trip['trip_type']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="trip-details">
                        <div class="detail-item">
                            <div class="detail-label">Student</div>
                            <div class="detail-value">👨‍🎓 <?php echo htmlspecialchars($trip['student_name']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Bus Number</div>
                            <div class="detail-value">🚌 <?php echo htmlspecialchars($trip['bus_number']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Driver</div>
                            <div class="detail-value">👨‍✈️ <?php echo htmlspecialchars($trip['driver_name']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Start Time</div>
                            <div class="detail-value">🕐 <?php echo date('h:i A', strtotime($trip['start_time'])); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">End Time</div>
                            <div class="detail-value">
                                🕐 <?php echo $trip['end_time'] ? date('h:i A', strtotime($trip['end_time'])) : 'N/A'; ?>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Duration</div>
                            <div class="detail-value">
                                ⏱️ <?php 
                                if ($trip['end_time']) {
                                    $start = strtotime($trip['start_time']);
                                    $end = strtotime($trip['end_time']);
                                    $duration = $end - $start;
                                    echo gmdate('H:i', $duration);
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Distance</div>
                            <div class="detail-value">
                                📏 <?php echo $trip['total_distance'] ? number_format($trip['total_distance'], 2) . ' km' : 'N/A'; ?>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Max Speed</div>
                            <div class="detail-value">
                                ⚡ <?php echo $trip['max_speed'] ? number_format($trip['max_speed'] * 3.6, 0) . ' km/h' : 'N/A'; ?>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">GPS Points</div>
                            <div class="detail-value">📍 <?php echo $trip['location_count']; ?></div>
                        </div>
                    </div>
                    
                    <div class="trip-actions">
                        <button class="btn-view" onclick="viewTripOnMap(<?php echo $trip['id']; ?>)">
                            🗺️ View Route on Map
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
    </div>
    
    <!-- Trip Map Modal -->
    <div id="tripModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Trip Route</h2>
            <div id="modalMap" style="height: 500px; width: 100%; margin-top: 20px;"></div>
            <div id="modalInfo" style="margin-top: 20px;"></div>
        </div>
    </div>
    
    <script>
        let modalMap;
        let modalPolyline;
        
        function viewTripOnMap(tripId) {
            // Fetch trip details
            fetch(`/api/trip_details.php?trip_id=${tripId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showTripOnMap(data.trip, data.locations);
                    } else {
                        alert('Failed to load trip details');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to load trip details');
                });
        }
        
        function showTripOnMap(trip, locations) {
            const modal = document.getElementById('tripModal');
            modal.style.display = 'block';
            
            // Update modal title
            document.getElementById('modalTitle').textContent = `Trip #${trip.id} - ${trip.trip_type.toUpperCase()}`;
            
            // Initialize map
            if (!modalMap) {
                modalMap = new google.maps.Map(document.getElementById('modalMap'), {
                    zoom: 13,
                    center: { lat: 28.6139, lng: 77.2090 }
                });
                
                modalPolyline = new google.maps.Polyline({
                    map: modalMap,
                    strokeColor: '#4285F4',
                    strokeOpacity: 0.8,
                    strokeWeight: 4,
                    geodesic: true
                });
            }
            
            // Create path from locations
            if (locations && locations.length > 0) {
                const path = locations.map(loc => ({
                    lat: parseFloat(loc.latitude),
                    lng: parseFloat(loc.longitude)
                }));
                
                modalPolyline.setPath(path);
                
                // Add start marker
                new google.maps.Marker({
                    map: modalMap,
                    position: path[0],
                    label: {
                        text: '🟢',
                        fontSize: '24px'
                    },
                    title: 'Start'
                });
                
                // Add end marker
                if (path.length > 1) {
                    new google.maps.Marker({
                        map: modalMap,
                        position: path[path.length - 1],
                        label: {
                            text: '🔴',
                            fontSize: '24px'
                        },
                        title: 'End'
                    });
                }
                
                // Fit bounds
                const bounds = new google.maps.LatLngBounds();
                path.forEach(point => bounds.extend(point));
                modalMap.fitBounds(bounds);
            }
            
            // Update info
            const info = `
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
                    <div>
                        <strong>Start:</strong> ${new Date(trip.start_time).toLocaleString()}
                    </div>
                    <div>
                        <strong>End:</strong> ${trip.end_time ? new Date(trip.end_time).toLocaleString() : 'N/A'}
                    </div>
                    <div>
                        <strong>Distance:</strong> ${trip.total_distance ? trip.total_distance + ' km' : 'N/A'}
                    </div>
                </div>
            `;
            document.getElementById('modalInfo').innerHTML = info;
        }
        
        function closeModal() {
            document.getElementById('tripModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('tripModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
        
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
    </script>
</body>
</html>
