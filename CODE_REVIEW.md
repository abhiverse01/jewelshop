# Jewellery Shop — Code Review & Fixes

**Reviewed:** `jewellery-shop-restructured.zip`
**Workspace:** `/home/z/my-project/workspace/jewellery-shop/`
**Reviewer date:** 2026-06-28

---

## Executive Summary

The codebase is well-structured — clean separation of `config/`, `includes/`, `assets/`, and per-page PHP files, with shared header/footer, prepared statements, hashed passwords, and a responsive layout. The README's "Bug Fixes from Original" table is accurate as far as it goes.

**However**, the restructured code introduced a new class of bug that completely breaks the application at runtime: **every PHP file that handles POST requests or uses `$_SESSION` accesses those resources BEFORE `header.php` is loaded** — and `header.php` is where `session_start()` and `config/db.php` (which creates `$conn`) live. As shipped, the app would crash with a fatal error on every POST and on `products.php`, and the `add_product.php` page was unreachable even for logged-in users.

All five critical bugs are fixed in this workspace. Details below.

> **Note on testing environment:** the sandbox I am running in does not have PHP or MySQL installed and I cannot install them without root. The bugs identified here were found by careful static analysis of the include order, and were verified by reading the relevant lines in each file. They are deterministic — any standard PHP 7.4+ / MySQL stack would reproduce them on first load.

---

## Critical Bugs (app would not run)

### Bug #1 — `products.php`: `$conn` used before it exists

**File:** `products.php`
**Original lines 11–18:**

```php
$products = [];
$result = $conn->query("SELECT * FROM products ORDER BY created_at DESC");
if ($result) { while ($row = $result->fetch_assoc()) { $products[] = $row; } }

require_once __DIR__ . '/includes/header.php';  // ← $conn is created HERE
```

`$conn` is created inside `config/db.php`, which is loaded by `header.php`. At line 12, `$conn` is undefined, so `$conn->query(...)` throws:

```
Fatal error: Uncaught Error: Call to a member function query() on null in products.php:12
```

**Fix:** moved `require_once __DIR__ . '/includes/header.php';` to the top of the file, before the query.

### Bug #2 — `products.php`: `$_SESSION` checked before `session_start()`

**File:** `products.php`
**Original lines 25, 35:**

```php
<?php if (isset($_SESSION['user'])): ?>
    <a href="add_product.php" class="btn-add">+ Add Product</a>
<?php endif; ?>
```

`session_start()` is called by `header.php`, but the `$_SESSION` array is not populated with the user's session data until that call runs. Before it runs, `$_SESSION` is an empty autovivified array, so `isset($_SESSION['user'])` is always false. **Result:** the "+ Add Product" button is hidden for everyone, even logged-in users.

**Fix:** same as Bug #1 — loading `header.php` first makes the session available.

### Bug #3 — `add_product.php`: login required, but check always fails

**File:** `add_product.php`
**Original lines 14–18:**

```php
// Require login
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}
```

Same root cause as Bug #2: `session_start()` hasn't been called yet, so `$_SESSION['user']` is always unset. **Result:** every visitor — logged in or not — is redirected to `login.php`. The Add Product page is unreachable.

**Fix:** bootstrap `db.php` + `session_start()` at the very top of the file, BEFORE the auth check. The auth check + redirect also MUST run before `header.php` is required, because `header.php` outputs HTML and `header("Location: ...")` would fail with "headers already sent" if called afterwards.

### Bug #4 — `login.php`: `$conn` used before it exists AND session writes lost

**File:** `login.php`
**Original lines 24, 36–38:**

```php
$stmt = $conn->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
// ...
$_SESSION['user_id'] = $user['id'];
$_SESSION['user']    = $user['name'];
$_SESSION['email']   = $user['email'];
header("Location: index.php");
exit;
```

Two bugs in one:

1. `$conn` is used before `db.php` is loaded — fatal error "Call to a member function prepare() on null".
2. Even if (1) were fixed, the `$_SESSION` writes happen BEFORE `session_start()` is called. PHP only persists `$_SESSION` to the session store when the session is active. Writing to `$_SESSION` before `session_start()` creates a regular PHP array that gets **overwritten** when `session_start()` finally runs. Combined with the `header("Location: index.php"); exit;` redirect, the session is never persisted — **even a correct login would leave the user unauthenticated**.

**Fix:** bootstrap `db.php` + `session_start()` at the top of the file.

### Bug #5 — `register.php`: `$conn` used before it exists

**File:** `register.php`
**Original line 32:**

```php
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
```

Same root cause as Bug #4. **Result:** clicking the Register button crashes with a fatal error.

**Fix:** bootstrap `db.php` + `session_start()` at the top of the file.

---

## Minor Bugs & Polish (also fixed)

### Minor #1 — `register.php`: dead "Registration successful" alert

**Original lines 73–75 of register.php:**

```php
<?php if (isset($_GET['registered'])): ?>
    <div class="alert alert-success">Registration successful! Please log in.</div>
<?php endif; ?>
```

Successful registration redirects to `login.php?registered=1` (line 52), never to `register.php?registered=1`. So this block was dead code. Removed.

### Minor #2 — `.htaccess`: Apache 2.2 syntax (deprecated in 2.4+)

**Original:**

```apache
<FilesMatch "\.(db|sql|env|log)$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

`Order`/`Deny from all` is Apache 2.2 syntax. On Apache 2.4 (the default since 2012), this directive is only honored if `mod_access_compat` is enabled — otherwise it's silently ignored, leaving the protected files accessible. **Fix:** added the 2.4-native `Require all denied`, kept the 2.2 syntax as a fallback inside `<IfModule>`.

### Minor #3 — Missing `.input-error` CSS class

`assets/js/main.js` adds `class="input-error"` to required fields that fail validation (line 42), but `style.css` had no rule for `.input-error`. **Fix:** added the rule (red border + soft red background + red glow).

### Minor #4 — `header.php`: `session_start()` not idempotent

If a page bootstraps the session itself (which is now required for `add_product.php`, `login.php`, `register.php`), calling `session_start()` again in `header.php` would emit a notice ("Session already started"). **Fix:** wrapped the call in `if (session_status() === PHP_SESSION_NONE)`.

### Minor #5 — `add_product.php`: extension case sensitivity

The original took the file extension from `pathinfo(..., PATHINFO_EXTENSION)` without lowercasing. A file named `image.JPG` would be saved as `jewel_xxx.JPG` (uppercase). Most servers serve these fine, but lowercasing the extension is safer and more consistent.

### Minor #6 — `add_product.php`: `bind_param` type coercion

`$price` is a string from `$_POST`. `bind_param("sdss", ...)` declares it as a double (`d`). PHP coerces implicitly, but explicit casting (`(float) $price`) is safer across locales (e.g. locales where `,` is the decimal separator).

---

## Security Review

The code uses prepared statements everywhere user input reaches SQL — good. Passwords are hashed with `password_hash()` / verified with `password_verify()` — good. Session ID is regenerated on login (`session_regenerate_id(true)`) — good. File uploads check the actual MIME type with `finfo` (not just the user-supplied extension) and enforce a 5MB cap — good.

Remaining security considerations the user may want to address in future iterations (not blocking):

| Concern | Severity | Notes |
|---|---|---|
| No CSRF tokens on login/register/add_product forms | Medium | An attacker could forge a POST to `add_product.php` for a logged-in admin. Add a hidden `csrf_token` field validated against `$_SESSION['csrf_token']`. |
| No login rate-limiting | Medium | Brute-force vulnerable. Add exponential backoff or a CAPTCHA after N failed attempts per email/IP. |
| Uploaded images stored in webroot | Low | A malicious file like `jewel_xxx.jpg` containing PHP code is saved under `assets/images/`, which Apache may execute if mis-configured. Mitigation: store outside webroot and serve via a proxy script, OR add an `.htaccess` in `assets/images/` forcing `php_flag engine off`. |
| No session idle timeout | Low | Add `ini_set('session.gc_maxlifetime', 1800);` and a cookie lifetime. |
| DB credentials hardcoded in `config/db.php` | Low | Move to a `.env` file or environment variables; ensure `.env` is denied by `.htaccess` (it already is, by the `\.env` rule). |
| `header("Location: login.php")` uses relative URL | Info | HTTP/1.1 spec requires absolute URLs. Browsers tolerate relative, but stricter proxies may not. Use `header("Location: http://" . $_SERVER['HTTP_HOST'] . "/login.php")` or a `baseUrl` helper. |
| No HTTP security headers | Low | Add `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Content-Security-Policy` via `.htaccess` or in `header.php`. |

---

## Architectural Notes & Suggestions

1. **Single bootstrap file.** Every page now repeats the same 4-line bootstrap:
   ```php
   require_once __DIR__ . '/config/db.php';
   if (session_status() === PHP_SESSION_NONE) { session_start(); }
   ```
   Pull this into `includes/bootstrap.php` and `require` it at the top of every page. Reduces duplication and gives a single place to add error handlers, autoloaders, etc.

2. **Auth helper.** `add_product.php` (and future admin-only pages) repeats the "if not logged in, redirect" pattern. Add `includes/auth.php` that calls `bootstrap.php` and does the redirect. Usage becomes a one-liner: `require_once __DIR__ . '/includes/auth.php';`.

3. **Connection error UX.** `config/db.php` calls `die("Connection Failed: ...")` on failure, which dumps raw mysqli errors to the user. In production, log the error and show a generic "Service temporarily unavailable" page instead.

4. **Auto-seed on first run.** The README says to manually run `seed.sql`. Consider detecting an empty `products` table on first visit to `index.php` and auto-running the seed query (or just the sample products — the demo user is optional). Improves first-run UX for evaluation.

5. **`pages/` directory is reserved but the `$baseUrl` logic in `header.php` is broken for it.** `dirname($_SERVER['SCRIPT_NAME'])` returns `/pages` for files in `/pages/`, which would generate URLs like `/pages/assets/css/style.css` (404). If you ever use that directory, switch to a hardcoded base path or compute it relative to the project root.

6. **Product ownership.** The `products` table has no `user_id` column — any logged-in user can add products but no one "owns" them. If you want per-user products (a "My Products" page, edit/delete permissions), add a `user_id INT FOREIGN KEY` column and filter on it.

7. **No edit/delete product flow.** Currently you can only add. Consider `edit_product.php` and a delete action.

---

## File-by-File Summary of Changes

| File | Changes |
|---|---|
| `includes/header.php` | Made `session_start()` idempotent via `session_status()` check. Updated docblock. |
| `products.php` | Moved `require_once header.php` to top so `$conn` and `$_SESSION` are available. Updated docblock. |
| `add_product.php` | Added bootstrap (`db.php` + `session_start`) at top before auth check. Cast `$price` to float. Lowercase file extension. Updated docblock. |
| `login.php` | Added bootstrap at top. Updated docblock. |
| `register.php` | Added bootstrap at top. Removed dead "Registration successful" alert. Updated docblock. |
| `.htaccess` | Added Apache 2.4 `Require all denied`; kept 2.2 syntax inside `<IfModule>`. |
| `assets/css/style.css` | Added `.input-error` rule used by `main.js` form validation. |

No changes were needed to: `index.php`, `logout.php`, `config/db.php`, `seed.sql`, `assets/js/main.js`, `README.md`.

---

## How to Deploy and Verify

1. Copy the entire `jewellery-shop/` folder to your PHP server root (e.g. `/var/www/html/jewellery-shop/`).
2. Make sure PHP 7.4+ with `mysqli` and `fileinfo` extensions is enabled, and MySQL/MariaDB is running with a `root` user that has no password (or edit `config/db.php` to match your credentials).
3. Make `assets/images/` writable: `chmod 755 assets/images/`.
4. Visit `http://localhost/jewellery-shop/` — the database and tables are auto-created on first load.
5. (Optional) Seed sample data:
   ```bash
   mysql -u root jewellery_shop < seed.sql
   ```
   This adds 5 sample products and a demo user (`admin@jewelleryshop.com` / `password123`).
6. Smoke test the fixed flows:
   - Visit `products.php` — should load without fatal error, and show the "Add Product" button when logged in.
   - Register a new user at `register.php` — should redirect to `login.php?registered=1`.
   - Log in at `login.php` — should set the session cookie and redirect to `index.php` showing "Hi, <name>" in the nav.
   - Visit `add_product.php` while logged in — should show the form (NOT redirect to login).
   - Submit a product with an image — should save and redirect to `products.php` showing the new card.

---

## What I Could Not Verify

- **Runtime testing.** The sandbox has no PHP/MySQL. The fixes are based on careful reading of the include order; if you hit any runtime issue on your server, send me the error message and I'll fix it.
- **Visual styling.** I can confirm the CSS is well-formed and the class names in PHP match the selectors in CSS, but I cannot render the pages. The styling should look the same as before since I only added one new rule.
- **Image upload on real server.** The `finfo` MIME check, `uniqid` filename, and `move_uploaded_file` flow are all standard PHP, but actual upload behavior depends on your `php.ini` (`upload_max_filesize`, `post_max_size`, `file_uploads = On`).
