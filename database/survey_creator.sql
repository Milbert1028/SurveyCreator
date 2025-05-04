-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Mar 08, 2025 at 01:47 PM
-- Server version: 8.0.41
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `survey_creator`
--

-- --------------------------------------------------------

--
-- Table structure for table `email_verifications`
--

CREATE TABLE `email_verifications` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `token` varchar(64) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `options`
--

CREATE TABLE `options` (
  `id` int NOT NULL,
  `question_id` int NOT NULL,
  `option_text` text NOT NULL,
  `order_position` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `options`
--

INSERT INTO `options` (`id`, `question_id`, `option_text`, `order_position`, `created_at`) VALUES
(103, 66, '1 Star', 1, '2025-03-07 13:08:28'),
(104, 66, '2 Star', 2, '2025-03-07 13:08:28'),
(105, 66, '3 Star', 3, '2025-03-07 13:08:28'),
(106, 66, '4 Star', 4, '2025-03-07 13:08:28'),
(107, 66, '5 Star', 5, '2025-03-07 13:08:28'),
(108, 67, '1 Star', 1, '2025-03-07 13:08:28'),
(109, 67, '2 Star', 2, '2025-03-07 13:08:28'),
(110, 67, '3 Star', 3, '2025-03-07 13:08:28'),
(111, 67, '4 Star', 4, '2025-03-07 13:08:28'),
(112, 67, '5 Star', 5, '2025-03-07 13:08:28'),
(113, 68, '1 Star', 1, '2025-03-07 13:08:28'),
(114, 68, '2 Star', 2, '2025-03-07 13:08:28'),
(115, 68, '3 Star', 3, '2025-03-07 13:08:28'),
(116, 68, '4 Star', 4, '2025-03-07 13:08:28'),
(117, 68, '5 Star', 5, '2025-03-07 13:08:28'),
(118, 69, '1 Star', 1, '2025-03-07 13:08:28'),
(119, 69, '2 Star', 2, '2025-03-07 13:08:28'),
(120, 69, '3 Star', 3, '2025-03-07 13:08:28'),
(121, 69, '4 Star', 4, '2025-03-07 13:08:28'),
(122, 69, '5 Star', 5, '2025-03-07 13:08:28'),
(123, 70, '1 Star', 1, '2025-03-07 13:08:28'),
(124, 70, '2 Star', 2, '2025-03-07 13:08:28'),
(125, 70, '3 Star', 3, '2025-03-07 13:08:28'),
(126, 70, '4 Star', 4, '2025-03-07 13:08:28'),
(127, 70, '5 Star', 5, '2025-03-07 13:08:28'),
(153, 76, 'adobo', 1, '2025-03-07 23:51:44'),
(154, 76, 'manok', 2, '2025-03-07 23:51:44'),
(155, 77, 'red', 1, '2025-03-07 23:51:44'),
(156, 77, 'blue', 2, '2025-03-07 23:51:44'),
(157, 78, 'math', 1, '2025-03-07 23:51:44'),
(158, 78, 'science', 2, '2025-03-07 23:51:44'),
(159, 79, 'cow', 1, '2025-03-07 23:51:44'),
(160, 79, 'cat', 2, '2025-03-07 23:51:44'),
(161, 80, 'fudgebar', 1, '2025-03-07 23:51:44'),
(162, 80, 'barnuts', 2, '2025-03-07 23:51:44');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int NOT NULL,
  `email` varchar(255) NOT NULL,
  `code` varchar(6) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `code`, `expires_at`, `used`, `created_at`) VALUES
(18, 'we1@gmail.com', '286011', '2025-03-02 02:33:58', 0, '2025-03-02 01:33:58'),
(19, 'raiaselene71@gmail.com', '767938', '2025-03-02 02:57:05', 0, '2025-03-02 01:57:05'),
(21, 'ganoza@gmail.com', '642895', '2025-03-07 13:43:53', 1, '2025-03-07 12:43:53'),
(26, 'forgeranya812@gmail.com', '324307', '2025-03-08 12:26:18', 0, '2025-03-08 11:26:18');

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `id` int NOT NULL,
  `survey_id` int NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('multiple_choice','single_choice','text','rating') NOT NULL,
  `required` tinyint(1) DEFAULT '1',
  `order_position` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`id`, `survey_id`, `question_text`, `question_type`, `required`, `order_position`, `created_at`) VALUES
(66, 50, 'q1', 'rating', 1, 0, '2025-03-07 13:08:28'),
(67, 50, 'q2', 'rating', 1, 1, '2025-03-07 13:08:28'),
(68, 50, 'q3', 'rating', 1, 2, '2025-03-07 13:08:28'),
(69, 50, 'q4', 'rating', 1, 3, '2025-03-07 13:08:28'),
(70, 50, 'q5', 'rating', 1, 4, '2025-03-07 13:08:28'),
(76, 52, 'food', 'single_choice', 1, 0, '2025-03-07 23:51:44'),
(77, 52, 'color', 'single_choice', 1, 1, '2025-03-07 23:51:44'),
(78, 52, 'subject', 'single_choice', 1, 2, '2025-03-07 23:51:44'),
(79, 52, 'animals', 'single_choice', 1, 3, '2025-03-07 23:51:44'),
(80, 52, 'snack', 'single_choice', 1, 4, '2025-03-07 23:51:44'),
(87, 55, '', 'multiple_choice', 1, 0, '2025-03-08 03:01:53');

-- --------------------------------------------------------

--
-- Table structure for table `responses`
--

CREATE TABLE `responses` (
  `id` int NOT NULL,
  `survey_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `question_id` int NOT NULL,
  `option_id` int DEFAULT NULL,
  `answer_text` text,
  `submitted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `responses`
--

INSERT INTO `responses` (`id`, `survey_id`, `user_id`, `question_id`, `option_id`, `answer_text`, `submitted_at`) VALUES
(37, 50, 2, 66, 106, NULL, '2025-03-07 13:08:49'),
(38, 50, 2, 67, 112, NULL, '2025-03-07 13:08:49'),
(39, 50, 2, 68, 116, NULL, '2025-03-07 13:08:49'),
(40, 50, 2, 69, 122, NULL, '2025-03-07 13:08:49'),
(41, 50, 2, 70, 126, NULL, '2025-03-07 13:08:49'),
(42, 50, 4, 66, 106, NULL, '2025-03-07 13:09:26'),
(43, 50, 4, 67, 112, NULL, '2025-03-07 13:09:26'),
(44, 50, 4, 68, 117, NULL, '2025-03-07 13:09:26'),
(45, 50, 4, 69, 122, NULL, '2025-03-07 13:09:26'),
(46, 50, 4, 70, 126, NULL, '2025-03-07 13:09:26'),
(47, 52, 2, 76, 154, NULL, '2025-03-07 23:58:25'),
(48, 52, 2, 77, 155, NULL, '2025-03-07 23:58:25'),
(49, 52, 2, 78, 158, NULL, '2025-03-07 23:58:25'),
(50, 52, 2, 79, 159, NULL, '2025-03-07 23:58:25'),
(51, 52, 2, 80, 161, NULL, '2025-03-07 23:58:25'),
(52, 52, 2, 76, 154, NULL, '2025-03-07 23:59:41'),
(53, 52, 2, 77, 155, NULL, '2025-03-07 23:59:41'),
(54, 52, 2, 78, 158, NULL, '2025-03-07 23:59:41'),
(55, 52, 2, 79, 160, NULL, '2025-03-07 23:59:41'),
(56, 52, 2, 80, 161, NULL, '2025-03-07 23:59:41'),
(57, 52, 2, 76, 153, NULL, '2025-03-07 23:59:57'),
(58, 52, 2, 77, 156, NULL, '2025-03-07 23:59:57'),
(59, 52, 2, 78, 158, NULL, '2025-03-07 23:59:57'),
(60, 52, 2, 79, 160, NULL, '2025-03-07 23:59:57');

-- --------------------------------------------------------

--
-- Table structure for table `surveys`
--

CREATE TABLE `surveys` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `status` enum('draft','published','closed') DEFAULT 'draft',
  `show_progress_bar` tinyint(1) NOT NULL DEFAULT '0',
  `allow_multiple_responses` tinyint(1) NOT NULL DEFAULT '0',
  `require_login` tinyint(1) NOT NULL DEFAULT '0',
  `response_limit` int DEFAULT NULL,
  `close_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `surveys`
--

INSERT INTO `surveys` (`id`, `user_id`, `title`, `description`, `status`, `show_progress_bar`, `allow_multiple_responses`, `require_login`, `response_limit`, `close_date`, `created_at`, `updated_at`) VALUES
(50, 2, 'wew', 'we', 'published', 0, 0, 0, NULL, NULL, '2025-03-07 13:08:28', '2025-03-08 11:40:58'),
(52, 2, 'favorites', '3 8 2025', 'published', 0, 1, 0, NULL, NULL, '2025-03-07 23:51:44', '2025-03-08 02:52:08'),
(55, 2, 'greet', 'sdsd', 'draft', 0, 0, 0, NULL, NULL, '2025-03-08 03:01:53', '2025-03-08 03:01:53');

-- --------------------------------------------------------

--
-- Table structure for table `templates`
--

CREATE TABLE `templates` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `structure` json NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `templates`
--

INSERT INTO `templates` (`id`, `name`, `description`, `structure`, `created_at`, `updated_at`) VALUES
(1, 'Customer Feedback', 'Basic template for gathering customer feedback', '{\"title\": \"Customer Feedback Survey\", \"questions\": [{\"text\": \"How satisfied are you with our product/service?\", \"type\": \"rating\", \"order\": 1, \"required\": true}, {\"text\": \"What aspects of our product/service do you like the most?\", \"type\": \"multiple_choice\", \"order\": 2, \"options\": [\"Quality\", \"Price\", \"Customer Service\", \"Ease of Use\", \"Features\"], \"required\": true}, {\"text\": \"How can we improve our product/service?\", \"type\": \"text\", \"order\": 3, \"required\": false}], \"description\": \"We value your feedback! Please help us improve our products and services.\"}', '2025-02-25 11:58:50', '2025-02-25 11:58:50'),
(2, 'Event Feedback', 'Template for gathering feedback after events', '{\"title\": \"Event Feedback Survey\", \"questions\": [{\"text\": \"How would you rate the overall event?\", \"type\": \"rating\", \"order\": 1, \"required\": true}, {\"text\": \"Would you attend this event again?\", \"type\": \"single_choice\", \"order\": 2, \"options\": [\"Definitely\", \"Probably\", \"Not sure\", \"Probably not\", \"Definitely not\"], \"required\": true}, {\"text\": \"What suggestions do you have for future events?\", \"type\": \"text\", \"order\": 3, \"required\": false}], \"description\": \"Thank you for attending our event! Please share your thoughts with us.\"}', '2025-02-25 11:58:50', '2025-02-25 11:58:50'),
(3, 'Employee Satisfaction', 'Template for employee satisfaction surveys', '{\"title\": \"Employee Satisfaction Survey\", \"questions\": [{\"text\": \"How satisfied are you with your current role?\", \"type\": \"rating\", \"order\": 1, \"required\": true}, {\"text\": \"Which aspects of your job do you enjoy the most?\", \"type\": \"multiple_choice\", \"order\": 2, \"options\": [\"Work-Life Balance\", \"Team Collaboration\", \"Professional Growth\", \"Company Culture\", \"Benefits\"], \"required\": true}, {\"text\": \"What changes would improve your work experience?\", \"type\": \"text\", \"order\": 3, \"required\": false}], \"description\": \"Help us create a better workplace by sharing your feedback.\"}', '2025-02-25 11:58:50', '2025-02-25 11:58:50');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `email_verified` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `role`, `created_at`, `updated_at`, `email_verified`) VALUES
(2, 'milbert', 'forgeranya812@gmail.com', '$2y$10$WcKy/5FW602PDb33rOKYPOx41L6Zv2P35Vlrc8kEiAJJws97sb4EG', 'user', '2025-02-28 11:48:23', '2025-02-28 13:48:55', 0),
(4, 'raiaselene', 'raiaselene71@gmail.com', '$2y$10$p7FmEZ/VPvpN2rMzzbiS4eFy2vvMcwQf0iEVrI.k13TG3BQhnPiR6', 'user', '2025-03-02 01:55:18', '2025-03-02 01:56:01', 0),
(9, 'gwapo', 'francis@gmail.com', '$2y$10$F1uPpCPI2Nnp4MZ.9pCzyO//aZVB7YOMnynu40zL4hXH.RvrBR9HC', 'user', '2025-03-07 12:45:38', '2025-03-07 12:45:38', 0),
(10, 'sel132', 'rein@gmail.com', '$2y$10$zwC4PKN2fd6sCu8T4hoMCOeBvr3sGluTyMPPPXhXM5YMYkfS5Yaom', 'user', '2025-03-07 12:46:33', '2025-03-07 12:46:33', 0),
(11, 'we1', 'romil@gmail.com', '$2y$10$fUOOUfIJHP2YHfsQxLrcBezRU4HP/SsckfD5gtmTdM8zpJ0ZUZZQS', 'user', '2025-03-07 12:47:21', '2025-03-07 12:47:21', 0),
(12, 'kyla', 'akirito900@gmail.com', '$2y$10$NoF4PDvsiFT2u4S4k5.RAuykPle8TxuWB2H2ScqjfuB8bbvAogum6', 'user', '2025-03-08 02:11:21', '2025-03-08 02:11:21', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_token` (`token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `options`
--
ALTER TABLE `options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_options_question` (`question_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_email` (`email`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_questions_survey_id` (`survey_id`);

--
-- Indexes for table `responses`
--
ALTER TABLE `responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_responses_survey_id` (`survey_id`),
  ADD KEY `idx_responses_user_id` (`user_id`),
  ADD KEY `idx_responses_question_id` (`question_id`),
  ADD KEY `idx_responses_option_id` (`option_id`);

--
-- Indexes for table `surveys`
--
ALTER TABLE `surveys`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_surveys_user_id` (`user_id`);

--
-- Indexes for table `templates`
--
ALTER TABLE `templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `email_verifications`
--
ALTER TABLE `email_verifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `options`
--
ALTER TABLE `options`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=173;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT for table `responses`
--
ALTER TABLE `responses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `surveys`
--
ALTER TABLE `surveys`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `templates`
--
ALTER TABLE `templates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD CONSTRAINT `email_verifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `options`
--
ALTER TABLE `options`
  ADD CONSTRAINT `options_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `responses`
--
ALTER TABLE `responses`
  ADD CONSTRAINT `responses_ibfk_1` FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `responses_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `responses_ibfk_3` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `responses_ibfk_4` FOREIGN KEY (`option_id`) REFERENCES `options` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `surveys`
--
ALTER TABLE `surveys`
  ADD CONSTRAINT `surveys_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
