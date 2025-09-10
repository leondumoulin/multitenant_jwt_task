# 🧪 API Testing Guide

## 🚀 Quick Start

### 1. Start the Server
```bash
php artisan serve
```

### 2. Test Admin Login
```bash
curl -X POST http://127.0.0.1:8000/api/admin/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@system.com","password":"password123"}'
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "Bearer",
    "expires_in": 28800,
    "admin": {
      "id": 1,
      "name": "System Administrator",
      "email": "admin@system.com"
    }
  }
}
```

### 3. Test Admin Endpoints
```bash
# Get admin profile
curl -X GET http://127.0.0.1:8000/api/admin/me \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"

# List all tenants
curl -X GET http://127.0.0.1:8000/api/admin/tenants \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

### 4. Test Tenant Login
```bash
curl -X POST http://127.0.0.1:8000/api/tenant/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@acme.com","password":"password123","tenant_id":1}'
```

### 5. Test Tenant Endpoints
```bash
# Get tenant user profile
curl -X GET http://127.0.0.1:8000/api/tenant/me \
  -H "Authorization: Bearer YOUR_TENANT_TOKEN"

# List contacts
curl -X GET http://127.0.0.1:8000/api/tenant/contacts \
  -H "Authorization: Bearer YOUR_TENANT_TOKEN"

# Create a contact
curl -X POST http://127.0.0.1:8000/api/tenant/contacts \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TENANT_TOKEN" \
  -d '{
    "name": "John Smith",
    "email": "john@example.com",
    "phone": "+1234567890",
    "company": "Tech Corp",
    "status": "active"
  }'
```

## 📊 Demo Credentials

### System Admins
- **Email**: `admin@system.com` | **Password**: `password123`
- **Email**: `superadmin@system.com` | **Password**: `password123`

### Demo Tenants
1. **ACME Corporation**
   - **Admin**: `admin@acme.com` | **Password**: `password123` | **Tenant ID**: `1`

2. **Globex Corporation**
   - **Admin**: `admin@globex.com` | **Password**: `password123` | **Tenant ID**: `2`

## 🔧 Available Endpoints

### Admin APIs
- `POST /api/admin/login` - Admin login
- `GET /api/admin/me` - Get admin profile
- `POST /api/admin/logout` - Admin logout
- `GET /api/admin/tenants` - List all tenants
- `POST /api/admin/tenants` - Create new tenant
- `GET /api/admin/tenants/{id}` - Get tenant details
- `PATCH /api/admin/tenants/{id}/suspend` - Suspend tenant
- `PATCH /api/admin/tenants/{id}/activate` - Activate tenant

### Tenant APIs
- `POST /api/tenant/login` - Tenant user login
- `POST /api/tenant/refresh` - Refresh token
- `GET /api/tenant/me` - Get tenant user profile
- `POST /api/tenant/logout` - Tenant logout

#### CRM Resources
- `GET /api/tenant/contacts` - List contacts
- `POST /api/tenant/contacts` - Create contact
- `GET /api/tenant/contacts/{id}` - Get contact
- `PUT /api/tenant/contacts/{id}` - Update contact
- `DELETE /api/tenant/contacts/{id}` - Delete contact

- `GET /api/tenant/deals` - List deals
- `POST /api/tenant/deals` - Create deal
- `GET /api/tenant/deals/{id}` - Get deal
- `PUT /api/tenant/deals/{id}` - Update deal
- `DELETE /api/tenant/deals/{id}` - Delete deal

- `GET /api/tenant/activities` - List activities
- `POST /api/tenant/activities` - Create activity
- `GET /api/tenant/activities/{id}` - Get activity
- `PUT /api/tenant/activities/{id}` - Update activity
- `DELETE /api/tenant/activities/{id}` - Delete activity

#### Reports
- `GET /api/tenant/reports/deals` - Deals analytics
- `GET /api/tenant/reports/contacts` - Contacts analytics
- `GET /api/tenant/reports/activities` - Activities analytics

## 🧪 Testing with Postman

1. Import the `postman_collection.json` file
2. Set the `base_url` variable to `http://127.0.0.1:8000`
3. Start with Admin Login to get `admin_token`
4. Use the admin token to test admin endpoints
5. Create a tenant using admin endpoints
6. Use tenant login to get `tenant_token`
7. Test tenant endpoints with the tenant token

## 🔍 Troubleshooting

### Routes Not Working
If routes are not showing up, make sure:
1. `bootstrap/app.php` includes `api: __DIR__.'/../routes/api.php'`
2. Run `php artisan route:list` to verify routes are registered

### Authentication Errors
- Make sure JWT_SECRET is set in `.env`
- Verify the token is included in Authorization header
- Check that the user exists in the database

### Database Errors
- Run `php artisan migrate` to create tables
- Run `php artisan db:seed` to create demo data
- Check database connection in `.env`

## ✅ Success Indicators

- ✅ Server starts without errors
- ✅ Routes are listed with `php artisan route:list`
- ✅ Admin login returns JWT token
- ✅ Tenant login returns JWT token
- ✅ Protected endpoints require authentication
- ✅ Tenant isolation works (each tenant sees only their data)

## 🔧 Error Handling

The API now returns clean JSON error responses for all scenarios:

### Authentication Errors (401)
```json
{
  "success": false,
  "message": "Unauthenticated"
}
```

### Route Not Found (404)
```json
{
  "success": false,
  "message": "Route not found"
}
```

### Validation Errors (422)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field must be a valid email address."],
    "password": ["The password field is required."]
  }
}
```

### General Errors (500)
```json
{
  "success": false,
  "message": "Internal server error"
}
```

## 🎯 Error Testing Scenarios

Test these scenarios to verify proper error handling:

1. **No Authentication Token:**
   ```bash
   curl -X GET http://127.0.0.1:8000/api/admin/tenants
   # Expected: {"success":false,"message":"Unauthenticated"}
   ```

2. **Invalid Route:**
   ```bash
   curl -X GET http://127.0.0.1:8000/api/nonexistent
   # Expected: {"success":false,"message":"Route not found"}
   ```

3. **Invalid Login Data:**
   ```bash
   curl -X POST http://127.0.0.1:8000/api/admin/login \
     -H "Content-Type: application/json" \
     -d '{"email":"invalid-email"}'
   # Expected: Validation error with field details
   ```

4. **Expired Token:**
   ```bash
   curl -X GET http://127.0.0.1:8000/api/admin/tenants \
     -H "Authorization: Bearer expired-token"
   # Expected: {"success":false,"message":"Unauthenticated"}
   ```

## ✅ Fixed Issues

- ✅ **Route [login] not defined** - Fixed with custom exception handling
- ✅ **Internal Server Error** - Now returns clean JSON responses
- ✅ **Authentication failures** - Returns proper 401 responses
- ✅ **Route not found** - Returns proper 404 responses
- ✅ **Validation errors** - Returns detailed 422 responses
- ✅ **General exceptions** - Returns clean 500 responses

All API endpoints now return consistent JSON responses with `success` and `message` fields, making error handling predictable and user-friendly.
