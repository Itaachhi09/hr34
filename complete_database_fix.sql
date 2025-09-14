-- Complete Database Schema Fix for HR34
-- This script fixes all database issues and adds missing tables/columns

-- First, create the missing authentication tables
CREATE TABLE IF NOT EXISTS Roles (
  RoleID INT PRIMARY KEY AUTO_INCREMENT,
  RoleName VARCHAR(100) NOT NULL UNIQUE,
  Description TEXT DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS Users (
  UserID INT PRIMARY KEY AUTO_INCREMENT,
  EmployeeID INT NULL,
  Username VARCHAR(100) NOT NULL UNIQUE,
  PasswordHash VARCHAR(255) NOT NULL,
  RoleID INT NOT NULL,
  IsActive TINYINT(1) NOT NULL DEFAULT 1,
  IsTwoFactorEnabled TINYINT(1) NOT NULL DEFAULT 0,
  TwoFactorEmailCode VARCHAR(10) NULL,
  TwoFactorCodeExpiry DATETIME NULL,
  CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_roles FOREIGN KEY (RoleID) REFERENCES Roles(RoleID)
);

-- Add missing columns to employees table
ALTER TABLE employees 
ADD COLUMN IF NOT EXISTS MiddleName VARCHAR(50) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS Suffix VARCHAR(10) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS PersonalEmail VARCHAR(100) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS Gender ENUM('Male', 'Female', 'Other') DEFAULT NULL,
ADD COLUMN IF NOT EXISTS MaritalStatus ENUM('Single', 'Married', 'Divorced', 'Widowed') DEFAULT NULL,
ADD COLUMN IF NOT EXISTS Nationality VARCHAR(50) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS AddressLine1 VARCHAR(255) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS AddressLine2 VARCHAR(255) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS City VARCHAR(100) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS StateProvince VARCHAR(100) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS PostalCode VARCHAR(20) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS Country VARCHAR(100) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS EmergencyContactName VARCHAR(100) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS EmergencyContactRelationship VARCHAR(50) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS EmergencyContactPhone VARCHAR(20) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS ManagerID INT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS TerminationDate DATE DEFAULT NULL,
ADD COLUMN IF NOT EXISTS TerminationReason TEXT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS EmployeePhotoPath VARCHAR(500) DEFAULT NULL;

-- Add missing columns to departments table
ALTER TABLE departments 
ADD COLUMN IF NOT EXISTS Description TEXT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS ManagerID INT DEFAULT NULL;

-- Insert default data
INSERT IGNORE INTO Roles (RoleID, RoleName, Description) VALUES
  (1, 'System Admin', 'Full system access and administration'),
  (2, 'HR Admin', 'Human Resources administration'),
  (3, 'Employee', 'Standard employee access'),
  (4, 'Manager', 'Manager level access');

-- Insert default department
INSERT IGNORE INTO departments (DepartmentID, DepartmentName, Description) VALUES
  (1, 'Administration', 'Administrative department');

-- Insert default job role
INSERT IGNORE INTO jobroles (JobRoleID, RoleName, RoleDescription) VALUES
  (1, 'Administrator', 'System Administrator');

-- Insert default employees
INSERT IGNORE INTO employees (EmployeeID, FirstName, LastName, Email, Phone, DateOfBirth, HireDate, DepartmentID, JobRoleID, Status) VALUES
  (1, 'System', 'Administrator', 'admin@company.com', '000-000-0000', '1990-01-01', CURDATE(), 1, 1, 'Active'),
  (2, 'Test', 'User', 'testuser@company.com', '000-000-0001', '1990-01-01', CURDATE(), 1, 1, 'Active');

-- Insert default users
INSERT IGNORE INTO Users (UserID, EmployeeID, Username, PasswordHash, RoleID, IsActive, IsTwoFactorEnabled) VALUES
  (1, 1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, 0),
  (2, 2, 'testuser', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, 0);

-- Add foreign key constraints
ALTER TABLE Users 
ADD CONSTRAINT fk_users_employees FOREIGN KEY (EmployeeID) REFERENCES employees(EmployeeID);

ALTER TABLE employees 
ADD CONSTRAINT fk_employees_manager FOREIGN KEY (ManagerID) REFERENCES employees(EmployeeID);

ALTER TABLE departments 
ADD CONSTRAINT fk_departments_manager FOREIGN KEY (ManagerID) REFERENCES employees(EmployeeID);

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_users_username ON Users(Username);
CREATE INDEX IF NOT EXISTS idx_users_role ON Users(RoleID);
CREATE INDEX IF NOT EXISTS idx_users_active ON Users(IsActive);
CREATE INDEX IF NOT EXISTS idx_employees_department ON employees(DepartmentID);
CREATE INDEX IF NOT EXISTS idx_employees_manager ON employees(ManagerID);
CREATE INDEX IF NOT EXISTS idx_employees_status ON employees(Status);

-- Update existing data to have proper relationships
UPDATE employees SET ManagerID = 1 WHERE EmployeeID = 2;
UPDATE departments SET ManagerID = 1 WHERE DepartmentID = 1;

