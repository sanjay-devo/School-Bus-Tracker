<?php
// ⚠️ CRITICAL: Prevent caching to fix UI update bugs
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: -1');

require_once '../config.php';
require_once '../includes/auth.php';

// Handle CORS if needed
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

$response = ['success' => false, 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update location from driver
    requireLogin();
    requireRole('driver');
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $trip_id = $data['trip_id'] ?? null;
    $latitude = $data['latitude'] ?? null;
    $longitude = $data['longitude'] ?? null;
    $accuracy = $data['accuracy'] ?? null;
    $speed = $data['speed'] ?? null;
    $heading = $data['heading'] ?? null;
    
    // Validate GPS accuracy (ignore if > 30m)
    if ($accuracy && $accuracy > 30) {
        $response = ['success' => false, 'message' => 'GPS accuracy too low'];
        echo json_encode($response);
        exit;
    }
    
    if ($trip_id && $latitude && $longitude) {
        try {
            // 🔥 FIX 1: Verify trip belongs to this driver and is ACTIVE (trip_status = 1)
            $stmt = $pdo->prepare("
                SELECT id, polyline 
                FROM trips 
                WHERE id = ? 
                AND driver_id = ? 
                AND trip_status = 1
            ");
            $stmt->execute([$trip_id, getUserId()]);
            $trip = $stmt->fetch();
            
            if ($trip) {
                // 🔥 FIX 2: Insert location
                $stmt = $pdo->prepare("
                    INSERT INTO locations (trip_id, latitude, longitude, accuracy, speed, heading) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$trip_id, $latitude, $longitude, $accuracy, $speed, $heading]);
                
                // 🔥 FIX 3: Update polyline for route drawing
                $polyline = [];
                if (!empty($trip['polyline'])) {
                    $polyline = json_decode($trip['polyline'], true);
                    if (!is_array($polyline)) {
                        $polyline = [];
                    }
                }
                
                // Add new point to polyline
                $newPoint = [
                    'lat' => (float)$latitude,
                    'lng' => (float)$longitude,
                    'timestamp' => time()
                ];
                
                // Only add if significantly different from last point (to reduce data size)
                $shouldAdd = true;
                if (count($polyline) > 0) {
                    $lastPoint = end($polyline);
                    $distance = calculateDistance(
                        $lastPoint['lat'],
                        $lastPoint['lng'],
                        $newPoint['lat'],
                        $newPoint['lng']
                    );
                    
                    // Only add if moved more than 5 meters
                    if ($distance < 5) {
                        $shouldAdd = false;
                    }
                }
                
                if ($shouldAdd) {
                    $polyline[] = $newPoint;
                    
                    // Update polyline in database
                    $stmt = $pdo->prepare("
                        UPDATE trips 
                        SET polyline = ?,
                            last_action_time = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([json_encode($polyline), $trip_id]);
                }
                
                $response = [
                    'success' => true, 
                    'message' => 'Location updated',
                    'polyline_points' => count($polyline)
                ];
            } else {
                $response = ['success' => false, 'message' => 'Invalid or inactive trip'];
            }
        } catch (PDOException $e) {
            $response = ['success' => false, 'message' => 'Database error'];
            error_log('Location update error: ' . $e->getMessage());
        }
    } else {
        $response = ['success' => false, 'message' => 'Missing required fields'];
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get location for tracking
    requireLogin();
    
    $trip_id = $_GET['trip_id'] ?? null;
    
    if ($trip_id) {
        try {
            // 🔥 FIX 1: Get latest location with trip status
            $stmt = $pdo->prepare("
                SELECT 
                    l.latitude, 
                    l.longitude, 
                    l.accuracy, 
                    l.speed, 
                    l.heading, 
                    l.timestamp,
                    t.trip_status,
                    t.polyline
                FROM locations l
                JOIN trips t ON t.id = l.trip_id
                WHERE l.trip_id = ? 
                ORDER BY l.timestamp DESC 
                LIMIT 1
            ");
            $stmt->execute([$trip_id]);
            $location = $stmt->fetch();
            
            if ($location) {
                // Decode polyline for route drawing
                $polyline = [];
                if (!empty($location['polyline'])) {
                    $polyline = json_decode($location['polyline'], true);
                    if (!is_array($polyline)) {
                        $polyline = [];
                    }
                }
                
                $response = [
                    'success' => true,
                    'location' => [
                        'latitude' => $location['latitude'],
                        'longitude' => $location['longitude'],
                        'accuracy' => $location['accuracy'],
                        'speed' => $location['speed'],
                        'heading' => $location['heading'],
                        'timestamp' => $location['timestamp']
                    ],
                    'trip_status' => (int)$location['trip_status'],
                    'polyline' => $polyline
                ];
            } else {
                $response = ['success' => false, 'message' => 'No location data'];
            }
        } catch (PDOException $e) {
            $response = ['success' => false, 'message' => 'Database error'];
            error_log('Location fetch error: ' . $e->getMessage());
        }
    } else {
        $response = ['success' => false, 'message' => 'Missing trip_id'];
    }
}

echo json_encode($response);
?>
