-- ==========================
-- Core Human Capital Management (CHCM)
-- ==========================
CREATE TABLE Departments (
    DepartmentID INT PRIMARY KEY AUTO_INCREMENT,
    DepartmentName VARCHAR(100) NOT NULL
);

CREATE TABLE JobRoles (
    JobRoleID INT PRIMARY KEY AUTO_INCREMENT,
    RoleName VARCHAR(100) NOT NULL UNIQUE,
    RoleDescription TEXT
);

CREATE TABLE Employees (
    EmployeeID INT PRIMARY KEY AUTO_INCREMENT,
    FirstName VARCHAR(50) NOT NULL,
    LastName VARCHAR(50) NOT NULL,
    Email VARCHAR(100) UNIQUE NOT NULL,
    Phone VARCHAR(20) NOT NULL,
    DateOfBirth DATE NOT NULL,
    HireDate DATE NOT NULL,
    DepartmentID INT NOT NULL,
    JobRoleID INT NOT NULL,
    Status ENUM('Active', 'Inactive', 'Terminated') DEFAULT 'Active',
    FOREIGN KEY (DepartmentID) REFERENCES Departments(DepartmentID),
    FOREIGN KEY (JobRoleID) REFERENCES JobRoles(JobRoleID)
);

CREATE TABLE OrganizationalStructure (
    StructureID INT PRIMARY KEY AUTO_INCREMENT,
    StructureName VARCHAR(255) NOT NULL,
    ParentStructureID INT,
    FOREIGN KEY (ParentStructureID) REFERENCES OrganizationalStructure(StructureID)
);

CREATE TABLE EmployeeDocuments (
    DocumentID INT PRIMARY KEY AUTO_INCREMENT,
    EmployeeID INT NOT NULL,
    DocumentName VARCHAR(255) NOT NULL,
    DocumentType VARCHAR(100) NOT NULL,
    UploadDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (EmployeeID) REFERENCES Employees(EmployeeID)
);

-- ==========================
-- Leave Management
-- ==========================
CREATE TABLE LeaveTypes (
    LeaveTypeID INT PRIMARY KEY AUTO_INCREMENT,
    LeaveName VARCHAR(100) NOT NULL,
    Description TEXT,
    MaxDaysAllowed INT NOT NULL
);

CREATE TABLE LeaveRequests (
    RequestID INT PRIMARY KEY AUTO_INCREMENT,
    EmployeeID INT NOT NULL,
    LeaveTypeID INT NOT NULL,
    StartDate DATE NOT NULL,
    EndDate DATE NOT NULL,
    Status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    RequestDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (EmployeeID) REFERENCES Employees(EmployeeID),
    FOREIGN KEY (LeaveTypeID) REFERENCES LeaveTypes(LeaveTypeID)
);

CREATE TABLE LeaveBalances (
    BalanceID INT PRIMARY KEY AUTO_INCREMENT,
    EmployeeID INT NOT NULL,
    LeaveTypeID INT NOT NULL,
    RemainingDays INT NOT NULL,
    FOREIGN KEY (EmployeeID) REFERENCES Employees(EmployeeID),
    FOREIGN KEY (LeaveTypeID) REFERENCES LeaveTypes(LeaveTypeID)
);

-- ==========================
-- Time and Attendance (TAM)
-- ==========================
CREATE TABLE AttendanceRecords (
    RecordID INT PRIMARY KEY AUTO_INCREMENT,
    EmployeeID INT NOT NULL,
    Date DATE NOT NULL,
    CheckInTime TIME NOT NULL,
    CheckOutTime TIME,
    Status ENUM('Present', 'Absent', 'Late', 'On Leave') DEFAULT 'Present',
    FOREIGN KEY (EmployeeID) REFERENCES Employees(EmployeeID)
);

CREATE TABLE Shifts (
    ShiftID INT PRIMARY KEY AUTO_INCREMENT,
    ShiftName VARCHAR(100) NOT NULL,
    StartTime TIME NOT NULL,
    EndTime TIME NOT NULL
);

CREATE TABLE Schedules (
    ScheduleID INT PRIMARY KEY AUTO_INCREMENT,
    EmployeeID INT NOT NULL,
    ShiftID INT NOT NULL,
    ScheduleDate DATE NOT NULL,
    FOREIGN KEY (EmployeeID) REFERENCES Employees(EmployeeID),
    FOREIGN KEY (ShiftID) REFERENCES Shifts(ShiftID)
);

CREATE TABLE Timesheets (
    TimesheetID INT PRIMARY KEY AUTO_INCREMENT,
    EmployeeID INT NOT NULL,
    ScheduleID INT NOT NULL,
    HoursWorked DECIMAL(5,2) NOT NULL,
    SubmissionDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (EmployeeID) REFERENCES Employees(EmployeeID),
    FOREIGN KEY (ScheduleID) REFERENCES Schedules(ScheduleID)
);

-- ==========================
-- Claims and Reimbursement (CAR)
-- ==========================
CREATE TABLE ClaimTypes (
    ClaimTypeID INT PRIMARY KEY AUTO_INCREMENT,
    TypeName VARCHAR(100) NOT NULL,
    Description TEXT
);

CREATE TABLE Claims (
    ClaimID INT PRIMARY KEY AUTO_INCREMENT,
    EmployeeID INT NOT NULL,
    ClaimTypeID INT NOT NULL,
    Amount DECIMAL(10,2) NOT NULL,
    SubmissionDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    Status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    FOREIGN KEY (EmployeeID) REFERENCES Employees(EmployeeID),
    FOREIGN KEY (ClaimTypeID) REFERENCES ClaimTypes(ClaimTypeID)
);

CREATE TABLE ClaimApprovals (
    ApprovalID INT PRIMARY KEY AUTO_INCREMENT,
    ClaimID INT NOT NULL,
    ApprovedBy VARCHAR(100) NOT NULL,
    ApprovalDate DATETIME,
    Comments TEXT,
    FOREIGN KEY (ClaimID) REFERENCES Claims(ClaimID)
);

-- ==========================
-- Payroll
-- ==========================
CREATE TABLE PayrollRuns (
    PayrollID INT PRIMARY KEY AUTO_INCREMENT,
    PayPeriod VARCHAR(50) NOT NULL,
    RunDate DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE EmployeeSalaries (
    SalaryID INT PRIMARY KEY AUTO_INCREMENT,
    EmployeeID INT NOT NULL,
    SalaryAmount DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (EmployeeID) REFERENCES Employees(EmployeeID)
);

CREATE TABLE Deductions (
    DeductionID INT PRIMARY KEY AUTO_INCREMENT,
    PayrollID INT NOT NULL,
    Description VARCHAR(255) NOT NULL,
    Amount DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (PayrollID) REFERENCES PayrollRuns(PayrollID)
);

CREATE TABLE Bonuses (
    BonusID INT PRIMARY KEY AUTO_INCREMENT,
    EmployeeID INT NOT NULL,
    BonusAmount DECIMAL(10,2) NOT NULL,
    BonusDate DATE NOT NULL,
    FOREIGN KEY (EmployeeID) REFERENCES Employees(EmployeeID)
);

-- ==========================
-- Compensation Planning & Administration (CPA)
-- ==========================
CREATE TABLE CompensationPlans (
    PlanID INT PRIMARY KEY AUTO_INCREMENT,
    PlanName VARCHAR(255) NOT NULL,
    Description TEXT
);

CREATE TABLE SalaryAdjustments (
    AdjustmentID INT PRIMARY KEY AUTO_INCREMENT,
    EmployeeID INT NOT NULL,
    AdjustmentAmount DECIMAL(10,2) NOT NULL,
    EffectiveDate DATE NOT NULL,
    FOREIGN KEY (EmployeeID) REFERENCES Employees(EmployeeID)
);

CREATE TABLE Incentives (
    IncentiveID INT PRIMARY KEY AUTO_INCREMENT,
    EmployeeID INT NOT NULL,
    IncentiveAmount DECIMAL(10,2) NOT NULL,
    Reason TEXT,
    FOREIGN KEY (EmployeeID) REFERENCES Employees(EmployeeID)
);

-- ==========================
-- HR Analytics (HRA)
-- ==========================
CREATE TABLE HRReports (
    ReportID INT PRIMARY KEY AUTO_INCREMENT,
    ReportName VARCHAR(255) NOT NULL,
    GeneratedDate DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Metrics (
    MetricID INT PRIMARY KEY AUTO_INCREMENT,
    ReportID INT NOT NULL,
    MetricName VARCHAR(255) NOT NULL,
    Value DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (ReportID) REFERENCES HRReports(ReportID)
);

CREATE TABLE Dashboards (
    DashboardID INT PRIMARY KEY AUTO_INCREMENT,
    DashboardName VARCHAR(255) NOT NULL
);
