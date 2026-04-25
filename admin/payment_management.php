<?php
session_start();

// Include database configuration
require_once '../config/database.php';

// Check if database connection is working
if (!$conn || $conn->connect_error) {
    die("Database connection failed. Please check your database setup.");
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    // Clear any output buffers
    if (ob_get_level()) {
        ob_end_clean();
    }
    header('Location: ../auth/login.php');
    exit();
}

// Handle language change
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
    header('Location: payment_management.php');
    exit;
}

// Set default language or get from session
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'ar'])) {
    $_SESSION['lang'] = $_GET['lang'];
} elseif (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en'; // Default language
}

// Ensure language variables are set if not loaded from file
$is_arabic = $_SESSION['lang'] === 'ar';

if (!isset($lang['payment_management'])) $lang['payment_management'] = $is_arabic ? 'إدارة الدفعات' : 'Payment Management';
if (!isset($lang['manage_payments'])) $lang['manage_payments'] = $is_arabic ? 'إدارة دفعات المطعم وتتبع الإيرادات والمصروفات' : 'Manage restaurant payments and track income and expenses';
if (!isset($lang['amount'])) $lang['amount'] = $is_arabic ? 'المبلغ' : 'Amount';
if (!isset($lang['description'])) $lang['description'] = $is_arabic ? 'الوصف' : 'Description';
if (!isset($lang['type'])) $lang['type'] = $is_arabic ? 'النوع' : 'Type';
if (!isset($lang['income'])) $lang['income'] = $is_arabic ? 'إيرادات' : 'Income';
if (!isset($lang['outcome'])) $lang['outcome'] = $is_arabic ? 'مصروفات' : 'Outcome';
if (!isset($lang['add_payment'])) $lang['add_payment'] = $is_arabic ? 'إضافة دفعة' : 'Add Payment';
if (!isset($lang['delete_payment'])) $lang['delete_payment'] = $is_arabic ? 'حذف الدفعة' : 'Delete Payment';
if (!isset($lang['payment_added_success'])) $lang['payment_added_success'] = $is_arabic ? 'تمت إضافة الدفعة بنجاح!' : 'Payment entry added successfully!';
if (!isset($lang['payment_deleted_success'])) $lang['payment_deleted_success'] = $is_arabic ? 'تم حذف الدفعة بنجاح!' : 'Payment entry deleted successfully!';
if (!isset($lang['total_income'])) $lang['total_income'] = $is_arabic ? 'إجمالي الإيرادات' : 'Total Income';
if (!isset($lang['total_outcome'])) $lang['total_outcome'] = $is_arabic ? 'إجمالي المصروفات' : 'Total Outcome';
if (!isset($lang['balance'])) $lang['balance'] = $is_arabic ? 'الرصيد' : 'Balance';
if (!isset($lang['daily_stats'])) $lang['daily_stats'] = $is_arabic ? 'إحصائيات اليوم' : "Today's Stats";
if (!isset($lang['created_by'])) $lang['created_by'] = $is_arabic ? 'أنشئ بواسطة' : 'Created By';
if (!isset($lang['created_at'])) $lang['created_at'] = $is_arabic ? 'تاريخ الإنشاء' : 'Created At';
if (!isset($lang['actions'])) $lang['actions'] = $is_arabic ? 'الإجراءات' : 'Actions';
if (!isset($lang['delete'])) $lang['delete'] = $is_arabic ? 'حذف' : 'Delete';
if (!isset($lang['no_payments'])) $lang['no_payments'] = $is_arabic ? 'لا توجد دفعات' : 'No payments found';
if (!isset($lang['no_payments_message'])) $lang['no_payments_message'] = $is_arabic ? 'لم يتم العثور على دفعات. أضف دفعتك الأولى.' : 'No payments found. Add your first payment.';
if (!isset($lang['language'])) $lang['language'] = $is_arabic ? 'اللغة' : 'Language';
if (!isset($lang['logout'])) $lang['logout'] = $is_arabic ? 'تسجيل الخروج' : 'Logout';
if (!isset($lang['profile'])) $lang['profile'] = $is_arabic ? 'الملف الشخصي' : 'Profile';
if (!isset($lang['settings'])) $lang['settings'] = $is_arabic ? 'الإعدادات' : 'Settings';

// Load language file
$lang_file = __DIR__ . '/../languages/' . $_SESSION['lang'] . '.php';
if (file_exists($lang_file)) {
    $lang = require $lang_file;
} else {
    // Fallback to English if the selected language file is missing
    $lang = require __DIR__ . '/../languages/en.php';
}

// Handle form submission for adding new payment entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payment'])) {
    $amount = floatval($_POST['amount']);
    $description = trim($_POST['description']);
    $type = $_POST['type']; // 'income' or 'outcome'
    $created_by = $_SESSION['user_id'];
    
    if ($amount > 0 && !empty($description)) {
        $sql = "INSERT INTO payments (amount, description, type, created_by) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("dssi", $amount, $description, $type, $created_by);
        
        if ($stmt->execute()) {
            $success_message = "Payment entry added successfully!";
        } else {
            $error_message = "Error adding payment entry: " . $conn->error;
        }
        $stmt->close();
    } else {
        $error_message = "Please fill in all required fields with valid values.";
    }
}

// Handle delete payment entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_payment'])) {
    $payment_id = intval($_POST['payment_id']);
    
    if ($payment_id > 0) {
        $sql = "DELETE FROM payments WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $payment_id);
        
        if ($stmt->execute()) {
            $success_message = "Payment entry deleted successfully!";
        } else {
            $error_message = "Error deleting payment entry: " . $conn->error;
        }
        $stmt->close();
    } else {
        $error_message = "Invalid payment ID.";
    }
}

// Get payment entries
$payments_sql = "SELECT p.*, u.full_name as created_by_name 
                 FROM payments p 
                 LEFT JOIN users u ON p.created_by = u.id 
                 ORDER BY p.created_at DESC";
$payments_result = $conn->query($payments_sql);

// Calculate totals (All-time)
$income_total_sql = "SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE type = 'income'";
$outcome_total_sql = "SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE type = 'outcome'";
$income_total = $conn->query($income_total_sql)->fetch_assoc()['total'];
$outcome_total = $conn->query($outcome_total_sql)->fetch_assoc()['total'];
$balance_total = $income_total - $outcome_total;

// Calculate Daily stats
$today_start = date('Y-m-d 00:00:00');
$today_end = date('Y-m-d 23:59:59');
$income_daily_sql = "SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE type = 'income' AND created_at BETWEEN '$today_start' AND '$today_end'";
$outcome_daily_sql = "SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE type = 'outcome' AND created_at BETWEEN '$today_start' AND '$today_end'";
$income_daily = $conn->query($income_daily_sql)->fetch_assoc()['total'];
$outcome_daily = $conn->query($outcome_daily_sql)->fetch_assoc()['total'];
$balance_daily = $income_daily - $outcome_daily;

// Calculate Monthly stats
$month_start = date('Y-m-01 00:00:00');
$month_end = date('Y-m-t 23:59:59');
$income_monthly_sql = "SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE type = 'income' AND created_at BETWEEN '$month_start' AND '$month_end'";
$outcome_monthly_sql = "SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE type = 'outcome' AND created_at BETWEEN '$month_start' AND '$month_end'";
$income_monthly = $conn->query($income_monthly_sql)->fetch_assoc()['total'];
$outcome_monthly = $conn->query($outcome_monthly_sql)->fetch_assoc()['total'];
$balance_monthly = $income_monthly - $outcome_monthly;
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>" dir="<?php echo $_SESSION['lang'] == 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['payment_management']; ?> - Cafe Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/color-palette.css" rel="stylesheet">
    <style>
        body {
            background-color: var(--bg-secondary);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .sidebar {
            background: var(--gradient-sidebar);
            min-height: 100vh;
            box-shadow: 2px 0 10px var(--shadow-primary);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 15px 20px;
            border-radius: var(--radius-medium);
            margin: 5px 10px;
            transition: var(--transition-normal);
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: var(--text-white);
        }
        .sidebar .nav-link i {
            width: 25px;
        }
        .main-content {
            padding: var(--spacing-lg);
        }
        .stat-card {
            background: var(--bg-white);
            border-radius: var(--radius-large);
            padding: var(--spacing-lg);
            box-shadow: 0 5px 15px var(--shadow-secondary);
            transition: var(--transition-normal);
            border-left: 4px solid;
        }
        .stat-card.income { border-left-color: var(--success-color); }
        .stat-card.outcome { border-left-color: var(--danger-color); }
        .stat-card.balance { border-left-color: var(--primary-teal); }
        
        .stat-card .main-val {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: #000000;
        }
        
        .stat-card .main-label {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .sub-stats-grid {
            display: grid;
            grid-template-columns: 1px 1fr 1fr;
            gap: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(0,0,0,0.05);
            align-items: center;
        }
        
        .divider-v {
            background-color: rgba(0,0,0,0.05);
            height: 30px;
            grid-column: 1;
        }
        
        .mini-stat {
            display: flex;
            flex-direction: column;
        }
        
        .mini-stat-val {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .mini-stat-label {
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: var(--radius-round);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: var(--text-white);
        }
        .stat-icon.income { background: linear-gradient(45deg, var(--success-color), #1e7e34); }
        .stat-icon.outcome { background: linear-gradient(45deg, var(--danger-color), #c82333); }
        .stat-icon.balance { background: var(--gradient-primary); }
        .header {
            background: var(--bg-white);
            border-radius: var(--radius-large);
            padding: var(--spacing-lg);
            box-shadow: 0 2px 10px var(--shadow-light);
            margin-bottom: 30px;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-round);
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-white);
            font-weight: bold;
        }
        .payment-form {
            background: var(--bg-white);
            border-radius: var(--radius-large);
            padding: var(--spacing-lg);
            box-shadow: 0 5px 15px var(--shadow-secondary);
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            background: var(--bg-white);
            border-radius: var(--radius-large);
            padding: var(--spacing-lg);
            box-shadow: 0 5px 15px var(--shadow-secondary);
        }
        .payments-list {
            background: var(--bg-white);
            border-radius: var(--radius-large);
            padding: var(--spacing-lg);
            box-shadow: 0 5px 15px var(--shadow-secondary);
        }
        .table th {
            border-top: none;
            font-weight: 600;
            color: var(--text-muted);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
        }
        .rtl .sidebar {
            right: 0;
            box-shadow: -2px 0 10px rgba(0,0,0,0.1);
        }
        .rtl .main-content {
            margin-right: 250px;
        }
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                left: -250px;
                z-index: 1000;
                transition: left 0.3s ease;
            }
            .sidebar.show {
                left: 0;
            }
            .main-content {
                margin-left: 0;
            }
            .rtl .sidebar {
                right: -250px;
                left: auto;
            }
            .rtl .sidebar.show {
                right: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 p-0 sidebar">
                <div class="p-4">
                    <h4 class="text-white mb-4">
                        <i class="fas fa-coffee me-2"></i>
                        <?php echo $lang['login_title']; ?>
                    </h4>
                    <nav class="nav flex-column">
                        <a href="dashboard.php" class="nav-link">
                            <i class="fas fa-tachometer-alt me-2"></i>
                            <?php echo $lang['dashboard']; ?>
                        </a>
                        <a href="menu_management.php" class="nav-link">
                            <i class="fas fa-utensils me-2"></i>
                            <?php echo $lang['menu_management_nav']; ?>
                        </a>
                        <a href="inventory_management.php" class="nav-link">
                            <i class="fas fa-boxes me-2"></i>
                            <?php echo $lang['inventory_nav']; ?>
                        </a>
                        <a href="table_management.php" class="nav-link">
                            <i class="fas fa-table me-2"></i>
                            <?php echo $lang['table_management_nav']; ?>
                        </a>
                        <a href="order_management.php" class="nav-link">
                            <i class="fas fa-shopping-cart me-2"></i>
                            <?php echo $lang['order_management_nav']; ?>
                        </a>
                        <a href="staff_management.php" class="nav-link">
                            <i class="fas fa-user-tie me-2"></i>
                            <?php echo $lang['employees']; ?>
                        </a>
                        <a href="payment_management.php" class="nav-link active">
                            <i class="fas fa-money-bill-wave me-2"></i>
                            <?php echo $lang['payment_management']; ?>
                        </a>
                        <a href="reports.php" class="nav-link">
                            <i class="fas fa-chart-bar me-2"></i>
                            <?php echo $lang['reports']; ?>
                        </a>
                        <a href="#" class="nav-link">
                            <i class="fas fa-cog me-2"></i>
                            <?php echo $lang['settings']; ?>
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <!-- Header -->
                <div class="header">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h2 class="mb-0"><?php echo $lang['payment_management']; ?></h2>
                            <p class="text-muted mb-0">
                                <?php echo $lang['welcome']; ?>, <?php echo $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'; ?>!
                            </p>
                        </div>
                        <div class="col-md-6 text-end">
                            <div class="dropdown d-inline-block me-3">
                                <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-globe"></i> <?php echo $lang['language']; ?>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="?lang=en">English</a></li>
                                    <li><a class="dropdown-item" href="?lang=ar">العربية</a></li>
                                </ul>
                            </div>
                            <div class="dropdown d-inline-block">
                                <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <div class="user-avatar d-inline-block me-2">
                                        <?php echo strtoupper(substr($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'U', 0, 1)); ?>
                                    </div>
                                    <?php echo $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'; ?>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i> Profile</a></li>
                                    <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i> Settings</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i> <?php echo $lang['logout']; ?></a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Success/Error Messages -->
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <!-- Income Master Card -->
                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="stat-card income h-100">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <div class="main-label text-success text-uppercase">Total Income</div>
                                    <div class="main-val"><?php echo $lang['currency_symbol'] ?? 'EGP'; ?> <?php echo number_format($income_total, 2); ?></div>
                                </div>
                                <div class="stat-icon income">
                                    <i class="fas fa-arrow-up"></i>
                                </div>
                            </div>
                            <div class="sub-stats-grid">
                                <div class="mini-stat">
                                    <span class="mini-stat-val text-success">+<?php echo number_format($income_daily, 2); ?></span>
                                    <span class="mini-stat-label"><?php echo $lang['daily_stats']; ?></span>
                                </div>
                                <div class="divider-v"></div>
                                <div class="mini-stat">
                                    <span class="mini-stat-val text-success">+<?php echo number_format($income_monthly, 2); ?></span>
                                    <span class="mini-stat-label"><?php echo $lang['monthly_stats']; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Outcome Master Card -->
                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="stat-card outcome h-100">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <div class="main-label text-danger text-uppercase">Total Outcome</div>
                                    <div class="main-val"><?php echo $lang['currency_symbol'] ?? 'EGP'; ?> <?php echo number_format($outcome_total, 2); ?></div>
                                </div>
                                <div class="stat-icon outcome">
                                    <i class="fas fa-arrow-down"></i>
                                </div>
                            </div>
                            <div class="sub-stats-grid">
                                <div class="mini-stat">
                                    <span class="mini-stat-val text-danger">-<?php echo number_format($outcome_daily, 2); ?></span>
                                    <span class="mini-stat-label"><?php echo $lang['daily_stats']; ?></span>
                                </div>
                                <div class="divider-v"></div>
                                <div class="mini-stat">
                                    <span class="mini-stat-val text-danger">-<?php echo number_format($outcome_monthly, 2); ?></span>
                                    <span class="mini-stat-label"><?php echo $lang['monthly_stats']; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Balance Master Card -->
                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="stat-card balance h-100">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <div class="main-label text-primary text-uppercase">Total Balance</div>
                                    <div class="main-val"><?php echo $lang['currency_symbol'] ?? 'EGP'; ?> <?php echo number_format($balance_total, 2); ?></div>
                                </div>
                                <div class="stat-icon balance">
                                    <i class="fas fa-balance-scale"></i>
                                </div>
                            </div>
                            <div class="sub-stats-grid">
                                <div class="mini-stat">
                                    <span class="mini-stat-val <?php echo $balance_daily >= 0 ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo ($balance_daily >= 0 ? '+' : '') . number_format($balance_daily, 2); ?>
                                    </span>
                                    <span class="mini-stat-label"><?php echo $lang['daily_stats']; ?></span>
                                </div>
                                <div class="divider-v"></div>
                                <div class="mini-stat">
                                    <span class="mini-stat-val <?php echo $balance_monthly >= 0 ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo ($balance_monthly >= 0 ? '+' : '') . number_format($balance_monthly, 2); ?>
                                    </span>
                                    <span class="mini-stat-label"><?php echo $lang['monthly_stats']; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Form -->
                <div class="payment-form">
                    <h4 class="mb-4">Add New Payment Entry</h4>
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="type" class="form-label">Type</label>
                                <select class="form-select" id="type" name="type" required>
                                    <option value="">Select Type</option>
                                    <option value="income">Income</option>
                                    <option value="outcome">Outcome</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="amount" class="form-label">Amount</label>
                                <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0" placeholder="0.00" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Enter description..." required></textarea>
                        </div>
                        <button type="submit" name="add_payment" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Add Payment Entry
                        </button>
                    </form>
                </div>

                <!-- Payments List -->
                <div class="payments-list">
                    <h4 class="mb-4">Payment Entries</h4>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Description</th>
                                    <th>Created By</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($payments_result->num_rows > 0): ?>
                                    <?php while ($payment = $payments_result->fetch_assoc()): ?>
                                        <tr>
                                            <td>#<?php echo $payment['id']; ?></td>
                                            <td>
                                                <?php
                                                $type_class = '';
                                                $type_icon = '';
                                                switch($payment['type']) {
                                                    case 'income':
                                                        $type_class = 'bg-success';
                                                        $type_icon = 'fa-arrow-up';
                                                        break;
                                                    case 'outcome':
                                                        $type_class = 'bg-danger';
                                                        $type_icon = 'fa-arrow-down';
                                                        break;
                                                    default:
                                                        $type_class = 'bg-secondary';
                                                        $type_icon = 'fa-question';
                                                }
                                                ?>
                                                <span class="badge <?php echo $type_class; ?>">
                                                    <i class="fas <?php echo $type_icon; ?> me-1"></i>
                                                    <?php echo ucfirst($payment['type']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $lang['currency_symbol'] ?? 'EGP'; ?> <?php echo number_format($payment['amount'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($payment['description']); ?></td>
                                            <td><?php echo $payment['created_by_name'] ?? 'Unknown'; ?></td>
                                            <td><?php echo date('M d, Y H:i', strtotime($payment['created_at'])); ?></td>
                                            <td>
                                                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this payment entry?');" style="display: inline;">
                                                    <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                                    <button type="submit" name="delete_payment" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash me-1"></i>Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">No payment entries found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
