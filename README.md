# PHP Exam System Documentation

## Overview

PHP Exam System is a comprehensive online examination platform designed for educational institutions. The system provides a secure, reliable, and user-friendly environment for creating, administering, and grading assessments.

## System Requirements

- PHP 7.3 or higher
- MySQL/MariaDB database
- Web server (Apache, Nginx, etc.)
- Modern web browser (Chrome, Firefox, Safari, Edge)

## Main Features

### For Administrators
- Create and manage exams with various question types
- Organize exams by courses
- Set exam parameters (duration, date restrictions, security settings)
- Create timed sections within exams
- Monitor student performance with detailed reports
- Review and respond to reported questions
- Manual grading for essay and short answer questions

### For Students
- Take exams in a secure environment
- View course materials and upcoming exams
- Review past exam results and performance analytics
- Report issues with questions during exams
- Mark questions for review during exams
- Dark/light theme preference

### Security Features
- Tab switching detection
- Copy/paste prevention
- Automatic submission on security violations
- Configurable warning system
- Time monitoring and auto-submission

## Installation

### Local Installation

1. Copy all files to your web server directory
2. Make sure PHP and MySQL are installed and running
3. Create a database for the system
4. Update the database configuration in `config/config.php`
5. Access the `simple_setup.php` script in your browser to initialize the database
6. Follow the on-screen instructions to complete setup

### cPanel Installation

1. Upload the `php_exam_system.zip` file to your cPanel account
2. Extract the files to your desired directory
3. Create a MySQL database and user in cPanel
4. Update the database configuration in `config/config.php` with your database details
   - Use `127.0.0.1` as the database host (not `localhost`)
5. Access the `test_connection.php` script to verify database connectivity
6. Run the `simple_setup.php` script to initialize the database

## Getting Started

### Default Login Credentials

After running the setup script, the following default accounts are created:

**Admin Account:**
- Username: admin
- Password: admin123

**Student Account:**
- Username: student  
- Password: student123
- Matric Number: STU12345

*Important: Change these default passwords after your first login for security purposes.*

### Initial Configuration

As an administrator, you should:

1. **Update Site Settings:** Modify `config/config.php` to set your site name, URL, and other parameters
2. **Create Courses:** Add courses in the admin dashboard
3. **Create Exams:** Set up your first exam with questions
4. **Add Students:** Register students or let them self-register

## System Architecture

### File Structure

- `/admin` - Administrator interface files
- `/student` - Student interface files
- `/assets` - CSS, JavaScript, and image files
- `/config` - Configuration and utility functions
- `/includes` - Shared components (header, footer)
- `/templates` - Email templates and other reusable HTML
- `index.php` - Homepage
- `login.php` - Login page
- `register.php` - Registration page
- `simple_setup.php` - Database setup script
- `test_connection.php` - Database connection tester

### Database Schema

The main database tables include:

- `users` - User accounts (admin, students)
- `courses` - Course information
- `exams` - Exam configurations
- `sections` - Timed sections within exams
- `questions` - Exam questions
- `choices` - Multiple choice options
- `exam_attempts` - Record of student exam attempts
- `answers` - Student answers to questions
- `reported_questions` - Questions flagged by students
- `activity_logs` - System activity log

## Usage Guide

### Creating an Exam

1. Log in as an administrator
2. Go to "Exams" and click "Create New Exam"
3. Fill in the exam details (title, description, duration)
4. Configure advanced settings if needed (randomization, security)
5. Add questions to the exam:
   - Multiple choice questions with any number of options
   - Short answer questions for brief text responses
   - Essay questions for longer responses
6. Publish the exam when ready

### Taking an Exam

1. Log in as a student
2. Go to the dashboard to see available exams
3. Click on an exam to start it
4. Answer questions one by one or navigate between them
5. Mark difficult questions for review
6. Submit the exam when finished or when time expires

### Grading and Reviewing

1. Multiple choice questions are auto-graded
2. Short answer and essay questions require manual grading
3. Administrators can review and grade submissions
4. Students can see their grades and feedback afterward

## Security Considerations

### Browser Security Features

The system includes several security measures to maintain exam integrity:

- **Tab Switching Detection:** Detects when students switch to other tabs or applications
- **Copy/Paste Prevention:** Prevents copying exam content or pasting answers
- **Time Monitoring:** Tracks time spent on each question and section
- **Auto-submission:** Can automatically submit after security violations

These features can be configured per exam to balance security and student experience.

### Data Security

- Passwords are stored using secure hashing
- Session management prevents unauthorized access
- Input validation helps prevent SQL injection
- Secure database connection practices

## Troubleshooting

### Database Connection Issues

If you encounter database connection problems:

1. Run the `test_connection.php` script to diagnose connection issues
2. Verify database credentials in `config/config.php`
3. Try using `127.0.0.1` as the host instead of `localhost`
4. Check that the database user has proper permissions

### Page Load Errors

If pages fail to load or display errors:

1. Check PHP error logs for specific error messages
2. Verify file permissions (755 for directories, 644 for files)
3. Make sure all files were uploaded correctly

### Login Problems

If you cannot log in:

1. Verify you're using the correct credentials
2. Make sure the database is properly initialized (run `simple_setup.php`)
3. Check for cookie or session issues in your browser

## Customization

### Appearance

- Modify the CSS files in `/assets/css/` to change the look and feel
- Edit header and footer templates in `/includes/` to change layout
- Update site name and information in `config/config.php`

### Functionality

- Add custom question types by extending the question handling code
- Implement additional security measures in the exam interface
- Create custom report formats for exam results

## Support and Resources

For additional help:

1. Refer to this documentation for basic setup and usage
2. Check the comments in the code for detailed functionality
3. Contact the developer for more advanced support

---

## Developer Notes

### Adding New Features

When adding new features:

1. Maintain the existing code structure and naming conventions
2. Update the database schema as needed with proper foreign key relationships
3. Implement proper validation and security measures
4. Test thoroughly in various scenarios

### Database Migrations

If you need to update the database structure:

1. Back up the database before making changes
2. Use the `execute_safely` function in `update_schema.php`
3. Document all changes for future reference