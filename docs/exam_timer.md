# Exam Timer Documentation

## Overview

The Exam Timer is a critical component of the online examination system that manages countdown functionality during timed exams. It ensures that students have the appropriate amount of time to complete their exams and handles auto-submission when time expires.

## Features

1. **Countdown Timer**
   - Displays remaining time in HH:MM:SS format
   - Visual feedback changes (warning/danger colors) as time decreases
   - Flashing animation when time is critically low

2. **Time Management**
   - Tracks time remaining for entire exams
   - Supports sectioned exams with individual time limits
   - Persists time between page refreshes
   - Periodic backend updates to save time status

3. **Automatic Actions**
   - Auto-submits the exam when time expires
   - Shows warnings when time is running low
   - Provides time threshold callbacks for custom actions

4. **Security Features**
   - Works alongside exam security monitoring
   - Prevents timer manipulation
   - Logs time-related events

## Implementation

### JavaScript Class

The timer is implemented as a JavaScript class (`ExamTimer`) that provides a clean interface for timer functions:

```javascript
// Initialize and start a timer
const examTimer = new ExamTimer({
    timeRemainingSeconds: 1800, // 30 minutes
    countdownElementId: 'timerDisplay',
    formId: 'examForm',
    warningThreshold: 600, // 10 minutes
    dangerThreshold: 300,  // 5 minutes
    // Other options...
});

// Start the timer
examTimer.start();

// Stop the timer
examTimer.stop();

// Reset with new duration
examTimer.reset(newTimeInSeconds);

// Get current time
const timeLeft = examTimer.getTimeRemaining();
```

### Backend Integration

The timer integrates with the backend through a few key endpoints:

1. **Time Updates**: Periodically sends the remaining time to the server
2. **Section Management**: Updates time for sectioned exams
3. **Auto-Submission**: Triggers exam submission when time expires

## Configuration Options

The ExamTimer class accepts the following options:

| Option | Description |
|--------|-------------|
| `timeRemainingSeconds` | Initial time in seconds |
| `countdownElementId` | HTML element ID for the timer display |
| `formId` | HTML form ID for auto-submission |
| `warningThreshold` | Time in seconds for warning state |
| `dangerThreshold` | Time in seconds for danger state |
| `updateUrl` | Server endpoint for time updates |
| `sectionId` | Current section ID (for sectioned exams) |
| `onTimeExpired` | Callback when time runs out |
| `onTimeWarning` | Callback when warning threshold is reached |
| `onTimeDanger` | Callback when danger threshold is reached |
| `onTimeUpdate` | Callback on every second update |

## Usage in the System

The timer is used in:

1. `take_exam.php` - Main exam-taking interface
2. `section_action.php` - API endpoint for section management
3. `submit_exam.php` - For auto-submission when time expires

## Technical Details

1. The timer uses `setInterval` for countdown logic
2. Time formatting handles hours, minutes, seconds display
3. CSS animations are used for visual feedback
4. The timer persists state through page refreshes by saving to the database
5. Form submission includes time-expired status when needed

## Security Considerations

1. Time remaining is tracked on both client and server
2. Server-side validation ensures time limits are enforced
3. Timer integrates with the security logging system
4. Auto-submission prevents working beyond the time limit

## Customization

The timer appearance can be customized through CSS:
- Normal state: Default text color
- Warning state: Yellow/orange text (`text-warning` class)
- Danger state: Red text with flashing animation (`text-danger` and `timer-flash` classes)