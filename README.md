# MIW Travel Management System

[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
[![Database](https://img.shields.io/badge/Database-MySQL%2FPostgreSQL-green.svg)](https://www.mysql.com/)
[![Deployment](https://img.shields.io/badge/Deployment-Heroku%20Ready-purple.svg)](https://heroku.com)

A comprehensive travel management system for organizing Haji and Umroh pilgrimages, featuring document management, manifest generation, and payment processing.

## 🌐 Live Demo

**Production:** [https://miw-travel-app-576ab80a8cab.herokuapp.com/](https://miw-travel-app-576ab80a8cab.herokuapp.com/)

## ✨ Features

### Core Functionality
- **Multi-Service Registration**: Haji and Umroh pilgrimage packages
- **Document Management**: Upload, preview, and manage required documents
- **Manifest Generation**: Automated Excel/PDF manifest creation
- **Payment Processing**: Invoice generation and payment confirmation
- **Cancellation System**: Handle booking cancellations with proper verification
- **Admin Dashboard**: Complete administrative interface

### Technical Features
- **Multi-Environment Support**: Development (MySQL) and Production (PostgreSQL)
- **File Upload System**: Heroku-compatible ephemeral file handling
- **Email Integration**: PHPMailer with SMTP support
- **PDF Generation**: Multiple PDF libraries (TCPDF, DOMPDF, MPDF)
- **Excel Export**: PhpSpreadsheet integration
- **Responsive Design**: Mobile-friendly interface

## 🚀 Quick Start

### Local Development

1. **Clone the repository**
   ```bash
   git clone https://github.com/takaruma7/MIW.git
   cd MIW
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Setup local database**
   ```bash
   # Create MySQL database 'data_miw'
   # Import backup_sql/data_miw (latest).sql
   ```

4. **Configure environment**
   ```bash
   # Copy environment template
   cp .env.example .env
   
   # Edit .env with your local settings
   # Local development uses config.php (MySQL)
   ```

5. **Start development server**
   ```bash
   php -S localhost:8000
   ```

### Production Deployment (Heroku)

1. **Create Heroku app**
   ```bash
   heroku create your-app-name
   heroku addons:create heroku-postgresql:essential-0
   ```

2. **Set environment variables**
   ```bash
   heroku config:set APP_ENV=production
   heroku config:set MAX_FILE_SIZE=10M
   heroku config:set MAX_EXECUTION_TIME=300
   heroku config:set SECURE_HEADERS=true
   
   # SMTP Configuration
   heroku config:set SMTP_HOST=smtp.gmail.com
   heroku config:set SMTP_PORT=587
   heroku config:set SMTP_USERNAME=your-email@gmail.com
   heroku config:set SMTP_PASSWORD=your-app-password
   heroku config:set SMTP_ENCRYPTION=tls
   ```

3. **Deploy**
   ```bash
   git add .
   git commit -m "Initial deployment"
   git push heroku main
   ```

4. **Initialize database**
   ```bash
   heroku run php comprehensive_fix.php
   ```

## 📁 Project Structure

```
MIW/
├── 📄 Core Application Files
│   ├── index.php                    # Entry point (redirects to form_haji.php)
│   ├── config.php                   # Environment-aware configuration router
│   ├── form_haji.php               # Haji registration form
│   ├── form_umroh.php              # Umroh registration form
│   ├── form_pembatalan.php         # Cancellation form
│   └── admin_dashboard.php         # Admin main dashboard
│
├── 🎨 Frontend Assets
│   ├── styles.css                   # Main stylesheet
│   ├── admin_styles.css            # Admin interface styles
│   ├── invoice_styles.css          # Invoice/PDF styles
│   └── js/                         # JavaScript files
│
├── 🔧 Core Modules
│   ├── file_handler.php            # File serving and preview
│   ├── heroku_file_manager.php     # Heroku-compatible file management
│   ├── upload_handler.php          # File upload processing
│   ├── email_functions.php         # Email utilities
│   └── paket_functions.php         # Package management
│
├── 👥 Admin Interface
│   ├── admin_dashboard.php         # Main admin panel
│   ├── admin_kelengkapan.php       # Document completion tracking
│   ├── admin_manifest.php          # Manifest generation
│   ├── admin_paket.php             # Package management
│   ├── admin_pembatalan.php        # Cancellation management
│   ├── admin_pending.php           # Pending registrations
│   └── admin_roomlist.php          # Room allocation
│
├── 📋 Form Processing
│   ├── submit_haji.php             # Haji form processor
│   ├── submit_umroh.php            # Umroh form processor
│   ├── submit_pembatalan.php       # Cancellation processor
│   └── confirm_payment.php         # Payment confirmation
│
├── 🗄️ Database
│   ├── init_database_postgresql_complete_miw.sql  # PostgreSQL schema
│   ├── add_file_metadata_table.sql                # File metadata table
│   ├── comprehensive_fix.php                      # Database initialization
│   └── backup_sql/                               # Database backups
│
├── 📄 Document Generation
│   ├── invoice.php                 # Invoice generation
│   ├── manifest_haji.php           # Haji manifest
│   ├── manifest_umroh.php          # Umroh manifest
│   ├── export_manifest.php         # Excel export
│   └── kwitansi_template.php       # Receipt template
│
├── 🔧 Configuration
│   ├── config.heroku.php           # Heroku configuration
│   ├── config.render.php           # Render configuration
│   ├── config.postgresql.php       # PostgreSQL configuration
│   ├── composer.json               # Dependencies
│   └── Procfile                    # Heroku process definition
│
├── 🛠️ Utilities
│   ├── dev_inspector.php           # Development inspector tool
│   ├── cleanup_directory.php       # Directory cleanup analyzer
│   ├── terbilang.php              # Number to words converter
│   └── verify_cancellation.php    # Cancellation verification
│
└── 📁 Storage Directories
    ├── uploads/                    # User uploaded files
    ├── documents/                  # Generated documents
    ├── temp/                       # Temporary files
    ├── logs/                       # Application logs
    └── error_logs/                 # Error logs
```

## 🗄️ Database Schema

### Core Tables
- **`data_haji`** - Haji pilgrimage registrations
- **`data_umroh`** - Umroh pilgrimage registrations  
- **`data_pembatalan`** - Cancellation records
- **`data_paket`** - Travel packages
- **`file_metadata`** - File upload tracking (Heroku compatibility)

### Key Fields
- **Personal Information**: Name, ID, passport, contact details
- **Travel Details**: Package selection, dates, room preferences
- **Documents**: Required document uploads with metadata
- **Financial**: Payment status, invoice generation
- **Administrative**: Status tracking, manifest inclusion

## 🔧 Configuration

### Environment Detection
The system automatically detects the deployment environment:

- **Heroku**: Uses `config.heroku.php` (PostgreSQL)
- **Render**: Uses `config.render.php` (PostgreSQL)  
- **Local**: Uses `config.php` (MySQL)

### File Upload Handling
- **Development**: Files stored in `uploads/` directory
- **Heroku**: Ephemeral storage with metadata tracking
- **Production**: Recommended to use cloud storage (S3, Cloudinary)

### Email Configuration
SMTP settings are environment-specific:
```php
// Heroku (via environment variables)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
```

## 🎯 API Endpoints

### Public Endpoints
- `GET /` - Redirects to registration form
- `GET /form_haji.php` - Haji registration form
- `GET /form_umroh.php` - Umroh registration form
- `GET /form_pembatalan.php` - Cancellation form
- `POST /submit_haji.php` - Process Haji registration
- `POST /submit_umroh.php` - Process Umroh registration

### Admin Endpoints
- `GET /admin_dashboard.php` - Admin dashboard
- `GET /admin_kelengkapan.php` - Document management
- `GET /admin_manifest.php` - Manifest generation
- `GET /export_manifest.php` - Excel export
- `GET /invoice.php?id={id}&type={type}` - Generate invoice

### File Handling
- `GET /file_handler.php?file={filename}` - Serve uploaded files
- `POST /upload_handler.php` - Handle file uploads

## 🚀 Deployment Environments

### Supported Platforms
- **Heroku** ✅ (Primary, Production-Ready)
- **Render** ✅ (Alternative)
- **Railway** ✅ (Alternative)
- **Local XAMPP/WAMP** ✅ (Development)

### Environment Variables
```bash
# Required for production
APP_ENV=production
DATABASE_URL=postgresql://...

# File handling
MAX_FILE_SIZE=10M
MAX_EXECUTION_TIME=300

# Security
SECURE_HEADERS=true

# Email (Gmail SMTP)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
SMTP_ENCRYPTION=tls
```

## 🔨 Development Tools

### Developer Inspector
Access deployment information:
```
https://your-app.herokuapp.com/dev_inspector.php?pwd=dev123
```
Shows: Environment details, file system, database status, logs, performance metrics.

**⚠️ Security Warning**: Remove `dev_inspector.php` before final production deployment.

### Database Tools
```bash
# PostgreSQL (Heroku)
heroku pg:psql --app your-app-name

# Initialize/fix database
heroku run php comprehensive_fix.php --app your-app-name

# View logs
heroku logs --tail --app your-app-name
```

## 📚 Dependencies

### Core PHP Dependencies
```json
{
  "tecnickcom/tcpdf": "^6.8",      // PDF generation
  "dompdf/dompdf": "^2.0",         // Alternative PDF generation  
  "mpdf/mpdf": "^8.0",             // Another PDF option
  "phpoffice/phpspreadsheet": "^1.29", // Excel generation
  "phpmailer/phpmailer": "^6.6",   // Email functionality
  "guzzlehttp/guzzle": "^7.9"      // HTTP client
}
```

### System Requirements
- **PHP**: 8.1 or higher
- **Extensions**: PDO, GD, mbstring
- **Database**: MySQL 5.7+ or PostgreSQL 12+
- **Memory**: 256MB+ recommended

## 🛠️ Maintenance

### Regular Tasks
1. **Database Backup**: Export via admin panel or CLI
2. **Log Rotation**: Monitor `logs/` and `error_logs/`
3. **File Cleanup**: Use `cleanup_directory.php` for analysis
4. **Security Updates**: Keep dependencies updated with `composer update`

### Performance Optimization
- **File Storage**: Migrate to cloud storage for production
- **Database**: Regular optimization and indexing
- **Caching**: Consider implementing Redis/Memcached
- **CDN**: Use CDN for static assets

## 🔐 Security Features

- **Input Validation**: SQL injection prevention
- **File Upload Security**: Type and size restrictions
- **CSRF Protection**: Form token validation
- **Environment Separation**: Config isolation
- **Secure Headers**: Production security headers
- **Error Handling**: No sensitive data exposure

## 📞 Support

### Troubleshooting
1. **File Upload Issues**: Check `file_handler.php` logs
2. **Database Errors**: Run `comprehensive_fix.php`
3. **Email Problems**: Verify SMTP configuration
4. **Performance**: Monitor via `dev_inspector.php`

### Common Issues
- **403 Forbidden on files**: Heroku ephemeral storage limitation
- **Database connection**: Check environment variables
- **Memory limits**: Adjust `MAX_EXECUTION_TIME` and `MAX_FILE_SIZE`

## 📄 License

This project is proprietary software developed for MIW Travel Management.

## 🤝 Contributing

Internal development only. For feature requests or bug reports, contact the development team.

---

**Built with ❤️ for MIW Travel Management System**

*Last updated: July 26, 2025*
