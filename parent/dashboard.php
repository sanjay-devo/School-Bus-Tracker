<?php
require_once '../config.php';
require_once '../includes/auth.php';

requireLogin();
requireRole('parent');

$userId = getUserId();

// Get parent's children and their bus assignments
$stmt = $pdo->prepare("
    SELECT 
        s.*,
        b.id as bus_id,
        b.bus_number,
        r.route_name,
        r.id as route_id,
        u.name as driver_name,
        u.mobile as driver_mobile,
        t.id as trip_id,
        t.status as trip_status,
        t.trip_status as trip_active,
        t.start_time
    FROM students s
    LEFT JOIN student_bus_assignments sba ON sba.student_id = s.id AND sba.is_active = 1
    LEFT JOIN buses b ON b.id = sba.bus_id
    LEFT JOIN routes r ON r.id = sba.route_id
    LEFT JOIN users u ON u.id = b.driver_id
    LEFT JOIN trips t ON t.bus_id = b.id AND t.trip_status = 1
    WHERE s.parent_id = ? AND s.is_active = 1
    ORDER BY s.student_name
");
$stmt->execute([$userId]);
$students = $stmt->fetchAll();

$selectedStudent = $students[0] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Dashboard - School Bus Tracker</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAxn47G2g9sT3WUEMFEnX_qZB3s8wXkoGM&libraries=geometry"></script>
    <style>
        /* 🔥 Enhanced Trip Status Box */
        .trip-status-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }
        
        .trip-status-box.status-inactive {
            background: linear-gradient(135deg, #868f96 0%, #596164 100%);
            animation: none;
        }
        
        .trip-status-box.status-active {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }
        
        .trip-status-box.status-ended {
            background: linear-gradient(135deg, #ee0979 0%, #ff6a00 100%);
        }
        
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
            animation: blink 1.5s infinite;
        }
        
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        
        .status-inactive .status-indicator { background: #ccc; animation: none; }
        .status-active .status-indicator { background: #00ff00; }
        .status-ended .status-indicator { background: #ff0000; }
        
        /* Enhanced Info Display */
        .info-display {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .info-card {
            background: rgba(255,255,255,0.2);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        
        .info-card-label {
            font-size: 12px;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        
        .info-card-value {
            font-size: 24px;
            font-weight: bold;
        }
        
        /* Map enhancements */
        .map-container {
            position: relative;
        }
        
        .map-overlay {
            position: absolute;
            top: 10px;
            right: 10px;
            background: white;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
            z-index: 1000;
        }
        
        .legend {
            font-size: 12px;
            line-height: 1.8;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }
        
        .legend-icon {
            width: 30px;
            height: 30px;
            margin-right: 8px;
        }
    </style>
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
                <a href="/parent/dashboard.php" class="nav-item active">
                    <span>📍 Live Tracking</span>
                </a>
                <a href="/parent/trip_history.php" class="nav-item">
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
                <h1>Welcome, <?php echo htmlspecialchars(getUserName()); ?></h1>
            </header>
            
            <div class="tracking-container">
                <?php if (empty($students)): ?>
                    <div class="card">
                        <div class="card-body text-center">
                            <p>No students assigned yet. Please contact the administrator.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Student Selection -->
                    <?php if (count($students) > 1): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <label for="student-select">Select Child:</label>
                            <select id="student-select" class="form-control" onchange="changeStudent()">
                                <?php foreach ($students as $index => $student): ?>
                                    <option value="<?php echo $index; ?>" <?php echo $index === 0 ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($student['student_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- 🔥 TRIP STATUS BOX with Auto-Refresh -->
                    <div class="trip-status-box status-inactive" id="trip-status-box">
                        <h3 style="margin: 0 0 10px 0;">
                            <span class="status-indicator"></span>
                            <span id="status-text">No Active Trip</span>
                        </h3>
                        <div class="info-display">
                            <div class="info-card">
                                <div class="info-card-label">Distance</div>
                                <div class="info-card-value" id="live-distance">-</div>
                            </div>
                            <div class="info-card">
                                <div class="info-card-label">ETA</div>
                                <div class="info-card-value" id="live-eta">-</div>
                            </div>
                            <div class="info-card">
                                <div class="info-card-label">Speed</div>
                                <div class="info-card-value" id="live-speed">-</div>
                            </div>
                            <div class="info-card">
                                <div class="info-card-label">Accuracy</div>
                                <div class="info-card-value" id="live-accuracy">-</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Student Info Card -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 id="student-name"><?php echo htmlspecialchars($selectedStudent['student_name']); ?></h3>
                        </div>
                        <div class="card-body">
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>Bus Number:</label>
                                    <span id="bus-number"><?php echo htmlspecialchars($selectedStudent['bus_number'] ?? 'Not assigned'); ?></span>
                                </div>
                                <div class="info-item">
                                    <label>Route:</label>
                                    <span id="route-name"><?php echo htmlspecialchars($selectedStudent['route_name'] ?? 'Not assigned'); ?></span>
                                </div>
                                <div class="info-item">
                                    <label>Driver:</label>
                                    <span id="driver-name"><?php echo htmlspecialchars($selectedStudent['driver_name'] ?? 'Not assigned'); ?></span>
                                </div>
                                <div class="info-item">
                                    <label>Driver Contact:</label>
                                    <span id="driver-mobile"><?php echo htmlspecialchars($selectedStudent['driver_mobile'] ?? '-'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 🔥 UPGRADED Map Container with Icons & Polyline -->
                    <div class="card">
                        <div class="card-header">
                            <h3>🗺️ Live Bus Tracking</h3>
                            <div class="map-controls">
                                <button onclick="toggleMapType()" class="btn btn-sm">🗺️ Toggle View</button>
                                <button onclick="centerOnBus()" class="btn btn-sm btn-primary">🚌 Center Bus</button>
                                <button onclick="centerOnMe()" class="btn btn-sm btn-success">📍 Center Me</button>
                                <button onclick="fitBothMarkers()" class="btn btn-sm btn-info">🔍 Fit Both</button>
                            </div>
                        </div>
                        <div class="card-body p-0 map-container">
                            <div id="map" style="height: 600px; width: 100%;"></div>
                            
                            <!-- Map Legend -->
                            <div class="map-overlay">
                                <div class="legend">
                                    <strong>Legend:</strong>
                                    <div class="legend-item">
                                        <span class="legend-icon">🚌</span>
                                        <span>School Bus</span>
                                    </div>
                                    <div class="legend-item">
                                        <span class="legend-icon">🏠</span>
                                        <span>Home</span>
                                    </div>
                                    <div class="legend-item">
                                        <span class="legend-icon">🏫</span>
                                        <span>School</span>
                                    </div>
                                    <div class="legend-item">
                                        <span class="legend-icon">📍</span>
                                        <span>Your Location</span>
                                    </div>
                                    <div class="legend-item">
                                        <span style="display:inline-block;width:30px;height:3px;background:#4285F4;margin-right:8px;"></span>
                                        <span>Route Path</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="tracking-info">
                                <div class="info-item">
                                    <label>Last Updated:</label>
                                    <span id="last-update">-</span>
                                </div>
                                <div class="info-item">
                                    <label>Trip ID:</label>
                                    <span id="current-trip-id">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script>
        // Student data from PHP
        const students = <?php echo json_encode($students); ?>;
        let currentStudentIndex = 0;
        let currentStudent = students[currentStudentIndex];
        
        // Map variables
        let map;
        let busMarker;
        let userMarker;
        let homeMarker;
        let schoolMarker;
        let routePolyline;
        let busPosition = null;
        let userPosition = null;
        let mapType = 'roadmap';
        let watchId = null;
        
        // Tracking variables
        let updateInterval;
        const UPDATE_FREQUENCY = 2000; // 🔥 2 seconds auto-refresh
        let lastBusHeading = 0;
        
        // 🔥 Initialize map with custom icons
        function initMap() {
            const defaultCenter = { lat: 28.6139, lng: 77.2090 }; // Delhi default
            
            map = new google.maps.Map(document.getElementById('map'), {
                zoom: 13,
                center: defaultCenter,
                mapTypeId: 'roadmap',
                styles: [
                    {
                        featureType: 'poi',
                        stylers: [{ visibility: 'off' }]
                    }
                ]
            });
            
            // 🔥 Create custom BUS icon (rotatable)
            busMarker = new google.maps.Marker({
                map: map,
                title: 'School Bus',
                icon: {
                    path: 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z',
                    fillColor: '#FFC107',
                    fillOpacity: 1,
                    strokeColor: '#FF6F00',
                    strokeWeight: 2,
                    scale: 2,
                    anchor: new google.maps.Point(12, 12),
                    rotation: 0
                },
                label: {
                    text: '🚌',
                    fontSize: '32px'
                },
                optimized: false
            });
            
            // 🔥 Create HOME marker
            if (currentStudent.home_latitude && currentStudent.home_longitude) {
                homeMarker = new google.maps.Marker({
                    map: map,
                    position: {
                        lat: parseFloat(currentStudent.home_latitude),
                        lng: parseFloat(currentStudent.home_longitude)
                    },
                    title: 'Home',
                    label: {
                        text: '🏠',
                        fontSize: '32px'
                    },
                    optimized: false
                });
            }
            
            // 🔥 Create SCHOOL marker (if available)
            // You can add school coordinates to your database
            
            // 🔥 Create user/parent marker
            userMarker = new google.maps.Marker({
                map: map,
                title: 'Your Location',
                label: {
                    text: '📍',
                    fontSize: '28px'
                },
                optimized: false
            });
            
            // 🔥 Create route polyline
            routePolyline = new google.maps.Polyline({
                map: map,
                strokeColor: '#4285F4',
                strokeOpacity: 0.8,
                strokeWeight: 4,
                geodesic: true
            });
            
            // Start tracking user location
            startUserLocationTracking();
            
            // 🔥 Start auto-refresh (NO manual OK button)
            if (currentStudent.trip_id) {
                startAutoRefresh();
            }
        }
        
        // Start tracking user location
        function startUserLocationTracking() {
            if (watchId !== null) {
                navigator.geolocation.clearWatch(watchId);
            }
            
            const options = {
                enableHighAccuracy: true,
                timeout: 5000,
                maximumAge: 0
            };
            
            watchId = navigator.geolocation.watchPosition(
                (position) => {
                    userPosition = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    
                    userMarker.setPosition(userPosition);
                    
                    // Calculate distance if bus position is available
                    if (busPosition) {
                        updateDistanceAndETA();
                    }
                },
                (error) => {
                    console.error('User location error:', error);
                    // Use home location as fallback
                    if (currentStudent.home_latitude && currentStudent.home_longitude) {
                        userPosition = {
                            lat: parseFloat(currentStudent.home_latitude),
                            lng: parseFloat(currentStudent.home_longitude)
                        };
                        userMarker.setPosition(userPosition);
                    }
                },
                options
            );
        }
        
        // 🔥 Start auto-refresh (every 2 seconds)
        function startAutoRefresh() {
            if (updateInterval) {
                clearInterval(updateInterval);
            }
            
            updateBusLocation();
            updateInterval = setInterval(updateBusLocation, UPDATE_FREQUENCY);
        }
        
        // 🔥 Update bus location and polyline
        function updateBusLocation() {
            if (!currentStudent.trip_id) {
                updateTripStatusBox(0, null);
                return;
            }
            
            fetch(`/api/location.php?trip_id=${currentStudent.trip_id}&t=${Date.now()}`, {
                cache: 'no-cache'
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.location) {
                        const newPosition = {
                            lat: parseFloat(data.location.latitude),
                            lng: parseFloat(data.location.longitude)
                        };
                        
                        // 🔥 Rotate bus icon based on heading
                        if (data.location.heading !== null && data.location.heading !== undefined) {
                            lastBusHeading = data.location.heading;
                        }
                        
                        // Smooth marker animation
                        if (busPosition) {
                            animateMarker(busMarker, busPosition, newPosition, lastBusHeading);
                        } else {
                            busMarker.setPosition(newPosition);
                        }
                        
                        busPosition = newPosition;
                        
                        // 🔥 Update polyline from database
                        if (data.polyline && data.polyline.length > 0) {
                            const path = data.polyline.map(point => ({
                                lat: point.lat,
                                lng: point.lng
                            }));
                            routePolyline.setPath(path);
                        }
                        
                        // 🔥 Update trip status box
                        updateTripStatusBox(data.trip_status, data.location);
                        
                        // Update last update time
                        document.getElementById('last-update').textContent = new Date(data.location.timestamp).toLocaleTimeString();
                        document.getElementById('current-trip-id').textContent = '#' + currentStudent.trip_id;
                        
                        // Calculate distance and ETA
                        if (userPosition) {
                            updateDistanceAndETA();
                        }
                        
                        // Auto-fit markers on first load
                        if (!map.getBounds() || map.getZoom() === 13) {
                            fitBothMarkers();
                        }
                    } else {
                        // Trip might have ended
                        updateTripStatusBox(0, null);
                    }
                })
                .catch(error => {
                    console.error('Error fetching location:', error);
                });
        }
        
        // 🔥 Smooth marker animation with rotation
        function animateMarker(marker, start, end, heading) {
            const duration = 1000;
            const steps = 30;
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
                    
                    // Rotate icon
                    if (heading !== null) {
                        const icon = marker.getIcon();
                        if (icon && typeof icon === 'object') {
                            icon.rotation = heading;
                            marker.setIcon(icon);
                        }
                    }
                    
                    setTimeout(animate, stepDuration);
                }
            };
            
            animate();
        }
        
        // 🔥 Update Trip Status Box
        function updateTripStatusBox(tripStatus, locationData) {
            const statusBox = document.getElementById('trip-status-box');
            const statusText = document.getElementById('status-text');
            
            if (tripStatus === 1) {
                // Trip Active
                statusBox.className = 'trip-status-box status-active';
                statusText.textContent = '🟢 Trip Running (Live)';
                
                if (locationData) {
                    // Update speed
                    const speedKmh = locationData.speed ? (locationData.speed * 3.6).toFixed(1) : '0';
                    document.getElementById('live-speed').textContent = speedKmh + ' km/h';
                    
                    // Update accuracy
                    const accuracy = locationData.accuracy ? locationData.accuracy.toFixed(0) : '-';
                    document.getElementById('live-accuracy').textContent = accuracy + ' m';
                }
            } else {
                // Trip Not Active
                statusBox.className = 'trip-status-box status-inactive';
                statusText.textContent = '⚪ No Active Trip';
                document.getElementById('live-distance').textContent = '-';
                document.getElementById('live-eta').textContent = '-';
                document.getElementById('live-speed').textContent = '-';
                document.getElementById('live-accuracy').textContent = '-';
                
                // Clear polyline
                routePolyline.setPath([]);
            }
        }
        
        // 🔥 Calculate distance and ETA
        function updateDistanceAndETA() {
            if (!busPosition || !userPosition) return;
            
            const busLatLng = new google.maps.LatLng(busPosition.lat, busPosition.lng);
            const userLatLng = new google.maps.LatLng(userPosition.lat, userPosition.lng);
            
            // Calculate straight-line distance
            const distance = google.maps.geometry.spherical.computeDistanceBetween(busLatLng, userLatLng);
            
            // Display distance
            if (distance < 1000) {
                document.getElementById('live-distance').textContent = distance.toFixed(0) + ' m';
            } else {
                document.getElementById('live-distance').textContent = (distance / 1000).toFixed(2) + ' km';
            }
            
            // Calculate ETA (simple estimation)
            const averageSpeed = 30; // km/h
            const etaMinutes = Math.round((distance / 1000) / averageSpeed * 60);
            
            if (etaMinutes < 1) {
                document.getElementById('live-eta').textContent = '< 1 min';
            } else if (etaMinutes < 60) {
                document.getElementById('live-eta').textContent = etaMinutes + ' min';
            } else {
                const hours = Math.floor(etaMinutes / 60);
                const mins = etaMinutes % 60;
                document.getElementById('live-eta').textContent = hours + 'h ' + mins + 'm';
            }
        }
        
        // Toggle map type
        function toggleMapType() {
            if (mapType === 'roadmap') {
                mapType = 'hybrid';
                map.setMapTypeId('hybrid');
            } else {
                mapType = 'roadmap';
                map.setMapTypeId('roadmap');
            }
        }
        
        // Center on bus
        function centerOnBus() {
            if (busPosition) {
                map.setCenter(busPosition);
                map.setZoom(16);
            }
        }
        
        // Center on user
        function centerOnMe() {
            if (userPosition) {
                map.setCenter(userPosition);
                map.setZoom(16);
            }
        }
        
        // Fit both markers in view
        function fitBothMarkers() {
            if (busPosition && userPosition) {
                const bounds = new google.maps.LatLngBounds();
                bounds.extend(busPosition);
                bounds.extend(userPosition);
                if (homeMarker) {
                    bounds.extend(homeMarker.getPosition());
                }
                map.fitBounds(bounds);
            } else if (busPosition) {
                centerOnBus();
            } else if (userPosition) {
                centerOnMe();
            }
        }
        
        // Change student
        function changeStudent() {
            const select = document.getElementById('student-select');
            currentStudentIndex = parseInt(select.value);
            currentStudent = students[currentStudentIndex];
            
            // Update UI
            document.getElementById('student-name').textContent = currentStudent.student_name;
            document.getElementById('bus-number').textContent = currentStudent.bus_number || 'Not assigned';
            document.getElementById('route-name').textContent = currentStudent.route_name || 'Not assigned';
            document.getElementById('driver-name').textContent = currentStudent.driver_name || 'Not assigned';
            document.getElementById('driver-mobile').textContent = currentStudent.driver_mobile || '-';
            
            // Reset map
            busPosition = null;
            busMarker.setPosition(null);
            routePolyline.setPath([]);
            
            // Update home marker
            if (homeMarker) {
                homeMarker.setMap(null);
            }
            if (currentStudent.home_latitude && currentStudent.home_longitude) {
                homeMarker = new google.maps.Marker({
                    map: map,
                    position: {
                        lat: parseFloat(currentStudent.home_latitude),
                        lng: parseFloat(currentStudent.home_longitude)
                    },
                    title: 'Home',
                    label: { text: '🏠', fontSize: '32px' }
                });
            }
            
            // Restart tracking
            if (updateInterval) {
                clearInterval(updateInterval);
            }
            
            if (currentStudent.trip_id) {
                startAutoRefresh();
            } else {
                updateTripStatusBox(0, null);
            }
        }
        
        // Initialize on page load
        window.addEventListener('load', initMap);
        
        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            if (watchId !== null) {
                navigator.geolocation.clearWatch(watchId);
            }
            if (updateInterval) {
                clearInterval(updateInterval);
            }
        });
        
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
