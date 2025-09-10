#!/bin/bash

# Tenant Isolation Test Runner
# This script runs comprehensive tests to prove strict tenant isolation

echo "🔒 Running Tenant Isolation Tests"
echo "================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    if [ $2 -eq 0 ]; then
        echo -e "${GREEN}✅ $1${NC}"
    else
        echo -e "${RED}❌ $1${NC}"
    fi
}

print_header() {
    echo -e "${BLUE}📋 $1${NC}"
    echo "----------------------------------------"
}

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo -e "${RED}❌ Error: Please run this script from the Laravel project root directory${NC}"
    exit 1
fi

# Check if PHP is available
if ! command -v php &> /dev/null; then
    echo -e "${RED}❌ Error: PHP is not installed or not in PATH${NC}"
    exit 1
fi

# Check if Composer is available
if ! command -v composer &> /dev/null; then
    echo -e "${RED}❌ Error: Composer is not installed or not in PATH${NC}"
    exit 1
fi

print_header "Setting up test environment"

# Install dependencies if needed
echo "📦 Checking dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader
print_status "Dependencies installed" $?

# Clear caches
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
print_status "Caches cleared" $?

# Run migrations
echo "🗄️  Running migrations..."
php artisan migrate --force
print_status "Migrations completed" $?

print_header "Running Tenant Isolation Tests"

# Test categories
declare -a test_categories=(
    "Unit/Isolation/TenantIsolationTest"
    "Unit/Isolation/JwtIsolationTest"
    "Unit/Isolation/PermissionIsolationTest"
    "Unit/Isolation/AuditIsolationTest"
    "Feature/Tenant/TenantApiIsolationTest"
    "Feature/Admin/AdminApiIsolationTest"
)

total_tests=0
passed_tests=0
failed_tests=0

# Run each test category
for test_category in "${test_categories[@]}"; do
    echo ""
    print_header "Running $test_category"

    # Run the test
    php artisan test "tests/$test_category.php" --verbose
    test_result=$?

    if [ $test_result -eq 0 ]; then
        print_status "$test_category - PASSED" 0
        ((passed_tests++))
    else
        print_status "$test_category - FAILED" 1
        ((failed_tests++))
    fi

    ((total_tests++))
done

print_header "Test Results Summary"

echo -e "${BLUE}Total Test Categories: $total_tests${NC}"
echo -e "${GREEN}Passed: $passed_tests${NC}"
echo -e "${RED}Failed: $failed_tests${NC}"

if [ $failed_tests -eq 0 ]; then
    echo ""
    echo -e "${GREEN}🎉 All tenant isolation tests passed!${NC}"
    echo -e "${GREEN}✅ Strict tenant isolation is verified${NC}"
    echo ""
    echo -e "${BLUE}Key Isolation Features Verified:${NC}"
    echo "  • Database isolation between tenants"
    echo "  • JWT token isolation"
    echo "  • Permission and role isolation"
    echo "  • Audit log isolation"
    echo "  • API endpoint isolation"
    echo "  • Admin-tenant separation"
    echo ""
    exit 0
else
    echo ""
    echo -e "${RED}❌ Some tests failed. Tenant isolation may not be properly implemented.${NC}"
    echo -e "${YELLOW}Please review the failed tests and fix any issues.${NC}"
    echo ""
    exit 1
fi
