# 🚨 CRITICAL: EMAIL NOT SENDING ON RAILWAY - IMMEDIATE FIX

## ⚠️ PROBLEM
Email verification codes NOT sending on deployed Railway website.

## 🔍 DIAGNOSIS STEPS

### 1. Check Railway Environment Variables
```bash
# In Railway dashboard, verify these variables exist:
BREVO_API_KEY=your_actual_api_key
BREVO_SENDER_EMAIL=charityhub25@gmail.com
BREVO_SENDER_NAME=GiveOra
MAIL_MAILER=brevo
MAIL_FROM_ADDRESS=charityhub25@gmail.com
MAIL_FROM_NAME=GiveOra
```

### 2. Check Railway Deployment Status
```bash
# Verify latest commit deployed:
Commit: 9f1ac9f
Branch: main
Status: Should show "SUCCESS"
```

### 3. Check Migration Ran
```bash
# Railway should have run migrations automatically
# Verify pending_registrations table exists
```

### 4. Check Railway Logs
```bash
# Look for errors like:
- "Failed to send verification email"
- "Brevo API error"
- "Invalid API key"
- "Sender email not verified"
```

## ✅ IMMEDIATE FIXES

### FIX 1: Ensure Brevo API Key Set on Railway
1. Go to Railway dashboard
2. Click on your project
3. Go to Variables tab
4. Add/Update:
   ```
   BREVO_API_KEY=xkeysib-YOUR_ACTUAL_KEY_HERE
   ```
5. Redeploy

### FIX 2: Verify Sender Email in Brevo
1. Go to https://app.brevo.com/senders
2. Ensure `charityhub25@gmail.com` is verified
3. If not verified:
   - Click "Add a Sender"
   - Enter charityhub25@gmail.com
   - Verify via email confirmation

### FIX 3: Force Railway to Redeploy
```bash
# Railway should auto-deploy from GitHub push
# If not, manually trigger:
1. Go to Railway dashboard
2. Click "Deploy" → "Redeploy"
3. Wait 2-3 minutes
```

### FIX 4: Run Migration on Railway
```bash
# If migration didn't run automatically:
1. Railway dashboard → your project
2. Go to "Settings" → "Deploy"
3. Check "Run Migrations" is enabled
4. Or manually run via Railway CLI:
   railway run php artisan migrate --force
```

## 🧪 TEST DEPLOYMENT

### Test 1: Check API is Live
```bash
curl https://backend-production-3c74.up.railway.app/api/health
# Should return 200 OK
```

### Test 2: Test Brevo Connection
```bash
curl https://backend-production-3c74.up.railway.app/api/brevo/test-connection
# Should return: {"success":true}
```

### Test 3: Test Registration
```bash
curl -X POST https://backend-production-3c74.up.railway.app/api/auth/register-minimal \
  -H "Content-Type: application/json" \
  -d '{"name":"Test","email":"YOUR_EMAIL@gmail.com","password":"password123","password_confirmation":"password123"}'

# Should return:
{
  "success": true,
  "message": "Verification code sent to your email!",
  "requires_verification": true
}

# Check your email inbox for 6-digit code
```

## 🔧 RAILWAY LOGS TO CHECK

```bash
# In Railway dashboard → Logs tab, look for:

✅ GOOD:
"Pending registration created - awaiting email verification"
"Verification code sent"
"Brevo email sent successfully"

❌ BAD:
"Failed to send verification email"
"Brevo API error"
"Class 'PendingRegistration' not found"
"Table 'pending_registrations' doesn't exist"
```

## 📋 CHECKLIST FOR RAILWAY

- [ ] Latest commit (9f1ac9f) deployed
- [ ] BREVO_API_KEY set in Railway variables
- [ ] MAIL_MAILER=brevo set in Railway variables
- [ ] Sender email (charityhub25@gmail.com) verified in Brevo
- [ ] Migration ran (pending_registrations table exists)
- [ ] No errors in Railway logs
- [ ] Test endpoint returns success
- [ ] Actual email received in inbox

## 🚀 QUICK FIX COMMANDS

If nothing works, run these on Railway:

```bash
# Clear all caches
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear

# Run migrations
php artisan migrate --force

# Test Brevo
php artisan tinker
>>> Mail::to('charityhub25@gmail.com')->send(new \App\Mail\VerificationCodeMail('Test', 'test@test.com', '123456', now()->addMinutes(15)));
```

## 🎯 MOST COMMON ISSUES

1. **BREVO_API_KEY not set on Railway** → Add in Variables tab
2. **Sender email not verified in Brevo** → Verify at app.brevo.com
3. **Migration not run** → Manually run migration
4. **Old code still deployed** → Force redeploy
5. **Queue not processing** → Railway should have queue worker

## 📞 IF STILL NOT WORKING

Check Railway logs for EXACT error message and search for:
- "Brevo"
- "email"
- "verification"
- "error"
- "exception"

Copy the error here so I can fix it!
