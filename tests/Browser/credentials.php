<?php

/**
 * ============================================
 * BROWSER TESTING CREDENTIALS
 * ============================================
 * 
 * Yaha apne REAL credentials dalo jo aap
 * browser testing mein use karna chahte ho
 */

return [
    
    /**
     * ADMIN USER CREDENTIALS
     * Ye credentials login test mein use honge
     */
    'admin' => [
        'email' => 'admin@example.com',      // ← YAHA APNA EMAIL DALO
        'password' => 'admin123',            // ← YAHA APNA PASSWORD DALO
    ],

    /**
     * SALES PERSON CREDENTIALS
     * Intake form tests ke liye
     */
    'sales_person' => [
        'email' => 'sales@example.com',      // ← YAHA SALES USER EMAIL
        'password' => 'sales123',            // ← YAHA SALES USER PASSWORD
    ],

    /**
     * EMPLOYEE CREDENTIALS
     * Employee tests ke liye
     */
    'employee' => [
        'email' => 'employee@example.com',   // ← YAHA EMPLOYEE EMAIL
        'password' => 'employee123',         // ← YAHA EMPLOYEE PASSWORD
    ],

    /**
     * SERVICE MANAGER CREDENTIALS
     * Service ticket tests ke liye
     */
    'service_manager' => [
        'email' => 'service@example.com',    // ← YAHA SERVICE MANAGER EMAIL
        'password' => 'service123',          // ← YAHA SERVICE MANAGER PASSWORD
    ],

    /**
     * USE EXISTING DATABASE USER?
     * Agar true hai toh database se user fetch hoga
     * Agar false hai toh test mein naya user banega
     */
    'use_existing_users' => false,  // true kar do agar database users use karne hain

    /**
     * TEST DATABASE SETTINGS
     */
    'database' => [
        'connection' => 'mysql',
        'database' => 'crm_test',
        'reset_after_tests' => true,  // Har test ke baad database reset ho
    ],

];
