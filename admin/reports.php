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
    header('Location: reports.php');
    exit;
}

// Ensure language variables are set if not loaded from file
$is_arabic = $_SESSION['lang'] === 'ar';

if (!isset($lang['reports'])) $lang['reports'] = $is_arabic ? 'التقارير والتحليلات' : 'Reports & Analytics';
if (!isset($lang['reports_analytics'])) $lang['reports_analytics'] = $is_arabic ? 'تقارير وإحصائيات المطعم' : 'Restaurant Reports and Statistics';
if (!isset($lang['overview'])) $lang['overview'] = $is_arabic ? 'نظرة عامة' : 'Overview';
if (!isset($lang['sales_report'])) $lang['sales_report'] = $is_arabic ? 'تقرير المبيعات' : 'Sales Report';
if (!isset($lang['inventory_report'])) $lang['inventory_report'] = $is_arabic ? 'تقرير المخزون' : 'Inventory Report';
if (!isset($lang['date_range'])) $lang['date_range'] = $is_arabic ? 'نطاق التاريخ' : 'Date Range';
if (!isset($lang['start_date'])) $lang['start_date'] = $is_arabic ? 'تاريخ البدء' : 'Start Date';
if (!isset($lang['end_date'])) $lang['end_date'] = $is_arabic ? 'تاريخ الانتهاء' : 'End Date';
if (!isset($lang['generate_report'])) $lang['generate_report'] = $is_arabic ? 'إنشاء تقرير' : 'Generate Report';
if (!isset($lang['total_orders'])) $lang['total_orders'] = $is_arabic ? 'إجمالي الطلبات' : 'Total Orders';
if (!isset($lang['total_revenue'])) $lang['total_revenue'] = $is_arabic ? 'إجمالي الإيرادات' : 'Total Revenue';
if (!isset($lang['avg_order_value'])) $lang['avg_order_value'] = $is_arabic ? 'متوسط قيمة الطلب' : 'Avg Order Value';
if (!isset($lang['unique_customers'])) $lang['unique_customers'] = $is_arabic ? 'العملاء الفريدين' : 'Unique Customers';
if (!isset($lang['product_name'])) $lang['product_name'] = $is_arabic ? 'اسم المنتج' : 'Product Name';
if (!isset($lang['category'])) $lang['category'] = $is_arabic ? 'الفئة' : 'Category';
if (!isset($lang['total_sold'])) $lang['total_sold'] = $is_arabic ? 'إجمالي المباع' : 'Total Sold';
if (!isset($lang['revenue'])) $lang['revenue'] = $is_arabic ? 'الإيرادات' : 'Revenue';
if (!isset($lang['order_count'])) $lang['order_count'] = $is_arabic ? 'عدد الطلبات' : 'Order Count';
if (!isset($lang['item_name'])) $lang['item_name'] = $is_arabic ? 'اسم الصنف' : 'Item Name';
if (!isset($lang['current_stock'])) $lang['current_stock'] = $is_arabic ? 'المخزون الحالي' : 'Current Stock';
if (!isset($lang['consumed'])) $lang['consumed'] = $is_arabic ? 'مستهلك' : 'Consumed';
if (!isset($lang['consumption_cost'])) $lang['consumption_cost'] = $is_arabic ? 'تكلفة الاستهلاك' : 'Consumption Cost';
if (!isset($lang['stock_value'])) $lang['stock_value'] = $is_arabic ? 'قيمة المخزون' : 'Stock Value';
if (!isset($lang['language'])) $lang['language'] = $is_arabic ? 'اللغة' : 'Language';
if (!isset($lang['logout'])) $lang['logout'] = $is_arabic ? 'تسجيل الخروج' : 'Logout';
if (!isset($lang['profile'])) $lang['profile'] = $is_arabic ? 'الملف الشخصي' : 'Profile';
if (!isset($lang['settings'])) $lang['settings'] = $is_arabic ? 'الإعدادات' : 'Settings';

// Get date range from GET parameters or set defaults
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$report_type = $_GET['report_type'] ?? 'overview';

// Product Sales Report
$product_sales_sql = "
    SELECT 
        mi.id,
        mi.name,
        mi.name_ar,
        mi.price,
        c.name as category_name,
        c.name_ar as category_name_ar,
        COALESCE(SUM(oi.quantity), 0) as total_sold,
        COALESCE(SUM(oi.quantity * oi.unit_price), 0) as total_revenue,
        COALESCE(COUNT(DISTINCT o.id), 0) as order_count
    FROM menu_items mi
    LEFT JOIN categories c ON mi.category_id = c.id
    LEFT JOIN order_items oi ON mi.id = oi.menu_item_id
    LEFT JOIN orders o ON oi.order_id = o.id 
        AND o.status = 'completed' 
        AND DATE(o.created_at) BETWEEN '$start_date' AND '$end_date'
    WHERE mi.status = 'active'
    GROUP BY mi.id, mi.name, mi.name_ar, mi.price, c.name, c.name_ar
    ORDER BY total_revenue DESC
";
$product_sales_result = $conn->query($product_sales_sql);

// Inventory Usage Report
$inventory_usage_sql = "
    SELECT 
        i.id,
        i.item_name,
        i.item_name_ar,
        i.current_stock,
        i.unit,
        i.unit_cost,
        COALESCE(dci.consumed_quantity, 0) as total_consumed,
        COALESCE(dci.total_cost, 0) as total_consumption_cost,
        (i.current_stock * i.unit_cost) as current_stock_value
    FROM inventory i
    LEFT JOIN (
        SELECT 
            inventory_id,
            SUM(consumed_quantity) as consumed_quantity,
            SUM(total_cost) as total_cost
        FROM daily_closing_inventory 
        WHERE closing_date BETWEEN '$start_date' AND '$end_date'
        GROUP BY inventory_id
    ) dci ON i.id = dci.inventory_id
    WHERE i.status = 'active'
    ORDER BY total_consumption_cost DESC
";
$inventory_usage_result = $conn->query($inventory_usage_sql);

// Overall Statistics
$stats_sql = "
    SELECT 
        COUNT(DISTINCT o.id) as total_orders,
        COALESCE(SUM(o.total_amount), 0) as total_revenue,
        COALESCE(AVG(o.total_amount), 0) as avg_order_value,
        COUNT(DISTINCT o.customer_id) as unique_customers,
        COUNT(DISTINCT oi.menu_item_id) as unique_products_sold
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.status = 'completed' 
        AND DATE(o.created_at) BETWEEN '$start_date' AND '$end_date'
";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Payment Statistics
$payment_stats_sql = "
    SELECT 
        COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) as total_income,
        COALESCE(SUM(CASE WHEN type = 'outcome' THEN amount ELSE 0 END), 0) as total_outcome,
        COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) - 
        COALESCE(SUM(CASE WHEN type = 'outcome' THEN amount ELSE 0 END), 0) as net_balance
    FROM payments 
    WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date'
";
$payment_stats_result = $conn->query($payment_stats_sql);
$payment_stats = $payment_stats_result->fetch_assoc();

// Top Customers
$top_customers_sql = "
    SELECT 
        c.id,
        c.name,
        COUNT(o.id) as order_count,
        COALESCE(SUM(o.total_amount), 0) as total_spent
    FROM customers c
    LEFT JOIN orders o ON c.id = o.customer_id 
        AND o.status = 'completed' 
        AND DATE(o.created_at) BETWEEN '$start_date' AND '$end_date'
    GROUP BY c.id, c.name
    HAVING order_count > 0
    ORDER BY total_spent DESC
    LIMIT 10
";
$top_customers_result = $conn->query($top_customers_sql);

// Daily Sales Trend
$daily_sales_sql = "
    SELECT 
        DATE(created_at) as sale_date,
        COUNT(*) as order_count,
        COALESCE(SUM(total_amount), 0) as daily_revenue
    FROM orders 
    WHERE status = 'completed' 
        AND DATE(created_at) BETWEEN '$start_date' AND '$end_date'
    GROUP BY DATE(created_at)
    ORDER BY sale_date ASC
";
$daily_sales_result = $conn->query($daily_sales_sql);
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>" dir="<?php echo $_SESSION['lang'] == 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['reports']; ?> - Cafe Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/color-palette.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            padding: 20px;
        }
        .header {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
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
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        .stat-icon.orders { background: linear-gradient(45deg, #007bff, #0056b3); }
        .stat-icon.revenue { background: linear-gradient(45deg, #28a745, #1e7e34); }
        .stat-icon.customers { background: linear-gradient(45deg, #ffc107, #e0a800); }
        .stat-icon.balance { background: linear-gradient(45deg, #fd7e14, #e55a00); }
        .stat-icon.products { background: linear-gradient(45deg, #6f42c1, #563d7c); }
        .stat-icon.avg-order { background: linear-gradient(45deg, #20c997, #1a7a5e); }
        .report-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        .table th {
            border-top: none;
            font-weight: 600;
            color: var(--text-muted);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: var(--gradient-primary);
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .table td {
            padding: var(--spacing-sm);
            vertical-align: middle;
            border-bottom: 1px solid var(--border-light);
            transition: var(--transition-normal);
        }
        
        .table tbody tr:hover {
            background: rgba(var(--primary-teal-rgb), 0.05);
            transform: scale(1.01);
            box-shadow: 0 2px 8px var(--shadow-secondary);
        }
        
        .table-responsive {
            border-radius: var(--radius-large);
            overflow: hidden;
            box-shadow: 0 4px 15px var(--shadow-secondary);
        }
        
        /* Enhanced Report Cards */
        .report-section {
            background: var(--bg-white);
            border-radius: var(--radius-large);
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-lg);
            box-shadow: 0 4px 15px var(--shadow-secondary);
            transition: var(--transition-normal);
            position: relative;
            overflow: hidden;
            border: 1px solid var(--border-light);
        }
        
        .report-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(var(--primary-teal-rgb), 0.03), transparent);
            animation: slideShimmer 3s infinite;
        }
        
        .report-section::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(var(--primary-teal-rgb), 0.1) 0%, transparent 70%);
            filter: blur(40px);
            opacity: 0;
            transition: opacity 0.5s var(--transition-slow);
            pointer-events: none;
        }
        
        .report-section:hover::after {
            opacity: 0.3;
            transform: scale(1.1);
        }
        
        /* Enhanced Stat Cards */
        .stat-card-enhanced {
            background: var(--bg-white);
            border-radius: var(--radius-large);
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-lg);
            box-shadow: 0 4px 15px var(--shadow-secondary);
            transition: var(--transition-normal);
            position: relative;
            overflow: hidden;
            border-left: 4px solid var(--primary-teal);
        }
        
        .stat-card-enhanced::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(var(--primary-teal-rgb), 0.05), transparent);
            animation: slideShimmer 3s infinite;
        }
        
        .stat-card-enhanced::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(var(--primary-teal-rgb), 0.1) 0%, transparent 70%);
            filter: blur(40px);
            opacity: 0;
            transition: opacity 0.5s var(--transition-slow);
            pointer-events: none;
        }
        
        .stat-card-enhanced:hover::after {
            opacity: 0.3;
            transform: scale(1.1);
        }
        
        .stat-card-enhanced:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px var(--shadow-primary);
        }
        
        /* Enhanced Data Grid */
        .data-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-lg);
        }
        
        .data-item {
            background: linear-gradient(135deg, rgba(var(--primary-light-rgb), 0.9), rgba(var(--primary-light-rgb), 0.95));
            border-radius: var(--radius-medium);
            padding: var(--spacing-md);
            text-align: center;
            transition: var(--transition-normal);
            border: 1px solid var(--border-light);
        }
        
        .data-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px var(--shadow-primary);
            background: linear-gradient(135deg, rgba(var(--primary-teal-rgb), 0.1), rgba(var(--primary-dark-teal-rgb), 0.1));
        }
        
        /* Enhanced List Styles */
        .list-enhanced {
            background: var(--bg-white);
            border-radius: var(--radius-medium);
            padding: var(--spacing-md);
            margin-bottom: var(--spacing-md);
            box-shadow: 0 2px 8px var(--shadow-secondary);
        }
        
        .list-enhanced li {
            padding: var(--spacing-md);
            border-bottom: 1px solid var(--border-light);
            transition: var(--transition-normal);
            position: relative;
        }
        
        .list-enhanced li:hover {
            background: rgba(var(--primary-teal-rgb), 0.05);
            padding-left: var(--spacing-lg);
        }
        
        .list-enhanced li::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 6px;
            height: 6px;
            background: var(--primary-teal);
            border-radius: var(--radius-small);
            transform: translateY(-50%);
        }
        
        /* Enhanced Top Items List */
        .top-items-list {
            background: var(--bg-white);
            border-radius: var(--radius-large);
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-lg);
            box-shadow: 0 4px 15px var(--shadow-secondary);
        }
        
        .top-items-list li {
            padding: var(--spacing-md);
            border-bottom: 1px solid var(--border-light);
            transition: var(--transition-normal);
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
        }
        
        .top-items-list li:hover {
            background: rgba(var(--primary-teal-rgb), 0.05);
            padding-left: var(--spacing-lg);
        }
        
        .top-items-list li::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 6px;
            height: 6px;
            background: var(--primary-teal);
            border-radius: var(--radius-small);
            transform: translateY(-50%);
        }
        
        /* Enhanced Chart Container */
        .chart-enhanced {
            background: var(--bg-white);
            border-radius: var(--radius-large);
            padding: var(--spacing-lg);
            box-shadow: 0 4px 15px var(--shadow-secondary);
            position: relative;
            overflow: hidden;
        }
        
        .chart-enhanced::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(var(--primary-teal-rgb), 0.05), transparent);
            animation: slideShimmer 3s infinite;
        }
        
        .chart-enhanced::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(var(--primary-teal-rgb), 0.1) 0%, transparent 70%);
            filter: blur(40px);
            opacity: 0;
            transition: opacity 0.5s var(--transition-slow);
            pointer-events: none;
        }
        
        .chart-enhanced:hover::after {
            opacity: 0.3;
            transform: scale(1.1);
        }
        
        @keyframes slideShimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        /* Enhanced Chart Container */
        .chart-container {
            background: var(--bg-white);
            border-radius: var(--radius-large);
            padding: var(--spacing-lg);
            box-shadow: 0 4px 15px var(--shadow-secondary);
            position: relative;
            overflow: hidden;
        }
        
        .chart-container::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(var(--primary-teal-rgb), 0.1), transparent);
            pointer-events: none;
        }
        
        /* Enhanced Filter Section */
        .filter-section {
            background: var(--bg-white);
            border-radius: var(--radius-large);
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-lg);
            box-shadow: 0 4px 15px var(--shadow-secondary);
            border-left: 4px solid var(--primary-teal);
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
                        <a href="payment_management.php" class="nav-link">
                            <i class="fas fa-money-bill-wave me-2"></i>
                            <?php echo $lang['payment_management_nav']; ?>
                        </a>
                        <a href="reports.php" class="nav-link active">
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
                            <h2 class="mb-0"><?php echo $lang['reports']; ?></h2>
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

                <!-- Date Range Filter -->
                <div class="report-section">
                    <h4 class="mb-4">Date Range Filter</h4>
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="report_type" class="form-label">Report Type</label>
                            <select class="form-select" id="report_type" name="report_type">
                                <option value="overview" <?php echo $report_type == 'overview' ? 'selected' : ''; ?>>Overview</option>
                                <option value="products" <?php echo $report_type == 'products' ? 'selected' : ''; ?>>Products</option>
                                <option value="inventory" <?php echo $report_type == 'inventory' ? 'selected' : ''; ?>>Inventory</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter me-2"></i>Generate Report
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="window.print()">
                                <i class="fas fa-print me-2"></i>Print Report
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Overview Statistics -->
                <div class="row mb-4">
                    <div class="col-xl-2 col-md-4 mb-4">
                        <div class="stat-card orders">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon orders me-3">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <div>
                                    <h3 class="mb-0"><?php echo $stats['total_orders']; ?></h3>
                                    <p class="text-muted mb-0">Total Orders</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4 mb-4">
                        <div class="stat-card revenue">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon revenue me-3">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                                <div>
                                    <h3 class="mb-0"><?php echo $lang['currency_symbol'] ?? 'EGP'; ?> <?php echo number_format($stats['total_revenue'], 2); ?></h3>
                                    <p class="text-muted mb-0">Total Revenue</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4 mb-4">
                        <div class="stat-card customers">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon customers me-3">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div>
                                    <h3 class="mb-0"><?php echo $stats['unique_customers']; ?></h3>
                                    <p class="text-muted mb-0">Unique Customers</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4 mb-4">
                        <div class="stat-card avg-order">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon avg-order me-3">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div>
                                    <h3 class="mb-0"><?php echo $lang['currency_symbol'] ?? 'EGP'; ?> <?php echo number_format($stats['avg_order_value'], 2); ?></h3>
                                    <p class="text-muted mb-0">Avg Order Value</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4 mb-4">
                        <div class="stat-card products">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon products me-3">
                                    <i class="fas fa-box"></i>
                                </div>
                                <div>
                                    <h3 class="mb-0"><?php echo $stats['unique_products_sold']; ?></h3>
                                    <p class="text-muted mb-0">Products Sold</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4 mb-4">
                        <div class="stat-card balance">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon balance me-3">
                                    <i class="fas fa-balance-scale"></i>
                                </div>
                                <div>
                                    <h3 class="mb-0"><?php echo $lang['currency_symbol'] ?? 'EGP'; ?> <?php echo number_format($payment_stats['net_balance'], 2); ?></h3>
                                    <p class="text-muted mb-0">Net Balance</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sales Trend Chart -->
                <div class="report-section">
                    <h4 class="mb-4">Sales Trend</h4>
                    <div class="chart-container">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>

                <!-- Product Sales Report -->
                <div class="report-section">
                    <h4 class="mb-4">Product Sales Report</h4>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Units Sold</th>
                                    <th>Revenue</th>
                                    <th>Orders</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($product_sales_result->num_rows > 0): ?>
                                    <?php while ($product = $product_sales_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $product['name'] ?? $product['name_ar']; ?></td>
                                            <td><?php echo $product['category_name'] ?? $product['category_name_ar']; ?></td>
                                            <td><?php echo $lang['currency_symbol'] ?? 'EGP'; ?> <?php echo number_format($product['price'], 2); ?></td>
                                            <td><?php echo $product['total_sold']; ?></td>
                                            <td><?php echo $lang['currency_symbol'] ?? 'EGP'; ?> <?php echo number_format($product['total_revenue'], 2); ?></td>
                                            <td><?php echo $product['order_count']; ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No sales data found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Inventory Usage Report -->
                <div class="report-section">
                    <h4 class="mb-4">Inventory Usage Report</h4>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Current Stock</th>
                                    <th>Consumed</th>
                                    <th>Unit Cost</th>
                                    <th>Consumption Cost</th>
                                    <th>Stock Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($inventory_usage_result->num_rows > 0): ?>
                                    <?php while ($item = $inventory_usage_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $item['item_name'] ?? $item['item_name_ar']; ?></td>
                                            <td><?php echo $item['current_stock']; ?> <?php echo $item['unit']; ?></td>
                                            <td><?php echo $item['total_consumed']; ?> <?php echo $item['unit']; ?></td>
                                            <td><?php echo $lang['currency_symbol'] ?? 'EGP'; ?> <?php echo number_format($item['unit_cost'], 2); ?></td>
                                            <td><?php echo $lang['currency_symbol'] ?? 'EGP'; ?> <?php echo number_format($item['total_consumption_cost'], 2); ?></td>
                                            <td><?php echo $lang['currency_symbol'] ?? 'EGP'; ?> <?php echo number_format($item['current_stock_value'], 2); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No inventory data found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Top Customers -->
                <div class="report-section">
                    <h4 class="mb-4">Top Customers</h4>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Orders</th>
                                    <th>Total Spent</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($top_customers_result->num_rows > 0): ?>
                                    <?php while ($customer = $top_customers_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $customer['name']; ?></td>
                                            <td><?php echo $customer['order_count']; ?></td>
                                            <td><?php echo $lang['currency_symbol'] ?? 'EGP'; ?> <?php echo number_format($customer['total_spent'], 2); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">No customer data found</td>
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
    <script>
        // Sales Trend Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        const salesLabels = <?php 
            $labels = [];
            $revenues = [];
            while ($row = $daily_sales_result->fetch_assoc()) {
                $labels[] = date('M d', strtotime($row['sale_date']));
                $revenues[] = $row['daily_revenue'];
            }
            echo json_encode($labels);
        ?>;
        const salesData = <?php echo json_encode($revenues); ?>;
        
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: salesLabels,
                datasets: [{
                    label: 'Daily Revenue',
                    data: salesData,
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '<?php echo $lang['currency_symbol'] ?? 'EGP'; ?> ' + value.toFixed(2);
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
