# Railway Deployment Guide for MIW Travel System

This guide will deploy your MIW project to Railway.app for **FREE**.

## Why Railway?
- ✅ **$5 monthly credit** (more than enough for small-medium apps)
- ✅ **Native Docker support** 
- ✅ **MySQL database included**
- ✅ **Automatic HTTPS**
- ✅ **Custom domains**
- ✅ **GitHub integration**

## Pre-Deployment Steps

### 1. Prepare Production Environment
Your project is already Docker-ready! We just need to configure for Railway.

### 2. Database Configuration
Railway will provide MySQL database automatically.

## Deployment Steps

### Step 1: Create Railway Account
1. Go to [railway.app](https://railway.app)
2. Sign up with GitHub account
3. Connect your GitHub repository

### Step 2: Deploy Application
1. Click "Deploy from GitHub repo"
2. Select your `MIW` repository
3. Railway will detect Docker and build automatically

### Step 3: Add Database
1. In Railway dashboard, click "Add Service"
2. Select "MySQL"
3. Railway will create database and provide connection details

### Step 4: Configure Environment Variables
In Railway dashboard, add these environment variables:

```bash
# Database (Railway will auto-populate these)
DB_HOST=${MYSQL_HOST}
DB_PORT=${MYSQL_PORT}
DB_NAME=${MYSQL_DATABASE}
DB_USER=${MYSQL_USER}
DB_PASS=${MYSQL_PASSWORD}

# Application
APP_ENV=production

# Email Configuration (Update with your SMTP details)
SMTP_HOST=smtp.gmail.com
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
SMTP_PORT=587
SMTP_ENCRYPTION=tls
```

### Step 5: Access Your App
Railway will provide a URL like: `https://miw-production.up.railway.app`

## Cost Analysis
- **Free Tier**: $5 monthly credit
- **Typical Usage**: $2-3/month for small-medium traffic
- **Database**: Included in usage
- **SSL**: Free
- **Custom Domain**: Free

## Alternative: Render.com

If you prefer Render.com:
1. Similar process but with PostgreSQL instead of MySQL
2. Requires database migration from MySQL to PostgreSQL
3. Also free with good limits

## Next Steps
1. Would you like me to create the Railway configuration files?
2. Or prefer to try Render.com with PostgreSQL?
3. Or explore other options like Fly.io?

Choose your preferred option and I'll implement the complete deployment!
