<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
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
    header('Location: table_management.php');
    exit;
}

// Ensure language variables are set if not loaded from file
$is_arabic = $_SESSION['lang'] === 'ar';

if (!isset($lang['tables'])) $lang['tables'] = $is_arabic ? 'الطاولات' : 'Tables';
if (!isset($lang['table_number'])) $lang['table_number'] = $is_arabic ? 'رقم الطاولة' : 'Table Number';
if (!isset($lang['capacity'])) $lang['capacity'] = $is_arabic ? 'السعة' : 'Capacity';
if (!isset($lang['table_status'])) $lang['table_status'] = $is_arabic ? 'حالة الطاولة' : 'Table Status';
if (!isset($lang['location'])) $lang['location'] = $is_arabic ? 'الموقع' : 'Location';
if (!isset($lang['select_location'])) $lang['select_location'] = $is_arabic ? 'اختر الموقع' : 'Select Location';
if (!isset($lang['available'])) $lang['available'] = $is_arabic ? 'متاحة' : 'Available';
if (!isset($lang['occupied'])) $lang['occupied'] = $is_arabic ? 'مشغولة' : 'Occupied';
if (!isset($lang['ready_for_order'])) $lang['ready_for_order'] = $is_arabic ? 'جاهزة للطلب' : 'Ready for Order';
if (!isset($lang['have_order'])) $lang['have_order'] = $is_arabic ? 'لديها طلب' : 'Have Order';
if (!isset($lang['reserved'])) $lang['reserved'] = $is_arabic ? 'محجوزة' : 'Reserved';
if (!isset($lang['cleaning'])) $lang['cleaning'] = $is_arabic ? 'قيد التنظيف' : 'Cleaning';
if (!isset($lang['persons'])) $lang['persons'] = $is_arabic ? 'أشخاص' : 'persons';
if (!isset($lang['no_current_order'])) $lang['no_current_order'] = $is_arabic ? 'لا يوجد طلب حالي' : 'No Current Order';
if (!isset($lang['current_order'])) $lang['current_order'] = $is_arabic ? 'الطلب الحالي' : 'Current Order';
if (!isset($lang['add_table'])) $lang['add_table'] = $is_arabic ? 'إضافة طاولة' : 'Add Table';
if (!isset($lang['update_table'])) $lang['update_table'] = $is_arabic ? 'تحديث الطاولة' : 'Update Table';
if (!isset($lang['edit_table'])) $lang['edit_table'] = $is_arabic ? 'تعديل الطاولة' : 'Edit Table';
if (!isset($lang['delete_table'])) $lang['delete_table'] = $is_arabic ? 'حذف الطاولة' : 'Delete Table';
if (!isset($lang['cancel_edit'])) $lang['cancel_edit'] = $is_arabic ? 'إلغاء التعديل' : 'Cancel Edit';
if (!isset($lang['table_added_success'])) $lang['table_added_success'] = $is_arabic ? 'تمت إضافة الطاولة بنجاح!' : 'Table added successfully!';
if (!isset($lang['table_updated_success'])) $lang['table_updated_success'] = $is_arabic ? 'تم تحديث الطاولة بنجاح!' : 'Table updated successfully!';
if (!isset($lang['table_deleted_success'])) $lang['table_deleted_success'] = $is_arabic ? 'تم حذف الطاولة بنجاح!' : 'Table deleted successfully!';
if (!isset($lang['currency_symbol'])) $lang['currency_symbol'] = 'EGP';
if (!isset($lang['language'])) $lang['language'] = $is_arabic ? 'اللغة' : 'Language';
if (!isset($lang['logout'])) $lang['logout'] = $is_arabic ? 'تسجيل الخروج' : 'Logout';

// Additional translations for table management
if (!isset($lang['table_management'])) $lang['table_management'] = $is_arabic ? 'إدارة الطاولات' : 'Table Management';
if (!isset($lang['manage_tables'])) $lang['manage_tables'] = $is_arabic ? 'إدارة طاولات المطعم وتتبع حالتها' : 'Manage restaurant tables and track their status';
if (!isset($lang['no_tables'])) $lang['no_tables'] = $is_arabic ? 'لا توجد طاولات' : 'No tables found';
if (!isset($lang['no_tables_message'])) $lang['no_tables_message'] = $is_arabic ? 'لم يتم العثور على طاولات. أضف طاولاتك الأولى.' : 'No tables found. Add your first table.';
if (!isset($lang['indoor'])) $lang['indoor'] = $is_arabic ? 'داخلي' : 'Indoor';
if (!isset($lang['outdoor'])) $lang['outdoor'] = $is_arabic ? 'خارجي' : 'Outdoor';
if (!isset($lang['terrace'])) $lang['terrace'] = $is_arabic ? 'شرفة' : 'Terrace';
if (!isset($lang['vip_room'])) $lang['vip_room'] = $is_arabic ? 'غرفة VIP' : 'VIP Room';
if (!isset($lang['order'])) $lang['order'] = $is_arabic ? 'طلب' : 'Order';
if (!isset($lang['view'])) $lang['view'] = $is_arabic ? 'عرض' : 'View';
if (!isset($lang['paid'])) $lang['paid'] = $is_arabic ? 'مدفوع' : 'Paid';
if (!isset($lang['finish_cleaning'])) $lang['finish_cleaning'] = $is_arabic ? 'إنهاء التنظيف' : 'Finish Cleaning';
if (!isset($lang['mark_reserved'])) $lang['mark_reserved'] = $is_arabic ? 'تحديد كمحجوز' : 'Mark as Reserved';
if (!isset($lang['profile'])) $lang['profile'] = $is_arabic ? 'الملف الشخصي' : 'Profile';
if (!isset($lang['settings'])) $lang['settings'] = $is_arabic ? 'الإعدادات' : 'Settings';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'add_table') {
        $table_number = 'T' . ($_POST['table_number'] ?? '');
        $capacity = intval($_POST['capacity'] ?? 0);
        $location = trim($_POST['location'] ?? '');
        $status = $_POST['status'] ?? 'Ready for Order';
        
        if ($capacity > 0 && !empty($location)) {
            $sql = "INSERT INTO tables (table_number, capacity, location, status) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("siss", $table_number, $capacity, $location, $status);
            $stmt->execute();
            $success_message = "Table added successfully!";
        } else {
            $error_message = "Please fill all required fields!";
        }
    } elseif ($action == 'edit_table') {
        $table_id = intval($_POST['table_id'] ?? 0);
        $table_number = 'T' . ($_POST['table_number'] ?? '');
        $capacity = intval($_POST['capacity'] ?? 0);
        $location = trim($_POST['location'] ?? '');
        $status = $_POST['status'] ?? 'Ready for Order';
        
        if ($table_id > 0 && $capacity > 0 && !empty($location)) {
            $sql = "UPDATE tables SET table_number = ?, capacity = ?, location = ?, status = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sissi", $table_number, $capacity, $location, $status, $table_id);
            $stmt->execute();
            $success_message = "Table updated successfully!";
        } else {
            $error_message = "Please fill all required fields!";
        }
    } elseif ($action == 'delete_table') {
        $table_id = intval($_POST['table_id'] ?? 0);
        if ($table_id > 0) {
            $sql = "DELETE FROM tables WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $table_id);
            $stmt->execute();
            $success_message = "Table deleted successfully!";
        }
    } elseif ($action == 'checkout_table') {
        $table_id = intval($_POST['table_id'] ?? 0);
        $order_id = intval($_POST['order_id'] ?? 0);
        $payment_method = $_POST['payment_method'] ?? 'cash';
        
        if ($table_id > 0 && $order_id > 0) {
            // Update order status to completed and payment status to paid
            $sql = "UPDATE orders SET status = 'completed', payment_status = 'paid', payment_method = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $payment_method, $order_id);
            $stmt->execute();

            // Update table status to Cleaning and clear current order
            $update_sql = "UPDATE tables SET status = 'Cleaning', current_order_id = NULL WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $table_id);
            $update_stmt->execute();

            $success_message = "Checkout successful. Table is now being cleaned.";
        }
    } elseif ($action == 'finish_cleaning') {
        $table_id = intval($_POST['table_id'] ?? 0);
        if ($table_id > 0) {
            $sql = "UPDATE tables SET status = 'Ready for Order' WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $table_id);
            $stmt->execute();
            $success_message = "Cleaning finished. Table is now Ready for Order.";
        }
    } elseif ($action == 'mark_reserved') {
        $table_id = intval($_POST['table_id'] ?? 0);
        if ($table_id > 0) {
            $sql = "UPDATE tables SET status = 'Reserved' WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $table_id);
            $stmt->execute();
            $success_message = "Table marked as reserved.";
        }
    }
}

// Normalize table statuses - convert old values to new workflow
$normalize_sql = "UPDATE tables SET status = CASE 
    WHEN status = 'available' THEN 'Ready for Order'
    WHEN status = 'occupied' THEN 'Have Order'
    WHEN status = 'served' THEN 'Cleaning'
    ELSE status
END WHERE status IN ('available', 'occupied', 'served')";
$conn->query($normalize_sql);

// Get tables with current order information
$tables_sql = "SELECT t.*, 
                (SELECT o.order_number FROM orders o WHERE o.table_id = t.id AND o.status IN ('under progress', 'preparing') ORDER BY o.created_at DESC LIMIT 1) as current_order_number,
                (SELECT o.total_amount FROM orders o WHERE o.table_id = t.id AND o.status IN ('under progress', 'preparing') ORDER BY o.created_at DESC LIMIT 1) as current_order_total,
                (SELECT o.status FROM orders o WHERE o.table_id = t.id AND o.status IN ('under progress', 'preparing') ORDER BY o.created_at DESC LIMIT 1) as current_order_status,
                (SELECT o.id FROM orders o WHERE o.table_id = t.id AND o.status IN ('under progress', 'preparing') ORDER BY o.created_at DESC LIMIT 1) as current_order_id
                FROM tables t 
                ORDER BY t.table_number";
$tables_result = $conn->query($tables_sql);
$tables = $tables_result->fetch_all(MYSQLI_ASSOC);

$page_title = $lang['table_management'];
// Set text direction based on language
$text_direction = ($_SESSION['lang'] === 'ar') ? 'rtl' : 'ltr';
$html_lang = ($_SESSION['lang'] === 'ar') ? 'ar' : 'en';
?>

<!DOCTYPE html>
<html lang="<?php echo $html_lang; ?>" dir="<?php echo $text_direction; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['table_management']; ?> - Restaurant Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/color-palette.css" rel="stylesheet">
    <style>
        body {
            background-color: var(--bg-secondary);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .page-header {
            background: var(--bg-white);
            color: var(--dark-color);
            padding: var(--spacing-lg);
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px var(--shadow-secondary);
            border-radius: var(--radius-large);
            position: relative;
            overflow: hidden;
        }
        
        .page-header h1 {
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
        }
        
        .page-header p {
            opacity: 0.75;
            font-size: 1rem;
            margin-bottom: 0;
            color: var(--muted-color);
        }
        
        .table-card {
            background: var(--bg-white);
            border-radius: var(--radius-large);
            padding: var(--spacing-lg);
            box-shadow: 0 5px 15px var(--shadow-secondary);
            transition: var(--transition-normal);
            border-left: 4px solid;
            position: relative;
            overflow: hidden;
        }
        
        .table-card:hover {
            transform: translateY(-5px);
        }
        
        .table-card.ready-for-order {
            border-left-color: var(--success-color);
        }
        
        .table-card.have-order {
            border-left-color: var(--info-color);
        }
        
        .table-card.cleaning {
            border-left-color: var(--warning-color);
        }
        
        .table-card.reserved {
            border-left-color: var(--primary-teal);
        }
        
        .status-badge {
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .status-badge::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }
        
        .status-ready-for-order {
            background: linear-gradient(135deg, #d4edda, #55efc4);
            color: #155724;
            border: 1px solid #55efc4;
        }
        
        .status-have-order {
            background: linear-gradient(135deg, #d1ecf1, #81ecec);
            color: #0c5460;
            border: 1px solid #81ecec;
        }
        
        .status-cleaning {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .status-reserved {
            background: linear-gradient(135deg, #e2e9f3, #a29bfe);
            color: #383d41;
            border: 1px solid #a29bfe;
        }
        
        .workflow-buttons .btn {
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            padding: 10px 16px;
            border: none;
        }
        
        .workflow-buttons .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn-success {
            background: var(--success-color);
            color: white;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-primary);
        }
        
        .btn-info {
            background: var(--info-color);
            color: white;
        }
        
        .btn-info:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-primary);
        }
        
        .btn-primary {
            background: var(--gradient-primary);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-primary);
        }
        
        .btn-secondary {
            background: var(--primary-medium);
            color: white;
        }
        
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-primary);
        }
        
        .btn-outline-primary {
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }
        
        .btn-outline-danger {
            background: transparent;
            border: 2px solid var(--danger-color);
            color: var(--danger-color);
        }
        
        .btn-outline-danger:hover {
            background: var(--danger-color);
            color: white;
            transform: translateY(-2px);
        }
        
        .current-order-info {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            border: 1px solid #e9ecef;
            position: relative;
        }
        
        .current-order-info::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }
        
        .table-number {
            font-weight: 800;
            color: var(--dark-color);
            font-size: 1.4rem;
        }
        
        .enhanced-card {
            border: none;
            box-shadow: 0 5px 15px var(--shadow-secondary);
            border-radius: var(--radius-large);
            overflow: hidden;
            background: var(--bg-white);
        }
        
        .enhanced-card .card-header {
            background: var(--gradient-sidebar);
            color: white;
            border: none;
            padding: var(--spacing-md);
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .form-control, .form-select {
            border-radius: var(--radius-medium);
            border: 2px solid var(--border-light);
            padding: 12px 16px;
            transition: var(--transition-normal);
            background: var(--bg-white);
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--border-primary);
            box-shadow: var(--focus-shadow);
        }
        
        .input-group-text {
            background: var(--gradient-primary);
            color: white;
            border: none;
            border-radius: var(--radius-medium) 0 0 var(--radius-medium);
            font-weight: 600;
        }
        
        .input-group .form-control {
            border-radius: 0 var(--radius-medium) var(--radius-medium) 0;
        }
        
        .alert {
            border-radius: var(--radius-large);
            border: none;
            font-weight: 500;
        }
        
        .alert-success {
            background: var(--success-color);
            color: white;
        }
        
        .alert-danger {
            background: var(--danger-color);
            color: white;
        }
        
        .no-tables-message {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--muted-color);
        }
        
        .no-tables-message i {
            font-size: 4rem;
            opacity: 0.3;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }
        
        .no-current-order {
            text-align: center;
            padding: 2rem;
            color: var(--muted-color);
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            border: 1px dashed #dee2e6;
        }
        
        .no-current-order i {
            font-size: 2rem;
            opacity: 0.5;
            margin-bottom: 0.5rem;
        }
        
        /* Sidebar Styles */
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
            text-decoration: none;
            font-weight: 500;
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
        
        /* Loading animation */
        .loading-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid var(--border-light);
            border-top: 2px solid var(--primary-teal);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* RTL Styles for Arabic */
        [dir="rtl"] {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        [dir="rtl"] .sidebar {
            right: 0;
            left: auto;
            box-shadow: -2px 0 10px var(--shadow-primary);
        }
        
        [dir="rtl"] .main-content {
            margin-right: auto;
            margin-left: 0;
        }
        
        [dir="rtl"] .text-end {
            text-align: left !important;
        }
        
        [dir="rtl"] .text-start {
            text-align: right !important;
        }
        
        [dir="rtl"] .me-2,
        [dir="rtl"] .me-3 {
            margin-left: 0.5rem;
            margin-right: 0;
        }
        
        [dir="rtl"] .ms-2 {
            margin-right: 0.5rem;
            margin-left: 0;
        }
        
        [dir="rtl"] .dropdown-menu {
            right: 0;
            left: auto;
        }
        
        [dir="rtl"] .table-card {
            border-left: none;
            border-right: 4px solid;
        }
        
        [dir="rtl"] .table-card.ready-for-order {
            border-right-color: var(--success-color);
        }
        
        [dir="rtl"] .table-card.have-order {
            border-right-color: var(--info-color);
        }
        
        [dir="rtl"] .table-card.cleaning {
            border-right-color: var(--warning-color);
        }
        
        [dir="rtl"] .table-card.reserved {
            border-right-color: var(--primary-teal);
        }
        
        [dir="rtl"] .input-group-text {
            border-radius: 0 var(--radius-medium) var(--radius-medium) 0;
        }
        
        [dir="rtl"] .input-group .form-control {
            border-radius: var(--radius-medium) 0 0 var(--radius-medium);
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
                        <a href="table_management.php" class="nav-link active">
                            <i class="fas fa-table me-2"></i>
                            <?php echo $lang['table_management']; ?>
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
                <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h1 class="h2 mb-0">
                        <i class="fas fa-table me-3"></i><?php echo $lang['table_management']; ?>
                    </h1>
                    <p class="mb-0 mt-2 opacity-75"><?php echo $lang['manage_tables']; ?></p>
                </div>
                <div class="col-auto">
                    <div class="dropdown d-inline-block me-3">
                        <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-globe"></i> <?php echo $lang['language']; ?>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="?lang=en">English</a></li>
                            <li><a class="dropdown-item" href="?lang=ar">العربية</a></li>    
                        </ul>
                    </div>
                    <div class="dropdown d-inline-block me-3">
                        <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <div class="user-avatar d-inline-block me-2">
                                <?php echo strtoupper(substr($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'U', 0, 1)); ?>
                            </div>
                            <?php echo $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'; ?>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i> <?php echo $lang['profile']; ?></a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i> <?php echo $lang['settings']; ?></a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i> <?php echo $lang['logout']; ?></a></li>
                        </ul>
                    </div>
                                    </div>
            </div>
        </div>

        <!-- Messages -->
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

        <div class="row">
            <!-- Table Form -->
            <div class="col-lg-4">
                <div class="card enhanced-card mb-4" id="table-form">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0" id="form-title">
                            <i class="fas fa-plus me-2"></i><?php echo $lang['add_table']; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="table-form-element">
                            <input type="hidden" name="action" id="form-action" value="add_table">
                            <input type="hidden" name="table_id" id="form-table-id" value="">
                            
                            <div class="mb-3">
                                <label for="form-table-number" class="form-label"><?php echo $lang['table_number']; ?></label>
                                <div class="input-group">
                                    <span class="input-group-text">T</span>
                                    <input type="number" class="form-control" id="form-table-number" name="table_number" required min="1">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="form-capacity" class="form-label"><?php echo $lang['capacity']; ?></label>
                                <input type="number" class="form-control" id="form-capacity" name="capacity" required min="1">
                            </div>
                            
                            <div class="mb-3">
                                <label for="form-location" class="form-label"><?php echo $lang['location']; ?></label>
                                <select class="form-select" id="form-location" name="location" required>
                                    <option value=""><?php echo $lang['select_location']; ?></option>
                                    <option value="Indoor">Indoor</option>
                                    <option value="Outdoor">Outdoor</option>
                                    <option value="Terrace">Terrace</option>
                                    <option value="VIP Room">VIP Room</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="form-status" class="form-label"><?php echo $lang['table_status']; ?></label>
                                <select class="form-select" id="form-status" name="status">
                                    <option value="Ready for Order">Ready for Order</option>
                                    <option value="Reserved">Reserved</option>
                                    <option value="Cleaning">Cleaning</option>
                                </select>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-fill" id="submit-btn">
                                    <i class="fas fa-plus me-2"></i>Add Table
                                </button>
                                <button type="button" class="btn btn-secondary d-none" id="cancel-edit-btn" onclick="cancelTableEdit()">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Tables Grid -->
            <div class="col-lg-8">
                <div class="row">
                    <?php if (empty($tables)): ?>
                        <div class="col-12 text-center py-5">
                            <div class="mb-3">
                                <i class="fas fa-table fa-4x text-muted opacity-25"></i>
                            </div>
                            <h4 class="text-muted">No tables found</h4>
                            <p class="text-muted">Add your first table using the form on the left.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($tables as $table):
                            $status_class = strtolower(str_replace(' ', '-', $table['status']));
                            
                            // Map database status to display text
                            $status_display = $table['status'];
                            switch($table['status']) {
                                case 'Ready for Order':
                                    $status_display = $lang['ready_for_order'];
                                    break;
                                case 'Have Order':
                                    $status_display = $lang['have_order'];
                                    break;
                                case 'Reserved':
                                    $status_display = $lang['reserved'];
                                    break;
                                case 'Cleaning':
                                    $status_display = $lang['cleaning'];
                                    break;
                                case 'Available':
                                    $status_display = $lang['available'];
                                    break;
                                case 'Occupied':
                                    $status_display = $lang['occupied'];
                                    break;
                                default:
                                    $status_display = htmlspecialchars($table['status']);
                            }
                            ?>
                            <div class="col-md-6 col-sm-6 mb-4">
                                <div class="table-card h-100 <?php echo $status_class; ?>" id="table-card-<?php echo $table['id']; ?>">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h5 class="mb-0 fw-bold">
                                                <span class="table-number fs-4"><?php echo htmlspecialchars($table['table_number']); ?></span>
                                            </h5>
                                            <small class="text-muted">
                                                <i class="fas fa-users me-1"></i><?php echo $table['capacity']; ?>
                                                <?php echo $lang['persons']; ?>
                                            </small>
                                        </div>
                                        <span class="status-badge status-<?php echo $status_class; ?> table-status">
                                            <?php echo $status_display; ?>
                                        </span>
                                    </div>

                                    <div class="mb-3">
                                        <small class="text-muted d-block text-uppercase small fw-bold mb-1">Location</small>
                                        <div class="fw-semibold">
                                            <i class="fas fa-map-marker-alt me-1 text-primary"></i>
                                            <?php echo htmlspecialchars($table['location']); ?>
                                        </div>
                                    </div>

                                    <?php if ($table['current_order_id']): ?>
                                        <div class="current-order-info">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <small class="text-muted"><?php echo $lang['current_order']; ?></small>
                                                <span class="badge bg-primary"><?php echo htmlspecialchars($table['current_order_number']); ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <strong>Total: <?php echo $lang['currency_symbol']; ?><?php echo number_format($table['current_order_total'], 2); ?></strong>
                                                <small class="text-muted"><?php echo date('H:i', strtotime($table['created_at'] ?? 'now')); ?></small>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center text-muted py-3">
                                            <i class="fas fa-inbox fa-2x mb-2 opacity-50"></i>
                                            <div><?php echo $lang['no_current_order']; ?></div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="workflow-buttons d-flex flex-wrap gap-2 mt-auto pt-3 border-top">
                                        <?php if ($table['status'] === 'Ready for Order' || $table['status'] === 'Reserved'): ?>
                                            <a href="order_management.php?action=create_order&table_id=<?php echo $table['id']; ?>" class="btn btn-sm btn-success flex-grow-1 py-2 rounded-2">
                                                <i class="fas fa-utensils me-2"></i>Order
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($table['status'] === 'Have Order'): ?>
                                            <a href="order_management.php?edit_order=<?php echo $table['current_order_id']; ?>" class="btn btn-sm btn-info flex-grow-1 py-2 rounded-2 text-white">
                                                <i class="fas fa-eye me-2"></i>View
                                            </a>
                                            <form method="POST" class="flex-grow-1">
                                                <input type="hidden" name="action" value="checkout_table">
                                                <input type="hidden" name="table_id" value="<?php echo $table['id']; ?>">
                                                <input type="hidden" name="order_id" value="<?php echo $table['current_order_id']; ?>">
                                                <input type="hidden" name="payment_method" value="cash">
                                                <button type="submit" class="btn btn-sm btn-primary w-100 py-2 rounded-2">
                                                    <i class="fas fa-cash-register me-2"></i>Paid
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($table['status'] === 'Cleaning'): ?>
                                            <form method="POST" class="w-100">
                                                <input type="hidden" name="action" value="finish_cleaning">
                                                <input type="hidden" name="table_id" value="<?php echo $table['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-secondary w-100 py-2 rounded-2">
                                                    <i class="fas fa-broom me-2"></i>Finish Cleaning
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <div class="btn-group w-100" role="group">
                                            <button class="btn btn-sm btn-outline-primary" onclick="prepareEditForm(<?php echo $table['id']; ?>, '<?php echo htmlspecialchars($table['table_number']); ?>', <?php echo $table['capacity']; ?>, '<?php echo htmlspecialchars($table['location']); ?>', '<?php echo htmlspecialchars($table['status']); ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete_table">
                                                <input type="hidden" name="table_id" value="<?php echo $table['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this table?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form and Edit management
        function prepareEditForm(id, number, capacity, location, status) {
            // Scroll to form
            document.getElementById('table-form').scrollIntoView({ behavior: 'smooth' });

            // Fill form
            document.getElementById('form-title').textContent = 'Edit Table';
            document.getElementById('form-action').value = 'edit_table';
            document.getElementById('form-table-id').value = id;

            // Handle T prefix in table number
            let cleanNumber = number;
            if (number.startsWith('T')) {
                cleanNumber = number.substring(1);
            }

            document.getElementById('form-table-number').value = cleanNumber;
            document.getElementById('form-capacity').value = capacity;
            document.getElementById('form-location').value = location;
            document.getElementById('form-status').value = status;

            // Update buttons
            document.getElementById('submit-btn').innerHTML = '<i class="fas fa-save me-2"></i>Update Table';
            document.getElementById('cancel-edit-btn').classList.remove('d-none');

            // Highlight form
            const formCard = document.getElementById('table-form').closest('.card');
            formCard.classList.add('border-primary', 'shadow-lg');
            formCard.style.transition = 'all 0.3s ease';
        }

        function cancelTableEdit() {
            // Reset form
            document.getElementById('form-title').textContent = 'Add Table';
            document.getElementById('form-action').value = 'add_table';
            document.getElementById('form-table-id').value = '';
            document.getElementById('table-form-element').reset();

            // Update buttons
            document.getElementById('submit-btn').innerHTML = '<i class="fas fa-plus me-2"></i>Add Table';
            document.getElementById('cancel-edit-btn').classList.add('d-none');

            // Remove highlight
            const formCard = document.getElementById('table-form').closest('.card');
            formCard.classList.remove('border-primary', 'shadow-lg');
        }

        function confirmDelete(tableNumber) {
            return confirm(`Are you sure you want to delete table ${tableNumber}? This action cannot be undone.`);
        }
    </script>
            </div>
        </div>
    </div>
</body>
</html>
