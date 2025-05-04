-- Create database if not exists
CREATE DATABASE IF NOT EXISTS survey_creator;
USE survey_creator;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    email_verified TINYINT(1) DEFAULT 0 NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Email Verification table
CREATE TABLE IF NOT EXISTS email_verifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_token (token)
);

-- Surveys table
CREATE TABLE IF NOT EXISTS surveys (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('draft', 'published', 'closed') DEFAULT 'draft',
    show_progress_bar TINYINT(1) DEFAULT 0 NOT NULL,
    allow_multiple_responses TINYINT(1) DEFAULT 0 NOT NULL,
    require_login TINYINT(1) DEFAULT 0 NOT NULL,
    response_limit INT DEFAULT NULL,
    close_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Questions table
CREATE TABLE IF NOT EXISTS questions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    survey_id INT NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('multiple_choice', 'single_choice', 'text', 'rating') NOT NULL,
    required BOOLEAN DEFAULT TRUE,
    order_position INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE
);

-- Options table
CREATE TABLE IF NOT EXISTS options (
    id INT PRIMARY KEY AUTO_INCREMENT,
    question_id INT NOT NULL,
    option_text TEXT NOT NULL,
    order_position INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    INDEX idx_options_question (question_id)
);

-- Responses table
CREATE TABLE IF NOT EXISTS responses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    survey_id INT NOT NULL,
    user_id INT,
    question_id INT NOT NULL,
    option_id INT,
    answer_text TEXT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    FOREIGN KEY (option_id) REFERENCES options(id) ON DELETE SET NULL
);
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    code VARCHAR(6) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_email (email)
);
-- Survey templates table
CREATE TABLE IF NOT EXISTS templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    structure JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create indexes for better performance
CREATE INDEX idx_surveys_user_id ON surveys(user_id);
CREATE INDEX idx_questions_survey_id ON questions(survey_id);
CREATE INDEX idx_responses_survey_id ON responses(survey_id);
CREATE INDEX idx_responses_user_id ON responses(user_id);
CREATE INDEX idx_responses_question_id ON responses(question_id);
CREATE INDEX idx_responses_option_id ON responses(option_id);

-- Insert default templates
INSERT INTO templates (name, description, structure) VALUES
('Customer Feedback', 'Basic template for gathering customer feedback', JSON_OBJECT(
    'title', 'Customer Feedback Survey',
    'description', 'We value your feedback! Please help us improve our products and services.',
    'questions', JSON_ARRAY(
        JSON_OBJECT(
            'type', 'rating',
            'text', 'How satisfied are you with our product/service?',
            'required', true,
            'order', 1
        ),
        JSON_OBJECT(
            'type', 'multiple_choice',
            'text', 'What aspects of our product/service do you like the most?',
            'required', true,
            'order', 2,
            'options', JSON_ARRAY(
                'Quality',
                'Price',
                'Customer Service',
                'Ease of Use',
                'Features'
            )
        ),
        JSON_OBJECT(
            'type', 'text',
            'text', 'How can we improve our product/service?',
            'required', false,
            'order', 3
        )
    )
)),
('Event Feedback', 'Template for gathering feedback after events', JSON_OBJECT(
    'title', 'Event Feedback Survey',
    'description', 'Thank you for attending our event! Please share your thoughts with us.',
    'questions', JSON_ARRAY(
        JSON_OBJECT(
            'type', 'rating',
            'text', 'How would you rate the overall event?',
            'required', true,
            'order', 1
        ),
        JSON_OBJECT(
            'type', 'single_choice',
            'text', 'Would you attend this event again?',
            'required', true,
            'order', 2,
            'options', JSON_ARRAY(
                'Definitely',
                'Probably',
                'Not sure',
                'Probably not',
                'Definitely not'
            )
        ),
        JSON_OBJECT(
            'type', 'text',
            'text', 'What suggestions do you have for future events?',
            'required', false,
            'order', 3
        )
    )
)),
('Employee Satisfaction', 'Template for employee satisfaction surveys', JSON_OBJECT(
    'title', 'Employee Satisfaction Survey',
    'description', 'Help us create a better workplace by sharing your feedback.',
    'questions', JSON_ARRAY(
        JSON_OBJECT(
            'type', 'rating',
            'text', 'How satisfied are you with your current role?',
            'required', true,
            'order', 1
        ),
        JSON_OBJECT(
            'type', 'multiple_choice',
            'text', 'Which aspects of your job do you enjoy the most?',
            'required', true,
            'order', 2,
            'options', JSON_ARRAY(
                'Work-Life Balance',
                'Team Collaboration',
                'Professional Growth',
                'Company Culture',
                'Benefits'
            )
        ),
        JSON_OBJECT(
            'type', 'text',
            'text', 'What changes would improve your work experience?',
            'required', false,
            'order', 3
        )
    )
));
