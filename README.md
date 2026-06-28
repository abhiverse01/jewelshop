# Jewellery Shop - PHP E-Commerce

## Project Structure

```
jewellery-shop/
├── config/
│   └── db.php              # Database connection & table creation
├── includes/
│   ├── header.php           # Shared header with navigation
│   └── footer.php           # Shared footer
├── assets/
│   ├── css/
│   │   └── style.css        # All styles (extracted from inline)
│   ├── js/
│   │   └── main.js          # Form validation & mobile menu
│   └── images/              # Uploaded product images
│       └── .gitkeep
├── pages/                   # (Reserved for future expansion)
├── index.php                # Homepage
├── products.php             # Product listing grid
├── add_product.php          # Add new product form
├── login.php                # User login
├── register.php             # User registration
├── logout.php               # Session destroy & redirect
├── seed.sql                 # Sample data (products + demo user)
├── .htaccess                # Apache security rules
└── README.md                # This file
```

## Setup

1. **Prerequisites**: PHP 7.4+ with `mysqli` extension, MySQL/MariaDB, Apache (or any PHP server).

2. **Deploy**: Copy all files to your web server root (e.g., `htdocs/` or `/var/www/html/`).

3. **Database**: The app auto-creates the database (`jewellery_shop`) and tables (`users`, `products`) on first visit via `config/db.php`.

4. **Seed data** (optional):
   ```bash
   mysql -u root jewellery_shop < seed.sql
   ```
   This adds 5 sample products and a demo user (`admin@jewelleryshop.com` / `password123`).

5. **Permissions**: Ensure the `assets/images/` folder is writable by the web server:
   ```bash
   chmod 755 assets/images/
   ```

## Bug Fixes from Original

| Issue | Fix |
|-------|-----|
| `products` table was never created | Added to `config/db.php` |
| Column mismatch (`username` vs `name`) | Unified to `name` across all files |
| `products.php` was completely empty | Full product grid with image display |
| SQL injection in `login.php` | Prepared statements everywhere |
| Plain-text passwords | `password_hash()` + `password_verify()` |
| No consistent navigation | Shared `header.php` / `footer.php` |
| All CSS inline | Extracted to `assets/css/style.css` |
| No JS validation | Added `assets/js/main.js` |
| No file upload validation | MIME type + size checks added |
| **`$conn` used before `db.php` loaded** (products/login/register/add_product) | **Each page now bootstraps `config/db.php` + `session_start()` at the top, before any DB or session access.** |
| **`$_SESSION` writes lost in `login.php`** | **`session_start()` is now called BEFORE writing `$_SESSION`.** |
| **`add_product.php` always redirected to login** | **Auth check now runs after `session_start()`, so logged-in users can actually reach the page.** |
| **`.htaccess` used deprecated Apache 2.2 `Order/Deny`** | **Switched to Apache 2.4 `Require all denied` with 2.2 fallback inside `<IfModule>`.** |
| **Missing `.input-error` CSS class** | **Added rule (was referenced by `main.js` but never defined).** |
| `session_start()` not idempotent in `header.php` | Wrapped in `session_status()` check. |
| File extension case inconsistency | Lowercased uploaded file extensions. |
| Dead "Registration successful" alert on `register.php` | Removed (redirect goes to `login.php?registered=1`, so the block was unreachable). |

See `CODE_REVIEW.md` for the full review, security audit, and architectural suggestions.

## License

This project is for educational purposes.