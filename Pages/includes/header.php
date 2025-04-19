<?php
// filepath: c:\xampp\htdocs\Bookstore\includes\header.php

// Determine if we're in the Pages directory or root directory
$isInPagesDir = strpos($_SERVER['SCRIPT_NAME'], '/Pages/') !== false;
$rootPath = $isInPagesDir ? '../' : '';
$searchPath = $isInPagesDir ? 'search.php' : 'Pages/search.php';
$cartPath = $isInPagesDir ? 'cart-item.html' : 'Pages/cart-item.html';
$loginPath = $isInPagesDir ? 'login.php' : 'Pages/login.php';
$logoutPath = $isInPagesDir ? 'logout.php' : 'Pages/logout.php';
$registrationPath = $isInPagesDir ? 'registration.php' : 'Pages/registration.php';
?>
<header>
  <nav class="navbar-2">
    <div class="logo">
      <div class="img">
        <img src="<?php echo $rootPath; ?>images/logo.png" alt="" />
      </div>
      <div class="title">
        <h4><a href="<?php echo $rootPath; ?>index.php" style="text-decoration: none; color: inherit;">Fassila<i class="fa-solid fa-grid"></i></a></h4>
        <small>Book Store Website</small>
      </div>
    </div>
    <div class="search-box">
      <form action="<?php echo $searchPath; ?>" method="GET">
        <div class="search-field">
          <input
            type="text"
            name="query"
            placeholder="Search over 30 million Book titles"
            required
          />
          <button class="search-icon" type="submit">
            <i class="fa-solid fa-magnifying-glass"></i>
          </button>
        </div>
      </form>
    </div>
    <div class="nav-end">
      <button class="likebtn">
        <i class="fa-regular fa-heart"></i> <span>35</span>
      </button>
      <button class="cart">
        <a href="<?php echo $cartPath; ?>"><i class="fa-solid fa-cart-shopping"></i> <span>4</span></a>
      </button>
      <?php if(isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
        <div class="profile-img">
            <?php 
            $profileImage = isset($_SESSION['profile_image']) ? $_SESSION['profile_image'] : 'man1.png';
            ?>
            <a href="<?php echo $isInPagesDir ? 'account.php' : 'Pages/account.php'; ?>">
            <img
              src="<?php echo $rootPath; ?>uploads/profiles/<?php echo $profileImage; ?>"
              alt="Profile"
            />
            </a>
          <button class="logout">
            <a href="<?php echo $logoutPath; ?>">Logout</a>
          </button>
        </div>
      <?php else: ?>
        <button class="login">
          <a href="<?php echo $loginPath; ?>">Login</a>
        </button>
        <button class="signup">
          <a href="<?php echo $registrationPath; ?>">Sign Up</a>
        </button>
      <?php endif; ?>
    </div>
  </nav>
</header>