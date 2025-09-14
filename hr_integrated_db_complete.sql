-- =====================================================
-- HR34 INTEGRATED DATABASE - COMPLETE CONSOLIDATED SCHEMA
-- =====================================================
-- This file consolidates all database components into hr_integrated_db
-- Includes: Core tables, authentication, HMO, notifications, and sample data

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 08, 2025 at 06:39 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
-- Database: `hr_integrated_db`
-- --------------------------------------------------------

-- =====================================================
-- CORE HR TABLES
-- =====================================================

-- Table structure for table `departments`
CREATE TABLE `departments` (
  `DepartmentID` int(11) NOT NULL AUTO_INCREMENT,
  `DepartmentName` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  `ManagerID` int(11) DEFAULT NULL,
  PRIMARY KEY (`DepartmentID`),
  KEY `ManagerID` (`ManagerID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `jobroles`
CREATE TABLE `jobroles` (
  `JobRoleID` int(11) NOT NULL AUTO_INCREMENT,
  `RoleName` varchar(100) NOT NULL,
  `RoleDescription` text DEFAULT NULL,
  PRIMARY KEY (`JobRoleID`),
  UNIQUE KEY `RoleName` (`RoleName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `employees`
CREATE TABLE `employees` (
  `EmployeeID` int(11) NOT NULL AUTO_INCREMENT,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `MiddleName` varchar(50) DEFAULT NULL,
  `Suffix` varchar(10) DEFAULT NULL,
  `Email` varchar(100) NOT NULL,
  `PersonalEmail` varchar(100) DEFAULT NULL,
  `Phone` varchar(20) NOT NULL,
  `DateOfBirth` date NOT NULL,
  `Gender` enum('Male','Female','Other') DEFAULT NULL,
  `MaritalStatus` enum('Single','Married','Divorced','Widowed') DEFAULT NULL,
  `Nationality` varchar(50) DEFAULT NULL,
  `AddressLine1` varchar(255) DEFAULT NULL,
  `AddressLine2` varchar(255) DEFAULT NULL,
  `City` varchar(100) DEFAULT NULL,
  `StateProvince` varchar(100) DEFAULT NULL,
  `PostalCode` varchar(20) DEFAULT NULL,
  `Country` varchar(100) DEFAULT NULL,
  `EmergencyContactName` varchar(100) DEFAULT NULL,
  `EmergencyContactRelationship` varchar(50) DEFAULT NULL,
  `EmergencyContactPhone` varchar(20) DEFAULT NULL,
  `HireDate` date NOT NULL,
  `TerminationDate` date DEFAULT NULL,
  `TerminationReason` text DEFAULT NULL,
  `DepartmentID` int(11) NOT NULL,
  `JobRoleID` int(11) NOT NULL,
  `ManagerID` int(11) DEFAULT NULL,
  `Status` enum('Active','Inactive','Terminated') DEFAULT 'Active',
  `EmployeePhotoPath` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`EmployeeID`),
  UNIQUE KEY `Email` (`Email`),
  KEY `DepartmentID` (`DepartmentID`),
  KEY `JobRoleID` (`JobRoleID`),
  KEY `ManagerID` (`ManagerID`),
  KEY `Status` (`Status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- AUTHENTICATION TABLES
-- =====================================================

-- Table structure for table `Roles`
CREATE TABLE `Roles` (
  `RoleID` int(11) NOT NULL AUTO_INCREMENT,
  `RoleName` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  PRIMARY KEY (`RoleID`),
  UNIQUE KEY `RoleName` (`RoleName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `Users`
CREATE TABLE `Users` (
  `UserID` int(11) NOT NULL AUTO_INCREMENT,
  `EmployeeID` int(11) DEFAULT NULL,
  `Username` varchar(100) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `RoleID` int(11) NOT NULL,
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsTwoFactorEnabled` tinyint(1) NOT NULL DEFAULT 0,
  `TwoFactorEmailCode` varchar(10) DEFAULT NULL,
  `TwoFactorCodeExpiry` datetime DEFAULT NULL,
  `CreatedAt` timestamp DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`UserID`),
  UNIQUE KEY `Username` (`Username`),
  KEY `RoleID` (`RoleID`),
  KEY `EmployeeID` (`EmployeeID`),
  KEY `IsActive` (`IsActive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- PAYROLL TABLES
-- =====================================================

-- Table structure for table `payrollruns`
CREATE TABLE `payrollruns` (
  `PayrollRunID` int(11) NOT NULL AUTO_INCREMENT,
  `RunName` varchar(255) NOT NULL,
  `PayPeriodStart` date NOT NULL,
  `PayPeriodEnd` date NOT NULL,
  `RunDate` datetime DEFAULT CURRENT_TIMESTAMP,
  `Status` varchar(20) DEFAULT 'Draft',
  `TotalEmployees` int(11) DEFAULT 0,
  `TotalGrossPay` decimal(15,2) DEFAULT 0.00,
  `TotalDeductions` decimal(15,2) DEFAULT 0.00,
  `TotalNetPay` decimal(15,2) DEFAULT 0.00,
  `CreatedBy` int(11) DEFAULT NULL,
  `ProcessedDate` datetime DEFAULT NULL,
  PRIMARY KEY (`PayrollRunID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `employeesalaries`
CREATE TABLE `employeesalaries` (
  `SalaryID` int(11) NOT NULL AUTO_INCREMENT,
  `EmployeeID` int(11) NOT NULL,
  `SalaryAmount` decimal(10,2) NOT NULL,
  `EffectiveDate` date NOT NULL DEFAULT (CURDATE()),
  `EndDate` date DEFAULT NULL,
  PRIMARY KEY (`SalaryID`),
  KEY `EmployeeID` (`EmployeeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `bonuses`
CREATE TABLE `bonuses` (
  `BonusID` int(11) NOT NULL AUTO_INCREMENT,
  `EmployeeID` int(11) NOT NULL,
  `BonusAmount` decimal(10,2) NOT NULL,
  `BonusDate` date NOT NULL,
  `Reason` text DEFAULT NULL,
  PRIMARY KEY (`BonusID`),
  KEY `EmployeeID` (`EmployeeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `deductions`
CREATE TABLE `deductions` (
  `DeductionID` int(11) NOT NULL AUTO_INCREMENT,
  `EmployeeID` int(11) NOT NULL,
  `DeductionType` varchar(100) NOT NULL,
  `Amount` decimal(10,2) NOT NULL,
  `EffectiveDate` date NOT NULL DEFAULT (CURDATE()),
  `EndDate` date DEFAULT NULL,
  `Description` text DEFAULT NULL,
  PRIMARY KEY (`DeductionID`),
  KEY `EmployeeID` (`EmployeeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- DOCUMENTS TABLES
-- =====================================================

-- Table structure for table `documents`
CREATE TABLE `documents` (
  `DocumentID` int(11) NOT NULL AUTO_INCREMENT,
  `EmployeeID` int(11) NOT NULL,
  `DocumentName` varchar(255) NOT NULL,
  `DocumentType` varchar(100) NOT NULL,
  `FilePath` varchar(500) DEFAULT NULL,
  `UploadDate` datetime DEFAULT CURRENT_TIMESTAMP,
  `Status` varchar(20) DEFAULT 'Active',
  `Description` text DEFAULT NULL,
  PRIMARY KEY (`DocumentID`),
  KEY `EmployeeID` (`EmployeeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- TIME & ATTENDANCE TABLES
-- =====================================================

-- Table structure for table `shifts`
CREATE TABLE `shifts` (
  `ShiftID` int(11) NOT NULL AUTO_INCREMENT,
  `ShiftName` varchar(100) NOT NULL,
  `StartTime` time NOT NULL,
  `EndTime` time NOT NULL,
  `BreakDuration` int(11) DEFAULT 60,
  `IsActive` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`ShiftID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `attendancerecords`
CREATE TABLE `attendancerecords` (
  `RecordID` int(11) NOT NULL AUTO_INCREMENT,
  `EmployeeID` int(11) NOT NULL,
  `Date` date NOT NULL,
  `CheckInTime` time NOT NULL,
  `CheckOutTime` time DEFAULT NULL,
  `Status` enum('Present','Absent','Late','On Leave') DEFAULT 'Present',
  PRIMARY KEY (`RecordID`),
  KEY `EmployeeID` (`EmployeeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- HMO & BENEFITS TABLES
-- =====================================================

-- Table structure for table `HMOProviders`
CREATE TABLE `HMOProviders` (
  `ProviderID` int(11) NOT NULL AUTO_INCREMENT,
  `ProviderName` varchar(150) NOT NULL,
  `ContactInfo` varchar(255) DEFAULT NULL,
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`ProviderID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `BenefitsPlans`
CREATE TABLE `BenefitsPlans` (
  `BenefitID` int(11) NOT NULL AUTO_INCREMENT,
  `ProviderID` int(11) NOT NULL,
  `PlanName` varchar(150) NOT NULL,
  `CoverageDetails` text DEFAULT NULL,
  `MonthlyPremium` decimal(12,2) DEFAULT NULL,
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`BenefitID`),
  KEY `ProviderID` (`ProviderID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `EmployeeBenefits`
CREATE TABLE `EmployeeBenefits` (
  `EmployeeBenefitID` int(11) NOT NULL AUTO_INCREMENT,
  `EmployeeID` int(11) NOT NULL,
  `BenefitID` int(11) NOT NULL,
  `EnrollmentDate` date NOT NULL,
  `Status` varchar(50) NOT NULL DEFAULT 'Active',
  PRIMARY KEY (`EmployeeBenefitID`),
  KEY `EmployeeID` (`EmployeeID`),
  KEY `BenefitID` (`BenefitID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- NOTIFICATIONS TABLES
-- =====================================================

-- Table structure for table `notifications`
CREATE TABLE `notifications` (
  `NotificationID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) NOT NULL,
  `Title` varchar(255) NOT NULL,
  `Message` text NOT NULL,
  `Type` varchar(50) DEFAULT 'info',
  `IsRead` tinyint(1) DEFAULT 0,
  `CreatedDate` datetime DEFAULT CURRENT_TIMESTAMP,
  `ReadDate` datetime DEFAULT NULL,
  PRIMARY KEY (`NotificationID`),
  KEY `UserID` (`UserID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- COMPENSATION TABLES
-- =====================================================

-- Table structure for table `compensationplans`
CREATE TABLE `compensationplans` (
  `PlanID` int(11) NOT NULL AUTO_INCREMENT,
  `PlanName` varchar(255) NOT NULL,
  `Description` text DEFAULT NULL,
  PRIMARY KEY (`PlanID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `salaryadjustments`
CREATE TABLE `salaryadjustments` (
  `AdjustmentID` int(11) NOT NULL AUTO_INCREMENT,
  `EmployeeID` int(11) NOT NULL,
  `AdjustmentAmount` decimal(10,2) NOT NULL,
  `EffectiveDate` date NOT NULL,
  `Reason` text DEFAULT NULL,
  PRIMARY KEY (`AdjustmentID`),
  KEY `EmployeeID` (`EmployeeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `incentives`
CREATE TABLE `incentives` (
  `IncentiveID` int(11) NOT NULL AUTO_INCREMENT,
  `EmployeeID` int(11) NOT NULL,
  `IncentiveAmount` decimal(10,2) NOT NULL,
  `Reason` text DEFAULT NULL,
  `Date` date NOT NULL DEFAULT (CURDATE()),
  PRIMARY KEY (`IncentiveID`),
  KEY `EmployeeID` (`EmployeeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- ORGANIZATIONAL STRUCTURE TABLES
-- =====================================================

-- Table structure for table `organizationalstructure`
CREATE TABLE `organizationalstructure` (
  `StructureID` int(11) NOT NULL AUTO_INCREMENT,
  `StructureName` varchar(255) NOT NULL,
  `ParentStructureID` int(11) DEFAULT NULL,
  PRIMARY KEY (`StructureID`),
  KEY `ParentStructureID` (`ParentStructureID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- FOREIGN KEY CONSTRAINTS
-- =====================================================

-- Constraints for Users table
ALTER TABLE `Users`
  ADD CONSTRAINT `fk_users_roles` FOREIGN KEY (`RoleID`) REFERENCES `Roles` (`RoleID`),
  ADD CONSTRAINT `fk_users_employees` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`);

-- Constraints for employees table
ALTER TABLE `employees`
  ADD CONSTRAINT `fk_employees_department` FOREIGN KEY (`DepartmentID`) REFERENCES `departments` (`DepartmentID`),
  ADD CONSTRAINT `fk_employees_jobrole` FOREIGN KEY (`JobRoleID`) REFERENCES `jobroles` (`JobRoleID`),
  ADD CONSTRAINT `fk_employees_manager` FOREIGN KEY (`ManagerID`) REFERENCES `employees` (`EmployeeID`);

-- Constraints for departments table
ALTER TABLE `departments`
  ADD CONSTRAINT `fk_departments_manager` FOREIGN KEY (`ManagerID`) REFERENCES `employees` (`EmployeeID`);

-- Constraints for payroll tables
ALTER TABLE `employeesalaries`
  ADD CONSTRAINT `fk_employeesalaries_employee` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`);

ALTER TABLE `bonuses`
  ADD CONSTRAINT `fk_bonuses_employee` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`);

ALTER TABLE `deductions`
  ADD CONSTRAINT `fk_deductions_employee` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`);

-- Constraints for documents table
ALTER TABLE `documents`
  ADD CONSTRAINT `fk_documents_employee` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`);

-- Constraints for attendance table
ALTER TABLE `attendancerecords`
  ADD CONSTRAINT `fk_attendancerecords_employee` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`);

-- Constraints for HMO tables
ALTER TABLE `BenefitsPlans`
  ADD CONSTRAINT `fk_benefits_provider` FOREIGN KEY (`ProviderID`) REFERENCES `HMOProviders` (`ProviderID`);

ALTER TABLE `EmployeeBenefits`
  ADD CONSTRAINT `fk_emp_benefit_employee` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`),
  ADD CONSTRAINT `fk_emp_benefit_plan` FOREIGN KEY (`BenefitID`) REFERENCES `BenefitsPlans` (`BenefitID`);

-- Constraints for notifications table
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`UserID`) REFERENCES `Users` (`UserID`);

-- Constraints for compensation tables
ALTER TABLE `salaryadjustments`
  ADD CONSTRAINT `fk_salaryadjustments_employee` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`);

ALTER TABLE `incentives`
  ADD CONSTRAINT `fk_incentives_employee` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`);

-- Constraints for organizational structure
ALTER TABLE `organizationalstructure`
  ADD CONSTRAINT `fk_orgstructure_parent` FOREIGN KEY (`ParentStructureID`) REFERENCES `organizationalstructure` (`StructureID`);

-- =====================================================
-- SAMPLE DATA INSERTION
-- =====================================================

-- Insert default roles
INSERT IGNORE INTO `Roles` (`RoleID`, `RoleName`, `Description`) VALUES
(1, 'System Admin', 'Full system access and administration'),
(2, 'HR Admin', 'Human Resources administration'),
(3, 'Employee', 'Standard employee access'),
(4, 'Manager', 'Manager level access');

-- Insert default departments
INSERT IGNORE INTO `departments` (`DepartmentID`, `DepartmentName`, `Description`) VALUES
(1, 'Administration', 'Administrative department'),
(2, 'Human Resources', 'Human Resources department'),
(3, 'Information Technology', 'IT department'),
(4, 'Finance', 'Finance department'),
(5, 'Operations', 'Operations department'),
(6, 'Marketing', 'Marketing department');

-- Insert default job roles
INSERT IGNORE INTO `jobroles` (`JobRoleID`, `RoleName`, `RoleDescription`) VALUES
(1, 'Administrator', 'System Administrator'),
(2, 'Manager', 'Department Manager'),
(3, 'Staff', 'Regular Staff Member'),
(4, 'Senior Staff', 'Senior Staff Member'),
(5, 'Director', 'Department Director'),
(6, 'Coordinator', 'Project Coordinator');

-- Insert default employees
INSERT IGNORE INTO `employees` (`EmployeeID`, `FirstName`, `LastName`, `Email`, `Phone`, `DateOfBirth`, `HireDate`, `DepartmentID`, `JobRoleID`, `Status`) VALUES
(1, 'System', 'Administrator', 'admin@company.com', '555-0100', '1985-01-01', '2020-01-01', 1, 1, 'Active'),
(2, 'HR', 'Manager', 'hrmanager@company.com', '555-0101', '1988-05-15', '2020-02-01', 2, 2, 'Active'),
(3, 'HR', 'Staff', 'hrstaff@company.com', '555-0102', '1990-08-20', '2021-03-01', 2, 3, 'Active'),
(4, 'John', 'Doe', 'john.doe@company.com', '555-0103', '1985-03-15', '2020-01-15', 1, 3, 'Active'),
(5, 'Jane', 'Smith', 'jane.smith@company.com', '555-0104', '1990-07-22', '2021-03-10', 2, 3, 'Active'),
(6, 'Bob', 'Johnson', 'bob.johnson@company.com', '555-0105', '1988-11-08', '2019-06-01', 3, 3, 'Active'),
(7, 'Alice', 'Brown', 'alice.brown@company.com', '555-0106', '1992-04-12', '2022-02-20', 4, 3, 'Active'),
(8, 'Charlie', 'Wilson', 'charlie.wilson@company.com', '555-0107', '1987-09-30', '2020-11-05', 5, 3, 'Active');

-- Insert default users
INSERT IGNORE INTO `Users` (`UserID`, `EmployeeID`, `Username`, `PasswordHash`, `RoleID`, `IsActive`, `IsTwoFactorEnabled`) VALUES
(1, 1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, 0),
(2, 2, 'hrmanager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 1, 0),
(3, 3, 'hrstaff', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 1, 0);

-- Insert default shifts
INSERT IGNORE INTO `shifts` (`ShiftID`, `ShiftName`, `StartTime`, `EndTime`, `BreakDuration`) VALUES
(1, 'Day Shift', '08:00:00', '17:00:00', 60),
(2, 'Night Shift', '22:00:00', '07:00:00', 60),
(3, 'Evening Shift', '16:00:00', '01:00:00', 60);

-- Insert default HMO providers
INSERT IGNORE INTO `HMOProviders` (`ProviderID`, `ProviderName`, `ContactInfo`) VALUES
(1, 'HealthCare Plus', 'contact@healthcareplus.com'),
(2, 'MediCare Solutions', 'info@medicare-solutions.com'),
(3, 'Wellness Partners', 'support@wellnesspartners.com');

-- Insert default benefits plans
INSERT IGNORE INTO `BenefitsPlans` (`BenefitID`, `ProviderID`, `PlanName`, `CoverageDetails`, `MonthlyPremium`) VALUES
(1, 1, 'Basic Health Plan', 'Basic medical coverage', 150.00),
(2, 1, 'Premium Health Plan', 'Comprehensive medical coverage', 250.00),
(3, 2, 'Family Health Plan', 'Family medical coverage', 400.00);

-- Insert sample payroll runs
INSERT IGNORE INTO `payrollruns` (`PayrollRunID`, `RunName`, `PayPeriodStart`, `PayPeriodEnd`, `Status`, `TotalEmployees`, `TotalGrossPay`, `TotalDeductions`, `TotalNetPay`, `ProcessedDate`) VALUES
(1, 'January 2024 - First Half', '2024-01-01', '2024-01-15', 'Processed', 8, 45000.00, 9000.00, 36000.00, '2024-01-16 10:30:00'),
(2, 'January 2024 - Second Half', '2024-01-16', '2024-01-31', 'Processed', 8, 45000.00, 9000.00, 36000.00, '2024-02-01 10:30:00'),
(3, 'February 2024 - First Half', '2024-02-01', '2024-02-15', 'Processed', 8, 45000.00, 9000.00, 36000.00, '2024-02-16 10:30:00'),
(4, 'February 2024 - Second Half', '2024-02-16', '2024-02-29', 'Draft', 8, 45000.00, 9000.00, 36000.00, NULL);

-- Insert sample documents
INSERT IGNORE INTO `documents` (`DocumentID`, `EmployeeID`, `DocumentName`, `DocumentType`, `FilePath`, `Description`) VALUES
(1, 1, 'Employment Contract - System Admin.pdf', 'Contract', '/documents/contracts/emp_001.pdf', 'Initial employment contract'),
(2, 2, 'Employment Contract - HR Manager.pdf', 'Contract', '/documents/contracts/emp_002.pdf', 'Initial employment contract'),
(3, 3, 'Employment Contract - HR Staff.pdf', 'Contract', '/documents/contracts/emp_003.pdf', 'Initial employment contract'),
(4, 4, 'Employment Contract - John Doe.pdf', 'Contract', '/documents/contracts/emp_004.pdf', 'Initial employment contract'),
(5, 5, 'Employment Contract - Jane Smith.pdf', 'Contract', '/documents/contracts/emp_005.pdf', 'Initial employment contract');

-- Insert sample notifications
INSERT IGNORE INTO `notifications` (`NotificationID`, `UserID`, `Title`, `Message`, `Type`, `IsRead`) VALUES
(1, 1, 'Welcome to HR System', 'Welcome to the HR Management System!', 'info', 0),
(2, 1, 'System Update', 'The system has been updated with new features.', 'info', 0),
(3, 2, 'Payroll Processing', 'Payroll for February 2024 is ready for review.', 'warning', 0),
(4, 3, 'Document Upload', 'New document uploaded for review.', 'success', 0),
(5, 1, 'Security Alert', 'Please update your password for security.', 'error', 0);

-- Insert sample employee salaries
INSERT IGNORE INTO `employeesalaries` (`SalaryID`, `EmployeeID`, `SalaryAmount`, `EffectiveDate`) VALUES
(1, 1, 80000.00, '2020-01-01'),
(2, 2, 65000.00, '2020-02-01'),
(3, 3, 45000.00, '2021-03-01'),
(4, 4, 50000.00, '2020-01-15'),
(5, 5, 48000.00, '2021-03-10'),
(6, 6, 52000.00, '2019-06-01'),
(7, 7, 46000.00, '2022-02-20'),
(8, 8, 49000.00, '2020-11-05');

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

