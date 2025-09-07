/**
 * Main JavaScript File
 * Project Vault - Dr. YC James Yen Government Polytechnic, Kuppam
 */

// Global variables
let notificationCount = 0;

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

/**
 * Initialize the application
 */
function initializeApp() {
    // Initialize notification system
    initNotifications();
    
    // Initialize form validations
    initFormValidations();
    
    // Initialize interactive elements
    initInteractiveElements();
    
    // Initialize user dropdown
    initUserDropdown();
    
    // Load notifications if user is logged in
    if (document.body.classList.contains('logged-in')) {
        loadNotifications();
    }
}

/**
 * Initialize notification system
 */
function initNotifications() {
    const notificationIcon = document.getElementById('notificationIcon');
    const notificationDropdown = document.getElementById('notificationDropdown');
    
    if (notificationIcon && notificationDropdown) {
        notificationIcon.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleNotificationDropdown();
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!notificationIcon.contains(e.target) && !notificationDropdown.contains(e.target)) {
                notificationDropdown.style.display = 'none';
            }
        });
    }
}

/**
 * Toggle notification dropdown
 */
function toggleNotificationDropdown() {
    const dropdown = document.getElementById('notificationDropdown');
    if (dropdown) {
        const isVisible = dropdown.style.display === 'block';
        dropdown.style.display = isVisible ? 'none' : 'block';
        
        if (!isVisible) {
            markNotificationsAsRead();
        }
    }
}

/**
 * Load notifications from server
 */
function loadNotifications() {
    fetch('api/notifications.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationUI(data.notifications);
            }
        })
        .catch(error => {
            console.error('Error loading notifications:', error);
        });
}

/**
 * Update notification UI
 */
function updateNotificationUI(notifications) {
    const dropdown = document.getElementById('notificationDropdown');
    const badge = document.getElementById('notificationBadge');
    
    if (!dropdown) return;
    
    // Clear existing notifications
    dropdown.innerHTML = '';
    
    if (notifications.length === 0) {
        dropdown.innerHTML = '<div class="notification-item text-center">No notifications</div>';
    } else {
        notifications.forEach(notification => {
            const notificationElement = createNotificationElement(notification);
            dropdown.appendChild(notificationElement);
        });
    }
    
    // Update badge
    const unreadCount = notifications.filter(n => !n.is_read).length;
    if (badge) {
        badge.textContent = unreadCount;
        badge.style.display = unreadCount > 0 ? 'block' : 'none';
    }
}

/**
 * Create notification element
 */
function createNotificationElement(notification) {
    const div = document.createElement('div');
    div.className = `notification-item ${notification.is_read ? '' : 'unread'}`;
    
    div.innerHTML = `
        <div class="notification-title">${escapeHtml(notification.title)}</div>
        <div class="notification-message">${escapeHtml(notification.message)}</div>
        <div class="notification-time">${formatTime(notification.created_at)}</div>
    `;
    
    div.addEventListener('click', function() {
        markNotificationAsRead(notification.id);
        if (notification.related_id) {
            handleNotificationClick(notification);
        }
    });
    
    return div;
}

/**
 * Mark notification as read
 */
function markNotificationAsRead(notificationId) {
    fetch('api/notifications.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'mark_read',
            notification_id: notificationId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadNotifications(); // Reload notifications
        }
    })
    .catch(error => {
        console.error('Error marking notification as read:', error);
    });
}

/**
 * Mark all notifications as read
 */
function markNotificationsAsRead() {
    fetch('api/notifications.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'mark_all_read'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadNotifications(); // Reload notifications
        }
    })
    .catch(error => {
        console.error('Error marking notifications as read:', error);
    });
}

/**
 * Handle notification click
 */
function handleNotificationClick(notification) {
    switch (notification.type) {
        case 'collaboration_request':
        case 'collaboration_response':
            window.location.href = 'dashboard/collaborations.php';
            break;
        case 'project_approval':
            window.location.href = 'dashboard/projects.php';
            break;
        case 'password_reset':
            window.location.href = 'dashboard/settings.php';
            break;
    }
}

/**
 * Initialize form validations
 */
function initFormValidations() {
    // Password confirmation validation
    const passwordField = document.getElementById('password');
    const confirmPasswordField = document.getElementById('confirm_password');
    
    if (passwordField && confirmPasswordField) {
        confirmPasswordField.addEventListener('input', function() {
            validatePasswordConfirmation();
        });
    }
    
    // Roll number validation
    const rollNumberField = document.getElementById('roll_number');
    if (rollNumberField) {
        rollNumberField.addEventListener('input', function() {
            validateRollNumber();
        });
    }
}

/**
 * Validate password confirmation
 */
function validatePasswordConfirmation() {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const errorElement = document.getElementById('passwordError');
    
    if (confirmPassword && password !== confirmPassword) {
        showFieldError('confirm_password', 'Passwords do not match');
    } else {
        clearFieldError('confirm_password');
    }
}

/**
 * Validate roll number
 */
function validateRollNumber() {
    const rollNumber = document.getElementById('roll_number').value;
    
    if (rollNumber && rollNumber.length !== 12) {
        showFieldError('roll_number', 'Roll number must be exactly 12 characters');
    } else {
        clearFieldError('roll_number');
    }
}

/**
 * Show field error
 */
function showFieldError(fieldId, message) {
    const field = document.getElementById(fieldId);
    const errorElement = document.getElementById(fieldId + 'Error');
    
    if (field) {
        field.style.borderColor = '#dc3545';
    }
    
    if (!errorElement) {
        const error = document.createElement('div');
        error.id = fieldId + 'Error';
        error.className = 'field-error';
        error.style.color = '#dc3545';
        error.style.fontSize = '0.875rem';
        error.style.marginTop = '0.25rem';
        field.parentNode.appendChild(error);
    }
    
    const errorDiv = document.getElementById(fieldId + 'Error');
    if (errorDiv) {
        errorDiv.textContent = message;
    }
}

/**
 * Clear field error
 */
function clearFieldError(fieldId) {
    const field = document.getElementById(fieldId);
    const errorElement = document.getElementById(fieldId + 'Error');
    
    if (field) {
        field.style.borderColor = '#e9ecef';
    }
    
    if (errorElement) {
        errorElement.remove();
    }
}

/**
 * Initialize interactive elements
 */
function initInteractiveElements() {
    // Initialize tooltips
    initTooltips();
    
    // Initialize modals
    initModals();
    
    // Initialize search functionality
    initSearch();
}

/**
 * Initialize tooltips
 */
function initTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
}

/**
 * Show tooltip
 */
function showTooltip(e) {
    const text = e.target.getAttribute('data-tooltip');
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = text;
    tooltip.style.cssText = `
        position: absolute;
        background: #333;
        color: white;
        padding: 0.5rem;
        border-radius: 4px;
        font-size: 0.875rem;
        z-index: 1000;
        pointer-events: none;
    `;
    
    document.body.appendChild(tooltip);
    
    const rect = e.target.getBoundingClientRect();
    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
    tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
    
    e.target.tooltipElement = tooltip;
}

/**
 * Hide tooltip
 */
function hideTooltip(e) {
    if (e.target.tooltipElement) {
        e.target.tooltipElement.remove();
        e.target.tooltipElement = null;
    }
}

/**
 * Initialize modals
 */
function initModals() {
    const modalTriggers = document.querySelectorAll('[data-modal]');
    const modalCloses = document.querySelectorAll('.modal-close');
    
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const modalId = this.getAttribute('data-modal');
            openModal(modalId);
        });
    });
    
    modalCloses.forEach(close => {
        close.addEventListener('click', function() {
            closeModal(this.closest('.modal'));
        });
    });
    
    // Close modal when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            closeModal(e.target);
        }
    });
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
function closeModal(modal) {
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

/**
 * Initialize search functionality
 */
function initSearch() {
    const searchInput = document.getElementById('searchInput');
    const searchForm = document.getElementById('searchForm');
    
    if (searchInput) {
        searchInput.addEventListener('input', debounce(handleSearch, 300));
    }
    
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleSearch();
        });
    }
}

/**
 * Handle search
 */
function handleSearch() {
    const searchInput = document.getElementById('searchInput');
    const searchTerm = searchInput ? searchInput.value.trim() : '';
    
    if (searchTerm.length >= 2) {
        // Implement search logic here
        console.log('Searching for:', searchTerm);
    }
}

/**
 * Utility Functions
 */

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Format time for display
 */
function formatTime(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const diff = now - date;
    
    if (diff < 60000) { // Less than 1 minute
        return 'Just now';
    } else if (diff < 3600000) { // Less than 1 hour
        const minutes = Math.floor(diff / 60000);
        return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
    } else if (diff < 86400000) { // Less than 1 day
        const hours = Math.floor(diff / 3600000);
        return `${hours} hour${hours > 1 ? 's' : ''} ago`;
    } else {
        return date.toLocaleDateString();
    }
}

/**
 * Debounce function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Show loading spinner
 */
function showLoading(element) {
    if (element) {
        element.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
        element.disabled = true;
    }
}

/**
 * Hide loading spinner
 */
function hideLoading(element, originalText) {
    if (element) {
        element.innerHTML = originalText;
        element.disabled = false;
    }
}

/**
 * Show alert message
 */
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.innerHTML = `
        <i class="fas fa-${getAlertIcon(type)}"></i>
        ${escapeHtml(message)}
    `;
    
    // Insert at the top of the page
    const container = document.querySelector('.container') || document.body;
    container.insertBefore(alertDiv, container.firstChild);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

/**
 * Get alert icon based on type
 */
function getAlertIcon(type) {
    const icons = {
        success: 'check-circle',
        error: 'exclamation-circle',
        warning: 'exclamation-triangle',
        info: 'info-circle'
    };
    return icons[type] || 'info-circle';
}

/**
 * Confirm action
 */
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

/**
 * Copy text to clipboard
 */
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showAlert('Copied to clipboard!', 'success');
    }).catch(() => {
        showAlert('Failed to copy to clipboard', 'error');
    });
}

/**
 * Initialize user dropdown menu
 */
function initUserDropdown() {
    const userDropdowns = document.querySelectorAll('.user-dropdown');
    
    userDropdowns.forEach(dropdown => {
        const button = dropdown.querySelector('.user-button');
        const menu = dropdown.querySelector('.dropdown-menu');
        
        if (button && menu) {
            button.addEventListener('click', function(e) {
                e.stopPropagation();
                toggleUserDropdown(dropdown);
            });
        }
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        userDropdowns.forEach(dropdown => {
            if (!dropdown.contains(e.target)) {
                closeUserDropdown(dropdown);
            }
        });
    });
    
    // Close dropdowns when pressing Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            userDropdowns.forEach(dropdown => {
                closeUserDropdown(dropdown);
            });
        }
    });
}

/**
 * Toggle user dropdown
 */
function toggleUserDropdown(dropdown) {
    const isActive = dropdown.classList.contains('active');
    
    // Close all other dropdowns
    document.querySelectorAll('.user-dropdown').forEach(d => {
        if (d !== dropdown) {
            closeUserDropdown(d);
        }
    });
    
    if (isActive) {
        closeUserDropdown(dropdown);
    } else {
        openUserDropdown(dropdown);
    }
}

/**
 * Open user dropdown
 */
function openUserDropdown(dropdown) {
    dropdown.classList.add('active');
}

/**
 * Close user dropdown
 */
function closeUserDropdown(dropdown) {
    dropdown.classList.remove('active');
}
