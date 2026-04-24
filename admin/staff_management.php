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

// Ensure language variables are set if not loaded from file
$is_arabic = $_SESSION['lang'] === 'ar';

if (!isset($lang['staff_management'])) $lang['staff_management'] = $is_arabic ? 'إدارة الموظفين' : 'Staff Management';
if (!isset($lang['employees'])) $lang['employees'] = $is_arabic ? 'الموظفون' : 'Employees';
if (!isset($lang['manage_staff'])) $lang['manage_staff'] = $is_arabic ? 'إدارة موظفي المطعم وصلاحياتهم' : 'Manage restaurant staff and their permissions';
if (!isset($lang['add_staff'])) $lang['add_staff'] = $is_arabic ? 'إضافة موظف' : 'Add Staff';
if (!isset($lang['username'])) $lang['username'] = $is_arabic ? 'اسم المستخدم' : 'Username';
if (!isset($lang['full_name'])) $lang['full_name'] = $is_arabic ? 'الاسم الكامل' : 'Full Name';
if (!isset($lang['phone'])) $lang['phone'] = $is_arabic ? 'رقم الهاتف' : 'Phone';
if (!isset($lang['role'])) $lang['role'] = $is_arabic ? 'الدور' : 'Role';
if (!isset($lang['status'])) $lang['status'] = $is_arabic ? 'الحالة' : 'Status';
if (!isset($lang['active'])) $lang['active'] = $is_arabic ? 'نشط' : 'Active';
if (!isset($lang['inactive'])) $lang['inactive'] = $is_arabic ? 'غير نشط' : 'Inactive';
if (!isset($lang['actions'])) $lang['actions'] = $is_arabic ? 'الإجراءات' : 'Actions';
if (!isset($lang['edit'])) $lang['edit'] = $is_arabic ? 'تعديل' : 'Edit';
if (!isset($lang['delete'])) $lang['delete'] = $is_arabic ? 'حذف' : 'Delete';
if (!isset($lang['change_password'])) $lang['change_password'] = $is_arabic ? 'تغيير كلمة المرور' : 'Change Password';
if (!isset($lang['language'])) $lang['language'] = $is_arabic ? 'اللغة' : 'Language';
if (!isset($lang['logout'])) $lang['logout'] = $is_arabic ? 'تسجيل الخروج' : 'Logout';
if (!isset($lang['profile'])) $lang['profile'] = $is_arabic ? 'الملف الشخصي' : 'Profile';
if (!isset($lang['settings'])) $lang['settings'] = $is_arabic ? 'الإعدادات' : 'Settings';

// Handle language change
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
    header('Location: staff_management.php');
    exit();
}

// No need to create staff table - we'll use the existing users table

// Handle CRUD operations
$message = '';
$message_type = '';

// Add new staff (create user account)
if (isset($_POST['add_staff'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $role = $conn->real_escape_string($_POST['role']);
    $password = password_hash('password123', PASSWORD_DEFAULT); // Default password
    $status = $conn->real_escape_string($_POST['status']);
    
    // Validate phone number (must be exactly 11 digits)
    if (!empty($phone) && (!preg_match('/^[0-9]{11}$/', $phone))) {
        $message = "Phone number must be exactly 11 digits!";
        $message_type = "error";
    } else {
        $insert_sql = "INSERT INTO users (username, full_name, phone, role, password, status) 
                       VALUES ('$username', '$full_name', '$phone', '$role', '$password', '$status')";
        
        if ($conn->query($insert_sql)) {
            $message = "Staff member added successfully! Default password: password123";
            $message_type = "success";
        } else {
            $message = "Error adding staff member: " . $conn->error;
            $message_type = "error";
        }
    }
}

// Update staff
if (isset($_POST['update_staff'])) {
    $id = (int)$_POST['id'];
    
    // Check if this is a password-only update (from password modal)
    $password_only = (!isset($_POST['username']) && !isset($_POST['full_name']) && !isset($_POST['phone']) && !isset($_POST['role']) && !isset($_POST['status']));
    
    if ($password_only) {
        // Password-only update
        $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;
        
        if ($password) {
            $update_sql = "UPDATE users SET password = '$password' WHERE id = $id";
            
            if ($conn->query($update_sql)) {
                $message = "Password changed successfully!";
                $message_type = "success";
            } else {
                $message = "Error changing password: " . $conn->error;
                $message_type = "error";
            }
        } else {
            $message = "Please provide a new password.";
            $message_type = "error";
        }
    } else {
        // Full staff update
        $username = $conn->real_escape_string($_POST['username']);
        $full_name = $conn->real_escape_string($_POST['full_name']);
        $phone = $conn->real_escape_string($_POST['phone']);
        $role = $conn->real_escape_string($_POST['role']);
        $status = $conn->real_escape_string($_POST['status']);
        $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;
        
        // Validate phone number (must be exactly 11 digits)
        if (!empty($phone) && (!preg_match('/^[0-9]{11}$/', $phone))) {
            $message = "Phone number must be exactly 11 digits!";
            $message_type = "error";
        } else {
            // Build update query dynamically based on whether password is being updated
            $update_sql = "UPDATE users SET 
                           username = '$username', 
                           full_name = '$full_name', 
                           phone = '$phone', 
                           role = '$role', 
                           status = '$status'";
            
            // Add password to update if provided
            if ($password) {
                $update_sql .= ", password = '$password'";
            }
            
            $update_sql .= " WHERE id = $id";
            
            if ($conn->query($update_sql)) {
                $message = "Staff member updated successfully!";
                $message_type = "success";
            } else {
                $message = "Error updating staff member: " . $conn->error;
                $message_type = "error";
            }
        }
    }
}

// Delete staff
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $delete_sql = "DELETE FROM users WHERE id = $id";
    
    if ($conn->query($delete_sql)) {
        $message = "Staff member deleted successfully!";
        $message_type = "success";
    } else {
        $message = "Error deleting staff member: " . $conn->error;
        $message_type = "error";
    }
}

// Get all staff members (users with staff roles)
$staff_sql = "SELECT * FROM users WHERE role IN ('owner', 'staff') ORDER BY created_at DESC";
$staff_result = $conn->query($staff_sql);

// Get staff member for editing
$edit_staff = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $edit_sql = "SELECT * FROM users WHERE id = $id AND role IN ('owner', 'staff')";
    $edit_result = $conn->query($edit_sql);
    if ($edit_result && $edit_result->num_rows > 0) {
        $edit_staff = $edit_result->fetch_assoc();
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>" dir="<?php echo $_SESSION['lang'] == 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['staff_management'] ?? 'Staff Management'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/color-palette.css" rel="stylesheet">
    <style>
        body {
            background-color: var(--bg-secondary);
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
        .card {
            background: var(--bg-white);
            border-radius: var(--radius-large);
            padding: var(--spacing-lg);
            box-shadow: 0 5px 15px var(--shadow-secondary);
            margin-bottom: var(--spacing-lg);
        }
        .table-responsive {
            border-radius: var(--radius-large);
            overflow: hidden;
        }
        .btn-action {
            padding: 5px 10px;
            margin: 0 2px;
            border-radius: var(--radius-small);
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
        }
        .status-active {
            background-color: var(--success-color);
            color: var(--text-white);
        }
        .status-inactive {
            background-color: var(--danger-color);
            color: var(--text-white);
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
                        <a href="staff_management.php" class="nav-link active">
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
                            <h2 class="mb-0"><?php echo $lang['staff_management'] ?? 'Staff Management'; ?></h2>
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
                            <button class="btn btn-primary ms-2" data-bs-toggle="modal" data-bs-target="#staffModal">
                                <i class="fas fa-plus me-2"></i><?php echo $lang['add_staff'] ?? 'Add Staff'; ?>
                            </button>
                        </div>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Staff Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Username</th>
                                        <th><?php echo $lang['name'] ?? 'Full Name'; ?></th>
                                        <th><?php echo $lang['phone'] ?? 'Phone'; ?></th>
                                        <th>Role</th>
                                        <th><?php echo $lang['status'] ?? 'Status'; ?></th>
                                        <th><?php echo $lang['actions'] ?? 'Actions'; ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($staff_result && $staff_result->num_rows > 0): ?>
                                        <?php $count = 1; ?>
                                        <?php while ($row = $staff_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $count++; ?></td>
                                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($row['phone'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $row['role'] == 'owner' ? 'danger' : 'secondary'; ?>">
                                                        <?php echo ucfirst($row['role']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $row['status']; ?>">
                                                        <?php echo ucfirst($row['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-warning btn-action" onclick="editStaff(<?php echo $row['id']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-info btn-action" onclick="showPasswordChange(<?php echo $row['id']; ?>)">
                                                        <i class="fas fa-key"></i>
                                                    </button>
                                                    <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger btn-action" 
                                                       onclick="return confirm('Are you sure you want to delete this staff member?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center"><?php echo $lang['no_staff_found'] ?? 'No staff members found'; ?></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Staff Modal -->
    <div class="modal fade" id="staffModal" tabindex="-1" aria-labelledby="staffModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staffModalLabel">
                        <?php echo $edit_staff ? ($lang['edit_staff'] ?? 'Edit Staff') : ($lang['add_staff'] ?? 'Add Staff'); ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="id" value="<?php echo $edit_staff['id'] ?? ''; ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label">Username *</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($edit_staff['username'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="full_name" class="form-label"><?php echo $lang['name'] ?? 'Full Name'; ?> *</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                       value="<?php echo htmlspecialchars($edit_staff['full_name'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="phone" class="form-label"><?php echo $lang['phone'] ?? 'Phone'; ?> (11 characters)</label>
                                <input type="text" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($edit_staff['phone'] ?? ''); ?>"
                                       pattern="[0-9]{11}" maxlength="11" placeholder="Enter 11-digit phone number"
                                       oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                <small class="text-muted">Phone number must be exactly 11 digits</small>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="role" class="form-label">Role *</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="">Select Role</option>
                                    <option value="owner" <?php echo ($edit_staff['role'] ?? '') == 'owner' ? 'selected' : ''; ?>>Owner</option>
                                    <option value="staff" <?php echo ($edit_staff['role'] ?? '') == 'staff' ? 'selected' : ''; ?>>Staff</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label"><?php echo $lang['status'] ?? 'Status'; ?></label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active" <?php echo ($edit_staff['status'] ?? 'active') == 'active' ? 'selected' : ''; ?>>
                                        <?php echo $lang['active'] ?? 'Active'; ?>
                                    </option>
                                    <option value="inactive" <?php echo ($edit_staff['status'] ?? '') == 'inactive' ? 'selected' : ''; ?>>
                                        <?php echo $lang['inactive'] ?? 'Inactive'; ?>
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <?php if ($edit_staff): ?>
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">New Password (leave blank to keep current)</label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Enter new password or leave blank"
                                       minlength="6">
                                <small class="text-muted">Minimum 6 characters. Leave blank to keep current password.</small>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo $lang['cancel'] ?? 'Cancel'; ?></button>
                        <button type="submit" name="<?php echo $edit_staff ? 'update_staff' : 'add_staff'; ?>" class="btn btn-primary">
                            <?php echo $edit_staff ? ($lang['update'] ?? 'Update') : ($lang['add'] ?? 'Add'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Password Change Modal -->
    <div class="modal fade" id="passwordModal" tabindex="-1" aria-labelledby="passwordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="passwordModalLabel">Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="password_user_id">
                        <input type="hidden" name="update_staff" value="1">
                        
                        <div class="mb-3">
                            <label for="staff_name" class="form-label">Staff Member</label>
                            <input type="text" class="form-control" id="staff_name" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password_new" class="form-label">New Password *</label>
                            <input type="password" class="form-control" id="password_new" name="password" 
                                   required minlength="6">
                            <small class="text-muted">Minimum 6 characters required.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password_confirm" class="form-label">Confirm Password *</label>
                            <input type="password" class="form-control" id="password_confirm" required minlength="6">
                            <small class="text-muted">Re-enter the new password to confirm.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" onclick="return validatePasswordForm()">Change Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Auto-show modal for editing -->
    <?php if ($edit_staff): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var staffModal = new bootstrap.Modal(document.getElementById('staffModal'));
                staffModal.show();
            });
        </script>
    <?php endif; ?>

    <script>
        // Edit staff function
        function editStaff(id) {
            window.location.href = '?edit=' + id;
        }
        
        // Show password change modal
        function showPasswordChange(id) {
            // Get staff details from the table
            const row = document.querySelector('tr:has(button[onclick*="showPasswordChange(' + id + ')"])');
            if (row) {
                const cells = row.getElementsByTagName('td');
                const username = cells[1].textContent.trim();
                const fullName = cells[2].textContent.trim();
                
                document.getElementById('password_user_id').value = id;
                document.getElementById('staff_name').value = username + ' - ' + fullName;
                document.getElementById('password_new').value = '';
                document.getElementById('password_confirm').value = '';
                
                var passwordModal = new bootstrap.Modal(document.getElementById('passwordModal'));
                passwordModal.show();
            }
        }
        
        // Validate password form
        function validatePasswordForm() {
            const newPassword = document.getElementById('password_new').value;
            const confirmPassword = document.getElementById('password_confirm').value;
            
            if (newPassword.length < 6) {
                alert('Password must be at least 6 characters long.');
                return false;
            }
            
            if (newPassword !== confirmPassword) {
                alert('Passwords do not match. Please confirm the new password.');
                return false;
            }
            
            return true;
        }
        
        // Clear password fields when modal is hidden
        document.addEventListener('DOMContentLoaded', function() {
            const passwordModal = document.getElementById('passwordModal');
            if (passwordModal) {
                passwordModal.addEventListener('hidden.bs.modal', function () {
                    document.getElementById('password_new').value = '';
                    document.getElementById('password_confirm').value = '';
                });
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
