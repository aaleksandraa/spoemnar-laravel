# Laravel Backend Setup

## Configuration Completed

### 1. Database Configuration
- **Database**: PostgreSQL
- **Connection**: Configured in `.env` and `.env.example`
- **Database Name**: spomenar
- **Host**: 127.0.0.1
- **Port**: 5432

### 2. Laravel Sanctum
- **Installed**: laravel/sanctum v4.3.0
- **Configuration**: Published to `config/sanctum.php`
- **Migrations**: Published to `database/migrations`
- **User Model**: Updated with `HasApiTokens` trait

### 3. CORS Configuration
- **File**: `config/cors.php`
- **Allowed Origins**: Configured via `FRONTEND_URL` environment variable (default: http://localhost:3000)
- **Allowed Methods**: All methods (GET, POST, PUT, DELETE, etc.)
- **Allowed Headers**: All headers
- **Supports Credentials**: Enabled for cookie-based authentication

### 4. API Versioning
- **Structure**: `/api/v1/`
- **Routes File**: `backend/routes/api.php`
- **Registered**: In `bootstrap/app.php`
- **Test Endpoint**: `GET /api/v1/health` - Returns API status and version

### 5. Environment Variables Added
```env
# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=spomenar
DB_USERNAME=postgres
DB_PASSWORD=

# Sanctum Configuration
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,127.0.0.1,127.0.0.1:3000
SESSION_DOMAIN=localhost

# CORS Configuration
FRONTEND_URL=http://localhost:3000
```

## Next Steps

1. Create PostgreSQL database named `spomenar`
2. Run migrations: `php artisan migrate`
3. Start development server: `php artisan serve`
4. Test API endpoint: `http://localhost:8000/api/v1/health`

## Requirements Validated

- ✅ **Requirement 10.5**: API versioning implemented (/api/v1/)
- ✅ **Requirement 13.1**: CORS configured for frontend communication
- ✅ **Requirement 1.2**: Laravel Sanctum installed for JWT authentication
- ✅ Database configured for PostgreSQL (as per design document)
