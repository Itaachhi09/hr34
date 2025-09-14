# HR34 Quick Fix Guide

## 🚀 Quick Setup (5 minutes)

### Step 1: Start XAMPP
1. Open XAMPP Control Panel
2. Start **Apache** (should show green "Running")
3. Start **MySQL** (should show green "Running")

### Step 2: Run the Fix Script
Open your browser and go to:
```
http://localhost/hr34/fix_all_issues.php
```

This will automatically:
- ✅ Fix database schema
- ✅ Create test users
- ✅ Fix API configuration
- ✅ Test all endpoints
- ✅ Create Postman collection

### Step 3: Test the System
1. **Admin Login**: http://localhost/hr34/admin_landing.php
2. **Employee Login**: http://localhost/hr34/employee_landing.php
3. **API Gateway**: http://localhost/hr34/api_gateway/

## 🔑 Test Credentials
- **Username**: `admin`
- **Password**: `password`
- **Role**: System Admin

- **Username**: `testuser`  
- **Password**: `password`
- **Role**: System Admin

## 🛠️ Manual Fix (if automatic fails)

### 1. Fix Database
```bash
mysql -u root -p hr_integrated_db < complete_database_fix.sql
```

### 2. Test System
```bash
# Run system test
http://localhost/hr34/system_test.php

# Run API test
http://localhost/hr34/api_connection_test.php
```

## 📋 What Was Fixed

### ✅ Database Issues
- Added missing `Users` and `Roles` tables
- Fixed employee table structure
- Added proper foreign key relationships
- Created test data

### ✅ API Issues  
- Fixed API Gateway routing
- Updated frontend API URLs
- Added proper API key authentication
- Created Postman collection

### ✅ Frontend Issues
- Fixed JavaScript API calls
- Updated utility functions
- Fixed role-based access

### ✅ File Cleanup
- Removed unnecessary files
- Kept only essential system files

## 🐛 Troubleshooting

### "Database connection failed"
- Check XAMPP MySQL is running
- Verify database `hr_integrated_db` exists
- Run: `mysql -u root -p hr_integrated_db < complete_database_fix.sql`

### "API Gateway not accessible"
- Check XAMPP Apache is running
- Verify `api_gateway/index.php` exists
- Check URL: http://localhost/hr34/api_gateway/

### "Login failed"
- Use correct credentials: admin/password
- Check if test users were created
- Run system test to verify

### "Frontend not loading"
- Check browser console for errors
- Verify JavaScript files are accessible
- Clear browser cache

## 📞 Support

If you still have issues:
1. Run: http://localhost/hr34/system_test.php
2. Check the error messages
3. Follow the recommended fixes
4. Contact support with the error details

## 🎯 Success Indicators

You'll know everything is working when:
- ✅ XAMPP shows Apache and MySQL running
- ✅ `system_test.php` shows all green checkmarks
- ✅ Login works with admin/password
- ✅ API endpoints return data instead of errors
- ✅ Postman collection works

---

**Note**: This fix script addresses all the major issues identified in your system. The database schema has been corrected, API connections fixed, and unnecessary files removed while preserving all important functionality.

