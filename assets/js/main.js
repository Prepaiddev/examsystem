/**
 * PHP Exam System - Main JavaScript File
 */

// Document Ready Function
document.addEventListener('DOMContentLoaded', function() {
    console.log('PHP Exam System JavaScript Initialized');
    
    // Initialize any interactive components
    initializeComponents();
    
    // Add event listeners
    addEventListeners();
});

/**
 * Initialize Interactive Components
 */
function initializeComponents() {
    // Initialize Bootstrap tooltips if they exist
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize Bootstrap popovers if they exist
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Initialize Bootstrap toasts if they exist
    var toastElList = [].slice.call(document.querySelectorAll('.toast'));
    toastElList.map(function(toastEl) {
        return new bootstrap.Toast(toastEl);
    });
}

/**
 * Add Event Listeners
 */
function addEventListeners() {
    // Add event listeners for interactive elements
    
    // Example: Auto-close alerts after 5 seconds
    var alertList = document.querySelectorAll('.alert:not(.alert-permanent)');
    alertList.forEach(function(alert) {
        setTimeout(function() {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
    
    // Example: Confirmation dialogs for delete actions
    var confirmDeleteButtons = document.querySelectorAll('.confirm-delete');
    confirmDeleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });
}

/**
 * Theme Toggle Function
 */
function toggleTheme() {
    // This is handled server-side with PHP, this function is just for documentation
    console.log('Theme toggle clicked');
}

/**
 * Form Validation Helper
 * @param {HTMLFormElement} form - The form to validate
 * @returns {Boolean} - True if form is valid, false otherwise
 */
function validateForm(form) {
    // Check if the form uses HTML5 validation
    if (form.checkValidity()) {
        return true;
    } else {
        // Trigger browser's native validation UI
        form.reportValidity();
        return false;
    }
}

/**
 * Copy Text to Clipboard
 * @param {String} text - The text to copy
 * @returns {Promise} - Resolves when copying is complete
 */
function copyToClipboard(text) {
    return navigator.clipboard.writeText(text)
        .then(() => {
            console.log('Text copied to clipboard');
            return true;
        })
        .catch(err => {
            console.error('Failed to copy text: ', err);
            return false;
        });
}

/**
 * Format Time for Display
 * @param {Number} seconds - Time in seconds
 * @returns {String} - Formatted time string (HH:MM:SS)
 */
function formatTime(seconds) {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    
    return [
        hours.toString().padStart(2, '0'),
        minutes.toString().padStart(2, '0'),
        secs.toString().padStart(2, '0')
    ].join(':');
}

/**
 * Update Timer Display
 * @param {HTMLElement} timerElement - The element to update
 * @param {Number} seconds - Time in seconds
 */
function updateTimer(timerElement, seconds) {
    timerElement.textContent = formatTime(seconds);
    
    // Add warning classes based on time remaining
    if (seconds <= 300 && seconds > 60) { // 5 minutes or less
        timerElement.classList.add('timer-warning');
        timerElement.classList.remove('timer-danger');
    } else if (seconds <= 60) { // 1 minute or less
        timerElement.classList.add('timer-danger');
        timerElement.classList.remove('timer-warning');
    } else {
        timerElement.classList.remove('timer-warning', 'timer-danger');
    }
}

/**
 * Start Countdown Timer
 * @param {HTMLElement} timerElement - The element to update
 * @param {Number} seconds - Starting time in seconds
 * @param {Function} onComplete - Callback when timer reaches zero
 * @returns {Object} - Timer control object with stop method
 */
function startCountdown(timerElement, seconds, onComplete) {
    let remainingTime = seconds;
    updateTimer(timerElement, remainingTime);
    
    const intervalId = setInterval(function() {
        remainingTime--;
        updateTimer(timerElement, remainingTime);
        
        if (remainingTime <= 0) {
            clearInterval(intervalId);
            if (typeof onComplete === 'function') {
                onComplete();
            }
        }
    }, 1000);
    
    // Return control object
    return {
        stop: function() {
            clearInterval(intervalId);
        }
    };
}

/**
 * Ajax Helper Function
 * @param {String} url - The URL to send the request to
 * @param {Object} options - Request options
 * @returns {Promise} - Promise resolving to response data
 */
function ajax(url, options = {}) {
    // Default options
    const defaults = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    };
    
    // Merge defaults with provided options
    const settings = Object.assign({}, defaults, options);
    
    // Convert body to JSON if it's an object
    if (settings.body && typeof settings.body === 'object') {
        settings.body = JSON.stringify(settings.body);
    }
    
    // Make the fetch request
    return fetch(url, settings)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        });
}

/**
 * Security Monitoring for Exams
 * Tracks tab visibility and other security events
 */
class ExamSecurityMonitor {
    constructor(options = {}) {
        this.options = Object.assign({
            onVisibilityChange: null,
            logEndpoint: '/security_event.php',
            examId: null,
            attemptId: null
        }, options);
        
        this.violations = 0;
        this.warnings = 0;
        this.bindEvents();
    }
    
    bindEvents() {
        // Track tab visibility changes
        document.addEventListener('visibilitychange', () => {
            this.handleVisibilityChange();
        });
        
        // Disable right-click
        document.addEventListener('contextmenu', e => {
            e.preventDefault();
            this.logWarning('right_click_attempt');
            return false;
        });
        
        // Disable certain keyboard shortcuts
        document.addEventListener('keydown', e => {
            // Ctrl+C, Ctrl+V, Ctrl+S, F12, etc.
            if ((e.ctrlKey && (e.key === 'c' || e.key === 'v' || e.key === 's')) || 
                e.key === 'F12' || e.key === 'PrintScreen') {
                e.preventDefault();
                this.logWarning('key_combination_' + e.key);
                return false;
            }
        });
    }
    
    handleVisibilityChange() {
        if (document.visibilityState === 'hidden') {
            // Tab is no longer visible
            if (typeof this.options.onVisibilityChange === 'function') {
                this.options.onVisibilityChange(false);
            }
            this.logViolation('tab_switch');
        } else {
            // Tab is visible again
            if (typeof this.options.onVisibilityChange === 'function') {
                this.options.onVisibilityChange(true);
            }
        }
    }
    
    logViolation(type) {
        this.violations++;
        this.logEvent('violation', type);
    }
    
    logWarning(type) {
        this.warnings++;
        this.logEvent('warning', type);
    }
    
    logEvent(eventType, eventDetails) {
        // Log to console
        console.log(`Security ${eventType}: ${eventDetails}`);
        
        // Send to server if endpoint is configured
        if (this.options.logEndpoint && this.options.attemptId) {
            ajax(this.options.logEndpoint, {
                method: 'POST',
                body: {
                    event_type: eventType,
                    event_details: eventDetails,
                    exam_id: this.options.examId,
                    attempt_id: this.options.attemptId
                }
            }).catch(error => {
                console.error('Failed to log security event:', error);
            });
        }
        
        return { violations: this.violations, warnings: this.warnings };
    }
    
    getViolationCount() {
        return this.violations;
    }
    
    getWarningCount() {
        return this.warnings;
    }
}

// Export to global scope for use in inline scripts
window.ExamSystem = {
    validateForm,
    copyToClipboard,
    formatTime,
    startCountdown,
    ajax,
    ExamSecurityMonitor
};