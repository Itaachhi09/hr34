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

--
-- Database: `hr_integrated_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendancerecords`
--

CREATE TABLE `attendancerecords` (
  `RecordID` int(11) NOT NULL,
  `EmployeeID` int(11) NOT NULL,
  `Date` date NOT NULL,
  `CheckInTime` time NOT NULL,
  `CheckOutTime` time DEFAULT NULL,
  `Status` enum('Present','Absent','Late','On Leave') DEFAULT 'Present'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bonuses`
--

CREATE TABLE `bonuses` (
  `BonusID` int(11) NOT NULL,
  `EmployeeID` int(11) NOT NULL,
  `BonusAmount` decimal(10,2) NOT NULL,
  `BonusDate` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `claimapprovals`
--

CREATE TABLE `claimapprovals` (
  `ApprovalID` int(11) NOT NULL,
  `ClaimID` int(11) NOT NULL,
  `ApprovedBy` varchar(100) NOT NULL,
  `ApprovalDate` datetime DEFAULT NULL,
  `Comments` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `claims`
--

CREATE TABLE `claims` (
  `ClaimID` int(11) NOT NULL,
  `EmployeeID` int(11) NOT NULL,
  `ClaimTypeID` int(11) NOT NULL,
  `Amount` decimal(10,2) NOT NULL,
  `SubmissionDate` datetime DEFAULT current_timestamp(),
  `Status` enum('Pending','Approved','Rejected') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `claimtypes`
--

CREATE TABLE `claimtypes` (
  `ClaimTypeID` int(11) NOT NULL,
  `TypeName` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `compensationplans`
--

CREATE TABLE `compensationplans` (
  `PlanID` int(11) NOT NULL,
  `PlanName` varchar(255) NOT NULL,
  `Description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dashboards`
--

CREATE TABLE `dashboards` (
  `DashboardID` int(11) NOT NULL,
  `DashboardName` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deductions`
--

CREATE TABLE `deductions` (
  `DeductionID` int(11) NOT NULL,
  `PayrollID` int(11) NOT NULL,
  `Description` varchar(255) NOT NULL,
  `Amount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `DepartmentID` int(11) NOT NULL,
  `DepartmentName` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employeedocuments`
--

CREATE TABLE `employeedocuments` (
  `DocumentID` int(11) NOT NULL,
  `EmployeeID` int(11) NOT NULL,
  `DocumentName` varchar(255) NOT NULL,
  `DocumentType` varchar(100) NOT NULL,
  `UploadDate` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `EmployeeID` int(11) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Phone` varchar(20) NOT NULL,
  `DateOfBirth` date NOT NULL,
  `HireDate` date NOT NULL,
  `DepartmentID` int(11) NOT NULL,
  `JobRoleID` int(11) NOT NULL,
  `Status` enum('Active','Inactive','Terminated') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employeesalaries`
--

CREATE TABLE `employeesalaries` (
  `SalaryID` int(11) NOT NULL,
  `EmployeeID` int(11) NOT NULL,
  `SalaryAmount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hrreports`
--

CREATE TABLE `hrreports` (
  `ReportID` int(11) NOT NULL,
  `ReportName` varchar(255) NOT NULL,
  `GeneratedDate` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `incentives`
--

CREATE TABLE `incentives` (
  `IncentiveID` int(11) NOT NULL,
  `EmployeeID` int(11) NOT NULL,
  `IncentiveAmount` decimal(10,2) NOT NULL,
  `Reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobroles`
--

CREATE TABLE `jobroles` (
  `JobRoleID` int(11) NOT NULL,
  `RoleName` varchar(100) NOT NULL,
  `RoleDescription` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leavebalances`
--

CREATE TABLE `leavebalances` (
  `BalanceID` int(11) NOT NULL,
  `EmployeeID` int(11) NOT NULL,
  `LeaveTypeID` int(11) NOT NULL,
  `RemainingDays` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leaverequests`
--

CREATE TABLE `leaverequests` (
  `RequestID` int(11) NOT NULL,
  `EmployeeID` int(11) NOT NULL,
  `LeaveTypeID` int(11) NOT NULL,
  `StartDate` date NOT NULL,
  `EndDate` date NOT NULL,
  `Status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `RequestDate` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leavetypes`
--

CREATE TABLE `leavetypes` (
  `LeaveTypeID` int(11) NOT NULL,
  `LeaveName` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  `MaxDaysAllowed` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `metrics`
--

CREATE TABLE `metrics` (
  `MetricID` int(11) NOT NULL,
  `ReportID` int(11) NOT NULL,
  `MetricName` varchar(255) NOT NULL,
  `Value` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `organizationalstructure`
--

CREATE TABLE `organizationalstructure` (
  `StructureID` int(11) NOT NULL,
  `StructureName` varchar(255) NOT NULL,
  `ParentStructureID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payrollruns`
--

CREATE TABLE `payrollruns` (
  `PayrollID` int(11) NOT NULL,
  `PayPeriod` varchar(50) NOT NULL,
  `RunDate` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `salaryadjustments`
--

CREATE TABLE `salaryadjustments` (
  `AdjustmentID` int(11) NOT NULL,
  `EmployeeID` int(11) NOT NULL,
  `AdjustmentAmount` decimal(10,2) NOT NULL,
  `EffectiveDate` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `ScheduleID` int(11) NOT NULL,
  `EmployeeID` int(11) NOT NULL,
  `ShiftID` int(11) NOT NULL,
  `ScheduleDate` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shifts`
--

CREATE TABLE `shifts` (
  `ShiftID` int(11) NOT NULL,
  `ShiftName` varchar(100) NOT NULL,
  `StartTime` time NOT NULL,
  `EndTime` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timesheets`
--

CREATE TABLE `timesheets` (
  `TimesheetID` int(11) NOT NULL,
  `EmployeeID` int(11) NOT NULL,
  `ScheduleID` int(11) NOT NULL,
  `HoursWorked` decimal(5,2) NOT NULL,
  `SubmissionDate` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendancerecords`
--
ALTER TABLE `attendancerecords`
  ADD PRIMARY KEY (`RecordID`),
  ADD KEY `EmployeeID` (`EmployeeID`);

--
-- Indexes for table `bonuses`
--
ALTER TABLE `bonuses`
  ADD PRIMARY KEY (`BonusID`),
  ADD KEY `EmployeeID` (`EmployeeID`);

--
-- Indexes for table `claimapprovals`
--
ALTER TABLE `claimapprovals`
  ADD PRIMARY KEY (`ApprovalID`),
  ADD KEY `ClaimID` (`ClaimID`);

--
-- Indexes for table `claims`
--
ALTER TABLE `claims`
  ADD PRIMARY KEY (`ClaimID`),
  ADD KEY `EmployeeID` (`EmployeeID`),
  ADD KEY `ClaimTypeID` (`ClaimTypeID`);

--
-- Indexes for table `claimtypes`
--
ALTER TABLE `claimtypes`
  ADD PRIMARY KEY (`ClaimTypeID`);

--
-- Indexes for table `compensationplans`
--
ALTER TABLE `compensationplans`
  ADD PRIMARY KEY (`PlanID`);

--
-- Indexes for table `dashboards`
--
ALTER TABLE `dashboards`
  ADD PRIMARY KEY (`DashboardID`);

--
-- Indexes for table `deductions`
--
ALTER TABLE `deductions`
  ADD PRIMARY KEY (`DeductionID`),
  ADD KEY `PayrollID` (`PayrollID`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`DepartmentID`);

--
-- Indexes for table `employeedocuments`
--
ALTER TABLE `employeedocuments`
  ADD PRIMARY KEY (`DocumentID`),
  ADD KEY `EmployeeID` (`EmployeeID`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`EmployeeID`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD KEY `DepartmentID` (`DepartmentID`),
  ADD KEY `JobRoleID` (`JobRoleID`);

--
-- Indexes for table `employeesalaries`
--
ALTER TABLE `employeesalaries`
  ADD PRIMARY KEY (`SalaryID`),
  ADD KEY `EmployeeID` (`EmployeeID`);

--
-- Indexes for table `hrreports`
--
ALTER TABLE `hrreports`
  ADD PRIMARY KEY (`ReportID`);

--
-- Indexes for table `incentives`
--
ALTER TABLE `incentives`
  ADD PRIMARY KEY (`IncentiveID`),
  ADD KEY `EmployeeID` (`EmployeeID`);

--
-- Indexes for table `jobroles`
--
ALTER TABLE `jobroles`
  ADD PRIMARY KEY (`JobRoleID`),
  ADD UNIQUE KEY `RoleName` (`RoleName`);

--
-- Indexes for table `leavebalances`
--
ALTER TABLE `leavebalances`
  ADD PRIMARY KEY (`BalanceID`),
  ADD KEY `EmployeeID` (`EmployeeID`),
  ADD KEY `LeaveTypeID` (`LeaveTypeID`);

--
-- Indexes for table `leaverequests`
--
ALTER TABLE `leaverequests`
  ADD PRIMARY KEY (`RequestID`),
  ADD KEY `EmployeeID` (`EmployeeID`),
  ADD KEY `LeaveTypeID` (`LeaveTypeID`);

--
-- Indexes for table `leavetypes`
--
ALTER TABLE `leavetypes`
  ADD PRIMARY KEY (`LeaveTypeID`);

--
-- Indexes for table `metrics`
--
ALTER TABLE `metrics`
  ADD PRIMARY KEY (`MetricID`),
  ADD KEY `ReportID` (`ReportID`);

--
-- Indexes for table `organizationalstructure`
--
ALTER TABLE `organizationalstructure`
  ADD PRIMARY KEY (`StructureID`),
  ADD KEY `ParentStructureID` (`ParentStructureID`);

--
-- Indexes for table `payrollruns`
--
ALTER TABLE `payrollruns`
  ADD PRIMARY KEY (`PayrollID`);

--
-- Indexes for table `salaryadjustments`
--
ALTER TABLE `salaryadjustments`
  ADD PRIMARY KEY (`AdjustmentID`),
  ADD KEY `EmployeeID` (`EmployeeID`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`ScheduleID`),
  ADD KEY `EmployeeID` (`EmployeeID`),
  ADD KEY `ShiftID` (`ShiftID`);

--
-- Indexes for table `shifts`
--
ALTER TABLE `shifts`
  ADD PRIMARY KEY (`ShiftID`);

--
-- Indexes for table `timesheets`
--
ALTER TABLE `timesheets`
  ADD PRIMARY KEY (`TimesheetID`),
  ADD KEY `EmployeeID` (`EmployeeID`),
  ADD KEY `ScheduleID` (`ScheduleID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendancerecords`
--
ALTER TABLE `attendancerecords`
  MODIFY `RecordID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bonuses`
--
ALTER TABLE `bonuses`
  MODIFY `BonusID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `claimapprovals`
--
ALTER TABLE `claimapprovals`
  MODIFY `ApprovalID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `claims`
--
ALTER TABLE `claims`
  MODIFY `ClaimID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `claimtypes`
--
ALTER TABLE `claimtypes`
  MODIFY `ClaimTypeID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `compensationplans`
--
ALTER TABLE `compensationplans`
  MODIFY `PlanID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dashboards`
--
ALTER TABLE `dashboards`
  MODIFY `DashboardID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `deductions`
--
ALTER TABLE `deductions`
  MODIFY `DeductionID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `DepartmentID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employeedocuments`
--
ALTER TABLE `employeedocuments`
  MODIFY `DocumentID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `EmployeeID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employeesalaries`
--
ALTER TABLE `employeesalaries`
  MODIFY `SalaryID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hrreports`
--
ALTER TABLE `hrreports`
  MODIFY `ReportID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `incentives`
--
ALTER TABLE `incentives`
  MODIFY `IncentiveID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobroles`
--
ALTER TABLE `jobroles`
  MODIFY `JobRoleID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leavebalances`
--
ALTER TABLE `leavebalances`
  MODIFY `BalanceID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leaverequests`
--
ALTER TABLE `leaverequests`
  MODIFY `RequestID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leavetypes`
--
ALTER TABLE `leavetypes`
  MODIFY `LeaveTypeID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `metrics`
--
ALTER TABLE `metrics`
  MODIFY `MetricID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `organizationalstructure`
--
ALTER TABLE `organizationalstructure`
  MODIFY `StructureID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payrollruns`
--
ALTER TABLE `payrollruns`
  MODIFY `PayrollID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `salaryadjustments`
--
ALTER TABLE `salaryadjustments`
  MODIFY `AdjustmentID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `ScheduleID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shifts`
--
ALTER TABLE `shifts`
  MODIFY `ShiftID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `timesheets`
--
ALTER TABLE `timesheets`
  MODIFY `TimesheetID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendancerecords`
--
ALTER TABLE `attendancerecords`
  ADD CONSTRAINT `attendancerecords_ibfk_1` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`);

--
-- Constraints for table `bonuses`
--
ALTER TABLE `bonuses`
  ADD CONSTRAINT `bonuses_ibfk_1` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`);

--
-- Constraints for table `claimapprovals`
--
ALTER TABLE `claimapprovals`
  ADD CONSTRAINT `claimapprovals_ibfk_1` FOREIGN KEY (`ClaimID`) REFERENCES `claims` (`ClaimID`);

--
-- Constraints for table `claims`
--
ALTER TABLE `claims`
  ADD CONSTRAINT `claims_ibfk_1` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`),
  ADD CONSTRAINT `claims_ibfk_2` FOREIGN KEY (`ClaimTypeID`) REFERENCES `claimtypes` (`ClaimTypeID`);

--
-- Constraints for table `deductions`
--
ALTER TABLE `deductions`
  ADD CONSTRAINT `deductions_ibfk_1` FOREIGN KEY (`PayrollID`) REFERENCES `payrollruns` (`PayrollID`);

--
-- Constraints for table `employeedocuments`
--
ALTER TABLE `employeedocuments`
  ADD CONSTRAINT `employeedocuments_ibfk_1` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`);

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`DepartmentID`) REFERENCES `departments` (`DepartmentID`),
  ADD CONSTRAINT `employees_ibfk_2` FOREIGN KEY (`JobRoleID`) REFERENCES `jobroles` (`JobRoleID`);

--
-- Constraints for table `employeesalaries`
--
ALTER TABLE `employeesalaries`
  ADD CONSTRAINT `employeesalaries_ibfk_1` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`);

--
-- Constraints for table `incentives`
--
ALTER TABLE `incentives`
  ADD CONSTRAINT `incentives_ibfk_1` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`);

--
-- Constraints for table `leavebalances`
--
ALTER TABLE `leavebalances`
  ADD CONSTRAINT `leavebalances_ibfk_1` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`),
  ADD CONSTRAINT `leavebalances_ibfk_2` FOREIGN KEY (`LeaveTypeID`) REFERENCES `leavetypes` (`LeaveTypeID`);

--
-- Constraints for table `leaverequests`
--
ALTER TABLE `leaverequests`
  ADD CONSTRAINT `leaverequests_ibfk_1` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`),
  ADD CONSTRAINT `leaverequests_ibfk_2` FOREIGN KEY (`LeaveTypeID`) REFERENCES `leavetypes` (`LeaveTypeID`);

--
-- Constraints for table `metrics`
--
ALTER TABLE `metrics`
  ADD CONSTRAINT `metrics_ibfk_1` FOREIGN KEY (`ReportID`) REFERENCES `hrreports` (`ReportID`);

--
-- Constraints for table `organizationalstructure`
--
ALTER TABLE `organizationalstructure`
  ADD CONSTRAINT `organizationalstructure_ibfk_1` FOREIGN KEY (`ParentStructureID`) REFERENCES `organizationalstructure` (`StructureID`);

--
-- Constraints for table `salaryadjustments`
--
ALTER TABLE `salaryadjustments`
  ADD CONSTRAINT `salaryadjustments_ibfk_1` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`);

--
-- Constraints for table `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`),
  ADD CONSTRAINT `schedules_ibfk_2` FOREIGN KEY (`ShiftID`) REFERENCES `shifts` (`ShiftID`);

--
-- Constraints for table `timesheets`
--
ALTER TABLE `timesheets`
  ADD CONSTRAINT `timesheets_ibfk_1` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`),
  ADD CONSTRAINT `timesheets_ibfk_2` FOREIGN KEY (`ScheduleID`) REFERENCES `schedules` (`ScheduleID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
