<?php
// Include database connection
require_once __DIR__ . '/../connection.php';

// Check if contact_messages table already exists
$tableExists = $conn->query("SHOW TABLES LIKE 'contact_messages'");

if ($tableExists->num_rows == 0) {
    // Table doesn't exist, create it
    $createTableSQL = "CREATE TABLE `contact_messages` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `email` varchar(255) NOT NULL,
        `phone` varchar(20) NOT NULL,
        `message` text NOT NULL,
        `read_status` tinyint(1) DEFAULT 0,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    if ($conn->query($createTableSQL)) {
        echo "Contact Messages table created successfully.<br>";
    } else {
        echo "Error creating contact_messages table: " . $conn->error . "<br>";
    }
} else {
    echo "Contact Messages table already exists.<br>";
    
    // Check if read_status column exists
    $columnExists = $conn->query("SHOW COLUMNS FROM `contact_messages` LIKE 'read_status'");
    
    if ($columnExists->num_rows == 0) {
        // Add read_status column if it doesn't exist
        $addColumnSQL = "ALTER TABLE `contact_messages` 
                         ADD COLUMN `read_status` tinyint(1) DEFAULT 0 AFTER `message`";
        
        if ($conn->query($addColumnSQL)) {
            echo "Read status column added successfully.<br>";
        } else {
            echo "Error adding read_status column: " . $conn->error . "<br>";
        }
    } else {
        echo "Read status column already exists.<br>";
    }
}

// Close connection
$conn->close();

echo "Setup completed.";
?>