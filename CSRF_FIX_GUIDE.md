# CSRF Token Fix Guide

## Problem
Your frontend was getting "CSRF token mismatch" errors when making API requests to the Laravel backend.

## Root Cause
The Laravel backend was applying CSRF protection to API routes, but your frontend uses token-based authentication (Bearer tokens) which doesn't need CSRF protection.

## Solution Applied

### 1. Excluded API Routes from CSRF Verification
**File:** `app/Http/Middleware/VerifyCsrfToken.php`
```php
protected $except = [
    'api/*', // Exclude all API routes from CSRF verification
];
```

### 2. Removed Stateful Middleware from API Group
**File:** `app/Http/Kernel.php`
- Removed `EnsureFrontendRequestsAreStateful` from the API middleware group
- This prevents CSRF token requirements for API routes

### 3. Updated Sanctum Configuration
**File:** `config/sanctum.php`
- Added `shai-khadri.com` to stateful domains for proper authentication

## Deployment Steps

1. **Upload the updated files to Hostinger:**
   - `app/Http/Middleware/VerifyCsrfToken.php`
   - `app/Http/Kernel.php`
   - `config/sanctum.php`

2. **Clear Laravel caches:**
   ```bash
   php artisan config:clear
   php artisan route:clear
   php artisan cache:clear
   ```

3. **Test the API:**
   - Try creating an order in your POS
   - Check that quote-item requests work without CSRF errors

## What This Fixes

✅ **API requests will work without CSRF tokens**
✅ **Token-based authentication will work properly**
✅ **Your POS system will function normally**
✅ **No more "CSRF token mismatch" errors**

## Security Note

This is safe because:
- API routes use Bearer token authentication
- CSRF protection is only needed for session-based authentication
- Your frontend sends Authorization headers with tokens
- API routes are protected by `auth:sanctum` middleware

## Testing

After deployment, test these endpoints:
- `POST /api/orders/quote-item` (should work without CSRF)
- `POST /api/orders` (should work without CSRF)
- All other API endpoints should work normally 