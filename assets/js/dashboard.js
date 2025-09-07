/**
 * Dashboard JavaScript
 * Project Vault - Dr. YC James Yen Government Polytechnic, Kuppam
 */

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeDashboard();
});

/**
 * Initialize dashboard functionality
 */
function initializeDashboard() {
    // User dropdown is handled by main.js
    // No need to initialize here to avoid conflicts
    
    // Initialize interactive elements
    initInteractiveElements();
    
    // Load real-time updates
    startRealTimeUpdates();
}

// User dropdown functionality is handled by main.js
// No duplicate functions needed here





/**
 * Initialize interactive elements
 */
function initInteractiveElements() {
    // Initialize tooltips for stat cards
    initStatCardTooltips();
    
    // Initialize project item interactions
    initProjectItemInteractions();
    
    // Initialize action button animations
    initActionButtonAnimations();
}

/**
 * Initialize stat card tooltips
 */
function initStatCardTooltips() {
    const statCards = document.querySelectorAll('.stat-card');
    
    statCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
}

/**
 * Initialize project item interactions
 */
function initProjectItemInteractions() {
    const projectItems = document.querySelectorAll('.project-item');
    
    projectItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(5px)';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
        });
    });
}

/**
 * Initialize action button animations
 */
function initActionButtonAnimations() {
    const actionButtons = document.querySelectorAll('.action-btn');
    
    actionButtons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            const icon = this.querySelector('i');
            if (icon) {
                icon.style.transform = 'scale(1.2) rotate(5deg)';
            }
        });
        
        button.addEventListener('mouseleave', function() {
            const icon = this.querySelector('i');
            if (icon) {
                icon.style.transform = 'scale(1) rotate(0deg)';
            }
        });
    });
}

/**
 * Start real-time updates
 */
function startRealTimeUpdates() {
    
    // Update dashboard stats every 60 seconds
    setInterval(updateDashboardStats, 60000);
}


/**
 * Update dashboard stats
 */
function updateDashboardStats() {
    fetch('../api/dashboard.php?action=get_stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStatNumbers(data.stats);
            }
        })
        .catch(error => {
            console.error('Error updating dashboard stats:', error);
        });
}

/**
 * Update stat numbers with animation
 */
function updateStatNumbers(stats) {
    const statNumbers = document.querySelectorAll('.stat-number');
    
    statNumbers.forEach((statNumber, index) => {
        const currentValue = parseInt(statNumber.textContent);
        const newValue = stats[Object.keys(stats)[index]];
        
        if (currentValue !== newValue) {
            animateNumber(statNumber, currentValue, newValue);
        }
    });
}

/**
 * Animate number change
 */
function animateNumber(element, start, end) {
    const duration = 1000; // 1 second
    const startTime = performance.now();
    
    function updateNumber(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        const current = Math.round(start + (end - start) * progress);
        element.textContent = current;
        
        if (progress < 1) {
            requestAnimationFrame(updateNumber);
        }
    }
    
    requestAnimationFrame(updateNumber);
}

/**
 * Show loading state for action buttons
 */
function showActionLoading(button) {
    const originalContent = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
    button.disabled = true;
    
    return originalContent;
}

/**
 * Hide loading state for action buttons
 */
function hideActionLoading(button, originalContent) {
    button.innerHTML = originalContent;
    button.disabled = false;
}

/**
 * Handle quick action clicks
 */
function handleQuickAction(action, button) {
    const originalContent = showActionLoading(button);
    
    // Simulate action processing
    setTimeout(() => {
        hideActionLoading(button, originalContent);
        
        // Navigate to appropriate page
        switch (action) {
            case 'add_project':
                window.location.href = 'projects/add.php';
                break;
            case 'explore':
                window.location.href = '../projects/';
                break;
            case 'collaborations':
                window.location.href = 'collaborations/';
                break;
            case 'manage_users':
                window.location.href = 'admin/users.php';
                break;
        }
    }, 1000);
}

/**
 * Export functions for global use
 */
window.handleQuickAction = handleQuickAction;
