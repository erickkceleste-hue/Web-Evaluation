-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Aug 29, 2025 at 05:34 PM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `user`
--

-- --------------------------------------------------------

--
-- Table structure for table `assigned_evaluations`
--

DROP TABLE IF EXISTS `assigned_evaluations`;
CREATE TABLE IF NOT EXISTS `assigned_evaluations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `section` varchar(100) NOT NULL,
  `date` date NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_assignment` (`start_time`)
) ENGINE=MyISAM AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `evaluations`
--

DROP TABLE IF EXISTS `evaluations`;
CREATE TABLE IF NOT EXISTS `evaluations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` varchar(255) NOT NULL,
  `section` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `average_score` float NOT NULL,
  `comments` text NOT NULL,
  `evaluation_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `evaluations`
--

INSERT INTO `evaluations` (`id`, `student_id`, `section`, `average_score`, `comments`, `evaluation_date`) VALUES
(1, 'S12345', 'T98765', 4.5, 'Very effective teaching style. Clear and engaging.', '2025-08-12 16:12:54'),
(2, 'S12345', 'T98765', 4, 'Helpful and supportive.', '2025-08-12 16:12:54'),
(3, 'S12345', 'T56789', 3.8, 'Needs improvement in communication.', '2025-08-12 16:12:54');

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_answers`
--

DROP TABLE IF EXISTS `evaluation_answers`;
CREATE TABLE IF NOT EXISTS `evaluation_answers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_number` varchar(50) DEFAULT NULL,
  `student_name` varchar(255) DEFAULT NULL,
  `section` varchar(100) DEFAULT NULL,
  `teacher_name` varchar(255) DEFAULT NULL,
  `question_id` int DEFAULT NULL,
  `answer` int DEFAULT NULL,
  `feedback` text,
  `date_submitted` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `past_evaluation`
--

DROP TABLE IF EXISTS `past_evaluation`;
CREATE TABLE IF NOT EXISTS `past_evaluation` (
  `id` int NOT NULL AUTO_INCREMENT,
  `teacher_name` varchar(255) NOT NULL,
  `section` varchar(100) NOT NULL,
  `question_id` int NOT NULL,
  `answer` int NOT NULL,
  `feedback` text,
  `student_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `past_evaluation`
--

INSERT INTO `past_evaluation` (`id`, `teacher_name`, `section`, `question_id`, `answer`, `feedback`, `student_name`, `created_at`) VALUES
(1, 'Juan', 'ICT - 11A', 7, 4, 'Thankyou, Iloveyou!', 'Sample Student1', '2025-08-29 16:58:17'),
(2, 'Juan', 'ICT - 11A', 6, 4, 'Thankyou, Iloveyou!', 'Sample Student1', '2025-08-29 16:58:17'),
(3, 'Juan', 'ICT - 11A', 5, 5, 'Thankyou, Iloveyou!', 'Sample Student1', '2025-08-29 16:58:17'),
(4, 'Juan', 'ICT - 11A', 4, 4, 'Thankyou, Iloveyou!', 'Sample Student1', '2025-08-29 16:58:17'),
(5, 'Juan', 'ICT - 11A', 3, 4, 'Thankyou, Iloveyou!', 'Sample Student1', '2025-08-29 16:58:17'),
(6, 'Juan Dela Cruz', 'ICT - 11A', 7, 4, 'Thankyousomuch! ', 'Sample Student1', '2025-08-29 16:58:17'),
(7, 'Juan Dela Cruz', 'ICT - 11A', 6, 4, 'Thankyousomuch! ', 'Sample Student1', '2025-08-29 16:58:17'),
(8, 'Juan Dela Cruz', 'ICT - 11A', 5, 5, 'Thankyousomuch! ', 'Sample Student1', '2025-08-29 16:58:17'),
(9, 'Juan Dela Cruz', 'ICT - 11A', 4, 5, 'Thankyousomuch! ', 'Sample Student1', '2025-08-29 16:58:17'),
(10, 'Juan Dela Cruz', 'ICT - 11A', 3, 5, 'Thankyousomuch! ', 'Sample Student1', '2025-08-29 16:58:17'),
(11, 'Juan', 'ICT - 11A', 7, 2, 'A \"strongly agree\" scale is a type of Likert scale used in surveys to measure the intensity of a respondent\'s opinion.', 'Sample Student', '2025-08-29 16:58:17'),
(12, 'Juan', 'ICT - 11A', 6, 1, 'A \"strongly agree\" scale is a type of Likert scale used in surveys to measure the intensity of a respondent\'s opinion.', 'Sample Student', '2025-08-29 16:58:17'),
(13, 'Juan', 'ICT - 11A', 5, 3, 'A \"strongly agree\" scale is a type of Likert scale used in surveys to measure the intensity of a respondent\'s opinion.', 'Sample Student', '2025-08-29 16:58:17'),
(14, 'Juan', 'ICT - 11A', 4, 4, 'A \"strongly agree\" scale is a type of Likert scale used in surveys to measure the intensity of a respondent\'s opinion.', 'Sample Student', '2025-08-29 16:58:17'),
(15, 'Juan', 'ICT - 11A', 3, 2, 'A \"strongly agree\" scale is a type of Likert scale used in surveys to measure the intensity of a respondent\'s opinion.', 'Sample Student', '2025-08-29 16:58:17'),
(21, 'Juan Dela Cruz', 'ICT - 11A', 7, 5, 'Thanks', 'Erick Celeste', '2025-08-29 16:58:17'),
(22, 'Juan Dela Cruz', 'ICT - 11A', 6, 5, 'Thanks', 'Erick Celeste', '2025-08-29 16:58:17'),
(23, 'Juan Dela Cruz', 'ICT - 11A', 5, 5, 'Thanks', 'Erick Celeste', '2025-08-29 16:58:17'),
(24, 'Juan Dela Cruz', 'ICT - 11A', 4, 5, 'Thanks', 'Erick Celeste', '2025-08-29 16:58:17'),
(25, 'Juan Dela Cruz', 'ICT - 11A', 3, 4, 'Thanks', 'Erick Celeste', '2025-08-29 16:58:17'),
(26, 'Juan', 'STEM - 2A', 7, 5, 'Thankyou!', 'Morad Sultan', '2025-08-29 16:58:17'),
(27, 'Juan', 'STEM - 2A', 6, 5, 'Thankyou!', 'Morad Sultan', '2025-08-29 16:58:17'),
(28, 'Juan', 'STEM - 2A', 5, 5, 'Thankyou!', 'Morad Sultan', '2025-08-29 16:58:17'),
(29, 'Juan', 'STEM - 2A', 4, 5, 'Thankyou!', 'Morad Sultan', '2025-08-29 16:58:17'),
(30, 'Juan', 'STEM - 2A', 3, 5, 'Thankyou!', 'Morad Sultan', '2025-08-29 16:58:17'),
(36, 'Juan Dela Cruz', 'ICT - 11A', 3, 5, 'asdasfas', 'Sample Student', '2025-08-29 17:02:11'),
(37, 'Juan Dela Cruz', 'ICT - 11A', 4, 5, 'asdasfas', 'Sample Student', '2025-08-29 17:02:11'),
(38, 'Juan Dela Cruz', 'ICT - 11A', 5, 5, 'asdasfas', 'Sample Student', '2025-08-29 17:02:11'),
(39, 'Juan Dela Cruz', 'ICT - 11A', 6, 5, 'asdasfas', 'Sample Student', '2025-08-29 17:02:11'),
(40, 'Juan Dela Cruz', 'ICT - 11A', 7, 5, 'asdasfas', 'Sample Student', '2025-08-29 17:02:11');

-- --------------------------------------------------------

--
-- Table structure for table `questionnaire`
--

DROP TABLE IF EXISTS `questionnaire`;
CREATE TABLE IF NOT EXISTS `questionnaire` (
  `id` int NOT NULL AUTO_INCREMENT,
  `question` text NOT NULL,
  `scale` varchar(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `questionnaire`
--

INSERT INTO `questionnaire` (`id`, `question`, `scale`) VALUES
(3, 'The teacher encourages questions and provides helpful answers.', '1-5'),
(4, 'The teacher uses examples or demonstrations to aid understanding.', '1-5'),
(5, 'The teacher explains concepts clearly and effectively.', '1-5'),
(6, 'The teacher checks if students understand before moving to new topics.', '1-5'),
(7, 'The teacher connects lessons to real-world applications.', '1-5');

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

DROP TABLE IF EXISTS `student`;
CREATE TABLE IF NOT EXISTS `student` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_number` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `role` enum('teacher','student','admin') NOT NULL,
  `section` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `profile_image` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`id`, `id_number`, `name`, `role`, `section`, `profile_image`) VALUES
(40, '0001', 'Erick Celeste', 'student', 'ICT - 11A', ''),
(39, '0002', 'Morad Sultan', 'student', 'STEM - 2A', ''),
(41, '0003', 'Sample Student', 'student', 'ICT - 11A', ''),
(42, '0004', 'Sample Student1', 'student', 'ICT - 11A', '');

-- --------------------------------------------------------

--
-- Table structure for table `teacher`
--

DROP TABLE IF EXISTS `teacher`;
CREATE TABLE IF NOT EXISTS `teacher` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `position` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `teacher`
--

INSERT INTO `teacher` (`id`, `name`, `position`) VALUES
(23, 'Juan Dela Cruz', 'Master Teacher II'),
(33, 'Juan', 'Position Juan'),
(31, 'Sample Juan', 'Position Juan'),
(26, 'Sample Teacher', 'Sample Position');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_sections`
--

DROP TABLE IF EXISTS `teacher_sections`;
CREATE TABLE IF NOT EXISTS `teacher_sections` (
  `id` int NOT NULL AUTO_INCREMENT,
  `teacher_name` varchar(255) NOT NULL,
  `section` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `teacher_sections`
--

INSERT INTO `teacher_sections` (`id`, `teacher_name`, `section`, `subject`) VALUES
(36, 'Juan', 'STEM - 2A', 'General Mathematics'),
(40, 'Juan Dela Cruz', 'ICT 2 - Zuckerberg', 'Earth and Life Science'),
(41, 'Juan Dela Cruz', 'HUMSS - 11A', 'Media Literacy'),
(39, 'Juan', 'ICT 2 - Zuckerberg', 'CSS 102'),
(42, 'Sample Teacher', 'EIM - 11A', 'Oral Communication'),
(43, 'Juan Dela Cruz', 'ICT - 11A', 'Testing'),
(44, 'Juan', 'ICT - 11A', 'General Mathematics 1'),
(45, 'Sample Juan', 'ICT - 11A', 'Biology'),
(46, 'Juan Dela Cruz', 'STEM - 2A', 'Media Literacy');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `idnumber` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `role` enum('admin','teacher','student') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `idnumber`, `role`) VALUES
(28, '', 'admin1@gmail.com', '$2y$10$Kd9qpAsQRvXtBTG9ms9qIusOKr.9zgGGb3j0xCUFnrvlmbgvv4Lwq', '', 'admin'),
(61, 'Sample Student', '', '$2y$10$9fgjyYN6HcAjJY.z875lPeXSkTpxJSm4IvGaWXiYx.9SM.7SyH9PW', '0003', 'student'),
(29, '', 'admin2@gmail.com', '$2y$10$YxbPBuNIFeIEWMYQ671pseb9TeNXsjf6tiCLMxexnC1kjTfklIkFG', '', 'admin'),
(53, 'Sample Teacher', 'Teacher@gmail.com', '$2y$10$GJ6J1OsOhuhvdD/3ltM0qu1eVIVUrNC344/2ogngy.9DLclk/bR3u', '', 'teacher'),
(62, 'Sample Student1', '', '$2y$10$8D/w9cJenuqbu0czdW7fr..74APDvjHDrnhgQpEUyCCvHQAnHDvMK', '0004', 'student'),
(60, 'Erick Celeste', '', '$2y$10$PC8SvmQLRqemXemCdn8GJu9ZI/Rr6Y.JCJO/WWLYAFX8aXaHCK7dW', '0001', 'student'),
(59, 'Morad Sultan', '', '$2y$10$NUoXaY0j5.tepICUs016c.Pj3E/5G3bVGDhNHgK0mh1nk57w2HRI.', '0002', 'student');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
