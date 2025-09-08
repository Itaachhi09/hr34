<?php
header('Content-Type: application/json');
require_once '../db_connect.php';
// Create Roles/Users tables if missing
try {
    $pdo->exec(file_get_contents(__DIR__ . '/../../db_migrations/2025_09_08_add_roles_users.sql'));
} catch (Throwable $e) { /* ignore; may already exist */ }

try {
    // Ensure Roles
    $pdo->exec("INSERT IGNORE INTO Roles (RoleID, RoleName) VALUES (1,'System Admin'),(2,'HR Admin'),(3,'Employee'),(4,'Manager')");

    // Create minimal Employees if table exists
    $hasEmployees = false;
    try {
        $pdo->query('SELECT 1 FROM Employees LIMIT 1');
        $hasEmployees = true;
    } catch (Throwable $e) { $hasEmployees = false; }

    $adminEmployeeId = null;
    $hrEmployeeId = null;
    if ($hasEmployees) {
        // Ensure at least one Department and required JobRoles exist
        $deptId = (int)$pdo->query("SELECT DepartmentID FROM departments ORDER BY DepartmentID LIMIT 1")->fetchColumn();
        if ($deptId === 0) {
            $pdo->exec("INSERT INTO departments (DepartmentName) VALUES ('Administration')");
            $deptId = (int)$pdo->lastInsertId();
        }
        // Create roles if missing (ignore duplicates by unique RoleName)
        $pdo->exec("INSERT IGNORE INTO jobroles (RoleName, RoleDescription) VALUES
            ('System Administrator','System administration'),
            ('Chief HR','Human Resources lead')");
        // Fetch role IDs (unique RoleName constraint)
        $roleAdminId = (int)$pdo->query("SELECT JobRoleID FROM jobroles WHERE RoleName='System Administrator' LIMIT 1")->fetchColumn();
        $roleHrId = (int)$pdo->query("SELECT JobRoleID FROM jobroles WHERE RoleName='Chief HR' LIMIT 1")->fetchColumn();

        $today = date('Y-m-d');
        // Insert Employees with required columns
        $stmtEmp = $pdo->prepare("INSERT INTO Employees (FirstName, LastName, Email, Phone, DateOfBirth, HireDate, DepartmentID, JobRoleID, Status)
            VALUES (:fn, :ln, :em, :ph, :dob, :hd, :dept, :role, 'Active')");

        // System Admin employee
        $stmtEmp->execute([
            ':fn' => 'System', ':ln' => 'Administrator', ':em' => 'sysadmin@example.com',
            ':ph' => '0000000000', ':dob' => '1990-01-01', ':hd' => $today, ':dept' => $deptId, ':role' => $roleAdminId
        ]);
        $adminEmployeeId = (int)$pdo->lastInsertId();

        // Chief HR employee
        $stmtEmp->execute([
            ':fn' => 'Chief', ':ln' => 'HR', ':em' => 'chiefhr@example.com',
            ':ph' => '0000000001', ':dob' => '1990-01-02', ':hd' => $today, ':dept' => $deptId, ':role' => $roleHrId
        ]);
        $hrEmployeeId = (int)$pdo->lastInsertId();
    }

    // Passwords: admin123 / chief123
    $adminHash = password_hash('admin123', PASSWORD_BCRYPT);
    $chiefHash = password_hash('chief123', PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("SELECT UserID FROM Users WHERE Username = :u");
    $stmt->execute([':u' => 'sysadmin']);
    if (!$stmt->fetch()) {
        $stmtIns = $pdo->prepare("INSERT INTO Users (EmployeeID, Username, PasswordHash, RoleID, IsActive, IsTwoFactorEnabled) VALUES (:eid, 'sysadmin', :ph, 1, 1, 0)");
        $stmtIns->execute([':eid' => $adminEmployeeId, ':ph' => $adminHash]);
    }
    $stmt->execute([':u' => 'chiefhr']);
    if (!$stmt->fetch()) {
        $stmtIns = $pdo->prepare("INSERT INTO Users (EmployeeID, Username, PasswordHash, RoleID, IsActive, IsTwoFactorEnabled) VALUES (:eid, 'chiefhr', :ph, 2, 1, 0)");
        $stmtIns->execute([':eid' => $hrEmployeeId, ':ph' => $chiefHash]);
    }

    echo json_encode(['message' => 'Seed completed', 'users' => ['sysadmin' => 'admin123', 'chiefhr' => 'chief123']]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Seeding failed', 'details' => $e->getMessage()]);
}
?>


