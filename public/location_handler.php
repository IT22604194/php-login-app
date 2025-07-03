<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'config.php';
date_default_timezone_set('Asia/Colombo');

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    http_response_code(500);
    die("Connection failed: " . $conn->connect_error);
}

$rep_id = $_POST['rep_id'] ?? '';
$latitude = $_POST['latitude'] ?? '';
$longitude = $_POST['longitude'] ?? '';
$action = $_POST['action'] ?? 'clock_in'; // default to clock_in

if (!is_numeric($latitude) || !is_numeric($longitude) || empty($rep_id)) {
    http_response_code(400);
    echo "Invalid parameters";
    $conn->close();
    exit;
}

$timestamp = date('Y-m-d H:i:s');

if ($action === 'clock_out') {
    // Clock Out updates the most recent clock_in record
    $sql = "UPDATE locations 
            SET clock_out_latitude = ?, clock_out_longitude = ?, clock_out_timestamp = ?
            WHERE rep_id = ?
            ORDER BY timestamp DESC 
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $latitude, $longitude, $timestamp, $rep_id);

    if ($stmt->execute()) {
        http_response_code(200);
        echo "Clock out recorded successfully.";
    } else {
        http_response_code(500);
        echo "Error: " . $stmt->error;
    }

    $stmt->close();

} elseif ($action === 'location_update') {
    // Periodic location updates go into location_logs only
    $sql = "INSERT INTO location_logs (rep_id, latitude, longitude, timestamp) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdds", $rep_id, $latitude, $longitude, $timestamp);

    if ($stmt->execute()) {
        http_response_code(200);
        echo "Location saved.";
    } else {
        http_response_code(500);
        echo "Error: " . $stmt->error;
    }

    $stmt->close();

} else {
    // Clock In (default case)
    $sql = "INSERT INTO locations (rep_id, latitude, longitude, timestamp) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdds", $rep_id, $latitude, $longitude, $timestamp);

    if ($stmt->execute()) {
        http_response_code(200);
        echo "Clock in saved.";
    } else {
        http_response_code(500);
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
