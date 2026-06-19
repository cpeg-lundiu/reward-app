# 🐷 Piggy Rewards

A cute, mobile-first family rewards web app for **parents** and **children**.

- **Piggy bank** — each child has a real-money balance. Parents add money; children
  request withdrawals that parents approve. The piggy bank animates on refresh: coins
  drop **in** when the balance grew, slide **out** when it shrank.
- **Stars & tasks** — parents create daily / weekly / monthly / one-time tasks worth
  stars. Children see a month **calendar**, mark tasks done, and parents approve to
  award stars.
- **Rewards** — parents publish a star-priced catalog. Children claim rewards
  (stars spent immediately); parents mark claims complete (or reject to refund).
- **Currency** — each parent picks a family currency at sign-up and can change it
  later with an exchange rate, which converts all balances.

## Stack

PHP (Slim 4) · pure PHP templates (`slim/php-view`) · MySQL (PDO) ·
Tailwind CSS (standalone CLI) · clean **Controller → Service → Repository** layering.

## Requirements

- PHP 8.1+ with `pdo_mysql`
- Composer
- MySQL 8/9
- (Build only) the Tailwind standalone CLI binary — already downloaded as `tailwindcss.exe`

## Setup

1. **Install dependencies**

   ```bash
   composer install
   ```

2. **Configure the database** — copy `.env.example` to `.env` and fill in your
   connection details:

   ```
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_NAME=rewards
   DB_USER=rewards
   DB_PASS=yourpassword
   ```

3. **Create the tables**

   ```bash
   php database/migrate.php
   ```

   (Re-running this resets the schema — it drops and recreates all tables.)

4. **Build the CSS** (only needed if you change templates/styles)

   ```bash
   ./tailwindcss -c tailwind/tailwind.config.js -i tailwind/input.css -o public/css/app.css --minify
   # add --watch while developing
   ```

5. **Run the app**

   ```bash
   php -S 127.0.0.1:8080 -t public
   ```

   Open http://127.0.0.1:8080 — register a parent account to get started.

## How it works

1. A **parent** registers (email + password + family currency).
2. The parent adds **children** (each gets a login username + a preset password).
3. A **child** logs in with their username and is forced to choose their own
   password on first login.
4. Parents add balance, create tasks, and publish rewards; children watch their
   piggy bank grow, complete tasks for stars, and claim rewards.

## Project layout

```
config/        settings (loads .env)
database/      schema.sql + migrate.php (PDO migration runner)
public/        front controller (index.php), compiled CSS, piggy.js
src/
  Controllers/ HTTP layer (Auth, Parent, Child)
  Services/    business logic + DB transactions
  Repositories/ all SQL
  Middleware/  auth, role, forced-password-change, CSRF
  Support/     Auth, Flash, Csrf, Money, PasswordPolicy
templates/     pure PHP views (layout + auth/parent/child)
tailwind/      Tailwind config + input CSS
```

## Security notes

- Passwords are hashed with `password_hash()` and must meet a strong-password policy
  (8+ chars, upper, lower, number, symbol).
- All state-changing requests require a per-session CSRF token.
- Every query is scoped to the logged-in family — parents can only see/act on their
  own children's data.
