# Question Management System Documentation

## Overview

The Question Management System allows administrators and instructors to create, organize, and maintain a comprehensive question bank for exams. It supports multiple question types, scoring options, and organizational features to facilitate efficient exam creation.

## Question Types

The system supports multiple question formats to accommodate different assessment needs:

1. **Multiple Choice Questions**
   - Single correct answer format
   - Multiple correct answers option
   - Customizable number of answer choices
   - Options for randomizing choice order

2. **Short Answer Questions**
   - Text input responses
   - Pattern matching for auto-grading
   - Character/word count limitations
   - Case sensitivity options

3. **Essay Questions**
   - Extended text responses
   - Manual grading interface
   - Rubric attachment options
   - Rich text formatting support

## Question Creation Process

The question creation workflow includes:

1. **Basic Information**
   - Question text with rich formatting
   - Question type selection
   - Point value assignment
   - Section assignment (if applicable)

2. **Answer Configuration**
   - Adding answer choices for multiple-choice questions
   - Marking correct answers
   - Setting matching patterns for short answers
   - Defining evaluation criteria for essays

3. **Additional Options**
   - Adding explanations for post-exam review
   - Tagging for organizational purposes
   - Setting difficulty levels
   - Adding media attachments

## Organizing Questions

Questions can be organized through:

1. **Exam Sections**
   - Group questions by topics
   - Assign time limits to sections
   - Control question sequence

2. **Question Banks**
   - Store reusable questions
   - Categorize by topic, difficulty, or type
   - Import/export functionality
   - Search and filtering capabilities

## Question Properties

Each question includes the following properties:

| Property | Description |
|----------|-------------|
| `id` | Unique identifier |
| `exam_id` | Associated exam |
| `section_id` | Associated section (optional) |
| `type` | Question type (multiple_choice, short_answer, essay) |
| `text` | Question text content |
| `points` | Point value for scoring |
| `position` | Order within exam/section |
| `contains_math` | Flag for mathematical content |

## Answer Choices

For multiple-choice questions, answer choices include:

| Property | Description |
|----------|-------------|
| `id` | Unique identifier |
| `question_id` | Associated question |
| `text` | Answer choice text |
| `is_correct` | Whether this is a correct answer |

## Exam Integration

Questions are integrated into exams through:

1. **Direct Creation**: Creating questions within an exam
2. **Section Assignment**: Organizing questions into sections
3. **Positioning**: Setting the order of questions
4. **Randomization**: Optional randomizing of question order

## Scoring and Grading

The system handles question scoring through:

1. **Automatic Grading**
   - Multiple-choice questions
   - Pattern-matched short answers
   - Pre-defined scoring rules

2. **Manual Grading Interface**
   - Essay question evaluation
   - Partial credit assignment
   - Feedback provision
   - Rubric-based assessment

## Question Import/Export

Questions can be managed in bulk through:

1. **CSV Import**: Adding questions from spreadsheets
2. **Question Bank**: Reusing questions across exams
3. **Export Options**: Creating backups or sharing questions

## Question Reporting

Students can report problematic questions through:

1. **Issue Reporting**: Flagging unclear or incorrect questions
2. **Reason Description**: Providing details about the issue
3. **Admin Review**: Interface for reviewing and resolving reports

## Administrator Features

Administrators can:

1. **Manage Questions**: Create, edit, and delete questions
2. **Review Reports**: Address student-reported issues
3. **Organize Content**: Categorize and tag questions
4. **Statistics**: View question performance metrics

## Student Experience

From the student perspective:

1. **Clear Presentation**: Well-formatted question display
2. **Navigation**: Intuitive movement between questions
3. **Answer Saving**: Automatic saving of responses
4. **Review Marking**: Flagging questions for later review

## Technical Implementation

Questions are implemented through:

1. **Database Storage**: Structured tables for questions and answers
2. **Form Processing**: PHP handling of question creation/editing
3. **Display Logic**: Dynamic rendering based on question type
4. **Validation**: Input checking and error handling

## Best Practices

1. **Clear Wording**: Ensure questions are unambiguous
2. **Balanced Difficulty**: Mix easy, medium, and challenging questions
3. **Regular Review**: Periodically review question effectiveness
4. **Varied Formats**: Use multiple question types to assess different skills
5. **Proper Weighting**: Assign point values appropriate to difficulty and importance

## Common Operations

1. **Creating a Basic Multiple-Choice Question**:
   - Enter question text
   - Add 4-5 answer choices
   - Mark the correct answer(s)
   - Assign point value

2. **Organizing Questions in Sections**:
   - Create exam sections
   - Assign questions to sections
   - Set section time limits
   - Arrange question order

3. **Importing Questions from Bank**:
   - Search the question bank
   - Select relevant questions
   - Import to current exam
   - Adjust as needed