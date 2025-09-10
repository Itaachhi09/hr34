# HR34 REST API Documentation

## Overview

The HR34 REST API is a comprehensive microservices-based system for Human Resources management. It provides endpoints for authentication, employee management, payroll processing, HMO benefits, analytics, and notifications.

## Base URL

```
http://localhost/hr34/api_gateway
```

## Authentication

The API uses session-based authentication with optional API key validation for non-auth endpoints.

### API Key
Include the API key in the header for all requests except authentication:
```
X-API-Key: hr34-api-key-2024
```

### Development API Key
```
hr34-dev-key
```

## API Endpoints

### Authentication Service (`/api/v1/auth`)

#### POST /login
Authenticate user and create session.

**Request Body:**
```json
{
  "username": "admin",
  "password": "password"
}
```

**Response:**
```json
{
  "message": "Login successful",
  "two_factor_required": false,
  "user": {
    "user_id": 1,
    "employee_id": 1,
    "username": "admin",
    "full_name": "John Doe",
    "role_name": "System Admin"
  }
}
```

#### POST /verify-2fa
Verify two-factor authentication code.

**Request Body:**
```json
{
  "user_id": 1,
  "code": "123456"
}
```

#### GET /check-session
Check current session status.

#### POST /logout
Logout and destroy session.

#### POST /change-password
Change user password.

**Request Body:**
```json
{
  "current_password": "oldpassword",
  "new_password": "newpassword"
}
```

### Core HR Service (`/api/v1/core-hr`)

#### GET /employees
Get list of all employees.

**Query Parameters:**
- `employee_id` (optional): Filter by specific employee

#### POST /employees
Create new employee.

**Request Body:**
```json
{
  "FirstName": "John",
  "LastName": "Doe",
  "Email": "john.doe@company.com",
  "HireDate": "2024-01-01",
  "DepartmentID": 1,
  "JobRoleID": 1,
  "Phone": "123-456-7890",
  "DateOfBirth": "1990-01-01",
  "Gender": "Male",
  "AddressLine1": "123 Main St",
  "City": "New York",
  "StateProvince": "NY",
  "PostalCode": "10001"
}
```

#### GET /employees/{id}
Get specific employee details.

#### PUT /employees/{id}
Update employee information.

#### GET /departments
Get list of departments.

#### POST /departments
Create new department.

**Request Body:**
```json
{
  "DepartmentName": "Human Resources",
  "Description": "HR Department",
  "ManagerID": 1
}
```

#### GET /attendance
Get attendance records.

**Query Parameters:**
- `employee_id` (optional): Filter by employee
- `date_from` (optional): Start date (default: first day of current month)
- `date_to` (optional): End date (default: last day of current month)

#### POST /attendance
Record attendance.

**Request Body:**
```json
{
  "employee_id": 1,
  "date": "2024-01-15",
  "check_in_time": "09:00:00",
  "check_out_time": "17:00:00",
  "status": "Present"
}
```

#### GET /documents
Get employee documents.

**Query Parameters:**
- `employee_id` (required): Employee ID

#### GET /org-structure
Get organizational structure.

### Payroll Service (`/api/v1/payroll`)

#### GET /salaries
Get salary information.

#### POST /salaries
Create salary record.

**Request Body:**
```json
{
  "EmployeeID": 1,
  "BaseSalary": 50000,
  "EffectiveDate": "2024-01-01",
  "EndDate": null,
  "Notes": "Annual salary"
}
```

#### GET /bonuses
Get bonus records.

**Query Parameters:**
- `employee_id` (optional): Filter by employee
- `date_from` (optional): Start date
- `date_to` (optional): End date

#### POST /bonuses
Add bonus.

**Request Body:**
```json
{
  "EmployeeID": 1,
  "BonusAmount": 5000,
  "BonusDate": "2024-01-15",
  "Description": "Performance bonus"
}
```

#### GET /deductions
Get deduction records.

#### POST /deductions
Add deduction.

**Request Body:**
```json
{
  "EmployeeID": 1,
  "DeductionAmount": 200,
  "DeductionDate": "2024-01-15",
  "DeductionType": "Tax",
  "Description": "Income tax"
}
```

#### GET /payroll-runs
Get payroll runs.

#### POST /payroll-runs
Create payroll run.

**Request Body:**
```json
{
  "PayPeriodStart": "2024-01-01",
  "PayPeriodEnd": "2024-01-31",
  "PaymentDate": "2024-02-01",
  "Status": "Draft"
}
```

#### POST /payroll-runs/{id}/process
Process payroll run.

#### GET /payslips
Get payslips.

**Query Parameters:**
- `employee_id` (optional): Filter by employee
- `run_id` (optional): Filter by payroll run

#### GET /payslips/{id}
Get specific payslip.

### HMO Service (`/api/v1/hmo`)

#### GET /providers
Get HMO providers.

#### POST /providers
Create HMO provider.

**Request Body:**
```json
{
  "ProviderName": "Health Plus",
  "ContactPerson": "Jane Smith",
  "Phone": "123-456-7890",
  "Email": "contact@healthplus.com",
  "Address": "123 Health St",
  "CoverageDetails": "Full medical coverage",
  "IsActive": 1
}
```

#### PUT /providers/{id}
Update HMO provider.

#### DELETE /providers/{id}
Delete HMO provider.

#### GET /benefits-plans
Get benefits plans.

#### POST /benefits-plans
Create benefits plan.

**Request Body:**
```json
{
  "PlanName": "Premium Plan",
  "ProviderID": 1,
  "CoverageType": "Full Coverage",
  "MonthlyPremium": 500,
  "CoverageDetails": "Comprehensive medical coverage",
  "IsActive": 1
}
```

#### GET /employee-benefits
Get employee benefits.

**Query Parameters:**
- `employee_id` (required): Employee ID

#### POST /employee-benefits
Assign employee benefit.

**Request Body:**
```json
{
  "EmployeeID": 1,
  "PlanID": 1,
  "ProviderID": 1,
  "EnrollmentDate": "2024-01-01",
  "Status": "Active",
  "Notes": "New enrollment"
}
```

#### GET /claims
Get claims.

**Query Parameters:**
- `employee_id` (optional): Filter by employee
- `status` (optional): Filter by status

#### POST /claims
Submit claim.

**Request Body:**
```json
{
  "EmployeeID": 1,
  "ClaimTypeID": 1,
  "ProviderID": 1,
  "ClaimAmount": 1000,
  "ClaimDate": "2024-01-15",
  "Description": "Medical consultation",
  "Status": "Submitted"
}
```

#### GET /claim-types
Get claim types.

### Analytics Service (`/api/v1/analytics`)

#### GET /dashboard-summary
Get dashboard summary data.

**Query Parameters:**
- `role` (optional): User role for role-specific data

#### GET /key-metrics
Get key HR metrics.

#### GET /hr-analytics
Get comprehensive HR analytics.

#### GET /reports
Get available reports list.

#### GET /reports/employee-master
Get employee master report.

#### GET /reports/payroll-summary
Get payroll summary report.

**Query Parameters:**
- `date_from` (optional): Start date
- `date_to` (optional): End date

#### GET /reports/leave-summary
Get leave summary report.

**Query Parameters:**
- `year` (optional): Year (default: current year)

#### GET /reports/benefits-summary
Get benefits summary report.

### Notifications Service (`/api/v1/notifications`)

#### GET /
Get notifications for current user.

#### POST /
Create notification (Admin only).

**Request Body:**
```json
{
  "Title": "System Maintenance",
  "Message": "System will be under maintenance tonight",
  "TargetType": "All",
  "TargetID": null,
  "Priority": "High"
}
```

#### POST /mark-read
Mark notification as read.

**Request Body:**
```json
{
  "notification_id": 1
}
```

#### POST /mark-all-read
Mark all notifications as read.

#### DELETE /delete
Delete notification (Admin only).

**Request Body:**
```json
{
  "notification_id": 1
}
```

#### GET /types
Get notification target types.

#### GET /priorities
Get notification priorities.

### Compensation Service (`/api/v1/compensation`)

#### GET /plans
Get compensation plans.

#### POST /plans
Create compensation plan.

**Request Body:**
```json
{
  "PlanName": "Senior Manager Plan",
  "Description": "Compensation plan for senior managers",
  "BaseSalary": 80000,
  "BonusPercentage": 15,
  "CommissionRate": 5,
  "Allowances": "Car allowance, phone allowance",
  "Benefits": "Health insurance, retirement plan",
  "IsActive": 1
}
```

#### PUT /plans/{id}
Update compensation plan.

#### DELETE /plans/{id}
Delete compensation plan.

#### GET /salary-adjustments
Get salary adjustments.

**Query Parameters:**
- `employee_id` (optional): Filter by employee
- `date_from` (optional): Start date
- `date_to` (optional): End date

#### POST /salary-adjustments
Create salary adjustment.

**Request Body:**
```json
{
  "EmployeeID": 1,
  "AdjustmentAmount": 5000,
  "AdjustmentType": "Increase",
  "AdjustmentDate": "2024-01-01",
  "Reason": "Performance-based salary increase",
  "Status": "Approved"
}
```

#### GET /incentives
Get incentives.

**Query Parameters:**
- `employee_id` (optional): Filter by employee
- `date_from` (optional): Start date
- `date_to` (optional): End date

#### POST /incentives
Create incentive.

**Request Body:**
```json
{
  "EmployeeID": 1,
  "IncentiveAmount": 2000,
  "IncentiveType": "Performance",
  "IncentiveDate": "2024-01-15",
  "Description": "Q4 performance bonus",
  "Status": "Approved"
}
```

#### GET /employee-compensation
Get employee compensation.

**Query Parameters:**
- `employee_id` (required): Employee ID

#### POST /employee-compensation
Assign employee compensation.

**Request Body:**
```json
{
  "EmployeeID": 1,
  "PlanID": 1,
  "EffectiveDate": "2024-01-01",
  "Status": "Active",
  "Notes": "New compensation plan assignment"
}
```

#### PUT /employee-compensation/{id}
Update employee compensation.

#### GET /analytics
Get compensation analytics.

#### GET /reports/compensation-summary
Get compensation summary report.

**Query Parameters:**
- `year` (optional): Year (default: current year)

## Error Responses

All endpoints return consistent error responses:

```json
{
  "error": "Error message description"
}
```

### HTTP Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `405` - Method Not Allowed
- `500` - Internal Server Error

## User Roles

The system supports the following user roles:

- **System Admin**: Full access to all features
- **HR Admin**: Access to HR functions and reports
- **Payroll Admin**: Access to payroll functions
- **Manager**: Access to team management functions
- **Employee**: Access to personal information and limited functions

## Rate Limiting

Currently no rate limiting is implemented. Consider implementing rate limiting for production use.

## CORS

The API supports CORS for cross-origin requests. Adjust CORS settings for production environments.

## Database Connection

All microservices use the same database connection configuration from `php/db_connect.php`. Ensure proper environment variables are set:

- `DB_HOST`: Database host
- `DB_NAME`: Database name
- `DB_USER`: Database username
- `DB_PASS`: Database password

## Development Setup

1. Import the Postman collection from `postman_collection/HR34_API_Collection.json`
2. Set the base URL variable to your server URL
3. Use the provided API key for testing
4. Start with authentication endpoints to create a session
5. Use other endpoints with proper authentication

## Production Considerations

1. **Security**: Implement proper API key management and validation
2. **Rate Limiting**: Add rate limiting to prevent abuse
3. **Logging**: Implement comprehensive logging
4. **Monitoring**: Add health checks and monitoring
5. **CORS**: Configure CORS properly for your domain
6. **SSL**: Use HTTPS in production
7. **Database**: Use connection pooling and proper indexing
8. **Caching**: Implement caching for frequently accessed data

## Support

For technical support or questions about the API, please contact the development team.
