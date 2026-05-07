# 🚀 Browser Testing - Quick Run Guide

## ✅ Fixed Batch Files Created!

Maine 3 simple batch files banaye hain:

### 1. **quick-test.bat** (Sabse Simple)
```bash
quick-test.bat
```
- Server start karega
- Sab tests run karega
- Server stop karega

### 2. **run-tests-simple.bat** (With Messages)
```bash
run-tests-simple.bat
```
- Step-by-step messages ke saath
- Chrome driver check karega
- Tests run karega

### 3. **run-specific-test.bat** (Single Test)
```bash
run-specific-test.bat CustomerTest
run-specific-test.bat AuthenticationTest
run-specific-test.bat IntakeFormTest
```
- Specific test file run karega
- Fast execution

## 🎯 Recommended Method:

### Option A: Quick Test (Fastest)
```bash
quick-test.bat
```

### Option B: Manual (Best Control)
```bash
# Terminal 1
php artisan serve

# Terminal 2
php artisan dusk
```

### Option C: Single Test
```bash
# Terminal 1
php artisan serve

# Terminal 2
php artisan dusk tests/Browser/CustomerTest.php
```

## 📋 Available Tests:

```bash
# Authentication
php artisan dusk tests/Browser/AuthenticationTest.php

# Customer Management
php artisan dusk tests/Browser/CustomerTest.php

# Intake Form
php artisan dusk tests/Browser/IntakeFormTest.php

# Project Movement
php artisan dusk tests/Browser/ProjectMovementTest.php

# Employee Management
php artisan dusk tests/Browser/EmployeeManagementTest.php

# Service Tickets
php artisan dusk tests/Browser/ServiceTicketTest.php

# Roles & Permissions
php artisan dusk tests/Browser/RolePermissionTest.php

# Login with Existing User
php artisan dusk tests/Browser/LoginWithExistingUserTest.php
```

## 🔧 Manual Commands (Most Reliable):

### Step 1: Start Server
```bash
php artisan serve
```

### Step 2: Run Tests (New Terminal)
```bash
# All tests
php artisan dusk

# Single test file
php artisan dusk tests/Browser/CustomerTest.php

# Single test method
php artisan dusk --filter test_can_create_customer_with_full_form

# Stop on first failure
php artisan dusk --stop-on-failure
```

### Step 3: Stop Server
```bash
Ctrl + C (in server terminal)
```

## 💡 Pro Tips:

### Run Specific Test Method:
```bash
php artisan dusk --filter test_user_can_login_with_valid_credentials
```

### Run with Browser Visible:
Already enabled! Browser automatically dikhega.

### Run Parallel (Faster):
```bash
php artisan dusk --parallel
```

### Debug Mode:
```bash
php artisan dusk --stop-on-failure
```

## 🐛 Troubleshooting:

### Error: Chrome Driver Not Found
```bash
php artisan dusk:chrome-driver --detect
```

### Error: Port Already in Use
```bash
# Kill existing PHP processes
taskkill /F /IM php.exe

# Then start again
php artisan serve
```

### Error: Database Connection
```bash
# Check .env.dusk.local file exists
# Verify database credentials
```

## 🎬 Quick Start (Recommended):

### Method 1: Two Terminals (Best)
```bash
# Terminal 1
php artisan serve

# Terminal 2
php artisan dusk tests/Browser/LoginWithExistingUserTest.php
```

### Method 2: One Command
```bash
quick-test.bat
```

## 📊 Test Results:

Tests run hone ke baad aapko dikhega:
```
PASS  Tests\Browser\AuthenticationTest
✓ user can login with valid credentials (5.23s)
✓ invalid credentials show error (2.15s)

Tests:  2 passed
Time:   7.38s
```

## 🖼️ Screenshots:

Failed tests ki screenshots yaha save hongi:
```
tests/Browser/screenshots/failure-*.png
```

## 🎯 Recommended Workflow:

1. **Start Server:**
   ```bash
   php artisan serve
   ```

2. **Run Single Test First:**
   ```bash
   php artisan dusk tests/Browser/LoginWithExistingUserTest.php
   ```

3. **If Success, Run All:**
   ```bash
   php artisan dusk
   ```

4. **Stop Server:**
   ```bash
   Ctrl + C
   ```

---

## 🔥 Quick Commands Cheat Sheet:

```bash
# Start server
php artisan serve

# Run all tests
php artisan dusk

# Run specific test
php artisan dusk tests/Browser/CustomerTest.php

# Run with filter
php artisan dusk --filter test_can_create_customer

# Stop on failure
php artisan dusk --stop-on-failure

# Parallel execution
php artisan dusk --parallel

# Update Chrome driver
php artisan dusk:chrome-driver --detect

# Kill PHP processes
taskkill /F /IM php.exe
```

---

**Sabse Easy: Do terminals kholo, ek mein server, dusre mein tests!** 🎉
