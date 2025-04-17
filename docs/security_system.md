# Exam Security System Documentation

## Overview

The Exam Security System is designed to maintain the integrity of online assessments by detecting and responding to potential cheating attempts and security violations during exams. It provides both preventive measures and monitoring capabilities to ensure fair testing conditions.

## Features

1. **Browser Behavior Monitoring**
   - Tab/window switching detection
   - Full-screen mode enforcement
   - Browser visibility tracking
   - Copy/paste restriction options

2. **Progressive Response System**
   - Configurable warning thresholds
   - Violation counting and tracking
   - Auto-submission after maximum violations
   - Warning notifications to students

3. **Security Logging**
   - Detailed event logging with timestamps
   - Type and context of security events
   - Student-specific security history
   - Administrator review capabilities

4. **Configuration Options**
   - Enable/disable security features per exam
   - Choose between strict mode or warning mode
   - Set maximum violation thresholds
   - Customize security messages

## Implementation

### JavaScript Components

The security system uses JavaScript event listeners to detect various browser behaviors:

```javascript
// Tab visibility change detection
document.addEventListener('visibilitychange', function() {
    if (document.visibilityState === 'hidden') {
        logSecurityEvent('visibility_change');
    }
});

// Fullscreen exit detection
document.addEventListener('fullscreenchange', function() {
    if (!document.fullscreenElement) {
        logSecurityEvent('fullscreen_exit');
    }
});
```

### Security Event Logging

Security events are logged to the server through AJAX requests:

```javascript
function logSecurityEvent(eventType) {
    if (!securityEnabled) return;
    
    fetch('security_event.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            'attempt_id': attemptId,
            'event_type': eventType,
            'event_data': JSON.stringify({
                'url': window.location.href,
                'timestamp': new Date().toISOString()
            })
        })
    })
    .then(response => response.json())
    .then(data => {
        // Handle response (warnings, auto-submit, etc.)
    });
}
```

### Backend Integration

The security system integrates with the backend through:

1. **Exam Configuration**: Security settings defined during exam creation
2. **Event Logging API**: Endpoint for recording security events
3. **Attempt Management**: Auto-submission triggered by security violations
4. **Admin Dashboard**: Review of security logs and violations

## Types of Security Events

| Event Type | Description |
|------------|-------------|
| `visibility_change` | Browser tab/window switching |
| `fullscreen_exit` | Exiting fullscreen mode |
| `copy_attempt` | Attempt to copy exam content |
| `paste_attempt` | Attempt to paste content into exam |
| `multiple_windows` | Multiple browser windows detected |
| `right_click` | Right-click attempt detected |

## Administrator Features

Administrators can:

1. **Configure Security**: Enable/disable security features per exam
2. **Review Logs**: View security events for each exam attempt
3. **Adjust Thresholds**: Set violation limits before consequences
4. **Warning Mode**: Set whether to show warnings or strictly enforce rules

## Student Experience

When security features are enabled:

1. Students are informed of security monitoring before starting
2. Warning notifications appear when violations are detected
3. Progressive security measures are enforced based on violation count
4. Auto-submission occurs if maximum violations are reached

## Exam Configuration Options

During exam creation, administrators can configure:

1. **Browser Security**: Enable/disable security monitoring
2. **Allow Warnings**: Choose between warning mode or strict enforcement
3. **Max Violations**: Set threshold for auto-submission
4. **Security Message**: Customize the security notice shown to students

## Technical Details

1. **Client-Side Detection**: JavaScript monitors browser behavior in real-time
2. **Server-Side Validation**: PHP processes and records security events
3. **Database Storage**: Events stored with exam attempt records
4. **Security Log Format**: JSON data containing event type, timestamp, and context

## Limitations and Considerations

1. Browser-based security has inherent limitations and cannot prevent all forms of cheating
2. Some security measures may be affected by browser settings or extensions
3. False positives may occur in certain browser environments
4. Security features should be combined with proper exam design and other integrity measures

## Best Practices

1. Inform students about security measures before the exam
2. Use randomized questions when possible
3. Keep exam duration appropriate for the content
4. Combine automated security with proper exam proctoring when needed
5. Review security logs after exams to identify patterns