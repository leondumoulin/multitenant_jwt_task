#!/bin/bash

# Multi-tenant JWT Application - Unit Tests Runner
# This script runs all unit tests for the application

echo "🚀 Starting Unit Tests for Multi-tenant JWT Application"
echo "=================================================="

# Check if PHPUnit is available
if ! command -v ./vendor/bin/phpunit &> /dev/null; then
    echo "❌ PHPUnit not found. Please run 'composer install' first."
    exit 1
fi

# Set test environment
export APP_ENV=testing
export DB_CONNECTION=sqlite
export DB_DATABASE=:memory:

echo "📋 Running Unit Tests..."
echo ""

# Run all unit tests
./vendor/bin/phpunit tests/Unit --testdox --colors=always

# Check if tests passed
if [ $? -eq 0 ]; then
    echo ""
    echo "✅ All unit tests passed successfully!"
    echo ""
    echo "📊 Test Coverage Summary:"
    echo "  - JwtService: Token generation, validation, and extraction"
    echo "  - TenantService: Tenant creation, suspension, and activation"
    echo "  - DatabaseManager: Database operations and connection management"
    echo "  - Models: Tenant and User model functionality"
    echo "  - Guards: JWT authentication guards for admin and tenant"
    echo "  - Controllers: Authentication and tenant management endpoints"
    echo ""
    echo "🎉 Unit testing completed successfully!"
else
    echo ""
    echo "❌ Some tests failed. Please check the output above for details."
    exit 1
fi
