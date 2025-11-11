-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 12, 2025 at 07:45 AM
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
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `log_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `action` text DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_logs`
--

INSERT INTO `admin_logs` (`log_id`, `admin_id`, `action`, `timestamp`) VALUES
(1, 1, 'Added new sport \'cricket\' (ID: 1).', '2025-07-24 14:31:14'),
(2, 1, 'Added new sport \'football\' (ID: 2).', '2025-07-24 14:31:39'),
(3, 1, 'Added new sport \'volleyball\' (ID: 3).', '2025-07-24 14:32:15'),
(4, 1, 'Added new sport \'basketball\' (ID: 4).', '2025-07-24 14:32:46'),
(5, 1, 'Deleted item #4 from sports.', '2025-07-24 14:33:12'),
(6, 1, 'Added new sport \'basketball\' (ID: 5).', '2025-07-24 14:33:29'),
(7, 1, 'Created event \'sports meet\'.', '2025-07-25 12:19:40'),
(8, 1, 'Created team \'bcaa\'.', '2025-07-25 12:48:03'),
(9, 1, 'Created team \'bcab\'.', '2025-07-25 12:48:18'),
(10, 1, 'Updated event #1.', '2025-07-25 12:48:36'),
(11, 1, 'Updated score for event #1.', '2025-07-25 12:49:24'),
(12, 1, 'Created team: \'bcaa\'.', '2025-07-27 18:11:27'),
(13, 1, 'Created team: \'bcabcri\'.', '2025-08-04 21:17:35'),
(14, 1, 'Deleted item ID #4 from teams.', '2025-08-04 21:17:48'),
(15, 1, 'Deleted item ID #3 from teams.', '2025-08-04 21:17:59'),
(16, 1, 'Created team: \'bcabcri\'.', '2025-08-04 21:18:07'),
(17, 1, 'Created team: \'bcaacri\'.', '2025-08-04 21:18:32'),
(18, 1, 'Created event: \'sports meet\'.', '2025-08-04 21:19:59'),
(19, 1, 'Updated event ID #2.', '2025-08-04 21:20:31'),
(20, 1, 'Updated score for event ID #2.', '2025-08-04 21:21:52'),
(21, 1, 'Updated score for event ID #2.', '2025-08-04 21:24:11'),
(22, 1, 'Deleted item ID #3 from events.', '2025-08-08 20:49:31'),
(23, 1, 'Deleted item ID #8 from users.', '2025-08-08 22:15:19'),
(24, 1, 'Deleted item ID #6 from users.', '2025-08-08 22:15:22'),
(25, 1, 'Created event: \'sports meet\'.', '2025-08-08 22:22:59'),
(26, 1, 'Updated event ID #4.', '2025-08-08 22:23:30'),
(27, 1, 'Updated sport ID #1.', '2025-08-10 22:29:21'),
(28, 1, 'Updated score for event ID #4.', '2025-08-10 22:31:07'),
(29, 1, 'Set registration ID #2 to approved.', '2025-08-10 22:33:21'),
(30, 1, 'Set registration ID #1 to approved.', '2025-08-10 22:33:22'),
(31, 1, 'Added new sport: \'baseball\'.', '2025-08-11 18:28:28'),
(32, 1, 'Created event: \'sports meet\'.', '2025-08-11 23:29:21');

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
(1, 'sports meet', 5, '2025-07-25 06:55:00', 'college ground', '', 'completed', 1, 2, 16, 7, 'team1_win'),
(2, 'sports meet', 1, '2025-08-05 15:49:00', 'ground', 'mn', 'completed', 6, 5, 150, 145, 'team1_win'),
(4, 'sports meet', 1, '2025-08-28 10:42:00', 'ground', '', 'completed', 1, 2, 100, 98, 'team1_win'),
(5, 'sports meet', 1, '2025-08-14 23:29:00', 'ground', '', '', 1, 6, 0, 0, 'pending');

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
(12, 5, 'Bug Report', 0, '2025-08-04 21:14:58', 'ooo');

-- --------------------------------------------------------

--
-- Table structure for table `registrations`
--

CREATE TABLE `registrations` (
  `registration_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `team_id` int(11) DEFAULT NULL,
  `event_id` int(11) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT NULL,
  `registered_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registrations`
--

INSERT INTO `registrations` (`registration_id`, `user_id`, `team_id`, `event_id`, `status`, `registered_at`) VALUES
(1, 5, 6, NULL, 'approved', '2025-08-10 21:21:35'),
(2, 5, 5, NULL, 'approved', '2025-08-10 22:32:56'),
(3, 9, 2, NULL, 'pending', '2025-08-11 22:44:41'),
(4, 1, 1, NULL, 'pending', '2025-08-11 23:36:16');

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
(5, 'basketball', NULL, 18, 'active', 3, 1, 0),
(6, 'baseball', NULL, 20, 'active', 3, 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `teams`
--

CREATE TABLE `teams` (
  `team_id` int(11) NOT NULL,
  `team_name` varchar(100) DEFAULT NULL,
  `sport_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teams`
--

INSERT INTO `teams` (`team_id`, `team_name`, `sport_id`, `created_by`, `created_at`) VALUES
(1, 'bcaa', 5, 1, '2025-07-25 12:48:03'),
(2, 'bcab', 5, 1, '2025-07-25 12:48:18'),
(5, 'bcabcri', 1, 1, '2025-08-04 21:18:07'),
(6, 'bcaacri', 1, 1, '2025-08-04 21:18:32');

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
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `password`, `student_id`, `role`, `status`, `created_at`) VALUES
(1, 'alen', 'alenjacobtdpl@gmail.com', '12345678Aj', NULL, 'admin', 'active', '2025-07-24 14:13:10'),
(2, 'al', 'jacob@gmail.com', 'aj122344ff', NULL, '', 'active', '2025-07-24 15:31:34'),
(3, 'alen', 'kanappi@gmail.com', '12345678Aj', NULL, '', 'active', '2025-07-25 13:03:33'),
(4, 'ale', 'alkhg@gmail.com', 'Aj@8590180194', NULL, '', 'active', '2025-07-26 12:15:16'),
(5, 'alenjac', 'alen@gmail.com', 'aj122344ff', NULL, '', 'active', '2025-08-04 21:14:44'),
(7, 'sd', 'kp@gmail.com', 'Adithyan1', NULL, '', 'active', '2025-08-05 11:22:44'),
(9, 'alen', 'jacobalen@gmail.com', '12345678Aj', NULL, '', 'active', '2025-08-08 20:40:12');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `admin_logs_ibfk_1` (`admin_id`);

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
-- Indexes for table `registrations`
--
ALTER TABLE `registrations`
  ADD PRIMARY KEY (`registration_id`),
  ADD KEY `registrations_ibfk_1` (`user_id`),
  ADD KEY `registrations_ibfk_2` (`event_id`),
  ADD KEY `team_id` (`team_id`);

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
  ADD KEY `team_ibfk_2` (`created_by`);

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
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `event_schedule`
--
ALTER TABLE `event_schedule`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `registrations`
--
ALTER TABLE `registrations`
  MODIFY `registration_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sports`
--
ALTER TABLE `sports`
  MODIFY `sport_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `teams`
--
ALTER TABLE `teams`
  MODIFY `team_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `team_members`
--
ALTER TABLE `team_members`
  MODIFY `team_member` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

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
-- Constraints for table `registrations`
--
ALTER TABLE `registrations`
  ADD CONSTRAINT `registrations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `registrations_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `registrations_ibfk_3` FOREIGN KEY (`team_id`) REFERENCES `teams` (`team_id`) ON DELETE CASCADE;

--
-- Constraints for table `teams`
--
ALTER TABLE `teams`
  ADD CONSTRAINT `team_ibfk_1` FOREIGN KEY (`sport_id`) REFERENCES `sports` (`sport_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `team_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `team_members`
--
ALTER TABLE `team_members`
  ADD CONSTRAINT `team_member_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `teams` (`team_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `team_member_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
