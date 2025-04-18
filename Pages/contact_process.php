<?php
// filepath: c:\xampp\htdocs\Bookstore\Pages\contact_process.php
// Start session for displaying messages after redirect
session_start();

// Include database connection
require_once '../connection.php';

// Function to sanitize user inputs
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Check if form is submitted
if(isset($_POST['submit'])) {
    // Initialize error flag
    $error = false;
    
    // Validate and sanitize name
    if(empty($_POST['name'])) {
        $_SESSION['contact_error'] = "Please enter your name";
        $error = true;
    } else {
        $name = sanitize_input($_POST['name']);
    }
    
    // Validate and sanitize email
    if(empty($_POST['email'])) {
        $_SESSION['contact_error'] = "Please enter your email";
        $error = true;
    } else {
        $email = sanitize_input($_POST['email']);
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['contact_error'] = "Please enter a valid email address";
            $error = true;
        }
    }
    
    // Validate and sanitize phone
    if(empty($_POST['phone'])) {
        $_SESSION['contact_error'] = "Please enter your phone number";
        $error = true;
    } else {
        $phone = sanitize_input($_POST['phone']);
        // Basic phone number validation (can be improved based on your requirements)
        if(!preg_match("/^[0-9+\-\(\) ]{6,20}$/", $phone)) {
            $_SESSION['contact_error'] = "Please enter a valid phone number";
            $error = true;
        }
    }
    
    // Validate and sanitize message
    if(empty($_POST['message'])) {
        $_SESSION['contact_error'] = "Please enter your message";
        $error = true;
    } else {
        $message = sanitize_input($_POST['message']);
        if(strlen($message) < 10) {
            $_SESSION['contact_error'] = "Message must be at least 10 characters long";
            $error = true;
        }
    }
    
    // If no validation errors, insert into database
    if(!$error) {
        // Prepare SQL statement
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, phone, message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $phone, $message);
        
        // Execute query
        if($stmt->execute()) {
            $_SESSION['contact_success'] = "Thank you for your message! We'll get back to you soon.";
            header("Location: contact.php");
            $stmt->close();
            exit();
        } else {
            $stmt->close();
            $_SESSION['contact_error'] = "Oops! Something went wrong. Please try again later.";
            header("Location: contact.php");
            exit();
        }
    } else {
        // If there are validation errors, redirect back to contact page
        header("Location: contact.php");
        exit();
    }
} else {
    // If someone tries to access this page directly
    header("Location: contact.php");
    exit();
}
