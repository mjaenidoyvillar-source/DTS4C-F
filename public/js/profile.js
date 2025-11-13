/**
 * Profile Picture Management
 * Shared functionality for profile picture upload, preview, and removal
 */

(function() {
    'use strict';

    // Constants
    const DEFAULT_AVATAR_TEMPLATE = 'https://api.dicebear.com/7.x/avataaars/svg?seed=';
    const AVATAR_SELECTORS = {
        owner: '.user-avatar img',
        handler: '.user-avatar img',
        admin: '.user-avatar img, .navbar .user-avatar img'
    };

    /**
     * Get default avatar URL for user
     */
    function getDefaultAvatarUrl(userId) {
        return DEFAULT_AVATAR_TEMPLATE + (userId || 0);
    }

    /**
     * Show or hide remove button
     */
    function toggleRemoveButton(show) {
        const container = document.getElementById('removePictureButtonContainer');
        if (!container) return;

        if (show) {
            container.style.setProperty('display', 'block', 'important');
            container.classList.remove('d-none');
        } else {
            container.style.display = 'none';
        }
    }

    /**
     * Update all avatar images on the page
     */
    function updateAvatarImages(url, userId) {
        // Try different selectors to find avatar images
        const selectors = [
            '.user-avatar img',
            '.navbar .user-avatar img',
            '[class*="avatar"] img'
        ];
        
        const imageUrl = url || getDefaultAvatarUrl(userId);
        const timestamp = url ? '?t=' + new Date().getTime() : '';
        
        selectors.forEach(selector => {
            const avatarImages = document.querySelectorAll(selector);
            avatarImages.forEach(img => {
                img.src = imageUrl + timestamp;
            });
        });
    }

    /**
     * Check if profile picture URL is valid
     */
    function hasValidProfilePicture(url) {
        return url && url !== null && url !== '' && url !== 'null';
    }

    /**
     * Preview profile picture when file is selected
     */
    window.previewProfilePicture = function(input) {
        if (!input?.files?.[0]) return;

        const reader = new FileReader();
        reader.onload = function(e) {
            const previewImg = document.getElementById('profilePicturePreview');
            if (previewImg) {
                previewImg.src = e.target.result;
            }
            toggleRemoveButton(true);
        };
        reader.readAsDataURL(input.files[0]);
    };

    /**
     * Remove profile picture
     */
    window.removeProfilePicture = async function() {
        if (!confirm('Are you sure you want to remove your profile picture?')) {
            return;
        }

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const response = await fetch('/profile/picture', {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken || '',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (response.ok) {
                const previewImg = document.getElementById('profilePicturePreview');
                const fileInput = document.getElementById('profilePictureInput');
                const userId = document.querySelector('[data-user-id]')?.getAttribute('data-user-id') || 
                              document.body.getAttribute('data-user-id') || 0;

                // Reset preview
                if (previewImg) {
                    previewImg.src = getDefaultAvatarUrl(userId);
                }

                // Hide remove button
                toggleRemoveButton(false);

                // Clear file input
                if (fileInput) {
                    fileInput.value = '';
                }

                // Update all avatars
                updateAvatarImages(null, userId);

                if (typeof showToast === 'function') {
                    showToast('Profile picture removed successfully');
                }
            } else {
                if (typeof showToast === 'function') {
                    showToast(data.message || 'Failed to remove profile picture', 'error');
                }
            }
        } catch (error) {
            console.error('Error removing profile picture:', error);
            if (typeof showToast === 'function') {
                showToast('An error occurred while removing profile picture', 'error');
            }
        }
    };

    /**
     * Initialize profile picture in modal
     */
    window.initProfilePicture = function(profileData, userId) {
        const previewImg = document.getElementById('profilePicturePreview');
        const profilePictureUrl = profileData?.profile_picture;
        const hasPicture = hasValidProfilePicture(profilePictureUrl);

        if (previewImg) {
            previewImg.src = hasPicture ? profilePictureUrl : getDefaultAvatarUrl(userId);
        }

        toggleRemoveButton(hasPicture);
    };

    /**
     * Update profile picture after successful upload
     */
    window.updateProfilePictureAfterUpload = function(responseData, userId) {
        const previewImg = document.getElementById('profilePicturePreview');
        const profilePictureUrl = responseData?.user?.profile_picture;
        const hasPicture = hasValidProfilePicture(profilePictureUrl);

        if (previewImg) {
            previewImg.src = hasPicture 
                ? profilePictureUrl + '?t=' + new Date().getTime()
                : getDefaultAvatarUrl(userId);
        }

        toggleRemoveButton(hasPicture);
        updateAvatarImages(profilePictureUrl, userId);
    };

})();

