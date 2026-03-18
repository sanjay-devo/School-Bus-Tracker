<?php
// ⚠️ CRITICAL: Prevent caching
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: -1');

require_once '../config.php';
require_once '../includes/auth.php';

requireLogin();

$response = ['success' => false, 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $trip_id = $_GET['trip_id'] ?? null;
    
    if ($trip_id) {
        try {
            // Get trip details
            $stmt = $pdo->prepare("
                SELECT 
                    t.*,
                    b.bus_number,
                    r.route_name,
                    u.name as driver_name
                FROM trips t
                JOIN buses b ON b.id = t.bus_id
                JOIN routes r ON r.id = t.route_id
                JOIN users u ON u.id = t.driver_id
                WHERE t.id = ?
            ");
            $stmt->execute([$trip_id]);
            $trip = $stmt->fetch();
            
            if ($trip) {
                // Verify user has access to this trip
                $hasAccess = false;
                
                if (getUserRole() === 'admin' || getUserRole() === 'driver') {
                    $hasAccess = true;
                } elseif (getUserRole() === 'parent') {
                    // Check if parent's child is on this bus
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) as count
                        FROM students s
                        JOIN student_bus_assignments sba ON sba.student_id = s.id
                        WHERE s.parent_id = ? AND sba.bus_id = ? AND sba.is_active = 1
                    ");
                    $stmt->execute([getUserId(), $trip['bus_id']]);
                    $result = $stmt->fetch();
                    $hasAccess = $result['count'] > 0;
                }
                
                if (!$hasAccess) {
                    $response = ['success' => false, 'message' => 'Access denied'];
                } else {
                    // Get all locations for this trip
                    $stmt = $pdo->prepare("
                        SELECT 
                            latitude,
                            longitude,
                            speed,
                            accuracy,
                            heading,
                            timestamp
                        FROM locations
                        WHERE trip_id = ?
                        ORDER BY timestamp ASC
                    ");
                    $stmt->execute([$trip_id]);
                    $locations = $stmt->fetchAll();
                    
                    $response = [
                        'success' => true,
                        'trip' => $trip,
                        'locations' => $locations
                    ];
                }
            } else {
                $response = ['success' => false, 'message' => 'Trip not found'];
            }
        } catch (PDOException $e) {
            $response = ['success' => false, 'message' => 'Database error'];
            error_log('Trip details error: ' . $e->getMessage());
        }
    } else {
        $response = ['success' => false, 'message' => 'Missing trip_id'];
    }
}

echo json_encode($response);
?>
