<?php
/**
 * Login — Authenticates users against the database
 *
 * Fixes from original:
 *   - SQL injection prevented with prepared statements
 *   - Password verified with password_verify() (supports hashed passwords)
 *   - Session uses correct 'name' column (was referencing non-existent column)
 *   - Clean error handling without inline <script> alerts
 *
 * IMPORTANT: db.php + session_start() MUST be bootstrapped at the top of this
 * file, BEFORE POST handling. The original version used $conn and wrote to
 * $_SESSION before header.php (which creates $conn and calls session_start())
 * was loaded — that broke both the DB query and the session persistence.
 */
$pageTitle = 'Login';

// --- Bootstrap: load $conn + start session BEFORE any POST handling ---
require_once __DIR__ . '/config/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';

if (isset($_POST['login'])) {

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter both email and password.';
    } else {
        // Use prepared statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verify hashed password
            if (password_verify($password, $user['password'])) {
                // Regenerate session ID to prevent fixation attacks
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user']    = $user['name'];
                $_SESSION['email']   = $user['email'];

                header("Location: index.php");
                exit;
            } else {
                $error = 'Incorrect password. Please try again.';
            }
        } else {
            $error = 'No account found with that email address.';
        }
        $stmt->close();
    }
}

// POST handling complete — now safe to render HTML
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-box">
        <h2>Welcome Back</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['registered'])): ?>
            <div class="alert alert-success">Account created successfully! Please log in.</div>
        <?php endif; ?>

        <?php if (isset($_GET['logout'])): ?>
            <div class="alert alert-success">You have been logged out.</div>
        <?php endif; ?>

        <form method="POST" data-validate>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" placeholder="Enter your email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" placeholder="Enter your password" required>
            </div>

            <button type="submit" name="login" class="btn-primary">Login</button>
        </form>

        <p class="auth-footer">
            Don't have an account? <a href="register.php">Register here</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>