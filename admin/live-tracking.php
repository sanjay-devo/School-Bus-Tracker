<?php
require_once '../config.php';
require_once '../includes/auth.php';

requireLogin();
requireRole('admin');

// Get all active trips
$stmt = $pdo->query("
    SELECT 
        t.id as trip_id,
        t.trip_type,
        t.start_time,
        b.id as bus_id,
        b.bus_number,
        r.route_name,
        u.name as driver_name,
        u.mobile as driver_mobile
    FROM trips t
    JOIN buses b ON b.id = t.bus_id
    JOIN routes r ON r.id = t.route_id
    JOIN users u ON u.id = t.driver_id
    WHERE t.status = 'started'
    ORDER BY t.start_time DESC
");
$activeTrips = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Tracking - Admin</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAxn47G2g9sT3WUEMFEnX_qZB3s8wXkoGM&libraries=geometry"></script>
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
                <a href="/admin/trips.php" class="nav-item">Trip Reports</a>
                <a href="/admin/live-tracking.php" class="nav-item active">Live Tracking</a>
                <a href="/logout.php" class="nav-item">Logout</a>
            </nav>
        </aside>
        
        <main class="main-content">
            <header class="content-header">
                <h1>Live Bus Tracking</h1>
            </header>
            
            <div class="tracking-layout">
                <!-- Sidebar with trip list -->
                <div class="trip-list">
                    <h3>Active Trips (<?php echo count($activeTrips); ?>)</h3>
                    
                    <?php if (empty($activeTrips)): ?>
                        <p class="text-muted">No active trips</p>
                    <?php else: ?>
                        <?php foreach ($activeTrips as $trip): ?>
                            <div class="trip-card" onclick="selectTrip(<?php echo $trip['trip_id']; ?>)" id="trip-card-<?php echo $trip['trip_id']; ?>">
                                <div class="trip-header">
                                    <strong><?php echo htmlspecialchars($trip['bus_number']); ?></strong>
                                    <span class="badge badge-info"><?php echo ucfirst($trip['trip_type']); ?></span>
                                </div>
                                <div class="trip-info">
                                    <p><small>Driver: <?php echo htmlspecialchars($trip['driver_name']); ?></small></p>
                                    <p><small>Route: <?php echo htmlspecialchars($trip['route_name']); ?></small></p>
                                    <p><small>Started: <?php echo date('h:i A', strtotime($trip['start_time'])); ?></small></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Map container -->
                <div class="map-container">
                    <div class="map-controls">
                        <button onclick="toggleMapType()" class="btn btn-sm">Toggle View</button>
                        <button onclick="fitAllBuses()" class="btn btn-sm btn-primary">Fit All Buses</button>
                        <label>
                            <input type="checkbox" id="auto-refresh" checked> Auto-refresh (1s)
                        </label>
                    </div>
                    <div id="map" style="height: calc(100vh - 150px); width: 100%;"></div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        const trips = <?php echo json_encode($activeTrips); ?>;
        let map;
        let markers = {};
        let mapType = 'roadmap';
        let updateInterval;
        let selectedTripId = null;
        
        function initMap() {
            const defaultCenter = { lat: 28.6139, lng: 77.2090 };
            
            map = new google.maps.Map(document.getElementById('map'), {
                zoom: 12,
                center: defaultCenter,
                mapTypeId: 'roadmap'
            });
            
            // Create markers for all trips
            trips.forEach(trip => {
                const busIcon = {
                    url: '/assets/images/bus-marker.png',
                    scaledSize: new google.maps.Size(40, 40),
                    origin: new google.maps.Point(0, 0),
                    anchor: new google.maps.Point(20, 20)
                };
                
                const marker = new google.maps.Marker({
                    map: map,
                    title: trip.bus_number,
                    icon: busIcon,
                    optimized: false
                });
                
                const infoWindow = new google.maps.InfoWindow({
                    content: `
                        <div style="padding: 10px;">
                            <h4>${trip.bus_number}</h4>
                            <p><strong>Driver:</strong> ${trip.driver_name}</p>
                            <p><strong>Route:</strong> ${trip.route_name}</p>
                            <p><strong>Type:</strong> ${trip.trip_type}</p>
                            <p><strong>Contact:</strong> ${trip.driver_mobile}</p>
                        </div>
                    `
                });
                
                marker.addListener('click', () => {
                    Object.values(markers).forEach(m => m.infoWindow.close());
                    infoWindow.open(map, marker);
                    selectTrip(trip.trip_id);
                });
                
                markers[trip.trip_id] = {
                    marker: marker,
                    infoWindow: infoWindow,
                    position: null
                };
            });
            
            // Start auto-refresh
            startAutoRefresh();
        }
        
        function startAutoRefresh() {
            if (updateInterval) {
                clearInterval(updateInterval);
            }
            
            updateAllLocations();
            
            updateInterval = setInterval(() => {
                if (document.getElementById('auto-refresh').checked) {
                    updateAllLocations();
                }
            }, 1000);
        }
        
        function updateAllLocations() {
            trips.forEach(trip => {
                fetch(`/api/location.php?trip_id=${trip.trip_id}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.location) {
                            const newPosition = {
                                lat: parseFloat(data.location.latitude),
                                lng: parseFloat(data.location.longitude)
                            };
                            
                            const markerData = markers[trip.trip_id];
                            
                            if (markerData.position) {
                                animateMarker(markerData.marker, markerData.position, newPosition);
                            } else {
                                markerData.marker.setPosition(newPosition);
                            }
                            
                            markerData.position = newPosition;
                        }
                    })
                    .catch(error => console.error('Error fetching location:', error));
            });
        }
        
        function animateMarker(marker, start, end) {
            const duration = 1000;
            const steps = 60;
            const stepDuration = duration / steps;
            let step = 0;
            
            const deltaLat = (end.lat - start.lat) / steps;
            const deltaLng = (end.lng - start.lng) / steps;
            
            const animate = () => {
                if (step < steps) {
                    step++;
                    const newLat = start.lat + (deltaLat * step);
                    const newLng = start.lng + (deltaLng * step);
                    marker.setPosition({ lat: newLat, lng: newLng });
                    setTimeout(animate, stepDuration);
                }
            };
            
            animate();
        }
        
        function selectTrip(tripId) {
            selectedTripId = tripId;
            
            // Highlight selected trip card
            document.querySelectorAll('.trip-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            const card = document.getElementById('trip-card-' + tripId);
            if (card) {
                card.classList.add('selected');
            }
            
            // Center on selected bus
            const markerData = markers[tripId];
            if (markerData && markerData.position) {
                map.setCenter(markerData.position);
                map.setZoom(15);
                markerData.infoWindow.open(map, markerData.marker);
            }
        }
        
        function toggleMapType() {
            if (mapType === 'roadmap') {
                mapType = 'hybrid';
                map.setMapTypeId('hybrid');
            } else {
                mapType = 'roadmap';
                map.setMapTypeId('roadmap');
            }
        }
        
        function fitAllBuses() {
            const bounds = new google.maps.LatLngBounds();
            let hasPositions = false;
            
            Object.values(markers).forEach(markerData => {
                if (markerData.position) {
                    bounds.extend(markerData.position);
                    hasPositions = true;
                }
            });
            
            if (hasPositions) {
                map.fitBounds(bounds);
            }
        }
        
        window.addEventListener('load', initMap);
        
        window.addEventListener('beforeunload', () => {
            if (updateInterval) {
                clearInterval(updateInterval);
            }
        });
    </script>
</body>
</html>
