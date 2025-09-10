# HR34 Login Troubleshooting Guide

## 🚨 Quick Fix Steps

### Step 1: Check XAMPP Status
1. **Open XAMPP Control Panel**
2. **Start Apache** (should show green "Running")
3. **Start MySQL** (should show green "Running")
4. If either shows "Stopped", click "Start"

### Step 2: Test Database Connection
Open in browser: `http://localhost/hr34/web_test.php`

This will show you:
- ✅ PHP is working
- ✅ Database connection status
- ✅ Test user status
- ✅ API Gateway status

### Step 3: Create Test User
If the test user doesn't exist, run:
```bash
php create_test_account.php
```

Or use the web debug tool: `http://localhost/hr34/login_debug.html`

### Step 4: Test Login
Use the login form: `http://localhost/hr34/login_test.html`

## 🔍 Common Issues & Solutions

### Issue 1: "Database connection failed"
**Symptoms:**
- Error: "Database connection failed"
- Cannot connect to MySQL

**Solutions:**
1. **Check XAMPP MySQL is running**
   - Open XAMPP Control Panel
   - Make sure MySQL shows "Running" (green)

2. **Check database exists**
   - Open phpMyAdmin: `http://localhost/phpmyadmin`
   - Look for database `hr_integrated_db`
   - If missing, import `hr_integrated_db.sql`

3. **Check database credentials**
   - Edit `php/db_connect.php`
   - Verify host, database name, username, password

### Issue 2: "API Gateway not accessible"
**Symptoms:**
- Error: "API Gateway is not accessible"
- HTTP 404 or connection error

**Solutions:**
1. **Check XAMPP Apache is running**
   - Open XAMPP Control Panel
   - Make sure Apache shows "Running" (green)

2. **Check file paths**
   - Verify `api_gateway/index.php` exists
   - Check URL: `http://localhost/hr34/api_gateway/`

3. **Check .htaccess (if using)**
   - Make sure mod_rewrite is enabled in Apache
   - Check .htaccess file exists and is correct

### Issue 3: "Login failed - Invalid username or password"
**Symptoms:**
- Login returns error message
- User not found or password incorrect

**Solutions:**
1. **Create test user**
   ```bash
   php create_test_account.php
   ```

2. **Check user exists in database**
   - Open phpMyAdmin
   - Go to `hr_integrated_db` → `Users` table
   - Look for username `testuser`

3. **Reset password**
   - Delete existing test user from database
   - Run create script again

### Issue 4: "Session not found"
**Symptoms:**
- Login works but API calls fail
- "Authentication required" errors

**Solutions:**
1. **Check session configuration**
   - Make sure PHP sessions are enabled
   - Check session storage permissions

2. **Use same browser session**
   - Don't close browser between login and API calls
   - Make sure cookies are enabled

### Issue 5: "cURL Error" or "Connection Error"
**Symptoms:**
- Network connection errors
- Timeout errors

**Solutions:**
1. **Check XAMPP is running**
   - Both Apache and MySQL must be running

2. **Check firewall/antivirus**
   - Temporarily disable to test
   - Add XAMPP to exceptions

3. **Check port conflicts**
   - Default ports: Apache (80), MySQL (3306)
   - Change ports in XAMPP if needed

## 🛠️ Debug Tools

### 1. Web Test Page
```
http://localhost/hr34/web_test.php
```
- Tests database connection
- Creates test user if needed
- Shows system status

### 2. Login Debug Tool
```
http://localhost/hr34/login_debug.html
```
- Interactive debugging tool
- Step-by-step testing
- Visual feedback

### 3. Command Line Tests
```bash
# Test database
php test_database.php

# Test API
php test_api.php

# Test login
php test_login.php
```

## 📋 Step-by-Step Setup

### 1. Start XAMPP
- Open XAMPP Control Panel
- Start Apache
- Start MySQL

### 2. Import Database
- Open phpMyAdmin: `http://localhost/phpmyadmin`
- Create database: `hr_integrated_db`
- Import file: `hr_integrated_db.sql`

### 3. Create Test User
```bash
php create_test_account.php
```

### 4. Test System
```
http://localhost/hr34/web_test.php
```

### 5. Test Login
```
http://localhost/hr34/login_test.html
```

## 🔧 Manual Database Setup

If the scripts don't work, create the test user manually:

### 1. Open phpMyAdmin
```
http://localhost/phpmyadmin
```

### 2. Select Database
- Click on `hr_integrated_db`

### 3. Create Role (if needed)
```sql
INSERT INTO Roles (RoleName, Description) 
VALUES ('System Admin', 'System Administrator');
```

### 4. Create Employee
```sql
INSERT INTO Employees (FirstName, LastName, Email, HireDate, Status) 
VALUES ('Test', 'User', 'testuser@company.com', CURDATE(), 'Active');
```

### 5. Create User
```sql
INSERT INTO Users (Username, PasswordHash, RoleID, EmployeeID, IsActive, IsTwoFactorEnabled) 
VALUES ('testuser', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, 1, 0);
```

## 📞 Still Having Issues?

### Check These Files:
1. `php/db_connect.php` - Database connection
2. `api_gateway/index.php` - API Gateway
3. `microservices/auth_service.php` - Authentication service

### Check These URLs:
1. `http://localhost/hr34/` - Main site
2. `http://localhost/hr34/api_gateway/` - API Gateway
3. `http://localhost/phpmyadmin` - Database admin

### Check XAMPP Logs:
- Apache Error Log: `C:\NEWXAMPP\apache\logs\error.log`
- MySQL Error Log: `C:\NEWXAMPP\mysql\data\*.err`

## 🎯 Success Indicators

You'll know everything is working when:
- ✅ XAMPP shows Apache and MySQL running
- ✅ `web_test.php` shows all green checkmarks
- ✅ `login_test.html` shows "Login Successful"
- ✅ API endpoints return data instead of errors

## 🚀 Quick Test Commands

```bash
# Check if files exist
dir api_gateway
dir microservices
dir php

# Test database
php -r "require 'php/db_connect.php'; echo 'DB OK';"

# Test API Gateway
curl http://localhost/hr34/api_gateway/

# Test login
curl -X POST http://localhost/hr34/api_gateway/api/v1/auth/login -H "Content-Type: application/json" -d "{\"username\":\"testuser\",\"password\":\"testpass123\"}"
```

The most common issue is XAMPP not running. Make sure both Apache and MySQL are started! 🚀
