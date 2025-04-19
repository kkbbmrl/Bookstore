<?php
// filepath: c:\xampp\htdocs\Bookstore\includes\footer.php

// Determine if we're in the Pages directory or root directory
$isInPagesDir = strpos($_SERVER['SCRIPT_NAME'], '/Pages/') !== false;
$rootPath = $isInPagesDir ? '../' : '';
?>
<footer>
  <div class="container">
    <div class="categories list">
      <h4>Book Categories</h4>
      <ul>
        <li><a href="<?php echo $isInPagesDir ? 'book-filter.php' : 'Pages/book-filter.php'; ?>">Action</a></li>
        <li><a href="<?php echo $isInPagesDir ? 'book-filter.php' : 'Pages/book-filter.php'; ?>">Adventure</a></li>
        <li><a href="<?php echo $isInPagesDir ? 'book-filter.php' : 'Pages/book-filter.php'; ?>">Comedy</a></li>
        <li><a href="<?php echo $isInPagesDir ? 'book-filter.php' : 'Pages/book-filter.php'; ?>">Crime</a></li>
        <li><a href="<?php echo $isInPagesDir ? 'book-filter.php' : 'Pages/book-filter.php'; ?>">Drama</a></li>
        <li><a href="<?php echo $isInPagesDir ? 'book-filter.php' : 'Pages/book-filter.php'; ?>">Fantasy</a></li>
        <li><a href="<?php echo $isInPagesDir ? 'book-filter.php' : 'Pages/book-filter.php'; ?>">Horror</a></li>
      </ul>
    </div>
    <div class="quick-links list">
      <h4>Quick Links</h4>
      <ul>
        <li><a href="<?php echo $rootPath; ?>index.php">About Us</a></li>
        <li><a href="<?php echo $isInPagesDir ? 'contact.php' : 'Pages/contact.php'; ?>">Contact Us</a></li>
        <li><a href="<?php echo $isInPagesDir ? 'book-filter.php' : 'Pages/book-filter.php'; ?>">Products</a></li>
        <li><a href="<?php echo $isInPagesDir ? 'login.php' : 'Pages/login.php'; ?>">Login</a></li>
        <li><a href="<?php echo $isInPagesDir ? 'registration.php' : 'Pages/registration.php'; ?>">Sign Up</a></li>
        <li><a href="<?php echo $isInPagesDir ? 'cart-item.php' : 'Pages/cart-item.php'; ?>">Cart</a></li>
        <li><a href="<?php echo $isInPagesDir ? 'checkout.html' : 'Pages/checkout.html'; ?>">Checkout</a></li>
      </ul>
    </div>
  </div>
</footer>

<button class="back-to-top"><i class="fa-solid fa-chevron-up"></i></button>

<!-- Back to top script -->
<script src="<?php echo $rootPath; ?>js/back-to-top.js"></script>

<!-- Search functionality script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Get search form
  const searchForm = document.querySelector('.search-box form');
  
  if (searchForm) {
    // Add event listener for searching
    searchForm.addEventListener('submit', function(e) {
      const searchButton = this.querySelector('.search-icon');
      searchButton.closest('.search-field').classList.add('loading');
      
      // Store search in localStorage for analytics (optional)
      const searchQuery = document.querySelector('input[name="query"]').value;
      localStorage.setItem('lastSearch', searchQuery);
      
      // Track popular searches (optional)
      let popularSearches = JSON.parse(localStorage.getItem('popularSearches') || '[]');
      popularSearches.push({
        query: searchQuery,
        timestamp: new Date().toISOString()
      });
      
      // Keep only last 10 searches
      if (popularSearches.length > 10) {
        popularSearches = popularSearches.slice(-10);
      }
      
      localStorage.setItem('popularSearches', JSON.stringify(popularSearches));
    });
    
    // Show recent searches (optional enhancement)
    const searchInput = document.querySelector('input[name="query"]');
    if (searchInput) {
      searchInput.addEventListener('focus', function() {
        const lastSearch = localStorage.getItem('lastSearch');
        if (lastSearch) {
          // Create a suggestion element if you want to implement this feature
          // This is optional enhancement
        }
      });
    }
  }
});
</script>