-- HR34 Database Schema Fix
-- This script adds the missing authentication tables and fixes the database structure

-- Create Roles table
CREATE TABLE IF NOT EXISTS Roles (
  RoleID INT PRIMARY KEY AUTO_INCREMENT,
  RoleName VARCHAR(100) NOT NULL UNIQUE,
  Description TEXT DEFAULT NULL
);

-- Create Users table
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
  CONSTRAINT fk_users_roles FOREIGN KEY (RoleID) REFERENCES Roles(RoleID),
  CONSTRAINT fk_users_employees FOREIGN KEY (EmployeeID) REFERENCES employees(EmployeeID)
);

-- Insert default roles
INSERT IGNORE INTO Roles (RoleID, RoleName, Description) VALUES
  (1, 'System Admin', 'Full system access and administration'),
  (2, 'HR Admin', 'Human Resources administration'),
  (3, 'Employee', 'Standard employee access'),
  (4, 'Manager', 'Manager level access');

-- Create test admin user
INSERT IGNORE INTO Users (UserID, EmployeeID, Username, PasswordHash, RoleID, IsActive, IsTwoFactorEnabled) VALUES
  (1, 1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, 0),
  (2, 2, 'testuser', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, 0);

-- Ensure we have at least one employee for the admin user
INSERT IGNORE INTO employees (EmployeeID, FirstName, LastName, Email, Phone, DateOfBirth, HireDate, DepartmentID, JobRoleID, Status) VALUES
  (1, 'System', 'Administrator', 'admin@company.com', '000-000-0000', '1990-01-01', CURDATE(), 1, 1, 'Active'),
  (2, 'Test', 'User', 'testuser@company.com', '000-000-0001', '1990-01-01', CURDATE(), 1, 1, 'Active');

-- Ensure we have at least one department
INSERT IGNORE INTO departments (DepartmentID, DepartmentName) VALUES
  (1, 'Administration');

-- Ensure we have at least one job role
INSERT IGNORE INTO jobroles (JobRoleID, RoleName, RoleDescription) VALUES
  (1, 'Administrator', 'System Administrator');

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_users_username ON Users(Username);
CREATE INDEX IF NOT EXISTS idx_users_role ON Users(RoleID);
CREATE INDEX IF NOT EXISTS idx_users_active ON Users(IsActive);

