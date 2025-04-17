/**
 * Exam Timer System
 * Handles countdown for timed exams and auto-submission
 */
class ExamTimer {
    constructor(options) {
        this.options = Object.assign({
            timeRemainingSeconds: 0,
            countdownElementId: 'exam-timer',
            formId: 'exam-form',
            warningThreshold: 300, // 5 minutes
            dangerThreshold: 60,   // 1 minute
            updateUrl: null,
            sectionId: null,
            onTimeExpired: null,
            onTimeWarning: null,
            onTimeDanger: null,
            onTimeUpdate: null
        }, options);

        this.timeRemaining = this.options.timeRemainingSeconds;
        this.countdownElement = document.getElementById(this.options.countdownElementId);
        this.examForm = document.getElementById(this.options.formId);
        this.isRunning = false;
        this.timerInterval = null;
        this.warningTriggered = false;
        this.dangerTriggered = false;
        this.autoSubmitTriggered = false;
    }

    /**
     * Format seconds into HH:MM:SS
     */
    formatTime(seconds) {
        if (seconds < 0) seconds = 0;
        
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        
        return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }

    /**
     * Update the timer display
     */
    updateDisplay() {
        if (!this.countdownElement) return;
        
        this.countdownElement.textContent = this.formatTime(this.timeRemaining);
        
        // Reset classes
        this.countdownElement.classList.remove('text-warning', 'text-danger', 'timer-flash');
        
        // Apply warning/danger styles
        if (this.timeRemaining <= this.options.dangerThreshold) {
            this.countdownElement.classList.add('text-danger', 'timer-flash');
        } else if (this.timeRemaining <= this.options.warningThreshold) {
            this.countdownElement.classList.add('text-warning');
        }
    }

    /**
     * Start the timer countdown
     */
    start() {
        if (this.isRunning) return;
        
        this.isRunning = true;
        this.updateDisplay();
        
        this.timerInterval = setInterval(() => {
            this.timeRemaining--;
            
            // Update the display
            this.updateDisplay();
            
            // Call the timeUpdate callback if provided
            if (this.options.onTimeUpdate && typeof this.options.onTimeUpdate === 'function') {
                this.options.onTimeUpdate(this.timeRemaining);
            }
            
            // Time warning threshold
            if (!this.warningTriggered && this.timeRemaining <= this.options.warningThreshold) {
                this.warningTriggered = true;
                if (this.options.onTimeWarning && typeof this.options.onTimeWarning === 'function') {
                    this.options.onTimeWarning(this.timeRemaining);
                }
            }
            
            // Time danger threshold
            if (!this.dangerTriggered && this.timeRemaining <= this.options.dangerThreshold) {
                this.dangerTriggered = true;
                if (this.options.onTimeDanger && typeof this.options.onTimeDanger === 'function') {
                    this.options.onTimeDanger(this.timeRemaining);
                }
            }
            
            // Time expired
            if (this.timeRemaining <= 0 && !this.autoSubmitTriggered) {
                this.autoSubmitTriggered = true;
                this.stop();
                
                if (this.options.onTimeExpired && typeof this.options.onTimeExpired === 'function') {
                    this.options.onTimeExpired();
                } else if (this.examForm) {
                    // Auto-submit the form if no custom handler is provided
                    this.autoSubmit();
                }
            }
            
            // Periodically update the server with the time remaining
            if (this.options.updateUrl && this.timeRemaining > 0 && this.timeRemaining % 30 === 0) {
                this.updateServerTime();
            }
        }, 1000);
    }

    /**
     * Stop the timer
     */
    stop() {
        if (this.timerInterval) {
            clearInterval(this.timerInterval);
            this.timerInterval = null;
        }
        this.isRunning = false;
    }

    /**
     * Reset the timer with a new duration
     */
    reset(newTimeInSeconds) {
        this.stop();
        this.timeRemaining = newTimeInSeconds;
        this.warningTriggered = false;
        this.dangerTriggered = false;
        this.autoSubmitTriggered = false;
        this.updateDisplay();
    }

    /**
     * Auto-submit the exam form
     */
    autoSubmit() {
        if (!this.examForm) return;
        
        // Create and append a hidden field to indicate time expiration
        const timeExpiredInput = document.createElement('input');
        timeExpiredInput.type = 'hidden';
        timeExpiredInput.name = 'time_expired';
        timeExpiredInput.value = '1';
        this.examForm.appendChild(timeExpiredInput);
        
        // Submit the form
        this.examForm.submit();
    }

    /**
     * Update the server with the current time remaining
     */
    updateServerTime() {
        if (!this.options.updateUrl) return;
        
        const data = new FormData();
        data.append('time_remaining', this.timeRemaining);
        
        if (this.options.sectionId) {
            data.append('section_id', this.options.sectionId);
        }
        
        fetch(this.options.updateUrl, {
            method: 'POST',
            body: data,
            credentials: 'same-origin'
        }).catch(error => {
            console.error('Error updating timer on server:', error);
        });
    }

    /**
     * Get current time remaining in seconds
     */
    getTimeRemaining() {
        return this.timeRemaining;
    }
}

// Add CSS for timer flash animation
document.addEventListener('DOMContentLoaded', function() {
    const style = document.createElement('style');
    style.textContent = `
        @keyframes timer-flash {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .timer-flash {
            animation: timer-flash 1s infinite;
        }
    `;
    document.head.appendChild(style);
});