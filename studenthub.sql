-- MySQL dump 10.13  Distrib 26.7.0, for macos27.0 (arm64)
--
-- Host: localhost    Database: studenthub
-- ------------------------------------------------------
-- Server version	26.7.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `studenthub`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `studenthub` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `studenthub`;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `user_email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_role` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'admin',
  `action` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '127.0.0.1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `fk_audit_users` (`user_id`),
  CONSTRAINT `fk_audit_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,1,'admin@campusly.com','admin','LOGIN_SUCCESS','Administrator authenticated successfully via secure password_verify()','127.0.0.1','2026-10-21 04:00:12'),(2,1,'admin@campusly.com','admin','USER_STATUS_TOGGLE','Toggled user status for Account #3 (jash@campusly.com) to Active','127.0.0.1','2026-10-21 04:44:05'),(3,1,'admin@campusly.com','admin','EVENT_CREATE','Published new campus event: AI Workshop (Venue: Auditorium)','127.0.0.1','2026-10-21 05:32:40'),(4,2,'jay@campusly.com','student','UNAUTHORIZED_ACCESS_403','Access Violation: Student attempted to access restricted Admin Dashboard /dashboard.php (HTTP 403 Blocked)','127.0.0.1','2026-10-21 07:15:18'),(5,1,'admin@campusly.com','admin','USER_ROLE_CHANGE','Promoted user #4 permissions to verified Student','127.0.0.1','2026-10-21 07:40:22'),(6,1,'admin@campusly.com','admin','PASSWORD_RESET','Initiated password reset for user #2 (temporary credentials issued)','127.0.0.1','2026-10-21 08:05:44'),(7,1,'admin@campusly.com','admin','EVENT_CREATE','Published new campus event: Code Wizards (Venue: CSPIT)','127.0.0.1','2026-10-21 08:35:10'),(8,1,'admin@campusly.com','admin','LOGIN_SUCCESS','Admin session refreshed with session_regenerate_id()','127.0.0.1','2026-10-21 09:00:00'),(9,2,'jay@campusly.com','student','LOGIN_SUCCESS','Student user jay@campusly.com authenticated successfully','127.0.0.1','2026-10-21 09:20:11'),(10,2,'jay@campusly.com','student','UNAUTHORIZED_ACCESS_403','Forbidden access attempt to /dashboard.php by role: student (jay@campusly.com)','127.0.0.1','2026-10-21 09:20:11'),(11,1,'admin@campusly.com','admin','LOGIN_SUCCESS','Admin user admin@campusly.com authenticated successfully','127.0.0.1','2026-10-21 09:20:27'),(12,1,'admin@campusly.com','admin','USER_STATUS_TOGGLE','Administrator toggled status of user #4 (bhavy@campusly.com) to Inactive','127.0.0.1','2026-10-21 09:20:34'),(13,1,'admin@campusly.com','admin','LOGIN_SUCCESS','Admin user admin@campusly.com authenticated successfully','127.0.0.1','2026-10-21 09:21:24'),(14,1,'admin@campusly.com','admin','LOGIN_SUCCESS','Admin user admin@campusly.com authenticated successfully','127.0.0.1','2026-10-21 09:30:23'),(15,2,'jay@campusly.com','student','LOGIN_SUCCESS','Student user jay@campusly.com authenticated successfully','127.0.0.1','2026-10-21 09:30:29'),(16,2,'jay@campusly.com','student','UNAUTHORIZED_ACCESS_403','Forbidden access attempt to /dashboard.php by role: student (jay@campusly.com)','127.0.0.1','2026-10-21 09:30:30'),(17,1,'admin@campusly.com','admin','LOGIN_SUCCESS','Admin user admin@campusly.com authenticated successfully','127.0.0.1','2026-10-21 09:32:21'),(18,2,'jay@campusly.com','student','LOGIN_SUCCESS','Student user jay@campusly.com authenticated successfully','127.0.0.1','2026-10-21 09:32:34'),(19,1,'admin@campusly.com','admin','LOGIN_SUCCESS','Admin user admin@campusly.com authenticated successfully','127.0.0.1','2026-10-21 09:49:57'),(20,2,'jay@campusly.com','student','LOGIN_SUCCESS','Student user jay@campusly.com authenticated successfully','127.0.0.1','2026-10-21 09:50:07'),(21,2,'jay@campusly.com','student','UNAUTHORIZED_ACCESS_403','Forbidden access attempt to /dashboard.php by role: student (jay@campusly.com)','127.0.0.1','2026-10-21 09:50:08');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event_audit_log`
--

DROP TABLE IF EXISTS `event_audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_audit_log` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `event_id` int DEFAULT NULL,
  `event_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `performed_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_audit_log`
--

LOCK TABLES `event_audit_log` WRITE;
/*!40000 ALTER TABLE `event_audit_log` DISABLE KEYS */;
INSERT INTO `event_audit_log` VALUES (1,6,'Cloud Security & DevOps Summit 2026','CREATE','Created event with poster \'uploads/posters/poster_20261021_092131_4f766cc68df3.png\' at venue \'CSPIT Seminar Hall 1\'','Administrator','2026-10-21 09:21:31'),(2,6,'Cloud Security & DevOps Summit 2026','UPDATE','Updated details (venue: CSPIT Central Auditorium Hall A, category: Technical, status: Open)','Administrator','2026-10-21 09:21:33'),(3,6,'Cloud Security & DevOps Summit 2026','DELETE','Permanently removed event record and unlinked poster','Administrator','2026-10-21 09:21:35'),(4,7,'Cloud Security & DevOps Summit 2026','CREATE','Created event with poster \'uploads/posters/poster_20261021_093225_16b12a095241.png\' at venue \'CSPIT Seminar Hall 1\'','Administrator','2026-10-21 09:32:25'),(5,7,'Cloud Security & DevOps Summit 2026','UPDATE','Updated details (venue: CSPIT Central Auditorium Hall A, category: Technical, status: Open)','Administrator','2026-10-21 09:32:28'),(6,7,'Cloud Security & DevOps Summit 2026','DELETE','Permanently removed event record and unlinked poster','Administrator','2026-10-21 09:32:29');
/*!40000 ALTER TABLE `event_audit_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `events` (
  `event_id` int NOT NULL AUTO_INCREMENT,
  `event_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `venue` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `poster` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event_date` date DEFAULT NULL,
  `category` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Technical',
  `status` enum('Open','Closed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Open',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`event_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `events`
--

LOCK TABLES `events` WRITE;
/*!40000 ALTER TABLE `events` DISABLE KEYS */;
INSERT INTO `events` VALUES (1,'Hackathon','Lab 301','uploads/posters/poster_hackathon.png','2026-10-28','Hackathon','Open','Annual 36-hour hackathon with web, mobile, and AI tracks.','2026-10-21 09:12:07'),(2,'Web Workshop','Lab 302','uploads/posters/poster_web_workshop.png','2026-11-05','Workshop','Open','Hands-on full-stack workshop covering PHP, MySQL, and modern JavaScript.','2026-10-21 09:12:07'),(3,'AI Workshop','Auditorium','uploads/posters/poster_ai_workshop.png','2026-11-12','Technical','Open','Deep dive into Generative AI, LLMs, and computer vision architectures.','2026-10-21 09:12:07'),(5,'Code Wizards','CSPIT','uploads/posters/poster_code_wizards.png','2026-11-20','Technical','Closed','Intense competitive programming arena testing data structures and algorithms.','2026-10-21 09:12:07');
/*!40000 ALTER TABLE `events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `registrations`
--

DROP TABLE IF EXISTS `registrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `registrations` (
  `reg_id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `event_id` int NOT NULL,
  `registered_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`reg_id`),
  KEY `fk_reg_students` (`student_id`),
  KEY `fk_reg_events` (`event_id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `registrations`
--

LOCK TABLES `registrations` WRITE;
/*!40000 ALTER TABLE `registrations` DISABLE KEYS */;
INSERT INTO `registrations` VALUES (1,1,1,'2026-10-21 09:14:25'),(2,2,2,'2026-10-21 09:14:25'),(6,3,1,'2026-08-15 04:50:00'),(7,4,2,'2026-08-22 09:15:00'),(8,5,3,'2026-09-05 03:45:00'),(9,6,1,'2026-09-12 06:00:00'),(10,7,2,'2026-09-18 10:30:00'),(11,8,5,'2026-09-27 07:55:00'),(12,9,3,'2026-10-02 04:30:00'),(13,10,1,'2026-10-08 08:40:00'),(14,11,2,'2026-10-14 06:15:00'),(15,12,5,'2026-10-18 10:00:00'),(16,13,3,'2026-10-20 04:20:00'),(17,14,1,'2026-10-21 05:45:00');
/*!40000 ALTER TABLE `registrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `students` (
  `student_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `course` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('Active','Inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  PRIMARY KEY (`student_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` VALUES (1,'Jay Navadiya','jay@campusly.com','CE','Active'),(2,'jayyyy','jayyyy@campusly.com','CSE','Active'),(3,'Aarav Patel','25cs003@charusat.edu.in','CE','Active'),(4,'Ananya Sharma','25ce004@charusat.edu.in','CE','Active'),(5,'Rohan Mehta','25it005@charusat.edu.in','IT','Inactive'),(6,'Priya Desai','25cs006@charusat.edu.in','CSE','Active'),(7,'Vikram Joshi','25ce007@charusat.edu.in','CE','Active'),(8,'Sneha Shah','25it008@charusat.edu.in','IT','Active'),(9,'Dev Dave','25ec009@charusat.edu.in','EC','Active'),(10,'Diya Trivedi','25cs010@charusat.edu.in','CSE','Active'),(11,'Harsh Panchal','25ce011@charusat.edu.in','CE','Active'),(12,'Ishan Parekh','25it012@charusat.edu.in','IT','Active'),(13,'Kavya Soni','25cs013@charusat.edu.in','CSE','Active'),(14,'Meera Vora','25ce014@charusat.edu.in','CE','Active'),(15,'Neel Shukla','25ec015@charusat.edu.in','EC','Active'),(16,'Riya Pandya','25it016@charusat.edu.in','IT','Active'),(17,'Siddharth Rawal','25cs017@charusat.edu.in','CSE','Active'),(18,'Tanvi Bhatt','25ce018@charusat.edu.in','CE','Active'),(19,'Varun Prajapati','25it019@charusat.edu.in','IT','Active'),(20,'Yash Solanki','25ec020@charusat.edu.in','EC','Active'),(23,'Kavish Parekh','25cs021@charusat.edu.in','CSE','Active'),(24,'Jash Patel','jash.patel@charusat.edu.in','CSE','Active'),(27,'Aditya Verma','25cs026@charusat.edu.in','CSE','Inactive'),(29,'Aditya Verma','aditya.verma300@charusat.edu.in','CSE','Active'),(31,'Aditya Verma','aditya.verma777@charusat.edu.in','CSE','Active');
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','student') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'student',
  `status` enum('Active','Inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `student_id` int DEFAULT NULL,
  `remember_token_hash` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token_expires` datetime DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Administrator','admin@campusly.com','$2y$12$nah.j6ixJ8ZOl33C.6kyouGO3kDb5n.KK.zOJDtTFnslMpmKjImbq','admin','Active','2026-10-21 15:19:57','2026-10-14 18:23:24',NULL,NULL,NULL),(2,'Jay Navadiya','jay@campusly.com','$2y$12$0YY8yE51ZMgUQlbHx8h.AeqEnzcjke5n7E.VAdlKwV8RwPbImdbVO','student','Active','2026-10-21 15:20:07','2026-10-14 18:23:24',NULL,NULL,NULL),(3,'Jash Patel','student@campusly.com','$2y$12$0YY8yE51ZMgUQlbHx8h.AeqEnzcjke5n7E.VAdlKwV8RwPbImdbVO','student','Active','2026-10-14 23:53:24','2026-10-14 18:23:24',NULL,NULL,NULL),(4,'Bhavy Gol','bhavy@campusly.com','$2y$12$0YY8yE51ZMgUQlbHx8h.AeqEnzcjke5n7E.VAdlKwV8RwPbImdbVO','student','Inactive','2026-10-14 23:53:24','2026-10-14 18:23:24',NULL,NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-21 16:24:58
