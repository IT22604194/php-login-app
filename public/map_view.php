<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'config.php';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]);
    exit();
}

$rep_id = isset($_GET['rep_id']) ? $_GET['rep_id'] : '';

if (empty($rep_id)) {
    echo "Rep ID not provided.";
    exit();
}
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Only show locations for the given rep_id
$sql = "SELECT latitude, longitude, timestamp FROM location_logs WHERE rep_id = ?";

$params = [$rep_id];
$types = "s";

if (!empty($start_date)) {
    $sql .= " AND DATE(timestamp) >= ?";
    $params[] = $start_date;
    $types .= "s";
}
if (!empty($end_date)) {
    $sql .= " AND DATE(timestamp) <= ?";
    $params[] = $end_date;
    $types .= "s";
}

$sql .= " ORDER BY timestamp ASC";

$stmt = $conn->prepare($sql);

$bind_names[] = $types;
foreach ($params as $key => $value) {
    $bind_name = 'bind' . $key;
    $$bind_name = $value;
    $bind_names[] = &$$bind_name;
}

call_user_func_array([$stmt, 'bind_param'], $bind_names);

$stmt->execute();
$result = $stmt->get_result();

$locations = [];
while ($row = $result->fetch_assoc()) {
    $locations[] = $row;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Location Logs Map - OpenStreetMap</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <style>
        #map {
            height: 90vh;
            width: 100%;
        }
    </style>
</head>
<body>
    <h2>Location Logs Map for Rep: <?php echo htmlspecialchars($rep_id); ?></h2>
    <div id="map"></div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script>
        var locations = <?php echo json_encode($locations); ?>;

        var map = L.map('map').setView([7.8731, 80.7718], 7); // Center on Sri Lanka

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: 'Map data © <a href="https://openstreetmap.org">OpenStreetMap</a> contributors'
        }).addTo(map);

        // Plot markers
        locations.forEach(function(loc, index) {
            var marker = L.marker([loc.latitude, loc.longitude]).addTo(map);
            marker.bindPopup("Order: " + (index + 1) + "<br>Time: " + loc.timestamp).openPopup();
        });

        // Fit map to markers
        if (locations.length > 0) {
            var latlngs = locations.map(loc => [loc.latitude, loc.longitude]);
            map.fitBounds(latlngs);
        }
        if (locations.length > 1) {
            var polylinePoints = locations.map(loc => [loc.latitude, loc.longitude]);
            var polyline = L.polyline(polylinePoints, {color: 'blue'}).addTo(map);
        }

    </script>
</body>
</html>

