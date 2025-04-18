<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Book Filter Page</title>

    <!--- google font link-->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="book-filter.css" />
    <!-- Fontawesome Link for Icons -->
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
    <link rel="apple-touch-icon" sizes="57x57" href="../favicon/apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="../favicon/apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="../favicon/apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="../favicon/apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="../favicon/apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="../favicon/apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="../favicon/apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="../favicon/apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-icon-180x180.png">
    <link rel="icon" type="image/png" sizes="192x192"  href="../favicon/android-icon-192x192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="../favicon/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicon/favicon-16x16.png">
    <link rel="manifest" href="../favicon/manifest.json">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="/ms-icon-144x144.png">
    <meta name="theme-color" content="#ffffff">
    <style>
      .books {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 20px;
        margin-top: 20px;
      }
      .book-card {
        transition: transform 0.3s ease;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        background: white;
      }
      .book-card:hover {
        transform: translateY(-5px);
      }
      .book-card .img {
        height: 250px;
        position: relative;
        overflow: hidden;
      }
      .book-card .img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s;
      }
      .book-card .img img:hover {
        transform: scale(1.05);
      }
      .loader-container {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 200px;
      }
      .loader {
        border: 5px solid #f3f3f3;
        border-top: 5px solid #6c5dd4;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        animation: spin 1s linear infinite;
      }
      @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
      }
      .no-results {
        text-align: center;
        padding: 20px;
        grid-column: 1 / -1;
      }
      .category-tag {
        background-color: #f0f0f0;
        padding: 2px 6px;
        border-radius: 4px;
        margin-right: 4px;
        font-size: 0.8em;
        display: inline-block;
        margin-bottom: 3px;
      }
      .search-field {
        position: relative;
      }
    </style>
  </head>
  <body>
    <header>
      <nav class="navbar-2">
        <div class="logo">
          <div class="img">
            <a href="../index.php"><img src="../images/logo.png" alt="" /></a>
          </div>
          <div class="title">
            <h4>Fassila<i class="fa-solid fa-grid"></i></h4>
            <small>Book Store Website</small>
          </div>
        </div>
        <div class="search-box">
          <div class="search-field">
            <input
              type="text"
              id="search-input"
              placeholder="Search over 30 million Book titles"
            />
            <button class="search-icon" id="search-button">
              <i class="fa-solid fa-magnifying-glass"></i>
            </button>
          </div>
        </div>
        <div class="nav-end">
          <button class="likebtn">
            <i class="fa-regular fa-heart"></i> <span>35</span>
          </button>
          <button class="cart">
            <a href="cart-item.php"><i class="fa-solid fa-cart-shopping"></i> <span>4</span></a>
          </button>
          <div class="profile-img">
            <img
              src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQiM0o_5tIn0DAmbB2wKS4GvurHctTwxD5om2vi4NOsj1ODDSGULrviZ-QV3ul8JYEMfO0&usqp=CAU"
              alt=""
            />
          </div>
        </div>
      </nav>
    </header>
    <div class="breadcrumb-container">
      <ul class="breadcrumb">
        <li><a href="../index.php">Home</a></li>
        <li><a href="#">Books</a></li>
      </ul>
    </div>
    <section class="filter">
      <div class="book-grid-container">
        <div class="filter-option">
          <div class="filter-group">
            <h4>Filter Options</h4>
          <div class="editor-pick select-box">
            <div class="opt-title">
              <h4>Editor Picks</h4>
              <i class="fa-solid fa-caret-down"></i>
            </div>
            <div class="option">
              <ul>
                <li><a href="#" data-sort="popular">Best Sales</a></li>
                <li><a href="#" data-sort="recommended">Most Recommended</a></li>
                <li><a href="#" data-sort="newest">Newest Books</a></li>
                <li><a href="#" data-sort="featured">Featured</a></li>
              </ul>
            </div>
          </div>
          <div class="select-date dropdown">
            <div class="opt-title">
              <h4>Select Date</h4>
              <i class="fa-solid fa-caret-down"></i>
            </div>
            <div class="option">
              <input type="date" name="published_date" id="published_date" />
            </div>
          </div>
          <div class="genre-category select-box">
            <div class="opt-title">
              <h4>Shop By Category</h4>
              <i class="fa-solid fa-caret-down"></i>
            </div>
            <div class="option">
              <div class="category">
                <input type="checkbox" value="fiction" class="category-filter" />
                <small>Fiction</small>
              </div>
              <div class="category">
                <input type="checkbox" value="fantasy" class="category-filter" />
                <small>Fantasy</small>
              </div>
              <div class="category">
                <input type="checkbox" value="adventure" class="category-filter" />
                <small>Adventure</small>
              </div>
              <div class="category">
                <input type="checkbox" value="history" class="category-filter" />
                <small>History</small>
              </div>
              <div class="category">
                <input type="checkbox" value="science_fiction" class="category-filter" />
                <small>Science Fiction</small>
              </div>
              <div class="category">
                <input type="checkbox" value="horror" class="category-filter" />
                <small>Horror</small>
              </div>
              <div class="category">
                <input type="checkbox" value="biography" class="category-filter" />
                <small>Biography</small>
              </div>
              <div class="category">
                <input type="checkbox" value="mystery" class="category-filter" />
                <small>Mystery</small>
              </div>
              <div class="category">
                <input type="checkbox" value="comedy" class="category-filter" />
                <small>Comedy</small>
              </div>
              <div class="category">
                <input type="checkbox" value="romance" class="category-filter" />
                <small>Romance</small>
              </div>
              <div class="category">
                <input type="checkbox" value="crime" class="category-filter" />
                <small>Crime</small>
              </div>
              <div class="category">
                <input type="checkbox" value="thriller" class="category-filter" />
                <small>Thriller</small>
              </div>
            </div>
          </div>
          <div class="range-slider dropdown">
            <div class="opt-title">
              <h4>Price Range</h4>
            </div>
            <div class="option">
              <div class="price-input">
                <div class="field">
                  <span>Min</span>
                  <input type="number" class="input-min" value="10">
                </div>
                <div class="separator">-</div>
                <div class="field">
                  <span>Max</span>
                  <input type="number" class="input-max" value="50">
                </div>
              </div>
              <div class="slider">
                <div class="progress"></div>
              </div>
              <div class="range-input">
                <input type="range" class="range-min" min="0" max="100" value="10" step="5">
                <input type="range" class="range-max" min="0" max="100" value="50" step="5">
              </div>
            </div>
          </div>
          <div class="footer-btn">
            <button id="apply-filters">Refine Search</button>
            <button id="reset-filters">Reset Filter</button>
          </div>
          </div>
          <i class="fa fa-chevron-right rightbtn"></i>
        </div>
        <div class="book-collections">
          <h4>Books</h4>
          <div class="category">
            <div class="category-list">
              <button class="time-filter" data-time="today">Today</button>
              <button class="time-filter" data-time="week">This Week</button>
              <button class="time-filter" data-time="month">This Month</button>
            </div>
          </div>
          <div class="books" id="books-container">
            <div class="loader-container">
              <div class="loader"></div>
            </div>
          </div>
          <div class="footer">
            <div class="data-shown">
              <p id="showing-results">Loading books...</p>
            </div>
            <div class="pagination">
              <button id="prev-page"><i class="fa fa-chevron-left"></i>Previous</button>
              <div class="number" id="pagination-numbers">
                <a href="#" class="active">1</a>
              </div>
              <button id="next-page">Next<i class="fa fa-chevron-right"></i></button>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="book-sale">
      <div class="heading">
        <h4>Books On Sale</h4>
        <div class="arrowbtn">
          <i id="left" class="fa-solid fa-angle-left"></i>
          <i id="right" class="fa-solid fa-angle-right"></i>
        </div>
      </div>
      <div class="book-container">
        <div class="wrapper">
          <ul class="carousel">
            <li class="card">
              <div class="img">
                <a href="book-detail.php"><img src="../images/book-1.jpg" alt="" /></a>
                <span class="badge">50%</span>
              </div>
              <h5>The Giver</h5>
              <div class="genre">
                <span>adventure,</span><span>survival</span>
              </div>
              <div class="footer">
                <span class="star"><i class="fa fa-star"></i> 4.7</span>
                <div class="price">
                  <span>$45.4</span>
                  <span><strike>$90.4</strike></span>
                </div>
              </div>
            </li>
            <li class="card">
              <div class="img">
                <a href="book-detail.php"><img src="../images/book-2.jpg" alt="" /></a>
                <span class="badge">50%</span>
              </div>
              <h5>The Wright ...</h5>
              <div class="genre">
                <span>adventure,</span><span>survival</span>
              </div>
              <div class="footer">
                <span class="star"><i class="fa fa-star"></i> 4.7</span>
                <div class="price">
                  <span>$45.4</span>
                  <span><strike>$90.4</strike></span>
                </div>
              </div>
            </li>
            <li class="card">
              <div class="img">
                <a href="book-detail.php"><img src="../images/book-9.jpg" alt="" /></a>
                <span class="badge">50%</span>
              </div>
              <h5>The Ruins Of...</h5>
              <div class="genre">
                <span>adventure,</span><span>survival</span>
              </div>
              <div class="footer">
                <span class="star"><i class="fa fa-star"></i> 4.7</span>
                <div class="price">
                  <span>$45.4</span>
                  <span><strike>$90.4</strike></span>
                </div>
              </div>
            </li>
            <li class="card">
              <div class="img">
                <a href="book-detail.php"><img src="../images/book-10.jpg" alt="" /></a>
                <span class="badge">50%</span>
              </div>
              <h5>Percy Jackson</h5>
              <div class="genre">
                <span>adventure,</span><span>survival</span>
              </div>
              <div class="footer">
                <span class="star"><i class="fa fa-star"></i> 4.7</span>
                <div class="price">
                  <span>$45.4</span>
                  <span><strike>$90.4</strike></span>
                </div>
              </div>
            </li>
            <li class="card">
              <div class="img">
                <a href="book-detail.php"><img src="../images/book-5.jpg" alt="" /></a>
                <span class="badge">50%</span>
              </div>
              <h5>To kill a...</h5>
              <div class="genre">
                <span>adventure,</span><span>survival</span>
              </div>
              <div class="footer">
                <span class="star"><i class="fa fa-star"></i> 4.7</span>
                <div class="price">
                  <span>$45.4</span>
                  <span><strike>$90.4</strike></span>
                </div>
              </div>
            </li>
            <li class="card">
              <div class="img">
                <a href="book-detail.php"><img src="../images/book-6.jpg" alt="" /></a>
                <span class="badge">50%</span>
              </div>
              <h5>Horry Potter</h5>
              <div class="genre">
                <span>adventure,</span><span>survival</span>
              </div>
              <div class="footer">
                <span class="star"><i class="fa fa-star"></i> 4.7</span>
                <div class="price">
                  <span>$45.4</span>
                  <span><strike>$90.4</strike></span>
                </div>
              </div>
            </li>
            <li class="card">
              <div class="img">
                <a href="book-detail.php"><img src="../images/book-7.jpg" alt="" /></a>
                <span class="badge">50%</span>
              </div>
              <h5>Heroes of ...</h5>
              <div class="genre">
                <span>adventure,</span><span>survival</span>
              </div>
              <div class="footer">
                <span class="star"><i class="fa fa-star"></i> 4.7</span>
                <div class="price">
                  <span>$45.4</span>
                  <span><strike>$90.4</strike></span>
                </div>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </section>

    <section class="service">
      <div class="service-container">
        <div class="service-card">
          <div class="icon">
            <i class="fa-solid fa-bolt-lightning"></i>
          </div>
          <div class="service-content">
            <h5>Quick Delivery</h5>
            <p>
              Lorem ipsum dolor sit amet consectetur adipisicing elit. Id,
              exercitationem.
            </p>
          </div>
        </div>
        <div class="service-card">
          <div class="icon">
            <i class="fa-solid fa-shield"></i>
          </div>
          <div class="service-content">
            <h5>Secure Payment</h5>
            <p>
              Lorem ipsum dolor sit amet consectetur adipisicing elit. Id,
              exercitationem.
            </p>
          </div>
        </div>
        <div class="service-card">
          <div class="icon">
            <i class="fa-solid fa-thumbs-up"></i>
          </div>
          <div class="service-content">
            <h5>Best Quality</h5>
            <p>
              Lorem ipsum dolor sit amet consectetur adipisicing elit. Id,
              exercitationem.
            </p>
          </div>
        </div>
        <div class="service-card">
          <div class="icon">
            <i class="fa-solid fa-star"></i>
          </div>
          <div class="service-content">
            <h5>Return Guarantee</h5>
            <p>
              Lorem ipsum dolor sit amet consectetur adipisicing elit. Id,
              exercitationem.
            </p>
          </div>
        </div>
      </div>
    </section>

    <section class="subscription">
      <div class="container">
        <h4>Subscribe our newsletter for Latest <br> books updates</h4>
        <div class="input">
          <input type="text" placeholder="Type your email here">
          <button>subscribe</button>
        </div>
      </div>
      <div class="circle-1"></div>
      <div class="circle-2"></div>
    </section>

    <footer>
      <div class="container">
        <div class="logo-description">
          <div class="logo">
            <div class="img">
              <a href="../index.php"><img src="../images/logo.png" alt=""></a>
            </div>
            <div class="title">
              <h4>Fassila</h4>
              <small>Book Store Website</small>
            </div>
          </div>
          <div class="logo-body">
            <p>Lorem ipsum, dolor sit amet consectetur adipisicing elit. Magnam voluptates eius quasi reiciendis recusandae provident veritatis sequi, dolores architecto dolor possimus quos</p>
          </div>
          <div class="social-links">
            <h4>Follow Us</h4>
            <ul class="links">
              <li><a href=""><i class="fa-brands fa-facebook-f"></i></a></li>
              <li><a href=""><i class="fa-brands fa-youtube"></i></a></li>
              <li><a href=""><i class="fa-brands fa-twitter"></i></a></li>
              <li><a href=""><i class="fa-brands fa-linkedin"></i></a></li>
              <li><a href=""><i class="fa-brands fa-instagram"></i></a></li>
            </ul>
          </div>
        </div>
        <div class="categories list">
          <h4>Book Categories</h4>
          <ul>
            <li><a href="">Action</a></li>
            <li><a href="">Adventure</a></li>
            <li><a href="">Comedy</a></li>
            <li><a href="">Crime</a></li>
            <li><a href="">Drama</a></li>
            <li><a href="">Fantasy</a></li>
            <li><a href="">Horror</a></li>
          </ul>
        </div>
        <div class="quick-links list">
          <h4>Quick Links</h4>
          <ul>
            <li><a href="../index.php">About Us</a></li>
            <li><a href="contact.html">Contact Us</a></li>
            <li><a href="book-filter.php">Products</a></li>
            <li><a href="login.php">Login</a></li>
            <li><a href="registration.php">Sign Up</a></li>
            <li><a href="cart-item.html">Cart</a></li>
            <li><a href="checkout.html">Checkout</a></li>
          </ul>
        </div>
        <div class="our-store list">
          <h4>Our Store</h4>
          <div class="map" style="margin-top: 1rem;">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d6310.594819201665!2d-122.42768319999999!3d37.73616639999999!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x808f7e60a337d5f5%3A0xfa0bb626904e5ab2!2z4KSV4KWJ4KSy4KWH4KScIOCkueCkv-Cksiwg4KS44KS-4KSoIOCkq-CljeCksOCkvuCkguCkuOCkv-CkuOCljeCkleCliywg4KSV4KWI4KSy4KWA4KSr4KWL4KSw4KWN4KSo4KS_4KSv4KS-LCDgpK_gpYLgpKjgpL7gpIfgpJ_gpYfgpKEg4KS44KWN4KSf4KWH4KSf4KWN4oCN4KS4!5e0!3m2!1shi!2sin!4v1686917463994!5m2!1shi!2sin" height="70" style="width: 100%;border: none;border-radius: 5px;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
          </div>
          <ul>
            <li><a href=""><i class="fa-solid fa-location-dot"></i>832 Thompson Drive,San Fransisco CA 94 107,United States</a></li>
            <li><a href=""><i class="fa-solid fa-phone"></i>+12 1345678991</a></li>
            <li><a href=""><i class="fa-solid fa-envelope"></i>support@Fassila.id</a></li>
          </ul>
        </div>
      </div>
    </footer>
    <button class="back-to-top"><i class="fa-solid fa-chevron-up"></i></button>

    <script>
      let filterdiv = document.querySelector(".filter-option");
      let iconbtn = document.querySelector(".rightbtn");

      iconbtn.addEventListener("click", () => {
        filterdiv.classList.toggle("active-div");
        iconbtn.classList.toggle("active-btn");
      });

      // Filter dropdowns
      let editorpick = document.getElementsByClassName("select-box");
      let icon = document.querySelectorAll(".select-box .opt-title i");
      let answer = document.querySelectorAll(".select-box .option");
      for (let i = 0; i < editorpick.length; i++) {
        editorpick[i].addEventListener("click", () => {
          if (icon[i].classList.contains("active")) {
            icon[i].classList.remove("active");
            answer[i].style.maxHeight = null;
            answer[i].style.marginTop = "0rem";
            answer[i].style.padding = "0px";
          } else {
            icon[i].classList.add("active");
            answer[i].style.maxHeight = answer[i].scrollHeight + "px";
            answer[i].style.padding = "0px 20px 20px 20px";
          }
        });
      }

      let selectdate = document.querySelector(".select-date .opt-title");
      let downarrow = document.querySelector(".select-date .opt-title i");
      let option = document.querySelector(".select-date .option");
      selectdate.addEventListener("click",() => {
        if(downarrow.classList.contains("active")){
          downarrow.classList.remove("active");
          option.style.display = "none";
          option.style.padding = "0px";
        }else{
          downarrow.classList.add("active");
          option.style.display = "block";
          option.style.maxHeight = option.scrollHeight+"px";
          option.style.padding = "0px 20px 20px 20px";
        }
      })

      // Price range slider functionality
      const rangeInput = document.querySelectorAll(".range-input input"),
      priceInput = document.querySelectorAll(".price-input input"),
      range = document.querySelector(".slider .progress");
      let priceGap = 5;

      priceInput.forEach(input =>{
          input.addEventListener("input", e =>{
              let minPrice = parseInt(priceInput[0].value),
              maxPrice = parseInt(priceInput[1].value);
              
              if((maxPrice - minPrice >= priceGap) && maxPrice <= rangeInput[1].max){
                  if(e.target.className === "input-min"){
                      rangeInput[0].value = minPrice;
                      range.style.left = ((minPrice / rangeInput[0].max) * 100) + "%";
                  }else{
                      rangeInput[1].value = maxPrice;
                      range.style.right = 100 - (maxPrice / rangeInput[1].max) * 100 + "%";
                  }
              }
          });
      });

      rangeInput.forEach(input =>{
          input.addEventListener("input", e =>{
              let minVal = parseInt(rangeInput[0].value),
              maxVal = parseInt(rangeInput[1].value);

              if((maxVal - minVal) < priceGap){
                  if(e.target.className === "range-min"){
                      rangeInput[0].value = maxVal - priceGap
                  }else{
                      rangeInput[1].value = minVal + priceGap;
                  }
              }else{
                  priceInput[0].value = minVal;
                  priceInput[1].value = maxVal;
                  range.style.left = ((minVal / rangeInput[0].max) * 100) + "%";
                  range.style.right = 100 - (maxVal / rangeInput[1].max) * 100 + "%";
              }
          });
      });

      // Open Library API Integration
      document.addEventListener('DOMContentLoaded', function() {
        // Variables
        let currentPage = 1;
        const booksPerPage = 12;
        let currentQuery = 'fantasy';
        let totalResults = 0;
        const booksContainer = document.getElementById('books-container');
        const searchInput = document.getElementById('search-input');
        const searchButton = document.getElementById('search-button');
        const prevPageBtn = document.getElementById('prev-page');
        const nextPageBtn = document.getElementById('next-page');
        const applyFiltersBtn = document.getElementById('apply-filters');
        const resetFiltersBtn = document.getElementById('reset-filters');
        const categoryFilters = document.querySelectorAll('.category-filter');
        const timeFilters = document.querySelectorAll('.time-filter');
        const paginationNumbers = document.getElementById('pagination-numbers');
        const showingResults = document.getElementById('showing-results');

        // Initial fetch
        fetchBooks(currentQuery);

        // Event listeners
        searchButton.addEventListener('click', function() {
          const query = searchInput.value.trim();
          if (query) {
            currentQuery = query;
            currentPage = 1;
            fetchBooks(currentQuery);
          }
        });

        searchInput.addEventListener('keypress', function(e) {
          if (e.key === 'Enter') {
            const query = searchInput.value.trim();
            if (query) {
              currentQuery = query;
              currentPage = 1;
              fetchBooks(currentQuery);
            }
          }
        });

        prevPageBtn.addEventListener('click', function() {
          if (currentPage > 1) {
            currentPage--;
            fetchBooks(currentQuery);
          }
        });

        nextPageBtn.addEventListener('click', function() {
          currentPage++;
          fetchBooks(currentQuery);
        });

        applyFiltersBtn.addEventListener('click', function() {
          applyFilters();
        });

        resetFiltersBtn.addEventListener('click', function() {
          resetFilters();
        });

        timeFilters.forEach(button => {
          button.addEventListener('click', function() {
            timeFilters.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            applyFilters();
          });
        });

        // Like button functionality
        document.addEventListener('click', function(e) {
          if (e.target.closest('.like')) {
            e.target.closest('.like').classList.toggle('liked');
          }
        });

        // Functions
        function fetchBooks(query) {
          showLoading();

          // Getting selected categories
          const selectedCategories = [];
          document.querySelectorAll('.category-filter:checked').forEach(checkbox => {
            selectedCategories.push(checkbox.value);
          });

          // Add categories to query if selected
          let searchQuery = query;
          if (selectedCategories.length > 0) {
            searchQuery = `${query} subject:(${selectedCategories.join(' OR ')})`;
          }

          fetch(`https://openlibrary.org/search.json?q=${encodeURIComponent(searchQuery)}&page=${currentPage}&limit=${booksPerPage}`)
            .then(response => {
              if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
              }
              return response.json();
            })
            .then(data => {
              totalResults = data.numFound;
              renderBooks(data.docs);
              updatePagination();
              updateResultsCount(data.numFound, currentPage, booksPerPage);
            })
            .catch(error => {
              console.error('Error fetching books:', error);
              booksContainer.innerHTML = `<div class="no-results">Error loading books. Please try again later.</div>`;
            });
        }

        function renderBooks(books) {
          if (!books || books.length === 0) {
            booksContainer.innerHTML = '<div class="no-results">No books found. Try a different search.</div>';
            return;
          }

          let booksHTML = '';

          books.forEach(book => {
            const coverID = book.cover_i || '';
            const coverURL = coverID 
              ? `https://covers.openlibrary.org/b/id/${coverID}-M.jpg` 
              : '../images/book-loader.gif';
            
            const title = book.title || 'Unknown Title';
            const authorName = book.author_name ? book.author_name[0] : 'Unknown Author';
            
            // Extract up to 3 subjects or categories
            const subjects = book.subject || [];
            const subjectHTML = subjects.slice(0, 3).map(subject => 
              `<span class="category-tag">${subject}</span>`
            ).join('');

            const firstPublishYear = book.first_publish_year || 'Unknown Year';
            const rating = generateRandomRating(); // Just for display

            booksHTML += `
              <div class="book-card">
                <div class="img">
                  <a href="book-detail.php?id=${book.key}"><img src="${coverURL}" alt="${title}" /></a>
                  <button class="like" id="likebtn">
                    <i class="fa-regular fa-heart"></i>
                  </button>
                </div>
                <div style="padding: 15px;">
                  <h5 style="margin: 0;">${title.length > 20 ? title.substring(0, 18) + '...' : title}</h5>
                  <small style="color: #666; display: block; margin: 4px 0;">${authorName}</small>
                  <div style="margin: 5px 0;">${subjectHTML}</div>
                  <div class="star-rating" title="${rating}/5">
                    ${generateStars(rating)}
                  </div>
                  <div style="margin-top: 10px; display: flex; justify-content: space-between; align-items: center;">
                    <span>Published: ${firstPublishYear}</span>
                    <button class="add-to-cart" style="background: #6c5dd4; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">
                      <i class="fa-solid fa-cart-plus"></i>
                    </button>
                  </div>
                </div>
              </div>
            `;
          });

          booksContainer.innerHTML = booksHTML;
        }

        function updatePagination() {
          const totalPages = Math.ceil(totalResults / booksPerPage);
          paginationNumbers.innerHTML = '';
          
          // Only show 5 page numbers max
          const startPage = Math.max(1, currentPage - 2);
          const endPage = Math.min(totalPages, startPage + 4);
          
          for (let i = startPage; i <= endPage; i++) {
            const pageLink = document.createElement('a');
            pageLink.href = '#';
            pageLink.textContent = i;
            if (i === currentPage) {
              pageLink.classList.add('active');
            }
            
            pageLink.addEventListener('click', function(e) {
              e.preventDefault();
              currentPage = i;
              fetchBooks(currentQuery);
            });
            
            paginationNumbers.appendChild(pageLink);
          }
          
          // Update buttons state
          prevPageBtn.disabled = currentPage === 1;
          nextPageBtn.disabled = currentPage >= totalPages;
        }

        function updateResultsCount(total, page, limit) {
          const start = (page - 1) * limit + 1;
          const end = Math.min(start + limit - 1, total);
          showingResults.textContent = `Showing ${start} - ${end} of ${total} books`;
        }

        function showLoading() {
          booksContainer.innerHTML = `
            <div class="loader-container">
              <div class="loader"></div>
            </div>
          `;
        }

        function generateRandomRating() {
          return (Math.random() * 3 + 2).toFixed(1); // Random rating between 2.0 and 5.0
        }

        function generateStars(rating) {
          const fullStars = Math.floor(rating);
          const halfStar = rating % 1 >= 0.5;
          const emptyStars = 5 - fullStars - (halfStar ? 1 : 0);
          
          return `${
            '<i class="fa-solid fa-star"></i>'.repeat(fullStars)
          }${
            halfStar ? '<i class="fa-solid fa-star-half-stroke"></i>' : ''
          }${
            '<i class="fa-regular fa-star"></i>'.repeat(emptyStars)
          }`;
        }

        function applyFilters() {
          currentPage = 1;
          fetchBooks(currentQuery);
        }

        function resetFilters() {
          // Reset checkboxes
          document.querySelectorAll('.category-filter').forEach(checkbox => {
            checkbox.checked = false;
          });
          
          // Reset search
          searchInput.value = '';
          currentQuery = 'bestseller';
          
          // Reset price range
          document.querySelector('.input-min').value = 10;
          document.querySelector('.input-max').value = 50;
          document.querySelector('.range-min').value = 10;
          document.querySelector('.range-max').value = 50;
          
          range.style.left = "10%";
          range.style.right = "50%";
          
          // Reset and fetch
          currentPage = 1;
          fetchBooks(currentQuery);
        }
      });
    </script>
    <script src="../js/repeat-js.js"></script>
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="../js/back-to-top.js"></script>
  </body>
</html>
