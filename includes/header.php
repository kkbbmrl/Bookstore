<?php
// Make sure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If db connection isn't included yet, include it
if (!isset($conn)) {
    include_once 'db_connection.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Header styles */
        header {
            background-color: #f8f9fa;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .logo a {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            text-decoration: none;
        }
        
        nav ul {
            list-style: none;
            display: flex;
            margin: 0;
            padding: 0;
        }
        
        nav ul li {
            margin-right: 20px;
        }
        
        nav ul li a {
            color: #333;
            text-decoration: none;
        }
        
        .user-actions {
            display: flex;
            align-items: center;
        }
        
        .user-actions a {
            margin-left: 15px;
            color: #333;
            text-decoration: none;
        }
        
        /* Profile dropdown styles */
        .user-profile {
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        
        .profile-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
        }
        
        .dropdown {
            position: relative;
            display: inline-block;
        }
        
        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: #f9f9f9;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
        }
        
        .dropdown:hover .dropdown-content {
            display: block;
        }
        
        .dropdown-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            margin: 0;
        }
        
        .dropdown-content a:hover {
            background-color: #f1f1f1;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <a href="index.php">Bookstore</a>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="books.php">Books</a></li>
                <li><a href="categories.php">Categories</a></li>
                <li><a href="about.php">About Us</a></li>
                <li><a href="contact.php">Contact</a></li>
            </ul>
        </nav>
        <div class="user-actions">
            <?php if(isset($_SESSION['user_id'])): 
                // Get user data including profile picture
                $user_id = $_SESSION['user_id'];
                $stmt = $conn->prepare("SELECT name, profile_picture FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user_data = $result->fetch_assoc();
                $profile_pic = !empty($user_data['profile_picture']) ? $user_data['profile_picture'] : 'uploads/profiles/man1.png';
            ?>
                <div class="dropdown">
                    <div class="user-profile">
                        <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile" class="profile-img">
                        <span><?php echo htmlspecialchars($user_data['name']); ?></span>
                    </div>
                    <div class="dropdown-content">
                        <a href="account.php">My Account</a>
                        <a href="orders.php">My Orders</a>
                        <a href="logout.php">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
            <?php endif; ?>
        </div>
    </header>