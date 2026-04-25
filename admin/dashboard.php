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

// Set default language or get from session
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

// Handle language change
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
    header('Location: dashboard.php');
    exit;
}

// Ensure language variables are set if not loaded from file
$is_arabic = $_SESSION['lang'] === 'ar';

if (!isset($lang['dashboard'])) $lang['dashboard'] = $is_arabic ? 'لوحة التحكم' : 'Dashboard';
if (!isset($lang['menu_management'])) $lang['menu_management'] = $is_arabic ? 'إدارة القائمة' : 'Menu Management';
if (!isset($lang['inventory_management'])) $lang['inventory_management'] = $is_arabic ? 'إدارة المخزون' : 'Inventory Management';
if (!isset($lang['table_management'])) $lang['table_management'] = $is_arabic ? 'إدارة الطاولات' : 'Table Management';
if (!isset($lang['order_management'])) $lang['order_management'] = $is_arabic ? 'إدارة الطلبات' : 'Order Management';
if (!isset($lang['staff_management'])) $lang['staff_management'] = $is_arabic ? 'إدارة الموظفين' : 'Staff Management';
if (!isset($lang['employees'])) $lang['employees'] = $is_arabic ? 'الموظفون' : 'Employees';
if (!isset($lang['payment_management'])) $lang['payment_management'] = $is_arabic ? 'إدارة الدفعات' : 'Payment Management';
if (!isset($lang['reports'])) $lang['reports'] = $is_arabic ? 'التقارير' : 'Reports';
if (!isset($lang['settings'])) $lang['settings'] = $is_arabic ? 'الإعدادات' : 'Settings';
if (!isset($lang['welcome'])) $lang['welcome'] = $is_arabic ? 'مرحباً' : 'Welcome';
if (!isset($lang['total_revenue'])) $lang['total_revenue'] = $is_arabic ? 'إجمالي الإيرادات' : 'Total Revenue';
if (!isset($lang['orders_today'])) $lang['orders_today'] = $is_arabic ? 'الطلبات اليوم' : 'Orders Today';
if (!isset($lang['profit_today'])) $lang['profit_today'] = $is_arabic ? 'الربح اليوم' : 'Profit Today';
if (!isset($lang['pending_orders'])) $lang['pending_orders'] = $is_arabic ? 'الطلبات المعلقة' : 'Pending Orders';
if (!isset($lang['completed_orders'])) $lang['completed_orders'] = $is_arabic ? 'الطلبات المكتملة' : 'Completed Orders';
if (!isset($lang['total_customers'])) $lang['total_customers'] = $is_arabic ? 'إجمالي العملاء' : 'Total Customers';
if (!isset($lang['recent_orders'])) $lang['recent_orders'] = $is_arabic ? 'الطلبات الأخيرة' : 'Recent Orders';
if (!isset($lang['language'])) $lang['language'] = $is_arabic ? 'اللغة' : 'Language';
if (!isset($lang['logout'])) $lang['logout'] = $is_arabic ? 'تسجيل الخروج' : 'Logout';
if (!isset($lang['profile'])) $lang['profile'] = $is_arabic ? 'الملف الشخصي' : 'Profile';
if (!isset($lang['daily_closing'])) $lang['daily_closing'] = $is_arabic ? 'الإغلاق اليومي' : 'Daily Closing';

// Get dashboard statistics
$today_start = date('Y-m-d 00:00:00');
$today_end = date('Y-m-d 23:59:59');

// Orders Today (count)
$today_orders_sql = "SELECT COUNT(*) as count FROM orders WHERE created_at BETWEEN '$today_start' AND '$today_end'";
$today_orders = $conn->query($today_orders_sql)->fetch_assoc()['count'];

// Orders Revenue Today (sum of order items for completed orders)
$today_order_revenue_sql = "SELECT SUM(oi.total_price) as revenue 
                           FROM order_items oi 
                           JOIN orders o ON oi.order_id = o.id 
                           WHERE o.status = 'completed' AND o.created_at BETWEEN '$today_start' AND '$today_end'";
$today_order_revenue_res = $conn->query($today_order_revenue_sql);
$today_order_revenue = ($today_order_revenue_res) ? $today_order_revenue_res->fetch_assoc()['revenue'] ?? 0 : 0;

// Payment Income Today
$today_payment_income_sql = "SELECT SUM(amount) as income FROM payments WHERE type = 'income' AND created_at BETWEEN '$today_start' AND '$today_end'";
$today_payment_income_res = $conn->query($today_payment_income_sql);
$today_payment_income = ($today_payment_income_res) ? $today_payment_income_res->fetch_assoc()['income'] ?? 0 : 0;

// Combined Revenue Today
$today_revenue = $today_order_revenue + $today_payment_income;

// Costs Today (Orders)
$today_order_cost_sql = "SELECT SUM(mi.cost_price * oi.quantity) as cost 
                        FROM order_items oi 
                        JOIN orders o ON oi.order_id = o.id 
                        JOIN menu_items mi ON oi.menu_item_id = mi.id 
                        WHERE o.status = 'completed' AND o.created_at BETWEEN '$today_start' AND '$today_end'";
$today_order_cost_res = $conn->query($today_order_cost_sql);
$today_order_cost = ($today_order_cost_res) ? $today_order_cost_res->fetch_assoc()['cost'] ?? 0 : 0;

// Payment Outcome Today
$today_payment_outcome_sql = "SELECT SUM(amount) as outcome FROM payments WHERE type = 'outcome' AND created_at BETWEEN '$today_start' AND '$today_end'";
$today_payment_outcome_res = $conn->query($today_payment_outcome_sql);
$today_payment_outcome = ($today_payment_outcome_res) ? $today_payment_outcome_res->fetch_assoc()['outcome'] ?? 0 : 0;

// Combined Profit Today
$today_profit = $today_revenue - $today_order_cost - $today_payment_outcome;

// Global Stats (All-time)
$total_orders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
$pending_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status NOT IN ('completed', 'cancelled')")->fetch_assoc()['count'];
$completed_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'completed'")->fetch_assoc()['count'];
$total_revenue = $conn->query("SELECT SUM(oi.total_price) as revenue FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.status = 'completed'")->fetch_assoc()['revenue'] ?? 0;
$total_customers = $conn->query("SELECT COUNT(*) as count FROM customers")->fetch_assoc()['count'];

// Get recent orders
$recent_orders_sql = "SELECT o.*, c.name as customer_name FROM orders o LEFT JOIN customers c ON o.customer_id = c.id ORDER BY o.created_at DESC LIMIT 5";
$recent_orders_result = $conn->query($recent_orders_sql);
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>" dir="<?php echo $_SESSION['lang'] == 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['dashboard']; ?> - <?php echo $lang['login_title']; ?></title>
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
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card.orders { border-left-color: var(--info-color); }
        .stat-card.revenue { border-left-color: var(--success-color); }
        .stat-card.customers { border-left-color: var(--warning-color); }
        .stat-card.pending { border-left-color: var(--danger-color); }
        .stat-card.completed { border-left-color: var(--primary-teal); }
        .stat-card.today { border-left-color: var(--primary-medium); }
        
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
        .stat-icon.orders { background: var(--gradient-primary); }
        .stat-icon.revenue { background: linear-gradient(45deg, var(--success-color), #1e7e34); }
        .stat-icon.customers { background: linear-gradient(45deg, var(--warning-color), #e0a800); }
        .stat-icon.pending { background: linear-gradient(45deg, var(--danger-color), #c82333); }
        .stat-icon.completed { background: linear-gradient(45deg, var(--primary-teal), var(--primary-dark-teal)); }
        .stat-icon.today { background: linear-gradient(45deg, var(--primary-medium), var(--primary-dark)); }
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
        .recent-orders {
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
                        <a href="dashboard.php" class="nav-link active">
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
                            <?php echo $lang['table_management']; ?>
                        </a>
                        <a href="order_management.php" class="nav-link">
                            <i class="fas fa-shopping-cart me-2"></i>
                            <?php echo $lang['order_management']; ?>
                        </a>
                                                <a href="staff_management.php" class="nav-link">
                            <i class="fas fa-user-tie me-2"></i>
                            <?php echo $lang['employees']; ?>
                        </a>
                        <a href="payment_management.php" class="nav-link">
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
                            <h2 class="mb-0"><?php echo $lang['dashboard']; ?></h2>
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

                <!-- Master Statistics Row -->
                <div class="row mb-4">
                    <!-- Revenue Master Card -->
                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="stat-card revenue h-100">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <div class="main-label text-success text-uppercase"><?php echo $lang['total_revenue']; ?></div>
                                    <div class="main-val"><?php echo $lang['currency_symbol'] ?? 'EGP'; ?> <?php echo number_format($total_revenue, 2); ?></div>
                                </div>
                                <div class="stat-icon revenue">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                            </div>
                            <div class="sub-stats-grid">
                                <div class="mini-stat">
                                    <span class="mini-stat-val text-success">+<?php echo number_format($today_order_revenue, 2); ?></span>
                                    <span class="mini-stat-label">Orders Today</span>
                                </div>
                                <div class="divider-v"></div>
                                <div class="mini-stat">
                                    <span class="mini-stat-val text-success">+<?php echo number_format($today_payment_income, 2); ?></span>
                                    <span class="mini-stat-label">Income Today</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Profit Master Card -->
                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="stat-card balance h-100">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <div class="main-label text-primary text-uppercase"><?php echo $lang['today_profit']; ?></div>
                                    <div class="main-val"><?php echo $lang['currency_symbol'] ?? 'EGP'; ?> <?php echo number_format($today_profit, 2); ?></div>
                                </div>
                                <div class="stat-icon today">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                            </div>
                            <div class="sub-stats-grid">
                                <div class="mini-stat">
                                    <span class="mini-stat-val text-primary"><?php echo number_format($today_revenue, 2); ?></span>
                                    <span class="mini-stat-label">Rev</span>
                                </div>
                                <div class="divider-v"></div>
                                <div class="mini-stat">
                                    <span class="mini-stat-val text-danger">(-<?php echo number_format($today_order_cost + $today_payment_outcome, 2); ?>)</span>
                                    <span class="mini-stat-label">Costs</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Orders & Customers Master Card -->
                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="stat-card orders h-100">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <div class="main-label text-info text-uppercase">Status Overview</div>
                                    <div class="main-val"><?php echo $total_orders; ?> <small style="font-size: 0.9rem; font-weight: normal; color: var(--text-muted);">Total Orders</small></div>
                                </div>
                                <div class="stat-icon orders">
                                    <i class="fas fa-clipboard-list"></i>
                                </div>
                            </div>
                            <div class="sub-stats-vertical mt-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted"><?php echo $lang['pending_orders']; ?></span>
                                    <span class="fw-bold text-danger"><?php echo $pending_orders; ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted"><?php echo $lang['completed_orders']; ?></span>
                                    <span class="fw-bold text-success"><?php echo $completed_orders; ?></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted"><?php echo $lang['total_customers']; ?></span>
                                    <span class="fw-bold text-warning"><?php echo $total_customers; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Stats Row -->
                <div class="row mb-4">
                    <div class="col-xl-6 mb-4">
                        <div class="stat-card pending">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon pending me-3">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div>
                                    <h3 class="mb-0"><?php echo $pending_orders; ?></h3>
                                    <p class="text-muted mb-0"><?php echo $lang['pending_orders']; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6 mb-4">
                        <div class="stat-card completed">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon completed me-3">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div>
                                    <h3 class="mb-0"><?php echo $completed_orders; ?></h3>
                                    <p class="text-muted mb-0"><?php echo $lang['completed_orders']; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="recent-orders">
                    <h4 class="mb-4">Recent Orders</h4>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recent_orders_result->num_rows > 0): ?>
                                    <?php while ($order = $recent_orders_result->fetch_assoc()): ?>
                                        <tr>
                                            <td>#<?php echo $order['id']; ?></td>
                                            <td><?php echo $order['customer_name'] ?? 'Walk-in Customer'; ?></td>
                                            <td><?php echo $lang['currency_symbol'] ?? 'EGP'; ?> <?php echo number_format($order['total_amount'], 2); ?></td>
                                            <td>
                                                <?php
                                                $status_class = '';
                                                switch($order['status']) {
                                                    case 'pending':
                                                        $status_class = 'bg-warning';
                                                        break;
                                                    case 'completed':
                                                        $status_class = 'bg-success';
                                                        break;
                                                    case 'cancelled':
                                                        $status_class = 'bg-danger';
                                                        break;
                                                    default:
                                                        $status_class = 'bg-secondary';
                                                }
                                                ?>
                                                <span class="badge <?php echo $status_class; ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No orders found</td>
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
