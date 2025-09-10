# 🏢 Multi-Tenant SaaS CRM with Laravel & JWT

A comprehensive **Multi-Tenant SaaS CRM Platform** built with Laravel, featuring strict tenant isolation, custom JWT authentication, role-based permissions, audit logging, and complete tenant management capabilities.

## 🎯 Features

- **Multi-Tenancy**: Each tenant has a completely isolated database
- **Custom JWT Guards**: Separate authentication for admin and tenant users
- **Role-Based Permissions**: Fine-grained access control with Spatie/Permission
- **Audit Logging**: Complete activity tracking for compliance
- **Queue-Based Tenant Creation**: Asynchronous tenant provisioning
- **Tenant Management**: Full CRUD operations for tenant lifecycle
- **CRM Functionality**: Contacts, Deals, Activities, and Reports
- **Strict Isolation**: Complete data separation between tenants
- **Comprehensive Testing**: 50+ isolation tests proving tenant security

## 🚀 Quick Start

### Prerequisites

- PHP 8.1 or higher
- MySQL 5.7 or higher
- Composer
- Node.js & NPM (for frontend assets)

### Installation

1. **Clone the repository**
```bash
git clone <repository-url>
cd multitenant_jwt_task
```

2. **Install dependencies**
```bash
composer install
npm install
```

3. **Environment setup**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Database configuration**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=multitenant_crm
DB_USERNAME=root
DB_PASSWORD=your_password

# JWT Configuration
JWT_SECRET=your-jwt-secret-key-here
JWT_ALGO=HS256
JWT_TTL=60

# Queue Configuration (for tenant creation)
QUEUE_CONNECTION=database
```

5. **Run migrations and seeders**
```bash
php artisan migrate
php artisan db:seed
```

seed will create two tenant

6. **Start the server**
```bash
php artisan serve
```

The application will be available at `http://localhost:8000`

## 📋 Setup Instructions

### 1. How to Run Migrations

#### Main Database Migrations
```bash
# Run all main database migrations
php artisan migrate

# This creates the following tables:
# - admins (system administrators)
# - tenants (tenant information)
# - jobs (queue jobs)
# - notifications (system notifications)
# - failed_jobs (failed queue jobs)
# - Spatie permission tables (roles, permissions, etc.)
# - audit_logs (system audit logs)
```

#### Fresh Migration (Reset Everything)
```bash
# Drop all tables and re-run migrations
php artisan migrate:fresh

# Drop all tables, re-run migrations, and seed
php artisan migrate:fresh --seed
```

#### Tenant Database Migrations
```bash
# Tenant migrations are automatically run when creating tenants
# They create tenant-specific tables:
# - users (tenant users)
# - contacts (CRM contacts)
# - deals (CRM deals)
# - activities (CRM activities)
# - tenant_roles (tenant-specific roles)
# - tenant_permissions (tenant-specific permissions)
# - tenant_role_has_permissions (role-permission relationships)
# - tenant_model_has_roles (user-role relationships)
# - tenant_model_has_permissions (user-permission relationships)
# - audit_logs (tenant-specific audit logs)
```

### 2. How to Create Tenants

#### Method 1: Using API (Recommended)
```bash
# Step 1: Get admin token
curl -X POST http://localhost:8000/api/admin/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@system.com",
    "password": "password123"
  }'

# Step 2: Create new tenant (asynchronous via queue)
curl -X POST http://localhost:8000/api/admin/tenants \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "New Company",
    "admin_name": "John Doe",
    "admin_email": "admin@newcompany.com",
    "admin_password": "password123",
    "admin_password_confirmation": "password123"
  }'

# Step 3: Check tenant creation status
curl -X GET http://localhost:8000/api/admin/tenants/{tenant_id}/status \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

#### Method 2: Using Artisan Command
```bash
# Create tenant synchronously (for testing)
php artisan tinker

# In tinker console:
$tenantService = app(\App\Services\TenantService::class);
$tenant = $tenantService->createTenantSync([
    'name' => 'Test Company',
    'admin_name' => 'Test Admin',
    'admin_email' => 'admin@testcompany.com',
    'admin_password' => 'password123',
    'admin_password_confirmation' => 'password123'
]);
```

#### Method 3: Using Seeder
```bash
# Create sample tenants (ACME and Globex)
php artisan db:seed --class=SampleTenantsSeeder
```

#### Tenant Creation Process
1. **Tenant Record**: Creates tenant record in main database
2. **Queue Job**: Dispatches `CreateTenantJob` for asynchronous processing
3. **Database Creation**: Creates separate database for tenant
4. **Migrations**: Runs tenant-specific migrations
5. **Seeding**: Seeds roles, permissions, and default admin user
6. **Status Update**: Updates tenant status to 'active'

### 3. How to Generate & Use JWT Tokens

#### Admin JWT Token Generation
```bash
# Login as system admin
curl -X POST http://localhost:8000/api/admin/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@system.com",
    "password": "password123"
  }'

# Response includes JWT token:
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "admin": {
      "id": 1,
      "name": "System Administrator",
      "email": "admin@system.com"
    }
  }
}
```

#### Tenant User JWT Token Generation
```bash
# Login as tenant user
curl -X POST http://localhost:8000/api/tenant/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@acme.com",
    "password": "password123"
  }'

# Response includes JWT token:
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "user": {
      "id": 1,
      "name": "John Smith",
      "email": "admin@acme.com"
    }
  }
}
```

#### Using JWT Tokens in API Calls

##### Admin API Calls
```bash
# List all tenants
curl -X GET http://localhost:8000/api/admin/tenants \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"

# Create new tenant
curl -X POST http://localhost:8000/api/admin/tenants \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{...}'

# Get tenant details
curl -X GET http://localhost:8000/api/admin/tenants/{id} \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

##### Tenant API Calls
```bash
# List contacts
curl -X GET http://localhost:8000/api/tenant/contacts \
  -H "Authorization: Bearer YOUR_TENANT_TOKEN"

# Create contact
curl -X POST http://localhost:8000/api/tenant/contacts \
  -H "Authorization: Bearer YOUR_TENANT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "New Contact",
    "email": "contact@example.com",
    "phone": "+1-555-0123"
  }'

# List deals
curl -X GET http://localhost:8000/api/tenant/deals \
  -H "Authorization: Bearer YOUR_TENANT_TOKEN"
```

#### Token Management
```bash
# Refresh token (get new token)
curl -X POST http://localhost:8000/api/tenant/refresh \
  -H "Authorization: Bearer YOUR_TENANT_TOKEN"

# Logout (invalidate token)
curl -X POST http://localhost:8000/api/tenant/logout \
  -H "Authorization: Bearer YOUR_TENANT_TOKEN"

# Get current user info
curl -X GET http://localhost:8000/api/tenant/me \
  -H "Authorization: Bearer YOUR_TENANT_TOKEN"
```

### 4. How to Run Seeders

#### Run All Seeders
```bash
# Run all seeders (AdminSeeder + SampleTenantsSeeder)
php artisan db:seed
```

#### Run Specific Seeders
```bash
# Create system admin only
php artisan db:seed --class=AdminSeeder

# Create sample tenants (ACME and Globex)
php artisan db:seed --class=SampleTenantsSeeder

# Create tenant roles and permissions
php artisan db:seed --class=TenantRolesAndPermissionsSeeder
```

#### Sample Data Created by Seeders

##### System Admin (AdminSeeder)
- **Email**: `admin@system.com`
- **Password**: `password123`
- **Role**: System Administrator

##### Sample Tenants (SampleTenantsSeeder)
- **ACME Corporation**
  - Database: `tenant_acme`
  - Admin: `acme@admin.com` / `password123`
  
- **Globex Corporation**
  - Database: `tenant_globex`
  - Admin: `globex@admin.com` / `password123`

#### Fresh Database with All Seeders
```bash
# Drop all tables, re-run migrations, and seed everything
php artisan migrate:fresh --seed
```

#### Queue Processing for Tenant Creation
```bash
# Start queue worker to process tenant creation jobs
php artisan queue:work

# In another terminal, run the seeder
php artisan db:seed --class=SampleTenantsSeeder

# Monitor queue status
php artisan queue:monitor
```

## 🗄️ Database Setup

### Running Migrations

The system uses multiple migration sets:

#### Main Database Migrations
```bash
# Run all main database migrations
php artisan migrate

# This creates:
# - admins table
# - tenants table
# - jobs table (for queues)
# - notifications table
# - failed_jobs table
# - Spatie permission tables
# - Audit logs table
```

#### Tenant Database Migrations
```bash
# Tenant migrations are automatically run when creating tenants
# They include:
# - users table
# - contacts table
# - deals table
# - activities table
# - tenant_roles table
# - tenant_permissions table
# - tenant_role_has_permissions table
# - tenant_model_has_roles table
# - tenant_model_has_permissions table
# - audit_logs table (tenant-specific)
```

### Migration Commands

```bash
# Fresh migration with seeders
php artisan migrate:fresh --seed

# Run specific seeder
php artisan db:seed --class=SampleTenantsSeeder

# Reset and re-run all migrations
php artisan migrate:reset
php artisan migrate
```

## 🏢 Tenant Management

### Creating Tenants

#### Method 1: Using API (Recommended)
```bash
# Get admin token first
curl -X POST http://localhost:8000/api/admin/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@system.com",
    "password": "password123"
  }'

# Create new tenant (asynchronous)
curl -X POST http://localhost:8000/api/admin/tenants \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "New Company",
    "admin_name": "John Doe",
    "admin_email": "admin@newcompany.com",
    "admin_password": "password123",
    "admin_password_confirmation": "password123"
  }'

# Check tenant creation status
curl -X GET http://localhost:8000/api/admin/tenants/{tenant_id}/status \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

#### Method 2: Using Artisan Command
```bash
# Create tenant synchronously (for testing)
php artisan tinker

# In tinker:
$tenantService = app(\App\Services\TenantService::class);
$tenant = $tenantService->createTenantSync([
    'name' => 'Test Company',
    'admin_name' => 'Test Admin',
    'admin_email' => 'admin@testcompany.com',
    'admin_password' => 'password123',
    'admin_password_confirmation' => 'password123'
]);
```

#### Method 3: Using Seeder
```bash
# Run the sample tenants seeder
php artisan db:seed --class=SampleTenantsSeeder
```

### Tenant Status

Tenants can have the following statuses:
- `pending` - Tenant creation in progress
- `creating` - Database setup in progress
- `active` - Tenant is ready for use
- `suspended` - Tenant is temporarily disabled
- `failed` - Tenant creation failed

### Managing Tenants

```bash
# List all tenants
curl -X GET http://localhost:8000/api/admin/tenants \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"

# Suspend tenant
curl -X PATCH http://localhost:8000/api/admin/tenants/{id}/suspend \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"

# Activate tenant
curl -X PATCH http://localhost:8000/api/admin/tenants/{id}/activate \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"

# View tenant details
curl -X GET http://localhost:8000/api/admin/tenants/{id} \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

## 🔐 JWT Authentication

### Generating JWT Tokens

#### Admin Authentication
```bash
curl -X POST http://localhost:8000/api/admin/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@system.com",
    "password": "password123"
  }'

# Response:
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "admin": {
      "id": 1,
      "name": "System Administrator",
      "email": "admin@system.com"
    }
  }
}
```

#### Tenant User Authentication
```bash
curl -X POST http://localhost:8000/api/tenant/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@acme.com",
    "password": "password123"
  }'

# Response:
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "user": {
      "id": 1,
      "name": "John Smith",
      "email": "admin@acme.com"
    }
  }
}
```

### Using JWT Tokens

#### Admin API Calls
```bash
# List tenants
curl -X GET http://localhost:8000/api/admin/tenants \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"

# Create tenant
curl -X POST http://localhost:8000/api/admin/tenants \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{...}'
```

#### Tenant API Calls
```bash
# List contacts
curl -X GET http://localhost:8000/api/tenant/contacts \
  -H "Authorization: Bearer YOUR_TENANT_TOKEN"

# Create contact
curl -X POST http://localhost:8000/api/tenant/contacts \
  -H "Authorization: Bearer YOUR_TENANT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "New Contact",
    "email": "contact@example.com",
    "phone": "+1-555-0123"
  }'
```

### Token Management

#### Refresh Token
```bash
curl -X POST http://localhost:8000/api/tenant/refresh \
  -H "Authorization: Bearer YOUR_TENANT_TOKEN"
```

#### Logout
```bash
curl -X POST http://localhost:8000/api/tenant/logout \
  -H "Authorization: Bearer YOUR_TENANT_TOKEN"
```

#### Get Current User
```bash
curl -X GET http://localhost:8000/api/tenant/me \
  -H "Authorization: Bearer YOUR_TENANT_TOKEN"
```

## 👥 Sample Data

### System Admin
- **Email**: `admin@system.com`
- **Password**: `password123`
- **Role**: System Administrator

### Sample Tenants

#### ACME Corporation
- **Database**: `tenant_acme_corporation`
- **Admin**: `admin@acme.com` / `password123`
- **Users**:
  - John Smith (admin@acme.com) - Super Admin
  - Sarah Johnson (sarah@acme.com) - Manager
  - Mike Wilson (mike@acme.com) - Sales Rep
- **Sample Data**: 2 contacts, 2 deals, 3 activities

#### Globex Corporation
- **Database**: `tenant_globex_corporation`
- **Admin**: `admin@globex.com` / `password123`
- **Users**:
  - Jane Doe (admin@globex.com) - Super Admin
  - Tom Anderson (tom@globex.com) - Admin
  - Emma Taylor (emma@globex.com) - Manager
- **Sample Data**: 2 contacts, 2 deals, 3 activities

### Running Sample Seeders

```bash
# Create sample tenants with full data
php artisan db:seed --class=SampleTenantsSeeder

# Or run all seeders
php artisan db:seed
```

## 🔒 Role-Based Permissions

### Default Roles

1. **Super Admin** - Full access to all features
2. **Admin** - Administrative access with most permissions
3. **Manager** - Management access to contacts, deals, activities
4. **Sales Rep** - Access to manage contacts and deals
5. **User** - Basic access to view and manage own data

### Permission Categories

- **Users** - User management and role assignment
- **Contacts** - Contact CRUD operations
- **Deals** - Deal management and closing
- **Activities** - Activity tracking and management
- **Reports** - Report viewing and export
- **Settings** - System configuration
- **Audit Logs** - Activity history viewing
- **Roles** - Role and permission management

### Managing Permissions

```bash
# Get user permissions
curl -X GET http://localhost:8000/api/tenant/roles/user-permissions \
  -H "Authorization: Bearer YOUR_TENANT_TOKEN"

# Assign role to user
curl -X POST http://localhost:8000/api/tenant/roles/assign-role \
  -H "Authorization: Bearer YOUR_TENANT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 2,
    "role_name": "manager"
  }'

# Grant direct permission
curl -X POST http://localhost:8000/api/tenant/roles/give-permission \
  -H "Authorization: Bearer YOUR_TENANT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 2,
    "permission_name": "contacts.delete"
  }'
```

## 📊 Audit Logging

### Viewing Audit Logs

```bash
# Get user's audit logs
curl -X GET http://localhost:8000/api/tenant/audit-logs/my-logs \
  -H "Authorization: Bearer YOUR_TENANT_TOKEN"

# Get audit statistics
curl -X GET http://localhost:8000/api/tenant/audit-logs/stats \
  -H "Authorization: Bearer YOUR_TENANT_TOKEN"

# Get recent activity
curl -X GET http://localhost:8000/api/tenant/audit-logs/recent \
  -H "Authorization: Bearer YOUR_TENANT_TOKEN"

# Filter by action
curl -X GET http://localhost:8000/api/tenant/audit-logs/action/created \
  -H "Authorization: Bearer YOUR_TENANT_TOKEN"
```

### Audit Log Features

- **Automatic Logging**: All model changes are automatically logged
- **Complete Context**: IP, user agent, URL, method, timestamps
- **Change Tracking**: Old and new values for updates
- **Tenant Isolation**: Audit logs are completely isolated between tenants
- **Search & Filter**: Advanced filtering by user, action, resource, date

## 🧪 Testing

### Running Tests

```bash
# Run all tests
php artisan test

# Run tenant isolation tests
./run-isolation-tests.sh

# Run specific test categories
php artisan test tests/Unit/Isolation/
php artisan test tests/Feature/Tenant/
php artisan test tests/Feature/Admin/
```

### Test Coverage

The test suite includes:
- **50+ Isolation Tests** - Proving strict tenant isolation
- **JWT Security Tests** - Token validation and security
- **Permission Tests** - Role-based access control
- **API Tests** - Endpoint security and isolation
- **Audit Tests** - Activity tracking verification

## 📚 API Documentation

### Admin APIs

#### Authentication
```http
POST /api/admin/login
POST /api/admin/logout
```

#### Tenant Management
```http
GET    /api/admin/tenants
POST   /api/admin/tenants
GET    /api/admin/tenants/{id}
GET    /api/admin/tenants/{id}/status
PATCH  /api/admin/tenants/{id}/suspend
PATCH  /api/admin/tenants/{id}/activate
```

### Tenant APIs

#### Authentication
```http
POST /api/tenant/login
POST /api/tenant/logout
POST /api/tenant/refresh
GET  /api/tenant/me
```

#### CRM Resources
```http
# Contacts
GET    /api/tenant/contacts
POST   /api/tenant/contacts
GET    /api/tenant/contacts/{id}
PUT    /api/tenant/contacts/{id}
DELETE /api/tenant/contacts/{id}

# Deals
GET    /api/tenant/deals
POST   /api/tenant/deals
GET    /api/tenant/deals/{id}
PUT    /api/tenant/deals/{id}
DELETE /api/tenant/deals/{id}

# Activities
GET    /api/tenant/activities
POST   /api/tenant/activities
GET    /api/tenant/activities/{id}
PUT    /api/tenant/activities/{id}
DELETE /api/tenant/activities/{id}
```

#### Reports
```http
GET /api/tenant/reports/deals
GET /api/tenant/reports/contacts
GET /api/tenant/reports/activities
```

#### Role Management
```http
GET  /api/tenant/roles
GET  /api/tenant/roles/permissions
GET  /api/tenant/roles/user-permissions
POST /api/tenant/roles/assign-role
POST /api/tenant/roles/remove-role
POST /api/tenant/roles/give-permission
POST /api/tenant/roles/revoke-permission
```

#### Audit Logs
```http
GET /api/tenant/audit-logs
GET /api/tenant/audit-logs/my-logs
GET /api/tenant/audit-logs/stats
GET /api/tenant/audit-logs/recent
GET /api/tenant/audit-logs/action/{action}
GET /api/tenant/audit-logs/resource/{type}
GET /api/tenant/audit-logs/resource/{type}/{id}
```

## 🔧 Configuration

### Environment Variables

```env
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=multitenant_crm
DB_USERNAME=root
DB_PASSWORD=your_password

# JWT
JWT_SECRET=your-jwt-secret-key
JWT_ALGO=HS256
JWT_TTL=60

# Queue
QUEUE_CONNECTION=database

# Mail (for notifications)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="Multi-Tenant CRM"
```

### Queue Configuration

```bash
# Start queue worker
php artisan queue:work

# Process failed jobs
php artisan queue:retry all

# Monitor queue
php artisan queue:monitor
```

## 🛡️ Security Features

- **Database-level isolation**: Each tenant has a separate database
- **JWT Security**: Custom guards with comprehensive token validation
- **Role-based permissions**: Fine-grained access control
- **Audit logging**: Complete activity tracking
- **Input validation**: Strict validation on all endpoints
- **CSRF protection**: Built-in Laravel CSRF protection
- **SQL injection prevention**: Eloquent ORM protection

## 🚀 Deployment

### Production Setup

1. **Environment Configuration**
```bash
# Set production environment
APP_ENV=production
APP_DEBUG=false

# Configure production database
DB_HOST=your-production-host
DB_DATABASE=your-production-db
DB_USERNAME=your-production-user
DB_PASSWORD=your-production-password

# Set secure JWT secret
JWT_SECRET=your-very-secure-jwt-secret
```

2. **Queue Workers**
```bash
# Start queue workers
php artisan queue:work --daemon

# Or use supervisor for process management
```

3. **Caching**
```bash
# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 📄 License

This project is licensed under the MIT License.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Ensure all tests pass
6. Submit a pull request

## 📞 Support

For support and questions:
- Create an issue in the repository
- Check the documentation
- Review the test cases for examples

---

**Built with ❤️ using Laravel & JWT for Multi-Tenant SaaS Applications**
