# MIW Travel - Deployment Configuration

## Heroku Deployment (Primary)

### Quick Deploy
```bash
# Create app
heroku create your-app-name

# Add PostgreSQL
heroku addons:create heroku-postgresql:essential-0

# Set environment variables
heroku config:set APP_ENV=production
heroku config:set MAX_FILE_SIZE=10M
heroku config:set MAX_EXECUTION_TIME=300
heroku config:set SECURE_HEADERS=true

# SMTP Settings
heroku config:set SMTP_HOST=smtp.gmail.com
heroku config:set SMTP_PORT=587
heroku config:set SMTP_USERNAME=your-email@gmail.com
heroku config:set SMTP_PASSWORD=your-app-password
heroku config:set SMTP_ENCRYPTION=tls

# Deploy
git push heroku main

# Initialize database
heroku run php comprehensive_fix.php
```

### Environment Variables Required
- `APP_ENV=production`
- `DATABASE_URL` (auto-set by Heroku PostgreSQL)
- `MAX_FILE_SIZE=10M`
- `MAX_EXECUTION_TIME=300`
- `SECURE_HEADERS=true`
- `SMTP_HOST=smtp.gmail.com`
- `SMTP_PORT=587`
- `SMTP_USERNAME=your-email@gmail.com`
- `SMTP_PASSWORD=your-app-password`
- `SMTP_ENCRYPTION=tls`

## Alternative Platforms

### Render
```yaml
# render.yaml
services:
  - type: web
    name: miw-travel-app
    env: php
    buildCommand: composer install
    startCommand: vendor/bin/heroku-php-apache2 public/
    envVars:
      - key: APP_ENV
        value: production
```

### Railway
```json
{
  "build": {
    "builder": "herokuish"
  },
  "deploy": {
    "startCommand": "vendor/bin/heroku-php-apache2 public/"
  }
}
```

### Local Development
```bash
# Setup
composer install
# Create MySQL database 'data_miw'
# Import backup_sql/data_miw (latest).sql
php -S localhost:8000
```

## File Structure for Deployment
```
MIW/
├── composer.json       # Dependencies
├── Procfile           # Heroku process definition
├── config.php         # Unified configuration
├── comprehensive_fix.php  # Database initialization
└── [application files...]
```

## Notes
- Uses unified `config.php` with automatic environment detection
- PostgreSQL for production, MySQL for local development
- Ephemeral file storage on Heroku (use cloud storage for production)
- SMTP email via Gmail (requires app password)
