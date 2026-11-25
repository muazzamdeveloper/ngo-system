# Sarv Abhyudaya Foundation – NGO Management System
## Setup, run, GitHub and Hostinger guide

This guide covers everything in order: what the zip contains, installing libraries, creating `.env`,
connecting MySQL, running the server, creating the super admin, every page link, and how to put the
project on GitHub and Hostinger and keep updating it.

Commands are shown for Windows (XAMPP / Command Prompt) and Linux/Mac (Terminal). Only the
command differs; the Laravel commands are the same everywhere.

---

## 1. What is in the zip (and what is not)

| Included | Not included (you create it) |
|---|---|
| All source code, views, CSS, JavaScript, routes, migrations, seeders, tests | `.env` (your secrets and database login – create it in step 4) |
| `composer.json` and `composer.lock` | `vendor/` folder (created by `composer install`) |
| `.env.example` (template for `.env`) | Uploaded files, logs and caches (created when the system runs) |
| This guide and `INSTALL-DOMPDF.md` | `.git` folder (created in step 9) |

**Important:** `APP_KEY` in `.env` encrypts stored Aadhaar numbers. Once data exists, never change or
lose `APP_KEY`. Back it up somewhere safe.

---

## 2. Requirements

- **PHP 8.2 or newer.** PHP 8.3 is recommended (this project is tested on 8.3). PHP 8.5 is supported.
- **PHP extensions:** `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`,
  `gd`, `dom`, `curl`. XAMPP has all of these on by default.
- **MySQL 8.0+** or **MariaDB 10.6+** (you will connect this project to a new database; see step 3).
- **Composer 2** – https://getcomposer.org
- **Git** – https://git-scm.com (needed for GitHub, step 9).
- Node.js is **not** required. The public site uses plain CSS and JavaScript files.

Check PHP and extensions:

```bash
php -v
php -m
```

---

## 3. Create the MySQL database

### Option A – phpMyAdmin (XAMPP, Windows or Linux)
1. Start **Apache** and **MySQL** in XAMPP.
2. Open http://localhost/phpmyadmin → **Databases** → name `ngo_system`, collation **utf8mb4_unicode_ci** → **Create**.
3. Optional but recommended: **User accounts** → **Add user account** → user `ngo_user`, host `localhost`,
   a strong password, and grant it all privileges on `ngo_system`.

### Option B – command line
```bash
mysql -u root -p
```
```sql
CREATE DATABASE ngo_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ngo_user'@'localhost' IDENTIFIED BY 'ChooseAStrongPassword#2026';
GRANT ALL PRIVILEGES ON ngo_system.* TO 'ngo_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

On a new Hostinger plan, create the database and its user in **hPanel → Databases → MySQL Databases**, then
use the database name, user and password it shows you in step 8.

---

## 4. Install the libraries (run once after unzipping)

Open a terminal **inside the project folder** (the folder that contains `artisan`).

```bash
cd ngo_system
composer install
composer require dompdf/dompdf
```

- `composer install` creates the `vendor/` folder from `composer.lock`.
- **Important:** `composer.json` already lists `dompdf/dompdf`, but `composer.lock` does not include it yet.
  Run `composer require dompdf/dompdf` (not only `composer install`) once, so Composer adds it to the lock file.
  Then commit both `composer.json` and `composer.lock`. If you run only `composer install`, Composer warns that
  the lock file is out of date and skips dompdf.
- `composer require dompdf/dompdf` installs the PDF library used for the **ID card** and **joining letter**.
  Until it is installed, the PDF download buttons show "PDF library not installed"; everything else works.
  If it fails with a network error, see `INSTALL-DOMPDF.md`.

---

## 5. Create the `.env` file

Windows (Command Prompt):
```cmd
copy .env.example .env
```

Linux / Mac:
```bash
cp .env.example .env
```

Generate the application key (this fills `APP_KEY`):
```bash
php artisan key:generate
```

Open `.env` in any text editor and set these lines.

**Local computer (XAMPP):**
```env
APP_NAME="Sarv Abhyudaya Foundation"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ngo_system
DB_USERNAME=ngo_user
DB_PASSWORD=ChooseAStrongPassword#2026

MAIL_MAILER=log
```

**Live Hostinger site:**
```env
APP_NAME="Sarv Abhyudaya Foundation"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_hostinger_db_name
DB_USERNAME=your_hostinger_db_user
DB_PASSWORD=your_hostinger_db_password

MAIL_MAILER=smtp
MAIL_HOST=smtp.yourprovider.com
MAIL_PORT=587
MAIL_USERNAME=your_mail_user
MAIL_PASSWORD=your_mail_password
MAIL_FROM_ADDRESS=info@yourdomain.com
MAIL_FROM_NAME="Sarv Abhyudaya Foundation"
```

Notes:
- `DB_HOST=127.0.0.1` works on both XAMPP and most shared hosting. If Hostinger shows a different
  database host in hPanel, use that value.
- Use `MAIL_MAILER=log` until real SMTP details are available. Emails are then written to `storage/logs`
  instead of being sent. Mail settings can also be changed later in the admin panel (**SMTP settings**).
- Never commit `.env` to GitHub. The project's `.gitignore` already blocks it.

---

## 6. Commands to run before starting the server

Run these in the project folder, in this order, the first time only:

```bash
php artisan migrate --seed
php artisan storage:link
php artisan app:create-super-admin
```

| Command | What it does |
|---|---|
| `php artisan migrate --seed` | Creates all tables and the starting data (roles, permissions, default pages and policies). |
| `php artisan storage:link` | Links the public uploads folder so uploaded images show on the site. |
| `php artisan app:create-super-admin` | Creates the one super admin account (step 7). |

**Never run `php artisan migrate:fresh` on a database with real data.** It deletes everything.
After every update, run only `php artisan migrate --seed` (it is safe to repeat).

On Linux or a Hostinger server, the `storage` and `bootstrap/cache` folders must be writable:

```bash
chmod -R 775 storage bootstrap/cache
```

Optional check that everything works (runs the 377 automated tests):
```bash
php artisan test
```

---

## 7. Create the super admin

The super admin controls access for everyone: it can create staff accounts, set each person's module
access, and manage roles. Only one super admin is allowed.

```bash
php artisan app:create-super-admin
```

The command asks for:
1. **Full name**
2. **Email address** (this is the login email)
3. **Password** (minimum 10 characters; typing is hidden)
4. **Confirm password**

If a super admin already exists, the command stops and creates nothing.

After it succeeds:
1. Open **http://127.0.0.1:8000/login** (local) or **https://yourdomain.com/login** (live).
2. Sign in with that email and password.
3. You are taken to **/admin/dashboard**.

---

## 8. Run the server

```bash
php artisan serve
```

Then open **http://127.0.0.1:8000** in your browser. Keep the terminal open while using the site; press
**Ctrl + C** to stop it.

On Hostinger the site runs automatically through the domain, so you do not start a server there (see step 10).

---

## 9. Page links

### Public website

| Page | Link |
|---|---|
| Home page | `/` (there is no `/home` address; the home page is the root) |
| Login | `/login` |
| Membership application (Apply Now) | `/apply` |
| Application thank-you | `/apply/thanks` |
| Donate | `/donate` |
| Volunteer registration | `/volunteer` |
| Verify an ID card / letter | `/verify` |
| Contact Us (form) | `/page/contact-us` |
| About Us | `/page/about-organisation` |
| Leadership Desk | `/page/leadership-desk` |
| Our Team (management team) | `/page/management-team` |
| Our General Members | `/page/our-general-members` |
| Our Volunteers / Interns | `/page/our-volunteers` |
| Our Supporters / Donors | `/page/our-supporters` |
| News & Activities | `/page/news-activities` |
| Certifications | `/page/certifications` |
| Official Documents | `/page/official-documents` |
| Campaigns (crowdfunding) | `/campaigns` |
| Projects | `/projects` |
| Events | `/events` |
| Press releases | `/press-releases` |
| Awards | `/awards` |
| Photo gallery | `/gallery` |
| Video gallery | `/videos` |
| Terms & Conditions | `/policy/terms` |
| Privacy Policy | `/policy/privacy` |
| Refund & Cancellation | `/policy/refund` |
| Legal Disclaimer | `/policy/disclaimer` |
| Shipping & Delivery | `/policy/shipping` |

### Member account (after a member logs in)
| Page | Link |
|---|---|
| Member home | `/account` |
| Edit profile | `/account/profile` |
| Change password | `/account/password` |

### Admin panel (staff and super admin log in at `/login`)
| Page | Link | Who |
|---|---|---|
| Dashboard | `/admin/dashboard` | Anyone with access |
| Members | `/admin/members` | Per access |
| Applications (membership) | `/admin/applications` | Per access |
| Membership payments | `/admin/payments` | Per access |
| Donations | `/admin/donations` | Per access |
| Volunteers | `/admin/volunteers` | Per access |
| Card settings (ID card design) | `/admin/card-settings` | Per access |
| Website pages content (editable text and images) | `/admin/content` | Per access |
| Contact messages (inbox) | `/admin/contact-messages` | Per access |
| Home page sections | `/admin/home-sections` | Per access |
| Site configuration | `/admin/site-config` | Per access |
| Settings | `/admin/settings` | Per access |
| Payment settings & donation QR | `/admin/payment-settings` | Per access |
| Payment gateways | `/admin/gateways` | Per access |
| SMTP (email) settings | `/admin/smtp` | Per access |
| Ticker (news strip) | `/admin/ticker` | Per access |
| Site links | `/admin/site-links` | Per access |
| Policies (terms, privacy, etc.) | `/admin/policies` | Per access |
| Login activity | `/admin/login-activity` | Per access |
| Staff accounts | `/admin/users` | Super admin |
| Roles | `/admin/roles` | Super admin |
| **User access (per-person module permissions)** | `/admin/access` | **Super admin only** |
| Security settings | `/admin/security` | Super admin |
| Profile (your own) | `/admin/profile` | Anyone |

Campaigns, events, projects, gallery, videos, awards and press releases are managed from the admin sidebar.

---

## 10. Give staff access (super admin)

1. Sign in as the super admin → **Users** (`/admin/users`) → **Create user**. Choose the starting role.
2. Open **User access** (`/admin/access`), find the person, and tick only the modules they may open.
3. Each person has their own tick list, so two managers with the same role can have different modules.
4. The **Coordinator presets** button fills a standard list for a role; you can then adjust it per person.

---

## 11. Create the GitHub repository and upload the project

### 11.1 Create an empty repository on GitHub
1. Log in at https://github.com → **New repository**.
2. Name it, for example `ngo-system`. Choose **Private** (recommended).
3. Do **not** tick "Add a README" or ".gitignore" (the project already has them).
4. Click **Create repository** and copy its address, for example
   `https://github.com/YOUR-USERNAME/ngo-system.git`.

### 11.2 Upload the project from your computer
Open a terminal **inside the project folder** (the folder with `artisan`):

```bash
git init -b main
git add .
git status
git commit -m "Initial commit: NGO management system"
git remote add origin https://github.com/YOUR-USERNAME/ngo-system.git
git push -u origin main
```

- Before committing, check the `git status` list. It must **not** show `.env`, `vendor/`, `storage/logs/`,
  or uploaded files. The `.gitignore` already excludes them.
- GitHub will ask you to sign in. Use `gh auth login` (GitHub CLI), or a **Personal Access Token** as the
  password (GitHub → Settings → Developer settings → Personal access tokens).
- Windows may show "CRLF will be replaced by LF" warnings. They are harmless.

---

## 12. The everyday workflow: make a change, then push to GitHub and Hostinger

Use this routine every time you change something.

### On your computer
```bash
# 1. Get the latest version first (important when more than one person works on it)
git pull

# 2. Make your changes in the files (code, views, CSS, etc.)

# 3. Test locally
php artisan migrate --seed     # only if you added a migration
php artisan test               # optional, but recommended
php artisan serve              # check the page in your browser

# 4. Save the change to GitHub
git add -A
git status                     # confirm only the files you meant to change are listed
git commit -m "Describe what you changed, e.g. Fix donate page amount buttons"
git push
```

Never edit files directly on GitHub's website for this project, and never copy `.env` into Git.

### On Hostinger (after each push)
Connect with SSH (hPanel → **Advanced → SSH Access**, then use the SSH details it shows), then run:

```bash
cd ~/domains/yourdomain.com/ngo_system      # the folder your site was cloned into (step 13)
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Keep `.env`, `storage/app/private` and `storage/app/public` on the server only. A `git pull` never touches
them, because they are not in Git.

---

## 13. Put the project on Hostinger

These steps assume a Hostinger plan with **SSH access** and **PHP 8.2 or newer**. Menu names in hPanel
change over time; if a name differs, search hPanel's help for "SSH" or "document root".

### 13.1 Prepare the PDF library first (on your computer)
PDF downloads need dompdf in `composer.json`. Run `composer require dompdf/dompdf` locally (step 4), then
commit and push the updated `composer.json` and `composer.lock`. The server then installs it with
`composer install`.

### 13.2 Choose PHP and create the database
1. hPanel → **Advanced → PHP Configuration** → select **PHP 8.3** for your domain.
2. hPanel → **Databases → MySQL Databases** → create a database and a user with a password. Note the names.

### 13.3 Clone the project onto the server
Connect with SSH, then:

```bash
cd ~/domains/yourdomain.com
git clone https://github.com/YOUR-USERNAME/ngo-system.git ngo_system
cd ngo_system
composer install --no-dev --optimize-autoloader
```

If the repository is private, GitHub will ask for your username and a Personal Access Token as the password.

### 13.4 Point the domain to the `public` folder
In hPanel, open the domain's settings and set its **document root** to the `public` folder of the project,
for example `domains/yourdomain.com/ngo_system/public`. This keeps `.env`, `vendor/` and other project files
out of the public internet. Without this step, the site will not load correctly.

### 13.5 Create `.env` on the server
```bash
cp .env.example .env
php artisan key:generate
nano .env          # set the production values from step 5 (Hostinger database, APP_URL=https://yourdomain.com)
```

Copy the `APP_KEY` line somewhere safe, outside the server.

### 13.6 First-time setup on the server
```bash
php artisan migrate --seed
php artisan storage:link
php artisan app:create-super-admin
chmod -R 775 storage bootstrap/cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Then open **https://yourdomain.com/login** and sign in as the super admin.

### 13.7 Turn on HTTPS
hPanel → **SSL** → install the free certificate for the domain, and set `APP_URL` to `https://` in `.env`.

---

## 14. Backups (do this regularly)

- **Database:** phpMyAdmin (or hPanel → Databases → Backup) → export the `ngo_system` database.
- **Uploaded files:** copy `storage/app/public` and `storage/app/private` (member documents, site photos).
- **`.env`:** keep a copy with `APP_KEY` in a password manager. Without it, Aadhaar numbers cannot be read.

---

## 15. Common problems

| Problem | Fix |
|---|---|
| "PDF library not installed" | Run `composer require dompdf/dompdf` (step 4). |
| "No application encryption key" | Run `php artisan key:generate` and check `APP_KEY` in `.env`. |
| "SQLSTATE[HY000] [2002] Connection refused" | MySQL is not running, or `DB_HOST`/`DB_PORT` are wrong. |
| "Access denied for user" | Check `DB_USERNAME` and `DB_PASSWORD` in `.env`. |
| Page shows an error on the server | Check `storage/logs/laravel.log`. Keep `APP_DEBUG=false` on the live site. |
| Uploaded images do not show | Run `php artisan storage:link`. |
| Changes do not appear on Hostinger | Run `php artisan optimize:clear` and then the cache commands in step 12. |
| "Permission denied" on storage | Run `chmod -R 775 storage bootstrap/cache`. |
| Staff can't open a module | Super admin → **User access** → tick the module for that person. |

---

## 16. Security checklist

Already built into the system:
- Member documents (ID card, joining letter, Aadhaar images, payment screenshots) are stored in `storage/app/private`,
  which the web cannot reach directly. They are served only through permission-checked routes.
- Public verification downloads need a signed link that expires after 15 minutes. Membership checks are rate limited.
- Aadhaar numbers are encrypted in the database. Aadhaar images need the "view Aadhaar" permission.
- Login, donation, contact, volunteer and application forms are rate limited. Uploads are checked for type and size.
- Browser security headers (frame blocking, no MIME sniffing, referrer and permissions policy, HTTPS-only HSTS) are sent on every page.
- Only the super admin can change Google Analytics, Facebook pixel and Search Console codes, because they run on every public page.

Your responsibilities on the live site:
- Set `APP_ENV=production`, `APP_DEBUG=false`, and `APP_URL=https://...`. Debug mode shows secrets on error pages.
- Point the domain to the `public` folder. A `.htaccess` in the project root blocks private folders and `.env` as a backup.
- Keep `.env` out of Git and out of public downloads. Back up `APP_KEY` safely.
- Use HTTPS (hPanel → SSL). Use strong passwords (10+ characters) for the super admin and staff.
- Give staff only the modules they need (User access). Review Login activity regularly.
- Back up the database and the `storage/app/private` and `storage/app/public` folders.

## 17. Quick reference

```bash
# First time, on a new computer
composer install
composer require dompdf/dompdf
cp .env.example .env            # Windows: copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan app:create-super-admin
php artisan serve               # open http://127.0.0.1:8000

# Every day
git pull
php artisan serve
git add -A && git commit -m "message" && git push
```
