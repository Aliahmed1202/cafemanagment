-- Create payments table for income and outcome tracking
USE cafe_management;

CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT NOT NULL,
    type ENUM('income', 'outcome') NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_type (type),
    INDEX idx_created_at (created_at),
    INDEX idx_created_by (created_by)
);

-- Create view for payment summary
CREATE VIEW payment_summary AS
SELECT 
    p.id,
    p.amount,
    p.description,
    p.type,
    p.created_at,
    u.full_name as created_by_name,
    u.username as created_by_username
FROM payments p
LEFT JOIN users u ON p.created_by = u.id
ORDER BY p.created_at DESC;

-- Insert sample payment data
INSERT INTO payments (amount, description, type, created_by) VALUES
(1500.00, 'Daily sales revenue', 'income', 1),
(800.00, 'Catering service payment', 'income', 1),
(500.00, 'Monthly coffee beans purchase', 'outcome', 2),
(300.00, 'Utility bills payment', 'outcome', 2),
(200.00, 'Cleaning supplies', 'outcome', 3),
(1200.00, 'Weekend sales revenue', 'income', 1),
(150.00, 'Milk and dairy products', 'outcome', 3),
(450.00, 'Private event booking', 'income', 1);
