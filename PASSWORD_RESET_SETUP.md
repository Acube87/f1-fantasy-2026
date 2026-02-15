# Password Reset Setup Guide

## ✅ What's Been Added

1. **Database Table**: `password_reset_tokens`
2. **New Pages**:
   - `forgot-password.php` - Request reset link
   - `reset-password.php` - Set new password
3. **Login Page**: Added "Forgot Password?" link

---

## 🚀 Setup Instructions

### Step 1: Create Database Table

Visit: `http://your-site.com/admin/setup-password-reset.php`

This will create the `password_reset_tokens` table.

### Step 2: Configure Email (Choose One)

#### Option A: Use Server's Default Mail (Simplest)
- No configuration needed
- Works if your hosting provider supports PHP `mail()`
- **Test it first** - some hosts block this

#### Option B: Configure SMTP (More Reliable)
If native PHP mail doesn't work, you can upgrade to PHPMailer:

1. Install PHPMailer: `composer require phpmailer/phpmailer`
2. Update `forgot-password.php` lines 51-62 to use PHPMailer instead of `mail()`

---

## 📧 Email Configuration Options

### For Local Development (Testing)
- **MailHog** or **Mailtrap** - catches emails without sending
- Install MailHog: `brew install mailhog` (Mac) then run `mailhog`

### For Production
- Check if your host supports PHP `mail()` (most do)
- Or use SMTP with Gmail, SendGrid, Mailgun, etc.

---

## 🧪 Testing

1. Visit `http://your-site.com/login.php`
2. Click "Forgot Password?"
3. Enter email address
4. Check inbox for reset link
5. Click link and set new password

---

## ⚙️ How It Works

1. User enters email on `forgot-password.php`
2. System generates secure 64-character token
3. Token stored in database (expires in 1 hour)
4. Email sent with reset link containing token
5. User clicks link → `reset-password.php?token=xxx`
6. Token validated (not used, not expired)
7. User sets new password
8. Token marked as used

---

## 🔒 Security Features

- ✅ Rate limiting (3 attempts per 15 minutes)
- ✅ CSRF protection
- ✅ Tokens expire in 1 hour
- ✅ Tokens can only be used once
- ✅ Doesn't reveal if email exists
- ✅ Secure random token generation

---

## 📝 Notes

- Reset links expire after **1 hour**
- Users can request multiple resets (old tokens still valid until expiration)
- Email doesn't reveal if account exists (security best practice)
- All tokens are hashed and stored securely

---

## 🛠️ Troubleshooting

**Email not sending?**
1. Check server error logs
2. Verify `mail()` is enabled: `php -i | grep sendmail`
3. Test with a simple script:
   ```php
   <?php
   mail('your@email.com', 'Test', 'Testing PHP mail');
   echo 'Email sent!';
   ?>
   ```

**Token invalid?**
- Check database: `SELECT * FROM password_reset_tokens ORDER BY created_at DESC;`
- Ensure system time is correct (tokens use server time)

---

## 🎯 Next Steps

Users can now:
1. Click "Forgot Password?" on login page
2. Receive reset email
3. Click link to reset password
4. Log in with new password

**That's it!** Your password reset system is ready to use. 🏎️💨
