# MIW Travel Management System - Docker Deployment

This document provides instructions for deploying the MIW Travel Management System using Docker. Two deployment options are available: development (local) and production.

## Prerequisites

- [Docker](https://www.docker.com/get-started)
- [Docker Compose](https://docs.docker.com/compose/install/)

## Quick Start

### Setup Environment Variables

1. Copy the example environment file:

```bash
cp .env.example .env
```

2. Edit the `.env` file and update with your settings:

```bash
# Database Configuration
DB_HOST=db
DB_PORT=3306
DB_NAME=data_miw
DB_USER=miw_user
DB_PASS=your_secure_password
DB_ROOT_PASS=your_secure_root_password

# Email Configuration
SMTP_HOST=smtp.gmail.com
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
SMTP_PORT=587
SMTP_ENCRYPTION=tls

# Application Settings
APP_ENV=development
```

### Development Deployment

For local development with automatic code syncing:

```bash
# Linux/Mac:
./deploy.sh

# Windows:
deploy.bat
```

Access:
- MIW Application: http://localhost:8080
- PHPMyAdmin: http://localhost:8081 (user: miw_user, password: as set in .env)

### Production Deployment

For production environments:

```bash
# Linux/Mac:
./deploy.sh production

# Windows:
deploy.bat production
```

## Configuration Options

### Docker Compose Files

- `docker-compose.yml`: Development configuration with mounted volumes for live code editing
- `docker-compose.production.yml`: Production configuration with optimized settings and persistent volumes

### Directory Structure

Key directories that are volume-mounted in Docker:

- `/uploads`: For user-uploaded files
- `/documents`: For document storage
- `/logs`: For application logs

## Database Management

### Initial Database

The initial database is created from SQL files in the `backup_sql` directory.

### Database Backup

To backup the database:

```bash
docker-compose exec db sh -c 'mysqldump -u $MYSQL_USER -p$MYSQL_PASSWORD $MYSQL_DATABASE' > backup_$(date +%Y%m%d).sql
```

### Database Restore

To restore a database:

```bash
docker-compose exec -T db sh -c 'mysql -u $MYSQL_USER -p$MYSQL_PASSWORD $MYSQL_DATABASE' < your_backup_file.sql
```

## Cloud Deployment Options

This project can be deployed to various cloud platforms:

### Digital Ocean

Deploy with Digital Ocean App Platform:
1. Create a new app from GitHub
2. Select Docker as the deployment method
3. Set environment variables from your `.env` file
4. Configure database service

### AWS

Deploy with AWS ECS/Fargate:
1. Push Docker image to ECR
2. Create an ECS cluster and task definition
3. Configure RDS for database
4. Set up load balancer and service

### Google Cloud

Deploy with Google Cloud Run:
1. Push Docker image to Container Registry
2. Deploy to Cloud Run
3. Configure Cloud SQL for database
4. Set up proper environment variables

### Azure

Deploy with Azure App Service:
1. Push Docker image to Azure Container Registry
2. Deploy to App Service
3. Configure Azure Database for MySQL
4. Set up environment variables

## Troubleshooting

### Common Issues

1. **Database Connection Failures**:
   - Ensure database service is running: `docker-compose ps`
   - Check credentials in `.env` file
   - Verify network configuration

2. **Permission Issues**:
   - Ensure proper permissions on mounted volumes
   - Run: `docker-compose exec web chown -R www-data:www-data /var/www/html/uploads`

3. **Email Sending Problems**:
   - Verify SMTP credentials
   - Check if email service provider allows connections from Docker containers

### Getting Help

For more assistance, please open an issue on the project repository.
