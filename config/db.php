<?php

/**
 * Database Configuration & Connection
 *
 * Creates the database and required tables if they don't exist.
 * Uses UTF-8 charset for full Unicode support.
 *
 * --- Credential resolution order ---
 *  1. Environment variables (JEWEL_DB_HOST, JEWEL_DB_USER, JEWEL_DB_PASS, JEWEL_DB_NAME)
 *  2. A local override file: config/db.local.php  (gitignored — safe for credentials)
 *  3. Hardcoded defaults below (root / no password — works on XAMPP/WAMP default installs)
 *
 * If your MySQL root has a password (most standalone MySQL installs do), create
 * a file called `config/db.local.php` with this content:
 *
 *     <?php
 *     $DB_HOST = 'localhost';
 *     $DB_USER = 'root';
 *     $DB_PASS = 'your_password_here';
 *     $DB_NAME = 'jewellery_shop';
 *
 * That file is gitignored, so your password never gets committed.
 */

// --- Defaults (overridable by env vars or db.local.php) ---
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'jewellery_shop';

// --- 1. Allow a local override file ---
$localOverride = __DIR__ . '/db.local.php';
if (is_file($localOverride)) {
    require_once $localOverride;
}

// --- 2. Allow environment variables to override (highest priority) ---
if (getenv('JEWEL_DB_HOST') !== false) $DB_HOST = getenv('JEWEL_DB_HOST');
if (getenv('JEWEL_DB_USER') !== false) $DB_USER = getenv('JEWEL_DB_USER');
if (getenv('JEWEL_DB_PASS') !== false) $DB_PASS = getenv('JEWEL_DB_PASS');
if (getenv('JEWEL_DB_NAME') !== false) $DB_NAME = getenv('JEWEL_DB_NAME');

// --- Pre-flight check: make sure the mysqli extension is available ---
if (!extension_loaded('mysqli')) {
    http_response_code(500);
    if (php_sapi_name() === 'cli') {
        echo "ERROR: The 'mysqli' PHP extension is not loaded.\n\n";
        echo "Fix: open your php.ini (run `php --ini` to find it) and uncomment:\n";
        echo "    extension=mysqli\n";
        echo "Also make sure: extension_dir = \"ext\"\n";
    } else {
        echo "<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'>";
        echo "<title>Configuration error — Jewellery Shop</title>";
        echo "<style>
            body{font-family:'Segoe UI',Arial,sans-serif;background:#f8f6f3;color:#333;
                 max-width:720px;margin:60px auto;padding:0 20px;line-height:1.6}
            h1{color:#b00020;border-bottom:2px solid #b00020;padding-bottom:8px}
            h3{margin-top:28px;color:#1a1a2e}
            code{background:#f0ece6;padding:2px 6px;border-radius:3px;
                 font-family:Consolas,'Courier New',monospace;color:#a8864e}
            pre{background:#1a1a2e;color:#e2cc8f;padding:16px;border-radius:8px;
                overflow-x:auto;font-size:0.9rem}
            .step{margin:12px 0;padding:12px 16px;background:#fff;
                  border-left:4px solid #c9a96e;border-radius:4px;
                  box-shadow:0 1px 3px rgba(0,0,0,0.08)}
            .step strong{color:#1a1a2e}
        </style></head><body>";
        echo "<h1>PHP extension 'mysqli' is not enabled</h1>";
        echo "<p>The Jewellery Shop needs the <code>mysqli</code> extension to talk to MySQL, ";
        echo "but your PHP installation does not have it loaded.</p>";
        echo "<h3>How to fix it</h3>";
        echo "<div class='step'><strong>1.</strong> Find your <code>php.ini</code> file. ";
        echo "In a terminal run <code>php --ini</code> and look at the ";
        echo "<em>Loaded Configuration File</em> line.</div>";
        echo "<div class='step'><strong>2.</strong> If no file is listed (says <em>None</em>), copy ";
        echo "<code>php.ini-development</code> to <code>php.ini</code> in your PHP folder.</div>";
        echo "<div class='step'><strong>3.</strong> Open <code>php.ini</code> and find the line ";
        echo "<code>;extension=mysqli</code>. Remove the leading semicolon so it reads ";
        echo "<code>extension=mysqli</code>.</div>";
        echo "<div class='step'><strong>4.</strong> Also make sure ";
        echo "<code>extension_dir = \"ext\"</code> is set (a few lines above).</div>";
        echo "<div class='step'><strong>5.</strong> Save, restart your PHP server ";
        echo "(<code>Ctrl+C</code> then re-run <code>php -S localhost:8000</code>), reload this page.</div>";
        echo "</body></html>";
    }
    exit;
}

// --- Attempt the connection, with a friendly error page on failure ---
// PHP 8.1+ throws mysqli_sql_exception by default (instead of populating
// connect_error), so we MUST use try/catch here. On older PHP the exception
// simply won't be thrown and the catch is a no-op.
$conn = null;
try {
    $conn = @new mysqli($DB_HOST, $DB_USER, $DB_PASS);
} catch (\mysqli_sql_exception $e) {
    $conn = null;
    $err = $e->getMessage();
    // Fall through to the error-handling block below.
}

if ($conn === null || $conn->connect_error) {
    if (!isset($err)) {
        $err = $conn ? $conn->connect_error : 'Unknown connection error';
    }
    http_response_code(500);
    $isAccessDenied = (stripos($err, 'Access denied') !== false);
    $isUnknownHost  = (stripos($err, 'No connection could be made') !== false
                    || stripos($err, 'No such file or directory') !== false
                    || stripos($err, 'Connection refused') !== false
                    || stripos($err, 'Connection refused') !== false);

    if (php_sapi_name() === 'cli') {
        echo "DB connection failed: $err\n";
        if ($isAccessDenied) {
            echo "\nYour MySQL server is running, but it rejected the username/password.\n";
            echo "Create a file: config/db.local.php with your REAL MySQL password:\n";
            echo "  <?php\n  \$DB_HOST='localhost';\n  \$DB_USER='root';\n  \$DB_PASS='YOUR_REAL_PASSWORD';\n  \$DB_NAME='jewellery_shop';\n";
            echo "\nMake sure you didn't leave the placeholder text 'YOUR_MYSQL_ROOT_PASSWORD_HERE' in the file.\n";
        } elseif ($isUnknownHost) {
            echo "\nMySQL server not found. Is MySQL/MariaDB running? Start it via:\n";
            echo "  - XAMPP Control Panel → click 'Start' next to MySQL\n";
            echo "  - Or: net start MySQL80  (Windows service)\n";
        }
    } else {
        echo "<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'>";
        echo "<title>Database connection failed — Jewellery Shop</title>";
        echo "<style>
            body{font-family:'Segoe UI',Arial,sans-serif;background:#f8f6f3;color:#333;
                 max-width:760px;margin:60px auto;padding:0 20px;line-height:1.6}
            h1{color:#b00020;border-bottom:2px solid #b00020;padding-bottom:8px}
            h2{margin-top:28px;color:#1a1a2e;font-size:1.15rem}
            code{background:#f0ece6;padding:2px 6px;border-radius:3px;
                 font-family:Consolas,'Courier New',monospace;color:#a8864e}
            pre{background:#1a1a2e;color:#e2cc8f;padding:16px;border-radius:8px;
                overflow-x:auto;font-size:0.9rem}
            .step{margin:12px 0;padding:12px 16px;background:#fff;
                  border-left:4px solid #c9a96e;border-radius:4px;
                  box-shadow:0 1px 3px rgba(0,0,0,0.08)}
            .step strong{color:#1a1a2e}
            .err{background:#fde8e8;border:1px solid #f5c6c6;color:#b00020;
                 padding:12px 16px;border-radius:4px;font-family:Consolas,monospace;
                 margin:16px 0}
            .warn{background:#fff8e1;border:1px solid #ffe082;color:#8a6100;
                  padding:12px 16px;border-radius:4px;margin:16px 0}
        </style></head><body>";
        echo "<h1>Cannot connect to MySQL database</h1>";
        echo "<div class='err'>mysqli error: " . htmlspecialchars($err) . "</div>";

        // Show what credentials we tried (without leaking the password itself)
        $passHint = $DB_PASS === '' ? "<strong>empty</strong>" :
                    (strlen($DB_PASS) >= 8 ? "<strong>non-empty (length " . strlen($DB_PASS) . ")</strong>" : "<strong>non-empty</strong>");
        echo "<p>App tried to connect as <code>" . htmlspecialchars($DB_USER) . "@"
             . htmlspecialchars($DB_HOST) . "</code> with password: " . $passHint . ".</p>";

        if ($isAccessDenied) {
            echo "<div class='warn'><strong>Most common cause:</strong> you copied ";
            echo "<code>db.local.example.php</code> to <code>db.local.php</code> but didn't replace ";
            echo "the placeholder text <code>YOUR_MYSQL_ROOT_PASSWORD_HERE</code> with your actual ";
            echo "MySQL root password. Open <code>config/db.local.php</code> and check.</div>";

            echo "<h2>Your MySQL server is running — it just rejected the login</h2>";
            echo "<p>This means either (a) the password in <code>db.local.php</code> is wrong, or ";
            echo "(b) you put the literal placeholder text instead of your real password.</p>";

            echo "<h2>Verify your password from the command line</h2>";
            echo "<div class='step'><strong>1.</strong> Open a new terminal (not the one running PHP).</div>";
            echo "<div class='step'><strong>2.</strong> Try logging in with mysql client:</div>";
            echo "<pre>mysql -u root -p</pre>";
            echo "<div class='step'><strong>3.</strong> It will prompt <code>Enter password:</code> — type your MySQL root password. ";
            echo "If it works, you'll see <code>mysql&gt;</code>. If not, you'll get the same Access denied error.</div>";
            echo "<div class='step'><strong>4.</strong> Once you know the working password, open ";
            echo "<code>config/db.local.php</code> and put it between the quotes:</div>";
            echo "<pre>&lt;?php
\$DB_HOST = 'localhost';
\$DB_USER = 'root';
\$DB_PASS = 'the-password-that-just-worked';
\$DB_NAME = 'jewellery_shop';</pre>";
            echo "<div class='step'><strong>5.</strong> Save and reload this page — no PHP restart needed.</div>";

            echo "<h2>Other things to check</h2>";
            echo "<div class='step'>If <code>mysql -u root -p</code> also fails with Access denied, your MySQL root ";
            echo "password is different from what you remember. Reset it: ";
            echo "<a href='https://dev.mysql.com/doc/refman/8.0/en/resetting-permissions.html' target='_blank'>";
            echo "MySQL docs — How to reset the root password</a>.</div>";
            echo "<div class='step'>If you're using <strong>XAMPP</strong>: XAMPP's root has no password. ";
            echo "Set <code>\$DB_PASS = '';</code> in <code>db.local.php</code> AND make sure you started ";
            echo "MySQL from the XAMPP Control Panel (not the standalone MySQL service).</div>";
            echo "<div class='step'>If you have BOTH standalone MySQL AND XAMPP installed, they conflict. ";
            echo "Stop one of them (use XAMPP's MySQL on port 3306, OR standalone MySQL on 3306 — not both).</div>";
        } elseif ($isUnknownHost) {
            echo "<h2>MySQL server not running</h2>";
            echo "<p>PHP couldn't find a MySQL server at <code>" . htmlspecialchars($DB_HOST) . "</code>.</p>";
            echo "<div class='step'><strong>XAMPP / WAMP:</strong> Open the Control Panel and click <strong>Start</strong> next to MySQL.</div>";
            echo "<div class='step'><strong>Standalone MySQL on Windows:</strong> Open <code>services.msc</code>, ";
            echo "find the <code>MySQL80</code> (or similar) service, and start it.</div>";
            echo "<div class='step'><strong>Standalone MySQL on macOS:</strong> ";
            echo "<code>brew services start mysql</code> or use System Settings → MySQL → Start.</div>";
            echo "<div class='step'><strong>Linux:</strong> <code>sudo systemctl start mysql</code> ";
            echo "or <code>sudo systemctl start mariadb</code>.</div>";
        } else {
            echo "<h2>Unknown connection error</h2>";
            echo "<p>Check the error message above and your MySQL configuration.</p>";
        }
        echo "</body></html>";
    }
    exit;
}

// Set charset to utf8mb4
if (!$conn->set_charset("utf8mb4")) {
    die("Error setting charset: " . $conn->error);
}

// Wrap schema operations in try/catch — PHP 8.1+ throws mysqli_sql_exception
// on query failures too, which would bypass our friendly error handling.
try {
    // Create database if not exists
    $sql = "CREATE DATABASE IF NOT EXISTS `$DB_NAME` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    if ($conn->query($sql) === FALSE) {
        throw new \mysqli_sql_exception($conn->error);
    }

    // Select database
    $conn->select_db($DB_NAME);

    // Create users table
    $usersTable = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    if ($conn->query($usersTable) === FALSE) {
        throw new \mysqli_sql_exception($conn->error);
    }

    // Create products table (was MISSING in the original project)
    $productsTable = "CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(200) NOT NULL,
        price DECIMAL(10, 2) NOT NULL,
        description TEXT,
        image VARCHAR(500) DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    if ($conn->query($productsTable) === FALSE) {
        throw new \mysqli_sql_exception($conn->error);
    }
} catch (\mysqli_sql_exception $e) {
    http_response_code(500);
    die("Database setup error: " . htmlspecialchars($e->getMessage()));
}

// Expose the resolved credentials as the old variable names so the rest of the
// app (which references $database, etc.) keeps working if it needs to.
$host     = $DB_HOST;
$user     = $DB_USER;
$password = $DB_PASS;
$database = $DB_NAME;

?>