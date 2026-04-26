<?php
// Staff Dashboard - Order Management Only
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
    header('Location: staff_dashboard.php');
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
            
            header("Location: staff_dashboard.php?edit_order=$order_id");
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
    } elseif ($action == 'mark_as_paid') {
        $order_id = intval($_POST['order_id'] ?? 0);
        
        if ($order_id > 0) {
            // Check if order has items and total amount > 0
            $check_sql = "SELECT COUNT(*) as item_count, total_amount FROM orders o 
                         LEFT JOIN order_items oi ON o.id = oi.order_id 
                         WHERE o.id = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("i", $order_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result()->fetch_assoc();
            
            if ($check_result['item_count'] > 0 && $check_result['total_amount'] > 0) {
                // Update payment status to paid
                $sql = "UPDATE orders SET payment_status = 'paid', status = 'completed' WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $order_id);
                $stmt->execute();
                
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Order must have items and total amount greater than 0 to mark as paid']);
            }
            exit;
        }
    } elseif ($action == 'cancel_order') {
        $order_id = intval($_POST['order_id'] ?? 0);
        $password = $_POST['password'] ?? '';
        
        // Verify password (using a simple admin password for demo - in production, use proper authentication)
        if ($password !== 'admin123') {
            echo json_encode(['success' => false, 'error' => 'Invalid password']);
            exit;
        }
        
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
        $order_id = intval($_POST['order_id'] ?? 0);
        $menu_item_id = intval($_POST['menu_item_id'] ?? 0);
        $quantity = intval($_POST['quantity'] ?? 1);
        $instructions = trim($_POST['instructions'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if ($order_id > 0 && $menu_item_id > 0 && $quantity > 0) {
            // Get order status to check if completed
            $status_sql = "SELECT status FROM orders WHERE id = ?";
            $status_stmt = $conn->prepare($status_sql);
            $status_stmt->bind_param("i", $order_id);
            $status_stmt->execute();
            $order_status = $status_stmt->get_result()->fetch_assoc()['status'] ?? '';
            
            // Check if order is completed and require password
            if ($order_status === 'completed') {
                if ($password !== 'admin123') {
                    echo json_encode(['success' => false, 'error' => 'Password required to add items to completed order']);
                    exit;
                }
            }
            
            // Get menu item details
            $item_sql = "SELECT name, price FROM menu_items WHERE id = ?";
            $item_stmt = $conn->prepare($item_sql);
            $item_stmt->bind_param("i", $menu_item_id);
            $item_stmt->execute();
            $item = $item_stmt->get_result()->fetch_assoc();
            
            if ($item) {
                $total_price = $item['price'] * $quantity;
                
                $sql = "INSERT INTO order_items (order_id, menu_item_id, quantity, unit_price, total_price, special_instructions) 
                        VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iiidds", $order_id, $menu_item_id, $quantity, $item['price'], $total_price, $instructions);
                $stmt->execute();
                
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
                echo json_encode(['success' => false, 'error' => 'Menu item not found']);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
            exit;
        }
    } elseif ($action == 'update_item_quantity') {
        $item_id = intval($_POST['item_id'] ?? 0);
        $quantity = intval($_POST['quantity'] ?? 0);
        $password = $_POST['password'] ?? '';
        
        if ($item_id > 0 && $quantity > 0) {
            // Get item details and order status
            $item_sql = "SELECT oi.order_id, oi.unit_price, o.status FROM order_items oi 
                        LEFT JOIN orders o ON oi.order_id = o.id WHERE oi.id = ?";
            $item_stmt = $conn->prepare($item_sql);
            $item_stmt->bind_param("i", $item_id);
            $item_stmt->execute();
            $item = $item_stmt->get_result()->fetch_assoc();
            
            if ($item) {
                // Check if order is completed and require password
                if ($item['status'] === 'completed') {
                    if ($password !== 'admin123') {
                        echo json_encode(['success' => false, 'error' => 'Password required to edit completed order items']);
                        exit;
                    }
                }
                
                $total_price = $item['unit_price'] * $quantity;
                $order_id = $item['order_id'];
                
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
        $password = $_POST['password'] ?? '';
        
        if ($item_id > 0) {
            // Get order_id and order status before deleting
            $item_sql = "SELECT oi.order_id, o.status FROM order_items oi 
                        LEFT JOIN orders o ON oi.order_id = o.id WHERE oi.id = ?";
            $item_stmt = $conn->prepare($item_sql);
            $item_stmt->bind_param("i", $item_id);
            $item_stmt->execute();
            $item = $item_stmt->get_result()->fetch_assoc();
            
            if ($item) {
                $order_id = $item['order_id'];
                
                // Check if order is completed and require password
                if ($item['status'] === 'completed') {
                    if ($password !== 'admin123') {
                        echo json_encode(['success' => false, 'error' => 'Password required to remove items from completed order']);
                        exit;
                    }
                }
                
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
    // Check if table exists and get its status
    $table_sql = "SELECT t.* FROM tables t WHERE t.id = ?";
    $table_stmt = $conn->prepare($table_sql);
    $table_stmt->bind_param("i", $create_order_table_id);
    $table_stmt->execute();
    $table = $table_stmt->get_result()->fetch_assoc();
    
        
    // Check if table has any active orders
    $order_check_sql = "SELECT COUNT(*) as active_count FROM orders o WHERE o.table_id = ? AND o.status NOT IN ('completed', 'cancelled')";
    $order_check_stmt = $conn->prepare($order_check_sql);
    $order_check_stmt->bind_param("i", $create_order_table_id);
    $order_check_stmt->execute();
    $order_check = $order_check_stmt->get_result()->fetch_assoc();
    
    // Flexible status check - treat empty/available as Ready for Order
    $table_status = trim($table['status'] ?? '');
    $is_ready = ($table_status === 'Ready for Order' || $table_status === '' || $table_status === 'available' || $table_status === 'Available');
    
    if ($table && $order_check['active_count'] == 0 && $is_ready) {
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
    } else {
        // Table is not available for new orders - debug logging
        error_log("Order creation failed - table data: " . print_r($table, true));
        error_log("Order creation failed - active order count: " . ($order_check['active_count'] ?? 'ERROR'));
        if (!$table) {
            error_log("Order creation failed - Table not found");
            $_SESSION['error_message'] = 'Table not found.';
        } elseif ($order_check['active_count'] > 0) {
            error_log("Order creation failed - Table has " . $order_check['active_count'] . " active orders");
            $_SESSION['error_message'] = 'This table already has an active order. Please complete the existing order first.';
        } else {
            error_log("Order creation failed - Table not ready. Status: " . ($table['status'] ?? 'NULL'));
            $_SESSION['error_message'] = 'This table is not ready for orders. Please change table status to "Ready for Order" first.';
        }
        header("Location: staff_dashboard.php");
        exit;
    }
        header("Location: staff_dashboard.php?edit_order=$order_id");
        exit;
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

// Get tables with current orders (only most recent order per table)
$tables_sql = "SELECT t.*, 
                        t.status as table_status,
                        o.id as order_id, 
                        o.order_number, 
                        o.status as order_status,
                        o.payment_status,
                        o.created_at as order_time,
                        c.name as customer_name
                 FROM tables t 
                 LEFT JOIN (
                     SELECT o.*, 
                            ROW_NUMBER() OVER (PARTITION BY o.table_id ORDER BY o.created_at DESC) as rn
                     FROM orders o 
                     WHERE o.status NOT IN ('completed', 'cancelled')
                 ) o ON t.id = o.table_id AND o.rn = 1
                 LEFT JOIN customers c ON o.customer_id = c.id 
                 ORDER BY t.table_number";

// Debug: Log the SQL query
error_log("Tables SQL Query: " . $tables_sql);

$tables_result = $conn->query($tables_sql);

// Debug: Check if query executed successfully
if ($tables_result === false) {
    error_log("Tables query failed: " . $conn->error);
    $tables = [];
} else {
    $tables = $tables_result->fetch_all(MYSQLI_ASSOC);
    error_log("Tables query executed successfully. Found " . count($tables) . " tables.");
    
    // Test: Simple query to verify table status
    $test_sql = "SELECT id, table_number, status FROM tables ORDER BY table_number";
    $test_result = $conn->query($test_sql);
    if ($test_result) {
        $test_tables = $test_result->fetch_all(MYSQLI_ASSOC);
        error_log("Simple test query results:");
        foreach ($test_tables as $test_table) {
            error_log("  Table ID: " . $test_table['id'] . " - Number: " . $test_table['table_number'] . " - Status: '" . $test_table['status'] . "'");
        }
        
        // Update tables that don't have "Ready for Order" status
        $update_sql = "UPDATE tables SET status = 'Ready for Order' WHERE status IS NULL OR status != 'Ready for Order'";
        $update_result = $conn->query($update_sql);
        if ($update_result) {
            $affected_rows = $conn->affected_rows;
            error_log("Updated " . $affected_rows . " tables to 'Ready for Order' status");
            
            // Verify the update
            $verify_sql = "SELECT id, table_number, status FROM tables ORDER BY table_number";
            $verify_result = $conn->query($verify_sql);
            if ($verify_result) {
                $verify_tables = $verify_result->fetch_all(MYSQLI_ASSOC);
                error_log("After update verification:");
                foreach ($verify_tables as $verify_table) {
                    error_log("  Table ID: " . $verify_table['id'] . " - Number: " . $verify_table['table_number'] . " - Status: '" . $verify_table['status'] . "'");
                }
            }
        } else {
            error_log("Failed to update table statuses: " . $conn->error);
        }
    } else {
        error_log("Simple test query failed: " . $conn->error);
    }
}

// Get menu items for adding to order
$menu_items_sql = "SELECT mi.*, c.name as category_name 
                   FROM menu_items mi 
                   LEFT JOIN categories c ON mi.category_id = c.id 
                   ORDER BY c.name, mi.name";
$menu_items_result = $conn->query($menu_items_sql);
$menu_items = $menu_items_result->fetch_all(MYSQLI_ASSOC);

// Calculate statistics
$total_orders = count($orders);
$total_revenue = array_sum(array_filter(array_column($orders, 'total_amount'), function($amount, $key) use ($orders) {
    return $orders[$key]['status'] === 'completed';
}, ARRAY_FILTER_USE_BOTH));
$active_orders = count(array_filter($orders, function($order) {
    return in_array($order['status'], ['under progress', 'preparing', 'ready']);
}));
$completed_today = count(array_filter($orders, function($order) {
    return $order['status'] === 'completed' && 
           date('Y-m-d', strtotime($order['created_at'])) === date('Y-m-d');
}));

$page_title = $lang['order_management'] . ' - Staff Dashboard';
// Set text direction based on language
$text_direction = ($_SESSION['lang'] === 'ar') ? 'rtl' : 'ltr';
$html_lang = ($_SESSION['lang'] === 'ar') ? 'ar' : 'en';
?>

<!DOCTYPE html>
<html lang="<?php echo $html_lang; ?>" dir="<?php echo $text_direction; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Restaurant Management System</title>
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
        
        /* Staff Dashboard Specific Styles */
        .staff-header {
            background: var(--gradient-sidebar);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 20px 20px;
        }
        
        .staff-header h1 {
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .staff-header .subtitle {
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        /* RTL Styles for Arabic */
        [dir="rtl"] {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
        
        [dir="rtl"] .table-responsive {
            direction: rtl;
        }
        
        [dir="rtl"] .table {
            direction: rtl;
        }
    </style>
</head>
<body>
    <!-- Staff Header -->
    <div class="staff-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="mb-2">
                        <i class="fas fa-user-tie me-3"></i>Staff Dashboard
                    </h1>
                    <p class="subtitle mb-0">Order Management System</p>
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
                            <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i> <?php echo $lang['profile']; ?></a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i> <?php echo $lang['settings']; ?></a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i> <?php echo $lang['logout']; ?></a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
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

        <!-- Tables Section -->
        <div class="card enhanced-card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-table me-2"></i><?php echo $lang['tables']; ?> - <?php echo $lang['status']; ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="row" id="tablesContainer">
                    <?php foreach ($tables as $table): ?>
                            <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card table-card <?php echo $table['order_id'] ? 'table-occupied' : 'table-' . strtolower(str_replace(' ', '-', trim($table['table_status'] ?? 'ready-for-order'))); ?>">
                                <div class="card-body text-center">
                                    <h4 class="table-number"><?php echo htmlspecialchars($table['table_number']); ?></h4>
                                    
                                    <?php if ($table['order_id']): ?>
                                        <!-- Table has active order -->
                                        <div class="order-info">
                                            <div class="order-number"><?php echo htmlspecialchars($table['order_number']); ?></div>
                                            <div class="customer-name"><?php echo htmlspecialchars($table['customer_name'] ?? 'Walk-in'); ?></div>
                                            <div class="order-time"><?php echo date('H:i', strtotime($table['order_time'])); ?></div>
                                            <div class="order-status">
                                                <span class="badge status-<?php echo str_replace(' ', '-', $table['order_status']); ?>">
                                                    <?php echo ucfirst($table['order_status']); ?>
                                                </span>
                                                <span class="badge payment-<?php echo $table['payment_status']; ?> ms-2">
                                                    <?php echo ucfirst($table['payment_status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="table-actions mt-2">
                                            <a href="?edit_order=<?php echo $table['order_id']; ?>" class="btn btn-info btn-sm">
                                                <i class="fas fa-eye me-1"></i>View Order
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <!-- Table availability depends on status -->
                                        <div class="table-status">
                                            <div class="mb-2">
                                                <small class="text-muted"><?php echo $lang['table_status']; ?>:</small>
                                                <div>
                                                    <span class="badge status-<?php echo str_replace(' ', '-', $table['table_status'] ?? 'ready-for-order'); ?>">
                                                        <?php echo ucfirst($table['table_status'] ?? 'Ready for Order'); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <?php 
                                                $table_status = trim($table['table_status'] ?? '');
                                                $is_available = ($table_status === 'Ready for Order' || $table_status === '' || $table_status === 'available' || $table_status === 'Available');
                                                if ($is_available): ?>
                                                <i class="fas fa-check-circle fa-2x text-success"></i>
                                                <div class="mt-2">
                                                    <span class="badge bg-success"><?php echo $lang['available']; ?></span>
                                                </div>
                                            <?php else: ?>
                                                <i class="fas fa-times-circle fa-2x text-danger"></i>
                                                <div class="mt-2">
                                                    <span class="badge bg-danger">Unavailable</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="table-actions mt-2">
                                            <?php 
                                                $table_status = trim($table['table_status'] ?? '');
                                                $is_available = ($table_status === 'Ready for Order' || $table_status === '' || $table_status === 'available' || $table_status === 'Available');
                                                if ($is_available): ?>
                                                <a href="?action=create_order&table_id=<?php echo $table['id']; ?>" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-plus me-1"></i>Create Order
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-secondary btn-sm" disabled>
                                                    <i class="fas fa-times me-1"></i>Not Available
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
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
                                                        <option value="completed" <?php echo ($edit_order['status'] ?? 'under progress') == 'completed' ? 'selected' : ''; ?>>Completed</option>
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
            
            // Check if order is completed and require password
            const orderStatus = '<?php echo $edit_order['status'] ?? ''; ?>';
            let body = `action=update_item_quantity&item_id=${itemId}&quantity=${newQuantity}`;
            
            if (orderStatus === 'completed') {
                const password = prompt('Please enter admin password to edit completed order item:');
                if (!password) return;
                body += `&password=${password}`;
            }
            
            fetch('staff_dashboard.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: body
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
            const confirmMessage = 'Are you sure you want to remove this item?';
            
            // Check if order is completed and require password
            const orderStatus = '<?php echo $edit_order['status'] ?? ''; ?>';
            
            if (orderStatus === 'completed') {
                const password = prompt('Please enter admin password to remove item from completed order:');
                if (!password) return;
                
                if (confirm(confirmMessage)) {
                    fetch('staff_dashboard.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=remove_order_item&item_id=${itemId}&password=${password}`
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
            } else {
                // For non-completed orders, proceed normally
                if (confirm(confirmMessage)) {
                    fetch('staff_dashboard.php', {
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
        }

        function addOrderItem(event) {
            console.log('Add Order Item - Function Called');
            event.preventDefault(); // Prevent default form submission
            
            const form = document.getElementById('addItemForm');
            console.log('Add Order Item - Form Element:', form);
            
            // Check if order is completed and require password
            const orderStatus = '<?php echo $edit_order['status'] ?? ''; ?>';
            
            if (orderStatus === 'completed') {
                const password = prompt('Please enter admin password to add items to completed order:');
                if (!password) return;
                form.querySelector('input[name="password"]')?.remove();
                const passwordInput = document.createElement('input');
                passwordInput.type = 'hidden';
                passwordInput.name = 'password';
                passwordInput.value = password;
                form.appendChild(passwordInput);
            }
            
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
            
            fetch('staff_dashboard.php', {
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
            
            fetch('staff_dashboard.php', {
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
                fetch('staff_dashboard.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=mark_as_paid&order_id=<?php echo $edit_order_id; ?>`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Order marked as paid and completed successfully!');
                        // Auto print and close
                        if (confirm('Would you like to print the check now?')) {
                            printCheck(() => {
                                window.location.href = 'staff_dashboard.php';
                            });
                        } else {
                            window.location.href = 'staff_dashboard.php';
                        }
                    } else {
                        alert('Error: ' + (data.error || 'Failed to mark order as paid'));
                    }
                });
            }
        }

        function printCheck() {
            const callback = arguments[0]; // Optional callback function
            
            // Create a new window for printing
            const printWindow = window.open('', '_blank');
            
            // Get current date and time
            const now = new Date();
            const dateTime = now.toLocaleString();
            
            // Build the check HTML
            const checkHTML = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Order Check - #<?php echo $edit_order['order_number'] ?? ''; ?></title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .check-header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
                        .check-title { font-size: 24px; font-weight: bold; margin: 10px 0; }
                        .check-info { margin: 15px 0; }
                        .check-items { width: 100%; border-collapse: collapse; margin: 20px 0; }
                        .check-items th, .check-items td { border: 1px solid #000; padding: 8px; text-align: left; }
                        .check-items th { background-color: #f0f0f0; font-weight: bold; }
                        .check-total { text-align: right; font-size: 18px; font-weight: bold; margin-top: 20px; }
                        .check-footer { margin-top: 30px; text-align: center; font-style: italic; }
                        @media print { body { margin: 10px; } }
                    </style>
                </head>
                <body>
                    <div class="check-header">
                        <div class="check-title">RESTAURANT CHECK</div>
                        <div>Order #<?php echo htmlspecialchars($edit_order['order_number'] ?? ''); ?></div>
                    </div>
                    
                    <div class="check-info">
                        <strong>Date:</strong> ${dateTime}<br>
                        <strong>Table:</strong> <?php echo htmlspecialchars($edit_order['table_number'] ?? ''); ?><br>
                        <strong>Customer:</strong> <?php echo htmlspecialchars($edit_order['customer_name'] ?? 'Walk-in'); ?><br>
                        <strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($edit_order['status'] ?? '')); ?><br>
                        <strong>Payment:</strong> <?php echo htmlspecialchars(ucfirst($edit_order['payment_status'] ?? '')); ?>
                    </div>
                    
                    <table class="check-items">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Qty</th>
                                <th>Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($edit_order_items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['name'] ?? ''); ?></td>
                                    <td><?php echo $item['quantity'] ?? 0; ?></td>
                                    <td><?php echo $lang['currency_symbol']; ?><?php echo number_format($item['price'] ?? 0, 2); ?></td>
                                    <td><?php echo $lang['currency_symbol']; ?><?php echo number_format($item['total_price'] ?? 0, 2); ?></td>
                                </tr>
                                <?php if (!empty($item['instructions'])): ?>
                                <tr>
                                    <td colspan="4" style="font-style: italic; font-size: 12px;">
                                        Notes: <?php echo htmlspecialchars($item['instructions']); ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="check-total">
                        Subtotal: <?php echo $lang['currency_symbol']; ?><?php echo number_format($edit_order['subtotal'] ?? 0, 2); ?><br>
                        Total: <?php echo $lang['currency_symbol']; ?><?php echo number_format($edit_order['total_amount'] ?? 0, 2); ?>
                    </div>
                    
                    <?php if (!empty($edit_order['notes'])): ?>
                    <div class="check-info">
                        <strong>Order Notes:</strong><br>
                        <?php echo htmlspecialchars($edit_order['notes']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="check-footer">
                        Thank you for your business!<br>
                        Please come again soon.
                    </div>
                </body>
                </html>
            `;
            
            // Write the HTML to the new window
            printWindow.document.write(checkHTML);
            printWindow.document.close();
            
            // Wait for the content to load, then print
            printWindow.onload = function() {
                printWindow.print();
                printWindow.close();
                
                // Execute callback if provided
                if (typeof callback === 'function') {
                    callback();
                }
            };
        }

        function cancelOrder() {
            const password = prompt('Please enter admin password to cancel this order:');
            if (password) {
                fetch('staff_dashboard.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=cancel_order&order_id=<?php echo $edit_order_id; ?>&password=${password}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Order cancelled successfully!');
                        window.location.href = 'staff_dashboard.php';
                    } else {
                        alert('Error: ' + (data.error || 'Failed to cancel order'));
                    }
                });
            }
        }

        // Update order status when select changes
        document.getElementById('order-status-select')?.addEventListener('change', function() {
            const newStatus = this.value;
            
            fetch('staff_dashboard.php', {
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
</body>
</html>
