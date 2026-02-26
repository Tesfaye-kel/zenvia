/**
 * Zenvia Social Network - Main JavaScript
 */

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize all functions
    initLikeButtons();
    initCommentForms();
    initFriendButtons();
    initImagePreview();
    initAutoResize();
    initSearchHighlight();
    initThemeToggle();
});

/**
 * Initialize theme toggle functionality
 */
function initThemeToggle() {
    const themeToggle = document.getElementById('themeToggle');
    if (!themeToggle) return;
    
    // Load saved theme from localStorage
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
    }
    
    // Add click event listener
    themeToggle.addEventListener('click', function() {
        toggleTheme();
    });
}

/**
 * Toggle between light and dark mode
 */
function toggleTheme() {
    const body = document.body;
    body.classList.toggle('dark-mode');
    
    // Save preference to localStorage
    if (body.classList.contains('dark-mode')) {
        localStorage.setItem('theme', 'dark');
    } else {
        localStorage.setItem('theme', 'light');
    }
}

/**
 * Set specific theme
 */
function setTheme(theme) {
    if (theme === 'dark') {
        document.body.classList.add('dark-mode');
        localStorage.setItem('theme', 'dark');
    } else {
        document.body.classList.remove('dark-mode');
        localStorage.setItem('theme', 'light');
    }
}

/**
 * Get current theme
 */
function getCurrentTheme() {
    return document.body.classList.contains('dark-mode') ? 'dark' : 'light';
}

/**
 * Initialize like button functionality
 */
function initLikeButtons() {
    const likeButtons = document.querySelectorAll('.like-btn');
    
    likeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const postId = this.dataset.postId;
            const action = this.classList.contains('liked') ? 'unlike' : 'like';
            const likeCountEl = document.getElementById('like-count-' + postId);
            
            fetch('like.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=' + action + '&post_id=' + postId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.classList.toggle('liked');
                    if (likeCountEl) {
                        let count = parseInt(likeCountEl.textContent);
                        likeCountEl.textContent = action === 'like' ? count + 1 : count - 1;
                    }
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });
}

/**
 * Like a post (standalone function)
 */
function likePost(postId) {
    const button = document.getElementById('like-btn-' + postId);
    if (!button) return;
    
    const action = button.classList.contains('liked') ? 'unlike' : 'like';
    const likeCountEl = document.getElementById('like-count-' + postId);
    
    fetch('like.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=' + action + '&post_id=' + postId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            button.classList.toggle('liked');
            if (likeCountEl) {
                let count = parseInt(likeCountEl.textContent);
                likeCountEl.textContent = action === 'like' ? count + 1 : count - 1;
            }
        }
    })
    .catch(error => console.error('Error:', error));
}

/**
 * Check if user has liked a post
 */
function checkLikeStatus(postId) {
    fetch('like.php?post_id=' + postId + '&action=check')
    .then(response => response.json())
    .then(data => {
        if (data.success && data.liked) {
            const button = document.getElementById('like-btn-' + postId);
            if (button) {
                button.classList.add('liked');
            }
        }
    })
    .catch(error => console.error('Error:', error));
}

/**
 * Get like count for a post
 */
function getLikeCount(postId) {
    fetch('like.php?post_id=' + postId + '&action=count')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const countEl = document.getElementById('like-count-' + postId);
            if (countEl) {
                countEl.textContent = data.count;
            }
        }
    })
    .catch(error => console.error('Error:', error));
}

/**
 * Initialize comment forms
 */
function initCommentForms() {
    const commentForms = document.querySelectorAll('.comment-form');
    
    commentForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const postId = this.querySelector('input[name="post_id"]').value;
            const content = this.querySelector('input[name="content"]').value;
            
            if (!content.trim()) {
                return;
            }
            
            fetch('comment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=add_comment&post_id=' + postId + '&content=' + encodeURIComponent(content)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Add comment to the list
                    const commentsList = document.getElementById('comments-list-' + postId);
                    if (commentsList) {
                        const commentHtml = createCommentElement(data.comment);
                        commentsList.insertAdjacentHTML('beforeend', commentHtml);
                    }
                    
                    // Clear input
                    this.querySelector('input[name="content"]').value = '';
                    
                    // Update comment count
                    const countEl = document.getElementById('comment-count-' + postId);
                    if (countEl) {
                        countEl.textContent = parseInt(countEl.textContent) + 1;
                    }
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });
}

/**
 * Create comment HTML element
 */
function createCommentElement(comment) {
    return `
        <div class="comment">
            <img src="images/profile_pics/${comment.profile_pic}" alt="${comment.username}" class="comment-avatar">
            <div class="comment-content">
                <div class="comment-user">${comment.first_name} ${comment.last_name}</div>
                <p class="comment-text">${comment.content}</p>
                <div class="comment-time">Just now</div>
            </div>
        </div>
    `;
}

/**
 * Show comment box for a post
 */
function showCommentBox(postId) {
    const commentBox = document.getElementById('comment-box-' + postId);
    if (commentBox) {
        commentBox.style.display = 'block';
        const input = commentBox.querySelector('input[name="content"]');
        if (input) {
            input.focus();
        }
    }
}

/**
 * Initialize friend buttons
 */
function initFriendButtons() {
    const friendButtons = document.querySelectorAll('.friend-btn');
    
    friendButtons.forEach(button => {
        button.addEventListener('click', function() {
            const friendId = this.dataset.friendId;
            const action = this.dataset.action;
            
            fetch('friends.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=' + action + '&friend_id=' + friendId
            })
            .then(response => response.text())
            .then(html => {
                // Reload the page to show updated friend status
                location.reload();
            })
            .catch(error => console.error('Error:', error));
        });
    });
}

/**
 * Add a friend
 */
function addFriend(friendId) {
    fetch('friends.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=add_friend&friend_id=' + friendId
    })
    .then(response => response.text())
    .then(html => {
        location.reload();
    })
    .catch(error => console.error('Error:', error));
}

/**
 * Initialize image preview
 */
function initImagePreview() {
    const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
    
    imageInputs.forEach(input => {
        input.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Create preview if it doesn't exist
                    let preview = input.closest('.form-group').querySelector('.image-preview');
                    if (!preview) {
                        preview = document.createElement('div');
                        preview.className = 'image-preview';
                        input.closest('.form-group').appendChild(preview);
                    }
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview" style="max-width: 200px; margin-top: 10px;">';
                };
                reader.readAsDataURL(file);
            }
        });
    });
}

/**
 * Auto-resize textarea
 */
function initAutoResize() {
    const textareas = document.querySelectorAll('textarea[auto-resize]');
    
    textareas.forEach(textarea => {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
        
        // Initial resize
        textarea.dispatchEvent(new Event('input'));
    });
}

/**
 * Search highlight
 */
function initSearchHighlight() {
    const searchInput = document.querySelector('.header-search input');
    
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const query = this.value.toLowerCase();
            if (query.length < 2) return;
            
            // Highlight search terms in results
            const results = document.querySelectorAll('.search-result, .result-item, .friend-card');
            results.forEach(result => {
                const text = result.textContent.toLowerCase();
                if (text.includes(query)) {
                    result.style.backgroundColor = '#fff3cd';
                } else {
                    result.style.backgroundColor = '';
                }
            });
        });
    }
}

/**
 * Show notification
 */
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = 'alert alert-' + type;
    notification.textContent = message;
    notification.style.position = 'fixed';
    notification.style.top = '80px';
    notification.style.right = '20px';
    notification.style.zIndex = '2000';
    notification.style.minWidth = '250px';
    notification.style.animation = 'slideIn 0.3s ease-out';
    
    document.body.appendChild(notification);
    
    // Auto-remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-in';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

/**
 * Delete a post
 */
function deletePost(postId) {
    if (!confirm('Are you sure you want to delete this post?')) {
        return;
    }
    
    fetch('post.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=delete_post&post_id=' + postId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const post = document.getElementById('post-' + postId);
            if (post) {
                post.remove();
            }
            showNotification('Post deleted successfully', 'success');
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => console.error('Error:', error));
}

/**
 * Delete a comment
 */
function deleteComment(commentId, postId) {
    if (!confirm('Are you sure you want to delete this comment?')) {
        return;
    }
    
    fetch('comment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=delete_comment&comment_id=' + commentId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const comment = document.getElementById('comment-' + commentId);
            if (comment) {
                comment.remove();
            }
            
            // Update comment count
            const countEl = document.getElementById('comment-count-' + postId);
            if (countEl) {
                countEl.textContent = parseInt(countEl.textContent) - 1;
            }
            
            showNotification('Comment deleted successfully', 'success');
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => console.error('Error:', error));
}

/**
 * Load more posts (infinite scroll)
 */
let loadingMorePosts = false;
function loadMorePosts(offset, limit) {
    if (loadingMorePosts) return;
    loadingMorePosts = true;
    
    fetch('load_posts.php?offset=' + offset + '&limit=' + limit)
    .then(response => response.text())
    .then(html => {
        const feed = document.querySelector('.feed');
        if (feed && html) {
            feed.insertAdjacentHTML('beforeend', html);
            initLikeButtons();
            initCommentForms();
        }
        loadingMorePosts = false;
    })
    .catch(error => {
        console.error('Error:', error);
        loadingMorePosts = false;
    });
}

/**
 * Initialize infinite scroll
 */
function initInfiniteScroll() {
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const loadMoreTrigger = document.getElementById('load-more-trigger');
                    if (loadMoreTrigger) {
                        const offset = parseInt(loadMoreTrigger.dataset.offset);
                        const limit = parseInt(loadMoreTrigger.dataset.limit);
                        loadMorePosts(offset, limit);
                    }
                }
            });
        });
        
        const trigger = document.getElementById('load-more-trigger');
        if (trigger) {
            observer.observe(trigger);
        }
    }
}

/**
 * Format relative time
 */
function timeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    
    if (seconds < 60) {
        return 'Just now';
    } else if (seconds < 3600) {
        const minutes = Math.floor(seconds / 60);
        return minutes + ' minute' + (minutes > 1 ? 's' : '') + ' ago';
    } else if (seconds < 86400) {
        const hours = Math.floor(seconds / 3600);
        return hours + ' hour' + (hours > 1 ? 's' : '') + ' ago';
    } else if (seconds < 604800) {
        const days = Math.floor(seconds / 86400);
        return days + ' day' + (days > 1 ? 's' : '') + ' ago';
    } else {
        return date.toLocaleDateString();
    }
}

/**
 * Copy to clipboard
 */
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            showNotification('Copied to clipboard', 'success');
        });
    } else {
        // Fallback
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        showNotification('Copied to clipboard', 'success');
    }
}

/**
 * Share post
 */
function sharePost(postId) {
    const url = window.location.origin + '/post.php?id=' + postId;
    copyToClipboard(url);
}

/**
 * Open modal
 */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

/**
 * Close modal
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modals = document.querySelectorAll('.modal[style*="block"]');
        modals.forEach(modal => {
            modal.style.display = 'none';
        });
        document.body.style.overflow = 'auto';
    }
});
