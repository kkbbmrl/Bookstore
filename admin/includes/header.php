<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in, redirect to login page if not
// But skip the check for the login page itself
$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page !== 'login.php' && (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true)) {
    header("Location: login.php");
    exit();
}

// Include database connection
if (!isset($conn)) {
    require_once '../connection.php';
    
    // Verify connection
    if (!$conn) {
        die("Connection not established");
    } elseif ($conn->connect_error) {
        die("Connection failed: {$conn->connect_error}");
    }
}

// Get count of unread messages for sidebar
$unread_messages_count = 0;
if ($current_page !== 'login.php') {
    $unread_query = $conn->query("SELECT COUNT(*) as unread FROM contact_messages WHERE read_status = 0");
    if ($unread_query && $unread_result = $unread_query->fetch_assoc()) {
        $unread_messages_count = $unread_result['unread'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Fassila Bookstore</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
            padding-top: 20px;
            position: fixed;
            width: 250px;
        }
        .sidebar .logo {
            padding: 10px 15px 20px;
            color: white;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }
        .sidebar .logo img {
            max-width: 120px;
            margin-bottom: 10px;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.75);
            padding: 12px 20px;
            margin: 0 10px;
            border-radius: 5px;
            display: flex;
            align-items: center;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.1);
        }
        .sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        .content-wrapper {
            margin-left: 250px;
            padding: 20px;
            padding-top: 80px;
        }
        .top-navbar {
            background-color: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: fixed;
            width: calc(100% - 250px);
            z-index: 100;
            margin-left: 250px;
            padding: 15px 20px;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .card-header {
            background-color: white;
            border-bottom: 1px solid #f0f0f0;
            font-weight: 600;
            padding: 15px 20px;
        }
        .dropdown-menu {
            border: none;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .stats-card {
            background: linear-gradient(45deg, #4CAF50, #8BC34A);
            color: white;
        }
        .stats-card.purple {
            background: linear-gradient(45deg, #9C27B0, #E91E63);
        }
        .stats-card.blue {
            background: linear-gradient(45deg, #2196F3, #03A9F4);
        }
        .stats-card.orange {
            background: linear-gradient(45deg, #FF9800, #FF5722);
        }
        .stats-card .card-body {
            padding: 20px;
        }
        .stats-card .icon {
            font-size: 48px;
            opacity: 0.5;
        }
        .stats-card .count {
            font-size: 28px;
            font-weight: 700;
        }
        .stats-card .title {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.8;
        }
        .table {
            white-space: nowrap;
        }
        .table th {
            font-weight: 600;
            background-color: #f8f9fa;
        }
        .pagination {
            margin-bottom: 0;
        }
    </style>
    <script src="../js/image-handlers.js"></script>
</head>
<body>
    <!-- Skip sidebar and navbar for login page -->
    <?php if ($current_page !== 'login.php'): ?>
    
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <img src="../images/logo.png" alt="Fassila Logo">
            <h5>Fassila Admin</h5>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'books.php') ? 'active' : ''; ?>" href="books.php">
                <i class="fas fa-book"></i> Books
            </a>
            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'categories.php') ? 'active' : ''; ?>" href="categories.php">
                <i class="fas fa-list"></i> Categories
            </a>
            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'users.php') ? 'active' : ''; ?>" href="users.php">
                <i class="fas fa-users"></i> Users
            </a>
            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'orders.php') ? 'active' : ''; ?>" href="orders.php">
                <i class="fas fa-shopping-cart"></i> Orders
            </a>
            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'contact_messages.php') ? 'active' : ''; ?>" href="contact_messages.php">
                <i class="fas fa-envelope"></i> Messages
                <?php if ($unread_messages_count > 0): ?>
                    <span class="badge badge-danger ml-auto"><?php echo $unread_messages_count; ?></span>
                <?php endif; ?>
            </a>
            <a class="nav-link" href="logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </div>
    
    <!-- Top Navbar -->
    <div class="top-navbar">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <?php
                $page = basename($_SERVER['PHP_SELF']);
                $title = '';
                
                switch($page) {
                    case 'dashboard.php':
                        $title = 'Dashboard';
                        break;
                    case 'books.php':
                        $title = 'Manage Books';
                        break;
                    case 'categories.php':
                        $title = 'Manage Categories';
                        break;
                    case 'users.php':
                        $title = 'Manage Users';
                        break;
                    case 'orders.php':
                        $title = 'Manage Orders';
                        break;
                    case 'contact_messages.php':
                        $title = 'Contact Messages';
                        break;
                    default:
                        $title = 'Admin Panel';
                }
                
                echo $title;
                ?>
            </h4>
            <div class="dropdown">
                <a href="#" class="dropdown-toggle text-dark text-decoration-none d-flex align-items-center" id="userDropdown" data-toggle="dropdown" aria-expanded="false">
                    <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQiM0o_5tIn0DAmbB2wKS4GvurHctTwxD5om2vi4NOsj1ODDSGULrviZ-QV3ul8JYEMfO0&amp;usqp=CAU" alt="Account Avatar" style="width:40px; height:40px; border-radius:50%; object-fit:cover; margin-right:10px;">
                    <?php echo isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Admin'; ?>
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item" href="profile.php">
                        <i class="fas fa-user fa-fw mr-2 text-muted"></i> Profile
                    </a>
                    <a class="dropdown-item" href="settings.php">
                        <i class="fas fa-cog fa-fw mr-2 text-muted"></i> Settings
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="logout.php">
                        <i class="fas fa-sign-out-alt fa-fw mr-2 text-muted"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <?php if(isset($_SESSION['admin_login_success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                    echo $_SESSION['admin_login_success']; 
                    unset($_SESSION['admin_login_success']);
                ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                    echo $_SESSION['success_message']; 
                    unset($_SESSION['success_message']);
                ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                    echo $_SESSION['error_message']; 
                    unset($_SESSION['error_message']);
                ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
    <?php endif; ?>