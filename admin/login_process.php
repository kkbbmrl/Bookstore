<?php
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

// Handle admin login form submission
if(isset($_POST['admin_login'])) {
    // Get and sanitize form data
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password']; // Don't sanitize password before verification
    
    // Validate email
    if(empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['login_error'] = "Please enter a valid email address";
        header("Location: login.php");
        exit();
    }
    
    // Validate password
    if(empty($password)) {
        $_SESSION['login_error'] = "Please enter your password";
        header("Location: login.php");
        exit();
    }
    
    // First check if the user exists in the database
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows === 1) {
        // User found, verify password
        $user = $result->fetch_assoc();
        
        // Check if the is_admin column exists
        $is_admin = false;
        if(isset($user['is_admin'])) {
            $is_admin = (bool)$user['is_admin'];
        } else {
            // For backward compatibility - consider specific admin emails
            $admin_emails = ['admin@bookstore.com', 'admin@fassila.com']; // Add your admin emails
            if(in_array($email, $admin_emails)) {
                $is_admin = true;
            }
        }
        
        // If you're using password_hash() for storing passwords (recommended)
        if(password_verify($password, $user['password'])) {
            if($is_admin) {
                // Password is correct and user is admin, create admin session
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_name'] = $user['name'];
                $_SESSION['admin_email'] = $user['email'];
                $_SESSION['admin_logged_in'] = true;
                
                // Set success message
                $_SESSION['admin_login_success'] = "Welcome to the admin panel, " . $user['name'] . "!";
                
                // Redirect to admin dashboard
                header("Location: dashboard.php");
                exit();
            } else {
                // User is not an admin
                $_SESSION['login_error'] = "You don't have admin privileges";
                header("Location: login.php");
                exit();
            }
        } else {
            // Password is incorrect
            $_SESSION['login_error'] = "Invalid email or password";
            header("Location: login.php");
            exit();
        }
    } else {
        // User not found
        $_SESSION['login_error'] = "Invalid credentials";
        $stmt->close();
        header("Location: login.php");
        exit();
    }
} else {
    // If someone tries to access this page directly
    header("Location: login.php");
    exit();
}
?>