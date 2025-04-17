# Online Examination System Documentation

## System Overview

The Online Examination System is a comprehensive platform designed for creating, administering, and evaluating online tests and examinations. The system provides a secure, intelligent, and user-friendly digital assessment platform with advanced monitoring and interactive features.

## Key Features

### For Students
- **Exam Taking**: Take scheduled exams and tests with time tracking
- **Progress Tracking**: View completed exams and results
- **Profile Management**: Update personal information and preferences
- **Course Enrollment**: View enrolled courses and related assessments
- **Question Reporting**: Report problematic questions for instructor review

### For Administrators/Instructors
- **Exam Creation**: Create comprehensive exams with various question types
- **Section Management**: Organize exams into timed sections
- **Course Management**: Create and manage courses
- **Student Management**: Enroll/unenroll students from courses
- **Results Analysis**: View detailed analytics of student performance
- **Security Monitoring**: Track security violations during exams

## System Architecture

The system is built using:
- **PHP**: Backend server-side processing
- **MySQL**: Database for storing all system data
- **JavaScript**: Client-side interactivity and security features
- **Bootstrap CSS**: Modern responsive UI design

## System Components

1. **Authentication System**:
   - User registration and login
   - Role-based access control (student/admin)
   - Profile management
   - Password reset functionality

2. **Course Management**:
   - Course creation and modification
   - Student enrollment management
   - Course-exam relationships

3. **Exam Management**:
   - Exam creation with various configuration options
   - Question management with multiple formats
   - Sectioned exams with individual time limits
   - Scheduling features with start/end dates

4. **Exam Taking Interface**:
   - Interactive question navigation
   - Answer saving and review marking
   - Timer with visual feedback and auto-submission
   - Security monitoring and violation tracking

5. **Results and Analytics**:
   - Score calculation and result display
   - Performance analytics for instructors
   - Detailed exam attempt history
   - Student progress tracking

6. **Security Features**:
   - Browser security monitoring
   - Tab switching detection
   - Full-screen enforcement
   - Progressive security violation handling

## Folder Structure

```
php_exam_system/
├── admin/                 # Admin interface files
├── assets/                # CSS, JavaScript, and images
│   ├── css/               # Stylesheets
│   ├── js/                # JavaScript files
│   └── img/               # Images and icons
├── config/                # Configuration files
├── docs/                  # Documentation
├── includes/              # Reusable PHP components
└── student/               # Student interface files
```

## Database Schema

The database includes tables for:
- Users (students and administrators)
- Courses and enrollment relationships
- Exams and their configuration
- Exam sections with time limits
- Questions and answer choices
- Exam attempts and student answers
- Security logs and violation tracking

## Key Components Documentation

- [Exam Timer](exam_timer.md): Documentation for the exam timing system
- [Security System](security_system.md): Documentation for the exam security features
- [Question Management](question_management.md): Documentation for creating and managing questions

## Login Credentials

### Administrator Access
- **Username**: admin
- **Password**: admin123

### Student Access
- **Username**: student
- **Password**: student123

## Installation

1. Upload all files to your web server
2. Create a MySQL database and import the provided SQL schema
3. Update database configuration in `config/config.php`
4. Ensure proper permissions on directories
5. Access the system through your web browser

## System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Modern web browser with JavaScript enabled

## Security Considerations

- The system uses password hashing for secure authentication
- Regular security updates should be applied
- Database credentials should be protected
- Sessions expire after periods of inactivity

## Customization

The system can be customized through:
- Modifying CSS styles in assets/css/style.css
- Updating site configuration in config/config.php
- Adding new question types by extending the existing framework
- Creating custom course and exam templates