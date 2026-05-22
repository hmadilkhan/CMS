# 🔐 Test Credentials Setup Guide

## 3 Tarike Credentials Set Karne Ke

### ✅ Option 1: Test File Mein Direct Change Karo (EASIEST)

**File:** `tests/Browser/AuthenticationTest.php`

```php
public function test_user_can_login_with_valid_credentials()
{
    UserType::create(['name' => 'Admin']);
    
    // YAHA APNA EMAIL AUR PASSWORD DALO ↓
    $user = User::factory()->create([
        'email' => 'admin@example.com',     // ← CHANGE THIS
        'password' => bcrypt('admin123'),   // ← CHANGE THIS
        'user_type_id' => 1
    ]);
    
    Role::create(['name' => 'Super Admin']);
    $user->assignRole('Super Admin');

    $this->browse(function (Browser $browser) {
        $browser->visit('/login')
                ->type('email', 'admin@example.com')    // ← CHANGE THIS
                ->type('password', 'admin123')          // ← CHANGE THIS
                ->press('Log in')
                ->pause(2000)
                ->assertPathIs('/dashboard');
    });
}
```

### ✅ Option 2: Existing Database User Use Karo (RECOMMENDED)

**File:** `tests/Browser/LoginWithExistingUserTest.php`

```php
public function test_login_with_existing_database_user()
{
    $this->browse(function (Browser $browser) {
        $browser->visit('/login')
                ->type('email', 'your-real-email@example.com')  // ← DATABASE EMAIL
                ->type('password', 'your-real-password')        // ← DATABASE PASSWORD
                ->press('Log in')
                ->pause(3000)
                ->assertPathIs('/dashboard');
    });
}
```

**Run karo:**
```bash
php artisan dusk tests/Browser/LoginWithExistingUserTest.php
```

### ✅ Option 3: Direct Login (Password Ki Zarurat Nahi)

```php
public function test_direct_login_without_password()
{
    // Database se user fetch karo
    $user = User::where('email', 'your-email@example.com')->first();

    $this->browse(function (Browser $browser) use ($user) {
        // Direct login - password nahi chahiye
        $browser->loginAs($user)
                ->visit('/dashboard')
                ->pause(2000)
                ->assertSee('Dashboard');
    });
}
```

## 🎯 Quick Setup Steps:

### Step 1: Apna Real User Dekho Database Mein

```bash
php artisan tinker
```

```php
// Sab admin users dekho
User::where('user_type_id', 1)->get(['id', 'name', 'email']);

// Ya specific user
User::where('email', 'admin@example.com')->first();
```

### Step 2: Credentials File Edit Karo

**File:** `tests/Browser/credentials.php`

```php
return [
    'admin' => [
        'email' => 'admin@solenenergyco.com',  // ← YAHA APNA EMAIL
        'password' => 'YourPassword123',       // ← YAHA APNA PASSWORD
    ],
];
```

### Step 3: Test Run Karo

```bash
php artisan dusk tests/Browser/LoginWithExistingUserTest.php
```

## 📝 Sabse Easy Method:

### Method A: Test Database Use Karo (Recommended)

Test database mein naya user automatically ban jayega:

```php
// Ye automatically user banata hai
$user = User::factory()->create([
    'email' => 'test@example.com',
    'password' => bcrypt('password123'),
]);
```

**Benefit:** Real database affect nahi hoga

### Method B: Real Database Use Karo

`.env.dusk.local` mein:
```env
DB_DATABASE=your_real_database  # Real database name
```

Phir existing user se login:
```php
$browser->visit('/login')
        ->type('email', 'your-real-email@example.com')
        ->type('password', 'your-real-password')
        ->press('Log in');
```

## 🔧 Specific Tests Ke Liye Credentials:

### Customer Test:
```php
// File: tests/Browser/CustomerTest.php
private function setupUser()
{
    $user = User::factory()->create([
        'email' => 'customer-test@example.com',  // ← CHANGE
        'password' => bcrypt('test123'),         // ← CHANGE
    ]);
    return $user;
}
```

### Intake Form Test:
```php
// File: tests/Browser/IntakeFormTest.php
private function setupSalesPerson()
{
    $user = User::factory()->create([
        'email' => 'sales@example.com',          // ← CHANGE
        'password' => bcrypt('sales123'),        // ← CHANGE
    ]);
    return $user;
}
```

### Employee Test:
```php
// File: tests/Browser/EmployeeManagementTest.php
private function setupAdmin()
{
    $user = User::factory()->create([
        'email' => 'admin@example.com',          // ← CHANGE
        'password' => bcrypt('admin123'),        // ← CHANGE
    ]);
    return $user;
}
```

## 🚀 Quick Commands:

### Test Specific User Se:
```bash
# Login test with your credentials
php artisan dusk --filter test_login_with_existing_database_user

# Direct login (no password needed)
php artisan dusk --filter test_direct_login_without_password
```

### Database Check:
```bash
# Check users in test database
mysql -u root -p crm_test -e "SELECT id, name, email FROM users;"

# Check users in real database
mysql -u root -p your_database -e "SELECT id, name, email FROM users;"
```

## 💡 Pro Tips:

### 1. Test Database Use Karo (Safest)
```env
# .env.dusk.local
DB_DATABASE=crm_test  # Separate test database
```

### 2. Real Database Use Karo (For Real Testing)
```env
# .env.dusk.local
DB_DATABASE=your_real_crm_database
```

### 3. Direct Login Use Karo (Fastest)
```php
$browser->loginAs($user)  // Password ki zarurat nahi
```

## 🎯 Recommended Approach:

**Sabse Best Tarika:**

1. **Test database use karo** (`crm_test`)
2. **Factory se user banao** (automatic)
3. **Direct login use karo** (`loginAs()`)

```php
public function test_customer_creation()
{
    $user = User::factory()->create();  // Auto user
    
    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)  // Direct login - no password
                ->visit('/customers/create')
                ->type('first_name', 'John')
                // ... rest of test
    });
}
```

**Ye method:**
- ✅ Password ki zarurat nahi
- ✅ Real database safe rahega
- ✅ Har test fresh user ke saath
- ✅ Fast execution

---

## 🔥 Quick Fix - Abhi Test Karo:

**File:** `tests/Browser/LoginWithExistingUserTest.php`

Line 15-16 mein apna email/password dalo:
```php
->type('email', 'YOUR-EMAIL@example.com')
->type('password', 'YOUR-PASSWORD')
```

**Run:**
```bash
php artisan serve
php artisan dusk tests/Browser/LoginWithExistingUserTest.php
```

Browser khulega aur aapka real user login hoga! 🎉
