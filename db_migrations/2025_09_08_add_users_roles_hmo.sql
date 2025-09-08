-- Roles and Users seed (idempotent)
CREATE TABLE IF NOT EXISTS Roles (
  RoleID INT PRIMARY KEY,
  RoleName VARCHAR(100) NOT NULL UNIQUE
);

INSERT IGNORE INTO Roles (RoleID, RoleName) VALUES
  (1, 'System Admin'),
  (2, 'HR Admin'),
  (3, 'Employee'),
  (4, 'Manager');

-- Ensure Users table exists minimally (skip if schema already present)
-- Note: Adjust columns if your existing Users table differs
CREATE TABLE IF NOT EXISTS Users (
  UserID INT AUTO_INCREMENT PRIMARY KEY,
  EmployeeID INT NULL,
  Username VARCHAR(100) NOT NULL UNIQUE,
  PasswordHash VARCHAR(255) NOT NULL,
  RoleID INT NOT NULL,
  IsActive TINYINT(1) NOT NULL DEFAULT 1,
  IsTwoFactorEnabled TINYINT(1) NOT NULL DEFAULT 0,
  TwoFactorEmailCode VARCHAR(10) NULL,
  TwoFactorCodeExpiry DATETIME NULL,
  CONSTRAINT fk_users_roles FOREIGN KEY (RoleID) REFERENCES Roles(RoleID)
)

-- Seed Admin and Chief HR accounts (replace password hashes as needed)
INSERT INTO Users (EmployeeID, Username, PasswordHash, RoleID, IsActive, IsTwoFactorEnabled)
SELECT NULL, 'sysadmin', '$2y$10$X/1h1m4d3MOCKHASHMOCKHASHMOCKha3N0FZy4iTnQ7m7w3qh', 1, 1, 0
WHERE NOT EXISTS (SELECT 1 FROM Users WHERE Username = 'sysadmin');

INSERT INTO Users (EmployeeID, Username, PasswordHash, RoleID, IsActive, IsTwoFactorEnabled)
SELECT NULL, 'chiefhr', '$2y$10$Y/2h1m4d3MOCKHASHMOCKHASHMOCKha3N0FZy4iTnQ7m7w3qi', 2, 1, 0
WHERE NOT EXISTS (SELECT 1 FROM Users WHERE Username = 'chiefhr');

-- HMO & Benefits
CREATE TABLE IF NOT EXISTS HMOProviders (
  ProviderID INT AUTO_INCREMENT PRIMARY KEY,
  ProviderName VARCHAR(150) NOT NULL,
  ContactInfo VARCHAR(255) NULL,
  IsActive TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS BenefitsPlans (
  BenefitID INT AUTO_INCREMENT PRIMARY KEY,
  ProviderID INT NOT NULL,
  PlanName VARCHAR(150) NOT NULL,
  CoverageDetails TEXT NULL,
  MonthlyPremium DECIMAL(12,2) NULL,
  IsActive TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_benefits_provider FOREIGN KEY (ProviderID) REFERENCES HMOProviders(ProviderID)
);

CREATE TABLE IF NOT EXISTS EmployeeBenefits (
  EmployeeBenefitID INT AUTO_INCREMENT PRIMARY KEY,
  EmployeeID INT NOT NULL,
  BenefitID INT NOT NULL,
  EnrollmentDate DATE NOT NULL,
  Status VARCHAR(50) NOT NULL DEFAULT 'Active',
  CONSTRAINT fk_emp_benefit_plan FOREIGN KEY (BenefitID) REFERENCES BenefitsPlans(BenefitID)
);


