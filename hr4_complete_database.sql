-- Complete HR4 Database Schema
-- This script creates all tables required by the HR4 system without modifying any code

-- Create database and user (optional)
CREATE DATABASE IF NOT EXISTS hr4 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hr4;

-- Roles
CREATE TABLE IF NOT EXISTS Roles (
  RoleID INT AUTO_INCREMENT PRIMARY KEY,
  RoleName VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT INTO Roles (RoleName) VALUES
  ('System Admin'), ('HR Admin'), ('Manager'), ('Employee')
ON DUPLICATE KEY UPDATE RoleName = VALUES(RoleName);

-- Organizational structure (departments) - code uses both 'OrganizationalStructure' and 'organizationalstructure'
CREATE TABLE IF NOT EXISTS OrganizationalStructure (
  DepartmentID INT AUTO_INCREMENT PRIMARY KEY,
  DepartmentName VARCHAR(150) NOT NULL,
  ParentDepartmentID INT NULL,
  INDEX (ParentDepartmentID),
  CONSTRAINT fk_org_parent FOREIGN KEY (ParentDepartmentID) REFERENCES OrganizationalStructure(DepartmentID) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Create lowercase alias for code compatibility
CREATE TABLE IF NOT EXISTS organizationalstructure (
  DepartmentID INT AUTO_INCREMENT PRIMARY KEY,
  DepartmentName VARCHAR(150) NOT NULL,
  ParentDepartmentID INT NULL,
  INDEX (ParentDepartmentID),
  CONSTRAINT fk_org_parent_lower FOREIGN KEY (ParentDepartmentID) REFERENCES organizationalstructure(DepartmentID) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Employees
CREATE TABLE IF NOT EXISTS Employees (
  EmployeeID INT AUTO_INCREMENT PRIMARY KEY,
  FirstName VARCHAR(100) NOT NULL,
  MiddleName VARCHAR(100) NULL,
  LastName VARCHAR(100) NOT NULL,
  Suffix VARCHAR(20) NULL,
  Email VARCHAR(190) NULL,
  PersonalEmail VARCHAR(190) NULL,
  PhoneNumber VARCHAR(50) NULL,
  DateOfBirth DATE NULL,
  Gender VARCHAR(20) NULL,
  MaritalStatus VARCHAR(50) NULL,
  Nationality VARCHAR(100) NULL,
  AddressLine1 VARCHAR(200) NULL,
  AddressLine2 VARCHAR(200) NULL,
  City VARCHAR(100) NULL,
  StateProvince VARCHAR(100) NULL,
  PostalCode VARCHAR(30) NULL,
  Country VARCHAR(100) NULL,
  EmergencyContactName VARCHAR(150) NULL,
  EmergencyContactRelationship VARCHAR(100) NULL,
  EmergencyContactPhone VARCHAR(50) NULL,
  HireDate DATE NULL,
  JobTitle VARCHAR(150) NULL,
  DepartmentID INT NULL,
  ManagerID INT NULL,
  IsActive BOOLEAN NOT NULL DEFAULT TRUE,
  TerminationDate DATE NULL,
  TerminationReason VARCHAR(255) NULL,
  EmployeePhotoPath VARCHAR(255) NULL,
  INDEX (DepartmentID),
  INDEX (ManagerID),
  UNIQUE KEY uq_employees_email (Email),
  UNIQUE KEY uq_employees_personal_email (PersonalEmail),
  CONSTRAINT fk_emp_dept FOREIGN KEY (DepartmentID) REFERENCES OrganizationalStructure(DepartmentID) ON DELETE SET NULL,
  CONSTRAINT fk_emp_manager FOREIGN KEY (ManagerID) REFERENCES Employees(EmployeeID) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Users
CREATE TABLE IF NOT EXISTS Users (
  UserID INT AUTO_INCREMENT PRIMARY KEY,
  EmployeeID INT NOT NULL,
  Username VARCHAR(100) NOT NULL UNIQUE,
  PasswordHash VARCHAR(255) NOT NULL,
  RoleID INT NOT NULL,
  IsActive BOOLEAN NOT NULL DEFAULT TRUE,
  IsTwoFactorEnabled BOOLEAN NOT NULL DEFAULT FALSE,
  TwoFactorEmailCode VARCHAR(20) NULL,
  TwoFactorCodeExpiry DATETIME NULL,
  INDEX (EmployeeID),
  INDEX (RoleID),
  CONSTRAINT fk_users_employee FOREIGN KEY (EmployeeID) REFERENCES Employees(EmployeeID) ON DELETE CASCADE,
  CONSTRAINT fk_users_role FOREIGN KEY (RoleID) REFERENCES Roles(RoleID)
) ENGINE=InnoDB;

-- Claim Types
CREATE TABLE IF NOT EXISTS ClaimTypes (
  ClaimTypeID INT AUTO_INCREMENT PRIMARY KEY,
  TypeName VARCHAR(150) NOT NULL UNIQUE,
  Description VARCHAR(255) NULL,
  RequiresReceipt BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- Claims
CREATE TABLE IF NOT EXISTS Claims (
  ClaimID INT AUTO_INCREMENT PRIMARY KEY,
  EmployeeID INT NOT NULL,
  ClaimTypeID INT NOT NULL,
  SubmissionDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ClaimDate DATE NULL,
  Amount DECIMAL(12,2) NOT NULL,
  Currency VARCHAR(10) NOT NULL DEFAULT 'PHP',
  Description TEXT NULL,
  ReceiptPath VARCHAR(255) NULL,
  Status VARCHAR(50) NOT NULL DEFAULT 'Submitted',
  PayrollID INT NULL,
  INDEX (EmployeeID),
  INDEX (ClaimTypeID),
  INDEX (PayrollID),
  INDEX idx_claims_status (Status),
  CONSTRAINT fk_claims_employee FOREIGN KEY (EmployeeID) REFERENCES Employees(EmployeeID) ON DELETE CASCADE,
  CONSTRAINT fk_claims_type FOREIGN KEY (ClaimTypeID) REFERENCES ClaimTypes(ClaimTypeID)
) ENGINE=InnoDB;

-- Claim Approvals (missing from original schema)
CREATE TABLE IF NOT EXISTS ClaimApprovals (
  ApprovalID INT AUTO_INCREMENT PRIMARY KEY,
  ClaimID INT NOT NULL,
  ApproverID INT NOT NULL,
  ApprovalDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  Status VARCHAR(50) NOT NULL, -- 'Approved', 'Rejected'
  Comments TEXT NULL,
  INDEX (ClaimID),
  INDEX (ApproverID),
  CONSTRAINT fk_claim_approvals_claim FOREIGN KEY (ClaimID) REFERENCES Claims(ClaimID) ON DELETE CASCADE,
  CONSTRAINT fk_claim_approvals_approver FOREIGN KEY (ApproverID) REFERENCES Employees(EmployeeID) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Leave Types
CREATE TABLE IF NOT EXISTS LeaveTypes (
  LeaveTypeID INT AUTO_INCREMENT PRIMARY KEY,
  TypeName VARCHAR(150) NOT NULL UNIQUE,
  Description VARCHAR(255) NULL,
  RequiresApproval BOOLEAN NOT NULL DEFAULT TRUE,
  AccrualRate DECIMAL(8,2) NULL,
  MaxCarryForwardDays DECIMAL(8,2) NULL,
  IsActive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- Leave Requests
CREATE TABLE IF NOT EXISTS LeaveRequests (
  RequestID INT AUTO_INCREMENT PRIMARY KEY,
  EmployeeID INT NOT NULL,
  LeaveTypeID INT NOT NULL,
  StartDate DATE NOT NULL,
  EndDate DATE NOT NULL,
  NumberOfDays DECIMAL(6,2) NOT NULL,
  Reason VARCHAR(255) NULL,
  Status VARCHAR(50) NOT NULL DEFAULT 'Pending',
  RequestDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ApproverID INT NULL,
  INDEX (EmployeeID),
  INDEX (LeaveTypeID),
  INDEX (ApproverID),
  INDEX idx_leaverequests_status (Status),
  CONSTRAINT fk_lr_employee FOREIGN KEY (EmployeeID) REFERENCES Employees(EmployeeID) ON DELETE CASCADE,
  CONSTRAINT fk_lr_type FOREIGN KEY (LeaveTypeID) REFERENCES LeaveTypes(LeaveTypeID),
  CONSTRAINT fk_lr_approver FOREIGN KEY (ApproverID) REFERENCES Employees(EmployeeID) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Leave Balances
CREATE TABLE IF NOT EXISTS LeaveBalances (
  LeaveBalanceID INT AUTO_INCREMENT PRIMARY KEY,
  EmployeeID INT NOT NULL,
  LeaveTypeID INT NOT NULL,
  BalanceYear INT NOT NULL,
  AvailableDays DECIMAL(6,2) NOT NULL DEFAULT 0,
  UNIQUE KEY uq_lb_emp_type_year (EmployeeID, LeaveTypeID, BalanceYear),
  CONSTRAINT fk_lb_employee FOREIGN KEY (EmployeeID) REFERENCES Employees(EmployeeID) ON DELETE CASCADE,
  CONSTRAINT fk_lb_type FOREIGN KEY (LeaveTypeID) REFERENCES LeaveTypes(LeaveTypeID)
) ENGINE=InnoDB;

-- Payroll Runs
CREATE TABLE IF NOT EXISTS PayrollRuns (
  PayrollID INT AUTO_INCREMENT PRIMARY KEY,
  PayPeriodStartDate DATE NOT NULL,
  PayPeriodEndDate DATE NOT NULL,
  PaymentDate DATE NOT NULL,
  Status VARCHAR(50) NOT NULL DEFAULT 'Pending',
  ProcessedDate DATETIME NULL,
  INDEX idx_payrollruns_paymentdate (PaymentDate),
  INDEX idx_payrollruns_status (Status)
) ENGINE=InnoDB;

-- Payslips
CREATE TABLE IF NOT EXISTS Payslips (
  PayslipID INT AUTO_INCREMENT PRIMARY KEY,
  PayrollID INT NOT NULL,
  EmployeeID INT NOT NULL,
  PayPeriodStartDate DATE NOT NULL,
  PayPeriodEndDate DATE NOT NULL,
  PaymentDate DATE NOT NULL,
  BasicSalary DECIMAL(12,2) NOT NULL DEFAULT 0,
  HourlyRate DECIMAL(12,2) NULL,
  HoursWorked DECIMAL(10,2) NOT NULL DEFAULT 0,
  OvertimeHours DECIMAL(10,2) NOT NULL DEFAULT 0,
  RegularPay DECIMAL(12,2) NOT NULL DEFAULT 0,
  OvertimePay DECIMAL(12,2) NOT NULL DEFAULT 0,
  HolidayPay DECIMAL(12,2) NOT NULL DEFAULT 0,
  NightDifferentialPay DECIMAL(12,2) NOT NULL DEFAULT 0,
  BonusesTotal DECIMAL(12,2) NOT NULL DEFAULT 0,
  OtherEarnings DECIMAL(12,2) NOT NULL DEFAULT 0,
  GrossIncome DECIMAL(12,2) NOT NULL DEFAULT 0,
  SSS_Contribution DECIMAL(12,2) NOT NULL DEFAULT 0,
  PhilHealth_Contribution DECIMAL(12,2) NOT NULL DEFAULT 0,
  PagIBIG_Contribution DECIMAL(12,2) NOT NULL DEFAULT 0,
  WithholdingTax DECIMAL(12,2) NOT NULL DEFAULT 0,
  OtherDeductionsTotal DECIMAL(12,2) NOT NULL DEFAULT 0,
  TotalDeductions DECIMAL(12,2) NOT NULL DEFAULT 0,
  NetIncome DECIMAL(12,2) NOT NULL DEFAULT 0,
  INDEX (PayrollID),
  INDEX (EmployeeID),
  CONSTRAINT fk_payslips_payroll FOREIGN KEY (PayrollID) REFERENCES PayrollRuns(PayrollID) ON DELETE CASCADE,
  CONSTRAINT fk_payslips_employee FOREIGN KEY (EmployeeID) REFERENCES Employees(EmployeeID) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Employee Salaries
CREATE TABLE IF NOT EXISTS EmployeeSalaries (
  SalaryID INT AUTO_INCREMENT PRIMARY KEY,
  EmployeeID INT NOT NULL,
  BaseSalary DECIMAL(12,2) NOT NULL,
  PayFrequency VARCHAR(30) NOT NULL, -- 'Monthly', 'Bi-Weekly', 'Hourly'
  PayRate DECIMAL(12,2) NULL,
  EffectiveDate DATE NOT NULL,
  EndDate DATE NULL,
  IsCurrent BOOLEAN NOT NULL DEFAULT TRUE,
  INDEX (EmployeeID),
  INDEX idx_empsal_iscurrent (IsCurrent),
  CONSTRAINT fk_empsal_employee FOREIGN KEY (EmployeeID) REFERENCES Employees(EmployeeID) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Bonuses
CREATE TABLE IF NOT EXISTS Bonuses (
  BonusID INT AUTO_INCREMENT PRIMARY KEY,
  EmployeeID INT NOT NULL,
  PayrollID INT NULL,
  BonusAmount DECIMAL(12,2) NOT NULL,
  BonusType VARCHAR(100) NULL,
  AwardDate DATE NOT NULL,
  PaymentDate DATE NULL,
  INDEX (EmployeeID),
  INDEX (PayrollID),
  CONSTRAINT fk_bonus_employee FOREIGN KEY (EmployeeID) REFERENCES Employees(EmployeeID) ON DELETE CASCADE,
  CONSTRAINT fk_bonus_payroll FOREIGN KEY (PayrollID) REFERENCES PayrollRuns(PayrollID) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Deductions
CREATE TABLE IF NOT EXISTS Deductions (
  DeductionID INT AUTO_INCREMENT PRIMARY KEY,
  EmployeeID INT NOT NULL,
  PayrollID INT NOT NULL,
  DeductionType VARCHAR(100) NOT NULL,
  DeductionAmount DECIMAL(12,2) NOT NULL,
  Provider VARCHAR(150) NULL,
  INDEX (EmployeeID),
  INDEX (PayrollID),
  CONSTRAINT fk_deductions_employee FOREIGN KEY (EmployeeID) REFERENCES Employees(EmployeeID) ON DELETE CASCADE,
  CONSTRAINT fk_deductions_payroll FOREIGN KEY (PayrollID) REFERENCES PayrollRuns(PayrollID) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Timesheets
CREATE TABLE IF NOT EXISTS Timesheets (
  TimesheetID INT AUTO_INCREMENT PRIMARY KEY,
  EmployeeID INT NOT NULL,
  ScheduleID INT NULL,
  PeriodStartDate DATE NOT NULL,
  PeriodEndDate DATE NOT NULL,
  TotalHoursWorked DECIMAL(10,2) NOT NULL DEFAULT 0,
  OvertimeHours DECIMAL(10,2) NOT NULL DEFAULT 0,
  Status VARCHAR(50) NOT NULL DEFAULT 'Pending',
  INDEX (EmployeeID),
  INDEX (ScheduleID),
  INDEX idx_timesheets_status (Status),
  CONSTRAINT fk_timesheet_employee FOREIGN KEY (EmployeeID) REFERENCES Employees(EmployeeID) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Shifts
CREATE TABLE IF NOT EXISTS Shifts (
  ShiftID INT AUTO_INCREMENT PRIMARY KEY,
  ShiftName VARCHAR(100) NOT NULL,
  StartTime TIME NOT NULL,
  EndTime TIME NOT NULL,
  BreakDurationMinutes INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- Schedules
CREATE TABLE IF NOT EXISTS Schedules (
  ScheduleID INT AUTO_INCREMENT PRIMARY KEY,
  EmployeeID INT NOT NULL,
  ShiftID INT NULL,
  StartDate DATE NOT NULL,
  EndDate DATE NULL,
  Workdays VARCHAR(50) NULL, -- e.g., "Mon,Tue,Wed,Thu,Fri"
  INDEX (EmployeeID),
  INDEX (ShiftID),
  CONSTRAINT fk_schedule_employee FOREIGN KEY (EmployeeID) REFERENCES Employees(EmployeeID) ON DELETE CASCADE,
  CONSTRAINT fk_schedule_shift FOREIGN KEY (ShiftID) REFERENCES Shifts(ShiftID) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Attendance
CREATE TABLE IF NOT EXISTS AttendanceRecords (
  RecordID INT AUTO_INCREMENT PRIMARY KEY,
  EmployeeID INT NOT NULL,
  AttendanceDate DATE NOT NULL,
  ClockInTime TIME NULL,
  ClockOutTime TIME NULL,
  Status VARCHAR(50) NULL, -- 'Present','Absent','Late'...
  Notes VARCHAR(255) NULL,
  INDEX (EmployeeID, AttendanceDate),
  CONSTRAINT fk_attendance_employee FOREIGN KEY (EmployeeID) REFERENCES Employees(EmployeeID) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Compensation Plans
CREATE TABLE IF NOT EXISTS CompensationPlans (
  PlanID INT AUTO_INCREMENT PRIMARY KEY,
  PlanName VARCHAR(150) NOT NULL,
  Description VARCHAR(255) NULL,
  EffectiveDate DATE NOT NULL,
  EndDate DATE NULL,
  PlanType VARCHAR(100) NULL
) ENGINE=InnoDB;

-- Incentives
CREATE TABLE IF NOT EXISTS Incentives (
  IncentiveID INT AUTO_INCREMENT PRIMARY KEY,
  EmployeeID INT NOT NULL,
  PlanID INT NULL,
  IncentiveType VARCHAR(100) NULL,
  Amount DECIMAL(12,2) NOT NULL,
  AwardDate DATE NOT NULL,
  PayoutDate DATE NULL,
  PayrollID INT NULL,
  INDEX (EmployeeID),
  INDEX (PlanID),
  INDEX (PayrollID),
  CONSTRAINT fk_incentive_employee FOREIGN KEY (EmployeeID) REFERENCES Employees(EmployeeID) ON DELETE CASCADE,
  CONSTRAINT fk_incentive_plan FOREIGN KEY (PlanID) REFERENCES CompensationPlans(PlanID) ON DELETE SET NULL,
  CONSTRAINT fk_incentive_payroll FOREIGN KEY (PayrollID) REFERENCES PayrollRuns(PayrollID) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Salary Adjustments
CREATE TABLE IF NOT EXISTS SalaryAdjustments (
  AdjustmentID INT AUTO_INCREMENT PRIMARY KEY,
  EmployeeID INT NOT NULL,
  PreviousSalaryID INT NULL,
  NewSalaryID INT NULL,
  AdjustmentDate DATE NOT NULL,
  Reason VARCHAR(255) NULL,
  ApprovedBy INT NULL,
  ApprovalDate DATE NULL,
  PercentageIncrease DECIMAL(6,3) NULL,
  INDEX (EmployeeID),
  INDEX (PreviousSalaryID),
  INDEX (NewSalaryID),
  CONSTRAINT fk_saladj_employee FOREIGN KEY (EmployeeID) REFERENCES Employees(EmployeeID) ON DELETE CASCADE,
  CONSTRAINT fk_saladj_prev FOREIGN KEY (PreviousSalaryID) REFERENCES EmployeeSalaries(SalaryID) ON DELETE SET NULL,
  CONSTRAINT fk_saladj_new FOREIGN KEY (NewSalaryID) REFERENCES EmployeeSalaries(SalaryID) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Notifications
CREATE TABLE IF NOT EXISTS Notifications (
  NotificationID INT AUTO_INCREMENT PRIMARY KEY,
  UserID INT NOT NULL,
  SenderUserID INT NULL,
  NotificationType VARCHAR(100) NULL,
  Message VARCHAR(255) NOT NULL,
  Link VARCHAR(255) NULL,
  IsRead BOOLEAN NOT NULL DEFAULT FALSE,
  CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (UserID),
  INDEX (SenderUserID),
  INDEX idx_notifications_isread (IsRead),
  CONSTRAINT fk_notif_user FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE CASCADE,
  CONSTRAINT fk_notif_sender FOREIGN KEY (SenderUserID) REFERENCES Users(UserID) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Employee Documents (code queries both 'EmployeeDocuments' and 'employeedocuments')
CREATE TABLE IF NOT EXISTS EmployeeDocuments (
  DocumentID INT AUTO_INCREMENT PRIMARY KEY,
  EmployeeID INT NOT NULL,
  DocumentType VARCHAR(100) NULL,
  DocumentName VARCHAR(200) NOT NULL,
  FilePath VARCHAR(255) NOT NULL,
  UploadedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (EmployeeID),
  CONSTRAINT fk_docs_employee FOREIGN KEY (EmployeeID) REFERENCES Employees(EmployeeID) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Create lowercase alias for code compatibility
CREATE TABLE IF NOT EXISTS employeedocuments (
  DocumentID INT AUTO_INCREMENT PRIMARY KEY,
  EmployeeID INT NOT NULL,
  DocumentType VARCHAR(100) NULL,
  DocumentName VARCHAR(200) NOT NULL,
  FilePath VARCHAR(255) NOT NULL,
  UploadedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (EmployeeID),
  CONSTRAINT fk_docs_employee_lower FOREIGN KEY (EmployeeID) REFERENCES Employees(EmployeeID) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Add foreign key constraint for Claims -> PayrollRuns (was missing)
ALTER TABLE Claims ADD CONSTRAINT fk_claims_payroll FOREIGN KEY (PayrollID) REFERENCES PayrollRuns(PayrollID) ON DELETE SET NULL;

-- Indexes used by filters and joins commonly
CREATE INDEX IF NOT EXISTS idx_users_active ON Users (IsActive);
CREATE INDEX IF NOT EXISTS idx_employees_active ON Employees (IsActive);
CREATE INDEX IF NOT EXISTS idx_claims_employee_status ON Claims (EmployeeID, Status);
CREATE INDEX IF NOT EXISTS idx_leave_requests_employee_status ON LeaveRequests (EmployeeID, Status);

-- Sample data for testing
INSERT INTO OrganizationalStructure (DepartmentName, ParentDepartmentID) VALUES
  ('Human Resources', NULL),
  ('Information Technology', NULL),
  ('Finance', NULL),
  ('Operations', NULL),
  ('Recruitment', 1),
  ('Payroll', 1),
  ('Software Development', 2),
  ('IT Support', 2),
  ('Accounting', 3),
  ('Audit', 3)
ON DUPLICATE KEY UPDATE DepartmentName = VALUES(DepartmentName);

INSERT INTO organizationalstructure (DepartmentName, ParentDepartmentID) VALUES
  ('Human Resources', NULL),
  ('Information Technology', NULL),
  ('Finance', NULL),
  ('Operations', NULL),
  ('Recruitment', 1),
  ('Payroll', 1),
  ('Software Development', 2),
  ('IT Support', 2),
  ('Accounting', 3),
  ('Audit', 3)
ON DUPLICATE KEY UPDATE DepartmentName = VALUES(DepartmentName);

INSERT INTO ClaimTypes (TypeName, Description, RequiresReceipt) VALUES
  ('Travel Expenses', 'Business travel related expenses', TRUE),
  ('Meal Allowance', 'Business meal expenses', TRUE),
  ('Office Supplies', 'Office equipment and supplies', TRUE),
  ('Training Costs', 'Professional development and training', TRUE),
  ('Medical Reimbursement', 'Medical expenses not covered by insurance', TRUE)
ON DUPLICATE KEY UPDATE TypeName = VALUES(TypeName);

INSERT INTO LeaveTypes (TypeName, Description, RequiresApproval, AccrualRate, MaxCarryForwardDays) VALUES
  ('Annual Leave', 'Regular vacation leave', TRUE, 1.25, 5.0),
  ('Sick Leave', 'Medical and health related leave', TRUE, 1.0, 10.0),
  ('Personal Leave', 'Personal time off', TRUE, 0.5, 2.0),
  ('Maternity Leave', 'Maternity and childbirth leave', TRUE, 0.0, 0.0),
  ('Paternity Leave', 'Paternity leave for new fathers', TRUE, 0.0, 0.0)
ON DUPLICATE KEY UPDATE TypeName = VALUES(TypeName);

INSERT INTO Shifts (ShiftName, StartTime, EndTime, BreakDurationMinutes) VALUES
  ('Day Shift', '08:00:00', '17:00:00', 60),
  ('Night Shift', '22:00:00', '07:00:00', 60),
  ('Morning Shift', '06:00:00', '15:00:00', 45),
  ('Evening Shift', '14:00:00', '23:00:00', 45),
  ('Flexible Hours', '09:00:00', '18:00:00', 60)
ON DUPLICATE KEY UPDATE ShiftName = VALUES(ShiftName);

-- Sample admin user (password: admin123)
INSERT INTO Employees (FirstName, LastName, Email, HireDate, JobTitle, DepartmentID, IsActive) VALUES
  ('System', 'Admin', 'admin@hr4.com', CURDATE(), 'System Administrator', 1, TRUE)
ON DUPLICATE KEY UPDATE Email = VALUES(Email);

INSERT INTO Users (EmployeeID, Username, PasswordHash, RoleID, IsActive, IsTwoFactorEnabled) VALUES
  (1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, TRUE, FALSE)
ON DUPLICATE KEY UPDATE Username = VALUES(Username);

-- Sample regular employee
INSERT INTO Employees (FirstName, LastName, Email, HireDate, JobTitle, DepartmentID, IsActive) VALUES
  ('John', 'Doe', 'john.doe@hr4.com', CURDATE(), 'Software Developer', 7, TRUE)
ON DUPLICATE KEY UPDATE Email = VALUES(Email);

INSERT INTO Users (EmployeeID, Username, PasswordHash, RoleID, IsActive, IsTwoFactorEnabled) VALUES
  (2, 'john.doe', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, TRUE, FALSE)
ON DUPLICATE KEY UPDATE Username = VALUES(Username);

-- Sample salary for employees
INSERT INTO EmployeeSalaries (EmployeeID, BaseSalary, PayFrequency, PayRate, EffectiveDate, IsCurrent) VALUES
  (1, 80000.00, 'Monthly', NULL, CURDATE(), TRUE),
  (2, 60000.00, 'Monthly', NULL, CURDATE(), TRUE)
ON DUPLICATE KEY UPDATE BaseSalary = VALUES(BaseSalary);

-- Sample compensation plan
INSERT INTO CompensationPlans (PlanName, Description, EffectiveDate, PlanType) VALUES
  ('Standard Compensation', 'Standard employee compensation package', CURDATE(), 'Base Salary'),
  ('Performance Bonus', 'Performance-based bonus program', CURDATE(), 'Variable Pay')
ON DUPLICATE KEY UPDATE PlanName = VALUES(PlanName);

COMMIT;
