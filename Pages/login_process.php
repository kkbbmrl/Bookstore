<?php
// filepath: c:\xampp\htdocs\Bookstore\Pages\login_process.php
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

// Handle login form submission
if(isset($_POST['login'])) {
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
    
    // Check if user exists in database
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows === 1) {
        // User found, verify password
        $user = $result->fetch_assoc();
        
        // If you're using password_hash() for storing passwords (recommended)
        if(password_verify($password, $user['password'])) {
            // Password is correct, create session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['logged_in'] = true;
            
            // Set success message
            $_SESSION['login_success'] = "Welcome back, " . $user['name'] . "!";
            
            // Redirect to homepage
            header("Location: ../index.php");
            exit();
        } else {
            // Password is incorrect
            $_SESSION['login_error'] = "Invalid email or password";
            header("Location: login.php");
            exit();
        }
    } else {
        // User not found
        $_SESSION['login_error'] = "Invalid email or password";
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