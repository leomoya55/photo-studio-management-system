# Legend Academy - Layered Architecture

## 🏗️ Project Structure

```
ProyectoVanessa/
├── config/                 # Configuration files
│   ├── paths.php          # Path definitions and auto-loader
│   ├── db_connect.php     # Database connection
│   └── session_manager.php # Session management
├── controllers/           # Business logic layer
│   └── AuthController.php # Authentication controller
├── models/               # Data access layer
│   ├── User.php         # User model
│   └── Classes.php      # Classes model
├── views/               # Presentation layer templates
│   ├── index.php        # Home page template
│   ├── clases.php       # Classes page template
│   ├── horarios.php     # Schedule page template
│   ├── catalogo.php     # Catalog page template
│   ├── ubicacion.php    # Location page template
│   ├── redes-sociales.php # Social media template
│   ├── contact.php      # Contact form template
│   ├── login.php        # Login form template
│   ├── register.php     # Registration template
│   ├── dashboard.php    # User dashboard template
│   ├── enroll.php       # Enrollment template
│   └── logout.php       # Logout handler
├── includes/            # Shared includes and helpers
├── admin/              # Admin panel files
│   ├── admin.php       # Main admin dashboard
│   ├── admin_products.php
│   └── admin_social.php
├── assets/             # Static resources
│   ├── css/           # Stylesheets
│   └── js/            # JavaScript files
├── data/              # JSON data files
│   ├── classes.json   # Classes data
│   ├── instructors.json
│   └── social_posts.json
├── vendor/            # Composer dependencies
├── app.php           # Application router (optional)
├── index.php         # Home page
├── clases.php        # Classes page
├── horarios.php      # Schedule page
├── login.php         # Login page
├── register.php      # Registration page
└── ... (other pages)
```

## 📁 Directory Purposes

### `/config/`
- **Purpose**: Configuration files and system setup
- **Files**: Database connections, path definitions, session management
- **Usage**: Include `config/paths.php` at the top of files to access all configurations

### `/controllers/`
- **Purpose**: Business logic and request handling
- **Files**: Controller classes that handle user interactions
- **Usage**: Controllers process form data, validate input, and coordinate between models and views

### `/models/`
- **Purpose**: Data access layer and business objects
- **Files**: Classes that interact with database and data files
- **Usage**: Models handle all database operations and data validation

### `/views/`
- **Purpose**: Presentation layer templates
- **Files**: All view templates and UI components (index.php, clases.php, etc.)
- **Usage**: HTML/PHP templates that display data to users
- **Access**: Included via root-level entry points for backward compatibility

### `/includes/`
- **Purpose**: Shared utilities and helper functions
- **Files**: Common functions, utilities, and shared components

### `/admin/`
- **Purpose**: Administrative interface
- **Files**: Admin-only pages and functionality
- **Access**: Requires admin role authentication

### `/data/`
- **Purpose**: JSON data storage
- **Files**: Static data files for classes, instructors, etc.
- **Usage**: JSON files for configuration and static content

## 🚀 Usage Examples

### Including Files
```php
// In root-level files
require_once 'config/paths.php';

// In views/ subdirectory files
require_once '../config/paths.php';

// Use helper functions to include files
includeModel('User.php');
includeController('AuthController.php');
```

### Entry Point Structure
```php
// Root-level entry points (index.php, clases.php, etc.)
<?php include 'views/index.php'; ?>

// This provides backward compatibility while maintaining MVC structure
```

### Using Models
```php
// Create a user model
$userModel = new User($conn);

// Get all customers
$customers = $userModel->getAllCustomers();
```

### Using Controllers
```php
// Create auth controller
$auth = new AuthController($conn);

// Handle login
$result = $auth->login($email, $password);
```

## 🧹 Cleaned Up Files

### Removed Files
- `check_users.php` - Debug file
- `check_schema.php` - Debug file  
- `test_connection.php` - Test file
- `test_email.php` - Test file
- `test_login.php` - Test file
- `test_registration.php` - Test file
- `system_test.php` - Test file
- `setup_database.php` - Setup file
- `setup_social_posts_table.php` - Setup file
- `fix_database_collation.php` - Fix file
- `simple_collation_fix.php` - Fix file

### Reorganized Files
- Configuration files moved to `/config/`
- Admin files moved to `/admin/`
- Data files consolidated in `/data/`

## 📝 Benefits

1. **Separation of Concerns**: Each layer has a specific responsibility
2. **Maintainability**: Easier to maintain and update code
3. **Scalability**: Better structure for adding new features
4. **Security**: Better organization of access controls
5. **Debugging**: Easier to locate and fix issues
6. **Clean Codebase**: Removed unnecessary test and debug files

## 🔄 Migration Notes

- All existing functionality preserved
- New path system automatically loads required files
- Admin panel accessible at `/admin/`
- All public pages work as before
- Database connections handled centrally in `/config/`

## ✅ Completed MVC Implementation

### Recent Achievements
- **✅ View Layer Separation**: All view files moved to `/views/` directory
- **✅ Entry Point System**: Root-level files now include views for backward compatibility
- **✅ Path Management**: Updated all path references to work with new structure
- **✅ File Cleanup**: Removed 11 unnecessary test and debug files

### Current Architecture Status
- **Models**: User.php, Classes.php implemented
- **Controllers**: AuthController.php handling authentication
- **Views**: All presentation templates in `/views/` directory
- **Configuration**: Centralized in `/config/` with auto-loading

## 🎯 Future Enhancements

1. Create more specific controllers for different features
2. Add more model classes (Schedule, Product, SocialPost)
3. Implement proper URL routing system
4. Add unit tests in separate `/tests/` directory
5. Create shared view components for common UI elements