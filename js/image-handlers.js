/**
 * Image handling utilities for Bookstore
 */

/**
 * Image preview functionality for file inputs
 * @param {HTMLElement} input - The file input element
 * @param {string} previewId - The ID of the preview container
 */
function previewImage(input, previewId) {
    const previewContainer = document.getElementById(previewId);
    if (!previewContainer) return;
    
    const previewImage = previewContainer.querySelector('img') || document.createElement('img');
    
    if (!previewContainer.querySelector('img')) {
        previewImage.classList.add('img-thumbnail');
        previewImage.style.maxHeight = '200px';
        previewImage.style.maxWidth = '100%';
        previewContainer.appendChild(previewImage);
    }
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            previewImage.src = e.target.result;
            previewContainer.style.display = 'block';
        };
        
        reader.readAsDataURL(input.files[0]);
    } else {
        previewContainer.style.display = 'none';
    }
}

/**
 * Validates an image file based on size and type
 * @param {File} file - The file to validate
 * @param {number} maxSizeMB - Maximum allowed size in MB
 * @returns {Object} - Validation result with status and message
 */
function validateImage(file, maxSizeMB = 2) {
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    const maxSize = maxSizeMB * 1024 * 1024; // Convert to bytes
    
    if (!file) {
        return { valid: false, message: 'No file selected' };
    }
    
    if (!allowedTypes.includes(file.type)) {
        return { valid: false, message: 'Only JPG, JPEG, PNG & GIF files are allowed' };
    }
    
    if (file.size > maxSize) {
        return { valid: false, message: `File size should be less than ${maxSizeMB}MB` };
    }
    
    return { valid: true, message: 'File is valid' };
}

/**
 * Displays an error message for image uploads
 * @param {string} containerId - The ID of the container to show the error in
 * @param {string} message - The error message
 */
function showImageError(containerId, message) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    // Remove any existing error messages
    const existingErrors = container.querySelectorAll('.image-upload-error');
    existingErrors.forEach(el => el.remove());
    
    // Create and add new error message
    const errorDiv = document.createElement('div');
    errorDiv.className = 'alert alert-danger mt-2 image-upload-error';
    errorDiv.textContent = message;
    
    container.appendChild(errorDiv);
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        errorDiv.style.opacity = '0';
        errorDiv.style.transition = 'opacity 0.5s ease';
        
        setTimeout(() => {
            errorDiv.remove();
        }, 500);
    }, 5000);
}

// Initialize image preview functionality for all file inputs when the page loads
document.addEventListener('DOMContentLoaded', function() {
    const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
    
    imageInputs.forEach(input => {
        // Extract preview ID from data attribute or generate one
        let previewId = input.dataset.preview;
        
        if (!previewId) {
            previewId = `preview_${input.id || Math.random().toString(36).substring(2, 9)}`;
            
            // Create preview container if doesn't exist
            let previewContainer = document.getElementById(previewId);
            
            if (!previewContainer) {
                previewContainer = document.createElement('div');
                previewContainer.id = previewId;
                previewContainer.className = 'image-preview mt-2';
                previewContainer.style.display = 'none';
                
                // Insert after the input
                input.parentNode.insertBefore(previewContainer, input.nextSibling);
            }
            
            // Set data attribute for future reference
            input.dataset.preview = previewId;
        }
        
        // Add change event listener
        input.addEventListener('change', function() {
            previewImage(this, previewId);
            
            // Validate image
            const validationResult = validateImage(this.files[0]);
            if (!validationResult.valid) {
                showImageError(previewId, validationResult.message);
                this.value = ''; // Clear the input
                document.getElementById(previewId).style.display = 'none';
            }
        });
    });
});