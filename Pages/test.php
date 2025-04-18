<?php
// Include database connection
require_once 'connection.php';

// Create a test user
$name = "Test User";
$email = "test@example.com";
$password = password_hash("password123", PASSWORD_DEFAULT); // Use a secure hashing algorithm

// Insert user into database
$stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $name, $email, $password);

if($stmt->execute()) {
    echo "Test user created successfully!";
} else {
    echo "Error creating test user: " . $conn->error;
}

$stmt->close();
$conn->close();
?>