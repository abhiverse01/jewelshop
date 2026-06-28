# Local Setup Guide

Quick-start options for running the Jewellery Shop locally. Pick whichever fits your OS.

---

## Option A — PHP built-in server (fastest, no Apache needed)

Prerequisites: PHP 7.4+ with `mysqli` and `fileinfo` extensions, MySQL or MariaDB.

### 1. Start MySQL

```bash
# Linux / macOS (Homebrew)
sudo systemctl start mysql       # or: brew services start mysql
# Windows: launch the "MySQL80" service from Services.msc
```

### 2. (Optional) Confirm a root user with no password exists

```bash
mysql -u root
```

If your root user has a password, edit `config/db.php` and set `$password = "yourpassword";`.

### 3. Run the PHP built-in server

From the project root:

```bash
php -S localhost:8000
```

### 4. Open the app

Visit <http://localhost:8000/> in your browser.

- The database `jewellery_shop` and its tables are created automatically on first visit.
- (Optional) Seed sample data:

  ```bash
  mysql -u root jewellery_shop < seed.sql
  ```

  This adds 5 sample products and a demo user (`admin@jewelleryshop.com` / `password123`).

### 5. Smoke-test the fixed flows

- `http://localhost:8000/products.php` — should load without fatal error.
- `http://localhost:8000/register.php` — register a new user; should redirect to `login.php?registered=1`.
- `http://localhost:8000/login.php` — log in; should set a session cookie and show "Hi, \<name\>" in the nav.
- `http://localhost:8000/add_product.php` while logged in — should show the form (NOT redirect to login).
- Submit a product with an image — should save and redirect to `products.php` showing the new card.

---

## Option B — XAMPP / MAMP / WAMP (all-in-one)

1. Install XAMPP (Windows/Linux) or MAMP (macOS).
2. Copy the entire `jewellery-shop/` folder into the htdocs / www directory:
   - **XAMPP (Windows/Linux):** `C:\xampp\htdocs\jewellery-shop\` or `/opt/lampp/htdocs/jewellery-shop/`
   - **MAMP (macOS):** `/Applications/MAMP/htdocs/jewellery-shop/`
3. Start Apache + MySQL from the XAMPP/MAMP control panel.
4. Visit <http://localhost/jewellery-shop/>.
5. (Optional) Seed via phpMyAdmin: open phpMyAdmin → select the `jewellery_shop` database → Import → choose `seed.sql` → Go.

---

## Option C — VS Code + Dev Containers (reproducible)

If you have Docker + the VS Code "Dev Containers" extension, you can run the whole stack in a container. Create a `Dockerfile` + `.devcontainer/devcontainer.json` (not included — let me know if you want me to add one).

---

## Troubleshooting

| Symptom | Fix |
|---|---|
| `Fatal error: Call to undefined function mysqli_init()` | Enable the `mysqli` extension in `php.ini` (uncomment `extension=mysqli`). |
| `Connection Failed: Access denied for user 'root'@'localhost'` | Either set a password in `config/db.php`, or create a matching DB user. |
| Image upload fails with `Failed to upload image` | Check `assets/images/` is writable (`chmod 755 assets/images/`) and `php.ini` has `file_uploads = On` and `upload_max_filesize >= 5M`. |
| `Warning: session_start(): Session already started` | Already fixed in this version (header.php uses `session_status()` check). If you still see it, you have a PHP < 5.4 — upgrade. |
| Pages 404 on Apache but work on built-in server | Make sure `AllowOverride All` is set for the project directory in your Apache vhost, so `.htaccess` is honored. |

---

## Project Layout (after this fix)

```
jewellery-shop/
├── .vscode/                # Recommended extensions + launch configs
│   ├── extensions.json
│   ├── launch.json
│   └── settings.json
├── .gitignore
├── .htaccess               # Apache 2.4-compatible security rules
├── CODE_REVIEW.md          # Detailed review, security audit, architecture notes
├── LOCAL_SETUP.md          # This file
├── README.md               # Original project README (updated with fix list)
├── assets/
│   ├── css/style.css       # + new .input-error rule
│   ├── js/main.js
│   └── images/             # uploaded product images (gitignored)
├── config/
│   └── db.php              # DB connection + auto table creation
├── includes/
│   ├── header.php          # session_start() now idempotent
│   └── footer.php
├── pages/                  # reserved for future expansion
├── add_product.php         # bootstraps session + DB at top
├── index.php
├── login.php               # bootstraps session + DB at top
├── logout.php
├── products.php            # header.php required first (was broken)
├── register.php            # bootstraps session + DB at top
└── seed.sql                # 5 sample products + 1 demo user
```
