<?php
// filepath: c:\xampp\htdocs\Bookstore\Pages\register_process.php
// Start session
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

// Registration form processing
if(isset($_POST['register'])) {
    // Initialize error message variable
    $error = "";
    
    // Validate and sanitize inputs
    if(empty($_POST['name'])) {
        $error = "Username is required";
    } else {
        $name = sanitize_input($_POST['name']);
        // Check if username is at least 3 characters
        if(strlen($name) < 3) {
            $error = "Username must be at least 3 characters";
        }
    }
    
    if(empty($_POST['email'])) {
        $error = "Email is required";
    } else {
        $email = sanitize_input($_POST['email']);
        // Validate email format
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format";
        } else {
            // Check if email already exists
            $check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $check_email->bind_param("s", $email);
            $check_email->execute();
            $result = $check_email->get_result();
            if($result->num_rows > 0) {
                $error = "Email already exists. Please use a different email or login.";
            }
            $check_email->close();
        }
    }
    
    if(empty($_POST['password'])) {
        $error = "Password is required";
    } else {
        $password = $_POST['password'];
        // Check password strength
        if(strlen($password) < 6) {
            $error = "Password must be at least 6 characters";
        }
    }
    
    if(empty($_POST['cpassword'])) {
        $error = "Confirm Password is required";
    } else {
        $cpassword = $_POST['cpassword'];
        // Check if passwords match
        if($password !== $cpassword) {
            $error = "Passwords do not match";
        }
    }
    
    // Sanitize optional inputs
    $phone = isset($_POST['phone']) ? sanitize_input($_POST['phone']) : NULL;
    $address = isset($_POST['address']) ? sanitize_input($_POST['address']) : NULL;
    $role = "customer"; // Default role for new users
    $current_time = date("Y-m-d H:i:s");
    
    // If no validation errors, proceed with registration
    if(empty($error)) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Prepare SQL statement to insert new user
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, address, role, created_at, updated_at) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $name, $email, $hashed_password, $phone, $address, $role, $current_time, $current_time);
        
        if($stmt->execute()) {
            // Registration successful
            $_SESSION['registration_success'] = "Account created successfully! Please login.";
            header("Location: login.php");
            exit();
        } else {
            // Registration failed
            $error = "Error creating account: " . $conn->error;
        }
        
        $stmt->close();
    }
    
    // If there's an error, store it in session and redirect back to registration page
    if(!empty($error)) {
        $_SESSION['registration_error'] = $error;
        header("Location: registration.php");
        exit();
    }
} else {
    // If someone tries to access this page directly
    header("Location: registration.php");
    exit();
}
?>