# SMTP Email Implementation - Testing Guide

## Overview
This document provides instructions for testing the SMTP email system and forgot password feature implemented in DEVSMW.

## System Architecture

### Components Implemented
1. **Email Helper Functions** (`includes/email.php`)
   - `send_email()` - Main email sending function
   - `get_smtp_config()` - Retrieve SMTP settings from database
   - `update_smtp_config()` - Update SMTP settings
   - `send_password_reset_email()` - Send password reset email with HTML/text versions
   - `generate_password_reset_token()` - Create a secure reset token
   - `verify_password_reset_token()` - Validate reset token and check expiry
   - `mark_reset_token_used()` - Mark token as used (prevents reuse)
   - SMTPMailer class - Handles multipart MIME email construction

2. **Database Tables**
   - `email_config` - Stores SMTP configuration (host, port, encryption, credentials, from address)
   - `password_reset_tokens` - Tracks reset tokens with expiry (1 hour) and usage

3. **Admin Pages**
   - `admin/smtp_config.php` - UI for configuring SMTP settings with test email function
   - `admin/forgot_password.php` - Form for users to request password reset
   - `admin/reset_password.php` - Form to set new password with token validation
   - `admin/login.php` - Updated with "Forgot password?" link

## Testing Procedures

### Phase 1: SMTP Configuration Setup

1. **Access Configuration Page**
   - Login as admin
   - Click "Email Settings" button on dashboard
   - Or navigate directly to `/admin/smtp_config.php`

2. **Configure Gmail SMTP** (recommended for testing)
   - SMTP Host: `smtp.gmail.com`
   - SMTP Port: `587`
   - SMTP Encryption: `TLS`
   - SMTP Username: Your Gmail address (e.g., `your-email@gmail.com`)
   - SMTP Password: [App Password from Gmail security settings](https://support.google.com/accounts/answer/185833)
     - Note: Regular Gmail password won't work; must create App Password
   - From Email Address: `noreply@devsmw.com` (or your email)
   - From Name: `DEVSMW Admin`

3. **Save Configuration**
   - Click "Save Configuration" button
   - Settings are stored encrypted in the database

4. **Test Email Function**
   - Scroll down to "Test SMTP Configuration" section
   - Enter a test email address
   - Click "Send Test Email"
   - Check inbox for test email
   - If successful, SMTP is working correctly

### Phase 2: Forgot Password Flow

1. **Request Password Reset**
   - Go to `/admin/login.php`
   - Click "Forgot password?" link
   - Enter username and email address
   - Click "Send Reset Link"
   - System generates token and sends email

2. **Check Email**
   - Look for email from configured "From Name"
   - Email contains:
     - Welcome message
     - "Reset Password" button with link
     - Direct link as backup
     - 1-hour expiry warning
     - Plain text and HTML versions

3. **Reset Password**
   - Click the reset link in email
   - Link format: `/admin/reset-password.php?token=<long-token-string>`
   - Verify URL token and check expiry
   - If valid, show form with:
     - Username (disabled, for reference)
     - New Password field (min 12 chars)
     - Confirm Password field
   - Enter new password (12+ characters)
   - Click "Reset Password"

4. **Verify Success**
   - System validates token, password, and confirmation
   - Updates admin_users.password_hash with bcrypt hash
   - Marks token as used (prevents reuse)
   - Logs action in audit_log
   - Shows success message
   - Redirects to login page

5. **Login with New Password**
   - Go to `/admin/login.php`
   - Login with new password
   - Verify successful authentication

### Phase 3: Security Tests

1. **Test Invalid/Expired Tokens**
   - Manually construct reset URL with invalid token
   - System should show: "Invalid or expired password reset link"
   - Should offer link to request new reset

2. **Test Token Expiry**
   - Generate reset token
   - Wait >1 hour
   - Try to use token
   - System should reject as expired

3. **Test Token Reuse Prevention**
   - Use token once to reset password
   - Try to use same token again
   - System should reject as already used

4. **Test Password Strength**
   - Try password < 12 characters
   - System should show: "Password must be at least 12 characters"
   - Try mismatched passwords
   - System should show: "Passwords do not match"

### Phase 4: Error Handling

1. **SMTP Not Configured**
   - Remove/clear SMTP credentials from database
   - Try to send password reset email
   - System should log error and show user-friendly message

2. **Email Send Failure**
   - Intentionally break SMTP config (wrong password)
   - Try to send test email
   - System should show error and log details

3. **Database Errors**
   - Check error logs for any database errors
   - All errors logged to PHP error log

## Database Verification

### Check email_config Table
```sql
SELECT * FROM email_config LIMIT 1;
```

Expected columns:
- id (always 1)
- smtp_host
- smtp_port
- smtp_encryption
- smtp_username
- smtp_password (stored as-is, no encryption in v1)
- from_address
- from_name
- updated_at (auto-updated on config change)

### Check password_reset_tokens Table
```sql
SELECT * FROM password_reset_tokens;
```

Expected columns:
- token_hash (SHA256 hash of token)
- admin_user_id (FK to admin_users)
- created_at (when token was generated)
- expires_at (creation time + 1 hour)
- used_at (NULL until token is used)

## Email Testing with Real SMTP

### Gmail Setup
1. Enable 2-Step Verification in Google Account
2. Go to [App Passwords](https://myaccount.google.com/apppasswords)
3. Select "Mail" and "Windows Computer"
4. Google generates 16-character password
5. Use this password in SMTP config (ignore spaces)

### Other Email Providers
- **Office 365**: smtp.office365.com:587:TLS
- **SendGrid**: smtp.sendgrid.net:587:TLS (username: apikey)
- **AWS SES**: email-smtp.[region].amazonaws.com:587:TLS
- **Custom Server**: Check provider documentation

## Troubleshooting

### "Failed to send test email"
- Check error logs: Check PHP error_log location
- Verify SMTP credentials are correct
- Test with different provider (Gmail recommended)
- Check firewall/network connectivity to SMTP host

### "Invalid or expired password reset link"
- Token may have expired (1 hour TTL)
- Request new reset link from forgot_password.php
- Check token_hash matches in database

### "SMTP not configured"
- Verify email_config table has SMTP settings
- Check from_address is not empty
- Ensure SMTP_USERNAME and SMTP_PASSWORD are set

### "Password must be at least 12 characters"
- This is intentional for security
- Use password manager to generate strong passwords
- Min 12 chars requirement can be adjusted in reset_password.php (line with strlen check)

## Files Modified/Created

### New Files
- `includes/email.php` - Email functions and SMTPMailer class (200+ lines)
- `admin/smtp_config.php` - SMTP configuration UI
- `admin/forgot_password.php` - Password reset request form
- `admin/reset_password.php` - Password reset form with token validation
- `database/migrations/002_add_email_tables.sql` - Database schema

### Modified Files
- `admin/login.php` - Added "Forgot password?" link
- `admin/dashboard.php` - Added "Email Settings" link
- `includes/bootstrap.php` - Fixed closing bracket (no new functions added)

## Performance Considerations

- Token generation uses `random_bytes(32)` for cryptographic security
- Tokens hashed with SHA256 before storage (one-way hash)
- Password hashed with PASSWORD_DEFAULT (bcrypt) before storage
- Email sending done synchronously (could be queued in future)
- 1-hour token expiry balances security and user experience

## Security Features

1. **Token Security**
   - Tokens use cryptographically secure random bytes
   - Hashed before storage (SHA256)
   - Expire after 1 hour
   - Can only be used once
   - Tied to specific admin user

2. **Password Security**
   - Minimum 12 characters
   - Hashed with bcrypt (PASSWORD_DEFAULT)
   - Salt generated automatically by bcrypt
   - Old password hash replaced (old session invalidates)

3. **Email Security**
   - Multipart MIME (both plain text and HTML)
   - Email addresses validated before sending
   - Audit logs all password reset actions
   - No sensitive info in email subject

4. **API Security**
   - CSRF tokens required on all POST forms
   - Admin authentication required for configuration
   - Session timeout: 30 minutes idle
   - All database queries use prepared statements

## Audit Trail

All password reset and SMTP configuration changes logged to `audit_logs` table:
- Action: `password_reset` (when token is used)
- Action: `update_email_config` (when SMTP settings saved)
- Includes admin user ID, timestamp, and what changed

## Future Enhancements

1. Email queue system (store unsent emails for retry)
2. Email templates system (customizable reset email)
3. Rate limiting on password reset requests
4. Password reset history
5. OAuth/SAML integration
6. Email verification for admin signup
7. Two-factor authentication via email
8. Bulk password reset for multiple admins

## Support & Documentation

For more information:
- See `docs/` folder for architecture documentation
- Check `database/schema.sql` for complete schema
- Review `includes/bootstrap.php` for helper functions
