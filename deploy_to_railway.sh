#!/bin/bash

# Railway Deployment Script for MIW Travel
# Project ID: 2725c7e0-071b-43ea-9be7-33142b967d77

echo "🚀 Deploying MIW to Railway..."
echo "Project ID: 2725c7e0-071b-43ea-9be7-33142b967d77"
echo

# Install Railway CLI if not present
if ! command -v railway &> /dev/null; then
    echo "📦 Installing Railway CLI..."
    npm install -g @railway/cli
fi

# Login to Railway
echo "🔐 Logging in to Railway..."
railway login

# Link to existing project
echo "🔗 Linking to Railway project..."
railway link 2725c7e0-071b-43ea-9be7-33142b967d77

# Deploy the application
echo "🚀 Deploying application..."
railway up

echo "✅ Deployment initiated!"
echo "📊 Check deployment status at: https://railway.app/project/2725c7e0-071b-43ea-9be7-33142b967d77"
