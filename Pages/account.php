<?php
session_start();
include '../connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = "";
$error_message = "";

// Get current user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    
    // Update basic info
    $update_stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
    $update_stmt->bind_param("ssi", $name, $email, $user_id);
    
    // Handle password change if provided
    if (!empty($_POST['new_password'])) {
        if (strlen($_POST['new_password']) < 6) {
            $error_message = "Password must be at least 6 characters long";
        } else if ($_POST['new_password'] === $_POST['confirm_password']) {
            $password_hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?");
            $update_stmt->bind_param("sssi", $name, $email, $password_hash, $user_id);
        } else {
            $error_message = "Passwords do not match";
        }
    }
    
    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowed_types = array('image/jpeg', 'image/png', 'image/jpg');
        if (in_array($_FILES['profile_picture']['type'], $allowed_types)) {
            $upload_dir = 'uploads/profiles/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $filename = $user_id . '_' . time() . '_' . $_FILES['profile_picture']['name'];
            $destination = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $destination)) {
                $update_pic_stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                $update_pic_stmt->bind_param("si", $destination, $user_id);
                $update_pic_stmt->execute();
            } else {
                $error_message = "Failed to upload image";
            }
        } else {
            $error_message = "Invalid file type. Only JPG, JPEG and PNG are allowed";
        }
    }
    
    if (empty($error_message)) {
        $update_stmt->execute();
        $success_message = "Account updated successfully!";
        
        // Refresh user data
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Account - Bookstore</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h1>Edit Your Account</h1>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="account-details">
            <div class="profile-image">
                <img src="<?php echo !empty($user['profile_picture']) ? htmlspecialchars($user['profile_picture']) : 'uploads/profiles/man1.png'; ?>" alt="Profile Picture">
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="profile_picture">Profile Picture</label>
                    <input type="file" id="profile_picture" name="profile_picture">
                </div>
                
                <h3>Change Password</h3>
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password">
                </div>
                
                <button type="submit" class="btn">Save Changes</button>
            </form>
        </div>
    </div>
    <footer>
      <div class="container">
        <div class="logo-description">
          <div class="logo">
            <div class="img">
              <img src="../images/logo.png" alt="" />
            </div>
            <div class="title">
              <h4>Fassila</h4>
              <small>Book Store Website</small>
            </div>
          </div>
          <div class="logo-body">
            <p>
              Lorem ipsum, dolor sit amet consectetur adipisicing elit. Magnam
              voluptates eius quasi reiciendis recusandae provident veritatis
              sequi, dolores architecto dolor possimus quos
            </p>
          </div>
          <div class="social-links">
            <h4>Follow Us</h4>
            <ul class="links">
              <li>
                <a href=""><i class="fa-brands fa-facebook-f"></i></a>
              </li>
              <li>
                <a href=""><i class="fa-brands fa-youtube"></i></a>
              </li>
              <li>
                <a href=""><i class="fa-brands fa-twitter"></i></a>
              </li>
              <li>
                <a href=""><i class="fa-brands fa-linkedin"></i></a>
              </li>
              <li>
                <a href=""><i class="fa-brands fa-instagram"></i></a>
              </li>
            </ul>
          </div>
        </div>
        <div class="categories list">
          <h4>Book Categories</h4>
          <ul>
            <li><a href="Pages/book-filter.php">Action</a></li>
            <li><a href="Pages/book-filter.php">Adventure</a></li>
            <li><a href="Pages/book-filter.php">Comedy</a></li>
            <li><a href="Pages/book-filter.php">Crime</a></li>
            <li><a href="Pages/book-filter.php">Drama</a></li>
            <li><a href="Pages/book-filter.php">Fantasy</a></li>
            <li><a href="Pages/book-filter.php">Horror</a></li>
          </ul>
        </div>
        <div class="quick-links list">
          <h4>Quick Links</h4>
          <ul>
            <li><a href="index.php">About Us</a></li>
            <li><a href="pages/contact.php">Contact Us</a></li>
            <li><a href="pages/book-filter.php">Products</a></li>
            <li><a href="pages/login.php">Login</a></li>
            <li><a href="pages/registration.php">Sign Up</a></li>
            <li><a href="pages/cart-item.html">Cart</a></li>
            <li><a href="pages/checkout.html">Checkout</a></li>
          </ul>
        </div>
        <div class="our-store list">
          <h4>Our Store</h4>
          <div class="map" style="margin-top: 1rem">
            <iframe
              src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d6310.594819201665!2d-122.42768319999999!3d37.73616639999999!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x808f7e60a337d5f5%3A0xfa0bb626904e5ab2!2z4KSV4KWJ4KSy4KWH4KScIOCkueCkv-Cksiwg4KS44KS-4KSoIOCkq-CljeCksOCkvuCkguCkuOCkv-CkuOCljeCkleCliywg4KSV4KWI4KSy4KWA4KSr4KWL4KSw4KWN4KSo4KS_4KSv4KS-LCDgpK_gpYLgpKjgpL7gpIfgpJ_gpYfgpKEg4KS44KWN4KSf4KWH4KSf4KWN4oCN4KS4!5e0!3m2!1shi!2sin!4v1686917463994!5m2!1shi!2sin"
              height="70"
              style="width: 100%; border: none; border-radius: 5px"
              allowfullscreen=""
              loading="lazy"
              referrerpolicy="no-referrer-when-downgrade"
            ></iframe>
          </div>
          <ul>
            <li>
              <a href=""
                ><i class="fa-solid fa-location-dot"></i>832 Thompson Drive,San
                Fransisco CA 94 107,United States</a
              >
            </li>
            <li>
              <a href=""><i class="fa-solid fa-phone"></i>+12 1345678991</a>
            </li>
            <li>
              <a href=""
                ><i class="fa-solid fa-envelope"></i>support@Fassila.id</a
              >
            </li>
          </ul>
        </div>
      </div>
    </footer>
     <script src="../js/back-to-top.js"></script>
</body>
</html>