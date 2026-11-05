# Payday

Payday is the payroll app for **Adunni Foods Ltd** (Ikeja, Lagos): about fifteen staff across sales,
warehouse, logistics, accounts and admin. HR keeps the staff list and grades up to date, records leave,
and runs payroll at the end of each month. The owner checks the draft and approves it, then the summary
goes to the bank and the accountant. Staff log in to see their own payslips.

Built with PHP 8.3 and Laravel 13 (Blade views, SQLite, no front-end build step).

## Running it

Docker (seeds fresh demo data on every start):

```bash
docker compose up --build
```

Then open http://localhost:5119.

Without Docker:

```bash
composer setup                      # install, .env, app key, fresh database with demo data
php artisan serve --port=5119 --no-reload   # with PHP_CLI_SERVER_WORKERS=4 from .env
```

Reset the demo data at any time with `php artisan migrate:fresh --seed`.

### Demo logins

All passwords are `payday2026`.

| Who | Email | Can do |
| --- | --- | --- |
| Bisi Adeyemi, HR & payroll officer | `bisi@adunnifoods.ng` | Everything except approving payroll |
| Folake Adunni-Bello, owner | `folake@adunnifoods.ng` | Everything, including approving payroll |
| Adaeze Okonkwo, sales officer | `adaeze@adunnifoods.ng` | Her own approved payslips |

The demo data always has the two previous months approved and a draft for the current month, with a
new starter this month, a starter and a leaver last month, a staff loan nearly paid off and some unpaid leave.

## Tests

```bash
php artisan test
```

Tests use an in-memory SQLite database.

## Pay rules

These are the rules Adunni Foods agreed with its accountant. They are deliberately simple and are
**not** the statutory Nigerian PAYE or pension rules; check with the accountant before relying on them
for anything official.

**Grades.** Pay comes from the staff member's grade: basic, housing allowance and transport allowance a
month. Gross pay is the sum of the three. Changing a grade's amounts applies to everyone on that grade
from the next payroll run; payroll that has already been run keeps the amounts it was run with.

**Part months.** Someone who starts or leaves during the month is paid for the calendar days they are on
the books, counting their first and last day: gross for the month x days employed / days in the month.
Someone who starts on the 10th of a 30-day month is paid 21/30.

**Unpaid leave** is taken off for every calendar day it covers in the month, weekends and public holidays included, at the same daily rate.
Four days of unpaid leave from a Friday to a Monday are four days off pay, even though they are only two working days of leave. Annual and sick leave are paid.

**Pension.** The employee contribution is 8% of gross pay for the month (basic + housing + transport,
after any part-month or unpaid-leave reduction). The employer's share is paid separately and does not
appear on the payslip.

**Income tax (PAYE).** Taxable pay is gross pay minus pension. Payday works out the tax on a year of that
month's taxable pay using the table below, then divides by 12.

| Annual taxable income | Rate |
| --- | --- |
| First ₦800,000 | 0% |
| Next ₦1,200,000 (to ₦2,000,000) | 10% |
| Next ₦3,000,000 (to ₦5,000,000) | 15% |
| Next ₦7,000,000 (to ₦12,000,000) | 20% |
| Above ₦12,000,000 | 25% |

So nobody whose taxable pay is ₦66,666.67 a month or less pays tax.

**Staff loans.** Each loan has a monthly repayment. The deduction is the repayment or what is still owed,
whichever is smaller, so the last instalment is often smaller than the others. The balance goes down when
the owner approves the payroll, not when the draft is made.

**Rounding.** All amounts are held in kobo. Every line on a payslip is rounded to the kobo, and net pay is
gross pay minus total deductions.

**Leave.** Everyone gets 20 working days (Monday to Friday) of annual leave per calendar year. Leave is
recorded by HR on the Leave screen, from the first day to the last day of leave, both included.

## Payroll flow

1. HR picks the month on the Payroll screen and clicks **Run payroll**. Payday makes a draft with a payslip
   for everyone employed for at least one day of the month. A month can only be run once; discard the draft
   to run it again.
2. HR and the owner check the draft. **Download summary (CSV)** gives the bank and accountant sheet.
3. The owner clicks **Approve payroll**. Loan balances go down and staff can see the payslips under
   **My payslips**.

## Project layout

| Path | What is there |
| --- | --- |
| `app/Services/PayrollCalculator.php` | One staff member's payslip for one month |
| `app/Services/PayrollService.php` | Making, approving and discarding payroll runs |
| `app/Services/PayPeriod.php` | Payroll months and day counting |
| `app/Services/TaxTable.php` | The PAYE table above |
| `app/Services/PayrollExport.php` | The CSV summary |
| `app/Services/LeaveCalendar.php` | Working days and annual leave balances |
| `app/Support/Money.php` | Kobo to naira and back |
| `app/Http/Controllers/` | One controller per screen |
| `resources/views/` | Blade templates |
| `public/css/app.css`, `public/js/` | Styles and the staff search box |
| `database/seeders/DatabaseSeeder.php` | Demo data |
