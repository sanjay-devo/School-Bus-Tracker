<?php
// ⚠️ CRITICAL: Prevent caching to fix UI update bugs
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: -1');

require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/email.php';

$response = ['success' => false, 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'start') {
        // Start trip (Driver only)
        requireRole('driver');
        
        $bus_id = $_POST['bus_id'] ?? null;
        $route_id = $_POST['route_id'] ?? null;
        $trip_type = $_POST['trip_type'] ?? 'pickup';
        $latitude = $_POST['latitude'] ?? null;
        $longitude = $_POST['longitude'] ?? null;
        
        if ($bus_id && $route_id) {
            try {
                $pdo->beginTransaction();
                
                // 🔥 FIX 1: Close ANY previous active trips for this driver to prevent duplicates
                $stmt = $pdo->prepare("
                    UPDATE trips 
                    SET status = 'completed', 
                        trip_status = 0, 
                        end_time = NOW(),
                        last_action_time = NOW()
                    WHERE driver_id = ? AND trip_status = 1
                ");
                $stmt->execute([getUserId()]);
                
                // 🔥 FIX 2: Also close any active trips for this specific bus
                $stmt = $pdo->prepare("
                    UPDATE trips 
                    SET status = 'completed', 
                        trip_status = 0, 
                        end_time = NOW(),
                        last_action_time = NOW()
                    WHERE bus_id = ? AND trip_status = 1
                ");
                $stmt->execute([$bus_id]);
                
                // 🔥 FIX 3: Create new trip with trip_status = 1 (STARTED)
                $stmt = $pdo->prepare("
                    INSERT INTO trips (
                        bus_id, 
                        driver_id, 
                        route_id, 
                        trip_type, 
                        start_time, 
                        status, 
                        trip_status,
                        start_latitude,
                        start_longitude,
                        last_action_time
                    ) VALUES (?, ?, ?, ?, NOW(), 'started', 1, ?, ?, NOW())
                ");
                $stmt->execute([
                    $bus_id, 
                    getUserId(), 
                    $route_id, 
                    $trip_type,
                    $latitude,
                    $longitude
                ]);
                $trip_id = $pdo->lastInsertId();
                
                $pdo->commit();
                
                // 🔥 FIX 4: Send instant email notifications (async/background)
                // Get parent emails for this bus
                $stmt = $pdo->prepare("
                    SELECT DISTINCT u.email, u.name, s.student_name, b.bus_number
                    FROM users u
                    JOIN students s ON s.parent_id = u.id
                    JOIN student_bus_assignments sba ON sba.student_id = s.id
                    JOIN buses b ON b.id = sba.bus_id
                    WHERE sba.bus_id = ? AND sba.is_active = 1 AND u.role = 'parent'
                ");
                $stmt->execute([$bus_id]);
                $parents = $stmt->fetchAll();
                
                // Send emails in background to avoid delays
                foreach ($parents as $parent) {
                    sendTripStartEmail(
                        $parent['email'], 
                        $parent['name'], 
                        $parent['student_name'], 
                        $parent['bus_number'],
                        $trip_id
                    );
                }
                
                // 🔥 FIX 5: Return trip_id immediately for frontend
                $response = [
                    'success' => true, 
                    'trip_id' => $trip_id, 
                    'message' => 'Trip started successfully',
                    'timestamp' => time()
                ];
                
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $response = ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
                error_log('Trip start error: ' . $e->getMessage());
            }
        } else {
            $response = ['success' => false, 'message' => 'Missing bus_id or route_id'];
        }
        
    } elseif ($action === 'stop') {
        // Stop trip (Driver only)
        requireRole('driver');
        
        $trip_id = $_POST['trip_id'] ?? null;
        $latitude = $_POST['latitude'] ?? null;
        $longitude = $_POST['longitude'] ?? null;
        
        if ($trip_id) {
            try {
                $pdo->beginTransaction();
                
                // 🔥 FIX 1: Verify trip belongs to this driver and is active
                $stmt = $pdo->prepare("
                    SELECT bus_id, polyline 
                    FROM trips 
                    WHERE id = ? 
                    AND driver_id = ? 
                    AND trip_status = 1
                ");
                $stmt->execute([$trip_id, getUserId()]);
                $trip = $stmt->fetch();
                
                if ($trip) {
                    // 🔥 FIX 2: Calculate trip statistics
                    $stmt = $pdo->prepare("
                        SELECT 
                            COUNT(*) as location_count,
                            MAX(speed) as max_speed,
                            AVG(speed) as avg_speed
                        FROM locations
                        WHERE trip_id = ?
                    ");
                    $stmt->execute([$trip_id]);
                    $stats = $stmt->fetch();
                    
                    // 🔥 FIX 3: Calculate total distance from polyline or locations
                    $totalDistance = 0;
                    if (!empty($trip['polyline'])) {
                        $polyline = json_decode($trip['polyline'], true);
                        if (is_array($polyline) && count($polyline) > 1) {
                            for ($i = 1; $i < count($polyline); $i++) {
                                $totalDistance += calculateDistance(
                                    $polyline[$i-1]['lat'],
                                    $polyline[$i-1]['lng'],
                                    $polyline[$i]['lat'],
                                    $polyline[$i]['lng']
                                );
                            }
                        }
                    }
                    
                    // 🔥 FIX 4: Update trip with trip_status = 0 (STOPPED)
                    $stmt = $pdo->prepare("
                        UPDATE trips 
                        SET status = 'completed', 
                            trip_status = 0,
                            end_time = NOW(),
                            end_latitude = ?,
                            end_longitude = ?,
                            total_distance = ?,
                            max_speed = ?,
                            average_speed = ?,
                            last_action_time = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $latitude,
                        $longitude,
                        $totalDistance / 1000, // Convert to kilometers
                        $stats['max_speed'],
                        $stats['avg_speed'],
                        $trip_id
                    ]);
                    
                    $pdo->commit();
                    
                    // 🔥 FIX 5: Send instant email notifications
                    $stmt = $pdo->prepare("
                        SELECT DISTINCT u.email, u.name, s.student_name, b.bus_number
                        FROM users u
                        JOIN students s ON s.parent_id = u.id
                        JOIN student_bus_assignments sba ON sba.student_id = s.id
                        JOIN buses b ON b.id = sba.bus_id
                        WHERE sba.bus_id = ? AND sba.is_active = 1 AND u.role = 'parent'
                    ");
                    $stmt->execute([$trip['bus_id']]);
                    $parents = $stmt->fetchAll();
                    
                    foreach ($parents as $parent) {
                        sendTripEndEmail(
                            $parent['email'], 
                            $parent['name'], 
                            $parent['student_name'], 
                            $parent['bus_number'],
                            $trip_id
                        );
                    }
                    
                    $response = [
                        'success' => true, 
                        'message' => 'Trip completed successfully',
                        'timestamp' => time(),
                        'total_distance' => round($totalDistance / 1000, 2) . ' km'
                    ];
                } else {
                    $response = ['success' => false, 'message' => 'Active trip not found'];
                }
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $response = ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
                error_log('Trip stop error: ' . $e->getMessage());
            }
        } else {
            $response = ['success' => false, 'message' => 'Missing trip_id'];
        }
        
    } elseif ($action === 'sos') {
        // Send SOS alert (Driver only)
        requireRole('driver');
        
        $latitude = $_POST['latitude'] ?? null;
        $longitude = $_POST['longitude'] ?? null;
        
        if ($latitude && $longitude) {
            try {
                // Get driver and bus info
                $stmt = $pdo->prepare("
                    SELECT u.name as driver_name, b.bus_number
                    FROM users u
                    LEFT JOIN buses b ON b.driver_id = u.id
                    WHERE u.id = ?
                ");
                $stmt->execute([getUserId()]);
                $driverInfo = $stmt->fetch();
                
                // Get all admin emails
                $stmt = $pdo->prepare("SELECT email FROM users WHERE role = 'admin' AND is_active = 1");
                $stmt->execute();
                $admins = $stmt->fetchAll();
                
                // Send SOS emails
                foreach ($admins as $admin) {
                    sendSOSEmail(
                        $admin['email'],
                        $driverInfo['driver_name'],
                        $driverInfo['bus_number'] ?? 'Unknown',
                        $latitude,
                        $longitude
                    );
                }
                
                $response = ['success' => true, 'message' => 'SOS alert sent'];
            } catch (PDOException $e) {
                $response = ['success' => false, 'message' => 'Database error'];
                error_log($e->getMessage());
            }
        }
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    requireLogin();
    
    $action = $_GET['action'] ?? '';
    
    if ($action === 'active') {
        // Get active trip for current driver
        requireRole('driver');
        
        try {
            $stmt = $pdo->prepare("
                SELECT t.*, b.bus_number, r.route_name
                FROM trips t
                JOIN buses b ON b.id = t.bus_id
                JOIN routes r ON r.id = t.route_id
                WHERE t.driver_id = ? AND t.status = 'started'
                LIMIT 1
            ");
            $stmt->execute([getUserId()]);
            $trip = $stmt->fetch();
            
            if ($trip) {
                $response = ['success' => true, 'trip' => $trip];
            } else {
                $response = ['success' => false, 'message' => 'No active trip'];
            }
        } catch (PDOException $e) {
            $response = ['success' => false, 'message' => 'Database error'];
            error_log($e->getMessage());
        }
    }
}

echo json_encode($response);
?>
