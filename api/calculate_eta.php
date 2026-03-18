<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../includes/auth.php';

requireLogin();

$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $origin = $data['origin'] ?? null;
    $destination = $data['destination'] ?? null;
    
    if ($origin && $destination) {
        $originStr = $origin['lat'] . ',' . $origin['lng'];
        $destinationStr = $destination['lat'] . ',' . $destination['lng'];
        
        // Call Google Directions API
        $url = "https://maps.googleapis.com/maps/api/directions/json?origin={$originStr}&destination={$destinationStr}&key=AIzaSyAxn47G2g9sT3WUEMFEnX_qZB3s8wXkoGM";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $result = curl_exec($ch);
        curl_close($ch);
        
        $directions = json_decode($result, true);
        
        if ($directions && $directions['status'] === 'OK') {
            $route = $directions['routes'][0];
            $leg = $route['legs'][0];
            
            $response = [
                'success' => true,
                'eta' => $leg['duration']['text'],
                'distance' => $leg['distance']['text'],
                'duration_value' => $leg['duration']['value'], // seconds
                'distance_value' => $leg['distance']['value'] // meters
            ];
        } else {
            $response = ['success' => false, 'message' => 'Directions API error'];
        }
    }
}

echo json_encode($response);
?>
