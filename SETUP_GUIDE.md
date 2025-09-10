# HR34 REST API Setup Guide

## Prerequisites

- XAMPP or similar LAMP/WAMP stack
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer (for PHPMailer)
- Postman (for API testing)

## Installation Steps

### 1. Database Setup

1. **Import Database Schema**
   ```sql
   -- Import the main database schema
   mysql -u root -p < hr_integrated_db.sql
   ```

2. **Run Database Migrations**
   ```sql
   -- Apply any additional migrations
   mysql -u root -p < db_migrations/2025_09_08_add_roles_users.sql
   mysql -u root -p < db_migrations/2025_09_08_add_users_roles_hmo.sql
   ```

3. **Verify Database Connection**
   - Check that the database `hr_integrated_db` exists
   - Verify all tables are created properly
   - Test connection with the provided credentials

### 2. Environment Configuration

1. **Set Environment Variables**
   Create a `.env` file in the project root or set system environment variables:
   ```env
   DB_HOST=localhost
   DB_NAME=hr_integrated_db
   DB_USER=root
   DB_PASS=
   GMAIL_USER=your-email@gmail.com
   GMAIL_APP_PASSWORD=your-app-password
   DEV_ALLOW_ANY_LOGIN=1
   ```

2. **Configure Email Settings (Optional)**
   - For 2FA email functionality, configure Gmail SMTP
   - Generate an App Password for Gmail
   - Set the environment variables above

### 3. Composer Dependencies

1. **Install PHPMailer**
   ```bash
   composer install
   ```

2. **Verify Installation**
   - Check that `vendor/autoload.php` exists
   - Verify PHPMailer is properly installed

### 4. File Permissions

1. **Set Proper Permissions**
   ```bash
   chmod 755 api_gateway/
   chmod 755 microservices/
   chmod 644 api_gateway/index.php
   chmod 644 microservices/*.php
   ```

### 5. Web Server Configuration

1. **Apache Configuration**
   - Ensure mod_rewrite is enabled
   - Configure virtual host to point to the project directory
   - Set DocumentRoot to the project root

2. **URL Rewriting (Optional)**
   Create `.htaccess` in the project root:
   ```apache
   RewriteEngine On
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteRule ^api_gateway/(.*)$ api_gateway/index.php [QSA,L]
   ```

## Create Test Account

### 1. Create Test User Account
Run the test account creation script to set up a user account for testing:

```bash
php create_test_account.php
```

This will create a test account with the following credentials:
- **Username**: `testuser`
- **Password**: `testpass123`
- **Role**: System Admin
- **2FA**: Disabled (for easy testing)

### 2. Test Login Functionality
Test the login with the created account:

```bash
php test_login.php
```

This will verify that:
- API Gateway is accessible
- Login functionality works
- API endpoints are accessible

### 3. Use HTML Login Form
Open `login_test.html` in your browser to test the login form:
```
http://localhost/hr34/login_test.html
```

## Testing the API

### 1. Import Postman Collection

1. **Open Postman**
2. **Import Collection**
   - Click "Import" button
   - Select `postman_collection/HR34_API_Collection.json`
   - Verify all requests are imported

3. **Configure Environment Variables**
   - Set `base_url` to `http://localhost/hr34/api_gateway`
   - Set `api_key` to `hr34-api-key-2024`

### 2. Test API Endpoints

#### Step 1: Test API Gateway
```
GET http://localhost/hr34/api_gateway/
```
Expected response:
```json
{
  "service": "HR34 API Gateway",
  "version": "v1",
  "status": "active",
  "available_services": ["auth", "core-hr", "payroll", "hmo", "analytics", "notifications"]
}
```

#### Step 2: Test Authentication
```
POST http://localhost/hr34/api_gateway/api/v1/auth/login
Content-Type: application/json

{
  "username": "admin",
  "password": "password"
}
```

#### Step 3: Test Core HR Service
```
GET http://localhost/hr34/api_gateway/api/v1/core-hr/employees
X-API-Key: hr34-api-key-2024
```

#### Step 4: Test Other Services
- Test Payroll endpoints
- Test HMO endpoints
- Test Analytics endpoints
- Test Notifications endpoints

### 3. Database Connectivity Test

Create a test script to verify database connectivity:

```php
<?php
// test_db_connection.php
require_once 'php/db_connect.php';

try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM Employees");
    $result = $stmt->fetch();
    echo "Database connection successful. Employee count: " . $result['count'];
} catch (Exception $e) {
    echo "Database connection failed: " . $e->getMessage();
}
?>
```

## Troubleshooting

### Common Issues

1. **Database Connection Failed**
   - Check database credentials in environment variables
   - Verify MySQL service is running
   - Check database name and user permissions

2. **API Key Validation Failed**
   - Ensure API key is included in headers
   - Check that the API key matches the configured keys
   - Verify the key is sent as `X-API-Key` header

3. **Session Issues**
   - Check PHP session configuration
   - Verify session storage permissions
   - Clear browser cookies and try again

4. **Email Not Working**
   - Verify Gmail credentials are correct
   - Check that App Password is generated correctly
   - Ensure SMTP settings are properly configured

5. **File Permission Issues**
   - Check file permissions on PHP files
   - Verify web server has read access
   - Ensure proper ownership of files

### Debug Mode

Enable debug mode by setting:
```env
DEV_ALLOW_ANY_LOGIN=1
```

This allows any credentials to login for testing purposes.

### Log Files

Check the following for error logs:
- PHP error log (configured in php.ini)
- Apache error log
- Application-specific logs (if configured)

## Production Deployment

### Security Considerations

1. **Remove Development Settings**
   - Set `DEV_ALLOW_ANY_LOGIN=0`
   - Use strong API keys
   - Enable proper authentication

2. **Database Security**
   - Use dedicated database user with limited permissions
   - Enable SSL for database connections
   - Regular database backups

3. **Web Server Security**
   - Use HTTPS in production
   - Configure proper CORS settings
   - Implement rate limiting
   - Regular security updates

### Performance Optimization

1. **Database Optimization**
   - Add proper indexes
   - Use connection pooling
   - Optimize queries

2. **Caching**
   - Implement Redis or Memcached
   - Cache frequently accessed data
   - Use HTTP caching headers

3. **Monitoring**
   - Set up application monitoring
   - Monitor database performance
   - Track API usage and errors

## Support

For additional support:
1. Check the API documentation
2. Review error logs
3. Test individual components
4. Contact the development team

## API Usage Examples

### Complete Workflow Example

1. **Login**
   ```bash
   curl -X POST http://localhost/hr34/api_gateway/api/v1/auth/login \
     -H "Content-Type: application/json" \
     -d '{"username":"admin","password":"password"}'
   ```

2. **Get Employees**
   ```bash
   curl -X GET http://localhost/hr34/api_gateway/api/v1/core-hr/employees \
     -H "X-API-Key: hr34-api-key-2024"
   ```

3. **Create Employee**
   ```bash
   curl -X POST http://localhost/hr34/api_gateway/api/v1/core-hr/employees \
     -H "Content-Type: application/json" \
     -H "X-API-Key: hr34-api-key-2024" \
     -d '{"FirstName":"John","LastName":"Doe","Email":"john@company.com","HireDate":"2024-01-01","DepartmentID":1,"JobRoleID":1}'
   ```

This setup guide should help you get the HR34 REST API up and running successfully.
