-- ====================================================================
-- Database: industrial_maintenance
-- Project: Industrial Equipment Maintenance & Tracking System
-- Description: Complete schema and realistic factory demo data
-- ====================================================================

CREATE DATABASE IF NOT EXISTS `industrial_maintenance` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `industrial_maintenance`;

-- Disable foreign key checks during setup
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `breakdowns`;
DROP TABLE IF EXISTS `maintenance_records`;
DROP TABLE IF EXISTS `machines`;
DROP TABLE IF EXISTS `technicians`;
DROP TABLE IF EXISTS `users`;

-- ====================================================================
-- 1. Table: users
-- ====================================================================
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` VARCHAR(50) DEFAULT 'Administrator',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ====================================================================
-- 2. Table: technicians
-- ====================================================================
CREATE TABLE `technicians` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `employee_id` VARCHAR(50) NOT NULL UNIQUE,
  `department` VARCHAR(100) NOT NULL,
  `specialization` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(25) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `joining_date` DATE NOT NULL,
  `status` ENUM('Active', 'Inactive') DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ====================================================================
-- 3. Table: machines
-- ====================================================================
CREATE TABLE `machines` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `machine_name` VARCHAR(150) NOT NULL,
  `machine_type` VARCHAR(100) NOT NULL,
  `department` VARCHAR(100) NOT NULL,
  `location` VARCHAR(150) NOT NULL,
  `manufacturer` VARCHAR(100) NOT NULL,
  `model_number` VARCHAR(100) NOT NULL,
  `installation_date` DATE NOT NULL,
  `last_maintenance_date` DATE NULL,
  `next_maintenance_date` DATE NULL,
  `status` ENUM('Working', 'Under Maintenance', 'Breakdown', 'Inactive') DEFAULT 'Working',
  `technician_id` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_machine_status` (`status`),
  INDEX `idx_machine_dept` (`department`),
  CONSTRAINT `fk_machines_technician` FOREIGN KEY (`technician_id`) 
    REFERENCES `technicians` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ====================================================================
-- 4. Table: maintenance_records
-- ====================================================================
CREATE TABLE `maintenance_records` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `machine_id` INT NOT NULL,
  `maintenance_type` ENUM('Preventive', 'Corrective', 'Emergency') NOT NULL,
  `scheduled_date` DATE NOT NULL,
  `completed_date` DATE NULL,
  `technician_id` INT NULL,
  `description` TEXT NOT NULL,
  `parts_replaced` VARCHAR(255) NULL,
  `cost` DECIMAL(10,2) DEFAULT 0.00,
  `status` ENUM('Scheduled', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_maintenance_status` (`status`),
  INDEX `idx_maintenance_sched` (`scheduled_date`),
  CONSTRAINT `fk_maint_machine` FOREIGN KEY (`machine_id`) 
    REFERENCES `machines` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_maint_technician` FOREIGN KEY (`technician_id`) 
    REFERENCES `technicians` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ====================================================================
-- 5. Table: breakdowns
-- ====================================================================
CREATE TABLE `breakdowns` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `machine_id` INT NOT NULL,
  `reported_date` DATETIME NOT NULL,
  `problem_description` TEXT NOT NULL,
  `severity` ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
  `technician_id` INT NULL,
  `start_time` DATETIME NULL,
  `resolution_time` DATETIME NULL,
  `status` ENUM('Reported', 'In Progress', 'Resolved') DEFAULT 'Reported',
  `resolution_notes` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_breakdown_status` (`status`),
  INDEX `idx_breakdown_severity` (`severity`),
  CONSTRAINT `fk_breakdown_machine` FOREIGN KEY (`machine_id`) 
    REFERENCES `machines` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_breakdown_technician` FOREIGN KEY (`technician_id`) 
    REFERENCES `technicians` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ====================================================================
-- SEED DATA
-- ====================================================================

-- 1. Default Administrator Account (Password: Admin@123)
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`) VALUES
(1, 'Plant Administrator', 'admin@factory.com', '$2y$10$ih9QuuUiCX3LvP8nCfMoSugv4pXowbaL7oc1SLRVMHFhrDmzzwxie', 'Plant Manager');

-- 2. Technicians (6 realistic records)
INSERT INTO `technicians` (`id`, `name`, `employee_id`, `department`, `specialization`, `phone`, `email`, `joining_date`, `status`) VALUES
(1, 'Rajesh Kumar', 'TECH-101', 'Production', 'CNC Machining & Precision Calibration', '+91 98765 43210', 'rajesh.kumar@factory.com', '2021-03-15', 'Active'),
(2, 'David Miller', 'TECH-102', 'Maintenance', 'High-Pressure Hydraulic & Pneumatics', '+91 98765 43211', 'david.miller@factory.com', '2020-07-22', 'Active'),
(3, 'Priya Sharma', 'TECH-103', 'Quality Control', 'Sensors, Metrology & Optical Alignment', '+91 98765 43212', 'priya.sharma@factory.com', '2022-01-10', 'Active'),
(4, 'Michael Chen', 'TECH-104', 'Assembly', 'Robotics, PLC Automation & Servo Drives', '+91 98765 43213', 'michael.chen@factory.com', '2019-11-05', 'Active'),
(5, 'Arun Patel', 'TECH-105', 'Packaging', 'Conveyors, High-Speed Mechanical Drives', '+91 98765 43214', 'arun.patel@factory.com', '2023-04-01', 'Active'),
(6, 'Suresh Nair', 'TECH-106', 'Maintenance', 'Electrical Switchgear & 415V Power Grids', '+91 98765 43215', 'suresh.nair@factory.com', '2018-09-18', 'Inactive');

-- 3. Machines (10 realistic industrial equipment records)
INSERT INTO `machines` (`id`, `machine_name`, `machine_type`, `department`, `location`, `manufacturer`, `model_number`, `installation_date`, `last_maintenance_date`, `next_maintenance_date`, `status`, `technician_id`) VALUES
(1, 'CNC Milling Machine VF-4', 'CNC Machining', 'Production', 'Bay 1 - Machine Floor A', 'Haas Automation', 'VF-4SS', '2022-02-10', '2026-09-15', '2026-11-15', 'Working', 1),
(2, '500T Hydraulic Deep Drawing Press', 'Hydraulic Press', 'Assembly', 'Bay 3 - Stamping Line', 'Schuler Group', 'HP-500X', '2021-06-20', '2026-09-02', '2026-10-25', 'Working', 2),
(3, 'Automated Sorting Conveyor Line', 'Material Handling', 'Packaging', 'Line 2 - Packaging Hall', 'Dorner Systems', 'AquaPruf 3200', '2023-01-18', '2026-08-20', '2026-10-20', 'Working', 5),
(4, '6-Axis Robotic Welding Station', 'Robotic Welding Cell', 'Assembly', 'Cell W-04 - Fabrication', 'KUKA Robotics', 'KR-CYBERTECH', '2022-08-14', '2026-07-10', '2026-10-04', 'Under Maintenance', 4),
(5, 'High-Speed Flow Wrapper', 'Industrial Packaging Machine', 'Packaging', 'Line 1 - Primary Packaging', 'Bosch Packaging', 'Sigpack TTM', '2021-11-05', '2026-09-28', '2026-12-28', 'Working', 5),
(6, 'Heavy Radial Arm Drilling Machine', 'Drilling Machine', 'Production', 'Bay 2 - Heavy Fab Section', 'Batliboi Ltd', 'BDR-65', '2020-04-12', '2026-06-18', '2026-09-30', 'Breakdown', 1),
(7, 'Precision Injection Molding Machine', 'Injection Molding Machine', 'Production', 'Plastics Division - Bay 2', 'Engel Austria', 'Victory 330', '2022-10-30', '2026-09-12', '2026-11-12', 'Working', 2),
(8, 'Rotary Screw Air Compressor 75HP', 'Air Compressor', 'Maintenance', 'Utility Room - Building B', 'Atlas Copco', 'GA-55+', '2019-05-15', '2026-08-01', '2026-10-15', 'Working', 2),
(9, 'Zeiss Coordinate Measuring Machine', 'Quality Inspection', 'Quality Control', 'QC Cleanroom 102', 'Carl Zeiss', 'CONTURA G2', '2023-05-22', '2026-09-20', '2026-12-20', 'Working', 3),
(10, 'Fiber Laser Metal Sheet Cutter', 'Laser Cutting Machine', 'Production', 'Sheet Metal Bay C', 'Trumpf', 'TruLaser 3030', '2020-01-10', '2026-05-10', '2026-11-01', 'Inactive', 1);

-- 4. Maintenance Records (12 records: mix of Scheduled, In Progress, Completed, Overdue)
INSERT INTO `maintenance_records` (`id`, `machine_id`, `maintenance_type`, `scheduled_date`, `completed_date`, `technician_id`, `description`, `parts_replaced`, `cost`, `status`) VALUES
(1, 1, 'Preventive', '2026-09-15', '2026-09-15', 1, 'Quarterly spindle alignment, coolant flush and way lube filter replacement.', 'Oil Filter, Coolant Seal Kit', 450.00, 'Completed'),
(2, 2, 'Preventive', '2026-09-02', '2026-09-02', 2, 'Main cylinder pressure calibration, hydraulic valve check and hose inspection.', 'Hydraulic O-Rings, Pressure Gauge', 620.00, 'Completed'),
(3, 3, 'Corrective', '2026-08-20', '2026-08-20', 5, 'Conveyor belt tracking correction and tensioner pulley bearing replacement.', 'Pillow Block Bearings (x2)', 280.00, 'Completed'),
(4, 4, 'Preventive', '2026-10-04', NULL, 4, 'Annual robotic wrist joint grease renewal and safety interlock circuit diagnostics.', 'KUKA Synthetic Grease 5L', 890.00, 'In Progress'),
(5, 5, 'Preventive', '2026-09-28', '2026-09-28', 5, 'Sealing jaw heating element replacement and temperature sensor calibration.', 'Heating Cartridges (x4), Thermocouple', 340.00, 'Completed'),
(6, 6, 'Emergency', '2026-09-30', NULL, 1, 'Emergency gearbox inspection after severe noise and spindle stall during drilling.', 'None yet (Awaiting teardown)', 150.00, 'Scheduled'),
(7, 7, 'Preventive', '2026-09-12', '2026-09-12', 2, 'Screw tip ring check, barrel heater band insulation test and clamping unit lube.', 'Copper Shims, Thermocouple wire', 510.00, 'Completed'),
(8, 8, 'Preventive', '2026-10-15', NULL, 2, 'Scheduled air/oil separator filter replacement and minimum pressure valve overhaul.', 'Oil Separator Filter, Air Intake Filter', 780.00, 'Scheduled'),
(9, 9, 'Preventive', '2026-09-20', '2026-09-20', 3, 'Calibration using master sphere and optical encoder cleaning in cleanroom.', 'None (Clean and Re-calibrate)', 200.00, 'Completed'),
(10, 1, 'Preventive', '2026-11-15', NULL, 1, 'Bi-monthly tool changer magazine check and carousel proximity switch alignment.', 'Proximity Sensor', 180.00, 'Scheduled'),
(11, 2, 'Preventive', '2026-10-25', NULL, 2, 'Hydraulic oil sampling analysis and return line filter cartridge renewal.', 'Cartridge Element 10 Micron', 420.00, 'Scheduled'),
(12, 3, 'Preventive', '2026-10-06', NULL, 5, 'Routine inspection of drive gearbox oil level and photogate sensor cleaning.', 'Gear Lubricant ISO VG 220', 95.00, 'Scheduled');

-- 5. Breakdown Records (6 records: mix of Critical, High, Medium, Low; Reported, In Progress, Resolved)
INSERT INTO `breakdowns` (`id`, `machine_id`, `reported_date`, `problem_description`, `severity`, `technician_id`, `start_time`, `resolution_time`, `status`, `resolution_notes`) VALUES
(1, 6, '2026-10-05 09:30:00', 'Spindle drive gearbox produced loud grinding sound and locked up during deep hole drilling. Motor tripped circuit breaker.', 'Critical', 1, '2026-10-05 10:15:00', NULL, 'Reported', 'Initial inspection suggests broken intermediate pinion gear. Gearbox cover removed; waiting for spare gears from supplier.'),
(2, 4, '2026-10-04 14:10:00', 'Axis 3 servo amplifier fault code E-2038 (Current Overload). Robot halted mid-cycle on welding seam.', 'High', 4, '2026-10-04 15:00:00', NULL, 'In Progress', 'Inspecting servo motor cabling harness for internal short circuit. Resistance check on motor winding ongoing.'),
(3, 2, '2026-08-18 11:20:00', 'Main hydraulic pump discharge line developed high-pressure spray leak from cracked flange fitting.', 'High', 2, '2026-08-18 11:45:00', '2026-08-18 16:30:00', 'Resolved', 'System de-pressurized, cracked SAE 4-bolt flange replaced with heavy-duty forged fitting. Fluid topped up and leak-tested at 250 bar.'),
(4, 5, '2026-07-25 08:45:00', 'Film feeder servo drive intermittently jamming and throwing position error, halting packaging conveyor.', 'Medium', 5, '2026-07-25 09:15:00', '2026-07-25 12:00:00', 'Resolved', 'Detected loose timing pulley on feeding shaft. Re-keyed shaft, tightened set screws with Loctite 243, recalibrated tension.'),
(5, 8, '2026-06-12 16:00:00', 'Air discharge temperature reached 105C, triggering automatic thermal shutdown safety circuit.', 'Medium', 2, '2026-06-12 16:30:00', '2026-06-12 19:15:00', 'Resolved', 'Cooling radiator fins were clogged with industrial dust. Cleaned oil cooler core with pressurized air and degreaser; restored normal 82C operating temp.'),
(6, 1, '2026-05-19 13:10:00', 'Coolant pump motor tripped overload relay. Low coolant pressure alarm on CNC control screen.', 'Low', 1, '2026-05-19 13:30:00', '2026-05-19 15:00:00', 'Resolved', 'Coolant reservoir intake screen was clogged with aluminum chips. Sump cleaned, impeller cleared, fluid filter cleaned.');
