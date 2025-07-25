# Render.com Deployment Guide for MIW Travel System

## Why Render.com?
- ✅ **Completely FREE** for web services
- ✅ **Free PostgreSQL** database (10GB)
- ✅ **Docker Support** with automatic builds
- ✅ **SSL/HTTPS** included
- ✅ **Custom domains** supported
- ✅ **GitHub integration** with auto-deploy

## Deployment Steps

### Step 1: Database Migration (MySQL → PostgreSQL)
Since Render uses PostgreSQL, we need to migrate your data:

1. **Export current MySQL data**:
   ```bash
   # From your local Docker environment
   docker-compose exec db mysqldump -u miw_user -p data_miw > miw_mysql_backup.sql
   ```

2. **Convert MySQL to PostgreSQL** (I'll provide a conversion script)

### Step 2: Create Render Account
1. Go to [render.com](https://render.com)
2. Sign up with GitHub
3. Connect your repository

### Step 3: Create PostgreSQL Database
1. In Render dashboard: "New" → "PostgreSQL"
2. Choose free tier
3. Note the connection details

### Step 4: Create Web Service
1. "New" → "Web Service"
2. Connect GitHub repository
3. Configure:
   - **Environment**: Docker
   - **Build Command**: (auto-detected)
   - **Start Command**: (auto-detected from Dockerfile)

### Step 5: Environment Variables
Add these in Render dashboard:

```bash
# Database (from Render PostgreSQL service)
DB_HOST=your-postgres-host
DB_PORT=5432
DB_NAME=your-database-name
DB_USER=your-username
DB_PASS=your-password

# Application
APP_ENV=production

# Email
SMTP_HOST=smtp.gmail.com
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
SMTP_PORT=587
SMTP_ENCRYPTION=tls
```

## Cost: $0/month
- Web Service: FREE
- PostgreSQL: FREE (10GB)
- SSL: FREE
- Custom domain: FREE

## Pros vs Railway
- **Render**: Completely free, PostgreSQL
- **Railway**: MySQL support, $5 credit (easier migration)

Would you like me to:
1. Create PostgreSQL migration scripts for Render?
2. Or proceed with Railway (easier, MySQL compatible)?
