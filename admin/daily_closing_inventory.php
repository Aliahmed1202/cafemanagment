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
    header('Location: daily_closing_inventory.php');
    exit;
}

// Ensure language variables are set if not loaded from file
$is_arabic = $_SESSION['lang'] === 'ar';

if (!isset($lang['daily_closing_inventory'])) $lang['daily_closing_inventory'] = $is_arabic ? 'الإغلاق اليومي للمخزون' : 'Daily Closing Inventory';
if (!isset($lang['closing_date'])) $lang['closing_date'] = $is_arabic ? 'تاريخ الإغلاق' : 'Closing Date';
if (!isset($lang['opening_stock'])) $lang['opening_stock'] = $is_arabic ? 'المخزون الافتتاحي' : 'Opening Stock';
if (!isset($lang['closing_stock'])) $lang['closing_stock'] = $is_arabic ? 'المخزون الختامي' : 'Closing Stock';
if (!isset($lang['consumed_quantity'])) $lang['consumed_quantity'] = $is_arabic ? 'الكمية المستهلكة' : 'Consumed Quantity';
if (!isset($lang['total_cost'])) $lang['total_cost'] = $is_arabic ? 'التكلفة الإجمالية' : 'Total Cost';
if (!isset($lang['notes'])) $lang['notes'] = $is_arabic ? 'ملاحظات' : 'Notes';
if (!isset($lang['record_closing'])) $lang['record_closing'] = $is_arabic ? 'تسجيل الإغلاق' : 'Record Closing';
if (!isset($lang['closing_history'])) $lang['closing_history'] = $is_arabic ? 'سجل الإغلاق' : 'Closing History';
if (!isset($lang['daily_consumption'])) $lang['daily_consumption'] = $is_arabic ? 'الاستهلاك اليومي' : 'Daily Consumption';
if (!isset($lang['cost_analysis'])) $lang['cost_analysis'] = $is_arabic ? 'تحليل التكاليف' : 'Cost Analysis';
if (!isset($lang['total_consumed'])) $lang['total_consumed'] = $is_arabic ? 'إجمالي المستهلك' : 'Total Consumed';
if (!isset($lang['total_cost_today'])) $lang['total_cost_today'] = $is_arabic ? 'التكلفة الإجمالية اليوم' : 'Total Cost Today';
if (!isset($lang['record_closing_success'])) $lang['record_closing_success'] = $is_arabic ? 'تم تسجيل الإغلاق اليومي بنجاح!' : 'Daily closing recorded successfully!';
if (!isset($lang['closing_updated_success'])) $lang['closing_updated_success'] = $is_arabic ? 'تم تحديث الإغلاق اليومي بنجاح!' : 'Daily closing updated successfully!';
if (!isset($lang['closing_deleted_success'])) $lang['closing_deleted_success'] = $is_arabic ? 'تم حذف الإغلاق اليومي بنجاح!' : 'Daily closing deleted successfully!';
if (!isset($lang['select_date'])) $lang['select_date'] = $is_arabic ? 'اختر التاريخ' : 'Select Date';
if (!isset($lang['auto_calculate'])) $lang['auto_calculate'] = $is_arabic ? 'حساب تلقائي' : 'Auto Calculate';
if (!isset($lang['track_daily_consumption'])) $lang['track_daily_consumption'] = $is_arabic ? 'تتبع استهلاك المخزون اليومي والتكاليف' : 'Track daily inventory consumption and costs';
if (!isset($lang['inventory_nav'])) $lang['inventory_nav'] = $is_arabic ? 'إدارة المخزون' : 'Inventory Management';
if (!isset($lang['back_to_dashboard'])) $lang['back_to_dashboard'] = $is_arabic ? 'العودة إلى لوحة التحكم' : 'Back to Dashboard';
if (!isset($lang['logout'])) $lang['logout'] = $is_arabic ? 'تسجيل الخروج' : 'Logout';
if (!isset($lang['language'])) $lang['language'] = $is_arabic ? 'اللغة' : 'Language';
if (!isset($lang['profile'])) $lang['profile'] = $is_arabic ? 'الملف الشخصي' : 'Profile';
if (!isset($lang['settings'])) $lang['settings'] = $is_arabic ? 'الإعدادات' : 'Settings';

// Ensure all required language keys exist
$lang = array_merge([
    'total_consumed' => $lang['total_consumed'] ?? 'Total Consumed',
    'total_cost_today' => $lang['total_cost_today'] ?? 'Total Cost Today',
    'record_closing' => $lang['record_closing'] ?? 'Record Closing',
    'closing_history' => $lang['closing_history'] ?? 'Closing History',
    'notes' => $lang['notes'] ?? 'Notes',
    'track_daily_consumption' => $lang['track_daily_consumption'] ?? 'Track daily inventory consumption and costs',
    'inventory_nav' => $lang['inventory_nav'] ?? 'Inventory Management',
    'back_to_dashboard' => $lang['back_to_dashboard'] ?? 'Back to Dashboard',
    'logout' => $lang['logout'] ?? 'Logout',
    'auto_calculate' => $lang['auto_calculate'] ?? 'Auto Calculate',
    'record_closing_success' => $lang['record_closing_success'] ?? 'Daily closing recorded successfully!',
    'closing_updated_success' => $lang['closing_updated_success'] ?? 'Daily closing updated successfully!',
    'closing_deleted_success' => $lang['closing_deleted_success'] ?? 'Daily closing deleted successfully!'
], $lang);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'record_closing') {
        $closing_date = $_POST['closing_date'] ?? date('Y-m-d');
        $inventory_id = intval($_POST['inventory_id'] ?? 0);
        $opening_stock = floatval($_POST['opening_stock'] ?? 0);
        $closing_stock = floatval($_POST['closing_stock'] ?? 0);
        $unit_cost = floatval($_POST['unit_cost'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        
        // Server-side calculation for accuracy
        $consumed_quantity = $opening_stock - $closing_stock;
        $total_cost = $consumed_quantity * $unit_cost;
        
        if ($inventory_id > 0 && $closing_date) {
            // Check if record already exists for this date and inventory item
            $check_sql = "SELECT id FROM daily_closing_inventory WHERE closing_date = ? AND inventory_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("si", $closing_date, $inventory_id);
            $check_stmt->execute();
            $existing = $check_stmt->get_result()->fetch_assoc();
            
            if ($existing) {
                // Update existing record
                $sql = "UPDATE daily_closing_inventory SET opening_stock = ?, closing_stock = ?, consumed_quantity = ?, unit_cost = ?, total_cost = ?, notes = ?, created_by = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ddddsisi", $opening_stock, $closing_stock, $consumed_quantity, $unit_cost, $total_cost, $notes, $_SESSION['user_id'], $existing['id']);
                $stmt->execute();
                $success_message = $lang['closing_updated_success'];
            } else {
                // Insert new record
                $sql = "INSERT INTO daily_closing_inventory (closing_date, inventory_id, opening_stock, closing_stock, consumed_quantity, unit_cost, total_cost, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sidddddsi", $closing_date, $inventory_id, $opening_stock, $closing_stock, $consumed_quantity, $unit_cost, $total_cost, $notes, $_SESSION['user_id']);
                $stmt->execute();
                $success_message = $lang['record_closing_success'];
            }
            
            // Update current stock in inventory
            $update_sql = "UPDATE inventory SET current_stock = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("di", $closing_stock, $inventory_id);
            $update_stmt->execute();
        }
    }
    
    elseif ($action == 'delete_closing') {
        $closing_id = intval($_POST['closing_id'] ?? 0);
        if ($closing_id > 0) {
            $sql = "DELETE FROM daily_closing_inventory WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $closing_id);
            $stmt->execute();
            $success_message = $lang['closing_deleted_success'];
        }
    }
}

// Get inventory items with last recorded notes
$inventory_sql = "SELECT i.*, 
                   (SELECT notes FROM daily_closing_inventory WHERE inventory_id = i.id ORDER BY closing_date DESC, created_at DESC LIMIT 1) as last_notes
                FROM inventory i 
                WHERE i.status = 'active' 
                ORDER BY i.item_name";
$inventory_result = $conn->query($inventory_sql);
$inventory_items = $inventory_result->fetch_all(MYSQLI_ASSOC);

// Get today's closing records
$today = date('Y-m-d');
$today_closing_sql = "SELECT dci.*, i.item_name, i.item_name_ar, i.unit, IFNULL(u.username, 'Unknown') as username FROM daily_closing_inventory dci JOIN inventory i ON dci.inventory_id = i.id LEFT JOIN users u ON dci.created_by = u.id WHERE dci.closing_date = ? ORDER BY i.item_name";
$today_stmt = $conn->prepare($today_closing_sql);
$today_stmt->bind_param("s", $today);
$today_stmt->execute();
$today_closing = $today_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get closing history
$history_sql = "SELECT dci.*, i.item_name, i.item_name_ar, i.unit, IFNULL(u.username, 'Unknown') as username FROM daily_closing_inventory dci JOIN inventory i ON dci.inventory_id = i.id LEFT JOIN users u ON dci.created_by = u.id ORDER BY dci.closing_date DESC, dci.created_at DESC LIMIT 50";
$history_result = $conn->query($history_sql);
$closing_history = $history_result->fetch_all(MYSQLI_ASSOC);

// Calculate today's totals
$total_consumed_today = 0;
$total_cost_today = 0;
foreach ($today_closing as $record) {
    $total_consumed_today += $record['consumed_quantity'];
    $total_cost_today += $record['total_cost'];
}
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>" dir="<?php echo $_SESSION['lang'] == 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['daily_closing_inventory']; ?> - Cafe Management System</title>
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
        .page-header {
            background: var(--gradient-primary);
            color: var(--text-white);
            padding: var(--spacing-lg);
            border-radius: var(--radius-large);
            margin-bottom: 30px;
            box-shadow: 0 10px 25px var(--shadow-primary);
        }
        
        .nav-tabs .nav-link {
            border: 2px solid var(--border-light);
            border-radius: var(--radius-medium);
            margin-right: var(--spacing-sm);
            font-weight: 600;
            transition: var(--transition-normal);
        }
        
        .nav-tabs .nav-link.active {
            background: var(--gradient-primary);
            border-color: var(--primary-teal);
            color: var(--text-white);
        }
        
        .form-control, .form-select {
            border-radius: var(--radius-medium);
            border: 2px solid var(--border-light);
            padding: 12px 16px;
            transition: var(--transition-normal);
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-teal);
            box-shadow: 0 0 0 4px rgba(var(--primary-teal-rgb), 0.15);
        }
        
        .btn-primary {
            background: var(--gradient-primary);
            border: none;
            border-radius: var(--radius-medium);
            padding: 12px 24px;
            font-weight: 600;
            transition: var(--transition-normal);
        }
        
        .btn-primary:hover {
            transform: var(--hover-transform);
            box-shadow: 0 8px 25px var(--shadow-primary);
        }
        
        .closing-card {
            background: var(--bg-white);
            border-radius: var(--radius-large);
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-lg);
            box-shadow: 0 4px 15px var(--shadow-secondary);
            transition: var(--transition-normal);
        }
        
        .closing-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px var(--shadow-secondary);
        }
        
        .stat-card {
            background: var(--gradient-primary);
            color: var(--text-white);
            border-radius: var(--radius-large);
            padding: var(--spacing-lg);
            text-align: center;
            box-shadow: 0 4px 15px var(--shadow-primary);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .alert {
            border-radius: var(--radius-large);
            border: none;
            backdrop-filter: blur(15px);
        }
        
        .alert-success {
            background: linear-gradient(135deg, rgba(var(--success-color), 0.15), rgba(var(--success-color), 0.08));
            color: var(--success-color);
            border: 2px solid rgba(var(--success-color), 0.3);
        }
        
        .alert-danger {
            background: linear-gradient(135deg, rgba(var(--danger-color), 0.15), rgba(var(--danger-color), 0.08));
            color: var(--danger-color);
            border: 2px solid rgba(var(--danger-color), 0.3);
        }
        
        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
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
                            Table Management
                        </a>
                        <a href="order_management.php" class="nav-link">
                            <i class="fas fa-shopping-cart me-2"></i>
                            Order Management
                        </a>
                        <a href="staff_management.php" class="nav-link">
                            <i class="fas fa-user-tie me-2"></i>
                            <?php echo $lang['employees']; ?>
                        </a>
                        <a href="payment_management.php" class="nav-link">
                            <i class="fas fa-money-bill-wave me-2"></i>
                            Payment Management
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
                            <h2 class="mb-0"><?php echo $lang['daily_closing_inventory']; ?></h2>
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
                                    <li><a class="dropdown-item" href="?lang=ar">Arabic</a></li>
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
                            <a href="inventory_management.php" class="btn btn-primary ms-2">
                                <i class="fas fa-boxes me-2"></i><?php echo $lang['inventory_nav']; ?>
                            </a>
                        </div>
                    </div>
                </div>

        <!-- Success/Error Messages -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Today's Statistics -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($total_consumed_today, 2); ?></div>
                    <div><?php echo $lang['total_consumed']; ?></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $lang['currency_symbol'] ?? 'EGP'; ?> <?php echo number_format($total_cost_today, 2); ?></div>
                    <div><?php echo $lang['total_cost_today']; ?></div>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs mb-4" id="closingTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="record-tab" data-bs-toggle="tab" data-bs-target="#record" type="button" role="tab">
                    <i class="fas fa-plus me-2"></i><?php echo $lang['record_closing']; ?>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="today-tab" data-bs-toggle="tab" data-bs-target="#today" type="button" role="tab">
                    <i class="fas fa-calendar-day me-2"></i>Today's Closing
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab">
                    <i class="fas fa-history me-2"></i><?php echo $lang['closing_history']; ?>
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="closingTabContent">
            <!-- Record Closing Tab -->
            <div class="tab-pane fade show active" id="record" role="tabpanel">
                <div class="row">
                    <div class="col-md-8">
                        <form method="POST">
                            <input type="hidden" name="action" value="record_closing">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label"><?php echo $lang['closing_date']; ?></label>
                                        <input type="date" class="form-control" name="closing_date" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Inventory Item</label>
                                        <select class="form-select" name="inventory_id" id="inventorySelect" required>
                                            <option value="">Select Item</option>
                                            <?php foreach ($inventory_items as $item): ?>
                                                <option value="<?php echo $item['id']; ?>" 
                                                        data-unit="<?php echo $item['unit']; ?>" 
                                                        data-cost="<?php echo $item['unit_cost']; ?>" 
                                                        data-stock="<?php echo $item['current_stock']; ?>"
                                                        data-notes="<?php echo htmlspecialchars($item['last_notes'] ?? ''); ?>">
                                                    <?php echo htmlspecialchars($item[$_SESSION['lang'] == 'ar' ? 'item_name_ar' : 'item_name'] ?? ''); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label"><?php echo $lang['opening_stock']; ?></label>
                                        <input type="number" class="form-control" name="opening_stock" id="openingStock" step="0.01" min="0" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label"><?php echo $lang['closing_stock']; ?></label>
                                        <input type="number" class="form-control" name="closing_stock" id="closingStock" step="0.01" min="0" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label"><?php echo $lang['consumed_quantity']; ?></label>
                                        <input type="number" class="form-control" name="consumed_quantity" id="consumedQuantity" step="0.01" min="0" readonly>
                                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="calculateConsumed()">
                                            <i class="fas fa-calculator me-1"></i><?php echo $lang['auto_calculate']; ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label"><?php echo $lang['unit_cost'] ?? 'Unit Cost'; ?> (<?php echo $lang['currency_symbol'] ?? 'EGP'; ?>)</label>
                                        <input type="number" class="form-control" name="unit_cost" id="unitCost" step="0.01" min="0" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label"><?php echo $lang['total_cost']; ?> (<?php echo $lang['currency_symbol'] ?? 'EGP'; ?>)</label>
                                        <input type="number" class="form-control" name="total_cost" id="totalCost" step="0.01" min="0" readonly>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label"><?php echo $lang['notes']; ?></label>
                                <textarea class="form-control" name="notes" rows="3"></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i><?php echo $lang['record_closing']; ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Today's Closing Tab -->
            <div class="tab-pane fade" id="today" role="tabpanel">
                <div class="row">
                    <?php foreach ($today_closing as $record): ?>
                        <div class="col-md-6">
                            <div class="closing-card">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="mb-0"><?php echo htmlspecialchars($record[$_SESSION['lang'] == 'ar' ? 'item_name_ar' : 'item_name'] ?? ''); ?></h5>
                                    <span class="badge bg-primary"><?php echo $record['unit']; ?></span>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <small class="text-muted"><?php echo $lang['opening_stock']; ?></small>
                                        <div class="fw-bold"><?php echo $record['opening_stock']; ?></div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted"><?php echo $lang['closing_stock']; ?></small>
                                        <div class="fw-bold"><?php echo $record['closing_stock']; ?></div>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <small class="text-muted"><?php echo $lang['consumed_quantity']; ?></small>
                                        <div class="fw-bold text-danger"><?php echo $record['consumed_quantity']; ?></div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted"><?php echo $lang['total_cost']; ?></small>
                                        <div class="fw-bold text-primary"><?php echo $lang['currency_symbol'] ?? 'EGP'; ?> <?php echo number_format($record['total_cost'], 2); ?></div>
                                    </div>
                                </div>
                                
                                <?php if ($record['notes']): ?>
                                    <small class="text-muted d-block mb-3">
                                        <i class="fas fa-sticky-note me-1"></i>
                                        <?php echo htmlspecialchars($record['notes']); ?>
                                    </small>
                                <?php endif; ?>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-user me-1"></i>
                                        <?php echo htmlspecialchars($record['username'] ?? 'Unknown'); ?>
                                    </small>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="delete_closing">
                                        <input type="hidden" name="closing_id" value="<?php echo $record['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this record?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- History Tab -->
            <div class="tab-pane fade" id="history" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Item</th>
                                <th>Opening Stock</th>
                                <th>Closing Stock</th>
                                <th>Consumed</th>
                                <th>Total Cost</th>
                                <th>Recorded By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($closing_history as $record): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($record['closing_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($record[$_SESSION['lang'] == 'ar' ? 'item_name_ar' : 'item_name'] ?? ''); ?></td>
                                    <td><?php echo $record['opening_stock']; ?> <?php echo $record['unit']; ?></td>
                                    <td><?php echo $record['closing_stock']; ?> <?php echo $record['unit']; ?></td>
                                    <td class="text-danger"><?php echo $record['consumed_quantity']; ?> <?php echo $record['unit']; ?></td>
                                    <td class="text-primary"><?php echo $lang['currency_symbol'] ?? 'EGP'; ?> <?php echo number_format($record['total_cost'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($record['username'] ?? 'Unknown'); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete_closing">
                                            <input type="hidden" name="closing_id" value="<?php echo $record['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this record?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-fill inventory details when item is selected
        function setupInventoryAutoFill() {
            const inventorySelect = document.getElementById('inventorySelect');
            if (!inventorySelect) return;
            
            inventorySelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                if (!selectedOption || this.value === "") {
                    // Clear fields if no item selected
                    document.getElementById('openingStock').value = '';
                    document.getElementById('closingStock').value = '';
                    document.getElementById('consumedQuantity').value = '';
                    document.getElementById('unitCost').value = '';
                    document.getElementById('totalCost').value = '';
                    document.querySelector('textarea[name="notes"]').value = '';
                    return;
                }
                
                const cost = selectedOption.dataset.cost;
                const stock = selectedOption.dataset.stock;
                const notes = selectedOption.dataset.notes;
                
                // Set values
                document.getElementById('openingStock').value = stock || 0;
                document.getElementById('closingStock').value = stock || 0; // Default closing to current stock
                document.getElementById('unitCost').value = cost || 0;
                document.querySelector('textarea[name="notes"]').value = notes || '';
                
                // Auto-calculate
                calculateConsumed();
            });
        }
        
        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            setupInventoryAutoFill();
            
            // Add input listeners for real-time recalculation
            document.getElementById('openingStock').addEventListener('input', calculateConsumed);
            document.getElementById('closingStock').addEventListener('input', calculateConsumed);
        });
        
        // Calculate consumed quantity
        function calculateConsumed() {
            const opening = parseFloat(document.getElementById('openingStock').value) || 0;
            const closing = parseFloat(document.getElementById('closingStock').value) || 0;
            const consumed = opening - closing;
            
            document.getElementById('consumedQuantity').value = consumed.toFixed(2);
            calculateTotal();
        }
        
        // Calculate total cost
        function calculateTotal() {
            const consumed = parseFloat(document.getElementById('consumedQuantity').value) || 0;
            const cost = parseFloat(document.getElementById('unitCost').value) || 0;
            const total = consumed * cost;
            
            document.getElementById('totalCost').value = total.toFixed(2);
        }
    </script>
</body>
</html>
