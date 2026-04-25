<?php
// Order Management - Updated with correct paths
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
    header('Location: order_management.php');
    exit;
}

// Set text direction based on language
$text_direction = ($_SESSION['lang'] === 'ar') ? 'rtl' : 'ltr';
$html_lang = ($_SESSION['lang'] === 'ar') ? 'ar' : 'en';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'create_order') {
        $table_id = intval($_POST['table_id'] ?? 0);
        $customer_id = intval($_POST['customer_id'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        
        if ($table_id > 0) {
            // Generate order number
            $order_number = 'ORD' . date('Ymd') . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
            
            $sql = "INSERT INTO orders (order_number, table_id, customer_id, status, payment_status, notes, created_at) 
                    VALUES (?, ?, ?, 'under progress', 'unpaid', ?, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("siis", $order_number, $table_id, $customer_id, $notes);
            $stmt->execute();
            
            $order_id = $stmt->insert_id;
            
            // Update table status
            $update_sql = "UPDATE tables SET status = 'Have Order', current_order_id = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ii", $order_id, $table_id);
            $update_stmt->execute();
            
            header("Location: order_management.php?edit_order=$order_id");
            exit;
        }
    } elseif ($action == 'update_order_status') {
        $order_id = intval($_POST['order_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        
        if ($order_id > 0 && !empty($status)) {
            // Check if trying to set status to 'completed'
            if ($status === 'completed') {
                // Get current payment status
                $payment_check_sql = "SELECT payment_status FROM orders WHERE id = ?";
                $payment_check_stmt = $conn->prepare($payment_check_sql);
                $payment_check_stmt->bind_param("i", $order_id);
                $payment_check_stmt->execute();
                $payment_result = $payment_check_stmt->get_result()->fetch_assoc();
                
                if ($payment_result['payment_status'] !== 'paid') {
                    echo json_encode(['success' => false, 'error' => 'Order must be paid before it can be marked as completed']);
                    exit;
                }
            }
            
            $sql = "UPDATE orders SET status = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $status, $order_id);
            $stmt->execute();
            
            echo json_encode(['success' => true]);
            exit;
        }
    } elseif ($action == 'update_payment_status') {
        $order_id = intval($_POST['order_id'] ?? 0);
        $payment_status = $_POST['payment_status'] ?? '';
        
        if ($order_id > 0 && !empty($payment_status)) {
            // Get current order status before updating payment
            $status_check_sql = "SELECT status FROM orders WHERE id = ?";
            $status_check_stmt = $conn->prepare($status_check_sql);
            $status_check_stmt->bind_param("i", $order_id);
            $status_check_stmt->execute();
            $status_result = $status_check_stmt->get_result()->fetch_assoc();
            $current_status = $status_result['status'];
            
            // Update payment status
            $sql = "UPDATE orders SET payment_status = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $payment_status, $order_id);
            $stmt->execute();
            
            // If payment is set to 'paid' and order is not already completed or cancelled, mark as completed
            if ($payment_status === 'paid' && !in_array($current_status, ['completed', 'cancelled'])) {
                $complete_sql = "UPDATE orders SET status = 'completed' WHERE id = ?";
                $complete_stmt = $conn->prepare($complete_sql);
                $complete_stmt->bind_param("i", $order_id);
                $complete_stmt->execute();
            }
            
            echo json_encode(['success' => true]);
            exit;
        }
    } elseif ($action == 'save_order_notes') {
        $order_id = intval($_POST['order_id'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        
        if ($order_id > 0) {
            $sql = "UPDATE orders SET notes = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $notes, $order_id);
            $stmt->execute();
            
            echo json_encode(['success' => true]);
            exit;
        }
    } elseif ($action == 'add_order_item') {
        // Debug: Log received data
        error_log("Add Order Item - POST Data: " . print_r($_POST, true));
        error_log("Add Order Item - Raw POST: " . file_get_contents('php://input'));
        
        $order_id = intval($_POST['order_id'] ?? 0);
        $menu_item_id = intval($_POST['menu_item_id'] ?? 0);
        $quantity = intval($_POST['quantity'] ?? 1);
        $instructions = trim($_POST['instructions'] ?? '');
        
        error_log("Add Order Item - Parsed: order_id=$order_id, menu_item_id=$menu_item_id, quantity=$quantity");
        
        if ($order_id > 0 && $menu_item_id > 0 && $quantity > 0) {
            // Get menu item details
            $item_sql = "SELECT name, price FROM menu_items WHERE id = ?";
            $item_stmt = $conn->prepare($item_sql);
            $item_stmt->bind_param("i", $menu_item_id);
            $item_stmt->execute();
            $item = $item_stmt->get_result()->fetch_assoc();
            
            error_log("Add Order Item - Menu Item: " . print_r($item, true));
            
            if ($item) {
                $total_price = $item['price'] * $quantity;
                
                $sql = "INSERT INTO order_items (order_id, menu_item_id, quantity, unit_price, total_price, special_instructions) 
                        VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iiidds", $order_id, $menu_item_id, $quantity, $item['price'], $total_price, $instructions);
                $stmt->execute();
                
                error_log("Add Order Item - Item Inserted Successfully");
                
                // Update order total
                $update_sql = "UPDATE orders SET 
                    subtotal = (SELECT COALESCE(SUM(total_price), 0) FROM order_items WHERE order_id = ?),
                    total_amount = (SELECT COALESCE(SUM(total_price), 0) FROM order_items WHERE order_id = ?)
                    WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("iii", $order_id, $order_id, $order_id);
                $update_stmt->execute();
                
                error_log("Add Order Item - Order Total Updated");
                
                echo json_encode(['success' => true]);
                exit;
            } else {
                error_log("Add Order Item - Menu Item Not Found");
                echo json_encode(['success' => false, 'error' => 'Menu item not found']);
                exit;
            }
        } else {
            error_log("Add Order Item - Invalid Parameters: order_id=$order_id, menu_item_id=$menu_item_id, quantity=$quantity");
            echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
            exit;
        }
    } elseif ($action == 'update_item_quantity') {
        $item_id = intval($_POST['item_id'] ?? 0);
        $quantity = intval($_POST['quantity'] ?? 0);
        
        if ($item_id > 0 && $quantity > 0) {
            // Get item details
            $item_sql = "SELECT order_id, unit_price FROM order_items WHERE id = ?";
            $item_stmt = $conn->prepare($item_sql);
            $item_stmt->bind_param("i", $item_id);
            $item_stmt->execute();
            $item = $item_stmt->get_result()->fetch_assoc();
            
            if ($item) {
                $total_price = $item['unit_price'] * $quantity;
                $order_id = $item['order_id'];
                
                // Start transaction or just run updates (MyISAM doesn't support transactions)
                $sql = "UPDATE order_items SET quantity = ?, total_price = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("idi", $quantity, $total_price, $item_id);
                
                if ($stmt->execute()) {
                    // Update order total
                    $update_sql = "UPDATE orders SET 
                        subtotal = (SELECT COALESCE(SUM(total_price), 0) FROM order_items WHERE order_id = ?),
                        total_amount = (SELECT COALESCE(SUM(total_price), 0) FROM order_items WHERE order_id = ?)
                        WHERE id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("iii", $order_id, $order_id, $order_id);
                    $update_stmt->execute();
                    
                    echo json_encode(['success' => true]);
                    exit;
                } else {
                    echo json_encode(['success' => false, 'error' => 'Database update failed']);
                    exit;
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Item not found']);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
            exit;
        }
    } elseif ($action == 'remove_order_item') {
        $item_id = intval($_POST['item_id'] ?? 0);
        
        if ($item_id > 0) {
            // Get order_id before deleting
            $item_sql = "SELECT order_id FROM order_items WHERE id = ?";
            $item_stmt = $conn->prepare($item_sql);
            $item_stmt->bind_param("i", $item_id);
            $item_stmt->execute();
            $item = $item_stmt->get_result()->fetch_assoc();
            
            if ($item) {
                $order_id = $item['order_id'];
                
                $sql = "DELETE FROM order_items WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $item_id);
                if ($stmt->execute()) {
                    // Update order total
                    $update_sql = "UPDATE orders SET 
                        subtotal = (SELECT COALESCE(SUM(total_price), 0) FROM order_items WHERE order_id = ?),
                        total_amount = (SELECT COALESCE(SUM(total_price), 0) FROM order_items WHERE order_id = ?)
                        WHERE id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("iii", $order_id, $order_id, $order_id);
                    $update_stmt->execute();
                    
                    echo json_encode(['success' => true]);
                    exit;
                } else {
                    echo json_encode(['success' => false, 'error' => 'Database deletion failed']);
                    exit;
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Item not found']);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid item ID']);
            exit;
        }
    } elseif ($action == 'cancel_order') {
        $order_id = intval($_POST['order_id'] ?? 0);
        
        if ($order_id > 0) {
            // Get table_id before cancelling
            $order_sql = "SELECT table_id FROM orders WHERE id = ?";
            $order_stmt = $conn->prepare($order_sql);
            $order_stmt->bind_param("i", $order_id);
            $order_stmt->execute();
            $order = $order_stmt->get_result()->fetch_assoc();
            
            if ($order) {
                $table_id = $order['table_id'];
                
                // Update order status
                $sql = "UPDATE orders SET status = 'cancelled' WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $order_id);
                $stmt->execute();
                
                // Update table status
                $update_sql = "UPDATE tables SET status = 'Ready for Order', current_order_id = NULL WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("i", $table_id);
                $update_stmt->execute();
                
                echo json_encode(['success' => true]);
                exit;
            }
        }
    }
}

// Handle GET requests
$edit_order_id = intval($_GET['edit_order'] ?? 0);
$action = $_GET['action'] ?? '';
$create_order_table_id = intval($_GET['table_id'] ?? 0);
$edit_order = null;
$edit_order_items = [];

// Debug: Log GET parameters
error_log("GET parameters: " . print_r($_GET, true));
error_log("action: " . $action);
error_log("table_id: " . $create_order_table_id);

// Handle create_order GET request
if ($action === 'create_order' && $create_order_table_id > 0) {
    error_log("Processing create_order for table_id: " . $create_order_table_id);
    // Check if table exists and is ready
    $table_sql = "SELECT * FROM tables WHERE id = ?";
    $table_stmt = $conn->prepare($table_sql);
    $table_stmt->bind_param("i", $create_order_table_id);
    $table_stmt->execute();
    $table = $table_stmt->get_result()->fetch_assoc();
    
    if ($table) {
        // Generate order number
        $order_number = 'ORD' . date('Ymd') . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        // Create the order
        $sql = "INSERT INTO orders (order_number, table_id, customer_id, status, payment_status, notes, created_at) 
                VALUES (?, ?, 0, 'under progress', 'unpaid', '', NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $order_number, $create_order_table_id);
        $stmt->execute();
        
        $order_id = $stmt->insert_id;
        
        // Update table status
        $update_sql = "UPDATE tables SET status = 'Have Order' WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $create_order_table_id);
        $update_stmt->execute();
        
        // Redirect to edit the newly created order
        header("Location: order_management.php?edit_order=$order_id");
        exit;
    }
}

if ($edit_order_id > 0) {
    // Get order details
    $order_sql = "SELECT o.*, c.name as customer_name, t.table_number 
                  FROM orders o 
                  LEFT JOIN customers c ON o.customer_id = c.id 
                  LEFT JOIN tables t ON o.table_id = t.id 
                  WHERE o.id = ?";
    $order_stmt = $conn->prepare($order_sql);
    $order_stmt->bind_param("i", $edit_order_id);
    $order_stmt->execute();
    $edit_order = $order_stmt->get_result()->fetch_assoc();
    
    if ($edit_order) {
        // Get order items
        $items_sql = "SELECT oi.*, mi.name, mi.price, oi.special_instructions as instructions
                      FROM order_items oi 
                      LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id
                      WHERE oi.order_id = ? 
                      ORDER BY oi.id";
        $items_stmt = $conn->prepare($items_sql);
        $items_stmt->bind_param("i", $edit_order_id);
        $items_stmt->execute();
        $edit_order_items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

// Get orders with details
$orders_sql = "SELECT o.*, c.name as customer_name, t.table_number,
               (SELECT SUM(oi.total_price) FROM order_items oi WHERE oi.order_id = o.id) as calculated_total
               FROM orders o 
               LEFT JOIN customers c ON o.customer_id = c.id 
               LEFT JOIN tables t ON o.table_id = t.id 
               ORDER BY o.created_at DESC";
$orders_result = $conn->query($orders_sql);
$orders = $orders_result->fetch_all(MYSQLI_ASSOC);

// Get menu items for adding to order
$menu_items_sql = "SELECT mi.*, c.name as category_name 
                   FROM menu_items mi 
                   LEFT JOIN categories c ON mi.category_id = c.id 
                   ORDER BY c.name, mi.name";
$menu_items_result = $conn->query($menu_items_sql);
$menu_items = $menu_items_result->fetch_all(MYSQLI_ASSOC);

// Calculate statistics
$total_orders = count($orders);
$total_revenue = array_sum(array_column($orders, 'total_amount'));
$active_orders = count(array_filter($orders, function($order) {
    return in_array($order['status'], ['under progress', 'preparing', 'ready']);
}));
$completed_today = count(array_filter($orders, function($order) {
    return $order['status'] === 'completed' && 
           date('Y-m-d', strtotime($order['created_at'])) === date('Y-m-d');
}));

$page_title = $lang['order_management'];
// Set text direction based on language
$text_direction = ($_SESSION['lang'] === 'ar') ? 'rtl' : 'ltr';
$html_lang = ($_SESSION['lang'] === 'ar') ? 'ar' : 'en';
?>

<!DOCTYPE html>
<html lang="<?php echo $html_lang; ?>" dir="<?php echo $text_direction; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['order_management']; ?> - Restaurant Management System</title>
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
        
        .summary-card {
            background: var(--bg-white);
            border-radius: var(--radius-large);
            padding: var(--spacing-lg);
            box-shadow: 0 5px 15px var(--shadow-secondary);
            transition: var(--transition-normal);
            border-left: 4px solid;
            position: relative;
            overflow: hidden;
        }
        
        .summary-card:hover {
            transform: translateY(-5px);
        }
        
        .summary-card.orders { border-left-color: var(--info-color); }
        .summary-card.revenue { border-left-color: var(--success-color); }
        .summary-card.active { border-left-color: var(--warning-color); }
        .summary-card.completed { border-left-color: var(--primary-teal); }
        
        .summary-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        
        .summary-label {
            color: var(--muted-color);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
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
        
        .orders-table {
            background: var(--bg-white);
            border-radius: var(--radius-large);
            overflow: hidden;
            box-shadow: 0 5px 15px var(--shadow-secondary);
        }
        
        .orders-table thead {
            background: var(--gradient-sidebar);
            color: white;
        }
        
        .orders-table thead th {
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.85rem;
            padding: 1rem;
            border: none;
        }
        
        .orders-table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f3f5;
        }
        
        .orders-table tbody tr:hover {
            background-color: #f8f9fa;
            transition: background-color 0.3s ease;
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
        
        .status-under-progress, .status-preparing {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .status-ready {
            background: linear-gradient(135deg, #d1ecf1, #81ecec);
            color: #0c5460;
            border: 1px solid #81ecec;
        }
        
        .status-completed {
            background: linear-gradient(135deg, #d4edda, #55efc4);
            color: #155724;
            border: 1px solid #55efc4;
        }
        
        .status-cancelled {
            background: linear-gradient(135deg, #f8d7da, #ff7675);
            color: #721c24;
            border: 1px solid #ff7675;
        }
        
        .payment-unpaid {
            background: linear-gradient(135deg, #f8d7da, #ff7675);
            color: #721c24;
            border: 1px solid #ff7675;
        }
        
        .payment-paid {
            background: linear-gradient(135deg, #d4edda, #55efc4);
            color: #155724;
            border: 1px solid #55efc4;
        }
        
        .payment-refunded {
            background: linear-gradient(135deg, #e2e9f3, #a29bfe);
            color: #383d41;
            border: 1px solid #a29bfe;
        }
        
        .action-btn {
            padding: 8px 16px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: none;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            text-decoration: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .action-btn.btn-info {
            background: linear-gradient(135deg, var(--info-color), #20c997);
            color: white;
        }
        
        .item-row {
            transition: all 0.3s ease;
        }
        
        .item-row:hover {
            background-color: #f8f9fa;
            transform: scale(1.01);
        }
        
        .btn {
            border-radius: 10px;
            font-weight: 600;
            padding: 10px 20px;
            transition: all 0.3s ease;
            border: none;
        }
        
        .btn-primary {
            background: var(--gradient-primary);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-primary);
        }
        
        .btn-success {
            background: var(--success-color);
            color: white;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-primary);
        }
        
        .btn-warning {
            background: var(--warning-color);
            color: #212529;
        }
        
        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-primary);
        }
        
        .btn-danger {
            background: var(--danger-color);
            color: white;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-primary);
        }
        
        .btn-secondary {
            background: var(--primary-medium);
            color: white;
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
        
        .modal-content {
            border-radius: var(--radius-large);
            border: none;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .modal-header {
            background: var(--gradient-sidebar);
            color: white;
            border-radius: var(--radius-large) var(--radius-large) 0 0;
            border: none;
        }
        
        .modal-title {
            font-weight: 700;
        }
        
        .badge {
            border-radius: 8px;
            font-weight: 600;
            padding: 6px 12px;
        }
        
        .table-responsive {
            border-radius: 16px;
            overflow: hidden;
        }
        
        .fw-bold {
            font-weight: 700;
        }
        
        .text-primary {
            color: var(--text-primary) !important;
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
        
        .form-safe-area {
            background: var(--bg-light);
            border-radius: var(--radius-medium);
            border: 1px solid var(--border-light);
            margin-bottom: var(--spacing-md);
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
        
        [dir="rtl"] .summary-card {
            border-left: none;
            border-right: 4px solid;
        }
        
        [dir="rtl"] .summary-card.revenue {
            border-right-color: var(--success-color);
        }
        
        [dir="rtl"] .summary-card.orders {
            border-right-color: var(--info-color);
        }
        
        [dir="rtl"] .summary-card.customers {
            border-right-color: var(--warning-color);
        }
        
        [dir="rtl"] .summary-card.items {
            border-right-color: var(--primary-teal);
        }
        
        [dir="rtl"] .input-group-text {
            border-radius: 0 var(--radius-medium) var(--radius-medium) 0;
        }
        
        [dir="rtl"] .input-group .form-control {
            border-radius: var(--radius-medium) 0 0 var(--radius-medium);
        }
        
        [dir="rtl"] .table-responsive {
            direction: rtl;
        }
        
        [dir="rtl"] .table {
            direction: rtl;
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
                        <a href="order_management.php" class="nav-link active">
                            <i class="fas fa-shopping-cart me-2"></i>
                            <?php echo $lang['order_management']; ?>
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
                        <i class="fas fa-shopping-cart me-3"></i><?php echo $lang['order_management']; ?>
                    </h1>
                    <p class="mb-0 mt-2 opacity-75"><?php echo $lang['manage_orders']; ?></p>
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
                                        <a href="table_management.php" class="btn btn-info">
                        <i class="fas fa-table me-2"></i><?php echo $lang['table_management']; ?>
                    </a>
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

        <!-- Orders Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="summary-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="summary-number"><?php echo $total_orders; ?></div>
                            <div class="summary-label"><?php echo $lang['total_orders']; ?></div>
                        </div>
                        <div class="ms-3">
                            <i class="fas fa-shopping-cart fa-2x" style="color: #4ade80; opacity: 0.95;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="summary-number"><?php echo $lang['currency_symbol']; ?><?php echo number_format($total_revenue, 1); ?></div>
                            <div class="summary-label"><?php echo $lang['total_revenue']; ?></div>
                        </div>
                        <div class="ms-3">
                            <i class="fas fa-chart-line fa-2x text-info opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="summary-number"><?php echo $active_orders; ?></div>
                            <div class="summary-label"><?php echo $lang['active_orders']; ?></div>
                        </div>
                        <div class="ms-3">
                            <i class="fas fa-clock fa-2x text-warning opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="summary-number"><?php echo $completed_today; ?></div>
                            <div class="summary-label"><?php echo $lang['completed_today']; ?></div>
                        </div>
                        <div class="ms-3">
                            <i class="fas fa-check-circle fa-2x text-success opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="card enhanced-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><?php echo $lang['recent_orders']; ?></h5>
                <div class="d-flex gap-2">
                    <input type="text" class="form-control form-control-sm" id="searchOrders" placeholder="<?php echo $lang['search_orders']; ?>" style="width: 200px;">
                    <select class="form-select form-select-sm" id="filterStatus" style="width: 150px;">
                        <option value=""><?php echo $lang['all_status']; ?></option>
                        <option value="under progress">Under Progress</option>
                        <option value="preparing">Preparing</option>
                        <option value="ready">Ready</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <select class="form-select form-select-sm" id="filterPayment" style="width: 150px;">
                        <option value=""><?php echo $lang['all_payment']; ?></option>
                        <option value="unpaid">Unpaid</option>
                        <option value="paid">Paid</option>
                        <option value="refunded">Refunded</option>
                    </select>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th><?php echo $lang['order_number']; ?></th>
                                <th><?php echo $lang['table_number']; ?></th>
                                <th><?php echo $lang['customer']; ?></th>
                                <th><?php echo $lang['order_status']; ?></th>
                                <th><?php echo $lang['payment_status']; ?></th>
                                <th><?php echo $lang['created']; ?></th>
                                <th class="text-dark"><?php echo $lang['total']; ?></th>
                                <th><?php echo $lang['actions']; ?></th>
                            </tr>
                        </thead>
                        <tbody id="ordersTableBody">
                            <?php foreach ($orders as $index => $order): ?>
                                <tr class="order-row" data-status="<?php echo $order['status']; ?>"
                                    data-payment="<?php echo $order['payment_status']; ?>"
                                    data-date="<?php echo date('Y-m-d', strtotime($order['created_at'])); ?>"
                                    data-search="<?php echo strtolower($order['order_number'] . ' ' . ($order['customer_name'] ?? '') . ' ' . ($order['table_number'] ?? '')); ?>">
                                    <td>
                                        <div class="fw-bold"><?php echo htmlspecialchars($order['order_number']); ?></div>
                                        <small class="text-muted"><?php echo date('H:i', strtotime($order['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($order['table_number']): ?>
                                            <span class="badge bg-light text-dark">
                                                <?php echo htmlspecialchars($order['table_number']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($order['customer_name'] ?? 'Walk-in'); ?></div>
                                        <small class="text-muted">System</small>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo str_replace(' ', '-', $order['status']); ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge payment-<?php echo $order['payment_status']; ?>">
                                            <?php echo ucfirst($order['payment_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <div><?php echo date('M d, Y', strtotime($order['created_at'])); ?></div>
                                            <div class="text-muted"><?php echo date('H:i', strtotime($order['created_at'])); ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark">
                                            <?php echo $lang['currency_symbol']; ?><?php echo number_format($order['total_amount'], 2); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="actions-cell">
                                            <a href="?edit_order=<?php echo $order['id']; ?>" class="action-btn btn-info text-white">
                                                <i class="fas fa-eye me-2"></i>View
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Order Modal -->
    <?php if ($edit_order): ?>
        <div class="modal fade" id="edit-order-modal" tabindex="-1" aria-labelledby="edit-order-modal-label" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="edit-order-modal-label">
                            <i class="fas fa-edit me-2"></i><?php echo $lang['edit_order']; ?> #<?php echo htmlspecialchars($edit_order['order_number']); ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Order Header -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card enhanced-card">
                                    <div class="card-body">
                                        <h6 class="card-title mb-3"><?php echo $lang['order_information']; ?></h6>
                                        <div class="row">
                                            <div class="col-6">
                                                <small class="text-muted"><?php echo $lang['order_number']; ?></small>
                                                <div class="fw-bold"><?php echo htmlspecialchars($edit_order['order_number']); ?></div>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted"><?php echo $lang['table']; ?></small>
                                                <div class="fw-bold"><?php echo htmlspecialchars($edit_order['table_number'] ?? 'N/A'); ?></div>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted"><?php echo $lang['customer']; ?></small>
                                                <div class="fw-bold"><?php echo htmlspecialchars($edit_order['customer_name'] ?? 'Walk-in'); ?></div>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted"><?php echo $lang['created']; ?></small>
                                                <div class="fw-bold"><?php echo date('M d, Y H:i', strtotime($edit_order['created_at'])); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card enhanced-card">
                                    <div class="card-body">
                                        <h6 class="card-title mb-3"><?php echo $lang['order_summary']; ?></h6>
                                        <div class="row">
                                            <div class="col-6">
                                                <small class="text-muted"><?php echo $lang['subtotal']; ?></small>
                                                <div class="fw-bold"><?php echo $lang['currency_symbol']; ?><?php echo number_format($edit_order['subtotal'], 2); ?></div>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted"><?php echo $lang['total']; ?></small>
                                                <div class="fw-bold text-primary"><?php echo $lang['currency_symbol']; ?><?php echo number_format($edit_order['total_amount'], 2); ?></div>
                                            </div>
                                            <div class="col-12 mt-3">
                                                <small class="text-muted"><?php echo $lang['status']; ?></small>
                                                <div>
                                                    <select class="form-select form-select-sm" id="order-status-select">
                                                        <option value="under progress" <?php echo ($edit_order['status'] ?? 'under progress') == 'under progress' ? 'selected' : ''; ?>>Under Progress</option>
                                                        <option value="preparing" <?php echo ($edit_order['status'] ?? 'under progress') == 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                                                        <option value="ready" <?php echo ($edit_order['status'] ?? 'under progress') == 'ready' ? 'selected' : ''; ?>>Ready</option>
                                                        <option value="completed" <?php echo ($edit_order['status'] ?? 'under progress') == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                        <option value="cancelled" <?php echo ($edit_order['status'] ?? 'under progress') == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-12 mt-3">
                                                <small class="text-muted"><?php echo $lang['payment_status']; ?></small>
                                                <div>
                                                    <select class="form-select form-select-sm" id="payment-status-select">
                                                        <option value="unpaid" <?php echo ($edit_order['payment_status'] ?? 'unpaid') == 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                                                        <option value="paid" <?php echo ($edit_order['payment_status'] ?? 'unpaid') == 'paid' ? 'selected' : ''; ?>>Paid</option>
                                                        <option value="refunded" <?php echo ($edit_order['payment_status'] ?? 'unpaid') == 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Order Items -->
                        <div class="card enhanced-card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><?php echo $lang['order_items']; ?></h6>
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
                                    <i class="fas fa-plus me-2"></i><?php echo $lang['add_item']; ?>
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th><?php echo $lang['item']; ?></th>
                                                <th><?php echo $lang['price']; ?></th>
                                                <th><?php echo $lang['quantity']; ?></th>
                                                <th><?php echo $lang['total']; ?></th>
                                                <th><?php echo $lang['instructions']; ?></th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($edit_order_items as $item): ?>
                                                <tr class="item-row">
                                                    <td>
                                                        <div class="fw-semibold"><?php echo htmlspecialchars($item['name'] ?? $lang['unknown_item']); ?></div>
                                                        <small class="text-muted"><?php echo $lang['menu_item']; ?></small>
                                                    </td>
                                                    <td><?php echo $lang['currency_symbol']; ?><?php echo number_format($item['price'] ?? 0, 2); ?></td>
                                                    <td>
                                                                <div class="d-flex align-items-center gap-2">
                                                                    <button class="btn btn-sm btn-outline-secondary" onclick="updateItemQuantity(<?php echo (int)$item['id']; ?>, <?php echo (int)($item['quantity'] ?? 1) - 1; ?>)">-</button>
                                                                    <span><?php echo (int)($item['quantity'] ?? 1); ?></span>
                                                                    <button class="btn btn-sm btn-outline-secondary" onclick="updateItemQuantity(<?php echo (int)$item['id']; ?>, <?php echo (int)($item['quantity'] ?? 1) + 1; ?>)">+</button>
                                                                </div>
                                                            </td>
                                                            <td class="fw-bold"><?php echo $lang['currency_symbol']; ?><?php echo number_format($item['total_price'], 2); ?></td>
                                                            <td><?php echo htmlspecialchars($item['instructions'] ?? ''); ?></td>
                                                            <td>
                                                                <button class="btn btn-sm btn-outline-danger" onclick="removeOrderItem(<?php echo (int)$item['id']; ?>)">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Order Notes -->
                        <div class="card enhanced-card mb-4">
                            <div class="card-header">
                                <h6 class="mb-0"><?php echo $lang['order_notes']; ?></h6>
                            </div>
                            <div class="card-body">
                                <textarea class="form-control" id="order-notes" rows="3" placeholder="<?php echo $lang['add_order_notes']; ?>"><?php echo htmlspecialchars($edit_order['notes'] ?? ''); ?></textarea>
                                <button class="btn btn-primary mt-2" onclick="saveOrderNotes()">
                                    <i class="fas fa-save me-2"></i><?php echo $lang['save_notes']; ?>
                                </button>
                            </div>
                        </div>

                        <!-- Order Actions -->
                        <div class="card enhanced-card">
                            <div class="card-header">
                                <h6 class="mb-0"><?php echo $lang['order_actions']; ?></h6>
                            </div>
                            <div class="card-body">
                                <div class="d-flex gap-2 flex-wrap">
                                    <button class="btn btn-success" onclick="markOrderAsPaid()">
                                        <i class="fas fa-credit-card me-2"></i><?php echo $lang['mark_as_paid']; ?>
                                    </button>
                                    <button class="btn btn-warning" onclick="updatePaymentStatus()">
                                        <i class="fas fa-exchange-alt me-2"></i><?php echo $lang['update_payment']; ?>
                                    </button>
                                    <button class="btn btn-danger" onclick="cancelOrder()">
                                        <i class="fas fa-times me-2"></i><?php echo $lang['cancel_order']; ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo $lang['close']; ?></button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Add Item Modal -->
    <div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addItemModalLabel">
                        <i class="fas fa-plus me-2"></i><?php echo $lang['add_item_to_order']; ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addItemForm">
                        <input type="hidden" name="order_id" value="<?php echo $edit_order_id; ?>">
                        <!-- Debug: Show order_id -->
                        <div class="alert alert-info small mb-3">
                            Debug: Order ID = <?php echo $edit_order_id; ?><br>
                            Debug: Menu Items Count = <?php echo count($menu_items ?? []); ?>
                        </div>
                        
                        <div class="form-safe-area p-3">
                            <div class="mb-3">
                                <label for="menu_item_id" class="form-label">Menu Item</label>
                                <select class="form-select" name="menu_item_id" required>
                                    <option value="">Select an item</option>
                                    <?php foreach ($menu_items as $item): ?>
                                        <option value="<?php echo $item['id']; ?>" data-price="<?php echo $item['price']; ?>">
                                            <?php echo htmlspecialchars($item['name']); ?> - <?php echo $lang['currency_symbol']; ?><?php echo number_format($item['price'], 2); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="quantity" class="form-label">Quantity</label>
                                <input type="number" class="form-control" name="quantity" value="1" min="1" required>
                            </div>
                            <div class="mb-3">
                                <label for="instructions" class="form-label">Instructions</label>
                                <textarea class="form-control" name="instructions" rows="2" placeholder="Special instructions..."></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="addOrderItem(event)">Add Item</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-open edit order modal if edit_order parameter is present
        <?php if ($edit_order): ?>
            document.addEventListener('DOMContentLoaded', function() {
                const modal = new bootstrap.Modal(document.getElementById('edit-order-modal'));
                modal.show();
            });
        <?php endif; ?>

        // Search and filter functionality
        document.getElementById('searchOrders').addEventListener('input', filterOrders);
        document.getElementById('filterStatus').addEventListener('change', filterOrders);
        document.getElementById('filterPayment').addEventListener('change', filterOrders);

        function filterOrders() {
            const searchTerm = document.getElementById('searchOrders').value.toLowerCase();
            const statusFilter = document.getElementById('filterStatus').value;
            const paymentFilter = document.getElementById('filterPayment').value;
            const rows = document.querySelectorAll('#ordersTableBody .order-row');

            rows.forEach(row => {
                const searchMatch = !searchTerm || row.dataset.search.includes(searchTerm);
                const statusMatch = !statusFilter || row.dataset.status === statusFilter;
                const paymentMatch = !paymentFilter || row.dataset.payment === paymentFilter;

                row.style.display = (searchMatch && statusMatch && paymentMatch) ? '' : 'none';
            });
        }

        // Order management functions
        function updateItemQuantity(itemId, newQuantity) {
            if (newQuantity < 1) return;
            
            fetch('order_management.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update_item_quantity&item_id=${itemId}&quantity=${newQuantity}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Update Item Quantity - Error:', error);
                alert('Error updating quantity. Check console for details.');
            });
        }

        function removeOrderItem(itemId) {
            if (confirm('Are you sure you want to remove this item?')) {
                fetch('order_management.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=remove_order_item&item_id=${itemId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Remove Order Item - Error:', error);
                    alert('Error removing item. Check console for details.');
                });
            }
        }

        function addOrderItem(event) {
            console.log('Add Order Item - Function Called');
            event.preventDefault(); // Prevent default form submission
            
            const form = document.getElementById('addItemForm');
            console.log('Add Order Item - Form Element:', form);
            
            const formData = new FormData(form);
            formData.append('action', 'add_order_item');
            
            // Debug: Log form data
            console.log('Add Order Item - Form Data:');
            for (let [key, value] of formData.entries()) {
                console.log(key + ':', value);
            }
            
            // Validate form
            const menuItemId = form.querySelector('select[name="menu_item_id"]').value;
            const quantity = form.querySelector('input[name="quantity"]').value;
            
            if (!menuItemId) {
                alert('Please select a menu item');
                return;
            }
            
            if (!quantity || quantity < 1) {
                alert('Quantity must be at least 1');
                return;
            }
            
            fetch('order_management.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Add Order Item - Response:', data);
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('addItemModal')).hide();
                    location.reload();
                } else {
                    alert('Error adding item: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Add Order Item - Error:', error);
                alert('Error adding item: ' + error.message);
            });
        }

        function saveOrderNotes() {
            const notes = document.getElementById('order-notes').value;
            
            fetch('order_management.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=save_order_notes&order_id=<?php echo $edit_order_id; ?>&notes=${encodeURIComponent(notes)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Notes saved successfully!');
                }
            });
        }

        function markOrderAsPaid() {
            if (confirm('Mark this order as paid?')) {
                document.getElementById('payment-status-select').value = 'paid';
                updatePaymentStatus();
            }
        }

        function updatePaymentStatus() {
            const newStatus = document.getElementById('payment-status-select').value;
            
            fetch('order_management.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update_payment_status&order_id=<?php echo $edit_order_id; ?>&payment_status=${newStatus}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Payment status updated successfully!');
                    location.reload();
                }
            });
        }

        function cancelOrder() {
            if (confirm('Are you sure you want to cancel this order? This action cannot be undone.')) {
                fetch('order_management.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=cancel_order&order_id=<?php echo $edit_order_id; ?>`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Order cancelled successfully!');
                        window.location.href = 'order_management.php';
                    }
                });
            }
        }

        // Update order status when select changes
        document.getElementById('order-status-select')?.addEventListener('change', function() {
            const newStatus = this.value;
            
            fetch('order_management.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update_order_status&order_id=<?php echo $edit_order_id; ?>&status=${newStatus}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Order status updated successfully!');
                    location.reload();
                }
            });
        });
    </script>
            </div>
        </div>
    </div>
</body>
</html>
