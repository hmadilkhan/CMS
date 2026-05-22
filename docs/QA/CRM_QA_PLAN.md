# CRM QA Plan

Use this plan twice every month to keep the CRM stable before real users hit every workflow. The goal is to know the current health of the system, catch broken pages quickly, and build automated coverage around the modules that matter most.

## Monthly Schedule

Run two QA cycles every month:

1. Cycle A: Full regression, usually between the 1st and 3rd.
2. Cycle B: Release/readiness regression, usually between the 15th and 17th.

If a deployment is planned, run Cycle B before deployment and again after deployment on production or staging.

## Baseline Commands

Run these first and save the output in the QA log:

```bash
php artisan about
php artisan route:list
php artisan test
npm run build
```

Also check:

```bash
php artisan migrate:status
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

## Current Baseline Notes

As of 2026-05-05, `php artisan test` reaches the database but is not clean:

- 2 tests pass.
- 22 tests fail.
- Most failures come from user password hashing configuration in tests: `Could not verify the hashed value's configuration.`
- Some starter tests expect `/` or `/register` to return 200, but this CRM redirects those routes.

Before relying on automated tests, fix the test environment and update starter tests to match CRM behavior.

## Cycle A: Full Regression

### Authentication And Access

- Login with Super Admin, Admin, Employee, Sales Person, and Technician users.
- Verify failed login shows validation error.
- Verify logout works.
- Verify `/dashboard` redirects Super Admin to `/admin-dashboard`.
- Verify each role only sees permitted sidebar menus.
- Verify protected routes redirect guests to login.
- Verify impersonation works only for Super Admin.

### Dashboard

- Open admin dashboard.
- Open regular dashboard.
- Confirm cards, charts, counts, and date filters load.
- Confirm no component shows a 500 error or blank Livewire state.

### User Management

- Create user.
- Update user details.
- Delete/deactivate user.
- Create role.
- Assign permissions to role.
- Assign direct permissions to user.
- Confirm permission changes affect sidebar and route access.

### Employees

- Create employee with departments.
- Update employee.
- Upload/change employee image.
- Delete employee.
- Test department employee AJAX endpoint.
- Confirm invalid data returns validation errors.

### Customers

- Create customer for each finance option type.
- Test required conditional fields such as loan/dealer fee fields.
- Add module, inverter, adders, sales partner, subcontractor.
- Edit customer.
- Delete customer.
- Test customer lookup AJAX endpoints.
- Test customer email sending with mail faked or on staging.

### Intake Form

- Create intake record.
- Edit intake record.
- Delete intake record.
- Confirm conversion or relationship to customer/project if used by workflow.

### Projects

- Create project from customer.
- Open project list and project detail.
- Filter/search project list.
- Move project through department and subdepartment workflow.
- Assign employee task.
- Save department notes.
- Save call logs.
- Load call scripts and email scripts.
- Upload and delete project files.
- Add/update project adders.
- Toggle adders lock.
- Update project status.
- Generate project acceptance PDF.
- Submit project acceptance action.
- Update follow-up status.

### Operations

Test create, edit, delete, and validation for:

- Module Types
- Dealer Fee
- Tools
- Office Cost
- Labor Cost
- Adders
- Adder Types
- Inverter Types
- Inverter Base Cost / Redline Cost
- Sales Partners
- Subcontractors
- Finance Options
- Loan Terms
- Call Scripts
- Email Scripts
- Utility Company
- Assign Department

### Reports

- Profitability report: filter, view, Excel export, PDF export.
- Forecast report: filter, view, Excel export, PDF export.
- Override report: filter by sales partner, view, Excel export, PDF export.
- Transaction report: date filters, view, Excel export, PDF export.
- Report Builder: select fields, filters, calculated fields, save report.
- Report Runner: load saved report, run, export.

### Tickets And Service Tickets

- Create old/new ticket if both are still active.
- Change ticket status.
- Create service ticket from project.
- Assign service ticket.
- Update priority/status.
- Add comments.
- Upload files with ticket and comments.
- Delete files.
- Open employee dashboard and admin dashboard.
- Open ticket detail and admin detail.

### Site Surveys And Technician App

- Open schedule form from project.
- Fetch available slots.
- Schedule survey.
- Verify technician receives assignment.
- Technician app loads assigned surveys.
- Update technician location.
- Start survey.
- Complete survey.
- Verify calendar behavior on staging with test calendar.

### Email, IMAP, Notifications

- Fetch department emails on staging/test account.
- Show emails from CRM.
- Show website emails.
- Verify email attachments display.
- Verify notifications list.
- Mark one notification read.
- Mark all notifications read.

## Cycle B: Focused Regression

Run all baseline commands, then test:

- Login/logout.
- Sidebar pages open without 500.
- Customer create/edit.
- Project create/detail/move/upload notes.
- Service ticket create/comment/status.
- Reports open and one export works.
- Site survey available slots and schedule.
- Notifications read/unread.
- Any modules changed since Cycle A.

## Cleanup Checklist

Review these every Cycle A:

- Remove active `dd()`, `dump()`, `var_dump()`, and test debug output.
- Remove or protect browser-accessible debug routes.
- Remove old backup controllers/views after confirming they are unused.
- Review duplicate route names.
- Review public files such as `.zip`, `.sql`, and debug scripts.
- Confirm `.env` is not committed.
- Confirm production has `APP_DEBUG=false`.
- Confirm storage symlink is created by deployment, not by a public route.

## Known Cleanup Candidates

- `routes/web.php`: `/storage-link`
- `routes/web.php`: `test-google-calendar`
- `routes/web.php`: old `projects-move`
- `app/Livewire/Project/ProjectFields/EditFields.php`: active `dd()`
- `app/Livewire/Project/NotesSection.php`: active `dd()`
- `app/Http/Controllers/EmployeeControllerBackup.php`
- `routes/web_backup.php`
- `resources/views/projects/show_copy.blade.php`
- `resources/views/projects/project-list-backup.blade.php`
- `debug_check.php`
- `debug_columns.php`
- `public/build.zip`

## QA Log Template

Use this for every cycle:

```text
Cycle:
Date:
Tester:
Branch/commit:
Environment:
Database snapshot:

Commands:
- php artisan about:
- php artisan route:list:
- php artisan test:
- npm run build:

Modules tested:
- Auth:
- Dashboard:
- User Management:
- Employees:
- Customers:
- Intake Form:
- Projects:
- Operations:
- Reports:
- Tickets:
- Site Surveys:
- Email/Notifications:

Issues found:
1.
2.
3.

Fixed during cycle:
1.
2.

Deferred:
1.
2.

Release decision:
```

## Automation Roadmap

Build automated tests in this order:

1. Fix current auth/profile tests.
2. Add factories for roles, permissions, departments, subdepartments, customers, projects, employees, tickets, and surveys.
3. Add smoke tests for every sidebar route.
4. Add permission tests for each role.
5. Add CRUD tests for Customers, Employees, Projects, Operations, Tickets.
6. Add workflow tests for Customer to Project to Ticket to Report.
7. Fake mail, notification, storage, calendar, and IMAP in tests.
