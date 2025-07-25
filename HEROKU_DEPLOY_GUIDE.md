# 🚀 MIW Heroku Deployment Guide

## 🎯 **Why Heroku is Perfect for MIW:**
- ✅ **FREE PostgreSQL database** included
- ✅ **Automatic HTTPS/SSL**
- ✅ **Git-based deployment** (like Railway)
- ✅ **Zero server management**
- ✅ **Auto-scaling capabilities**
- ✅ **Add-ons ecosystem**

---

## 📋 **Prerequisites**
- [x] GitHub repository: `takaruma7/MIW`
- [x] Heroku account (free)
- [x] Heroku CLI installed
- [x] Git configured

---

## 🔧 **Step 1: Install Heroku CLI**

### **Windows (PowerShell):**
```powershell
# Download and install from: https://devcenter.heroku.com/articles/heroku-cli
# Or use Chocolatey:
choco install heroku-cli
```

### **Verify Installation:**
```bash
heroku --version
```

---

## 🚀 **Step 2: Deploy to Heroku**

### **Login to Heroku:**
```bash
heroku login
```

### **Create Heroku App:**
```bash
# Create app with custom name
heroku create miw-travel-app

# Or let Heroku generate random name
heroku create
```

### **Add PostgreSQL Database:**
```bash
heroku addons:create heroku-postgresql:mini
```

### **Set Environment Variables:**
```bash
heroku config:set APP_ENV=production
heroku config:set SMTP_HOST=smtp.gmail.com
heroku config:set SMTP_USERNAME=drakestates@gmail.com
heroku config:set SMTP_PASSWORD="lqqj vnug vrau dkfa"
heroku config:set SMTP_PORT=587
heroku config:set SMTP_ENCRYPTION=tls
heroku config:set MAX_FILE_SIZE=10M
heroku config:set MAX_EXECUTION_TIME=300
heroku config:set SECURE_HEADERS=true
```

### **Deploy Application:**
```bash
git add .
git commit -m "Deploy MIW to Heroku"
git push heroku main
```

---

## 🗄️ **Step 3: Initialize Database**

### **Get Your App URL:**
```bash
heroku open
```

### **Initialize Database:**
Visit: `https://your-app-name.herokuapp.com/init_database_universal.php`

This will automatically:
- ✅ Create all required tables
- ✅ Insert sample data
- ✅ Configure database schema

---

## ⚙️ **Step 4: Configure Environment Variables**

### **View Current Config:**
```bash
heroku config
```

### **Add Additional Variables:**
```bash
# Optional: Custom domain later
heroku config:set CUSTOM_DOMAIN=your-domain.com

# Optional: Debug mode (development only)
heroku config:set DEBUG_MODE=false
```

---

## 📊 **Step 5: Monitor Your Application**

### **View Logs:**
```bash
heroku logs --tail
```

### **Check App Status:**
```bash
heroku ps
```

### **Open Application:**
```bash
heroku open
```

---

## 🔄 **Automated Deployment Script**

I've created `deploy_heroku.bat` for you:

```batch
@echo off
echo Deploying MIW to Heroku...
heroku create miw-travel-app
heroku addons:create heroku-postgresql:mini
heroku config:set APP_ENV=production
heroku config:set SMTP_HOST=smtp.gmail.com
heroku config:set SMTP_USERNAME=drakestates@gmail.com
heroku config:set SMTP_PASSWORD="lqqj vnug vrau dkfa"
heroku config:set SMTP_PORT=587
heroku config:set SMTP_ENCRYPTION=tls
heroku config:set MAX_FILE_SIZE=10M
heroku config:set MAX_EXECUTION_TIME=300
heroku config:set SECURE_HEADERS=true
git push heroku main
heroku open
```

---

## 📁 **Files Ready for Heroku:**
- ✅ `Procfile` - Web server configuration
- ✅ `composer.json` - PHP dependencies
- ✅ `config.heroku.php` - Heroku-specific config
- ✅ `init_database_universal.php` - Database initialization
- ✅ All application files committed to Git

---

## 🎯 **Expected Results:**

After successful deployment:
- 🌐 **Live URL:** `https://your-app-name.herokuapp.com`
- 🔒 **Automatic HTTPS/SSL**
- 🗄️ **PostgreSQL database connected**
- 📧 **Email notifications working**
- 📄 **Document uploads functional**
- 🎉 **Ready for customer registration!**

---

## 🐛 **Troubleshooting**

### **Build Fails:**
```bash
# Check build logs
heroku logs --tail

# Restart app
heroku restart
```

### **Database Issues:**
```bash
# Check database connection
heroku pg:info

# Access database console
heroku pg:psql
```

### **App Won't Start:**
```bash
# Check dyno status
heroku ps

# Scale dynos
heroku ps:scale web=1
```

### **Environment Variables:**
```bash
# List all config vars
heroku config

# Set individual variable
heroku config:set VARIABLE_NAME=value
```

---

## 💰 **Heroku Pricing (Current)**

### **Free Tier:**
- ✅ 550-1000 dyno hours/month
- ✅ Apps sleep after 30min inactivity
- ✅ Free PostgreSQL (10,000 rows)
- ✅ Custom domain support

### **Paid Plans (Optional):**
- 💰 **Basic:** $7/month - No sleeping
- 💰 **Standard:** $25/month - Better performance
- 💰 **Performance:** $250/month - High performance

---

## 🔄 **Continuous Deployment**

Once set up, every push to GitHub `main` branch will:
1. Automatically deploy to Heroku
2. Run build process
3. Update live application
4. Send deployment notifications

---

## 🎉 **One-Click Deploy**

Add this badge to your GitHub README.md:

```markdown
[![Deploy](https://www.herokucdn.com/deploy/button.svg)](https://heroku.com/deploy?template=https://github.com/takaruma7/MIW)
```

---

## 📞 **Support Resources**

- 📖 **Heroku Dev Center:** https://devcenter.heroku.com/
- 💬 **Heroku Support:** Available in dashboard
- 🔧 **PHP Buildpack:** https://github.com/heroku/heroku-buildpack-php
- 🗄️ **PostgreSQL:** https://devcenter.heroku.com/articles/heroku-postgresql

---

**🚀 Your MIW application will be live at Heroku within 10 minutes!**

**Next:** Run `deploy_heroku.bat` or follow the manual steps above.
