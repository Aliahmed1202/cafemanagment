<?php
session_start();

// Include database configuration
require_once '../config/database.php';

// Check if database connection is working
if (!$conn || $conn->connect_error) {
    die("Database connection failed. Please check your database setup.");
}

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

// Handle logout success message
if (isset($_GET['logout']) && $_GET['logout'] == 'success') {
    $success_message = 'You have been successfully logged out.';
}

// Check if user is already logged in and redirect to dashboard
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    // Clear any output buffers
    if (ob_get_level()) {
        ob_end_clean();
    }
    header('Location: ../admin/dashboard.php');
    exit();
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) ? 1 : 0;
    
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Security token mismatch. Please try again.';
    } elseif (empty($username) || empty($password)) {
        $error = $lang['invalid_credentials'];
    } else {
        // Query to check user credentials
        $sql = "SELECT id, username, full_name, role, email FROM users WHERE username = ? AND password = ? AND status = 'active'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['login_time'] = date('Y-m-d H:i:s');
            $_SESSION['last_activity'] = time();
            
            // Set remember me cookie
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/'); // 30 days
                // Store token in database (would need to add remember_token column to users table)
            }
            
            header('Location: ../admin/dashboard.php');
            exit();
        } else {
            $error = $lang['invalid_credentials'];
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
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>" dir="<?php echo $_SESSION['lang'] == 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['login_title']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        /* Enhanced animated background */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(circle at 20% 80%, rgba(255, 255, 255, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(255, 255, 255, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 60% 60%, rgba(102, 126, 234, 0.1) 0%, transparent 40%);
            animation: float 25s ease-in-out infinite;
        }
        
        /* Floating particles */
        body::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(circle at 10% 90%, rgba(240, 147, 251, 0.1) 0%, transparent 30%),
                radial-gradient(circle at 90% 10%, rgba(245, 87, 108, 0.1) 0%, transparent 30%);
            animation: float 20s ease-in-out infinite reverse;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            25% { transform: translateY(-15px) rotate(1deg); }
            50% { transform: translateY(10px) rotate(-1deg); }
            75% { transform: translateY(-5px) rotate(0.5deg); }
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 24px;
            box-shadow: 
                0 25px 50px rgba(0, 0, 0, 0.15),
                0 10px 20px rgba(0, 0, 0, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.9),
                0 0 0 100px rgba(102, 126, 234, 0.1);
            backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            padding: 32px;
            max-width: 380px;
            width: 100%;
            position: relative;
            z-index: 10;
            transform: translateY(0);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
        }
        
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent, rgba(102, 126, 234, 0.05), transparent);
            border-radius: 24px;
            z-index: -1;
        }
        
        .login-container:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 
                0 35px 70px rgba(0, 0, 0, 0.2),
                0 15px 30px rgba(0, 0, 0, 0.15),
                inset 0 1px 0 rgba(255, 255, 255, 0.9),
                0 0 0 120px rgba(102, 126, 234, 0.15);
        }
        
        .cafe-logo {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 32px;
            box-shadow: 
                0 15px 35px rgba(240, 147, 251, 0.4),
                0 5px 15px rgba(245, 87, 108, 0.3),
                inset 0 0 0 3px rgba(255, 255, 255, 0.2);
            position: relative;
            animation: pulse 2.5s ease-in-out infinite;
            transition: all 0.3s ease;
        }
        
        .cafe-logo:hover {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 
                0 20px 40px rgba(240, 147, 251, 0.5),
                0 8px 20px rgba(245, 87, 108, 0.4);
        }
        
        .cafe-logo::before {
            content: '';
            position: absolute;
            top: -5px;
            left: -5px;
            right: -5px;
            bottom: -5px;
            background: linear-gradient(135deg, #f093fb, #f5576c, #667eea, #764ba2);
            border-radius: 50%;
            z-index: -1;
            opacity: 0.8;
            animation: rotate 4s linear infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.08); }
        }
        
        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .login-title {
            font-size: 24px;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea, #764ba2, #f093fb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 6px;
            text-align: center;
            position: relative;
            animation: shimmer 3s ease-in-out infinite;
        }
        
        @keyframes shimmer {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }
        
        .login-subtitle {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 24px;
            text-align: center;
            font-weight: 400;
            opacity: 0.9;
        }
        
        .form-label {
            font-weight: 700;
            color: #343a40;
            margin-bottom: 8px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: block;
        }
        
        .input-group {
            margin-bottom: 16px;
            position: relative;
        }
        
        .form-control {
            border-radius: 14px;
            border: 2px solid #e9ecef;
            padding: 14px 18px 14px 48px;
            font-size: 14px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(248, 249, 250, 0.9);
            color: #343a40;
            font-weight: 500;
            position: relative;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 
                0 0 0 6px rgba(102, 126, 234, 0.15),
                0 6px 20px rgba(102, 126, 234, 0.2),
                inset 0 0 0 3px rgba(102, 126, 234, 0.1);
            background: white;
            transform: translateY(-3px);
            outline: none;
        }
        
        .form-control::placeholder {
            color: #adb5bd;
            font-weight: 400;
            font-style: italic;
        }
        
        .input-group-text {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border: 2px solid #e9ecef;
            border-right: none;
            border-radius: 14px 0 0 14px;
            padding: 16px 18px;
            color: #6c757d;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .input-group .form-control {
            border-radius: 0 14px 14px 0;
            padding-left: 18px;
        }
        
        .input-group:has(.password-toggle) .form-control {
            border-radius: 0;
            padding-right: 0;
        }
        
        .input-group:focus-within .input-group-text {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-color: #667eea;
            color: white;
            transform: scale(1.05);
        }
        
        .password-toggle {
            cursor: pointer;
            color: #6c757d;
            transition: all 0.3s ease;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border: 2px solid #e9ecef;
            border-left: none;
            border-radius: 0 14px 14px 0;
            padding: 16px 18px;
        }
        
        .password-toggle:hover {
            color: #667eea;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-color: #667eea;
            color: white;
            transform: scale(1.05);
        }
        
        .input-group .form-control + .password-toggle {
            border-radius: 0 14px 14px 0;
            border-left: none;
        }
        
        .rtl .input-group-text {
            border-right: 2px solid #e9ecef;
            border-left: none;
            border-radius: 0 14px 14px 0;
            order: 2;
        }
        
        .rtl .form-control {
            border-radius: 14px 0 0 14px;
            order: 1;
        }
        
        .rtl .password-toggle {
            border-right: none;
            border-left: 2px solid #e9ecef;
            border-radius: 14px 0 0 14px;
            order: 3;
        }
        
        .rtl .input-group {
            display: flex;
            flex-direction: row;
        }
        
        .rtl .input-group:has(.password-toggle) .form-control {
            border-radius: 0;
            order: 2;
        }
        
        .form-check {
            margin-bottom: 24px;
        }
        
        .form-check-input {
            width: 20px;
            height: 20px;
            border: 2px solid #dee2e6;
            border-radius: 6px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .form-check-input:checked {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-color: #667eea;
            transform: scale(1.1);
        }
        
        .form-check-label {
            color: #495057;
            font-weight: 600;
            margin-left: 10px;
            cursor: pointer;
            user-select: none;
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            border: none;
            border-radius: 14px;
            padding: 16px;
            font-weight: 800;
            font-size: 14px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            color: white;
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            box-shadow: 
                0 8px 25px rgba(102, 126, 234, 0.3),
                inset 0 0 0 2px rgba(255, 255, 255, 0.2);
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.6s ease;
        }
        
        .btn-login:hover::before {
            left: 100%;
        }
        
        .btn-login:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 
                0 15px 35px rgba(102, 126, 234, 0.4),
                0 8px 20px rgba(118, 75, 162, 0.3),
                inset 0 0 0 3px rgba(255, 255, 255, 0.3);
        }
        
        .btn-login:active {
            transform: translateY(-2px) scale(0.98);
        }
        
        .btn-login:disabled {
            opacity: 0.6;
            transform: none;
            cursor: not-allowed;
        }
        
        .language-switcher {
            position: absolute;
            top: 30px;
            right: 30px;
            z-index: 100;
        }
        
        .language-switcher .btn {
            background: rgba(255, 255, 255, 0.95);
            border: 2px solid rgba(255, 255, 255, 0.4);
            border-radius: 16px;
            padding: 14px 20px;
            font-weight: 700;
            color: #495057;
            backdrop-filter: blur(15px);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            min-width: 140px;
        }
        
        .language-switcher .btn:hover {
            background: white;
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border-color: rgba(102, 126, 234, 0.3);
        }
        
        .language-text {
            font-size: 14px;
            font-weight: 600;
        }
        
        .language-arrow {
            font-size: 12px;
            transition: transform 0.3s ease;
        }
        
        .language-switcher .btn:hover .language-arrow {
            transform: rotate(180deg);
        }
        
        .dropdown-menu-animated {
            background: rgba(255, 255, 255, 0.98);
            border: 2px solid rgba(255, 255, 255, 0.4);
            border-radius: 16px;
            box-shadow: 
                0 15px 35px rgba(0, 0, 0, 0.15),
                0 5px 15px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(20px);
            padding: 12px;
            min-width: 200px;
            animation: dropdownSlide 0.3s ease-out;
            border: none;
        }
        
        @keyframes dropdownSlide {
            from {
                opacity: 0;
                transform: translateY(-10px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .language-option {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 4px;
            transition: all 0.3s ease;
            font-weight: 600;
            color: #495057;
            border: 2px solid transparent;
        }
        
        .language-option:hover {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            transform: translateX(5px);
            border-color: rgba(102, 126, 234, 0.2);
            color: #667eea;
        }
        
        .language-option i {
            font-size: 16px;
            width: 20px;
            text-align: center;
        }
        
        .language-option .fa-flag-usa {
            color: #4a90e2;
        }
        
        .language-option .fa-flag {
            color: #e74c3c;
        }
        
        .language-option .fa-check {
            font-size: 14px;
            color: #28a745;
        }
        
        .alert {
            border-radius: 16px;
            border: none;
            padding: 18px 20px;
            margin-bottom: 24px;
            font-weight: 600;
            backdrop-filter: blur(15px);
            position: relative;
            overflow: hidden;
        }
        
        .alert::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.15), rgba(220, 53, 69, 0.08));
            color: #dc3545;
            border: 2px solid rgba(220, 53, 69, 0.3);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.2);
        }
        
        .alert-danger::before {
            background: linear-gradient(135deg, #dc3545, #c82333);
        }
        
        .alert-success {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.15), rgba(40, 167, 69, 0.08));
            color: #28a745;
            border: 2px solid rgba(40, 167, 69, 0.3);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.2);
        }
        
        .alert-success::before {
            background: linear-gradient(135deg, #28a745, #20c997);
        }
        
        .forgot-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s ease;
            display: inline-block;
            margin-top: 16px;
            position: relative;
        }
        
        .forgot-link::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            transition: width 0.3s ease;
        }
        
        .forgot-link:hover {
            color: #764ba2;
            transform: translateY(-2px);
        }
        
        .forgot-link:hover::after {
            width: 100%;
        }
        
        .demo-credentials {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 14px;
            padding: 20px;
            margin-top: 24px;
            border: 2px solid #dee2e6;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .demo-credentials::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #667eea, #764ba2);
        }
        
        .demo-credentials h6 {
            color: #343a40;
            font-weight: 800;
            margin-bottom: 16px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .demo-credentials .credential-item {
            font-size: 11px;
            margin-bottom: 8px;
            color: #6c757d;
            font-family: 'Courier New', monospace;
            background: white;
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .demo-credentials .credential-item:hover {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            transform: translateX(5px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .demo-credentials .credential-item strong {
            color: #667eea;
            font-weight: 800;
        }
        
        .footer-text {
            text-align: center;
            margin-top: 32px;
            color: #6c757d;
            font-size: 12px;
            font-weight: 500;
            opacity: 0.8;
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            backdrop-filter: blur(8px);
        }
        
        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(255, 255, 255, 0.2);
            border-top: 4px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            box-shadow: 0 0 20px rgba(102, 126, 234, 0.5);
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .rtl .language-switcher {
            left: 30px;
            right: auto;
        }
        
        /* Enhanced Responsive Design */
        @media (max-width: 576px) {
            .login-container {
                margin: 16px;
                padding: 24px 20px;
                max-width: 320px;
            }
            
            .login-title {
                font-size: 20px;
            }
            
            .cafe-logo {
                width: 60px;
                height: 60px;
                font-size: 28px;
            }
            
            .language-switcher {
                top: 16px;
                right: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="language-switcher">
        <div class="dropdown">
            <button class="btn btn-light dropdown-toggle" type="button" id="languageDropdown" data-bs-toggle="dropdown">
                <i class="fas fa-globe me-2"></i>
                <span class="language-text"><?php echo $lang['language']; ?></span>
                <i class="fas fa-chevron-down ms-2 language-arrow"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-animated" aria-labelledby="languageDropdown">
                <li>
                    <a class="dropdown-item language-option" href="?lang=en">
                        <i class="fas fa-flag-usa me-2"></i>
                        <span>English</span>
                        <?php if ($_SESSION['lang'] == 'en'): ?>
                            <i class="fas fa-check text-success ms-auto"></i>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item language-option" href="?lang=ar">
                        <i class="fas fa-flag me-2"></i>
                        <span>العربية</span>
                        <?php if ($_SESSION['lang'] == 'ar'): ?>
                            <i class="fas fa-check text-success ms-auto"></i>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="container vh-100 d-flex align-items-center justify-content-center">
        <div class="login-container mx-auto" style="max-width: 400px;">
            <div class="text-center mb-4">
                <div class="cafe-logo">
                    <i class="fas fa-coffee"></i>
                </div>
                <h1 class="login-title"><?php echo $lang['login_title']; ?></h1>
                <p class="login-subtitle"><?php echo $lang['login_subtitle']; ?></p>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="input-group">
                    <label for="username" class="form-label"><?php echo $lang['username']; ?></label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" class="form-control" id="username" name="username" 
                               placeholder="<?php echo $lang['username']; ?>" required>
                    </div>
                </div>

                <div class="input-group">
                    <label for="password" class="form-label"><?php echo $lang['password']; ?></label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="<?php echo $lang['password']; ?>" required>
                        <span class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="passwordIcon"></i>
                        </span>
                    </div>
                </div>

                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">
                        <?php echo $lang['remember_me']; ?>
                    </label>
                </div>

                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <button type="submit" class="btn btn-primary btn-login w-100 mb-3" id="loginBtn">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    <span id="loginBtnText"><?php echo $lang['login_button']; ?></span>
                </button>

                <div class="text-center">
                    <a href="forgot_password.php" class="forgot-link">
                        <?php echo $lang['forgot_password']; ?>
                    </a>
                </div>


            <div class="footer-text">
                <small class="text-muted">
                    &copy; 2024 <?php echo $lang['login_title']; ?>. All rights reserved.
                </small>
            </div>
        </div>
    </div>

    <!-- Success Message Tab -->
    <?php if (isset($success_message)): ?>
        <div class="position-fixed top-0 start-0 p-3" style="z-index: 1050;" id="successAlert">
            <div class="alert alert-success alert-dismissible fade show d-inline-flex align-items-center" role="alert" style="max-width: 300px;">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $success_message; ?>
                <button type="button" class="btn-close ms-2" data-bs-dismiss="alert"></button>
            </div>
        </div>
        <script>
            // Auto-hide success message after 3 seconds
            setTimeout(function() {
                const successAlert = document.getElementById('successAlert');
                if (successAlert) {
                    const bsAlert = new bootstrap.Alert(successAlert);
                    bsAlert.close();
                }
            }, 3000);
        </script>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password visibility toggle
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('passwordIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.classList.remove('fa-eye');
                passwordIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                passwordIcon.classList.remove('fa-eye-slash');
                passwordIcon.classList.add('fa-eye');
            }
        }
        
        // Form submission with loading state
        document.querySelector('form').addEventListener('submit', function(e) {
            const loginBtn = document.getElementById('loginBtn');
            const loginBtnText = document.getElementById('loginBtnText');
            const loadingOverlay = document.querySelector('.loading-overlay');
            
            // Show loading state
            loginBtn.disabled = true;
            loginBtnText.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i><?php echo $lang['loading']; ?>';
            loadingOverlay.style.display = 'flex';
        });
        
        // Auto-focus on username field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });
        
        // Enter key navigation
        document.getElementById('username').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('password').focus();
            }
        });
        
        // Session timeout warning (5 minutes)
        let sessionTimeout;
        function resetSessionTimeout() {
            clearTimeout(sessionTimeout);
            sessionTimeout = setTimeout(function() {
                alert('Your session will expire soon. Please save your work.');
            }, 300000); // 5 minutes
        }
        
        // Reset timeout on user activity
        document.addEventListener('mousemove', resetSessionTimeout);
        document.addEventListener('keypress', resetSessionTimeout);
        resetSessionTimeout();
    </script>
    
    <!-- Loading Overlay -->
    <div class="loading-overlay">
        <div class="loading-spinner"></div>
    </div>
</body>
</html>
