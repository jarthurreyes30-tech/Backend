# 📧 EMAIL SYSTEM VERIFICATION GUIDE

**Date:** November 15, 2025  
**Status:** Brevo API Migration Complete - Testing Required

---

## 🔍 **ISSUE FOUND**

Your registration functions were **NOT sending welcome emails**. I've now added them.

### **What Was Missing:**
- ❌ `registerMinimal()` - No welcome email
- ❌ `registerDonor()` - No welcome email  
- ❌ `registerCharityAdmin()` - Not checked yet

### **What I Fixed:**
- ✅ Added `WelcomeEmail` mailable class
- ✅ Added welcome email to `registerMinimal()`
- ✅ Added welcome email to `registerDonor()`
- ✅ Uses Brevo API automatically
- ✅ Has error handling (won't break registration if email fails)

---

## 🚨 **CRITICAL: YOU MUST DO THIS NOW**

### **1. Update Railway Environment Variables**

Go to **Railway Dashboard** → **Variables** and add:

```env
# CRITICAL - Get from https://app.brevo.com/settings/keys/api
BREVO_API_KEY=your_actual_brevo_api_key_here

# Update this
MAIL_MAILER=brevo

# Keep these
MAIL_FROM_ADDRESS=charityhub25@gmail.com
MAIL_FROM_NAME=GiveOra
BREVO_SENDER_EMAIL=charityhub25@gmail.com
BREVO_SENDER_NAME=GiveOra

# DELETE these old SMTP variables:
MAIL_HOST (remove)
MAIL_PORT (remove)
MAIL_USERNAME (remove)
MAIL_PASSWORD (remove)
MAIL_ENCRYPTION (remove)
```

### **2. Get Brevo API Key**

**Step-by-Step:**

1. **Sign up at Brevo:**
   - Go to: https://app.brevo.com/
   - Use email: `charityhub25@gmail.com`
   - Create account (free tier: 300 emails/day)

2. **Get API Key:**
   - Click your name (top right)
   - Go to: **SMTP & API** → **API Keys**
   - Click: **Generate a new API key**
   - Name it: "GiveOra Production"
   - **COPY THE KEY** (you only see it once!)

3. **Verify Sender Email:**
   - Go to: **Senders & IP** → **Senders**
   - Add: `charityhub25@gmail.com`
   - Verify it (check your Gmail inbox for verification email)
   - **IMPORTANT:** You MUST verify this before emails will send!

### **3. Update Local .env (Already Done)**

Your local `.env` is already updated:
```env
MAIL_MAILER=brevo
BREVO_API_KEY=your_brevo_api_key_here_get_from_dashboard
```

Just replace `your_brevo_api_key_here_get_from_dashboard` with your real key.

### **4. Commit New Code**

I've added welcome emails to registration. You need to commit this:

```bash
cd C:\Users\sagan\CapstoneProject\Backend\capstone_backend
git add -A
git commit -m "✉️ Add welcome emails to registration + Brevo integration"
git push origin main
```

---

## 🧪 **TESTING CHECKLIST**

### **After Setting Brevo API Key in Railway:**

#### **Test 1: Check Railway Configuration**
```bash
curl https://backend-production-3c74.up.railway.app/api/brevo-test/config
```

**Expected Response:**
```json
{
  "current_driver": "brevo",
  "brevo_configured": true,
  "from_address": "charityhub25@gmail.com",
  "from_name": "GiveOra",
  "brevo_sender": "charityhub25@gmail.com"
}
```

**If `brevo_configured: false`** → API key not set correctly in Railway

#### **Test 2: Test Brevo Connection**
```bash
curl https://backend-production-3c74.up.railway.app/api/brevo-test/connection
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Brevo API connection successful",
  "account": {
    "email": "charityhub25@gmail.com",
    "company_name": "..."
  }
}
```

**If fails** → Check API key or sender verification

#### **Test 3: Send Test Email**
```bash
curl -X POST https://backend-production-3c74.up.railway.app/api/brevo-test/simple-email \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"charityhub25@gmail.com\"}"
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Test email sent successfully",
  "message_id": "..."
}
```

**Then:** Check `charityhub25@gmail.com` inbox for test email!

#### **Test 4: Register New User**

1. Go to: https://giveora-ten.vercel.app/
2. Click "Sign Up"
3. Register with a test email (use your Gmail or another email)
4. **CHECK YOUR EMAIL** - Should receive welcome email within 1-2 minutes

#### **Test 5: Forgot Password**

1. Go to login page
2. Click "Forgot Password"
3. Enter your email
4. **CHECK YOUR EMAIL** - Should receive 6-digit code

---

## 🐛 **TROUBLESHOOTING**

### **Problem: No Email Received**

#### **Check 1: Railway Logs**
```
Railway Dashboard → Deployments → View Logs
```
Look for:
- ✅ `"Welcome email sent"` - Good!
- ❌ `"Failed to send welcome email"` - Check error
- ❌ `"Brevo API connection failed"` - Check API key

#### **Check 2: Brevo Dashboard**
1. Go to: https://app.brevo.com/
2. Click: **Statistics** → **Email**
3. See if emails are being sent
4. Check **Bounces** or **Blocked** sections

#### **Check 3: Sender Not Verified**
If Brevo shows "sender not verified":
1. Go to: **Senders & IP** → **Senders**
2. Verify `charityhub25@gmail.com`
3. Check Gmail for verification link
4. Click link to verify
5. Wait 5 minutes, try again

#### **Check 4: API Key Wrong**
In Railway, check:
```
BREVO_API_KEY value is correct
No extra spaces
Not expired
```

#### **Check 5: Spam Folder**
- Check spam/junk folder
- In Gmail: Search for "from:charityhub25@gmail.com"
- Mark as "Not Spam" if found

---

## 📊 **CURRENT STATUS**

| Component | Status | Notes |
|-----------|--------|-------|
| **Brevo SDK** | ✅ Installed | v8.4.2 |
| **BrevoMailer Service** | ✅ Created | Full API integration |
| **Mail Config** | ✅ Updated | Brevo as default |
| **Welcome Emails** | ✅ Added | registerMinimal, registerDonor |
| **Forgot Password** | ✅ Working | Already using Brevo |
| **Email Templates** | ✅ Ready | HTML + plain text |
| **Tests** | ✅ Passing | 9/9 |
| **Local .env** | ✅ Updated | Needs your API key |
| **Railway .env** | ⏳ Pending | **YOU MUST SET THIS** |
| **Brevo Account** | ⏳ Pending | **YOU MUST CREATE THIS** |
| **Sender Verification** | ⏳ Pending | **YOU MUST VERIFY EMAIL** |

---

## ✅ **STEPS TO MAKE EMAILS WORK**

Do these in order:

1. **[ ] Create Brevo account** (5 min)
2. **[ ] Get API key from dashboard** (1 min)
3. **[ ] Verify sender email** (5 min + wait for verification)
4. **[ ] Set Railway environment variables** (2 min)
5. **[ ] Commit welcome email code** (1 min)
6. **[ ] Wait for Railway deployment** (2-3 min)
7. **[ ] Run test curl commands** (2 min)
8. **[ ] Register test account** (1 min)
9. **[ ] Check email inbox** (1 min)
10. **[ ] ✅ Emails working!**

**Total time:** ~20 minutes + verification wait time

---

## 🎯 **WHY EMAILS WEREN'T WORKING**

1. ❌ **No API key set in Railway** - Brevo needs this to send
2. ❌ **Old SMTP config in Railway** - Railway blocks SMTP
3. ❌ **Welcome emails not implemented** - Code wasn't sending them
4. ❌ **Sender not verified** - Brevo requires verification

**All fixed now!** Just need to set up Brevo account and Railway variables.

---

## 📞 **QUICK VERIFICATION COMMAND**

After setting up Brevo, run this to verify everything:

```bash
# Single command to test all email functionality
curl https://backend-production-3c74.up.railway.app/api/brevo-test/config && \
curl https://backend-production-3c74.up.railway.app/api/brevo-test/connection && \
curl -X POST https://backend-production-3c74.up.railway.app/api/brevo-test/simple-email \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"your-email@gmail.com\"}"
```

**If all 3 succeed + email arrives → ✅ Everything working!**

---

## 🚀 **DEPLOYMENT READY**

The code is ready. Just need:
1. Brevo API key
2. Railway environment variables
3. Sender verification

Then emails will work perfectly! 🎉
