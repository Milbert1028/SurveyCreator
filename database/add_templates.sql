USE survey_creator;

-- Clear existing templates
TRUNCATE TABLE templates;

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
