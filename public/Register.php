<?php

error_reporting(E_ALL);
ini_set('display_errors',1);

include 'config.php';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if (isset($_POST['username'], $_POST['password'],$_POST['device_id'])) {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $device_id = trim($_POST['device_id']);

    // Check if user already exists
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $checkStmt->bind_param("s", $username);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        echo "Username already exists";
    } else {
        $insertStmt = $conn->prepare("INSERT INTO users (username, password,device_id) VALUES (?, ?,?)");
        $insertStmt->bind_param("sss", $username, $password,$device_id);
        if ($insertStmt->execute()) {
            echo "Registration successful";
        } else {
            echo "Error: " . $conn->error;
        }
        $insertStmt->close();
    }
    $checkStmt->close();
} else {
    echo "Missing username ,password or device ID";
}

$conn->close();
?>
