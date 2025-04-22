/**
 * Like Handler Script
 * This script provides functionality to update the like count in the header
 * whenever a book is liked or unliked across the site.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Function to update header like count
    window.updateHeaderLikeCount = function() {
        const headerLikeCount = document.querySelector('.nav-end .likebtn span');
        if (headerLikeCount) {
            fetch('Pages/get_like_count.php', {
                method: 'GET',
                cache: 'no-store' // Prevent caching to get fresh count
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    headerLikeCount.textContent = data.count;
                }
            })
            .catch(error => {
                console.error('Error updating like count:', error);
            });
        }
    };
    
    // Listen for custom events that signal a book was liked/unliked
    document.addEventListener('bookLikeStatusChanged', function() {
        updateHeaderLikeCount();
    });
});