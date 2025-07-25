# MIW Travel Management System

![Docker](https://img.shields.io/badge/docker-%230db7ed.svg?style=for-the-badge&logo=docker&logoColor=white)
![PHP](https://img.shields.io/badge/php-%23777BB4.svg?style=for-the-badge&logo=php&logoColor=white)
![Apache](https://img.shields.io/badge/apache-%23D42029.svg?style=for-the-badge&logo=apache&logoColor=white)
![MySQL](https://img.shields.io/badge/mysql-%2300f.svg?style=for-the-badge&logo=mysql&logoColor=white)

A comprehensive travel management system for Hajj and Umroh pilgrimage services built with PHP, Apache, and MySQL, fully containerized with Docker.

## 🚀 Quick Start

### Using Docker Hub Image

```bash
# Pull the latest image
docker pull takaruma7/miw:latest

# Clone the repository for docker-compose files
git clone https://github.com/takaruma7/MIW.git
cd MIW

# Start the application using Docker Hub image
docker-compose -f docker-compose.dockerhub.yml up -d
```

### Building from Source

```bash
# Clone the repository
git clone https://github.com/takaruma7/MIW.git
cd MIW

# Build and start
docker-compose up -d --build
```

## 📦 Available Images

- **Latest**: `takaruma7/miw:latest`
- **Version 1.0**: `takaruma7/miw:v1.0`

## 🔧 Services

| Service | Port | Description |
|---------|------|-------------|
| **Web Application** | 8080 | Main MIW application |
| **PHPMyAdmin** | 8081 | Database management |
| **MySQL** | 3307 | Database server |

## 🌐 Access URLs

- **MIW Application**: http://localhost:8080
- **PHPMyAdmin**: http://localhost:8081
- **Multi-device Access**: http://YOUR_IP:8080 (ensure firewall allows port 8080)

## ⚙️ Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `DB_HOST` | db | Database host |
| `DB_PORT` | 3306 | Database port |
| `DB_NAME` | data_miw | Database name |
| `DB_USER` | miw_user | Database user |
| `DB_PASS` | miw_password | Database password |
| `SMTP_HOST` | smtp.gmail.com | SMTP server |
| `SMTP_USERNAME` | your-email@gmail.com | SMTP username |
| `SMTP_PASSWORD` | your-app-password | SMTP password |

## 📁 Docker Compose Files

- `docker-compose.yml` - Development with local build
- `docker-compose.dockerhub.yml` - Using Docker Hub image for development
- `docker-compose.production.dockerhub.yml` - Production deployment

## 🔑 Features

- **Travel Package Management**: Hajj and Umroh packages
- **Customer Registration**: Complete pilgrim registration system
- **Document Management**: Upload and manage required documents
- **Manifest Generation**: Automated manifest creation for departures
- **Payment Processing**: Invoice and payment confirmation
- **Cancellation Management**: Handle booking cancellations
- **Admin Dashboard**: Comprehensive administrative interface
- **Email Notifications**: Automated email communications
- **Multi-format Exports**: PDF, Excel, and other formats

## 🛠️ Technology Stack

- **Backend**: PHP 8.1 with Apache 2.4
- **Database**: MySQL 8.0
- **Frontend**: HTML5, CSS3, JavaScript
- **Libraries**: 
  - PHPMailer for email functionality
  - TCPDF/DOMPDF/MPDF for PDF generation
  - PhpSpreadsheet for Excel operations
  - GuzzleHTTP for HTTP requests

## 📱 Multi-Device Access

To access from other devices on your network:

1. **Windows Firewall Configuration**:
   ```bash
   # Run the firewall configuration script
   .\configure_firewall.bat
   ```

2. **Find Your IP Address**:
   ```bash
   ipconfig
   ```

3. **Access from other devices**:
   ```
   http://YOUR_IP_ADDRESS:8080
   ```

## 🔨 Development Scripts

### Windows Batch Scripts

| Script | Purpose |
|--------|---------|
| `push_to_dockerhub.bat` | Build and push image to Docker Hub |
| `deploy_from_dockerhub.bat` | Deploy using Docker Hub image |
| `restart_docker.bat` | Restart containers with rebuild |
| `clean_restart_docker.bat` | Clean restart with cache clearing |
| `configure_firewall.bat` | Configure Windows firewall for network access |

### Manual Commands

```bash
# Build and push to Docker Hub
docker build -t takaruma7/miw:latest .
docker push takaruma7/miw:latest

# Deploy from Docker Hub
docker pull takaruma7/miw:latest
docker-compose -f docker-compose.dockerhub.yml up -d

# Check container status
docker-compose ps

# View logs
docker-compose logs web
```

## 🗄️ Database

The application uses MySQL with automatic initialization from SQL files in the `backup_sql/` directory. The database schema includes tables for:

- Customer management
- Package offerings
- Bookings and registrations
- Document storage
- Payment tracking
- Manifest generation

## 🔐 Security Features

- Environment variable configuration
- Secure database connections
- File upload validation
- Input sanitization
- Session management

## 📋 System Requirements

- **Docker**: 20.10+
- **Docker Compose**: 2.0+
- **RAM**: Minimum 2GB
- **Storage**: 5GB+ available space
- **Network**: Ports 8080, 8081, 3307 available

## 🚢 Production Deployment

For production deployment, use the production compose file:

```bash
# Set environment variables
export DB_NAME=your_production_db
export DB_USER=your_production_user
export DB_PASS=your_secure_password
export SMTP_HOST=your.smtp.server
export SMTP_USERNAME=your-production-email
export SMTP_PASSWORD=your-smtp-password

# Deploy
docker-compose -f docker-compose.production.dockerhub.yml up -d
```

## 📞 Support

For support and questions:
- **GitHub Issues**: [Report Issues](https://github.com/takaruma7/MIW/issues)
- **Docker Hub**: [takaruma7/miw](https://hub.docker.com/r/takaruma7/miw)

## 📄 License

This project is proprietary software for MIW Travel Management.

---

**Built with ❤️ for the travel and pilgrimage industry**
