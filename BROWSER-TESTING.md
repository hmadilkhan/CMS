# Laravel CRM - Browser Testing Guide

## Quick Start

### 1. Install Dependencies
```bash
composer require --dev laravel/dusk
php artisan dusk:install
```

### 2. Setup Environment
```bash
copy .env .env.dusk.local
```

Edit `.env.dusk.local`:
```env
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=mysql
DB_DATABASE=crm_test
```

### 3. Install Chrome Driver
```bash
php artisan dusk:chrome-driver --detect
```

### 4. Run Tests

**Option A: Automatic (Recommended)**
```bash
run-browser-tests.bat
```

**Option B: Manual**
```bash
# Terminal 1: Start server
php artisan serve

# Terminal 2: Run tests
php artisan dusk
```

## Test Files Created

- `AuthenticationTest.php` - Login, logout, registration
- `CustomerTest.php` - Customer CRUD operations
- `ProjectTest.php` - Project management
- `EmployeeTest.php` - Employee management
- `DashboardTest.php` - Dashboard & navigation
- `RolePermissionTest.php` - Roles & permissions
- `FormValidationTest.php` - Form validation

## Run Specific Tests

```bash
# Single test file
php artisan dusk tests/Browser/CustomerTest.php

# Single test method
php artisan dusk --filter test_can_create_customer

# Parallel testing (faster)
php artisan dusk --parallel
```

## Debugging

```bash
# Run with browser visible (remove headless)
# Edit tests/DuskTestCase.php and remove '--headless' option

# Take screenshots
$browser->screenshot('test-name');
# Screenshots saved in: tests/Browser/screenshots/

# Pause execution
$browser->pause(5000); // 5 seconds
```

## Common Issues

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

### Database Issues
```bash
# Create test database
mysql -u root -p
CREATE DATABASE crm_test;

# Run migrations
php artisan migrate --env=dusk.local
```

## Performance Testing

```bash
# Run all tests with timing
php artisan dusk --profile

# Parallel execution
php artisan dusk --parallel --processes=4
```

## CI/CD Integration

Add to `.github/workflows/dusk.yml`:
```yaml
name: Browser Tests
on: [push, pull_request]
jobs:
  dusk:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Install Dependencies
        run: composer install
      - name: Run Dusk Tests
        run: php artisan dusk
```

## Test Coverage

- ✅ Authentication flows
- ✅ CRUD operations (Customers, Projects, Employees)
- ✅ Form validation
- ✅ Navigation & routing
- ✅ Role-based access control
- ✅ Public project tracking
- ✅ Search & filtering

## Next Steps

1. Customize tests based on your specific forms
2. Add tests for file uploads
3. Add tests for email functionality
4. Add tests for reports
5. Add performance benchmarks
