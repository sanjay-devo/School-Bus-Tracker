<?php
require_once '../config.php';
require_once '../includes/auth.php';

requireLogin();
requireRole('driver');

$userId = getUserId();

// Get driver's bus information
$stmt = $pdo->prepare("
    SELECT b.*, r.route_name, r.id as route_id
    FROM buses b
    LEFT JOIN routes r ON r.bus_id = b.id
    WHERE b.driver_id = ?
    LIMIT 1
");
$stmt->execute([$userId]);
$bus = $stmt->fetch();

// Get active trip if any
$stmt = $pdo->prepare("
    SELECT * FROM trips 
    WHERE driver_id = ? AND status = 'started'
    ORDER BY start_time DESC
    LIMIT 1
");
$stmt->execute([$userId]);
$activeTrip = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Dashboard - School Bus Tracker</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAxn47G2g9sT3WUEMFEnX_qZB3s8wXkoGM&libraries=geometry"></script>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Driver Panel</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="/driver/dashboard.php" class="nav-item active">
                    <span>Dashboard</span>
                </a>
                <a href="/driver/trips.php" class="nav-item">
                    <span>Trip History</span>
                </a>
                <a href="/driver/profile.php" class="nav-item">
                    <span>Profile</span>
                </a>
                <a href="/logout.php" class="nav-item">
                    <span>Logout</span>
                </a>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <h1>Welcome, <?php echo htmlspecialchars(getUserName()); ?></h1>
                <div class="header-actions">
                    <span class="status-badge" id="connection-status">Connected</span>
                </div>
            </header>
            
            <div class="dashboard-content">
                <!-- Bus Info Card -->
                <div class="card">
                    <div class="card-header">
                        <h3>Bus Information</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($bus): ?>
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>Bus Number:</label>
                                    <span><?php echo htmlspecialchars($bus['bus_number']); ?></span>
                                </div>
                                <div class="info-item">
                                    <label>Route:</label>
                                    <span><?php echo htmlspecialchars($bus['route_name'] ?? 'Not assigned'); ?></span>
                                </div>
                                <div class="info-item">
                                    <label>Capacity:</label>
                                    <span><?php echo htmlspecialchars($bus['capacity']); ?> students</span>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No bus assigned to you yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($bus): ?>
                <!-- Trip Controls -->
                <div class="card">
                    <div class="card-header">
                        <h3>Trip Controls</h3>
                        <span class="trip-status" id="trip-status">
                            <?php echo $activeTrip ? 'Trip Active' : 'No Active Trip'; ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div id="trip-controls">
                            <?php if (!$activeTrip): ?>
                                <div class="form-group">
                                    <label for="trip-type">Trip Type:</label>
                                    <select id="trip-type" class="form-control">
                                        <option value="pickup">Pickup</option>
                                        <option value="drop">Drop</option>
                                    </select>
                                </div>
                                <button onclick="startTrip()" class="btn btn-success btn-lg">
                                    Start Trip
                                </button>
                            <?php else: ?>
                                <div class="active-trip-info">
                                    <p><strong>Trip ID:</strong> #<?php echo $activeTrip['id']; ?></p>
                                    <p><strong>Started:</strong> <?php echo date('h:i A', strtotime($activeTrip['start_time'])); ?></p>
                                    <p><strong>Type:</strong> <?php echo ucfirst($activeTrip['trip_type']); ?></p>
                                    <p class="text-success">
                                        <span id="location-update-status">Location tracking active...</span>
                                    </p>
                                </div>
                                <button onclick="stopTrip()" class="btn btn-danger btn-lg">
                                    Stop Trip
                                </button>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mt-3">
                            <button onclick="sendSOS()" class="btn btn-warning btn-lg btn-block" id="sos-btn">
                                🚨 SOS Emergency
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- GPS Status -->
                <div class="card">
                    <div class="card-header">
                        <h3>GPS Status</h3>
                    </div>
                    <div class="card-body">
                        <div class="gps-status">
                            <div class="status-item">
                                <label>Latitude:</label>
                                <span id="current-lat">-</span>
                            </div>
                            <div class="status-item">
                                <label>Longitude:</label>
                                <span id="current-lng">-</span>
                            </div>
                            <div class="status-item">
                                <label>Accuracy:</label>
                                <span id="current-accuracy">-</span>
                            </div>
                            <div class="status-item">
                                <label>Speed:</label>
                                <span id="current-speed">-</span>
                            </div>
                            <div class="status-item">
                                <label>Last Update:</label>
                                <span id="last-update">-</span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script>
        let watchId = null;
        let activeTripId = <?php echo $activeTrip ? $activeTrip['id'] : 'null'; ?>;
        let isOnline = navigator.onLine;
        let locationQueue = [];
        const busId = <?php echo $bus ? $bus['id'] : 'null'; ?>;
        const routeId = <?php echo $bus ? $bus['route_id'] : 'null'; ?>;
        
        // IndexedDB for offline storage
        let db;
        const dbName = 'BusTrackerDB';
        const storeName = 'locations';
        
        // Initialize IndexedDB
        function initDB() {
            const request = indexedDB.open(dbName, 1);
            
            request.onerror = () => console.error('IndexedDB error');
            
            request.onsuccess = (event) => {
                db = event.target.result;
                syncOfflineData();
            };
            
            request.onupgradeneeded = (event) => {
                db = event.target.result;
                if (!db.objectStoreNames.contains(storeName)) {
                    const objectStore = db.createObjectStore(storeName, { keyPath: 'id', autoIncrement: true });
                    objectStore.createIndex('timestamp', 'timestamp', { unique: false });
                }
            };
        }
        
        // Save location to IndexedDB
        function saveToIndexedDB(location) {
            if (!db) return;
            
            const transaction = db.transaction([storeName], 'readwrite');
            const objectStore = transaction.objectStore(storeName);
            objectStore.add(location);
        }
        
        // Sync offline data when back online
        function syncOfflineData() {
            if (!db || !isOnline || !activeTripId) return;
            
            const transaction = db.transaction([storeName], 'readwrite');
            const objectStore = transaction.objectStore(storeName);
            const request = objectStore.getAll();
            
            request.onsuccess = () => {
                const locations = request.result;
                if (locations.length > 0) {
                    console.log(`Syncing ${locations.length} offline locations...`);
                    
                    locations.forEach(loc => {
                        sendLocationToServer(loc).then(() => {
                            // Remove synced location
                            const deleteTransaction = db.transaction([storeName], 'readwrite');
                            const deleteStore = deleteTransaction.objectStore(storeName);
                            deleteStore.delete(loc.id);
                        });
                    });
                }
            };
        }
        
        // Monitor online/offline status
        window.addEventListener('online', () => {
            isOnline = true;
            document.getElementById('connection-status').textContent = 'Connected';
            document.getElementById('connection-status').className = 'status-badge status-online';
            syncOfflineData();
        });
        
        window.addEventListener('offline', () => {
            isOnline = false;
            document.getElementById('connection-status').textContent = 'Offline';
            document.getElementById('connection-status').className = 'status-badge status-offline';
        });
        
        // Start location tracking using watchPosition (NO REPEATED PERMISSION PROMPTS)
        function startLocationTracking() {
            if (!activeTripId) return;
            
            // Stop any existing watch
            if (watchId !== null) {
                navigator.geolocation.clearWatch(watchId);
            }
            
            const options = {
                enableHighAccuracy: true,
                timeout: 5000,
                maximumAge: 0
            };
            
            // Use watchPosition for continuous tracking WITHOUT repeated permission prompts
            watchId = navigator.geolocation.watchPosition(
                (position) => {
                    const location = {
                        trip_id: activeTripId,
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy,
                        speed: position.coords.speed,
                        heading: position.coords.heading,
                        timestamp: new Date().toISOString()
                    };
                    
                    // Update UI
                    document.getElementById('current-lat').textContent = location.latitude.toFixed(6);
                    document.getElementById('current-lng').textContent = location.longitude.toFixed(6);
                    document.getElementById('current-accuracy').textContent = location.accuracy.toFixed(2) + ' m';
                    document.getElementById('current-speed').textContent = (location.speed || 0).toFixed(2) + ' m/s';
                    document.getElementById('last-update').textContent = new Date().toLocaleTimeString();
                    
                    // Ignore if accuracy is too low (> 30m)
                    if (location.accuracy > 30) {
                        document.getElementById('location-update-status').textContent = 'Waiting for better GPS signal...';
                        document.getElementById('location-update-status').className = 'text-warning';
                        return;
                    }
                    
                    document.getElementById('location-update-status').textContent = 'Location tracking active...';
                    document.getElementById('location-update-status').className = 'text-success';
                    
                    // Send to server or save offline
                    if (isOnline) {
                        sendLocationToServer(location);
                    } else {
                        saveToIndexedDB(location);
                    }
                },
                (error) => {
                    console.error('Geolocation error:', error);
                    let errorMsg = 'Location error';
                    
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMsg = 'Location permission denied';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMsg = 'Location unavailable';
                            break;
                        case error.TIMEOUT:
                            errorMsg = 'Location timeout';
                            break;
                    }
                    
                    document.getElementById('location-update-status').textContent = errorMsg;
                    document.getElementById('location-update-status').className = 'text-danger';
                },
                options
            );
        }
        
        // Send location to server via AJAX
        function sendLocationToServer(location) {
            return fetch('/api/location.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(location)
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    console.error('Location update failed:', data.message);
                }
            })
            .catch(error => {
                console.error('Network error:', error);
                if (db) {
                    saveToIndexedDB(location);
                }
            });
        }
        
        // Stop location tracking
        function stopLocationTracking() {
            if (watchId !== null) {
                navigator.geolocation.clearWatch(watchId);
                watchId = null;
            }
        }
        
        // 🔥 Start trip with GPS location
        function startTrip() {
            if (!busId || !routeId) {
                alert('Bus or route not assigned');
                return;
            }
            
            const tripType = document.getElementById('trip-type').value;
            
            // 🔥 Get current location before starting trip
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const formData = new FormData();
                    formData.append('action', 'start');
                    formData.append('bus_id', busId);
                    formData.append('route_id', routeId);
                    formData.append('trip_type', tripType);
                    formData.append('latitude', position.coords.latitude);
                    formData.append('longitude', position.coords.longitude);
                    
                    fetch('/api/trip.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            activeTripId = data.trip_id;
                            // 🔥 FIX: Update UI instantly without reload
                            updateTripUI('started');
                            startLocationTracking();
                            alert('Trip started successfully!');
                        } else {
                            alert('Failed to start trip: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Failed to start trip');
                    });
                },
                (error) => {
                    alert('Location permission required to start trip');
                }
            );
        }
        
        // 🔥 Stop trip with GPS location
        function stopTrip() {
            if (!activeTripId) return;
            
            if (!confirm('Are you sure you want to stop the trip?')) return;
            
            // 🔥 Stop GPS tracking immediately
            stopLocationTracking();
            
            // 🔥 Get current location for trip end
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const formData = new FormData();
                    formData.append('action', 'stop');
                    formData.append('trip_id', activeTripId);
                    formData.append('latitude', position.coords.latitude);
                    formData.append('longitude', position.coords.longitude);
                    
                    fetch('/api/trip.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // 🔥 FIX: Update UI instantly without reload
                            activeTripId = null;
                            updateTripUI('stopped');
                            alert('Trip stopped successfully!\n' + (data.total_distance || ''));
                        } else {
                            alert('Failed to stop trip: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Failed to stop trip');
                    });
                },
                (error) => {
                    // Even if location fails, still stop the trip
                    const formData = new FormData();
                    formData.append('action', 'stop');
                    formData.append('trip_id', activeTripId);
                    
                    fetch('/api/trip.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            activeTripId = null;
                            updateTripUI('stopped');
                            alert('Trip stopped successfully!');
                        } else {
                            alert('Failed to stop trip: ' + data.message);
                        }
                    });
                }
            );
        }
        
        // 🔥 Update UI instantly without page reload
        function updateTripUI(status) {
            const tripControls = document.getElementById('trip-controls');
            const tripStatus = document.getElementById('trip-status');
            
            if (status === 'started') {
                tripStatus.textContent = 'Trip Active';
                tripStatus.className = 'trip-status status-active';
                
                tripControls.innerHTML = `
                    <div class="active-trip-info">
                        <p><strong>Trip ID:</strong> #${activeTripId}</p>
                        <p><strong>Started:</strong> ${new Date().toLocaleTimeString()}</p>
                        <p class="text-success">
                            <span id="location-update-status">Location tracking active...</span>
                        </p>
                    </div>
                    <button onclick="stopTrip()" class="btn btn-danger btn-lg">
                        Stop Trip
                    </button>
                    <div class="mt-3">
                        <button onclick="sendSOS()" class="btn btn-warning btn-lg btn-block" id="sos-btn">
                            🚨 SOS Emergency
                        </button>
                    </div>
                `;
            } else if (status === 'stopped') {
                tripStatus.textContent = 'No Active Trip';
                tripStatus.className = 'trip-status status-inactive';
                
                tripControls.innerHTML = `
                    <div class="form-group">
                        <label for="trip-type">Trip Type:</label>
                        <select id="trip-type" class="form-control">
                            <option value="pickup">Pickup</option>
                            <option value="drop">Drop</option>
                        </select>
                    </div>
                    <button onclick="startTrip()" class="btn btn-success btn-lg">
                        Start Trip
                    </button>
                `;
                
                // Clear GPS status
                document.getElementById('current-lat').textContent = '-';
                document.getElementById('current-lng').textContent = '-';
                document.getElementById('current-accuracy').textContent = '-';
                document.getElementById('current-speed').textContent = '-';
                document.getElementById('last-update').textContent = '-';
            }
        }
        
        // Send SOS
        function sendSOS() {
            if (!confirm('Send SOS emergency alert to admin?')) return;
            
            const sosBtn = document.getElementById('sos-btn');
            sosBtn.disabled = true;
            sosBtn.textContent = 'Sending SOS...';
            
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const formData = new FormData();
                    formData.append('action', 'sos');
                    formData.append('latitude', position.coords.latitude);
                    formData.append('longitude', position.coords.longitude);
                    
                    fetch('/api/trip.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('SOS alert sent successfully!');
                        } else {
                            alert('Failed to send SOS: ' + data.message);
                        }
                    })
                    .finally(() => {
                        sosBtn.disabled = false;
                        sosBtn.textContent = '🚨 SOS Emergency';
                    });
                },
                (error) => {
                    alert('Failed to get location for SOS');
                    sosBtn.disabled = false;
                    sosBtn.textContent = '🚨 SOS Emergency';
                }
            );
        }
        
        // 🔥 Check trip status every 2 seconds (in case of browser refresh)
        function checkTripStatus() {
            if (!activeTripId) return;
            
            fetch('/api/trip.php?action=active', {
                cache: 'no-cache'
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success || !data.trip) {
                    // Trip was stopped elsewhere, update UI
                    if (activeTripId) {
                        activeTripId = null;
                        stopLocationTracking();
                        updateTripUI('stopped');
                    }
                }
            })
            .catch(error => {
                console.error('Trip status check error:', error);
            });
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            initDB();
            
            // Start tracking if trip is active
            if (activeTripId) {
                startLocationTracking();
                // Check trip status periodically
                setInterval(checkTripStatus, 2000);
            }
        });
        
        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            stopLocationTracking();
        });
    </script>
</body>
</html>
