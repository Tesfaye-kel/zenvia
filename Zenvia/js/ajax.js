/**
 * Zenvia Social Network - AJAX Utilities
 */

// AJAX utility functions
const Ajax = {
    /**
     * Make a GET request
     */
    get: function(url, callback) {
        const xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        callback(null, response);
                    } catch (e) {
                        callback(null, xhr.responseText);
                    }
                } else {
                    callback(new Error('Request failed: ' + xhr.status));
                }
            }
        };
        xhr.send();
    },

    /**
     * Make a POST request
     */
    post: function(url, data, callback) {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', url, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        callback(null, response);
                    } catch (e) {
                        callback(null, xhr.responseText);
                    }
                } else {
                    callback(new Error('Request failed: ' + xhr.status));
                }
            }
        };
        
        // Convert data object to query string
        const params = Object.keys(data)
            .map(key => encodeURIComponent(key) + '=' + encodeURIComponent(data[key]))
            .join('&');
        xhr.send(params);
    },

    /**
     * Make a POST request with FormData
     */
    postFormData: function(url, formData, callback) {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', url, true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        callback(null, response);
                    } catch (e) {
                        callback(null, xhr.responseText);
                    }
                } else {
                    callback(new Error('Request failed: ' + xhr.status));
                }
            }
        };
        xhr.send(formData);
    }
};

// Auto-complete functionality
const AutoComplete = {
    init: function(inputId, suggestionsId, dataUrl) {
        const input = document.getElementById(inputId);
        const suggestions = document.getElementById(suggestionsId);
        
        if (!input || !suggestions) return;
        
        let debounceTimer;
        
        input.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const query = this.value;
            
            if (query.length < 2) {
                suggestions.style.display = 'none';
                return;
            }
            
            debounceTimer = setTimeout(function() {
                Ajax.get(dataUrl + '?q=' + encodeURIComponent(query), function(err, response) {
                    if (err) {
                        console.error(err);
                        return;
                    }
                    
                    if (response && response.length > 0) {
                        AutoComplete.renderSuggestions(suggestions, response);
                    } else {
                        suggestions.style.display = 'none';
                    }
                });
            }, 300);
        });
        
        // Hide suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!input.contains(e.target) && !suggestions.contains(e.target)) {
                suggestions.style.display = 'none';
            }
        });
    },
    
    renderSuggestions: function(container, items) {
        container.innerHTML = '';
        
        items.forEach(function(item) {
            const div = document.createElement('div');
            div.className = 'suggestion-item';
            div.innerHTML = '<img src="' + item.profile_pic + '" alt="">' +
                           '<span>' + item.first_name + ' ' + item.last_name + '</span>';
            div.addEventListener('click', function() {
                window.location.href = 'profile.php?id=' + item.id;
            });
            container.appendChild(div);
        });
        
        container.style.display = 'block';
    }
};

// Live search functionality
const LiveSearch = {
    searchTimeout: null,
    
    init: function(inputSelector, resultsSelector, searchUrl) {
        const input = document.querySelector(inputSelector);
        const results = document.querySelector(resultsSelector);
        
        if (!input || !results) return;
        
        input.addEventListener('input', function() {
            const query = this.value.trim();
            
            clearTimeout(LiveSearch.searchTimeout);
            
            if (query.length < 2) {
                results.innerHTML = '';
                results.style.display = 'none';
                return;
            }
            
            LiveSearch.searchTimeout = setTimeout(function() {
                Ajax.get(searchUrl + '?q=' + encodeURIComponent(query), function(err, response) {
                    if (err) {
                        console.error(err);
                        return;
                    }
                    
                    if (response && response.length > 0) {
                        LiveSearch.renderResults(results, response);
                    } else {
                        results.innerHTML = '<div class="no-results">No results found</div>';
                        results.style.display = 'block';
                    }
                });
            }, 300);
        });
    },
    
    renderResults: function(container, results) {
        container.innerHTML = '';
        
        results.forEach(function(user) {
            const div = document.createElement('a');
            div.href = 'profile.php?id=' + user.id;
            div.className = 'search-result-item';
            div.innerHTML = '<img src="images/profile_pics/' + user.profile_pic + '" alt="">' +
                           '<div><strong>' + user.first_name + ' ' + user.last_name + '</strong>' +
                           '<span>@' + user.username + '</span></div>';
            container.appendChild(div);
        });
        
        container.style.display = 'block';
    }
};

// Infinite scroll
const InfiniteScroll = {
    loading: false,
    offset: 0,
    limit: 10,
    container: null,
    loader: null,
    loadMoreUrl: null,
    
    init: function(containerSelector, loaderSelector, url, limit) {
        this.container = document.querySelector(containerSelector);
        this.loader = document.querySelector(loaderSelector);
        this.loadMoreUrl = url;
        this.limit = limit || 10;
        
        if (!this.container) return;
        
        // Initial load
        this.loadMore();
        
        // Scroll event
        window.addEventListener('scroll', () => {
            if (this.loading) return;
            
            const scrollPosition = window.innerHeight + window.scrollY;
            const threshold = document.body.offsetHeight - 200;
            
            if (scrollPosition >= threshold) {
                this.loadMore();
            }
        });
    },
    
    loadMore: function() {
        if (this.loading) return;
        this.loading = true;
        
        if (this.loader) {
            this.loader.style.display = 'block';
        }
        
        const url = this.loadMoreUrl + '&offset=' + this.offset + '&limit=' + this.limit;
        
        Ajax.get(url, (err, response) => {
            this.loading = false;
            
            if (this.loader) {
                this.loader.style.display = 'none';
            }
            
            if (err) {
                console.error(err);
                return;
            }
            
            if (response && response.html) {
                const temp = document.createElement('div');
                temp.innerHTML = response.html;
                
                while (temp.firstChild) {
                    this.container.appendChild(temp.firstChild);
                }
                
                this.offset += this.limit;
                
                // Reinitialize any JS functionality for new elements
                if (typeof initLikeButtons === 'function') {
                    initLikeButtons();
                }
                if (typeof initCommentForms === 'function') {
                    initCommentForms();
                }
            }
            
            if (response && response.end) {
                // No more items to load
                if (this.loader) {
                    this.loader.style.display = 'none';
                }
                window.removeEventListener('scroll', this.loadMore);
            }
        });
    }
};

// Image upload with preview
const ImageUploader = {
    init: function(inputSelector, previewSelector) {
        const input = document.querySelector(inputSelector);
        const preview = document.querySelector(previewSelector);
        
        if (!input || !preview) return;
        
        input.addEventListener('change', function() {
            const file = this.files[0];
            
            if (file) {
                // Check file type
                const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!validTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPEG, PNG, GIF, or WebP)');
                    this.value = '';
                    return;
                }
                
                // Check file size (max 5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('Image size must be less than 5MB');
                    this.value = '';
                    return;
                }
                
                // Show preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
                preview.innerHTML = '';
            }
        });
    }
};

// Form validation
const FormValidator = {
    validateEmail: function(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    },
    
    validateUsername: function(username) {
        const re = /^[a-zA-Z0-9_]{3,20}$/;
        return re.test(username);
    },
    
    validatePassword: function(password) {
        return password.length >= 6;
    },
    
    showError: function(input, message) {
        const formGroup = input.closest('.form-group');
        let errorEl = formGroup.querySelector('.error-message');
        
        if (!errorEl) {
            errorEl = document.createElement('div');
            errorEl.className = 'error-message';
            formGroup.appendChild(errorEl);
        }
        
        errorEl.textContent = message;
        input.classList.add('error');
    },
    
    clearError: function(input) {
        const formGroup = input.closest('.form-group');
        const errorEl = formGroup.querySelector('.error-message');
        
        if (errorEl) {
            errorEl.remove();
        }
        
        input.classList.remove('error');
    }
};

// Real-time notifications
const Notifications = {
    pollInterval: null,
    
    init: function(checkUrl, interval) {
        this.checkUrl = checkUrl;
        this.interval = interval || 30000; // Default 30 seconds
        
        this.check();
        
        // Set up polling
        this.pollInterval = setInterval(() => {
            this.check();
        }, this.interval);
    },
    
    check: function() {
        Ajax.get(this.checkUrl, function(err, response) {
            if (err) {
                console.error(err);
                return;
            }
            
            if (response && response.unread > 0) {
                Notifications.updateBadge(response.unread);
                
                if (response.latest) {
                    Notifications.showToast(response.latest);
                }
            }
        });
    },
    
    updateBadge: function(count) {
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'block' : 'none';
        }
    },
    
    showToast: function(notification) {
        const toast = document.createElement('div');
        toast.className = 'notification-toast';
        toast.innerHTML = '<img src="' + notification.profile_pic + '">' +
                         '<div><strong>' + notification.from_name + '</strong> ' +
                         notification.message + '</div>';
        
        document.body.appendChild(toast);
        
        // Auto remove after 5 seconds
        setTimeout(function() {
            toast.classList.add('fade-out');
            setTimeout(function() {
                toast.remove();
            }, 300);
        }, 5000);
    },
    
    stop: function() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
        }
    }
};

// Export functions to global scope
window.Ajax = Ajax;
window.AutoComplete = AutoComplete;
window.LiveSearch = LiveSearch;
window.InfiniteScroll = InfiniteScroll;
window.ImageUploader = ImageUploader;
window.FormValidator = FormValidator;
window.Notifications = Notifications;
