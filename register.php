<?php
/**
 * Register — Handles user registration with hashed passwords
 *
 * Fixes from original:
 *   - Column name changed from 'username' to 'name' (matching table schema)
 *   - Password is now hashed with password_hash() instead of plain text
 *   - SQL injection prevented with prepared statements
 *   - Duplicate email handled gracefully
 *
 * IMPORTANT: db.php + session_start() MUST be bootstrapped at the top of this
 * file, BEFORE POST handling. The original version used $conn before
 * header.php (which creates $conn) was loaded — that broke the registration
 * query with "Call to a member function prepare() on null".
 */
$pageTitle = 'Register';

// --- Bootstrap: load $conn + start session BEFORE any POST handling ---
require_once __DIR__ . '/config/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Process form submission
$error = '';
$success = '';

if (isset($_POST['register'])) {

    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Basic server-side validation
    if ($name === '' || $email === '' || $password === '') {
        $error = 'All fields are required.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = 'An account with this email already exists.';
            $stmt->close();
        } else {
            $stmt->close();

            // Hash the password securely
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert user with prepared statement
            $insert = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $insert->bind_param("sss", $name, $email, $hashedPassword);

            if ($insert->execute()) {
                $insert->close();
                header("Location: login.php?registered=1");
                exit;
            } else {
                $error = 'Registration failed. Please try again.';
                $insert->close();
            }
        }
    }
}

// POST handling complete — now safe to render HTML
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-box">
        <h2>Create Account</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" data-validate>
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" name="name" id="name" placeholder="Enter your full name" required>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" placeholder="Enter your email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" placeholder="Min. 6 characters" required minlength="6">
            </div>

            <button type="submit" name="register" class="btn-primary">Register</button>
        </form>

        <p class="auth-footer">
            Already have an account? <a href="login.php">Login here</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>