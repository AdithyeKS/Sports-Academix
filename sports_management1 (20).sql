-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 31, 2025 at 05:44 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sports_management1`
--

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL,
  `department_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`department_id`, `department_name`) VALUES
(1, 'Computer Application'),
(2, 'Computer Science'),
(3, 'Social Work'),
(4, 'Visual Media');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` int(11) NOT NULL,
  `event_name` varchar(100) DEFAULT NULL,
  `sport_id` int(11) DEFAULT NULL,
  `event_date` datetime DEFAULT NULL,
  `venue` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('schedule','outgoing','completed','postponed','cancelled') DEFAULT 'schedule',
  `team1_id` int(11) DEFAULT NULL,
  `team2_id` int(11) DEFAULT NULL,
  `team1_score` int(11) NOT NULL DEFAULT 0,
  `team2_score` int(11) NOT NULL DEFAULT 0,
  `result` enum('team1_win','team2_win','draw','pending') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`event_id`, `event_name`, `sport_id`, `event_date`, `venue`, `description`, `status`, `team1_id`, `team2_id`, `team1_score`, `team2_score`, `result`) VALUES
(8, 'sports meet', 1, '2025-08-25 12:22:00', 'ground', '', 'completed', 11, 10, 100, 98, 'team1_win'),
(9, 'sports meet', 1, '2025-08-30 22:48:00', 'ground', '', 'completed', 11, 10, 55, 89, 'team2_win'),
(10, 'sports meet', 5, '2025-09-09 20:05:00', 'ground', '', 'completed', 11, 10, 122, 128, 'team2_win'),
(11, 'sports meet', 1, '2025-09-10 22:01:00', 'ground', '', 'completed', 11, 10, 140, 156, 'team2_win'),
(15, 'sports meet', 2, '2025-09-20 13:46:00', 'ground', '', 'completed', 11, 10, 6, 9, 'team2_win'),
(16, 'sports meet', 3, '2025-09-20 20:14:00', 'ground', '', 'completed', 11, 10, 55, 51, 'team1_win'),
(17, 'sports meet', 1, '2025-09-30 11:10:00', 'ground', '', 'completed', 11, 10, 4, 8, 'team2_win'),
(18, 'sports meet', 2, '2025-10-31 23:09:00', 'ground', '', 'schedule', 19, 22, 0, 0, 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `event_schedule`
--

CREATE TABLE `event_schedule` (
  `schedule_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `team1_id` int(11) NOT NULL,
  `team2_id` int(11) NOT NULL,
  `match_date` datetime NOT NULL,
  `venue` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `feedback_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `is_read` tinyint(1) NOT NULL,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `message` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`feedback_id`, `user_id`, `subject`, `is_read`, `submitted_at`, `message`) VALUES
(13, 10, 'General Feedback', 0, '2025-08-17 21:38:35', 'dabfnjn');

-- --------------------------------------------------------

--
-- Table structure for table `match_appeals`
--

CREATE TABLE `match_appeals` (
  `appeal_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reason` text NOT NULL,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','reviewed','resolved') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `match_appeals`
--

INSERT INTO `match_appeals` (`appeal_id`, `event_id`, `user_id`, `reason`, `submitted_at`, `status`) VALUES
(2, 9, 11, 'not correct', '2025-09-26 11:05:06', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `match_comments`
--

CREATE TABLE `match_comments` (
  `comment_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `is_edited` tinyint(1) NOT NULL DEFAULT 0,
  `commented_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `match_comments`
--

INSERT INTO `match_comments` (`comment_id`, `event_id`, `user_id`, `comment`, `is_edited`, `commented_at`) VALUES
(1, 11, 11, 'hi', 0, '2025-09-20 19:23:18'),
(3, 11, 11, 'what a match', 0, '2025-09-20 19:50:46');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registrations`
--

CREATE TABLE `registrations` (
  `registration_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `sport_id` int(11) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `team_id` int(11) DEFAULT NULL,
  `event_id` int(11) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT NULL,
  `registered_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registrations`
--

INSERT INTO `registrations` (`registration_id`, `user_id`, `sport_id`, `department`, `team_id`, `event_id`, `status`, `registered_at`) VALUES
(6, 10, 1, 'Computer Application', NULL, NULL, 'approved', '2025-08-22 21:47:16'),
(7, 11, NULL, NULL, 11, NULL, 'rejected', '2025-08-25 12:45:02'),
(8, 11, 5, 'Computer Scieance', NULL, NULL, 'approved', '2025-08-26 22:28:38'),
(9, 11, 1, 'Computer Application', NULL, NULL, 'approved', '2025-09-15 21:04:06'),
(10, 11, 5, 'Computer Science', NULL, NULL, 'approved', '2025-09-19 13:43:12'),
(13, 10, 1, 'Social Work', NULL, NULL, 'approved', '2025-10-11 13:04:58');

-- --------------------------------------------------------

--
-- Table structure for table `sports`
--

CREATE TABLE `sports` (
  `sport_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `code` varchar(10) DEFAULT NULL,
  `max_players` int(11) DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `points_for_win` int(11) NOT NULL DEFAULT 3,
  `points_for_draw` int(11) NOT NULL DEFAULT 1,
  `points_for_loss` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sports`
--

INSERT INTO `sports` (`sport_id`, `name`, `code`, `max_players`, `status`, `points_for_win`, `points_for_draw`, `points_for_loss`) VALUES
(1, 'cricket', NULL, 20, 'active', 3, 1, 0),
(2, 'football', NULL, 25, 'active', 3, 1, 0),
(3, 'volleyball', NULL, 25, 'active', 3, 1, 0),
(5, 'Basketball', NULL, 20, 'active', 3, 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `teams`
--

CREATE TABLE `teams` (
  `team_id` int(11) NOT NULL,
  `team_name` varchar(100) DEFAULT NULL,
  `sport_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teams`
--

INSERT INTO `teams` (`team_id`, `team_name`, `sport_id`, `department_id`, `created_by`, `created_at`) VALUES
(10, 'Computer Science', 1, NULL, 10, '2025-08-25 12:09:54'),
(11, 'Computer Application', 1, NULL, 10, '2025-08-25 12:10:17'),
(17, 'Computer Application', 5, NULL, 10, '2025-09-20 20:28:50'),
(18, 'Computer Science', 5, NULL, 10, '2025-09-20 20:29:48'),
(19, 'Computer Application', 2, NULL, 10, '2025-09-20 20:30:01'),
(20, 'Computer Application', 3, NULL, 10, '2025-09-20 20:30:10'),
(21, 'Computer Science', 3, NULL, 10, '2025-09-20 20:30:22'),
(22, 'Computer Science', 2, NULL, 10, '2025-09-20 20:30:32'),
(23, 'Social Work', 1, NULL, 10, '2025-10-11 12:28:51');

-- --------------------------------------------------------

--
-- Table structure for table `team_members`
--

CREATE TABLE `team_members` (
  `team_member` int(11) NOT NULL,
  `team_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `role_in_team` varchar(50) DEFAULT 'Player'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `team_members`
--

INSERT INTO `team_members` (`team_member`, `team_id`, `user_id`, `role_in_team`) VALUES
(1, 18, 12, 'Player'),
(4, 17, 11, 'Player'),
(5, 11, 11, 'Player'),
(6, 11, 12, 'Player');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(250) DEFAULT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `status` enum('active','blocked') DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL,
  `otp_hash` varchar(255) DEFAULT NULL,
  `otp_expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `password`, `student_id`, `role`, `status`, `created_at`, `reset_token`, `reset_token_expires_at`, `otp_hash`, `otp_expires_at`) VALUES
(10, 'Alen Jacob', 'alenjacobtdpl@gmail.com', '$2y$10$Jl2pQI2T9QAugppfbvfCF.WOAxpfkl7MHIR2YpGSy1cRGbmwjY5Ie', NULL, 'admin', 'active', '2025-08-15 20:35:01', NULL, NULL, NULL, NULL),
(11, 'Alen Jacob', 'alen@gmail.com', '$2y$10$7Sx9d8Ywcqoyq81dsZSYbuFzuU1UPed9/W2amljrDCLKUKXIDOah.', NULL, 'user', 'active', '2025-08-15 21:06:59', NULL, NULL, NULL, NULL),
(12, 'alen', 'alka@gmail.com', '$2y$10$PhYZZRNM84XOBWyW8.Vd.u3NrCv7G8S0wJYxYFMwQzGDHLv6C8g1G', NULL, 'user', 'active', '2025-08-17 22:06:58', NULL, NULL, NULL, NULL),
(13, 'Abhith hb', 'abhith@gmail.com', '$2y$10$BEZ6dJ9g4lsXKECaZzE6S.HvM/kJhu/OOzUaSc.bqfa/adZWOW42u', NULL, 'user', 'active', '2025-10-11 12:07:47', NULL, NULL, NULL, NULL),
(15, 'Adithye Ks', 'Adithye@gmail.com', '$2y$10$Y.SL.shvLEbeY6tARFdtiekTciw/FuOvMnfe/7DFqOXZSpHztKf96', NULL, 'user', 'active', '2025-10-11 12:18:15', NULL, NULL, NULL, NULL),
(16, 'saaa sa', 'alenjacobtd@gmail.com', '$2y$10$JObxMICGGVyUnFD/z5zp0O4ZUAPzUzDOSb81/AWlJFtcX5HrDsbLi', NULL, 'user', 'blocked', '2025-10-11 13:32:38', NULL, NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`),
  ADD UNIQUE KEY `department_name` (`department_name`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `event_ibfk_1` (`sport_id`),
  ADD KEY `event_ibfk_2` (`team1_id`),
  ADD KEY `events_ibfk_3` (`team2_id`);

--
-- Indexes for table `event_schedule`
--
ALTER TABLE `event_schedule`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `team1_id` (`team1_id`),
  ADD KEY `team2_id` (`team2_id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `match_appeals`
--
ALTER TABLE `match_appeals`
  ADD PRIMARY KEY (`appeal_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `match_comments`
--
ALTER TABLE `match_comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `registrations`
--
ALTER TABLE `registrations`
  ADD PRIMARY KEY (`registration_id`),
  ADD KEY `registrations_ibfk_1` (`user_id`),
  ADD KEY `registrations_ibfk_2` (`event_id`),
  ADD KEY `team_id` (`team_id`),
  ADD KEY `fk_registrations_sports` (`sport_id`);

--
-- Indexes for table `sports`
--
ALTER TABLE `sports`
  ADD PRIMARY KEY (`sport_id`);

--
-- Indexes for table `teams`
--
ALTER TABLE `teams`
  ADD PRIMARY KEY (`team_id`),
  ADD KEY `team_ibfk_1` (`sport_id`),
  ADD KEY `team_ibfk_2` (`created_by`),
  ADD KEY `fk_teams_department` (`department_id`);

--
-- Indexes for table `team_members`
--
ALTER TABLE `team_members`
  ADD PRIMARY KEY (`team_member`),
  ADD KEY `team_member_ibfk_1` (`team_id`),
  ADD KEY `team_member_ibfk_2` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `event_schedule`
--
ALTER TABLE `event_schedule`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `match_appeals`
--
ALTER TABLE `match_appeals`
  MODIFY `appeal_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `match_comments`
--
ALTER TABLE `match_comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `registrations`
--
ALTER TABLE `registrations`
  MODIFY `registration_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `sports`
--
ALTER TABLE `sports`
  MODIFY `sport_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `teams`
--
ALTER TABLE `teams`
  MODIFY `team_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `team_members`
--
ALTER TABLE `team_members`
  MODIFY `team_member` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `event_ibfk_1` FOREIGN KEY (`sport_id`) REFERENCES `sports` (`sport_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_ibfk_2` FOREIGN KEY (`team1_id`) REFERENCES `teams` (`team_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `events_ibfk_3` FOREIGN KEY (`team2_id`) REFERENCES `teams` (`team_id`) ON DELETE CASCADE;

--
-- Constraints for table `event_schedule`
--
ALTER TABLE `event_schedule`
  ADD CONSTRAINT `schedule_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `schedule_ibfk_2` FOREIGN KEY (`team1_id`) REFERENCES `teams` (`team_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `schedule_ibfk_3` FOREIGN KEY (`team2_id`) REFERENCES `teams` (`team_id`) ON DELETE CASCADE;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `match_appeals`
--
ALTER TABLE `match_appeals`
  ADD CONSTRAINT `fk_appeal_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_appeal_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `match_comments`
--
ALTER TABLE `match_comments`
  ADD CONSTRAINT `fk_comment_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_comment_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `registrations`
--
ALTER TABLE `registrations`
  ADD CONSTRAINT `fk_registrations_sports` FOREIGN KEY (`sport_id`) REFERENCES `sports` (`sport_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `registrations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `registrations_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `registrations_ibfk_3` FOREIGN KEY (`team_id`) REFERENCES `teams` (`team_id`) ON DELETE CASCADE;

--
-- Constraints for table `teams`
--
ALTER TABLE `teams`
  ADD CONSTRAINT `fk_teams_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `team_ibfk_1` FOREIGN KEY (`sport_id`) REFERENCES `sports` (`sport_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `team_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `teams_ibfk_1` FOREIGN KEY (`sport_id`) REFERENCES `sports` (`sport_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `teams_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `team_members`
--
ALTER TABLE `team_members`
  ADD CONSTRAINT `team_member_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `teams` (`team_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `team_member_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `team_members_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `teams` (`team_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `team_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
