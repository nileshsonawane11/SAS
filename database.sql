-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql306.infinityfree.com
-- Generation Time: Apr 07, 2026 at 02:09 AM
-- Server version: 11.4.10-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db`
--
CREATE DATABASE IF NOT EXISTS `supervision_system` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `supervision_system`;

-- --------------------------------------------------------


--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `institute_name` varchar(255) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `admin_panel`
--

DROP TABLE IF EXISTS `admin_panel`;
CREATE TABLE `admin_panel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin` int(11) NOT NULL,
  `duties_restriction` tinyint(1) NOT NULL DEFAULT 0,
  `block_capacity` int(11) NOT NULL DEFAULT 0,
  `duty_rate` int(11) NOT NULL DEFAULT 0,
  `reliever` int(11) NOT NULL DEFAULT 0,
  `extra_faculty` float NOT NULL DEFAULT 0,
  `role_restriction` tinyint(1) NOT NULL DEFAULT 0,
  `dept_restriction` tinyint(1) NOT NULL DEFAULT 0,
  `sub_restriction` tinyint(1) NOT NULL DEFAULT 0,
  `strict_duties` double NOT NULL DEFAULT 0,
  `teaching_staff` float NOT NULL DEFAULT 0,
  `non_teaching_staff` float NOT NULL DEFAULT 0,
  `letter_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT ' ',
  `committee_doc` varchar(255) DEFAULT NULL,
  `peon_doc` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `admin` (`admin`),
  CONSTRAINT `admin` FOREIGN KEY (`admin`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blocks`
--

DROP TABLE IF EXISTS `blocks`;
CREATE TABLE `blocks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `block_no` varchar(20) DEFAULT NULL,
  `place` varchar(100) DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL,
  `double_sit` enum('Yes','No') DEFAULT NULL,
  `Created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `block_no` (`block_no`),
  KEY `block_owner` (`Created_by`),
  CONSTRAINT `block_owner` FOREIGN KEY (`Created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `block_supervisor_list`
--

DROP TABLE IF EXISTS `block_supervisor_list`;
CREATE TABLE `block_supervisor_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `faculty_id` int(11) NOT NULL,
  `s_id` varchar(255) NOT NULL,
  `schedule` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `Created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `block_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `committee`
--

DROP TABLE IF EXISTS `committee`;
CREATE TABLE `committee` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_name` varchar(100) NOT NULL,
  `designation` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `rate` int(11) NOT NULL,
  `duty` varchar(255) NOT NULL,
  `status` tinyint(1) NOT NULL,
  `Created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `users_committee_ibfk_1` (`Created_by`),
  CONSTRAINT `users_committee_ibfk_1` FOREIGN KEY (`Created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
CREATE TABLE `departments` (
  `dept_code` varchar(10) NOT NULL,
  `hod_email` varchar(100) NOT NULL,
  PRIMARY KEY (`dept_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_slots`
--

DROP TABLE IF EXISTS `exam_slots`;
CREATE TABLE `exam_slots` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
   PRIMARY KEY (`id`),
  `exam_name` varchar(150) NOT NULL,
  `mode` enum('Online','Offline') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `Created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  KEY `users_exam_ibfk_1` (`Created_by`),
  CONSTRAINT `users_exam_ibfk_1` FOREIGN KEY (`Created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

DROP TABLE IF EXISTS `faculty`;
CREATE TABLE `faculty` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dept_name` varchar(150) DEFAULT NULL,
  `dept_code` varchar(255) NOT NULL,
  `courses` varchar(255) NOT NULL,
  `faculty_name` varchar(150) DEFAULT NULL,
  `duties` int(11) NOT NULL,
  `role` varchar(255) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `mobile` varchar(15) DEFAULT NULL,
  `adhar` varchar(255) NOT NULL,
  `AC-NO` varchar(255) NOT NULL,
  `IFSC_code` varchar(255) NOT NULL,
  `status` varchar(10) DEFAULT NULL,
  `Created_by` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `users_faculty_ibfk_1` (`Created_by`),
  CONSTRAINT `users_faculty_ibfk_1` FOREIGN KEY (`Created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `peons`
--

DROP TABLE IF EXISTS `peons`;
CREATE TABLE `peons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `dept` varchar(255) NOT NULL,
  `rate` int(11) NOT NULL,
  `duties` int(11) NOT NULL,
  `status` tinyint(1) NOT NULL,
  `Created_by` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schedule`
--

DROP TABLE IF EXISTS `schedule`;
CREATE TABLE `schedule` (
  `id` varchar(255) NOT NULL,
  `task_name` varchar(255) DEFAULT NULL,
  `task_type` varchar(100) DEFAULT NULL,
  `Blocks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `scheduled` int(11) DEFAULT NULL,
  `Created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `block_schedule`
--

DROP TABLE IF EXISTS `block_schedule`;
CREATE TABLE `block_schedule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `block_no` varchar(20) DEFAULT NULL,
  `course_code` varchar(20) DEFAULT NULL,
  `schedule_date` date DEFAULT NULL,
  `schedule_time` varchar(255) DEFAULT NULL,
  `s_id` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `Created_by` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `s_id` (`s_id`),
  KEY `owner_schedule` (`Created_by`),
  CONSTRAINT `owner_schedule` FOREIGN KEY (`Created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `s_id` FOREIGN KEY (`s_id`) REFERENCES `schedule` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `block_supervisor`
--

DROP TABLE IF EXISTS `block_supervisor`;
CREATE TABLE `block_supervisor` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `block_schedule_id` int(11) DEFAULT NULL,
  `faculty_id` int(11) DEFAULT NULL,
  `s_id` varchar(255) NOT NULL,
  `Created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_extra` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_block_faculty` (`block_schedule_id`,`faculty_id`),
  KEY `block_supervisor_ibfk_3` (`s_id`),
  KEY `block_supervisor_ibfk_2` (`faculty_id`),
  KEY `owner` (`Created_by`),
  CONSTRAINT `block_supervisor_ibfk_1` FOREIGN KEY (`block_schedule_id`) REFERENCES `block_schedule` (`id`) ON DELETE CASCADE,
  CONSTRAINT `block_supervisor_ibfk_2` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `block_supervisor_ibfk_3` FOREIGN KEY (`s_id`) REFERENCES `schedule` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `owner` FOREIGN KEY (`Created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;