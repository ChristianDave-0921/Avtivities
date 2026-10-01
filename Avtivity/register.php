<?php
// register.php - User Registration
session_start();
require_once 'db_connection.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = trim($_POST['full_name'] ?? '');
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($full_name) || empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        // Check if username already exists
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $checkStmt->bind_param("s", $username);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $error = "Username '$username' is already taken. Please choose another.";
            $checkStmt->close();
        } else {
            $checkStmt->close();

            // Hash password and insert
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $insertStmt = $conn->prepare("INSERT INTO users (username, password, full_name, email) VALUES (?, ?, ?, ?)");
            $insertStmt->bind_param("ssss", $username, $hashedPassword, $full_name, $email);

            if ($insertStmt->execute()) {
                $_SESSION['success'] = "Registration successful! You can now sign in.";
                header("Location: login.php");
                exit();
            } else {
                $error = "Registration failed: " . $conn->error;
            }
            $insertStmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Student Record System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }
        .register-card {
            width: 100%;
            max-width: 480px;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            animation: fadeIn 0.4s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .register-header {
            background: #0d6efd;
            color: #ffffff;
            padding: 25px 20px 20px;
            text-align: center;
        }
        .register-header .icon-circle {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 28px;
            margin-bottom: 10px;
        }
        .register-body {
            padding: 30px;
        }
        .btn-register {
            background: #0d6efd;
            border: none;
            padding: 12px;
            font-weight: 600;
        }
        .btn-register:hover {
            background: #0b5ed7;
        }
    </style>
</head>
<body>

<div class="register-card">
    <div class="register-header">
        <div class="icon-circle">
            <i class="bi bi-person-plus-fill"></i>
        </div>
        <h4 class="mb-1 fw-bold">Create an Account</h4>
        <p class="mb-0 text-white-50 fs-6">Student Record System Dashboard</p>
    </div>

    <div class="register-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                <div><?= htmlspecialchars($error); ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="full_name" name="full_name" placeholder="Full Name" value="<?= htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                <label for="full_name"><i class="bi bi-card-heading me-1"></i> Full Name</label>
            </div>

            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="username" name="username" placeholder="Username" value="<?= htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                <label for="username"><i class="bi bi-person me-1"></i> Username</label>
            </div>

            <div class="form-floating mb-3">
                <input type="email" class="form-control" id="email" name="email" placeholder="Email" value="<?= htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                <label for="email"><i class="bi bi-envelope me-1"></i> Email Address</label>
            </div>

            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="password" name="password" placeholder="Password" minlength="6" required>
                <label for="password"><i class="bi bi-lock me-1"></i> Password (min. 6 characters)</label>
            </div>

            <div class="form-floating mb-4">
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm Password" minlength="6" required>
                <label for="confirm_password"><i class="bi bi-shield-check me-1"></i> Confirm Password</label>
            </div>

            <button type="submit" class="btn btn-primary w-100 btn-register mb-3">
                <i class="bi bi-person-check me-1"></i> Register Account
            </button>
        </form>

        <div class="text-center text-muted small">
            Already have an account? <a href="login.php" class="text-decoration-none fw-semibold">Sign in here</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
