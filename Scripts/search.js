document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('.search-field input');
    const searchButton = document.querySelector('.search-field button');
    
    // Create search results container if it doesn't exist
    let searchResultsContainer = document.querySelector('.search-results-container');
    if (!searchResultsContainer) {
        searchResultsContainer = document.createElement('div');
        searchResultsContainer.className = 'search-results-container';
        document.querySelector('.search-field').appendChild(searchResultsContainer);
    }
    
    // Debounce function to limit API calls
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }
    
    // Function to perform search
    const performSearch = debounce(function() {
        const query = searchInput.value.trim();
        if (query.length < 2) {
            searchResultsContainer.classList.remove('active');
            return;
        }
        
        // Show loading state
        searchResultsContainer.innerHTML = '<div class="no-results">Searching...</div>';
        searchResultsContainer.classList.add('active');
        
        // Make API request to your backend
        fetch(`/Bookstore/api/search.php?query=${encodeURIComponent(query)}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                // Clear previous results
                searchResultsContainer.innerHTML = '';
                
                // If no results found
                if (data.length === 0) {
                    searchResultsContainer.innerHTML = '<div class="no-results">No books found</div>';
                    return;
                }
                
                // Display results
                data.forEach(book => {
                    const resultItem = document.createElement('div');
                    resultItem.className = 'search-result-item';
                    
                    // Set default image if none exists
                    const imageUrl = book.image_url || '/Bookstore/images/default-book.jpg';
                    
                    resultItem.innerHTML = `
                        <img src="${imageUrl}" alt="${book.title}" onerror="this.src='/Bookstore/images/default-book.jpg'">
                        <div class="search-result-info">
                            <span class="book-title">${book.title}</span>
                            <span class="book-author">${book.author}</span>
                            <span class="book-price">$${parseFloat(book.price).toFixed(2)}</span>
                        </div>
                    `;
                    
                    // Add click event to redirect to book details
                    resultItem.addEventListener('click', () => {
                        window.location.href = `/Bookstore/Pages/book-detail.php?id=${book.id}`;
                    });
                    
                    searchResultsContainer.appendChild(resultItem);
                });
            })
            .catch(error => {
                console.error('Search error:', error);
                searchResultsContainer.innerHTML = '<div class="no-results">Error searching books. Please try again.</div>';
            });
    }, 300); // 300ms debounce
    
    // Add event listeners
    searchInput.addEventListener('input', performSearch);
    searchButton.addEventListener('click', function(e) {
        e.preventDefault();
        performSearch();
    });
    
    // Close search results when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.search-field')) {
            searchResultsContainer.classList.remove('active');
        }
    });
});
