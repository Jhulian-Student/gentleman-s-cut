-- ==========================================================
-- Database Schema for The Gentleman's Cut Barbershop
-- Database: gentlemans_cut_db
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `gentlemans_cut_db` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `gentlemans_cut_db`;

-- ----------------------------------------------------------
-- 1. Table: users
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `phone` VARCHAR(30) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('customer', 'barber', 'admin') NOT NULL DEFAULT 'customer',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 2. Table: services
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `services` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `price` DECIMAL(10, 2) NOT NULL,
    `duration` VARCHAR(50) NOT NULL,
    `description` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 3. Table: appointments
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `appointments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `customer_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(30) NOT NULL,
    `service` VARCHAR(100) NOT NULL,
    `appointment_date` DATE NOT NULL,
    `appointment_time` VARCHAR(20) NOT NULL,
    `notes` TEXT NULL,
    `status` ENUM('Pending', 'Confirmed', 'Cancelled') NOT NULL DEFAULT 'Pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Initial Seed Data
-- Passwords below are hashed using password_hash(..., PASSWORD_DEFAULT):
-- admin@gentlemanscut.com    -> admin123
-- barber@gentlemanscut.com   -> barber123
-- customer@gentlemanscut.com -> customer123
-- ----------------------------------------------------------
INSERT INTO `users` (`full_name`, `email`, `phone`, `password`, `role`) VALUES
('Administrator', 'admin@gentlemanscut.com', '09123456780', '$2y$12$VmUDP.NhDL/OrQvBQC96wuOVwNeaYmJtzHrimOawlkwc4OrTzvbkG', 'admin'),
('Master Barber Marco', 'barber@gentlemanscut.com', '09123456781', '$2y$12$HNpdZ6YNAKfwaBKCuZGzle9XuYWd9MxLw91bmFSx.iWJt4g.xhb16', 'barber'),
('Juan Dela Cruz', 'customer@gentlemanscut.com', '09123456782', '$2y$12$4zZcwuIXFns.NzhPJ0saQOVvdcgWOW1h6DtsTDops3hHDsSZL9ntq', 'customer')
ON DUPLICATE KEY UPDATE `full_name` = VALUES(`full_name`);

INSERT INTO `services` (`name`, `price`, `duration`, `description`) VALUES
('Classic Haircut', 250.00, '30 minutes', 'Timeless cut tailored to your head shape with warm towel finish.'),
('Fade / Taper', 300.00, '40 minutes', 'Precision skin fade or clean taper styled with premium pomade.'),
('Beard Grooming', 180.00, '20 minutes', 'Detailed beard sculpting, lining, hot oil treatment and razor finish.'),
('Haircut + Beard', 400.00, '50 minutes', 'Complete haircut and beard styling combo with hot towel treatment.'),
('Haircut + Shampoo', 350.00, '45 minutes', 'Full cut paired with refreshing scalp massage and wash.'),
('Premium Grooming', 500.00, '60 minutes', 'Signature full VIP package: cut, beard trim, shampoo, and styling.')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

INSERT INTO `appointments` (`customer_name`, `email`, `phone`, `service`, `appointment_date`, `appointment_time`, `notes`, `status`) VALUES
('Juan Dela Cruz', 'customer@gentlemanscut.com', '09123456782', 'Fade / Taper', '2026-09-25', '3:00 PM', 'Keep it clean on the sides, textured on top.', 'Pending'),
('Marcus Vance', 'marcus@example.com', '09198765432', 'Classic Haircut', '2026-09-25', '10:30 AM', 'First time customer.', 'Confirmed'),
('Daniel Rivera', 'daniel@example.com', '09221234567', 'Beard Grooming', '2026-09-26', '1:00 PM', 'Trim down to medium length.', 'Pending');
