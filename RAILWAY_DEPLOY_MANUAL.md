# MIW Railway Deployment Guide
## Project ID: 2725c7e0-071b-43ea-9be7-33142b967d77

🚀 **Your MIW project is ready for Railway deployment!**

## ✅ Step 1: Access Your Railway Project

1. Go to: https://railway.app/project/2725c7e0-071b-43ea-9be7-33142b967d77
2. Login with your GitHub account

## ✅ Step 2: Add MySQL Database Service

1. In your Railway dashboard, click **"+ New Service"**
2. Select **"Database"** → **"MySQL"**
3. Railway will create a MySQL database automatically
4. Note the database connection details (Railway provides these automatically)

## ✅ Step 3: Deploy Your Web Application

1. Click **"+ New Service"** again
2. Select **"GitHub Repo"**
3. Choose your **"MIW"** repository
4. Railway will detect the Dockerfile and start building

## ✅ Step 4: Configure Environment Variables

In your Railway web service, go to **"Variables"** tab and add:

```bash
# Application Settings
APP_ENV=production

# Database (Railway auto-fills these when you connect MySQL)
DB_HOST=${{MySQL.MYSQL_HOST}}
DB_PORT=${{MySQL.MYSQL_PORT}}
DB_NAME=${{MySQL.MYSQL_DATABASE}}
DB_USER=${{MySQL.MYSQL_USER}}
DB_PASS=${{MySQL.MYSQL_PASSWORD}}

# Email Configuration (UPDATE WITH YOUR SMTP DETAILS)
SMTP_HOST=smtp.gmail.com
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
SMTP_PORT=587
SMTP_ENCRYPTION=tls

# File Upload Settings
MAX_FILE_SIZE=10M
MAX_EXECUTION_TIME=300
```

## ✅ Step 5: Connect Database to Web Service

1. In your web service settings, go to **"Variables"**
2. Click **"Reference"** and select your MySQL service
3. This automatically connects the database

## ✅ Step 6: Deploy and Test

1. Railway will automatically build and deploy your Docker image
2. You'll get a URL like: `https://miw-production.up.railway.app`
3. Click the URL to access your live MIW application!

## 🎯 Expected Results

After deployment, you'll have:
- ✅ **Live MIW Application** at your Railway URL
- ✅ **MySQL Database** with all your data
- ✅ **HTTPS/SSL** automatically enabled
- ✅ **Professional domain** from Railway
- ✅ **Auto-scaling** and **99.9% uptime**

## 💰 Cost Estimate

- **Usage**: ~$2-3/month for normal traffic
- **Free Credit**: $5/month (covers your usage!)
- **Total Cost**: Effectively **FREE** for 2+ months

## 🔧 Troubleshooting

If deployment fails:
1. Check the **"Deployments"** tab for build logs
2. Ensure all environment variables are set
3. Verify your Dockerfile is working (it should be!)

## 🎉 Success!

Once deployed, your MIW Travel Management System will be:
- **Live on the internet** 🌐
- **Accessible from anywhere** 📱
- **Professional and scalable** 🚀

Your Railway project: https://railway.app/project/2725c7e0-071b-43ea-9be7-33142b967d77
