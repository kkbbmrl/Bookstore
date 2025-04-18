<?php
// filepath: c:\xampp\htdocs\Bookstore\includes\header.php
?>
<header>
  <nav class="navbar-2">
    <div class="logo">
      <div class="img">
        <img src="images/logo.png" alt="" />
      </div>
      <div class="title">
        <h4><a href="index.php" style="text-decoration: none; color: inherit;">Fassila<i class="fa-solid fa-grid"></i></a></h4>
        <small>Book Store Website</small>
      </div>
    </div>
    <div class="search-box">
      <form action="search.php" method="GET">
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
        <a href="Pages/cart-item.html"><i class="fa-solid fa-cart-shopping"></i> <span>4</span></a>
      </button>
      <?php if(isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
        <div class="profile-img">
          <img
            src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQiM0o_5tIn0DAmbB2wKS4GvurHctTwxD5om2vi4NOsj1ODDSGULrviZ-QV3ul8JYEMfO0&usqp=CAU"
            alt=""
          />
          <button class="logout">
            <a href="Pages/logout.php">Logout</a>
          </button>
        </div>
      <?php else: ?>
        <button class="login">
          <a href="Pages/login.php">Login</a>
        </button>
        <button class="signup">
          <a href="Pages/registration.php">Sign Up</a>
        </button>
      <?php endif; ?>
    </div>
  </nav>
</header>