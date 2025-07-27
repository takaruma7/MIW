# MIW Travel Management System - Comprehensive Documentation

## Table of Contents
1. [System Overview](#system-overview)
2. [Project Structure](#project-structure)
3. [Deployment Options](#deployment-options)
   - [Heroku Deployment](#heroku-deployment)
   - [Railway Deployment](#railway-deployment)
   - [Render Deployment](#render-deployment)
   - [Docker Deployment](#docker-deployment)
   - [Other Hosting Options](#other-hosting-options)
4. [Database Configuration](#database-configuration)
5. [Environment Variables](#environment-variables)
6. [File Upload System](#file-upload-system)
7. [Troubleshooting](#troubleshooting)
8. [Maintenance Tasks](#maintenance-tasks)

---

## System Overview

The MIW Travel Management System is a comprehensive web application for managing Hajj and Umroh pilgrimages. It includes:

- User registration forms for Haji and Umroh
- Admin dashboard for managing registrations
- Document management system
- Payment processing and confirmation
- Manifest generation
- Email notifications
- Cancellation handling

The system is built with PHP, MySQL/PostgreSQL, and is containerized with Docker for easy deployment.

---

## Project Structure

```
MIW/
├── 📄 Core Application Files
│   ├── index.php                    # Entry point (redirects to form_haji.php)
│   ├── config.php                   # Environment-aware configuration router
│   ├── form_haji.php                # Haji registration form
│   ├── form_umroh.php               # Umroh registration form
│   ├── form_pembatalan.php          # Cancellation form
│   └── admin_dashboard.php          # Admin main dashboard
│
├── 🎨 Frontend Assets
│   ├── styles.css                   # Main stylesheet
│   ├── admin_styles.css             # Admin interface styles
│   ├── invoice_styles.css           # Invoice/PDF styles
│   └── js/                          # JavaScript files
│
├── 🔧 Core Modules
│   ├── file_handler.php             # File serving and preview
│   ├── heroku_file_manager.php      # Heroku-compatible file management
│   ├── upload_handler.php           # Upload management
│   ├── session_manager.php          # Session handling and security
│   └── confirm_payment.php          # Payment confirmation
│
├── 🗄️ Database
│   ├── init_database_postgresql_complete_miw.sql  # PostgreSQL schema
│   ├── init_database_universal.php  # Database initialization
│   └── backup_sql/                  # Database backups
│
├── 📄 Document Generation
│   ├── invoice.php                  # Invoice generation
│   ├── manifest_haji.php            # Haji manifest
│   ├── manifest_umroh.php           # Umroh manifest
│   ├── export_manifest.php          # Excel export
│   └── kwitansi_template.php        # Receipt template
│
├── 🔧 Configuration
│   ├── config.heroku.php            # Heroku configuration
│   ├── config.php                   # Main configuration
│   ├── composer.json                # Dependencies
│   └── Procfile                     # Heroku process definition
│
├── 🛠️ Utilities
│   ├── terbilang.php                # Number to words converter
│   └── verify_cancellation.php      # Cancellation verification
│
└── 📁 Storage Directories
    ├── uploads/                     # User uploaded files
    ├── documents/                   # Generated documents
    ├── temp/                        # Temporary files
    ├── logs/                        # Application logs
    └── error_logs/                  # Error logs
```

---

## Deployment Options

The MIW system can be deployed to various platforms. Below are the configuration details for each option.

### Heroku Deployment

Heroku is a cloud platform that lets you build, deliver, monitor, and scale apps. It's an excellent option for MIW deployment.

#### Benefits
- ✅ FREE PostgreSQL database included
- ✅ Automatic HTTPS/SSL
- ✅ Git-based deployment
- ✅ Zero server management
- ✅ Auto-scaling capabilities

#### Deployment Steps

1. **Install Heroku CLI**
   ```powershell
   # Download and install from: https://devcenter.heroku.com/articles/heroku-cli
   # Or use Chocolatey:
   choco install heroku-cli
   ```

2. **Login to Heroku**
   ```bash
   heroku login
   ```

3. **Create Heroku App**
   ```bash
   # Create app with custom name
   heroku create miw-travel-app
   ```

4. **Add PostgreSQL Database**
   ```bash
   heroku addons:create heroku-postgresql:mini
   ```

5. **Set Environment Variables**
   ```bash
   heroku config:set APP_ENV=production
   heroku config:set SMTP_HOST=smtp.gmail.com
   heroku config:set SMTP_USERNAME=your-email@gmail.com
   heroku config:set SMTP_PASSWORD="your-app-password"
   heroku config:set SMTP_PORT=587
   heroku config:set SMTP_ENCRYPTION=tls
   heroku config:set MAX_FILE_SIZE=10M
   heroku config:set MAX_EXECUTION_TIME=300
   heroku config:set SECURE_HEADERS=true
   ```

6. **Deploy Application**
   ```bash
   git push heroku main
   ```

7. **Initialize Database**
   - Visit: `https://your-app-name.herokuapp.com/init_database_universal.php`

#### File Upload on Heroku

Heroku uses an ephemeral filesystem, which means uploaded files will be lost when the dyno restarts. To fix this issue:

**Option 1: Cloud Storage (Recommended)**
- Use AWS S3, Google Cloud Storage, or Cloudinary

**Option 2: Database Storage**
- Store files as BLOB in PostgreSQL

**Option 3: External File Service**
- Use Uploadcare, Filestack, or Imgur (for images)

### Railway Deployment

Railway is a modern platform with excellent MySQL support and a generous free tier.

#### Benefits
- ✅ $5 monthly credit (covers usage for small apps)
- ✅ Native Docker support
- ✅ MySQL database included
- ✅ Automatic HTTPS
- ✅ GitHub integration

#### Deployment Steps

1. **Access Railway Project**
   - Go to: https://railway.app/project/2725c7e0-071b-43ea-9be7-33142b967d77
   - Login with your GitHub account

2. **Add MySQL Database Service**
   - Click **"+ New Service"**
   - Select **"Database"** → **"MySQL"**

3. **Deploy Web Application**
   - Click **"+ New Service"** again
   - Select **"GitHub Repo"**
   - Choose your **"MIW"** repository

4. **Configure Environment Variables**
   ```bash
   # Application Settings
   APP_ENV=production
   
   # Database (Railway auto-fills these when you connect MySQL)
   DB_HOST=${{MySQL.MYSQL_HOST}}
   DB_PORT=${{MySQL.MYSQL_PORT}}
   DB_NAME=${{MySQL.MYSQL_DATABASE}}
   DB_USER=${{MySQL.MYSQL_USER}}
   DB_PASS=${{MySQL.MYSQL_PASSWORD}}
   
   # Email Configuration
   SMTP_HOST=smtp.gmail.com
   SMTP_USERNAME=your-email@gmail.com
   SMTP_PASSWORD=your-app-password
   SMTP_PORT=587
   SMTP_ENCRYPTION=tls
   
   # File Upload Settings
   MAX_FILE_SIZE=10M
   MAX_EXECUTION_TIME=300
   ```

5. **Connect Database to Web Service**
   - In web service settings, go to **"Variables"**
   - Click **"Reference"** and select your MySQL service

#### Cost Estimate
- Railway Credit: $5/month (FREE)
- Estimated Usage: $2-3/month
- Net Cost: Effectively free for 2+ months

### Render Deployment

Render offers a completely free tier for web services.

#### Benefits
- ✅ Completely FREE for web services
- ✅ Free PostgreSQL database (10GB)
- ✅ Docker Support with automatic builds
- ✅ SSL/HTTPS included
- ✅ GitHub integration with auto-deploy

#### Deployment Steps

1. **Sign Up for Render**
   - Go to https://render.com
   - Sign up with GitHub account

2. **Create PostgreSQL Database**
   - Click **"New +"**
   - Select **"PostgreSQL"**
   - Choose free tier

3. **Deploy Web Service**
   - Click **"New +"** again
   - Select **"Web Service"**
   - Connect your GitHub repository
   - Configure with Docker environment

4. **Configure Environment Variables**
   ```bash
   # Application
   APP_ENV=production
   
   # Database
   DB_HOST=your-database-hostname
   DB_PORT=5432
   DB_NAME=data_miw
   DB_USER=miw_user
   DB_PASS=your-database-password
   
   # Email Settings
   SMTP_HOST=smtp.gmail.com
   SMTP_USERNAME=your-email@gmail.com
   SMTP_PASSWORD=your-app-password
   SMTP_PORT=587
   SMTP_ENCRYPTION=tls
   
   # File Upload
   MAX_FILE_SIZE=10M
   MAX_EXECUTION_TIME=300
   SECURE_HEADERS=true
   ```

### Docker Deployment

The MIW system is containerized with Docker for easy local and production deployment.

#### Prerequisites
- Docker
- Docker Compose

#### Development Deployment
For local development with automatic code syncing:

```bash
# Linux/Mac:
./deploy.sh

# Windows:
deploy.bat
```

Access:
- MIW Application: http://localhost:8080
- PHPMyAdmin: http://localhost:8081

#### Production Deployment
```bash
# Linux/Mac:
./deploy.sh production

# Windows:
deploy.bat production
```

#### Docker Compose Files
- `docker-compose.yml`: Development configuration
- `docker-compose.production.yml`: Production configuration
- `docker-compose.dockerhub.yml`: DockerHub deployment

#### DockerHub Images
- **Latest**: `takaruma7/miw:latest`
- **Version 1.0**: `takaruma7/miw:v1.0`

### Other Hosting Options

#### Fly.io
- Free tier with 3 shared-cpu-1x 256MB VMs
- PostgreSQL included (free tier available)
- Docker-native platform
- Global edge deployment

#### Vercel
- Free tier: 100GB bandwidth/month
- Excellent for PHP via serverless functions
- No traditional database (requires external DB)

#### Hostinger
- $1.99/month
- Full PHP/MySQL support
- cPanel included
- 100GB bandwidth
- Free SSL

---

## Database Configuration

The MIW system supports both MySQL and PostgreSQL databases.

### MySQL Configuration (Default for local development)
```php
// Database connection for MySQL
$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'] ?? 'data_miw';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';
$port = $_ENV['DB_PORT'] ?? 3306;

$dsn = "mysql:host=$host;dbname=$dbname;port=$port";
$pdo = new PDO($dsn, $user, $pass);
```

### PostgreSQL Configuration (For Heroku/Render)
```php
// Database connection for PostgreSQL
$dsn = "pgsql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_NAME']}";
$pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS']);
```

### Key Database Tables
- `data_jamaah` - Pilgrim information
- `data_paket` - Travel packages
- `data_invoice` - Payment invoices
- `data_pembatalan` - Cancellation records
- `file_metadata` - Document metadata

### Database Initialization
Use the following to initialize the database structure:
- For MySQL: `backup_sql/data_miw (27).sql`
- For PostgreSQL: `init_database_postgresql_complete_miw.sql`
- Universal Initialization: Visit `/init_database_universal.php` after deployment

---

## Environment Variables

### Essential Environment Variables
```bash
# Application Settings
APP_ENV=production|development

# Database Configuration
DB_HOST=your-database-host
DB_PORT=3306|5432
DB_NAME=data_miw
DB_USER=your-username
DB_PASS=your-password

# Email Configuration
SMTP_HOST=smtp.gmail.com
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
SMTP_PORT=587
SMTP_ENCRYPTION=tls

# File Upload Settings
MAX_FILE_SIZE=10M
MAX_EXECUTION_TIME=300
SECURE_HEADERS=true
```

### Platform-Specific Variables

#### Heroku
```bash
# Heroku automatically provides:
DATABASE_URL=postgres://username:password@host:port/database
```

#### Railway
```bash
# Railway provides these automatically when you link services:
DB_HOST=${{MySQL.MYSQL_HOST}}
DB_PORT=${{MySQL.MYSQL_PORT}}
DB_NAME=${{MySQL.MYSQL_DATABASE}}
DB_USER=${{MySQL.MYSQL_USER}}
DB_PASS=${{MySQL.MYSQL_PASSWORD}}
```

---

## File Upload System

### Local File Structure
```
uploads/
├── documents/        # For ID cards, passports, etc.
├── payments/         # For payment proofs
└── cancellations/    # For cancellation documents
```

### Cloud Storage Options

For production deployment (especially on Heroku), use cloud storage:

1. **AWS S3 Integration**
   ```php
   // Example AWS S3 upload
   require 'vendor/autoload.php';
   use Aws\S3\S3Client;
   
   $s3 = new S3Client([
       'version' => 'latest',
       'region'  => 'your-region',
       'credentials' => [
           'key'    => $_ENV['AWS_KEY'],
           'secret' => $_ENV['AWS_SECRET'],
       ],
   ]);
   
   $s3->putObject([
       'Bucket' => $_ENV['S3_BUCKET'],
       'Key'    => $fileName,
       'Body'   => fopen($tempFilePath, 'r'),
       'ACL'    => 'public-read',
   ]);
   ```

2. **Database Storage**
   ```php
   // Example database storage
   $fileContent = file_get_contents($tempFilePath);
   $base64Content = base64_encode($fileContent);
   
   $stmt = $pdo->prepare("INSERT INTO file_storage (file_name, file_content, mime_type) VALUES (?, ?, ?)");
   $stmt->execute([$fileName, $base64Content, $mimeType]);
   ```

---

## Troubleshooting

### Common Issues

#### Database Connection Issues
- Verify environment variables are set correctly
- Check that database service is running
- Ensure proper database initialization
- Look at logs for specific connection errors

#### File Upload Issues
- On Heroku: Use cloud storage (AWS S3, etc.)
- Check directory permissions on local deployment
- Verify proper configuration of upload limits

#### Docker Build Issues
If encountering Git authentication errors:
1. Update composer.json to use HTTPS instead of SSH URLs
2. Update composer.lock file locally
3. Perform a clean Docker rebuild
4. Consider adding Git configuration in Dockerfile to prefer HTTPS over SSH URLs

---

## Maintenance Tasks

### Regular Tasks
1. **Database Backup**: Export via admin panel or CLI
2. **Log Rotation**: Monitor `logs/` and `error_logs/`
3. **File Cleanup**: Clean temporary files
4. **Security Updates**: Keep dependencies updated with `composer update`

### Performance Optimization
- **File Storage**: Migrate to cloud storage for production
- **Database**: Regular optimization and indexing
- **Caching**: Consider implementing Redis/Memcached
- **CDN**: Use CDN for static assets

### Security Features
- **Input Validation**: SQL injection prevention
- **File Upload Security**: Type and size restrictions
- **CSRF Protection**: Form token validation
- **Environment Separation**: Config isolation
- **Secure Headers**: Production security headers
- **Error Handling**: No sensitive data exposure
