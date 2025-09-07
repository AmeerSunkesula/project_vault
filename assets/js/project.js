/**
 * Project Detail Page JavaScript
 * Project Vault - Dr. YC James Yen Government Polytechnic, Kuppam
 */

// Initialize project page when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeProject();
});

/**
 * Initialize project page functionality
 */
function initializeProject() {
    // Initialize action buttons
    initActionButtons();
    
    // Initialize comment system
    initCommentSystem();
    
    // Load comments
    loadComments();
    
    // Initialize collaboration requests
    initCollaborationRequests();
}

/**
 * Initialize action buttons (upvote, downvote, star)
 */
function initActionButtons() {
    const actionButtons = document.querySelectorAll('.action-btn[data-action]');
    
    actionButtons.forEach(button => {
        button.addEventListener('click', function() {
            const action = this.dataset.action;
            const projectId = this.dataset.projectId;
            
            if (action && projectId) {
                handleAction(action, projectId, this);
            }
        });
    });
}

/**
 * Handle action button clicks
 */
function handleAction(action, projectId, button) {
    if (!isLoggedIn()) {
        showAlert('Please login to interact with projects', 'warning');
        return;
    }
    
    if (action === 'collaborate') {
        handleCollaborationRequest(projectId, button);
        return;
    }
    
    // Add loading state
    button.classList.add('loading');
    button.disabled = true;
    
    // Send request to server
    fetch('api/projects.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: action,
            project_id: projectId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update button state
            updateActionButton(button, data);
            
            // Update other related buttons if needed
            if (action === 'upvote' || action === 'downvote') {
                updateVoteButtons(action, data);
            }
            
            // Show success message
            showAlert(data.message, 'success');
        } else {
            showAlert(data.message || 'Action failed', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Network error. Please try again.', 'error');
    })
    .finally(() => {
        // Remove loading state
        button.classList.remove('loading');
        button.disabled = false;
    });
}

/**
 * Update action button state
 */
function updateActionButton(button, data) {
    const countElement = button.querySelector('.count');
    if (countElement) {
        countElement.textContent = data.count;
    }
    
    // Update active state
    if (data.is_active) {
        button.classList.add('active');
    } else {
        button.classList.remove('active');
    }
    
    // Add animation
    button.classList.add('updated');
    setTimeout(() => {
        button.classList.remove('updated');
    }, 500);
}

/**
 * Update vote buttons (handle mutual exclusivity)
 */
function updateVoteButtons(action, data) {
    const upvoteBtn = document.querySelector('.upvote-btn');
    const downvoteBtn = document.querySelector('.downvote-btn');
    
    if (action === 'upvote') {
        if (data.is_active) {
            // Remove downvote if it was active
            if (downvoteBtn && downvoteBtn.classList.contains('active')) {
                downvoteBtn.classList.remove('active');
                // Update downvote count
                fetch('api/projects.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'get_stats',
                        project_id: window.projectData.id
                    })
                })
                .then(response => response.json())
                .then(statsData => {
                    if (statsData.success) {
                        const downvoteCount = downvoteBtn.querySelector('.count');
                        if (downvoteCount) {
                            downvoteCount.textContent = statsData.stats.downvotes;
                        }
                    }
                });
            }
        }
    } else if (action === 'downvote') {
        if (data.is_active) {
            // Remove upvote if it was active
            if (upvoteBtn && upvoteBtn.classList.contains('active')) {
                upvoteBtn.classList.remove('active');
                // Update upvote count
                fetch('api/projects.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'get_stats',
                        project_id: window.projectData.id
                    })
                })
                .then(response => response.json())
                .then(statsData => {
                    if (statsData.success) {
                        const upvoteCount = upvoteBtn.querySelector('.count');
                        if (upvoteCount) {
                            upvoteCount.textContent = statsData.stats.upvotes;
                        }
                    }
                });
            }
        }
    }
}

/**
 * Initialize comment system
 */
function initCommentSystem() {
    const commentForm = document.getElementById('commentForm');
    
    if (commentForm) {
        commentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitComment(this);
        });
    }
}

/**
 * Submit comment
 */
function submitComment(form) {
    const content = form.querySelector('textarea[name="content"]').value.trim();
    
    if (!content) {
        showAlert('Please enter a comment', 'warning');
        return;
    }
    
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    
    // Show loading state
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Posting...';
    submitButton.disabled = true;
    
    // Send comment to server
    fetch('api/comments.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'add',
            project_id: window.projectData.id,
            content: content
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Clear form
            form.reset();
            
            // Reload comments
            loadComments();
            
            // Show success message
            showAlert('Comment posted successfully', 'success');
        } else {
            showAlert(data.message || 'Failed to post comment', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Network error. Please try again.', 'error');
    })
    .finally(() => {
        // Restore button state
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
    });
}

/**
 * Load comments
 */
function loadComments() {
    const commentsList = document.getElementById('commentsList');
    if (!commentsList) return;
    
    fetch(`api/comments.php?action=get&project_id=${window.projectData.id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayComments(data.comments);
            } else {
                console.error('Error loading comments:', data.message);
            }
        })
        .catch(error => {
            console.error('Error loading comments:', error);
        });
}

/**
 * Display comments
 */
function displayComments(comments) {
    const commentsList = document.getElementById('commentsList');
    if (!commentsList) return;
    
    if (comments.length === 0) {
        commentsList.innerHTML = '<p class="text-center text-muted">No comments yet. Be the first to comment!</p>';
        return;
    }
    
    commentsList.innerHTML = comments.map(comment => createCommentElement(comment)).join('');
    
    // Initialize comment interactions
    initCommentInteractions();
}

/**
 * Create comment element
 */
function createCommentElement(comment) {
    const isReply = comment.parent_id !== null;
    const replyClass = isReply ? 'reply' : '';
    
    return `
        <div class="comment ${replyClass}" data-comment-id="${comment.id}">
            <div class="comment-header">
                <span class="comment-author">${escapeHtml(comment.author_name)}</span>
                <span class="comment-date">${formatDate(comment.created_at)}</span>
            </div>
            <div class="comment-content">${escapeHtml(comment.content)}</div>
            <div class="comment-actions">
                ${isLoggedIn() ? `
                    <a href="#" onclick="replyToComment(${comment.id})">Reply</a>
                    ${comment.user_id == getCurrentUserId() ? `
                        <a href="#" onclick="editComment(${comment.id})">Edit</a>
                        <a href="#" onclick="deleteComment(${comment.id})" class="text-danger">Delete</a>
                    ` : ''}
                ` : ''}
            </div>
        </div>
    `;
}

/**
 * Initialize comment interactions
 */
function initCommentInteractions() {
    // Add any comment-specific interactions here
}

/**
 * Reply to comment
 */
function replyToComment(commentId) {
    // Implementation for replying to comments
    console.log('Reply to comment:', commentId);
}

/**
 * Edit comment
 */
function editComment(commentId) {
    // Implementation for editing comments
    console.log('Edit comment:', commentId);
}

/**
 * Delete comment
 */
function deleteComment(commentId) {
    if (confirm('Are you sure you want to delete this comment?')) {
        fetch('api/comments.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'delete',
                comment_id: commentId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadComments();
                showAlert('Comment deleted successfully', 'success');
            } else {
                showAlert(data.message || 'Failed to delete comment', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Network error. Please try again.', 'error');
        });
    }
}

/**
 * Initialize collaboration requests
 */
function initCollaborationRequests() {
    // Add collaboration request functionality
}

/**
 * Request collaboration
 */
function requestCollaboration(projectId) {
    if (!isLoggedIn()) {
        showAlert('Please login to request collaboration', 'warning');
        return;
    }
    
    if (confirm('Send a collaboration request for this project?')) {
        fetch('api/collaborations.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'request',
                project_id: projectId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Collaboration request sent successfully', 'success');
            } else {
                showAlert(data.message || 'Failed to send collaboration request', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Network error. Please try again.', 'error');
        });
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return document.querySelector('a[href*="dashboard"]') !== null;
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    // This would be passed from PHP or retrieved from a data attribute
    return window.projectData?.currentUserId || null;
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
    
    // Insert at the top of the main content
    const main = document.querySelector('.project-main');
    if (main) {
        main.insertBefore(alertDiv, main.firstChild);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
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
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Format date for display
 */
function formatDate(dateString) {
    const date = new Date(dateString);
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
 * Handle collaboration request
 */
function handleCollaborationRequest(projectId, button) {
    // Add loading state
    button.classList.add('loading');
    button.disabled = true;
    
    // Send collaboration request
    fetch('api/collaborations.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'request',
            project_id: projectId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update button state
            button.innerHTML = '<i class="fas fa-check"></i><span>Request Sent</span>';
            button.classList.add('active');
            button.disabled = true;
            
            showAlert(data.message, 'success');
        } else {
            showAlert(data.message || 'Failed to send collaboration request', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Network error. Please try again.', 'error');
    })
    .finally(() => {
        // Remove loading state
        button.classList.remove('loading');
    });
}

// Export functions for global use
window.requestCollaboration = requestCollaboration;
window.replyToComment = replyToComment;
window.editComment = editComment;
window.deleteComment = deleteComment;
window.handleCollaborationRequest = handleCollaborationRequest;
