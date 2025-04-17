# PHP Exam System - Database Structure

## Entity Relationship Diagram (ERD)

```
                                         +----------------+
                                         |    courses     |
                                         +----------------+
                                         | id             |
                                         | code           |
                                         | title          |
                                         | description    |
                                         | instructor_id  |-----------+
                                         | created_at     |           |
                                         +----------------+           |
                                                 ^                    |
                                                 |                    |
                                                 |                    |
                              +------------------+                    |
                              |                  |                    |
                              |                  |                    |
                     +--------+--------+  +------+---------+          |
                     |  user_courses   |  |     exams      |          |
                     +-----------------+  +----------------+          |
                     | user_id         |  | id             |          |
                     | course_id       |  | title          |          |
                     | enrolled_at     |  | description    |          |
                     +-----------------+  | duration_min   |          |
                              ^           | created_at     |          |
                              |           | published      |          |
                              |           | start_date     |          |
                              |           | end_date       |          |
                              |           | course_id      |          |
                              |           | passing_score  |          |
                              |           | has_sections   |          |
                              |           | randomize_q    |          |
                              |           | security opts  |          |
                              |           +----------------+          |
                              |                    ^                  |
                              |                    |                  |
                              |                    |                  |
                    +---------+----------+     +---+--------------+   |
                    |       users        |     |   sections       |   |
         +--------->|                    |     |                  |   |
         |          +-------------------+      +------------------+   |
         |          | id                |      | id               |   |
         |          | username          |      | exam_id          |   |
         |          | email             |      | title            |   |
         |          | password          |      | description      |   |
         |          | role              |<-----+ duration_min     |   |
         |          | created_at        |      | position         |   |
         |          | theme             |      +------------------+   |
         |          | matric_number     |               ^             |
         |          | level             |               |             |
         |          | last_active       |               |             |
         |          +-------------------+               |             |
         |                   ^    ^                     |             |
         |                   |    |                     |             |
         |                   |    |                     |             |
+--------+--------+  +-------+    +---------+  +--------+--------+   |
| activity_logs   |  |                       |  |   questions     |   |
+-----------------+  |  +------------------+ |  +-----------------+   |
| id              |  |  | reported_question| |  | id              |   |
| user_id         |  |  +------------------+ |  | exam_id         |   |
| action          |  |  | id               | |  | section_id      |   |
| target_type     |  |  | question_id      | |  | type            |   |
| target_id       |  |  | user_id          | |  | text            |   |
| details         |  |  | reason           | |  | points          |   |
| created_at      |  |  | created_at       | |  | position        |   |
+-----------------+  |  | status           | |  | contains_math   |   |
                     |  | admin_response   | |  +-----------------+   |
                     |  +------------------+ |           ^            |
                     |           ^           |           |            |
                     |           |           |           |            |
              +------+--------+  |  +--------+---------+ |            |
              | exam_attempts |  |  |    answers       | |            |
              +---------------+  |  +------------------+ |            |
              | id            |  |  | id               | |            |
              | exam_id       |  |  | attempt_id       | |            |
              | student_id    |--+  | question_id      |-+            |
              | started_at    |     | selected_choice  |              |
              | completed_at  |     | text_answer      |              |
              | score         |     | score            |              |
              | is_graded     |     | is_graded        |              |
              | passed        |     | grader_feedback  |              |
              | current_section|     | marked_for_review|              |
              | security log  |     +------------------+              |
              +---------------+               ^                       |
                      ^                       |                       |
                      |                       |                       |
             +--------+--------+     +--------+---------+             |
             | section_attempts|     |     choices      |             |
             +-----------------+     +------------------+             |
             | id              |     | id               |             |
             | attempt_id      |     | question_id      |             |
             | section_id      |     | text             |             |
             | started_at      |     | is_correct       |             |
             | completed_at    |     +------------------+             |
             | time_remaining  |                                      |
             +-----------------+                                      |
                                                                      |
                                                                      |
                                                                      |
                                                                      |
                                                                      |
                                                                      |
                                                                      +
```

## Table Descriptions

### 1. `users`
Stores user information for both administrators and students.

| Field | Type | Description |
|-------|------|-------------|
| id | INT | Primary key |
| username | VARCHAR(64) | Unique username |
| email | VARCHAR(120) | Unique email address |
| password_hash | VARCHAR(256) | Hashed password |
| role | VARCHAR(20) | User role: 'student' or 'admin' |
| created_at | DATETIME | Account creation timestamp |
| theme_preference | VARCHAR(10) | UI theme preference: 'light' or 'dark' |
| matric_number | VARCHAR(20) | Student ID number (for students only) |
| level | VARCHAR(20) | Academic level (for students only) |
| last_active | DATETIME | Last activity timestamp |

### 2. `courses`
Represents academic courses that exams can be associated with.

| Field | Type | Description |
|-------|------|-------------|
| id | INT | Primary key |
| code | VARCHAR(20) | Unique course code (e.g., "CS101") |
| title | VARCHAR(200) | Course title |
| description | TEXT | Course description |
| instructor_id | INT | Foreign key to users.id (instructor) |
| created_at | DATETIME | Course creation timestamp |

### 3. `user_courses`
Junction table for student enrollment in courses.

| Field | Type | Description |
|-------|------|-------------|
| user_id | INT | Foreign key to users.id |
| course_id | INT | Foreign key to courses.id |
| enrolled_at | DATETIME | Enrollment timestamp |

### 4. `exams`
Stores exam configurations and settings.

| Field | Type | Description |
|-------|------|-------------|
| id | INT | Primary key |
| title | VARCHAR(200) | Exam title |
| description | TEXT | Exam description |
| duration_minutes | INT | Total exam duration in minutes |
| created_at | DATETIME | Exam creation timestamp |
| published | TINYINT | Whether the exam is visible to students |
| start_date | DATETIME | Optional date when exam becomes available |
| end_date | DATETIME | Optional date when exam expires |
| course_id | INT | Foreign key to courses.id |
| passing_score | FLOAT | Minimum percentage to pass |
| has_sections | TINYINT | Whether exam has timed sections |
| randomize_questions | TINYINT | Whether to randomize question order |
| browser_security | TINYINT | Whether to enable security features |
| allow_browser_warnings | TINYINT | Show warnings instead of blocking |
| max_violations | INT | Maximum security violations before auto-submit |
| assessment_type | VARCHAR(20) | Type of assessment: 'exam', 'quiz', etc. |

### 5. `sections`
Timed sections within an exam.

| Field | Type | Description |
|-------|------|-------------|
| id | INT | Primary key |
| exam_id | INT | Foreign key to exams.id |
| title | VARCHAR(200) | Section title |
| description | TEXT | Section description |
| duration_minutes | INT | Section duration in minutes |
| position | INT | Section order within exam |

### 6. `questions`
Stores exam questions of various types.

| Field | Type | Description |
|-------|------|-------------|
| id | INT | Primary key |
| exam_id | INT | Foreign key to exams.id |
| section_id | INT | Foreign key to sections.id (optional) |
| type | VARCHAR(20) | Question type: 'multiple_choice', 'short_answer', 'essay' |
| text | TEXT | Question text |
| points | INT | Point value of question |
| position | INT | Question order within exam/section |
| contains_math | TINYINT | Whether question contains math equations |

### 7. `choices`
Multiple choice options for questions.

| Field | Type | Description |
|-------|------|-------------|
| id | INT | Primary key |
| question_id | INT | Foreign key to questions.id |
| text | TEXT | Choice text |
| is_correct | TINYINT | Whether this is the correct answer |

### 8. `exam_attempts`
Records of students taking exams.

| Field | Type | Description |
|-------|------|-------------|
| id | INT | Primary key |
| exam_id | INT | Foreign key to exams.id |
| student_id | INT | Foreign key to users.id |
| started_at | DATETIME | When attempt was started |
| completed_at | DATETIME | When attempt was completed (or NULL if in progress) |
| score | FLOAT | Percentage score (0-100) |
| is_graded | TINYINT | Whether all answers have been graded |
| passed | TINYINT | Whether score meets passing threshold |
| current_section_id | INT | Foreign key to sections.id (current section) |
| security_violations | INT | Count of security violations |
| security_warnings | INT | Count of security warnings |
| security_log | TEXT | JSON log of security events |

### 9. `section_attempts`
Tracks time spent on each section during an exam attempt.

| Field | Type | Description |
|-------|------|-------------|
| id | INT | Primary key |
| attempt_id | INT | Foreign key to exam_attempts.id |
| section_id | INT | Foreign key to sections.id |
| started_at | DATETIME | When section was started |
| completed_at | DATETIME | When section was completed |
| time_remaining_seconds | INT | Remaining time when section was left |

### 10. `answers`
Student responses to questions.

| Field | Type | Description |
|-------|------|-------------|
| id | INT | Primary key |
| attempt_id | INT | Foreign key to exam_attempts.id |
| question_id | INT | Foreign key to questions.id |
| selected_choice_id | INT | Foreign key to choices.id (for multiple choice) |
| text_answer | TEXT | Text response (for short answer/essay) |
| score | FLOAT | Points awarded for this answer |
| is_graded | TINYINT | Whether this answer has been graded |
| grader_feedback | TEXT | Feedback from instructor |
| marked_for_review | TINYINT | Whether student marked for review |

### 11. `reported_questions`
Questions flagged by students as problematic.

| Field | Type | Description |
|-------|------|-------------|
| id | INT | Primary key |
| question_id | INT | Foreign key to questions.id |
| user_id | INT | Foreign key to users.id |
| reason | TEXT | Reason for reporting |
| created_at | DATETIME | Report timestamp |
| status | VARCHAR(20) | Status: 'pending', 'reviewed', 'resolved' |
| admin_response | TEXT | Administrator's response |

### 12. `activity_logs`
System activity log for auditing.

| Field | Type | Description |
|-------|------|-------------|
| id | INT | Primary key |
| user_id | INT | Foreign key to users.id |
| action | VARCHAR(255) | Description of action performed |
| target_type | VARCHAR(50) | Type of target (exam, question, user, etc.) |
| target_id | VARCHAR(50) | ID of target |
| details | TEXT | Additional details |
| created_at | DATETIME | Action timestamp |

## Key Relationships

1. **Users to Courses**:
   - Admins can create and teach courses (instructor_id in courses)
   - Students can enroll in courses (user_courses junction table)

2. **Courses to Exams**:
   - Exams can be associated with specific courses (course_id in exams)
   - Or exams can be independent (course_id is NULL)

3. **Exams to Questions**:
   - Exams contain multiple questions
   - Questions can be organized within sections if the exam has sections

4. **Students to Exam Attempts**:
   - Students make exam attempts
   - Each attempt tracks answers to questions

5. **Questions to Answers**:
   - Questions can have multiple types (multiple choice, short answer, essay)
   - Multiple choice questions have predefined choices
   - Student answers link to questions via the attempt

6. **Security and Reporting**:
   - Exam attempts track security violations
   - Students can report problematic questions
   - System logs track user activities