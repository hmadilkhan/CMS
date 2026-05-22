# Laravel CRM - Complete Browser Testing Guide

## 📋 Tests Created Based on Your Existing Feature Tests

### ✅ Browser Tests Generated:

1. **AuthenticationTest.php** - Login, Logout, Guest Access
2. **CustomerTest.php** - Customer CRUD with Full Form Data
3. **IntakeFormTest.php** - Complete Intake Form Submission
4. **ProjectMovementTest.php** - Project Department Movement
5. **EmployeeManagementTest.php** - Employee Lifecycle Management
6. **ServiceTicketTest.php** - Service Ticket Workflow
7. **RolePermissionTest.php** - Role & Permission Management
8. **DashboardTest.php** - Dashboard Navigation
9. **EmployeeTest.php** - Employee Operations
10. **FormValidationTest.php** - Form Validation
11. **ProjectTest.php** - Project Management

## 🚀 Quick Start

### 1. Install Dusk
```bash
composer require --dev laravel/dusk
php artisan dusk:install
php artisan dusk:chrome-driver --detect
```

### 2. Setup Test Environment
Create `.env.dusk.local`:
```env
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=crm_test
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Create Test Database
```bash
mysql -u root -p
CREATE DATABASE crm_test;
exit;
```

### 4. Run Tests

**Option A: Automated (Recommended)**
```bash
run-browser-tests.bat
```

**Option B: Manual**
```bash
# Terminal 1
php artisan serve

# Terminal 2
php artisan dusk
```

## 📊 Test Coverage

### Authentication Tests
- ✅ Valid login with credentials
- ✅ Invalid login shows errors
- ✅ User logout functionality
- ✅ Guest redirect to login

### Customer Management Tests
- ✅ View customers list
- ✅ Create customer with full form (first_name, last_name, street, city, state, zipcode, phone, email, panel_qty, sold_date, sales_partner_id, inverter_type_id, module_type_id, inverter_qty, module_qty, contract_amount, redline_costs, commission, dealer_fee)
- ✅ Edit customer details
- ✅ Form validation errors
- ✅ Search customers

### Intake Form Tests
- ✅ Sales person submits complete intake form
- ✅ Form validation prevents empty submission
- ✅ Schedule survey option with utility company, NTP date, HOA details
- ✅ Creates customer, project, finance, and task records

### Project Movement Tests
- ✅ Admin moves project between departments
- ✅ Project list displays all projects
- ✅ Project detail page loads correctly
- ✅ Task completion and new task assignment

### Employee Management Tests
- ✅ Create employee with full details (name, code, joined_date, email, phone, username, password, departments, roles)
- ✅ Update employee information
- ✅ Change employee department and role
- ✅ Employee list display
- ✅ Filter employees by department
- ✅ Form validation

### Service Ticket Tests
- ✅ Create service ticket (subject, assigned_to, priority, notes)
- ✅ Add comments to ticket
- ✅ Resolve ticket
- ✅ Service dashboard displays tickets
- ✅ Form validation

### Role & Permission Tests
- ✅ Create new role
- ✅ Update role name
- ✅ Create permission
- ✅ Assign permissions to role
- ✅ Non-admin access restriction

## 🎯 Test Data Examples

### Customer Creation
```php
First Name: John
Last Name: Doe
Street: 123 Solar Street
City: Phoenix
State: AZ
Zipcode: 85001
Phone: 555-100-1000
Email: john.doe@example.com
Panel Qty: 10
Contract Amount: 25000
Redline Costs: 18000
Commission: 1500
```

### Intake Form Submission
```php
First Name: Browser
Last Name: TestCustomer
Street: 123 Test Street
City: Phoenix
State: AZ
Zipcode: 85001
Phone: 555-200-2000
Email: browser.test@example.com
Panel Qty: 12
Contract Amount: 30000
Redline Costs: 22000
Commission: 2000
```

### Employee Creation
```php
Name: Browser Test Employee
Code: EMP-BROWSER-001
Email: browser.employee@test.com
Phone: 555-700-7000
Username: browser-employee
Password: Password123!
Department: Deal Review
Role: Employee
```

### Service Ticket
```php
Subject: Roof leak inspection needed
Priority: High
Notes: Customer reported a leak near the solar array
Status: Pending → Resolved
```

## 🔧 Run Specific Tests

```bash
# Single test file
php artisan dusk tests/Browser/CustomerTest.php

# Single test method
php artisan dusk --filter test_can_create_customer_with_full_form

# Specific group
php artisan dusk --group=customer

# Stop on first failure
php artisan dusk --stop-on-failure

# Parallel execution (faster)
php artisan dusk --parallel
```

## 🐛 Debugging

### View Browser During Test
Edit `tests/DuskTestCase.php` and remove `--headless` from ChromeOptions:
```php
$options = (new ChromeOptions)->addArguments([
    '--disable-gpu',
    // '--headless', // Comment this line
    '--window-size=1920,1080',
]);
```

### Screenshots
Failed tests automatically save screenshots to:
```
tests/Browser/screenshots/failure-*.png
```

### Console Logs
Check browser console logs:
```
tests/Browser/console/*.log
```

### Pause Test Execution
Add to your test:
```php
$browser->pause(5000); // Pause for 5 seconds
```

## 📈 Performance Tips

1. **Use Parallel Testing**
   ```bash
   php artisan dusk --parallel --processes=4
   ```

2. **Database Transactions**
   Tests use `DatabaseMigrations` for clean state

3. **Headless Mode**
   Keep `--headless` enabled for faster execution

4. **Selective Testing**
   Run only changed tests during development

## ⚠️ Common Issues

### Chrome Driver Version Mismatch
```bash
php artisan dusk:chrome-driver --detect
```

### Port Already in Use
```bash
# Change port in .env.dusk.local
APP_URL=http://127.0.0.1:8001

# Start server on different port
php artisan serve --port=8001
```

### Database Connection Error
```bash
# Verify test database exists
mysql -u root -p -e "SHOW DATABASES LIKE 'crm_test';"

# Run migrations
php artisan migrate --env=dusk.local
```

### Timeout Issues
Increase wait time in tests:
```php
$browser->pause(3000); // 3 seconds
```

## 📝 Test Workflow Mapping

| Feature Test | Browser Test | What It Tests |
|-------------|--------------|---------------|
| CustomerProjectWorkflowTest | CustomerTest | Customer creation with full form data |
| IntakeFormWorkflowTest | IntakeFormTest | Sales person intake form submission |
| ProjectMovementWorkflowTest | ProjectMovementTest | Moving projects between departments |
| AdminManagementWorkflowTest | EmployeeManagementTest, RolePermissionTest | Employee & role management |
| OperationsWorkflowTest | - | Module types, costs, finance options |
| ProjectAcceptanceWorkflowTest | - | Project acceptance workflow |
| ServiceTicketWorkflowTest | ServiceTicketTest | Service ticket creation & resolution |

## 🎓 Next Steps

1. ✅ Run initial test suite: `run-browser-tests.bat`
2. ✅ Review failed tests and screenshots
3. ✅ Adjust selectors based on your actual HTML
4. ✅ Add more test scenarios as needed
5. ✅ Integrate with CI/CD pipeline

## 📞 Support

Check logs in:
- `tests/Browser/screenshots/` - Failed test screenshots
- `tests/Browser/console/` - Browser console logs
- `storage/logs/laravel.log` - Application logs

---

**All tests are based on your existing Feature tests and use real data entry to simulate actual user workflows!**
