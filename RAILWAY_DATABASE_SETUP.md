# Railway Database Setup Guide

## 📊 Import Your MIW Database to Railway

After creating your MySQL service in Railway, follow these steps to import your data:

### Step 1: Get Railway Database Connection Info

In Railway dashboard:
1. Click on your MySQL service
2. Go to "Connect" tab
3. Copy the connection details:
   - Host: `[PROVIDED BY RAILWAY]`
   - Port: `[PROVIDED BY RAILWAY]`
   - Database: `[PROVIDED BY RAILWAY]`
   - Username: `[PROVIDED BY RAILWAY]`
   - Password: `[PROVIDED BY RAILWAY]`

### Step 2: Import Database Schema and Data

Use your latest backup file: `backup_sql/data_miw (27).sql`

**Option A: Using Railway Dashboard**
1. In MySQL service, go to "Data" tab
2. Click "Import" or "Query"
3. Upload `backup_sql/data_miw (27).sql`

**Option B: Using MySQL Client**
```bash
# Replace connection details with your Railway MySQL info
mysql -h [RAILWAY_HOST] -P [RAILWAY_PORT] -u [RAILWAY_USER] -p[RAILWAY_PASSWORD] [RAILWAY_DATABASE] < backup_sql/data_miw\ \(27\).sql
```

**Option C: Using phpMyAdmin (if available)**
1. Access Railway MySQL via phpMyAdmin
2. Go to "Import" tab
3. Choose file: `backup_sql/data_miw (27).sql`
4. Click "Go"

### Step 3: Verify Database Import

After import, check that these tables exist:
- `admin_users`
- `packages` 
- `registrations`
- `pembatalan`
- `manifest`
- `documents`

### Step 4: Test Database Connection

In your Railway web service logs, you should see:
- ✅ "Connected to database successfully"
- ❌ No database connection errors

### 🔧 Troubleshooting Database Issues

**If connection fails:**
1. Verify environment variables are set correctly
2. Check that MySQL service is running
3. Ensure web service can reference MySQL service
4. Look at deployment logs for specific errors

**If data is missing:**
1. Re-import the SQL file
2. Check file size (should be > 1KB)
3. Verify table creation in Railway MySQL dashboard

### 🎯 Expected Database Structure

Your Railway MySQL should contain:
- **Tables**: ~10-15 tables with MIW data
- **Data**: Travel packages, user registrations, etc.
- **Size**: Several MB depending on your data

Once database is properly imported and connected, your MIW application will be fully functional on Railway!
