# 🚀 MIW Render Deployment Guide

## Why Render?
✅ **Free tier includes web services** (unlike Railway)  
✅ **Managed PostgreSQL database** (free tier)  
✅ **Automatic HTTPS/SSL**  
✅ **GitHub integration**  
✅ **Docker support**  
✅ **No credit card required**  

---

## 📋 Prerequisites
- [x] GitHub repository: `takaruma7/MIW`
- [x] Docker configuration ready
- [x] Environment variables configured
- [x] Database initialization script ready

---

## 🔧 Step 1: Sign Up for Render

1. Go to **https://render.com**
2. Click **"Get Started for Free"**
3. Sign up with **GitHub account**
4. Authorize Render to access your repositories

---

## 🗄️ Step 2: Create Database First

1. In Render dashboard, click **"New +"**
2. Select **"PostgreSQL"**
3. Fill in details:
   ```
   Name: miw-database
   Database: data_miw
   User: miw_user
   Region: Singapore (or closest to your users)
   Plan: Free
   ```
4. Click **"Create Database"**
5. **Save the connection details** that appear

---

## 🌐 Step 3: Deploy Web Service

1. Click **"New +"** again
2. Select **"Web Service"**
3. Connect your GitHub repository:
   ```
   Repository: takaruma7/MIW
   Branch: main
   ```
4. Configure service:
   ```
   Name: miw-web
   Region: Singapore
   Branch: main
   Build Command: (leave empty - Docker will handle)
   Start Command: (leave empty - Docker will handle)
   Plan: Free
   ```

---

## ⚙️ Step 4: Configure Environment Variables

In your web service settings, go to **"Environment"** tab and add:

```bash
# Application
APP_ENV=production

# Database (replace with your actual database values from Step 2)
DB_HOST=your-database-hostname
DB_PORT=5432
DB_NAME=data_miw
DB_USER=miw_user
DB_PASS=your-database-password

# Email Settings
SMTP_HOST=smtp.gmail.com
SMTP_USERNAME=drakestates@gmail.com
SMTP_PASSWORD=lqqj vnug vrau dkfa
SMTP_PORT=587
SMTP_ENCRYPTION=tls

# File Upload
MAX_FILE_SIZE=10M
MAX_EXECUTION_TIME=300
SECURE_HEADERS=true
```

---

## 🔄 Step 5: Modify for PostgreSQL

Since Render's free database is PostgreSQL (not MySQL), we need to update the database configuration:

### Update config.php for PostgreSQL:
```php
// Database connection for PostgreSQL
$dsn = "pgsql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_NAME']}";
$pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS']);
```

---

## 🚀 Step 6: Deploy and Test

1. **Trigger deployment** by pushing to GitHub
2. **Monitor build logs** in Render dashboard
3. **Wait for deployment** to complete (5-10 minutes)
4. **Test your application** at the provided URL
5. **Initialize database** by visiting `/init_database.php`

---

## 📊 Expected Results

After successful deployment:
- ✅ Live MIW application at Render URL (e.g., `https://miw-web.onrender.com`)
- ✅ Automatic HTTPS/SSL certificate
- ✅ PostgreSQL database connected
- ✅ Email notifications working
- ✅ File uploads functional
- ✅ Ready for customer registration!

---

## 🔧 PostgreSQL Migration Commands

To convert MySQL tables to PostgreSQL format:

```sql
-- Example conversion from MySQL to PostgreSQL
-- MySQL: AUTO_INCREMENT -> PostgreSQL: SERIAL
-- MySQL: TINYINT -> PostgreSQL: SMALLINT
-- MySQL: DATETIME -> PostgreSQL: TIMESTAMP
```

---

## 🐛 Troubleshooting

### Build Fails
- Check Dockerfile syntax
- Verify all files are committed to GitHub
- Check build logs in Render dashboard

### Database Connection Issues
- Verify environment variables are set correctly
- Check database credentials in Render dashboard
- Ensure database is running

### Application Errors
- Check application logs in Render dashboard
- Verify file permissions
- Test database initialization script

---

## 🎯 Alternative: Quick Deploy Button

Add this to your GitHub README.md for one-click deployment:

```markdown
[![Deploy to Render](https://render.com/images/deploy-to-render-button.svg)](https://render.com/deploy?repo=https://github.com/takaruma7/MIW)
```

---

## 📞 Support

If you encounter issues:
1. Check Render's documentation: https://render.com/docs
2. Review build logs in Render dashboard
3. Test locally with Docker first
4. Check environment variables

---

**🎉 Your MIW application will be live at a Render URL within 10 minutes!**
