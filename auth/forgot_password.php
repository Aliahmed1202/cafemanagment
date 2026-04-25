<?php
session_start();

// Include database configuration
require_once '../config/database.php';

// Set default language or get from session/cookie
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'ar'])) {
    $_SESSION['lang'] = $_GET['lang'];
} elseif (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en'; // Default language
}

// Load language file
$lang_file = __DIR__ . '/../languages/' . $_SESSION['lang'] . '.php';
if (file_exists($lang_file)) {
    $lang = require $lang_file;
} else {
    // Fallback to English if the selected language file is missing
    $lang = require __DIR__ . '/../languages/en.php';
}

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ../admin/dashboard.php');
    exit();
}

$message = '';
$error = '';

// Handle forgot password form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Security token mismatch. Please try again.';
    } elseif (empty($email)) {
        $error = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check if email exists in database
        $sql = "SELECT id, username, full_name FROM users WHERE email = ? AND status = 'active'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Generate reset token
            $reset_token = bin2hex(random_bytes(32));
            $reset_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store reset token (would need to add reset_token and reset_expiry columns to users table)
            $update_sql = "UPDATE users SET reset_token = ?, reset_expiry = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ssi", $reset_token, $reset_expiry, $user['id']);
            $update_stmt->execute();
            
            // In a real application, you would send an email here
            // For demo purposes, we'll just show the reset link
            $reset_link = "http://$_SERVER[HTTP_HOST]/a3det%20wanas/reset_password.php?token=$reset_token";
            
            $message = "Password reset link has been sent to your email. For demo purposes: <a href='$reset_link'>$reset_link</a>";
            
            // Log the password reset request
            $ip = $_SERVER['REMOTE_ADDR'];
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            $log_sql = "INSERT INTO password_reset_logs (user_id, email, ip_address, user_agent, request_time, status) VALUES (?, ?, ?, ?, NOW(), 'requested')";
            $log_stmt = $conn->prepare($log_sql);
            $log_stmt->bind_param("isss", $user['id'], $email, $ip, $user_agent);
            $log_stmt->execute();
            
        } else {
            // Don't reveal if email exists or not for security
            $message = "If an account with that email exists, a password reset link has been sent.";
        }
    }
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle language change
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
    header('Location: forgot_password.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>" dir="<?php echo $_SESSION['lang'] == 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?php echo $lang['login_title']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #00A896 0%, #005F60 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .forgot-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .cafe-logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(45deg, #00A896 0%, #005F60 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 36px;
        }
        .form-control {
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #00A896;
            box-shadow: 0 0 0 0.2rem rgba(0, 168, 150, 0.25);
        }
        .btn-submit {
            background: linear-gradient(45deg, #00A896 0%, #005F60 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 168, 150, 0.3);
        }
        .language-switcher {
            position: absolute;
            top: 20px;
            right: 20px;
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
        .input-group-text {
            background: transparent;
            border: 1px solid #e0e0e0;
            border-right: none;
            border-radius: 10px 0 0 10px;
        }
        .rtl .input-group-text {
            border-right: 1px solid #e0e0e0;
            border-left: none;
            border-radius: 0 10px 10px 0;
        }
        .rtl .form-control {
            border-radius: 0 10px 10px 0;
        }
        .rtl .language-switcher {
            left: 20px;
            right: auto;
        }
        .back-link {
            color: #00A896;
            text-decoration: none;
            font-weight: 500;
        }
        .back-link:hover {
            color: #005F60;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="language-switcher">
        <div class="dropdown">
            <button class="btn btn-light dropdown-toggle" type="button" id="languageDropdown" data-bs-toggle="dropdown">
                <i class="fas fa-globe"></i> <?php echo $lang['language']; ?>
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="?lang=en">English</a></li>
                <li><a class="dropdown-item" href="?lang=ar">العربية</a></li>
            </ul>
        </div>
    </div>

    <div class="container vh-100 d-flex align-items-center justify-content-center">
        <div class="forgot-container p-5" style="max-width: 450px; width: 100%;">
            <div class="text-center mb-4">
                <div class="cafe-logo">
                    <i class="fas fa-key"></i>
                </div>
                <h2 class="mb-2">Forgot Password</h2>
                <p class="text-muted">Enter your email address to reset your password</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="Enter your email address" required>
                    </div>
                </div>

                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <button type="submit" class="btn btn-primary btn-submit w-100 mb-3">
                    <i class="fas fa-paper-plane me-2"></i>
                    Send Reset Link
                </button>

                <div class="text-center">
                    <a href="login.php" class="back-link">
                        <i class="fas fa-arrow-left me-2"></i>
                        Back to Login
                    </a>
                </div>
            </form>

            <div class="text-center mt-4">
                <small class="text-muted">
                    © 2024 <?php echo $lang['login_title']; ?>. All rights reserved.
                </small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-focus on email field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('email').focus();
        });
    </script>
</body>
</html>
