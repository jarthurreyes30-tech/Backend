# 🚨 CRITICAL: Run These Commands on Railway NOW

## The Problem
The `pending_registrations` table doesn't exist in Railway's database yet!

## The Solution
Run these commands in Railway's terminal **immediately**:

---

## Step 1: Open Railway Terminal

1. Go to Railway dashboard
2. Click on your backend service
3. Click on the **"Terminal"** or **"Deploy Logs"** tab
4. Look for a way to open an interactive terminal

---

## Step 2: Run Migration Command

```bash
php artisan migrate --force
```

**Expected Output:**
```
INFO  Running migrations.

2024_11_14_120000_create_pending_registrations_table ........... DONE
```

---

## Step 3: Clear All Caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

---

## Step 4: Check If Table Exists

```bash
php artisan tinker --execute="echo 'Tables: '; print_r(DB::select('SHOW TABLES'));"
```

Look for `pending_registrations` in the output.

---

## Step 5: Start Queue Worker (CRITICAL!)

**Option A - If Railway Has Process Manager:**
Add this to `Procfile` or Railway processes:
```
worker: php artisan queue:work --tries=3 --timeout=90
```

**Option B - Manual Start (Temporary):**
```bash
php artisan queue:work --tries=3 --timeout=90 &
```

---

## Step 6: Test Registration

After running the migration, try registering again from your frontend.

---

## Alternative: If Terminal Access Is Limited

Create a migration check endpoint (I can add this for you) or use Railway's built-in deployment commands.

---

## Railway Deployment Script

If Railway supports deployment scripts, add this to your project:

**File: `railway.json`**
```json
{
  "build": {
    "builder": "nixpacks"
  },
  "deploy": {
    "startCommand": "php artisan migrate --force && php artisan config:clear && php artisan serve --host=0.0.0.0 --port=$PORT",
    "restartPolicyType": "always"
  }
}
```

Or in **`Procfile`**:
```
web: php artisan migrate --force && php artisan config:clear && php artisan serve --host=0.0.0.0 --port=$PORT
worker: php artisan queue:work --tries=3 --timeout=90
```

---

## 🎯 QUICK FIX

If you can't access Railway terminal right now, I can create a **temporary migration endpoint** that will run migrations via an API call.

**Would you like me to create that?**
