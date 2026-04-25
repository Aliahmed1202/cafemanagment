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
    header('Location: inventory_management.php');
    exit;
}

// Ensure language variables are set if not loaded from file
$is_arabic = $_SESSION['lang'] === 'ar';

if (!isset($lang['inventory_management'])) $lang['inventory_management'] = $is_arabic ? 'إدارة المخزون' : 'Inventory Management';
if (!isset($lang['inventory_nav'])) $lang['inventory_nav'] = $is_arabic ? 'إدارة المخزون' : 'Inventory Management';
if (!isset($lang['manage_inventory'])) $lang['manage_inventory'] = $is_arabic ? 'إدارة مخزون المطعم وتتبع المواد الخام' : 'Manage restaurant inventory and track raw materials';
if (!isset($lang['add_inventory_item'])) $lang['add_inventory_item'] = $is_arabic ? 'إضافة عنصر للمخزون' : 'Add Inventory Item';
if (!isset($lang['item_name'])) $lang['item_name'] = $is_arabic ? 'اسم الصنف' : 'Item Name';
if (!isset($lang['current_stock'])) $lang['current_stock'] = $is_arabic ? 'المخزون الحالي' : 'Current Stock';
if (!isset($lang['unit'])) $lang['unit'] = $is_arabic ? 'الوحدة' : 'Unit';
if (!isset($lang['minimum_stock'])) $lang['minimum_stock'] = $is_arabic ? 'الحد الأدنى للمخزون' : 'Minimum Stock';
if (!isset($lang['unit_cost'])) $lang['unit_cost'] = $is_arabic ? 'تكلفة الوحدة' : 'Unit Cost';
if (!isset($lang['supplier'])) $lang['supplier'] = $is_arabic ? 'المورد' : 'Supplier';
if (!isset($lang['last_restocked'])) $lang['last_restocked'] = $is_arabic ? 'آخر إعادة تخزين' : 'Last Restocked';
if (!isset($lang['status'])) $lang['status'] = $is_arabic ? 'الحالة' : 'Status';
if (!isset($lang['active'])) $lang['active'] = $is_arabic ? 'نشط' : 'Active';
if (!isset($lang['inactive'])) $lang['inactive'] = $is_arabic ? 'غير نشط' : 'Inactive';
if (!isset($lang['actions'])) $lang['actions'] = $is_arabic ? 'الإجراءات' : 'Actions';
if (!isset($lang['edit'])) $lang['edit'] = $is_arabic ? 'تعديل' : 'Edit';
if (!isset($lang['delete'])) $lang['delete'] = $is_arabic ? 'حذف' : 'Delete';
if (!isset($lang['restock'])) $lang['restock'] = $is_arabic ? 'إعادة التخزين' : 'Restock';
if (!isset($lang['language'])) $lang['language'] = $is_arabic ? 'اللغة' : 'Language';
if (!isset($lang['logout'])) $lang['logout'] = $is_arabic ? 'تسجيل الخروج' : 'Logout';
if (!isset($lang['profile'])) $lang['profile'] = $is_arabic ? 'الملف الشخصي' : 'Profile';
if (!isset($lang['settings'])) $lang['settings'] = $is_arabic ? 'الإعدادات' : 'Settings';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'add_inventory_item') {
        $item_name = trim($_POST['item_name'] ?? '');
        $item_name_ar = trim($_POST['item_name_ar'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $current_stock = floatval($_POST['current_stock'] ?? 0);
        $unit = trim($_POST['unit'] ?? '');
        $minimum_stock = floatval($_POST['minimum_stock'] ?? 0);
        $unit_cost = floatval($_POST['unit_cost'] ?? 0);
        $supplier = trim($_POST['supplier'] ?? '');
        $last_restocked = $_POST['last_restocked'] ?? date('Y-m-d');
        
        if (!empty($item_name) && !empty($item_name_ar) && !empty($unit)) {
            $sql = "INSERT INTO inventory (item_name, item_name_ar, description, current_stock, unit, minimum_stock, unit_cost, supplier, last_restocked) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssdsdss", $item_name, $item_name_ar, $description, $current_stock, $unit, $minimum_stock, $unit_cost, $supplier, $last_restocked);
            $stmt->execute();
            $success_message = $lang['item_added_success'];
        } else {
            $error_message = $lang['required_fields'];
        }
    }
    
    elseif ($action == 'edit_inventory_item') {
        $item_id = intval($_POST['item_id'] ?? 0);
        $item_name = trim($_POST['item_name'] ?? '');
        $item_name_ar = trim($_POST['item_name_ar'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $current_stock = floatval($_POST['current_stock'] ?? 0);
        $unit = trim($_POST['unit'] ?? '');
        $minimum_stock = floatval($_POST['minimum_stock'] ?? 0);
        $unit_cost = floatval($_POST['unit_cost'] ?? 0);
        $supplier = trim($_POST['supplier'] ?? '');
        $last_restocked = $_POST['last_restocked'] ?? date('Y-m-d');
        $status = $_POST['status'] ?? 'active';
        
        if ($item_id > 0 && !empty($item_name) && !empty($item_name_ar) && !empty($unit)) {
            $sql = "UPDATE inventory SET item_name = ?, item_name_ar = ?, description = ?, current_stock = ?, unit = ?, minimum_stock = ?, unit_cost = ?, supplier = ?, last_restocked = ?, status = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssdsdsssis", $item_name, $item_name_ar, $description, $current_stock, $unit, $minimum_stock, $unit_cost, $supplier, $last_restocked, $status, $item_id);
            $stmt->execute();
            $success_message = $lang['item_updated_success'];
        } else {
            $error_message = $lang['required_fields'];
        }
    }
    
    elseif ($action == 'delete_inventory_item') {
        $item_id = intval($_POST['item_id'] ?? 0);
        if ($item_id > 0) {
            $sql = "DELETE FROM inventory WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $item_id);
            $stmt->execute();
            $success_message = $lang['item_deleted_success'];
        }
    }
    
    elseif ($action == 'restock_item') {
        $item_id = intval($_POST['item_id'] ?? 0);
        $restock_quantity = floatval($_POST['restock_quantity'] ?? 0);
        
        if ($item_id > 0 && $restock_quantity > 0) {
            $sql = "UPDATE inventory SET current_stock = current_stock + ?, last_restocked = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $current_date = date('Y-m-d');
            $stmt->bind_param("dsi", $restock_quantity, $current_date, $item_id);
            $stmt->execute();
            $success_message = $lang['item_restocked_success'];
        }
    }
}

// Get inventory items with stock status
$inventory_sql = "SELECT 
    i.*,
    CASE 
        WHEN i.current_stock <= i.minimum_stock THEN 'Low Stock'
        WHEN i.current_stock <= (i.minimum_stock * 1.5) THEN 'Medium Stock'
        ELSE 'Good Stock'
    END as stock_status
FROM inventory i 
WHERE i.status = 'active' 
ORDER BY i.item_name";
$inventory_result = $conn->query($inventory_sql);
$inventory_items = $inventory_result->fetch_all(MYSQLI_ASSOC);

// Get item for editing
$edit_item = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $item_id = intval($_GET['edit']);
    $sql = "SELECT * FROM inventory WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_item = $result->fetch_assoc();
}

// Get low stock items
$low_stock_sql = "SELECT * FROM inventory WHERE current_stock <= minimum_stock AND status = 'active'";
$low_stock_result = $conn->query($low_stock_sql);
$low_stock_items = $low_stock_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>" dir="<?php echo $_SESSION['lang'] == 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['inventory_management']; ?> - Cafe Management System</title>
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
        
        .inventory-item-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .inventory-item-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .stock-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .stock-good {
            background: #d4edda;
            color: #155724;
        }
        
        .stock-medium {
            background: #fff3cd;
            color: #856404;
        }
        
        .stock-low {
            background: #f8d7da;
            color: #721c24;
        }
        
        .alert {
            border-radius: 16px;
            border: none;
            backdrop-filter: blur(15px);
        }
        
        .alert-success {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.15), rgba(40, 167, 69, 0.08));
            color: #28a745;
            border: 2px solid rgba(40, 167, 69, 0.3);
        }
        
        .alert-danger {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.15), rgba(220, 53, 69, 0.08));
            color: #dc3545;
            border: 2px solid rgba(220, 53, 69, 0.3);
        }
        
        .alert-warning {
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.15), rgba(255, 193, 7, 0.08));
            color: #ffc107;
            border: 2px solid rgba(255, 193, 7, 0.3);
        }
        
        .edit-mode {
            background: #f8f9fa;
            border: 2px solid #667eea;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .editable-field {
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .editable-field:hover {
            background: rgba(102, 126, 234, 0.1);
        }
        
        .editable-field::after {
            content: '\f303';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
            opacity: 0;
            transition: opacity 0.3s ease;
            font-size: 12px;
        }
        
        .editable-field:hover::after {
            opacity: 1;
        }
        
        .edit-input {
            border: 2px solid #667eea;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 14px;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .edit-input:focus {
            outline: none;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15);
        }
        
        .edit-actions {
            margin-top: 10px;
            display: flex;
            gap: 10px;
        }
        
        .btn-save, .btn-cancel {
            padding: 6px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-save {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        
        .btn-cancel {
            background: #6c757d;
            color: white;
        }
        
        .btn-cancel:hover {
            background: #5a6268;
        }
        
        .loading-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .stock-update-indicator {
            position: absolute;
            top: -10px;
            right: -10px;
            background: #28a745;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
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
                        <a href="inventory_management.php" class="nav-link active">
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
                            <h2 class="mb-0"><?php echo $lang['inventory_management']; ?></h2>
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
                            <a href="daily_closing_inventory.php" class="btn btn-primary ms-2">
                                <i class="fas fa-chart-line me-2"></i><?php echo $lang['daily_closing']; ?>
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

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Low Stock Alert -->
        <?php if (count($low_stock_items) > 0): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong><?php echo $lang['low_stock_alert']; ?>!</strong> <?php echo str_replace('{count}', count($low_stock_items), $lang['low_stock_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs mb-4" id="inventoryTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="items-tab" data-bs-toggle="tab" data-bs-target="#items" type="button" role="tab">
                    <i class="fas fa-list me-2"></i><?php echo $lang['inventory_items']; ?>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="add-item-tab" data-bs-toggle="tab" data-bs-target="#add-item" type="button" role="tab">
                    <i class="fas fa-plus me-2"></i><?php echo $lang['add_inventory_item']; ?>
                </button>
            </li>
            <?php if ($edit_item): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="edit-item-tab" data-bs-toggle="tab" data-bs-target="#edit-item" type="button" role="tab">
                        <i class="fas fa-edit me-2"></i><?php echo $lang['edit_inventory_item']; ?>
                    </button>
                </li>
            <?php endif; ?>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="inventoryTabContent">
            <!-- Inventory Items Tab -->
            <div class="tab-pane fade show active" id="items" role="tabpanel">
                <div class="row">
                    <?php foreach ($inventory_items as $item): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="inventory-item-card" id="item-<?php echo $item['id']; ?>">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="mb-0">
                                        <span class="item-name"><?php echo htmlspecialchars($item[$_SESSION['lang'] == 'ar' ? 'item_name_ar' : 'item_name'] ?? ''); ?></span>
                                        <span class="item-name-ar" style="display:none;"><?php echo htmlspecialchars($item['item_name_ar'] ?? ''); ?></span>
                                    </h5>
                                    <span class="stock-badge stock-<?php echo strtolower(str_replace(' ', '', $item['stock_status'])); ?>">
                                        <?php echo $item['stock_status']; ?>
                                    </span>
                                </div>
                                
                                <?php if ($item['description']): ?>
                                    <p class="text-muted small mb-3 item-description">
                                        <?php echo htmlspecialchars(substr($item['description'], 0, 100)) . '...'; ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <small class="text-muted"><?php echo $lang['current_stock']; ?></small>
                                        <div class="fw-bold current-stock"><?php echo $item['current_stock']; ?> <?php echo $item['unit']; ?></div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted"><?php echo $lang['minimum_stock']; ?></small>
                                        <div class="fw-bold minimum-stock"><?php echo $item['minimum_stock']; ?> <?php echo $item['unit']; ?></div>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <small class="text-muted"><?php echo $lang['unit_cost']; ?></small>
                                        <div class="fw-bold unit-cost"><?php echo $lang['currency_symbol'] ?? 'EGP'; ?> <?php echo number_format($item['unit_cost'], 2); ?></div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted"><?php echo $lang['last_restocked']; ?></small>
                                        <div class="fw-bold"><?php echo $item['last_restocked'] ? date('M d, Y', strtotime($item['last_restocked'])) : $lang['not_restocked_yet']; ?></div>
                                    </div>
                                </div>
                                
                                <?php if ($item['supplier']): ?>
                                    <small class="text-muted d-block mb-3 item-supplier">
                                        <i class="fas fa-truck me-1"></i>
                                        <?php echo $lang['supplier']; ?>: <?php echo htmlspecialchars($item['supplier']); ?>
                                    </small>
                                <?php endif; ?>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <button class="btn btn-sm btn-outline-primary" onclick="enableEditMode(<?php echo $item['id']; ?>)">
                                            <i class="fas fa-edit"></i> <?php echo $lang['edit']; ?>
                                        </button>
                                    </div>
                                    <div>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="restock_item">
                                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                            <input type="number" name="restock_quantity" class="form-control form-control-sm" placeholder="<?php echo $lang['quantity']; ?>" min="1" style="width: 80px; display: inline-block;">
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <i class="fas fa-plus"></i> <?php echo $lang['restock']; ?>
                                            </button>
                                        </form>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete_inventory_item">
                                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('<?php echo $lang['confirm_delete']; ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Add Item Tab -->
            <div class="tab-pane fade" id="add-item" role="tabpanel">
                <div class="row">
                    <div class="col-md-8">
                        <form method="POST">
                            <input type="hidden" name="action" value="add_inventory_item">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label"><?php echo $lang['item_name_en']; ?></label>
                                        <input type="text" class="form-control" name="item_name" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label"><?php echo $lang['item_name_ar']; ?></label>
                                        <input type="text" class="form-control" name="item_name_ar" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label"><?php echo $lang['description']; ?></label>
                                <textarea class="form-control" name="description" rows="3"></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label"><?php echo $lang['current_stock']; ?></label>
                                        <input type="number" class="form-control" name="current_stock" step="0.01" min="0" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label"><?php echo $lang['unit']; ?></label>
                                        <select class="form-select" name="unit" required>
                                            <option value=""><?php echo $lang['select_unit']; ?></option>
                                            <option value="kg">Kilograms (kg)</option>
                                            <option value="liters">Liters</option>
                                            <option value="pieces">Pieces</option>
                                            <option value="boxes">Boxes</option>
                                            <option value="bottles">Bottles</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label"><?php echo $lang['minimum_stock']; ?></label>
                                        <input type="number" class="form-control" name="minimum_stock" step="0.01" min="0">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label"><?php echo $lang['unit_cost']; ?> ($)</label>
                                        <input type="number" class="form-control" name="unit_cost" step="0.01" min="0">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label"><?php echo $lang['supplier']; ?></label>
                                        <input type="text" class="form-control" name="supplier">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label"><?php echo $lang['last_restocked']; ?></label>
                                <input type="date" class="form-control" name="last_restocked" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i><?php echo $lang['add_inventory_item']; ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Item Tab -->
            <?php if ($edit_item): ?>
                <div class="tab-pane fade" id="edit-item" role="tabpanel">
                    <div class="row">
                        <div class="col-md-8">
                            <form method="POST">
                                <input type="hidden" name="action" value="edit_inventory_item">
                                <input type="hidden" name="item_id" value="<?php echo $edit_item['id']; ?>">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Item Name (English)</label>
                                            <input type="text" class="form-control" name="item_name" value="<?php echo htmlspecialchars($edit_item['item_name'] ?? ''); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Item Name (Arabic)</label>
                                            <input type="text" class="form-control" name="item_name_ar" value="<?php echo htmlspecialchars($edit_item['item_name_ar'] ?? ''); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($edit_item['description'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Current Stock</label>
                                            <input type="number" class="form-control" name="current_stock" step="0.01" min="0" value="<?php echo $edit_item['current_stock']; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Unit</label>
                                            <select class="form-select" name="unit" required>
                                                <option value="">Select Unit</option>
                                                <option value="kg" <?php echo $edit_item['unit'] == 'kg' ? 'selected' : ''; ?>>Kilograms (kg)</option>
                                                <option value="liters" <?php echo $edit_item['unit'] == 'liters' ? 'selected' : ''; ?>>Liters</option>
                                                <option value="pieces" <?php echo $edit_item['unit'] == 'pieces' ? 'selected' : ''; ?>>Pieces</option>
                                                <option value="boxes" <?php echo $edit_item['unit'] == 'boxes' ? 'selected' : ''; ?>>Boxes</option>
                                                <option value="bottles" <?php echo $edit_item['unit'] == 'bottles' ? 'selected' : ''; ?>>Bottles</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Minimum Stock</label>
                                            <input type="number" class="form-control" name="minimum_stock" step="0.01" min="0" value="<?php echo $edit_item['minimum_stock']; ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Unit Cost ($)</label>
                                            <input type="number" class="form-control" name="unit_cost" step="0.01" min="0" value="<?php echo $edit_item['unit_cost']; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Supplier</label>
                                            <input type="text" class="form-control" name="supplier" value="<?php echo htmlspecialchars($edit_item['supplier'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Last Restocked</label>
                                            <input type="date" class="form-control" name="last_restocked" value="<?php echo $edit_item['last_restocked'] ?? date('Y-m-d'); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Status</label>
                                            <select class="form-select" name="status">
                                                <option value="active" <?php echo $edit_item['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                                <option value="inactive" <?php echo $edit_item['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i><?php echo $lang['update_inventory_item']; ?>
                                </button>
                                <a href="inventory_management.php" class="btn btn-secondary ms-2">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Global variables for editing state
        let currentEditingItem = null;
        let originalValues = {};
        
        // Enable edit mode for an inventory item
        function enableEditMode(itemId) {
            // Cancel any existing edit mode
            if (currentEditingItem !== null) {
                cancelEdit();
            }
            
            currentEditingItem = itemId;
            const card = document.getElementById(`item-${itemId}`);
            
            // Store original values
            originalValues = {
                name: card.querySelector('.item-name').textContent.trim(),
                nameAr: card.querySelector('.item-name-ar').textContent.trim(),
                description: card.querySelector('.item-description') ? card.querySelector('.item-description').textContent.trim() : '',
                currentStock: card.querySelector('.current-stock').textContent.trim().split(' ')[0],
                minimumStock: card.querySelector('.minimum-stock').textContent.trim().split(' ')[0],
                unitCost: card.querySelector('.unit-cost').textContent.trim().replace('$', ''),
                supplier: card.querySelector('.item-supplier') ? card.querySelector('.item-supplier').textContent.trim().replace('Supplier: ', '') : ''
            };
            
            // Create edit form
            const editHtml = `
                <div class="edit-mode">
                    <h6 class="mb-3">Edit Inventory Item</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Name (English)</label>
                                <input type="text" class="edit-input" id="edit-name" value="${originalValues.name.replace(/"/g, '&quot;')}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Name (Arabic)</label>
                                <input type="text" class="edit-input" id="edit-name-ar" value="${originalValues.nameAr.replace(/"/g, '&quot;')}">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="edit-input" id="edit-description" rows="2">${originalValues.description.replace(/`/g, '\\`')}</textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Current Stock</label>
                                <input type="number" class="edit-input" id="edit-current-stock" step="0.01" value="${originalValues.currentStock}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Minimum Stock</label>
                                <input type="number" class="edit-input" id="edit-minimum-stock" step="0.01" value="${originalValues.minimumStock}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Unit Cost ($)</label>
                                <input type="number" class="edit-input" id="edit-unit-cost" step="0.01" value="${originalValues.unitCost}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Supplier</label>
                                <input type="text" class="edit-input" id="edit-supplier" value="${originalValues.supplier.replace(/"/g, '&quot;')}">
                            </div>
                        </div>
                    </div>
                    <div class="edit-actions">
                        <button class="btn-save" onclick="saveEdit(${itemId})">
                            <i class="fas fa-save me-1"></i> Save
                        </button>
                        <button class="btn-cancel" onclick="cancelEdit()">
                            <i class="fas fa-times me-1"></i> Cancel
                        </button>
                    </div>
                </div>
            `;
            
            // Insert edit form after the card header
            const cardHeader = card.querySelector('.d-flex.justify-content-between');
            cardHeader.insertAdjacentHTML('afterend', editHtml);
            
            // Hide the original content (all mb-3 divs after the first one)
            const contentDivs = card.querySelectorAll('.mb-3:not(:first-child)');
            contentDivs.forEach(div => {
                div.style.display = 'none';
            });
        }
        
        // Save edited item
        function saveEdit(itemId) {
            const formData = {
                action: 'edit_inventory_item',
                item_id: itemId,
                item_name: document.getElementById('edit-name').value,
                item_name_ar: document.getElementById('edit-name-ar').value,
                description: document.getElementById('edit-description').value,
                current_stock: parseFloat(document.getElementById('edit-current-stock').value),
                minimum_stock: parseFloat(document.getElementById('edit-minimum-stock').value),
                unit_cost: parseFloat(document.getElementById('edit-unit-cost').value) || 0,
                supplier: document.getElementById('edit-supplier').value,
                status: 'active'
            };
            
            // Validate required fields
            if (!formData.item_name || !formData.item_name_ar) {
                showAlert('Please fill in all required fields', 'danger');
                return;
            }
            
            // Show loading state
            const saveBtn = document.querySelector('.btn-save');
            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<span class="loading-spinner"></span> Saving...';
            saveBtn.disabled = true;
            
            // Send AJAX request
            fetch('inventory_management.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(formData)
            })
            .then(response => response.text())
            .then(data => {
                // Parse response to check for success message
                if (data.includes('alert-success')) {
                    showAlert('Item updated successfully!', 'success');
                    updateItemDisplay(itemId, formData);
                    cancelEdit();
                } else {
                    showAlert('Error updating item. Please try again.', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Network error. Please try again.', 'danger');
            })
            .finally(() => {
                // Restore button state
                saveBtn.innerHTML = originalText;
                saveBtn.disabled = false;
            });
        }
        
        // Cancel edit mode
        function cancelEdit() {
            if (currentEditingItem !== null) {
                const card = document.getElementById(`item-${currentEditingItem}`);
                const editMode = card.querySelector('.edit-mode');
                if (editMode) {
                    editMode.remove();
                }
                
                // Show original content
                const contentDivs = card.querySelectorAll('.mb-3:not(:first-child)');
                contentDivs.forEach(div => {
                    div.style.display = 'block';
                });
                
                currentEditingItem = null;
                originalValues = {};
            }
        }
        
        // Update item display after successful save
        function updateItemDisplay(itemId, formData) {
            const card = document.getElementById(`item-${itemId}`);
            
            // Update text content
            card.querySelector('.item-name').textContent = formData.item_name;
            card.querySelector('.item-name-ar').textContent = formData.item_name_ar;
            const descElement = card.querySelector('.item-description');
            if (descElement) {
                descElement.textContent = formData.description;
            }
            card.querySelector('.current-stock').textContent = formData.current_stock;
            card.querySelector('.minimum-stock').textContent = formData.minimum_stock;
            card.querySelector('.unit-cost').textContent = '$' + parseFloat(formData.unit_cost).toFixed(2);
            const supplierElement = card.querySelector('.item-supplier');
            if (supplierElement) {
                supplierElement.textContent = formData.supplier;
            }
            
            // Add update indicator
            const indicator = document.createElement('div');
            indicator.className = 'stock-update-indicator';
            indicator.innerHTML = '<i class="fas fa-check"></i>';
            card.style.position = 'relative';
            card.appendChild(indicator);
            
            // Remove indicator after 3 seconds
            setTimeout(() => {
                if (indicator.parentNode) {
                    indicator.remove();
                }
            }, 3000);
            
            // Update stock status if needed
            updateStockStatus(card, formData.current_stock, formData.minimum_stock);
        }
        
        // Update stock status badge
        function updateStockStatus(card, currentStock, minimumStock) {
            const badge = card.querySelector('.stock-badge');
            let status, statusClass;
            
            if (currentStock <= minimumStock) {
                status = 'Low Stock';
                statusClass = 'stock-low';
            } else if (currentStock <= (minimumStock * 1.5)) {
                status = 'Medium Stock';
                statusClass = 'stock-medium';
            } else {
                status = 'Good Stock';
                statusClass = 'stock-good';
            }
            
            badge.textContent = status;
            badge.className = 'stock-badge ' + statusClass;
        }
        
        // Show alert message
        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            // Insert at the top of the main content area
            const mainContainer = document.querySelector('.main-content');
            mainContainer.insertBefore(alertDiv, mainContainer.firstChild);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }
        
        // Handle keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && currentEditingItem !== null) {
                cancelEdit();
            }
            if (e.key === 'Enter' && e.ctrlKey && currentEditingItem !== null) {
                saveEdit(currentEditingItem);
            }
        });
        
        // Initialize tooltips and other Bootstrap components
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize any Bootstrap tooltips if needed
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>
