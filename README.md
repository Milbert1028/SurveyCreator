# Survey Creator & Management System

A web application for creating, managing, and analyzing surveys. This system includes user authentication, admin dashboard, survey creation tools, and response analytics.

## Features

- User registration and authentication
- Admin management dashboard
- Survey creation and customization
- Response collection and analytics
- Template management
- Export functionality for survey data

## Installation

1. Clone this repository
2. Run `composer install` to install PHP dependencies
3. Configure your database in `includes/config.php`
4. Import database schema from `database/survey_creator.sql`
5. Create an admin user using `createadmin.php` (delete this file after use)

## Requirements

- PHP 7.4 or higher
- MySQL/MariaDB database
- Composer

## Security Notes

- After installation, be sure to delete `createadmin.php` and any installation scripts
- Update default passwords and secure your configuration files