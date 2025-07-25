# Cloud Hosting Implementation Guide for MIW Project

This guide provides step-by-step instructions for implementing Docker-based cloud hosting for the MIW Travel Management System. Follow these steps to deploy your application on various cloud platforms.

## Preparation Steps

### 1. Understanding Project Structure

Before deployment, ensure you understand the key components of your MIW project:

- **PHP Backend**: Admin dashboards, user registration, document management
- **MySQL Database**: Stores travel packages, user data, bookings, etc.
- **Document Storage**: Uploads for passports, identification, and other travel documents
- **Email System**: Notifications for registrations and payment confirmations

### 2. Preparing for Dockerization

The project is already partially dockerized with:
- `Dockerfile`: Sets up PHP and Apache environment
- `docker-compose.yml`: Now configured for local development
- `docker-compose.production.yml`: Optimized for production environments

## Local Development Deployment

### 1. Configure Environment Variables

```bash
# Copy example environment file
cp .env.example .env

# Edit with your specific settings
# Especially update SMTP and database credentials
```

### 2. Start Development Environment

```bash
# On Windows
deploy.bat

# On Linux/Mac
./deploy.sh
```

This will:
- Build the Docker images
- Start containers for web, database, and PHPMyAdmin
- Mount your code directories for live editing

### 3. Access Development Environment

- **MIW Application**: http://localhost:8080
- **PHPMyAdmin**: http://localhost:8081

## Production Deployment

### 1. Choose a Cloud Provider

Based on your requirements, any of these providers would work well:

#### Digital Ocean (Recommended for Small-Medium Projects)
- Simple setup with App Platform or Droplets
- Managed MySQL available
- Affordable pricing
- Good performance for Southeast Asian region

#### AWS (Recommended for Enterprise-Scale)
- Use ECS/Fargate for containerized deployment
- RDS for managed MySQL
- S3 for document storage
- Extensive scaling options

#### Google Cloud
- Cloud Run for stateless container hosting
- Cloud SQL for MySQL
- Flexible configuration options

#### Azure
- App Service for container hosting
- Azure Database for MySQL

### 2. Deployment Steps (Digital Ocean Example)

1. **Prepare Application**:
   ```bash
   # Set production environment variables
   cp .env.example .env.production
   # Edit .env.production with production values
   ```

2. **Initialize Repository** (if not already done):
   ```bash
   git init
   git add .
   git commit -m "Initial commit"
   ```

3. **Create Digital Ocean App**:
   - Log into Digital Ocean
   - Create new App from GitHub repository
   - Select "Docker" as deployment method
   - Configure environment variables from .env.production
   - Add managed database component

4. **Deploy**:
   - Click "Deploy to Production"
   - Digital Ocean will build and deploy your containers

5. **Configure Domain** (optional):
   - Add your domain in the Digital Ocean dashboard
   - Update DNS settings with your registrar

### 3. Monitoring and Maintenance

After deployment:
- Monitor application logs
- Set up database backups
- Configure alerting for errors

## Database Migration

### Migrating Local Data to Cloud

1. **Export Local Database**:
   ```bash
   # From your local environment
   docker-compose exec db sh -c 'mysqldump -u root -proot_password data_miw' > migration_backup.sql
   ```

2. **Import to Cloud Database**:
   ```bash
   # Using database credentials from your cloud provider
   mysql -h your-db-host.com -u username -p database_name < migration_backup.sql
   ```

## Best Practices for Production

1. **Security**:
   - Use SSL/TLS certificates
   - Implement proper firewall rules
   - Regular security updates
   - Sanitize all user inputs (already implemented in most files)

2. **Scalability**:
   - Configure auto-scaling if available
   - Optimize database queries
   - Implement caching if needed

3. **Reliability**:
   - Set up automated backups
   - Monitor application health
   - Implement error logging and alerting

## Testing Post-Deployment

After deploying to cloud:
1. Test user registration flow
2. Verify document upload functionality
3. Check email notifications
4. Test admin dashboard features
5. Verify database connections and queries

## Need Help?

For additional assistance with your Docker-based cloud deployment, refer to:
- The Docker documentation: https://docs.docker.com
- Your cloud provider's specific container service documentation
