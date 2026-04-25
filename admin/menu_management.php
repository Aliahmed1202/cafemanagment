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
    header('Location: menu_management.php');
    exit;
}

// Ensure language variables are set if not loaded from file
$is_arabic = $_SESSION['lang'] === 'ar';

if (!isset($lang['menu_management'])) $lang['menu_management'] = $is_arabic ? 'إدارة القائمة' : 'Menu Management';
if (!isset($lang['menu_management_nav'])) $lang['menu_management_nav'] = $is_arabic ? 'إدارة القائمة' : 'Menu Management';
if (!isset($lang['manage_menu'])) $lang['manage_menu'] = $is_arabic ? 'إدارة قائمة المطعم والفئات والعناصر' : 'Manage restaurant menu, categories and items';
if (!isset($lang['add_category'])) $lang['add_category'] = $is_arabic ? 'إضافة فئة' : 'Add Category';
if (!isset($lang['add_menu_item'])) $lang['add_menu_item'] = $is_arabic ? 'إضافة عنصر للقائمة' : 'Add Menu Item';
if (!isset($lang['category_name'])) $lang['category_name'] = $is_arabic ? 'اسم الفئة' : 'Category Name';
if (!isset($lang['category_description'])) $lang['category_description'] = $is_arabic ? 'وصف الفئة' : 'Category Description';
if (!isset($lang['item_name'])) $lang['item_name'] = $is_arabic ? 'اسم العنصر' : 'Item Name';
if (!isset($lang['item_price'])) $lang['item_price'] = $is_arabic ? 'سعر العنصر' : 'Item Price';
if (!isset($lang['cost_price'])) $lang['cost_price'] = $is_arabic ? 'سعر التكلفة' : 'Cost Price';
if (!isset($lang['category'])) $lang['category'] = $is_arabic ? 'الفئة' : 'Category';
if (!isset($lang['status'])) $lang['status'] = $is_arabic ? 'الحالة' : 'Status';
if (!isset($lang['active'])) $lang['active'] = $is_arabic ? 'نشط' : 'Active';
if (!isset($lang['inactive'])) $lang['inactive'] = $is_arabic ? 'غير نشط' : 'Inactive';
if (!isset($lang['actions'])) $lang['actions'] = $is_arabic ? 'الإجراءات' : 'Actions';
if (!isset($lang['edit'])) $lang['edit'] = $is_arabic ? 'تعديل' : 'Edit';
if (!isset($lang['delete'])) $lang['delete'] = $is_arabic ? 'حذف' : 'Delete';
if (!isset($lang['language'])) $lang['language'] = $is_arabic ? 'اللغة' : 'Language';
if (!isset($lang['logout'])) $lang['logout'] = $is_arabic ? 'تسجيل الخروج' : 'Logout';
if (!isset($lang['profile'])) $lang['profile'] = $is_arabic ? 'الملف الشخصي' : 'Profile';
if (!isset($lang['settings'])) $lang['settings'] = $is_arabic ? 'الإعدادات' : 'Settings';

// Handle all POST requests
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Debug log
    error_log("POST request received: " . print_r($_POST, true));
    $action = $_POST['action'] ?? '';
    
    // Handle AJAX edit requests
    if ($action == 'edit_menu_item' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        
        $item_id = intval($_POST['item_id'] ?? 0);
        $name = trim($_POST['item_name'] ?? '');
        $name_ar = trim($_POST['item_name_ar'] ?? '');
        $price = floatval($_POST['item_price'] ?? 0);
        $status = $_POST['status'] ?? 'active';
        
        // Validation
        if ($item_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
            exit;
        }
        
        if (empty($name) || empty($name_ar) || $price <= 0) {
            echo json_encode(['success' => false, 'message' => 'Name (both languages) and price are required']);
            exit;
        }
        
        // Simple update
        $sql = "UPDATE menu_items SET name = ?, name_ar = ?, price = ?, status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("ssdsi", $name, $name_ar, $price, $status, $item_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Menu item updated successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Prepare error: ' . $conn->error]);
        }
        exit;
    }
    
    // Handle regular form submissions
    if ($action == 'add_category') {
        $name = trim($_POST['category_name'] ?? '');
        $name_ar = trim($_POST['category_name_ar'] ?? '');
        $description = trim($_POST['category_description'] ?? '');
        $description_ar = trim($_POST['category_description_ar'] ?? '');
        
        if (!empty($name) && !empty($name_ar)) {
            $sql = "INSERT INTO categories (name, name_ar, description, description_ar) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $name, $name_ar, $description, $description_ar);
            $stmt->execute();
            $success_message = $lang['category_added_success'];
        } else {
            $error_message = $lang['required_fields'];
        }
    }
    
    elseif ($action == 'delete_category') {
        $category_id = intval($_POST['category_id'] ?? 0);
        if ($category_id > 0) {
            // Check if category has menu items
            $check_sql = "SELECT COUNT(*) as count FROM menu_items WHERE category_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("i", $category_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            $count = $result->fetch_assoc()['count'];
            
            if ($count > 0) {
                $error_message = $lang['category_has_items'];
            } else {
                $sql = "DELETE FROM categories WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $category_id);
                $stmt->execute();
                $success_message = $lang['category_deleted_success'];
            }
        }
    }
    
    elseif ($action == 'add_menu_item') {
        $category_id = $_POST['category_id'] ?? null;
        $name = trim($_POST['item_name'] ?? '');
        $name_ar = trim($_POST['item_name_ar'] ?? '');
        $description = trim($_POST['item_description'] ?? '');
        $description_ar = trim($_POST['item_description_ar'] ?? '');
        $price = floatval($_POST['item_price'] ?? 0);
        $cost_price = floatval($_POST['item_cost_price'] ?? 0);
        $preparation_time = intval($_POST['preparation_time'] ?? 0);
        $ingredients = trim($_POST['ingredients'] ?? '');
        $allergens = trim($_POST['allergens'] ?? '');
        
        if (!empty($name) && !empty($name_ar) && $price > 0) {
            $sql = "INSERT INTO menu_items (category_id, name, name_ar, description, description_ar, price, cost_price, preparation_time, ingredients, allergens) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issssddiss", $category_id, $name, $name_ar, $description, $description_ar, $price, $cost_price, $preparation_time, $ingredients, $allergens);
            $stmt->execute();
            $success_message = $lang['item_added_success'];
        } else {
            $error_message = $lang['required_fields'];
        }
    }
    
    elseif ($action == 'edit_menu_item') {
        $item_id = intval($_POST['item_id'] ?? 0);
        $name = trim($_POST['item_name'] ?? '');
        $name_ar = trim($_POST['item_name_ar'] ?? '');
        $price = floatval($_POST['item_price'] ?? 0);
        
        // Simple validation
        if ($item_id > 0 && !empty($name) && !empty($name_ar) && $price > 0) {
            // Simple update query first
            $sql = "UPDATE menu_items SET name = ?, name_ar = ?, price = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            
            if ($stmt) {
                $stmt->bind_param("ssdi", $name, $name_ar, $price, $item_id);
                
                if ($stmt->execute()) {
                    $success_message = 'Menu item updated successfully!';
                    // Clear edit item after successful update
                    header('Location: menu_management.php?success=1');
                    exit;
                } else {
                    $error_message = 'Database error: ' . $stmt->error;
                }
                $stmt->close();
            } else {
                $error_message = 'Database prepare error: ' . $conn->error;
            }
        } else {
            $error_message = 'Required fields are missing or invalid';
        }
    }
    
    elseif ($action == 'delete_menu_item') {
        $item_id = intval($_POST['item_id'] ?? 0);
        if ($item_id > 0) {
            $sql = "DELETE FROM menu_items WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $item_id);
            $stmt->execute();
            $success_message = $lang['item_deleted_success'];
        }
    }
}

// Get categories
$categories_result = $conn->query("SELECT * FROM categories ORDER BY name");
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);

// Get menu items with category names
$menu_items_sql = "SELECT mi.*, c.name as category_name, c.name_ar as category_name_ar FROM menu_items mi LEFT JOIN categories c ON mi.category_id = c.id ORDER BY mi.name";
$menu_items_result = $conn->query($menu_items_sql);
$menu_items = $menu_items_result->fetch_all(MYSQLI_ASSOC);

// Handle success message from URL
if (isset($_GET['success'])) {
    $success_message = 'Menu item updated successfully!';
}

// Get item for editing
$edit_item = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $item_id = intval($_GET['edit']);
    $sql = "SELECT * FROM menu_items WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_item = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>" dir="<?php echo $_SESSION['lang'] == 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['menu_management']; ?> - Cafe Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/color-palette.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
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
        
        .table {
            border-radius: var(--radius-large);
            overflow: hidden;
            box-shadow: 0 4px 15px var(--shadow-secondary);
        }
        
        .table thead {
            background: var(--gradient-primary);
            color: var(--text-white);
        }
        
        .menu-item-card {
            background: var(--bg-white);
            border-radius: var(--radius-large);
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-lg);
            box-shadow: 0 4px 15px var(--shadow-secondary);
            transition: var(--transition-normal);
        }
        
        .menu-item-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .price-tag {
            background: linear-gradient(135deg, var(--success-color), var(--primary-dark-teal));
            color: var(--text-white);
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 700;
            display: inline-block;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-active {
            background: var(--primary-light);
            color: var(--primary-dark-teal);
        }
        
        .status-inactive {
            background: var(--primary-medium);
            color: var(--text-white);
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
        
        /* Enhanced Button Styles */
        .btn-group {
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
            border-radius: var(--radius-medium);
            overflow: hidden;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 11px;
            font-weight: 600;
            border-radius: 0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .btn-sm::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-sm:hover::before {
            left: 100%;
        }
        
        .btn-outline-primary {
            background: linear-gradient(135deg, var(--bg-white), var(--border-light));
            border: 2px solid var(--info-color);
            color: var(--info-color);
            text-shadow: 0 1px 2px rgba(var(--info-color), 0.1);
        }
        
        .btn-outline-primary:hover {
            background: linear-gradient(135deg, var(--info-color), var(--primary-dark-teal));
            border-color: var(--primary-dark-teal);
            color: var(--text-white);
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(var(--info-color), 0.3);
        }
        
        .btn-outline-info {
            background: linear-gradient(135deg, var(--bg-white), var(--border-light));
            border: 2px solid var(--primary-teal);
            color: var(--primary-teal);
            text-shadow: 0 1px 2px rgba(var(--primary-teal-rgb), 0.1);
        }
        
        .btn-outline-info:hover {
            background: linear-gradient(135deg, var(--primary-teal), var(--primary-dark-teal));
            border-color: var(--primary-dark-teal);
            color: var(--text-white);
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(var(--primary-teal-rgb), 0.3);
        }
        
        .btn-outline-danger {
            background: linear-gradient(135deg, var(--bg-white), var(--border-light));
            border: 2px solid var(--danger-color);
            color: var(--danger-color);
            text-shadow: 0 1px 2px rgba(var(--danger-color), 0.1);
        }
        
        .btn-outline-danger:hover {
            background: linear-gradient(135deg, var(--danger-color), var(--primary-dark));
            border-color: var(--primary-dark);
            color: var(--text-white);
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(var(--danger-color), 0.3);
        }
        
        /* Button Icons Enhancement */
        .btn-sm i {
            margin-right: 4px;
            transition: transform 0.3s ease;
        }
        
        .btn-sm:hover i {
            transform: scale(1.1);
        }
        
        /* Active states for buttons */
        .btn-outline-primary:active,
        .btn-outline-info:active,
        .btn-outline-danger:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }
        
        /* Special button group styling */
        .btn-group .btn-sm:first-child {
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
        }
        
        .btn-group .btn-sm:last-child {
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
        }
        
        .btn-group .btn-sm:not(:first-child) {
            border-left: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        /* Enhanced focus states */
        .btn-sm:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.25);
        }
        
        .btn-outline-primary:focus {
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
        }
        
        .btn-outline-info:focus {
            box-shadow: 0 0 0 3px rgba(23, 162, 184, 0.25);
        }
        
        .btn-outline-danger:focus {
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.25);
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
                        <a href="menu_management.php" class="nav-link active">
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
                            <h2 class="mb-0"><?php echo $lang['menu_management']; ?></h2>
                            <p class="text-muted mb-0">
                                <?php echo $lang['welcome']; ?>, <?php echo $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'; ?>!
                            </p>
                        </div>
                        <div class="col-md-6 text-end">
                            <a href="../auth/logout.php" class="btn btn-primary">
                                <i class="fas fa-sign-out-alt me-2"></i> <?php echo $lang['logout']; ?>
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

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs mb-4" id="menuTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="items-tab" data-bs-toggle="tab" data-bs-target="#items" type="button" role="tab">
                    <i class="fas fa-list me-2"></i><?php echo $lang['menu_items']; ?>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="add-item-tab" data-bs-toggle="tab" data-bs-target="#add-item" type="button" role="tab">
                    <i class="fas fa-plus me-2"></i><?php echo $lang['add_menu_item']; ?>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="categories-tab" data-bs-toggle="tab" data-bs-target="#categories" type="button" role="tab">
                    <i class="fas fa-tags me-2"></i><?php echo $lang['categories']; ?>
                </button>
            </li>
            <?php if ($edit_item): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="edit-item-tab" data-bs-toggle="tab" data-bs-target="#edit-item" type="button" role="tab">
                        <i class="fas fa-edit me-2"></i><?php echo $lang['edit_menu_item']; ?>
                    </button>
                </li>
            <?php endif; ?>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="menuTabContent">
            <!-- Menu Items Tab -->
            <div class="tab-pane fade show active" id="items" role="tabpanel">
                <div class="row">
                    <?php foreach ($menu_items as $item): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="menu-item-card">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="mb-0"><?php echo htmlspecialchars($item[$_SESSION['lang'] == 'ar' ? 'name_ar' : 'name'] ?? $item['name'] ?? ''); ?></h5>
                                    <span class="status-badge status-<?php echo $item['status']; ?>">
                                        <?php echo ucfirst($item['status']); ?>
                                    </span>
                                </div>
                                
                                <?php if ($item[$_SESSION['lang'] == 'ar' ? 'category_name_ar' : 'category_name']): ?>
                                    <small class="text-muted d-block mb-2">
                                        <i class="fas fa-tag me-1"></i>
                                        <?php echo htmlspecialchars($item[$_SESSION['lang'] == 'ar' ? 'category_name_ar' : 'category_name'] ?? ''); ?>
                                    </small>
                                <?php endif; ?>
                                
                                <?php if ($item[$_SESSION['lang'] == 'ar' ? 'description_ar' : 'description'] ?? $item['description']): ?>
                                    <p class="text-muted small mb-3">
                                        <?php echo htmlspecialchars(substr($item[$_SESSION['lang'] == 'ar' ? 'description_ar' : 'description'] ?? $item['description'] ?? '', 0, 100)) . '...'; ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="price-tag">
                                        <?php echo $lang['currency_symbol'] ?? 'EGP'; ?> <?php echo number_format($item['price'], 2); ?>
                                    </span>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="openQuickEdit(<?php echo $item['id']; ?>)">
                                            <i class="fas fa-edit"></i> <?php echo $lang['quick_edit']; ?>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-info" onclick="openAdvancedEdit(<?php echo $item['id']; ?>)">
                                            <i class="fas fa-cog"></i> <?php echo $lang['advanced_edit']; ?>
                                        </button>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete_menu_item">
                                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('<?php echo $lang['confirm_delete']; ?>')">
                                                <i class="fas fa-trash"></i><?php echo $lang['delete']; ?>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                
                                <?php if ($item['preparation_time'] > 0): ?>
                                    <small class="text-muted d-block mt-2">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php echo $item['preparation_time']; ?> mins
                                    </small>
                                <?php endif; ?>
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
                            <input type="hidden" name="action" value="add_menu_item">
                            
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
                                <label class="form-label"><?php echo $lang['category']; ?></label>
                                <select class="form-select" name="category_id">
                                    <option value=""><?php echo $lang['select_category']; ?></option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>">
                                            <?php echo htmlspecialchars($category['name'] ?? ''); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label"><?php echo $lang['item_price']; ?></label>
                                        <input type="number" class="form-control" name="item_price" step="0.01" min="0" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label"><?php echo $lang['item_cost_price']; ?></label>
                                        <input type="number" class="form-control" name="item_cost_price" step="0.01" min="0">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label"><?php echo $lang['preparation_time']; ?></label>
                                        <input type="number" class="form-control" name="preparation_time" min="0">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label"><?php echo $lang['status']; ?></label>
                                        <select class="form-select" name="status">
                                            <option value="<?php echo $lang['active']; ?>"><?php echo $lang['active']; ?></option>
                                            <option value="<?php echo $lang['inactive']; ?>"><?php echo $lang['inactive']; ?></option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label"><?php echo $lang['item_description_en']; ?></label>
                                <textarea class="form-control" name="item_description" rows="3"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label"><?php echo $lang['item_description_ar']; ?></label>
                                <textarea class="form-control" name="item_description_ar" rows="3"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label"><?php echo $lang['ingredients']; ?></label>
                                <textarea class="form-control" name="ingredients" rows="2" placeholder="e.g., flour, eggs, milk"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label"><?php echo $lang['allergens']; ?></label>
                                <textarea class="form-control" name="allergens" rows="2" placeholder="e.g., nuts, gluten, dairy"></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i><?php echo $lang['add_menu_item']; ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Categories Tab -->
            <div class="tab-pane fade" id="categories" role="tabpanel">
                <div class="row">
                    <div class="col-md-8">
                        <form method="POST" class="mb-4">
                            <input type="hidden" name="action" value="add_category">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label"><?php echo $lang['category_name_en']; ?></label>
                                        <input type="text" class="form-control" name="category_name" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label"><?php echo $lang['category_name_ar']; ?></label>
                                        <input type="text" class="form-control" name="category_name_ar" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label"><?php echo $lang['category_description_en']; ?></label>
                                <textarea class="form-control" name="category_description" rows="3"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label"><?php echo $lang['category_description_ar']; ?></label>
                                <textarea class="form-control" name="category_description_ar" rows="3"></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i><?php echo $lang['add_category']; ?>
                            </button>
                        </form>
                        
                        <!-- Categories List -->
                        <h5 class="mb-3"><?php echo $lang['categories']; ?></h5>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th><?php echo $lang['category_name_en']; ?></th>
                                        <th><?php echo $lang['category_name_ar']; ?></th>
                                        <th><?php echo $lang['status']; ?></th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($category['name'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($category['name_ar'] ?? ''); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $category['status']; ?>">
                                                    <?php echo ucfirst($category['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($category['created_at'])); ?></td>
                                            <td>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="delete_category">
                                                    <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('<?php echo $lang['confirm_delete_category']; ?>')">
                                                        <i class="fas fa-trash"></i> <?php echo $lang['delete']; ?>
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

            <!-- Edit Item Tab -->
            <?php if ($edit_item): ?>
                <div class="tab-pane fade" id="edit-item" role="tabpanel">
                    <div class="row">
                        <div class="col-md-8">
                            <form method="POST">
                                <input type="hidden" name="action" value="edit_menu_item">
                                <input type="hidden" name="item_id" value="<?php echo $edit_item['id']; ?>">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label"><?php echo $lang['item_name_en']; ?></label>
                                            <input type="text" class="form-control" name="item_name" value="<?php echo htmlspecialchars($edit_item['name'] ?? ''); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label"><?php echo $lang['item_name_ar']; ?></label>
                                            <input type="text" class="form-control" name="item_name_ar" value="<?php echo htmlspecialchars($edit_item['name_ar'] ?? ''); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><?php echo $lang['category']; ?></label>
                                    <select class="form-select" name="category_id">
                                        <option value=""><?php echo $lang['select_category']; ?></option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>" <?php echo $edit_item['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name'] ?? ''); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label"><?php echo $lang['item_price']; ?></label>
                                            <input type="number" class="form-control" name="item_price" step="0.01" min="0" value="<?php echo $edit_item['price']; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label"><?php echo $lang['item_cost_price']; ?></label>
                                            <input type="number" class="form-control" name="item_cost_price" step="0.01" min="0" value="<?php echo $edit_item['cost_price']; ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label"><?php echo $lang['preparation_time']; ?></label>
                                            <input type="number" class="form-control" name="preparation_time" min="0" value="<?php echo $edit_item['preparation_time']; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label"><?php echo $lang['status']; ?></label>
                                            <select class="form-select" name="status">
                                                <option value="active" <?php echo $edit_item['status'] == 'active' ? 'selected' : ''; ?>><?php echo $lang['active']; ?></option>
                                                <option value="inactive" <?php echo $edit_item['status'] == 'inactive' ? 'selected' : ''; ?>><?php echo $lang['inactive']; ?></option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><?php echo $lang['item_description_en']; ?></label>
                                    <textarea class="form-control" name="item_description" rows="3"><?php echo htmlspecialchars($edit_item['description'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><?php echo $lang['item_description_ar']; ?></label>
                                    <textarea class="form-control" name="item_description_ar" rows="3"><?php echo htmlspecialchars($edit_item['description_ar'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><?php echo $lang['ingredients']; ?></label>
                                    <textarea class="form-control" name="ingredients" rows="2"><?php echo htmlspecialchars($edit_item['ingredients'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><?php echo $lang['allergens']; ?></label>
                                    <textarea class="form-control" name="allergens" rows="2"><?php echo htmlspecialchars($edit_item['allergens'] ?? ''); ?></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i><?php echo $lang['item_updated_success']; ?>
                                </button>
                                <a href="menu_management.php" class="btn btn-secondary ms-2">
                                    <i class="fas fa-times me-2"></i><?php echo $lang['cancel']; ?>
                                </a>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Edit Modal -->
    <div class="modal fade" id="quickEditModal" tabindex="-1" aria-labelledby="quickEditModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="quickEditModalLabel">
                        <i class="fas fa-edit me-2"></i><?php echo $lang['quick_edit']; ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="quickEditForm">
                        <input type="hidden" id="quickEditItemId" name="item_id">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label"><?php echo $lang['item_name_en']; ?></label>
                                    <input type="text" class="form-control" id="quickEditName" name="item_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label"><?php echo $lang['item_name_ar']; ?></label>
                                    <input type="text" class="form-control" id="quickEditNameAr" name="item_name_ar" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label"><?php echo $lang['item_price']; ?></label>
                                    <input type="number" class="form-control" id="quickEditPrice" name="item_price" step="0.01" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label"><?php echo $lang['category']; ?></label>
                                    <select class="form-select" id="quickEditCategory" name="category_id">
                                        <option value=""><?php echo $lang['select_category']; ?></option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name'] ?? ''); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label"><?php echo $lang['status']; ?></label>
                                    <select class="form-select" id="quickEditStatus" name="status">
                                        <option value="active"><?php echo $lang['active']; ?></option>
                                        <option value="inactive"><?php echo $lang['inactive']; ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo $lang['item_description_en']; ?></label>
                            <textarea class="form-control" id="quickEditDescription" name="item_description" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo $lang['cancel']; ?></button>
                    <button type="button" class="btn btn-primary" onclick="saveQuickEdit()">
                        <i class="fas fa-save me-2"></i><?php echo $lang['save']; ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Advanced Edit Modal -->
    <div class="modal fade" id="advancedEditModal" tabindex="-1" aria-labelledby="advancedEditModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="advancedEditModalLabel">
                        <i class="fas fa-cog me-2"></i><?php echo $lang['advanced_edit']; ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <form id="advancedEditForm">
                                <input type="hidden" id="advancedEditItemId" name="item_id">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label"><?php echo $lang['item_name_en']; ?></label>
                                            <input type="text" class="form-control" id="advancedEditName" name="item_name" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label"><?php echo $lang['item_name_ar']; ?></label>
                                            <input type="text" class="form-control" id="advancedEditNameAr" name="item_name_ar" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label"><?php echo $lang['category']; ?></label>
                                    <select class="form-select" id="advancedEditCategory" name="category_id">
                                        <option value=""><?php echo $lang['select_category']; ?></option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name'] ?? ''); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label"><?php echo $lang['item_price']; ?></label>
                                            <input type="number" class="form-control" id="advancedEditPrice" name="item_price" step="0.01" min="0" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label"><?php echo $lang['item_cost_price']; ?></label>
                                            <input type="number" class="form-control" id="advancedEditCostPrice" name="item_cost_price" step="0.01" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label"><?php echo $lang['preparation_time']; ?></label>
                                            <input type="number" class="form-control" id="advancedEditPrepTime" name="preparation_time" min="0">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label"><?php echo $lang['item_description_en']; ?></label>
                                            <textarea class="form-control" id="advancedEditDescription" name="item_description" rows="3"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label"><?php echo $lang['item_description_ar']; ?></label>
                                            <textarea class="form-control" id="advancedEditDescriptionAr" name="item_description_ar" rows="3"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label"><?php echo $lang['ingredients']; ?></label>
                                            <textarea class="form-control" id="advancedEditIngredients" name="ingredients" rows="2"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label"><?php echo $lang['allergens']; ?></label>
                                            <textarea class="form-control" id="advancedEditAllergens" name="allergens" rows="2"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label"><?php echo $lang['status']; ?></label>
                                    <select class="form-select" id="advancedEditStatus" name="status">
                                        <option value="active"><?php echo $lang['active']; ?></option>
                                        <option value="inactive"><?php echo $lang['inactive']; ?></option>
                                    </select>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-4">
                            <div class="sticky-top" style="top: 20px;">
                                <!-- Image Upload Section -->
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <i class="fas fa-image me-2"></i><?php echo $lang['upload_image']; ?>
                                    </div>
                                    <div class="card-body">
                                        <div id="imagePreview" class="text-center mb-3">
                                            <img src="" alt="Item Image" class="img-fluid rounded" style="max-height: 200px; display: none;">
                                            <div class="text-muted" id="noImageText">
                                                <i class="fas fa-image fa-3x mb-2"></i>
                                                <p>No image uploaded</p>
                                            </div>
                                        </div>
                                        <input type="file" id="imageUpload" class="form-control" accept="image/*">
                                        <button type="button" class="btn btn-sm btn-outline-danger mt-2" onclick="removeImage()" style="display: none;" id="removeImageBtn">
                                            <i class="fas fa-trash me-1"></i><?php echo $lang['remove_image']; ?>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Live Preview Section -->
                                <div class="card">
                                    <div class="card-header">
                                        <i class="fas fa-eye me-2"></i><?php echo $lang['preview_changes']; ?>
                                    </div>
                                    <div class="card-body">
                                        <div id="livePreview">
                                            <h6 id="previewName">Item Name</h6>
                                            <p class="text-muted small" id="previewDescription">Description</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo $lang['cancel']; ?></button>
                        <button type="button" class="btn btn-primary" onclick="saveAdvancedEdit()">
                            <i class="fas fa-save me-2"></i><?php echo $lang['save']; ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Menu items data for JavaScript
        const menuItems = <?php echo json_encode($menu_items); ?>;
        
        function openQuickEdit(itemId) {
            const item = menuItems.find(i => i.id == itemId);
            if (!item) return;
            
            document.getElementById('quickEditItemId').value = item.id;
            document.getElementById('quickEditName').value = item.name || '';
            document.getElementById('quickEditNameAr').value = item.name_ar || '';
            document.getElementById('quickEditPrice').value = item.price || '';
            document.getElementById('quickEditCategory').value = item.category_id || '';
            document.getElementById('quickEditStatus').value = item.status || 'active';
            document.getElementById('quickEditDescription').value = item.description || '';
            
            const modal = new bootstrap.Modal(document.getElementById('quickEditModal'));
            modal.show();
        }
        
        function openAdvancedEdit(itemId) {
            const item = menuItems.find(i => i.id == itemId);
            if (!item) return;
            
            document.getElementById('advancedEditItemId').value = item.id;
            document.getElementById('advancedEditName').value = item.name || '';
            document.getElementById('advancedEditNameAr').value = item.name_ar || '';
            document.getElementById('advancedEditPrice').value = item.price || '';
            document.getElementById('advancedEditCostPrice').value = item.cost_price || '';
            document.getElementById('advancedEditPrepTime').value = item.preparation_time || '';
            document.getElementById('advancedEditCategory').value = item.category_id || '';
            document.getElementById('advancedEditStatus').value = item.status || 'active';
            document.getElementById('advancedEditDescription').value = item.description || '';
            document.getElementById('advancedEditDescriptionAr').value = item.description_ar || '';
            document.getElementById('advancedEditIngredients').value = item.ingredients || '';
            document.getElementById('advancedEditAllergens').value = item.allergens || '';
            
            updateLivePreview();
            const modal = new bootstrap.Modal(document.getElementById('advancedEditModal'));
            modal.show();
        }
        
        function saveQuickEdit() {
            console.log('saveQuickEdit called');
            const form = document.getElementById('quickEditForm');
            const formData = new FormData(form);
            formData.append('action', 'edit_menu_item');
            
            console.log('Form data:', Object.fromEntries(formData));
            
            fetch('menu_management.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('quickEditModal')).hide();
                    showSuccessMessage(data.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showErrorMessage(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorMessage('An error occurred while saving');
            });
        }
        
        function saveAdvancedEdit() {
            const form = document.getElementById('advancedEditForm');
            const formData = new FormData(form);
            formData.append('action', 'edit_menu_item');
            
            fetch('menu_management.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('advancedEditModal')).hide();
                    showSuccessMessage(data.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showErrorMessage(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorMessage('An error occurred while saving');
            });
        }
        
        function showSuccessMessage(message) {
            // Create or update success alert
            let alertDiv = document.getElementById('successAlert');
            if (!alertDiv) {
                alertDiv = document.createElement('div');
                alertDiv.id = 'successAlert';
                alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed';
                alertDiv.style.top = '20px';
                alertDiv.style.right = '20px';
                alertDiv.style.zIndex = '9999';
                document.body.appendChild(alertDiv);
            }
            alertDiv.innerHTML = `
                <i class="fas fa-check-circle me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            // Auto-hide after 3 seconds
            setTimeout(() => {
                if (alertDiv) {
                    alertDiv.remove();
                }
            }, 3000);
        }
        
        function showErrorMessage(message) {
            // Create or update error alert
            let alertDiv = document.getElementById('errorAlert');
            if (!alertDiv) {
                alertDiv = document.createElement('div');
                alertDiv.id = 'errorAlert';
                alertDiv.className = 'alert alert-danger alert-dismissible fade show position-fixed';
                alertDiv.style.top = '20px';
                alertDiv.style.right = '20px';
                alertDiv.style.zIndex = '9999';
                document.body.appendChild(alertDiv);
            }
            alertDiv.innerHTML = `
                <i class="fas fa-exclamation-triangle me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                if (alertDiv) {
                    alertDiv.remove();
                }
            }, 5000);
        }
        
        function saveDraft() {
            // Implement draft saving functionality
            console.log('Draft saved');
        }
        
        function updateLivePreview() {
            const name = document.getElementById('advancedEditName').value || 'Item Name';
            const description = document.getElementById('advancedEditDescription').value || 'Description';
            
            // Update preview elements if they exist
            const previewName = document.getElementById('previewName');
            const previewDescription = document.getElementById('previewDescription');
            
            if (previewName) {
                previewName.textContent = name;
            }
            if (previewDescription) {
                previewDescription.textContent = description;
            }
            
            // Try to update price and status if elements exist
            const price = document.getElementById('advancedEditPrice').value || '0';
            const previewPrice = document.getElementById('previewPrice');
            if (previewPrice) {
                previewPrice.textContent = '$' + parseFloat(price).toFixed(2);
            }
            
            const status = document.getElementById('advancedEditStatus').value || 'active';
            const statusBadge = document.getElementById('previewStatus');
            if (statusBadge) {
                statusBadge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
                statusBadge.className = 'badge bg-' + (status === 'active' ? 'success' : 'secondary');
            }
        }
        
        function confirmDiscardChanges() {
            if (confirm('<?php echo $lang['confirm_discard_changes']; ?>')) {
                return true;
            }
            return false;
        }
        
        function removeImage() {
            const imagePreview = document.querySelector('#imagePreview img');
            const noImageText = document.getElementById('noImageText');
            const removeImageBtn = document.getElementById('removeImageBtn');
            const imageUpload = document.getElementById('imageUpload');
            
            if (imagePreview) {
                imagePreview.style.display = 'none';
                imagePreview.src = '';
            }
            if (noImageText) {
                noImageText.style.display = 'block';
            }
            if (removeImageBtn) {
                removeImageBtn.style.display = 'none';
            }
            if (imageUpload) {
                imageUpload.value = '';
            }
        }
        
        // Add event listeners for live preview
        document.addEventListener('DOMContentLoaded', function() {
            const advancedEditForm = document.getElementById('advancedEditForm');
            if (advancedEditForm) {
                const inputs = advancedEditForm.querySelectorAll('input, textarea, select');
                inputs.forEach(input => {
                    input.addEventListener('input', updateLivePreview);
                    input.addEventListener('change', updateLivePreview);
                });
            }
        });
    </script>
</body>
</html>
