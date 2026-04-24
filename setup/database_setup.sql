        -- Cafe Management System Database Setup
        -- Create database if not exists
        CREATE DATABASE IF NOT EXISTS cafe_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

        USE cafe_management;

        -- Users table
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE,
            phone VARCHAR(20),
            role ENUM('admin', 'manager', 'cashier', 'waiter') DEFAULT 'cashier',
            status ENUM('active', 'inactive') DEFAULT 'active',
            reset_token VARCHAR(64) DEFAULT NULL,
            reset_expiry TIMESTAMP NULL,
            remember_token VARCHAR(64) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );

        -- Customers table
        CREATE TABLE IF NOT EXISTS customers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE,
            phone VARCHAR(20),
            address TEXT,
            loyalty_points INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );

        -- Categories table
        CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            name_ar VARCHAR(100) NOT NULL,
            description TEXT,
            description_ar TEXT,
            image_url VARCHAR(255),
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );

        -- Menu items table
        CREATE TABLE IF NOT EXISTS menu_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_id INT,
            name VARCHAR(100) NOT NULL,
            name_ar VARCHAR(100) NOT NULL,
            description TEXT,
            description_ar TEXT,
            price DECIMAL(10,2) NOT NULL,
            cost_price DECIMAL(10,2),
            image_url VARCHAR(255),
            ingredients TEXT,
            allergens TEXT,
            status ENUM('active', 'inactive') DEFAULT 'active',
            preparation_time INT DEFAULT 0, -- in minutes
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );

-- Tables table
CREATE TABLE IF NOT EXISTS tables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_number VARCHAR(10) UNIQUE NOT NULL,
    capacity INT NOT NULL,
    status ENUM('available', 'occupied', 'served', 'reserved', 'cleaning') DEFAULT 'available',
    location VARCHAR(50),
    current_order_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (current_order_id) REFERENCES orders(id) ON DELETE SET NULL
);

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(20) UNIQUE NOT NULL,
    customer_id INT,
    table_id INT,
    user_id INT, -- who created the order
    subtotal DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'card', 'mobile', 'credit') DEFAULT 'cash',
    payment_status ENUM('pending', 'paid', 'refunded') DEFAULT 'pending',
    status ENUM('pending', 'preparing', 'ready', 'completed', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (table_id) REFERENCES tables(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
            customer_id INT,
            table_id INT,
            user_id INT, -- who created the order
            subtotal DECIMAL(10,2) NOT NULL,
            tax_amount DECIMAL(10,2) DEFAULT 0,
            discount_amount DECIMAL(10,2) DEFAULT 0,
            total_amount DECIMAL(10,2) NOT NULL,
            payment_method ENUM('cash', 'card', 'mobile', 'credit') DEFAULT 'cash',
            payment_status ENUM('pending', 'paid', 'refunded') DEFAULT 'pending',
            status ENUM('pending', 'preparing', 'ready', 'completed', 'cancelled') DEFAULT 'pending',
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
            FOREIGN KEY (table_id) REFERENCES tables(id) ON DELETE SET NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        );

        -- Order items table
        CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            menu_item_id INT NOT NULL,
            quantity INT NOT NULL,
            unit_price DECIMAL(10,2) NOT NULL,
            total_price DECIMAL(10,2) NOT NULL,
            special_instructions TEXT,
            status ENUM('ordered', 'preparing', 'ready', 'served', 'cancelled') DEFAULT 'ordered',
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE
        );

        -- Inventory table
        CREATE TABLE IF NOT EXISTS inventory (
            id INT AUTO_INCREMENT PRIMARY KEY,
            item_name VARCHAR(100) NOT NULL,
            item_name_ar VARCHAR(100) NOT NULL,
            description TEXT,
            current_stock DECIMAL(10,2) NOT NULL,
            unit VARCHAR(20) NOT NULL, -- kg, liters, pieces, etc.
            minimum_stock DECIMAL(10,2) DEFAULT 0,
            unit_cost DECIMAL(10,2),
            supplier VARCHAR(100),
            last_restocked DATE,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );

        -- Daily closing inventory table
        CREATE TABLE IF NOT EXISTS daily_closing_inventory (
            id INT AUTO_INCREMENT PRIMARY KEY,
            closing_date DATE NOT NULL,
            inventory_id INT NOT NULL,
            opening_stock DECIMAL(10,2) NOT NULL,
            closing_stock DECIMAL(10,2) NOT NULL,
            consumed_quantity DECIMAL(10,2) NOT NULL,
            unit_cost DECIMAL(10,2) NOT NULL,
            total_cost DECIMAL(10,2) NOT NULL,
            notes TEXT,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (inventory_id) REFERENCES inventory(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_date_inventory (closing_date, inventory_id)
        );

        -- Insert sample daily closing data
        INSERT INTO daily_closing_inventory (closing_date, inventory_id, opening_stock, closing_stock, consumed_quantity, unit_cost, total_cost, notes, created_by) VALUES
        ('2026-03-05', 1, 55.00, 50.00, 5.00, 80.00, 400.00, 'Normal daily consumption', 1),
        ('2026-03-05', 2, 25.00, 20.00, 5.00, 6.00, 30.00, 'Normal daily consumption', 1),
        ('2026-03-05', 3, 105.00, 100.00, 5.00, 4.00, 20.00, 'Normal daily consumption', 1),
        ('2026-03-05', 4, 35.00, 30.00, 5.00, 2.00, 10.00, 'Normal daily consumption', 1),
        ('2026-03-05', 5, 45.00, 40.00, 5.00, 35.00, 175.00, 'Normal daily consumption', 1);

        -- Create view for daily closing summary
        CREATE VIEW daily_closing_summary AS
        SELECT 
            dci.closing_date,
            i.item_name,
            i.item_name_ar,
            i.unit,
            dci.opening_stock,
            dci.closing_stock,
            dci.consumed_quantity,
            dci.unit_cost,
            dci.total_cost,
            dci.notes,
            u.username as created_by,
            dci.created_at
        FROM daily_closing_inventory dci
        JOIN inventory i ON dci.inventory_id = i.id
        JOIN users u ON dci.created_by = u.id
        ORDER BY dci.closing_date DESC, i.item_name;

        -- Suppliers table
        CREATE TABLE IF NOT EXISTS suppliers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            contact_person VARCHAR(100),
            email VARCHAR(100),
            phone VARCHAR(20),
            address TEXT,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );

        -- Insert sample data

        -- Insert sample users
        INSERT INTO users (username, password, full_name, email, phone, role, status) VALUES
        ('admin', 'admin123', 'Administrator', 'admin@cafe.com', '966500000001', 'admin', 'active'),
        ('manager', 'manager123', 'Ahmed Mohamed', 'manager@cafe.com', '966500000002', 'manager', 'active'),
        ('cashier1', 'cashier123', 'Fatima Ali', 'cashier1@cafe.com', '966500000003', 'cashier', 'active'),
        ('waiter1', 'waiter123', 'Mohammed Salem', 'waiter1@cafe.com', '966500000004', 'waiter', 'active');

        -- Insert sample categories
        INSERT INTO categories (name, name_ar, description, description_ar, status) VALUES
        ('Coffee', 'قهوة', 'Various types of coffee beverages', 'أنواع مختلفة من مشروبات القهوة', 'active'),
        ('Tea', 'شاي', 'Different tea varieties', 'أنواع مختلفة من الشاي', 'active'),
        ('Juices', 'عصائر', 'Fresh fruit juices', 'عصائر فواكه طازجة', 'active'),
        ('Sandwiches', 'سندويشات', 'Fresh sandwiches and wraps', 'سندويشات و wraps طازجة', 'active'),
        ('Desserts', 'حلويات', 'Sweet treats and desserts', 'الحلويات والمعجنات', 'active'),
        ('Snacks', 'وجبات خفيفة', 'Light snacks and appetizers', 'وجبات خفيفة ومقبلات', 'active');

        -- Insert sample menu items
        INSERT INTO menu_items (category_id, name, name_ar, description, description_ar, price, cost_price, preparation_time, status) VALUES
        (1, 'Espresso', 'إسبريسو', 'Strong black coffee', 'قهوة سوداء قوية', 8.00, 2.50, 3, 'active'),
        (1, 'Cappuccino', 'كابتشينو', 'Espresso with steamed milk foam', 'إسبريسو مع حليب مبخور ورغوة', 12.00, 3.50, 5, 'active'),
        (1, 'Latte', 'لاتيه', 'Espresso with steamed milk', 'إسبريسو مع حليب مبخور', 14.00, 4.00, 5, 'active'),
        (1, 'Turkish Coffee', 'قهوة تركية', 'Traditional Turkish coffee', 'قهوة تركية تقليدية', 10.00, 3.00, 7, 'active'),
        (2, 'Green Tea', 'شاي أخضر', 'Fresh green tea leaves', 'أوراق شاي أخضر طازجة', 6.00, 1.50, 4, 'active'),
        (2, 'Mint Tea', 'شاي بالنعناع', 'Tea with fresh mint leaves', 'شاي مع أوراق نعناع طازجة', 7.00, 1.80, 4, 'active'),
        (3, 'Orange Juice', 'عصير برتقال', 'Fresh squeezed orange juice', 'عصير برتقال طازج', 15.00, 5.00, 3, 'active'),
        (3, 'Apple Juice', 'عصير تفاح', 'Fresh apple juice', 'عصير تفاح طازج', 14.00, 4.50, 3, 'active'),
        (4, 'Club Sandwich', 'ساندويتش كلوب', 'Triple layer sandwich with chicken', 'ساندويتش ثلاثي الطبقات بالدجاج', 25.00, 12.00, 10, 'active'),
        (4, 'Cheese Sandwich', 'ساندويتش جبنة', 'Grilled cheese sandwich', 'ساندويتش جبنة مشوية', 18.00, 8.00, 8, 'active'),
        (5, 'Chocolate Cake', 'كيك شوكولاتة', 'Rich chocolate cake', 'كيك شوكولاتة غني', 20.00, 8.00, 2, 'active'),
        (5, 'Baklava', 'بقلاوة', 'Traditional Middle Eastern pastry', 'حلويات شرقية تقليدية', 15.00, 6.00, 2, 'active'),
        (6, 'French Fries', 'بطاطس مقلية', 'Crispy golden fries', 'بطاطس مقلية مقرمشة ذهبية', 12.00, 4.00, 6, 'active'),
        (6, 'Onion Rings', 'حلقات بصل', 'Crispy battered onion rings', 'حلقات بصل مقلية مقرمشة', 14.00, 5.00, 7, 'active');

        -- Insert sample tables
        INSERT INTO tables (table_number, capacity, status, location) VALUES
        ('T1', 4, 'available', 'Indoor'),
        ('T2', 4, 'available', 'Indoor'),
        ('T3', 2, 'available', 'Indoor'),
        ('T4', 6, 'available', 'Indoor'),
        ('T5', 4, 'available', 'Outdoor'),
        ('T6', 4, 'available', 'Outdoor'),
        ('T7', 8, 'available', 'Private Room'),
        ('T8', 2, 'available', 'Indoor');

        -- Insert sample customers
        INSERT INTO customers (name, email, phone, address, loyalty_points) VALUES
        ('John Smith', 'john@email.com', '966500000101', 'Riyadh, Saudi Arabia', 150),
        ('Sarah Johnson', 'sarah@email.com', '966500000102', 'Jeddah, Saudi Arabia', 320),
        ('Ahmed Al-Rashid', 'ahmed@email.com', '966500000103', 'Dammam, Saudi Arabia', 280),
        ('Fatima Hassan', 'fatima@email.com', '966500000104', 'Mecca, Saudi Arabia', 450),
        ('Mohammed Ali', 'mohammed@email.com', '966500000105', 'Medina, Saudi Arabia', 200);

        -- Insert sample inventory items
        INSERT INTO inventory (item_name, item_name_ar, description, current_stock, unit, minimum_stock, unit_cost, supplier, status) VALUES
        ('Coffee Beans', 'حبوب القهوة', 'Premium Arabica coffee beans', 50.0, 'kg', 10.0, 80.00, 'Coffee Supplier Co.', 'active'),
        ('Milk', 'حليب', 'Fresh whole milk', 20.0, 'liters', 5.0, 6.00, 'Dairy Farm', 'active'),
        ('Sugar', 'سكر', 'White granulated sugar', 100.0, 'kg', 20.0, 4.00, 'Food Supplier', 'active'),
        ('Bread', 'خبز', 'Fresh sandwich bread', 50.0, 'pieces', 10.0, 2.00, 'Bakery Co.', 'active'),
        ('Chicken Breast', 'صدر دجاج', 'Fresh chicken breast', 30.0, 'kg', 8.0, 25.00, 'Meat Supplier', 'active'),
        ('Cheese', 'جبنة', 'Cheddar cheese slices', 40.0, 'kg', 10.0, 35.00, 'Dairy Farm', 'active'),
        ('Potatoes', 'بطاطس', 'Fresh potatoes', 60.0, 'kg', 15.0, 3.00, 'Vegetable Supplier', 'active'),
        ('Oranges', 'برتقال', 'Fresh oranges for juice', 80.0, 'kg', 20.0, 5.00, 'Fruit Market', 'active');

        -- Insert sample suppliers
        INSERT INTO suppliers (name, contact_person, email, phone, address, status) VALUES
        ('Coffee Supplier Co.', 'Mr. Ahmed', 'ahmed@coffeesupplier.com', '966511111111', 'Riyadh, Saudi Arabia', 'active'),
        ('Dairy Farm', 'Ms. Fatima', 'fatima@dairyfarm.com', '966522222222', 'Jeddah, Saudi Arabia', 'active'),
        ('Food Supplier', 'Mr. Mohammed', 'mohammed@foodsupplier.com', '966533333333', 'Dammam, Saudi Arabia', 'active'),
        ('Bakery Co.', 'Ms. Sarah', 'sarah@bakery.com', '966544444444', 'Mecca, Saudi Arabia', 'active'),
        ('Meat Supplier', 'Mr. John', 'john@meatsupplier.com', '966555555555', 'Medina, Saudi Arabia', 'active'),
        ('Vegetable Supplier', 'Ms. Maryam', 'maryam@vegetables.com', '966566666666', 'Riyadh, Saudi Arabia', 'active'),
        ('Fruit Market', 'Mr. Ali', 'ali@fruitmarket.com', '966577777777', 'Jeddah, Saudi Arabia', 'active');

        -- Create indexes for better performance
        CREATE INDEX idx_orders_status ON orders(status);
        CREATE INDEX idx_orders_created_at ON orders(created_at);
        CREATE INDEX idx_order_items_order_id ON order_items(order_id);
        CREATE INDEX idx_menu_items_category ON menu_items(category_id);
        CREATE INDEX idx_customers_name ON customers(name);
        CREATE INDEX idx_users_username ON users(username);

        -- Create view for order summary
        CREATE VIEW order_summary AS
        SELECT 
            o.id,
            o.order_number,
            c.name as customer_name,
            t.table_number,
            u.full_name as created_by,
            o.total_amount,
            o.status,
            o.created_at
        FROM orders o
        LEFT JOIN customers c ON o.customer_id = c.id
        LEFT JOIN tables t ON o.table_id = t.id
        LEFT JOIN users u ON o.user_id = u.id;

        -- Create view for inventory status
        CREATE VIEW inventory_status AS
        SELECT 
            i.item_name,
            i.item_name_ar,
            i.current_stock,
            i.unit,
            i.minimum_stock,
            CASE 
                WHEN i.current_stock <= i.minimum_stock THEN 'Low Stock'
                WHEN i.current_stock <= (i.minimum_stock * 1.5) THEN 'Medium Stock'
                ELSE 'Good Stock'
            END as stock_status,
            s.name as supplier_name
        FROM inventory i
        LEFT JOIN suppliers s ON i.supplier = s.name
        WHERE i.status = 'active';

        -- Login logs table for security auditing
        CREATE TABLE IF NOT EXISTS login_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            username VARCHAR(50) NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('success', 'failed') NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_login_time (login_time),
            INDEX idx_status (status),
            INDEX idx_ip_address (ip_address)
        );

        -- Password reset logs table for security auditing
        CREATE TABLE IF NOT EXISTS password_reset_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            email VARCHAR(100) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            request_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('requested', 'completed', 'failed') NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_request_time (request_time),
            INDEX idx_status (status),
            INDEX idx_email (email)
        );

        -- Insert sample categories
        INSERT INTO categories (name, name_ar, description, description_ar) VALUES
        ('Beverages', 'المشروبات', 'Hot and cold drinks', 'مشروبات ساخنة وباردة'),
        ('Coffee', 'القهوة', 'Specialty coffee drinks', 'مشروبات القهوة المميزة'),
        ('Tea', 'الشاي', 'Various tea selections', 'مختارات الشاي المختلفة'),
        ('Pastries', 'المخبوزات', 'Fresh baked goods', 'السلع المخبوزة الطازجة'),
        ('Sandwiches', 'السندويتشات', 'Light meals and sandwiches', 'وجبات خفيفة وسندويتشات'),
        ('Desserts', 'الحلويات', 'Sweet treats and desserts', 'الحلويات والتراتيل الحلوة');

        -- Insert sample menu items
        INSERT INTO menu_items (category_id, name, name_ar, description, description_ar, price, cost_price, preparation_time, ingredients, allergens, status) VALUES
        -- Beverages
        (1, 'Fresh Orange Juice', 'عصير برتقال طازج', 'Freshly squeezed orange juice', 'عصير برتقال طازج', 4.50, 2.00, 5, 'Fresh oranges', 'Citrus', 'active'),
        (1, 'Lemonade', 'الليمونادة', 'Fresh lemonade with mint', 'الليمونادة الطازجة مع النعناع', 3.50, 1.50, 5, 'Fresh lemons, mint, sugar', 'Citrus', 'active'),
        (1, 'Iced Tea', 'الشاي المثلج', 'Refreshing iced tea', 'شاي منعش مثلج', 3.00, 1.00, 3, 'Tea bags, ice, lemon', 'Caffeine', 'active'),

        -- Coffee
        (2, 'Espresso', 'الإسبريسو', 'Strong Italian espresso', 'إسبريسو إيطالي قوي', 2.50, 0.80, 2, 'Coffee beans', 'Caffeine', 'active'),
        (2, 'Cappuccino', 'الكابتشينو', 'Espresso with steamed milk foam', 'إسبريسو مع حليب مبخر ورغوة', 4.00, 1.50, 4, 'Coffee beans, milk', 'Dairy, caffeine', 'active'),
        (2, 'Latte', 'اللاتيه', 'Smooth espresso with steamed milk', 'إسبريسو ناعم مع الحليب المبخر', 4.50, 1.80, 4, 'Coffee beans, milk', 'Dairy, caffeine', 'active'),
        (2, 'Mocha', 'الموكا', 'Espresso with chocolate and milk', 'إسبريسو بالشوكولاتة والحليب', 5.00, 2.20, 5, 'Coffee beans, chocolate, milk', 'Dairy, caffeine, chocolate', 'active'),

        -- Tea
        (3, 'Green Tea', 'الشاي الأخضر', 'Organic green tea', 'شاي أخضر عضوي', 3.00, 1.20, 3, 'Green tea leaves', 'Caffeine', 'active'),
        (3, 'Mint Tea', 'شاي النعناع', 'Refreshing mint tea', 'شاي النعناع المنعش', 3.50, 1.30, 3, 'Mint leaves, tea', 'Caffeine', 'active'),
        (3, 'Chamomile Tea', 'شاي البابونج', 'Relaxing chamomile tea', 'شاي البابونج المريح', 3.50, 1.40, 3, 'Chamomile flowers', 'None', 'active'),

        -- Pastries
        (4, 'Croissant', 'الكرواسان', 'Buttery French croissant', 'كرواسان فرنسي زبدة', 3.00, 1.20, 8, 'Flour, butter, yeast', 'Gluten, dairy', 'active'),
        (4, 'Chocolate Muffin', 'مافين الشوكولاتة', 'Rich chocolate muffin', 'مافين الشوكولاتة الغني', 3.50, 1.50, 12, 'Chocolate, flour, eggs', 'Gluten, dairy, eggs', 'active'),
        (4, 'Danish Pastry', 'معجنات الدنماركية', 'Fruit-filled Danish pastry', 'معجنات دنماركية بالفواكه', 4.00, 1.80, 15, 'Flour, butter, fruits', 'Gluten, dairy', 'active'),

        -- Sandwiches
        (5, 'Club Sandwich', 'ساندويتش النادي', 'Triple-decker club sandwich', 'ساندويتش النادي المكون من ثلاث طبقات', 8.50, 4.00, 12, 'Bread, turkey, bacon, lettuce, tomato', 'Gluten, meat', 'active'),
        (5, 'Grilled Cheese', 'ساندويتش الجبن المشوي', 'Classic grilled cheese sandwich', 'ساندويتش الجبن المشوي الكلاسيكي', 6.00, 2.50, 8, 'Bread, cheese, butter', 'Gluten, dairy', 'active'),
        (5, 'Vegetarian Wrap', 'لفيفة نباتية', 'Fresh vegetable wrap', 'لفيفة الخضروات الطازجة', 7.00, 3.00, 10, 'Tortilla, vegetables, hummus', 'Gluten', 'active'),

        -- Desserts
        (6, 'Chocolate Cake', 'كيكة الشوكولاتة', 'Decadent chocolate cake', 'كيكة الشوكولاتة الفاخرة', 5.50, 2.50, 5, 'Chocolate, flour, eggs, sugar', 'Gluten, dairy, eggs, chocolate', 'active'),
        (6, 'Tiramisu', 'التياراميسو', 'Classic Italian tiramisu', 'تياراميسو إيطالي كلاسيكي', 6.00, 3.00, 5, 'Mascarpone, coffee, ladyfingers', 'Dairy, caffeine, eggs', 'active'),
        (6, 'Ice Cream Scoop', 'كرة الآيس كريم', 'Premium ice cream scoop', 'كرة آيس كريم ممتازة', 3.50, 1.50, 2, 'Milk, cream, sugar, flavors', 'Dairy', 'active');
