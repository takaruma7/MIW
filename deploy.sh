#!/bin/bash
set -e

# Display ASCII art banner
echo "====================================================="
echo "  MIW Travel - Docker Deployment Script"
echo "====================================================="
echo ""

# Check if docker is installed
if ! command -v docker &> /dev/null; then
    echo "❌ Docker is not installed. Please install Docker first."
    exit 1
fi

# Check if docker-compose is installed
if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi

# Parse arguments
ENVIRONMENT="development"
if [ "$1" == "production" ]; then
    ENVIRONMENT="production"
fi

echo "🚀 Starting deployment in $ENVIRONMENT mode..."

# Check for environment file
ENV_FILE=".env"
if [ ! -f "$ENV_FILE" ]; then
    echo "⚠️ No .env file found. Creating from example..."
    cp .env.example .env
    echo "Please update your .env file with proper credentials."
    echo "Then run this script again."
    exit 1
fi

# Choose the right docker-compose file
COMPOSE_FILE="docker-compose.yml"
if [ "$ENVIRONMENT" == "production" ]; then
    COMPOSE_FILE="docker-compose.production.yml"
fi

# Pull the latest images
echo "📥 Pulling latest Docker images..."
docker-compose -f $COMPOSE_FILE pull

# Build the containers
echo "🔨 Building containers..."
docker-compose -f $COMPOSE_FILE build

# Start the containers
echo "▶️ Starting services..."
docker-compose -f $COMPOSE_FILE up -d

# Display status
echo "✅ Deployment completed successfully!"
echo ""
if [ "$ENVIRONMENT" == "development" ]; then
    echo "📊 PHPMyAdmin: http://localhost:8081"
    echo "🌐 MIW Application: http://localhost:8080"
else
    echo "🌐 MIW Application is now running in production mode!"
fi

echo ""
echo "====================================================="
