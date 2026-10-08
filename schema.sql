-- CivicConnect Database Schema
-- Compatible with MySQL 5.7+ / 8.0+ / MariaDB (XAMPP default)

CREATE DATABASE IF NOT EXISTS `civicconnect` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `civicconnect`;

-- 1. Departments Table
CREATE TABLE IF NOT EXISTS `departments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(20) NOT NULL UNIQUE,
    `description` TEXT,
    `head_name` VARCHAR(100) DEFAULT NULL,
    `contact_email` VARCHAR(100) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `role` ENUM('citizen', 'official', 'admin') NOT NULL DEFAULT 'citizen',
    `department_id` INT DEFAULT NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_user_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Categories Table
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `department_id` INT NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `sla_days` INT DEFAULT 3,
    `icon` VARCHAR(50) DEFAULT 'alert-circle',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_category_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Complaints Table
CREATE TABLE IF NOT EXISTS `complaints` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `complaint_uid` VARCHAR(30) UNIQUE NOT NULL,
    `citizen_id` INT NOT NULL,
    `category_id` INT NOT NULL,
    `department_id` INT NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT NOT NULL,
    `image` VARCHAR(255) DEFAULT NULL,
    `location` VARCHAR(255) NOT NULL,
    `latitude` DECIMAL(10, 8) DEFAULT NULL,
    `longitude` DECIMAL(11, 8) DEFAULT NULL,
    `status` ENUM('Pending', 'In Progress', 'Resolved', 'Rejected') DEFAULT 'Pending',
    `priority` ENUM('Low', 'Medium', 'High', 'Urgent') DEFAULT 'Medium',
    `assigned_to` INT DEFAULT NULL,
    `resolution_remarks` TEXT DEFAULT NULL,
    `resolution_image` VARCHAR(255) DEFAULT NULL,
    `resolved_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_complaint_citizen` FOREIGN KEY (`citizen_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_complaint_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_complaint_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_complaint_official` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Complaint Updates / Audit Log Table
CREATE TABLE IF NOT EXISTS `complaint_updates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `complaint_id` INT NOT NULL,
    `updated_by` INT NOT NULL,
    `old_status` VARCHAR(50) DEFAULT NULL,
    `new_status` VARCHAR(50) NOT NULL,
    `comment` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_update_complaint` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_update_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Notifications Table
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `complaint_id` INT DEFAULT NULL,
    `title` VARCHAR(150) NOT NULL,
    `message` VARCHAR(255) NOT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- SEED DATA (Default password for all sample accounts is: Password@123)
-- Password hash: $2y$10$RkTjJgB1yDRSKf0e2V1wzehigSjWqURQyE/t5bOeFkB8TJYK8cfUe

-- Insert Departments
INSERT INTO `departments` (`id`, `name`, `code`, `description`, `head_name`, `contact_email`) VALUES
(1, 'Public Works Department (PWD)', 'PWD', 'Road maintenance, potholes, bridge repairs, footpaths, and civil infrastructure.', 'Er. Rajesh Menon', 'pwd-support@civicconnect.gov'),
(2, 'Solid Waste Management', 'SWM', 'Garbage clearing, waste segregation, bins installation, and sanitary disposal.', 'Dr. S. Nair', 'swm@civicconnect.gov'),
(3, 'Water Supply & Sewerage Board', 'WSSB', 'Water leakages, pipeline bursts, drainage blockages, and clean drinking water.', 'Er. Kavitha Rao', 'water@civicconnect.gov'),
(4, 'Electricity & Streetlighting', 'ELEC', 'Faulty streetlights, open transformer hazards, wire sags, and public lighting.', 'Er. Thomas Varghese', 'electricity@civicconnect.gov'),
(5, 'Public Health & Sanitation', 'PHS', 'Mosquito fogging, stray animal control, sanitation hazards, and disease prevention.', 'Dr. A. Verma', 'health@civicconnect.gov')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- Insert Users
-- 1. Admin
-- 2-5. Department Officials
-- 6-7. Demo Citizens
INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `role`, `department_id`, `status`) VALUES
(1, 'System Administrator', 'admin@civicconnect.gov', '$2y$10$RkTjJgB1yDRSKf0e2V1wzehigSjWqURQyE/t5bOeFkB8TJYK8cfUe', '+91 9876543210', 'admin', NULL, 'active'),
(2, 'PWD Officer - Rajesh', 'pwd.officer@civicconnect.gov', '$2y$10$RkTjJgB1yDRSKf0e2V1wzehigSjWqURQyE/t5bOeFkB8TJYK8cfUe', '+91 9876543211', 'official', 1, 'active'),
(3, 'Sanitation Officer - Anita', 'swm.officer@civicconnect.gov', '$2y$10$RkTjJgB1yDRSKf0e2V1wzehigSjWqURQyE/t5bOeFkB8TJYK8cfUe', '+91 9876543212', 'official', 2, 'active'),
(4, 'Water Dept Officer - Ramesh', 'water.officer@civicconnect.gov', '$2y$10$RkTjJgB1yDRSKf0e2V1wzehigSjWqURQyE/t5bOeFkB8TJYK8cfUe', '+91 9876543213', 'official', 3, 'active'),
(5, 'Electrical Officer - Suresh', 'elec.officer@civicconnect.gov', '$2y$10$RkTjJgB1yDRSKf0e2V1wzehigSjWqURQyE/t5bOeFkB8TJYK8cfUe', '+91 9876543214', 'official', 4, 'active'),
(6, 'Adith S Kumar (Citizen)', 'citizen@civicconnect.gov', '$2y$10$RkTjJgB1yDRSKf0e2V1wzehigSjWqURQyE/t5bOeFkB8TJYK8cfUe', '+91 9876543220', 'citizen', NULL, 'active'),
(7, 'Priya Sharma (Citizen)', 'priya.citizen@gmail.com', '$2y$10$RkTjJgB1yDRSKf0e2V1wzehigSjWqURQyE/t5bOeFkB8TJYK8cfUe', '+91 9876543221', 'citizen', NULL, 'active')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- Insert Categories
INSERT INTO `categories` (`id`, `name`, `department_id`, `description`, `sla_days`, `icon`) VALUES
(1, 'Potholes & Broken Roads', 1, 'Deep potholes, broken tarmac, cracked road surface posing accident hazard.', 3, 'road'),
(2, 'Damaged Footpaths & Curbs', 1, 'Pedestrian footpath broken, missing curb slabs, uneven pavement.', 5, 'footprints'),
(3, 'Garbage Dump Accumulation', 2, 'Uncollected garbage overflow, illegal dumping on roadsides.', 2, 'trash-2'),
(4, 'Dead Animal Removal', 2, 'Immediate clearance of dead animals from public thoroughfares.', 1, 'shield-alert'),
(5, 'Main Water Pipeline Leakage', 3, 'Severe water pipe burst or ongoing fresh water wastage.', 1, 'droplets'),
(6, 'Drainage Blockage & Sewage Overflow', 3, 'Blocked storm drain, open manhole overflowing sewage onto road.', 2, 'waves'),
(7, 'Streetlight Not Working', 4, 'Dark street segment, flickering or non-functional public streetlight poles.', 2, 'lightbulb-off'),
(8, 'Exposed Live Wires / Transformer Sparks', 4, 'Urgent electrical hazard, hanging live wires or sparked poles.', 1, 'zap'),
(9, 'Mosquito Infestation & Stagnant Water', 5, 'Need for fogging, stagnant filthy water pool breeding mosquitoes.', 4, 'bug'),
(10, 'Others (Miscellaneous)', 1, 'Any other civic or public infrastructure issue not listed above.', 3, 'ellipsis')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- Insert Sample Complaints
INSERT INTO `complaints` (`id`, `complaint_uid`, `citizen_id`, `category_id`, `department_id`, `title`, `description`, `image`, `location`, `latitude`, `longitude`, `status`, `priority`, `assigned_to`, `resolution_remarks`, `created_at`, `updated_at`) VALUES
(1, 'CC-2026-0001', 6, 1, 1, 'Massive pothole near MG Road Metro Pillar 142', 'Dangerous pothole approximately 1.5 feet deep causing two-wheeler skidding during rainy evening.', 'sample_pothole.jpg', 'MG Road, Near Metro Pillar 142, Central Ward', 12.9715987, 77.5945627, 'In Progress', 'High', 2, 'Inspection completed. Repair crew scheduled for asphalt filling tonight.', NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 4 HOUR),
(2, 'CC-2026-0002', 6, 3, 2, 'Overflowing dumpster in Sector 4 Market', 'Trash bin has not been cleared for 4 days. Waste spilled over pedestrian walking zone creating foul smell.', 'sample_garbage.jpg', 'Sector 4 Community Market, North Zone', 12.9823000, 77.6012000, 'Resolved', 'Medium', 3, 'Trash cleaned and 2 additional large bins placed. Area disinfected with bleaching powder.', NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 1 DAY),
(3, 'CC-2026-0003', 7, 5, 3, 'Water main burst on 8th Cross Main Road', 'Heavy volume of clean drinking water gushing onto the road since morning 6 AM. Road getting eroded.', 'sample_water.jpg', '8th Cross, Gandhi Nagar, East Zone', 12.9654000, 77.5832000, 'Pending', 'Urgent', NULL, NULL, NOW() - INTERVAL 3 HOUR, NOW() - INTERVAL 3 HOUR),
(4, 'CC-2026-0004', 7, 7, 4, 'Streetlights out for entire 500m residential lane', '5 continuous streetlights dark since last weekend. Lane is unsafe for women and pedestrians at night.', 'sample_light.jpg', 'Rosewood Avenue Lane 3, West Zone', 12.9512000, 77.5741000, 'In Progress', 'Medium', 5, 'Electrical technician team dispatched to replace burnt out LED fixtures and sensor relays.', NOW() - INTERVAL 1 DAY, NOW() - INTERVAL 2 HOUR)
ON DUPLICATE KEY UPDATE `title`=VALUES(`title`);

-- Insert Sample Updates
INSERT INTO `complaint_updates` (`complaint_id`, `updated_by`, `old_status`, `new_status`, `comment`, `created_at`) VALUES
(1, 6, NULL, 'Pending', 'Complaint submitted by citizen with GPS location coordinates.', NOW() - INTERVAL 2 DAY),
(1, 2, 'Pending', 'In Progress', 'Assigned to Ward Road Repair Unit. Site inspection done.', NOW() - INTERVAL 4 HOUR),
(2, 6, NULL, 'Pending', 'Complaint filed regarding market dumpster overflow.', NOW() - INTERVAL 5 DAY),
(2, 3, 'Pending', 'In Progress', 'Waste collection truck unit #14 dispatched.', NOW() - INTERVAL 3 DAY),
(2, 3, 'In Progress', 'Resolved', 'Waste cleared completely and sanitation spray applied. Citizen verified.', NOW() - INTERVAL 1 DAY),
(4, 7, NULL, 'Pending', 'Complaint registered for dark street lane.', NOW() - INTERVAL 1 DAY),
(4, 5, 'Pending', 'In Progress', 'Maintenance crew scheduled for night line replacement.', NOW() - INTERVAL 2 HOUR);

-- Sample Notifications
INSERT INTO `notifications` (`user_id`, `complaint_id`, `title`, `message`, `is_read`, `created_at`) VALUES
(6, 1, 'Status Updated to In Progress', 'Your complaint CC-2026-0001 (Massive pothole...) has been assigned to PWD Officer Rajesh.', 0, NOW() - INTERVAL 4 HOUR),
(6, 2, 'Issue Resolved!', 'Your complaint CC-2026-0002 (Overflowing dumpster...) has been marked as Resolved.', 1, NOW() - INTERVAL 1 DAY),
(7, 4, 'Under Maintenance', 'Your complaint CC-2026-0004 (Streetlights out...) is now In Progress.', 0, NOW() - INTERVAL 2 HOUR);
