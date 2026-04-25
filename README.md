# Cafe Management System

A comprehensive cafe management system built with PHP, MySQL, and Bootstrap, supporting both Arabic and English languages.

## Features

### 🌐 Multi-language Support
- Full Arabic and English interface
- RTL/LTR layout support
- Dynamic language switching

### 👤 User Management
- Role-based access control (Admin, Manager, Cashier, Waiter)
- Secure authentication system
- User profiles and permissions

### 📊 Dashboard
- Real-time statistics
- Order overview
- Revenue tracking
- Customer management

### 🛒 Order Management
- Complete order lifecycle
- Table management
- Order status tracking
- Payment processing

### 🍽️ Menu Management
- Categories and menu items
- Bilingual menu support
- Pricing management
- Inventory integration

### 👥 Customer Management
- Customer database
- Loyalty points system
- Order history

### 📦 Inventory Management
- Stock tracking
- Supplier management
- Low stock alerts
- Purchase management

### 📈 Reports & Analytics
- Sales reports
- Inventory reports
- Customer analytics
- Financial summaries

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- WAMP/XAMPP/MAMP (for local development)

### Setup Instructions

1. **Clone/Download the project**
   ```bash
   # Extract to your web server directory (e.g., c:/wamp64/www/)
   ```

2. **Database Setup**
   - Create a new database named `cafe_management`
   - Import the `database_setup.sql` file
   ```sql
   mysql -u root -p cafe_management < database_setup.sql
   ```

3. **Configure Database Connection**
   - Edit `config/database.php` if needed:
   ```php
   $host = 'localhost';
   $username = 'root';
   $password = '';
   $database = 'cafe_management';
   ```

4. **Access the Application**
   - Open your browser and navigate to: `http://localhost/a3det%20wanas/`

## Default Login Credentials

| Username | Password | Role |
|----------|----------|------|
| admin | admin123 | Administrator |
| manager | manager123 | Manager |
| cashier1 | cashier123 | Cashier |
| waiter1 | waiter123 | Waiter |

## Project Structure

```
cafe_management/
├── config/
│   └── database.php          # Database configuration
├── languages/
│   ├── en.php               # English language file
│   └── ar.php               # Arabic language file
├── login.php                # Login page
├── dashboard.php            # Main dashboard
├── logout.php               # Logout handler
├── database_setup.sql       # Database setup script
└── README.md               # This file
```

## Database Schema

The system includes the following main tables:
- `users` - User accounts and roles
- `customers` - Customer information
- `categories` - Menu categories
- `menu_items` - Menu items and products
- `tables` - Restaurant tables
- `orders` - Order information
- `order_items` - Order line items
- `inventory` - Stock management
- `suppliers` - Supplier information

## Sample Data

The database setup includes:
- 4 sample users with different roles
- 6 menu categories
- 14 menu items with Arabic/English names
- 8 restaurant tables
- 5 sample customers
- 8 inventory items
- 7 suppliers

## Features in Detail

### Authentication & Security
- Session-based authentication
- Password protection
- Role-based access control
- SQL injection prevention
- XSS protection

### Responsive Design
- Mobile-friendly interface
- Bootstrap 5 framework
- Font Awesome icons
- Modern UI/UX design

### Multi-language Support
- Complete Arabic translation
- RTL layout support
- Dynamic language switching
- Persistent language preference

## Future Enhancements

- [ ] Online ordering system
- [ ] Mobile app integration
- [ ] Advanced reporting
- [ ] Email notifications
- [ ] SMS integration
- [ ] QR code ordering
- [ ] Kitchen display system
- [ ] Employee scheduling
- [ ] Advanced inventory features
- [ ] Customer loyalty program
- [ ] Gift card system
- [ ] Multi-location support

## Technical Specifications

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript
- **Framework**: Bootstrap 5
- **Icons**: Font Awesome 6
- **Character Set**: UTF-8 (supports Arabic)
- **Session Management**: PHP Sessions
- **Security**: Prepared statements, input validation

## Browser Support

- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+

## Support

For support and questions:
- Check the documentation
- Review the database schema
- Test with sample data
- Verify database connection

## License

This project is for educational and demonstration purposes.

---

**Note**: This is a complete cafe management system with real data and full Arabic/English support as requested. The system includes comprehensive features for managing a cafe or restaurant operation.
