# Railway Production Setup Instructions

## Critical Setup Steps for Email and Image Functionality

### 1. Environment Variables (Railway Dashboard)

Ensure ALL these variables are set in Railway:

```env
# Application
APP_NAME=GiveOra
APP_ENV=production
APP_DEBUG=false
APP_URL=https://backend-production-3c74.up.railway.app

# Mail Configuration (CRITICAL for email sending)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=charityhub25@gmail.com
MAIL_PASSWORD=nnkdtchwnldeubms
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=charityhub25@gmail.com
MAIL_FROM_NAME="GiveOra"

# Queue Configuration (CRITICAL for email delivery)
QUEUE_CONNECTION=database

# Frontend URL (for email links and CORS)
FRONTEND_URL=https://giveora-ten.vercel.app

# Database (automatically set by Railway)
# DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD

# Session & Cache
SESSION_DRIVER=database
CACHE_STORE=database

# Filesystem
FILESYSTEM_DISK=public
```

### 2. Run Migrations After Deployment

In Railway terminal or via SSH:

```bash
# Run migrations to create pending_registrations table
php artisan migrate --force

# Create storage link for public files
php artisan storage:link

# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

### 3. Seed Admin Account

```bash
# Seed admin account (email: admin@example.com, password: password)
php artisan db:seed --class=AdminSeeder
```

### 4. Queue Worker Configuration (CRITICAL!)

**⚠️ IMPORTANT:** Emails won't send without a queue worker running!

#### Option A: Add as Railway Service (Recommended)

1. Create a new service in Railway
2. Use the same repository
3. Set custom start command: `php artisan queue:work --tries=3 --timeout=90 --sleep=3`
4. Link to same database

#### Option B: Use Process Manager in Dockerfile

Add to your Dockerfile or Nixpacks configuration:

```dockerfile
CMD php artisan queue:work --daemon & php artisan serve --host=0.0.0.0 --port=$PORT
```

#### Option C: Scheduled Queue (Less reliable)

Add a cron job to process queue every minute:
```bash
* * * * * cd /app && php artisan queue:work --stop-when-empty
```

### 5. Verify Setup

#### A. Check Health Endpoint
```bash
curl https://backend-production-3c74.up.railway.app/api/ping
```

Expected response:
```json
{
  "ok": true,
  "time": "2024-11-14 12:00:00"
}
```

#### B. Test Email Connection
```bash
curl https://backend-production-3c74.up.railway.app/api/email/test-connection
```

Expected response:
```json
{
  "success": true,
  "message": "SMTP connection successful",
  "mail_config": {
    "driver": "smtp",
    "host": "smtp.gmail.com",
    "port": 587,
    "encryption": "tls"
  }
}
```

#### C. Test Registration Flow
```bash
# 1. Register
curl -X POST https://backend-production-3c74.up.railway.app/api/auth/register-minimal \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "Password123!",
    "password_confirmation": "Password123!"
  }'

# 2. Check email for 6-digit code

# 3. Verify with code
curl -X POST https://backend-production-3c74.up.railway.app/api/auth/verify-email-code \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "code": "123456"
  }'
```

#### D. Test Admin Login
```bash
curl -X POST https://backend-production-3c74.up.railway.app/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password"
  }'
```

### 6. Monitor Logs

```bash
# View Railway logs
railway logs --follow

# Or in Railway dashboard, go to Deployments > View Logs

# Look for:
# - "Verification email sent successfully"
# - "Queue job processed"
# - Mail errors (if any)
```

### 7. Troubleshooting Common Issues

#### Issue: Emails Not Sending

**Check:**
1. Queue worker is running (`ps aux | grep queue:work`)
2. MAIL_* environment variables are set correctly
3. Gmail app password is still valid
4. Check failed jobs: `php artisan queue:failed`

**Fix:**
```bash
# Restart queue worker
php artisan queue:restart

# Retry failed jobs
php artisan queue:retry all

# Clear config cache
php artisan config:clear
```

#### Issue: Images Not Displaying

**Check:**
1. Storage link exists: `ls -la public/storage`
2. Files uploaded to `storage/app/public`
3. APP_URL is set correctly

**Fix:**
```bash
# Create storage link
php artisan storage:link

# Set correct permissions
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

#### Issue: 500 Errors

**Check:**
```bash
# View logs
tail -f storage/logs/laravel.log

# Common causes:
# - Missing .env variables
# - Database connection issues
# - Missing migrations
# - File permission issues
```

**Fix:**
```bash
# Run migrations
php artisan migrate --force

# Clear all caches
php artisan optimize:clear

# Check environment
php artisan env
```

#### Issue: Admin Can't Login

**Fix:**
```bash
# Re-seed admin
php artisan db:seed --class=AdminSeeder

# Or manually reset password
php artisan tinker
>>> $admin = User::where('email', 'admin@example.com')->first();
>>> $admin->password = Hash::make('password');
>>> $admin->email_verified_at = now();
>>> $admin->save();
>>> exit
```

### 8. Production Checklist

Before going live:

- [ ] All environment variables set
- [ ] Migrations run successfully
- [ ] Storage link created
- [ ] Admin account seeded and tested
- [ ] Queue worker running and processing jobs
- [ ] Email sending tested and working
- [ ] Test registration flow works end-to-end
- [ ] Test admin login works
- [ ] Test file uploads work
- [ ] Check all API endpoints return correct status codes
- [ ] Monitor logs for errors
- [ ] Set APP_DEBUG=false in production
- [ ] Configure proper error handling and monitoring

### 9. Maintenance Commands

```bash
# Clear expired pending registrations (run daily)
php artisan tinker
>>> PendingRegistration::where('expires_at', '<', now())->delete();

# Clear old failed jobs (run weekly)
php artisan queue:flush

# Optimize application
php artisan optimize

# Clear old logs (run monthly)
rm storage/logs/laravel-*.log
```

### 10. Monitoring

Set up monitoring for:
- Queue worker uptime
- Email delivery rate
- Failed job count
- API response times
- Error rates

Use Railway metrics or integrate with:
- Sentry (error tracking)
- New Relic (performance)
- LogRocket (user sessions)

---

**Last Updated:** November 14, 2024
**Railway URL:** https://backend-production-3c74.up.railway.app/
**Frontend URL:** https://giveora-ten.vercel.app/
