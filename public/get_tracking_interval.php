<?php
header('Content-Type: application/json');

// Include credentials
include 'config.php';

// Manually create the DB connection
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]);
    exit();
}

$query = "SELECT tracking_interval_minutes FROM TrackingSettings ORDER BY id DESC LIMIT 1";
$result = mysqli_query($conn, $query);

if ($row = mysqli_fetch_assoc($result)) {
    echo json_encode(["tracking_interval_min" => intval($row["tracking_interval_minutes"])]);
} else {
    echo json_encode(["tracking_interval_min" => 5]); // fallback default
}

?>
