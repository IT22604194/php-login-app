<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'config.php';

// Set timezone once
date_default_timezone_set('Asia/Colombo');

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    http_response_code(500);
    die("Connection failed: " . $conn->connect_error);
}

// Optional: set session time zone for MySQL connection
$conn->query("SET time_zone = '+05:30'");

$rep_id = $_POST['rep_id'] ?? '';
$latitude = $_POST['latitude'] ?? '';
$longitude = $_POST['longitude'] ?? '';
$action = $_POST['action'] ?? 'clock_in';
$battery_level = $_POST['battery_level'] ?? null;

if (!is_numeric($latitude) || !is_numeric($longitude) || empty($rep_id)) {
    http_response_code(400);
    echo "Invalid parameters";
    $conn->close();
    exit;
}

if ($action === 'clock_out') {
    $timestamp = date('Y-m-d H:i:s');

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
    $timestamp = date('Y-m-d H:i:s');

    // Check for recent duplicate within 30 seconds
    $checkSql = "SELECT id FROM location_logs 
                 WHERE rep_id = ? AND latitude = ? AND longitude = ?
                 AND timestamp > DATE_SUB(NOW(), INTERVAL 30 SECOND)";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("sdd", $rep_id, $latitude, $longitude);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows === 0) {
        // Not duplicate: insert new location
        $insertSql = "INSERT INTO location_logs (rep_id, latitude, longitude, timestamp,battery_level) VALUES (?, ?, ?, ?,?)";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param("sddsi", $rep_id, $latitude, $longitude, $timestamp,$battery_level);

        if ($insertStmt->execute()) {
            http_response_code(200);
            echo "Location saved.";
        } else {
            http_response_code(500);
            echo "Error: " . $insertStmt->error;
        }

        $insertStmt->close();
    } else {
        http_response_code(200);
        echo "Duplicate location skipped.";
    }

    $checkStmt->close();

} else {
    $timestamp = date('Y-m-d H:i:s');

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
