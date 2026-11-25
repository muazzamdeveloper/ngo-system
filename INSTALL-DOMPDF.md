# Installing the PDF library (dompdf) and first-time setup

The ID card and the joining letter are PDF files made by the **dompdf** library.
It is not bundled in this project. Until it is installed, the download buttons show
the message "PDF library not installed" and everything else keeps working.

## 1. Install the libraries (once, after unzipping)

Open Command Prompt in the project folder and run:

```cmd
cd E:\xampp\htdocs\ngo_system
composer install
composer require dompdf/dompdf
```

- `composer install` creates the `vendor` folder (the zip does not contain it).
- `composer require dompdf/dompdf` installs the PDF library and writes it into `composer.json`.
- Needs internet. XAMPP's PHP must have the `gd`, `mbstring` and `dom` extensions on
  (they are on in a normal XAMPP). Check with `php -m`.

## 2. Set up the database

1. Start **Apache** and **MySQL** in XAMPP.
2. In phpMyAdmin create an empty database called `ngo_system` (collation `utf8mb4_unicode_ci`).
3. This zip does **not** contain `.env` or the `.git` folder, so unzipping over your project never overwrites your settings or your Git history.
   On a new computer copy your old `.env` (it holds the `APP_KEY` that decrypts stored Aadhaar numbers); only for a brand-new, empty
   install use `copy .env.example .env` and `php artisan key:generate`.
   Make sure `.env` has `DB_DATABASE=ngo_system`, `DB_USERNAME=root` and an empty `DB_PASSWORD=` (XAMPP defaults).
4. Create the tables and the starting data:

```cmd
php artisan migrate --seed
```

`migrate --seed` is safe to run again after every update. **Never run `migrate:fresh`** on a
database that has real data: it deletes everything.

## 3. First start

```cmd
php artisan app:create-super-admin
php artisan serve
```

Open `http://127.0.0.1:8000/login` and log in with the super admin you just created.

## 4. Fill in the site (admin panel)

| Where | What |
|---|---|
| Organization settings | name, logo, site icon, main colour, phone, email, addresses, social links |
| Card Settings | payment QR, fee, card heading and designation, validity, terms, letter texts, note under the form |
| Home page | hero slides, sections, pictures |
| Pages | About us and other pages |
| News ticker / Menus and footer links | headlines, header menu, buttons, footer links |
| Posts / Categories | news posts for the home page |
| Roles and permissions | what other staff may edit (see the groups Website, Posts, Members) |

## 5. Before going live

- Set `APP_ENV=production`, `APP_DEBUG=false` and the real `APP_URL` in `.env`.
- Replace `MAIL_MAILER=log` with real SMTP details so emails are really sent.
- Use a database user with a password instead of `root` with no password.
- Do not change `APP_KEY` once real data exists (Aadhaar numbers are encrypted with it).
- Keep regular backups of the database and of `storage/app/private` (uploaded files).

## Known limits

- Hindi (Devanagari) text does not print in the PDFs with dompdf's standard fonts. Latin text and digits are fine.
- The public blog and post pages are not built yet; posts only appear as cards on the home page.
