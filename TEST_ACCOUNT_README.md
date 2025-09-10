# HR34 Test Account Setup & Usage

## 🎯 Quick Start

### 1. Create Test Account
```bash
php create_test_account.php
```

### 2. Test Login
```bash
php test_login.php
```

### 3. Use Login Form
Open in browser: `http://localhost/hr34/login_test.html`

## 🔑 Test Account Credentials

| Field | Value |
|-------|-------|
| **Username** | `testuser` |
| **Password** | `testpass123` |
| **Role** | System Admin |
| **2FA** | Disabled |
| **Permissions** | Full access to all features |

## 📋 What the Test Account Includes

### ✅ **Database Records Created**
- **Employee Record**: Test User with basic information
- **User Account**: Linked to the employee record
- **Role Assignment**: System Admin role with full permissions
- **Active Status**: Account is active and ready to use

### ✅ **Permissions**
- Access to all API endpoints
- Full CRUD operations on all modules
- Analytics and reporting access
- User management capabilities
- Payroll processing access
- HMO and benefits management

## 🚀 Usage Examples

### 1. **Login Form Testing**
Use the HTML login form at `login_test.html`:
- Pre-filled with test credentials
- Real-time login testing
- Shows detailed response information
- Tests API connectivity

### 2. **Postman Collection**
Import the Postman collection and use:
- Pre-configured with test credentials
- All endpoints ready to test
- Environment variables set up
- No additional configuration needed

### 3. **API Testing Scripts**
Run the test scripts:
```bash
# Test database connectivity
php test_database.php

# Test all API endpoints
php test_api.php

# Test login functionality
php test_login.php
```

### 4. **Direct API Calls**
Use curl or any HTTP client:
```bash
# Login
curl -X POST http://localhost/hr34/api_gateway/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"testuser","password":"testpass123"}'

# Get employees (after login)
curl -X GET http://localhost/hr34/api_gateway/api/v1/core-hr/employees \
  -H "X-API-Key: hr34-api-key-2024"
```

## 🔧 Integration with Your Login Form

### **For Your Existing Login Form**
Update your login form to use these credentials:

```javascript
// Example login form submission
const loginData = {
    username: 'testuser',
    password: 'testpass123'
};

fetch('http://localhost/hr34/api_gateway/api/v1/auth/login', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify(loginData)
})
.then(response => response.json())
.then(data => {
    if (data.message === 'Login successful') {
        // Store session and redirect
        console.log('Login successful:', data.user);
    } else {
        console.error('Login failed:', data.error);
    }
});
```

### **Session Management**
After successful login, the API creates a PHP session. You can:
- Check session status: `GET /api/v1/auth/check-session`
- Logout: `POST /api/v1/auth/logout`
- Access protected endpoints with the session

## 📊 Testing Different Scenarios

### **1. Basic Login Test**
- Username: `testuser`
- Password: `testpass123`
- Expected: Successful login with System Admin role

### **2. API Endpoint Testing**
After login, test various endpoints:
- Employee management
- Payroll operations
- HMO benefits
- Analytics and reports
- Notifications

### **3. Role-Based Access Testing**
The test account has System Admin role, so it can:
- Access all employee data
- Manage payroll
- Configure HMO providers
- View all analytics
- Manage notifications

## 🛠️ Troubleshooting

### **Common Issues**

1. **"Database connection failed"**
   - Ensure XAMPP MySQL is running
   - Check database credentials in `php/db_connect.php`
   - Verify database `hr_integrated_db` exists

2. **"Login failed"**
   - Run `php create_test_account.php` first
   - Check if the user account was created
   - Verify password hash is correct

3. **"API Gateway not accessible"**
   - Ensure XAMPP Apache is running
   - Check if `api_gateway/index.php` is accessible
   - Verify URL path is correct

4. **"Session not found"**
   - Make sure to login first before accessing protected endpoints
   - Check if session is properly created
   - Verify session storage permissions

### **Debug Steps**

1. **Check Database**
   ```sql
   SELECT * FROM Users WHERE Username = 'testuser';
   SELECT * FROM Employees WHERE Email = 'testuser@company.com';
   ```

2. **Check API Gateway**
   ```
   http://localhost/hr34/api_gateway/
   ```

3. **Check Login Endpoint**
   ```
   http://localhost/hr34/api_gateway/api/v1/auth/login
   ```

## 📝 Additional Test Accounts

You can create additional test accounts by modifying `create_test_account.php`:

```php
// Create different role accounts
$test_accounts = [
    ['username' => 'hruser', 'role' => 'HR Admin'],
    ['username' => 'manager', 'role' => 'Manager'],
    ['username' => 'employee', 'role' => 'Employee']
];
```

## 🎉 Success Indicators

You'll know everything is working when:
- ✅ `create_test_account.php` runs without errors
- ✅ `test_login.php` shows successful login
- ✅ HTML login form shows "Login Successful"
- ✅ Postman collection requests work
- ✅ API endpoints return data instead of errors

## 📞 Support

If you encounter issues:
1. Check the troubleshooting section above
2. Run the test scripts to identify the problem
3. Check XAMPP services are running
4. Verify database connectivity
5. Review error logs in XAMPP

The test account provides a complete testing environment for your HR34 system! 🚀
