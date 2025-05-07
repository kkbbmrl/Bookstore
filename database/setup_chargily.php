<?php
// Include database connection
require_once '../connection.php';

// Add chargily_checkout_id column to orders table
$sql = "ALTER TABLE orders ADD COLUMN IF NOT EXISTS chargily_checkout_id VARCHAR(255)";

if (mysqli_query($conn, $sql)) {
    echo "Chargily checkout ID column added successfully";
} else {
    echo "Error adding column: " . mysqli_error($conn);
}

// Close connection
mysqli_close($conn);
?>