# Payment System v2.0 - Complete Testing Guide

## Prerequisites

### Required Environment
Since Replit doesn't support MySQL reliably, testing must be done on one of these environments:

#### Option 1: Shared Hosting (Recommended - Target Environment)
- cPanel with MySQL 5.7+ or MariaDB 10.2+
- PHP 7.4+ (ideally 8.0+)
- FTP/SFTP access
- phpMyAdmin access

#### Option 2: Local Development (XAMPP/MAMP/WAMP)
- Windows: XAMPP or WAMP
- Mac: MAMP or Laravel Valet
- Linux: LAMP stack

#### Option 3: Docker (Advanced)
```bash
docker run -d --name mysql-test -p 3306:3306 \
  -e MYSQL_ROOT_PASSWORD=root \
  -e MYSQL_DATABASE=parking_dev \
  mysql:8.0
```

### Required Accounts for Testing
- **Stripe Test Account**: https://dashboard.stripe.com/test (free)
- **Test Card**: 4242 4242 4242 4242 (Exp: any future date, CVC: any 3 digits)

---

## Installation & Setup

### Step 1: Deploy to MySQL Environment

1. **Upload Files via FTP/cPanel File Manager**
   ```
   Upload entire jrk-payments/ directory to:
   public_html/jrk-payments/
   ```

2. **Create MySQL Database**
   - Login to cPanel → MySQL Databases
   - Create database: `parking_dev` (or your preferred name)
   - Create user: `parking_user` with strong password
   - Grant ALL privileges to user on database

3. **Configure Database Connection**
   - Edit `jrk-payments/config.php`
   - Update database settings:
   ```php
   'db' => [
       'host' => 'localhost',
       'port' => '3306',
       'database' => 'parking_dev',
       'username' => 'parking_user',
       'password' => 'your_secure_password',
       'charset' => 'utf8mb4',
       'unix_socket' => '',  // Leave empty for shared hosting
   ],
   ```

4. **Set Permissions** (if using cPanel, usually automatic)
   ```bash
   chmod 755 jrk-payments/
   chmod 644 jrk-payments/*.php
   chmod 755 jrk-payments/qrcodes/  # Create if not exists
   ```

### Step 2: Install Core System Database

1. Access your application: `https://yourdomain.com/jrk-payments/`
2. Login with default credentials (from v1.1 setup)
3. Navigate to: **Settings → Database Operations**
4. Run core database migrations if needed

### Step 3: Install Payment System

1. While logged in as admin, go to: **Settings → Payments**
2. Click **"Install Payment System"** button
3. Wait for success message:
   ```
   ✓ Payment system installed successfully!
   ✓ Created tables: payment_settings, ticket_payments, qr_codes
   ✓ Updated violation_tickets table with payment columns
   ```

4. **Verify Installation** via phpMyAdmin:
   - Check for new tables:
     - `payment_settings`
     - `ticket_payments`
     - `qr_codes`
   - Check `violation_tickets` has new columns:
     - `payment_status`
     - `amount_paid`
     - `payment_link_id`
     - `qr_code_generated_at`

---

## Configuration Testing

### Test 1: Payment Processor Setup (Stripe)

**Objective:** Configure Stripe payment processor for a property

1. **Get Stripe Test Credentials**
   - Go to: https://dashboard.stripe.com/test/apikeys
   - Copy **Publishable key** (starts with `pk_test_`)
   - Copy **Secret key** (starts with `sk_test_`)

2. **Configure in Application**
   - Navigate to: **Settings → Payments**
   - Select a property from dropdown
   - Choose **Processor**: Stripe
   - **Uncheck** "Live Mode" (use test mode)
   - Enter **Publishable Key**: `pk_test_XXXXXXX`
   - Enter **Secret Key**: `sk_test_XXXXXXX`
   - **Check** "Enable QR Code Generation"
   - **Check** "Enable Online Payments"
   - Click **Save Payment Settings**

3. **Expected Result:**
   ✓ Toast message: "Payment settings saved successfully"
   ✓ Secret key field clears (security feature)
   ✓ Settings persist when you reload the page

4. **Verify in Database** (phpMyAdmin):
   ```sql
   SELECT * FROM payment_settings WHERE property_id = 1;
   ```
   - `processor_type` should be 'stripe'
   - `is_live_mode` should be 0
   - `publishable_key` should contain your pk_test key
   - `api_key_encrypted` should NOT be empty
   - `enable_qr_codes` should be 1
   - `enable_online_payments` should be 1

### Test 2: Manual Payment Options

1. **Configure Manual Payments**
   - In **Settings → Payments**
   - Check all three options:
     - ✓ Allow Cash Payments
     - ✓ Allow Check Payments
     - ✓ Allow Manual Card Entry
   - Check "Require Check Number for Check Payments"
   - Click **Save Payment Settings**

2. **Expected Result:**
   ✓ Settings saved successfully
   ✓ All manual payment methods available in payment modal

---

## Manual Payment Testing

### Test 3: Cash Payment Recording

**Setup:**
1. Create a test violation ticket with a fine (e.g., $50)
2. Go to **Violations Search** or **Ticket Status** tab
3. Find your test ticket

**Test Procedure:**
1. Click **"💰 Payment"** button on the ticket
2. **Verify Modal Opens** with ticket information:
   - Ticket ID displayed
   - Plate number shown
   - Total Fine: $50.00
   - Paid: $0.00
   - Balance Due: $50.00 (in yellow/orange)

3. **Record Cash Payment:**
   - Payment Method: **Cash**
   - Amount: **$25.00** (partial payment)
   - Notes: "Test partial payment"
   - Click **"Record Payment"**

4. **Expected Results:**
   ✓ Toast: "Payment recorded successfully. Status: partial"
   ✓ Modal closes
   ✓ Ticket list refreshes
   ✓ Payment status badge shows: **PARTIAL ($25.00)** in yellow/orange

5. **Verify in Database:**
   ```sql
   SELECT * FROM ticket_payments WHERE ticket_id = 'your_ticket_id';
   ```
   - `payment_method` = 'cash'
   - `amount` = 25.00
   - `status` = 'completed'
   - `recorded_by_user_id` = your user ID

   ```sql
   SELECT payment_status, amount_paid FROM violation_tickets 
   WHERE id = 'your_ticket_id';
   ```
   - `payment_status` = 'partial'
   - `amount_paid` = 25.00

6. **Complete Payment:**
   - Click **"💰 Payment"** again
   - Modal now shows:
     - Total Fine: $50.00
     - Paid: $25.00
     - Balance Due: $25.00
   - Payment Method: **Cash**
   - Amount: **$25.00**
   - Click **"Record Payment"**

7. **Expected Results:**
   ✓ Toast: "Payment recorded successfully. Status: paid"
   ✓ Payment badge shows: **PAID** in green
   ✓ Ticket status automatically changed to "CLOSED"
   ✓ Fine disposition set to "collected"

### Test 4: Check Payment Recording

1. **Create New Test Ticket** ($75 fine)
2. Click **"💰 Payment"**
3. **Record Check Payment:**
   - Payment Method: **Check**
   - Amount: **$75.00**
   - Check Number: **1234** (required field appears)
   - Notes: "Check payment test"
   - Click **"Record Payment"**

4. **Expected Results:**
   ✓ Payment recorded with check number
   ✓ Ticket marked as paid
   ✓ Ticket automatically closed

5. **Test Check Number Validation:**
   - Create another ticket
   - Try to record check payment WITHOUT check number
   - **Expected:** Error message "Check number is required for check payments"

### Test 5: Payment History

1. **Open Payment History:**
   - Find a ticket with multiple payments
   - Click **"📜 History"** button

2. **Verify Payment History Modal:**
   - **Payment Summary** shows:
     - Total Fine: $50.00
     - Total Paid: $50.00
     - Balance Due: $0.00
     - Payments Count: 2
   
   - **Payment List Table** shows:
     - Date/time of each payment
     - Payment method (Cash, Check #1234)
     - Amount ($25.00, $25.00)
     - Status (completed)
     - Notes

3. **Expected Results:**
   ✓ All payments displayed in chronological order
   ✓ Summary calculations correct
   ✓ Payment details accurate

---

## Online Payment Testing (Stripe)

### Test 6: Payment Link Generation

**Prerequisites:** Stripe configured with test keys

1. **Generate Payment Link:**
   - Create a test ticket ($100 fine)
   - Using the API endpoint (via Postman or browser):
   ```
   POST /api/payment-generate-link.php
   {
     "ticket_id": "your_ticket_id"
   }
   ```

2. **Expected API Response:**
   ```json
   {
     "success": true,
     "payment_link_url": "https://checkout.stripe.com/c/pay/...",
     "payment_link_id": "plink_xxxxx",
     "amount": 100.00,
     "processor": "stripe"
   }
   ```

3. **Verify Database:**
   ```sql
   SELECT payment_link_id FROM violation_tickets 
   WHERE id = 'your_ticket_id';
   ```
   - Should contain Stripe payment link ID

### Test 7: QR Code Generation

1. **Generate QR Code:**
   ```
   POST /api/payment-generate-qr.php
   {
     "ticket_id": "your_ticket_id",
     "payment_url": "https://checkout.stripe.com/c/pay/..."
   }
   ```

2. **Expected Response:**
   ```json
   {
     "success": true,
     "qr_code_path": "qrcodes/qr_ticket_xxxxx_timestamp.png",
     "qr_code_url": "/jrk-payments/qrcodes/qr_ticket_xxxxx_timestamp.png"
   }
   ```

3. **Verify QR Code File:**
   - Check `jrk-payments/qrcodes/` directory
   - QR code image file exists
   - Image opens and displays QR code

4. **Verify Database:**
   ```sql
   SELECT * FROM qr_codes WHERE ticket_id = 'your_ticket_id';
   ```
   - Record exists with file path

### Test 8: Complete Stripe Payment

1. **Access Payment Link:**
   - Copy the `payment_link_url` from Test 6
   - Open in browser (or scan QR code)

2. **Complete Payment:**
   - Stripe Checkout page loads
   - Enter test card: `4242 4242 4242 4242`
   - Expiry: Any future date (e.g., 12/25)
   - CVC: Any 3 digits (e.g., 123)
   - Email: test@example.com
   - Click **"Pay"**

3. **Expected Results:**
   ✓ Payment succeeds
   ✓ Stripe redirects to success page
   ✓ Webhook sent to your application

### Test 9: Webhook Processing

**Setup Webhook in Stripe Dashboard:**

1. Go to: https://dashboard.stripe.com/test/webhooks
2. Click **"Add endpoint"**
3. Endpoint URL: `https://yourdomain.com/jrk-payments/api/payment-webhook.php`
4. Events to send:
   - `checkout.session.completed`
   - `payment_intent.succeeded`
5. Copy **Signing secret** (starts with `whsec_`)

6. **Update Payment Settings:**
   - Go to **Settings → Payments**
   - Enter Webhook Secret
   - Save settings

**Test Webhook:**

1. Complete a Stripe payment (Test 8)
2. **Expected Automatic Results:**
   ✓ Payment recorded in `ticket_payments` table
   ✓ `payment_method` = 'stripe_online'
   ✓ `transaction_id` contains Stripe payment ID
   ✓ Ticket `payment_status` = 'paid'
   ✓ Ticket automatically closed
   ✓ No duplicate payments (idempotency check)

3. **Verify in Database:**
   ```sql
   SELECT * FROM ticket_payments 
   WHERE ticket_id = 'your_ticket_id' 
   AND payment_method = 'stripe_online';
   ```

4. **Test Duplicate Prevention:**
   - Manually replay the webhook (Stripe Dashboard → Webhooks → Event → Resend)
   - **Expected:** No duplicate payment created
   - Check database: Still only 1 payment record

---

## UI/UX Testing

### Test 10: Payment Status Display

1. **Violation Search:**
   - Search for violations
   - **Verify Payment Column** shows badges:
     - **UNPAID** (red) for $0 paid
     - **PARTIAL ($XX.XX)** (yellow) for partial payment
     - **PAID** (green) for fully paid

2. **Ticket Status Screen:**
   - View active tickets
   - **Verify Payment Column** appears with same badges
   - **Verify Payment Buttons** appear:
     - "💰 Payment" button for unpaid/partial tickets
     - "📜 History" button for all tickets

3. **Payment Modal UX:**
   - Open payment modal
   - **Verify:**
     - ✓ Ticket info clearly displayed
     - ✓ Balance due prominently shown in yellow
     - ✓ Amount field pre-fills with balance
     - ✓ Check number field only appears for check payments
     - ✓ Form validation works
     - ✓ Cancel button closes modal

---

## Edge Cases & Error Handling

### Test 11: Error Scenarios

1. **Overpayment Test:**
   - Ticket fine: $50
   - Try to record payment: $100
   - **Expected:** Payment allowed (overpayment possible for change)
   - Verify: `amount_paid` = $100, status = 'paid'

2. **Zero Amount Test:**
   - Try to record $0 payment
   - **Expected:** Error "Amount must be greater than 0"

3. **Invalid Stripe Keys:**
   - Enter fake Stripe keys
   - Try to generate payment link
   - **Expected:** Error message from Stripe API

4. **Database Connection Error:**
   - Temporarily break database config
   - **Expected:** User-friendly error message
   - **Not Expected:** SQL errors exposed to user

5. **Concurrent Payment Test:**
   - Open two browser tabs
   - Record payment in both simultaneously
   - **Expected:** Both payments recorded (no transaction conflicts)

---

## Security Testing

### Test 12: Authentication & Authorization

1. **Login Required:**
   - Logout
   - Try to access `/api/payment-settings.php`
   - **Expected:** 401 Unauthorized or redirect to login

2. **Permission Check:**
   - Login as non-admin user
   - Try to access payment settings
   - **Expected:** Permission denied (depending on your RBAC settings)

3. **CSRF Protection:**
   - Submit payment without CSRF token
   - **Expected:** 403 Forbidden

### Test 13: Data Validation

1. **SQL Injection Test:**
   - Try payment amount: `50'; DROP TABLE ticket_payments; --`
   - **Expected:** Sanitized/escaped, no SQL injection

2. **XSS Test:**
   - Payment notes: `<script>alert('XSS')</script>`
   - **Expected:** Script tags escaped in display

3. **API Key Encryption:**
   - Check `payment_settings` table
   - Verify `api_secret_encrypted` is NOT plaintext
   - **Note:** Current implementation uses simple XOR encryption
   - **Production:** Upgrade to Defuse PHP Encryption before go-live

---

## Performance Testing

### Test 14: Load Testing

1. **Multiple Payments:**
   - Record 50+ payments on different tickets
   - **Expected:** No slowdown, all recorded successfully

2. **Payment History:**
   - View payment history for ticket with 20+ payments
   - **Expected:** Loads quickly, all payments displayed

3. **QR Code Generation:**
   - Generate 10 QR codes rapidly
   - **Expected:** All created successfully, no timeouts

---

## Integration Testing

### Test 15: End-to-End Workflow

**Scenario:** Complete parking violation fine payment flow

1. **Create Violation** ($100 fine)
2. **Record Partial Cash Payment** ($30)
   - Verify: Status = PARTIAL
   - Verify: Balance = $70
   - Verify: Ticket still ACTIVE

3. **Record Check Payment** ($40, Check #5678)
   - Verify: Status = PARTIAL
   - Verify: Total paid = $70
   - Verify: Balance = $30

4. **Complete via Stripe:**
   - Generate payment link for $30
   - Generate QR code
   - Complete Stripe payment
   - Verify: Webhook processes payment
   - Verify: Status = PAID
   - Verify: Ticket CLOSED
   - Verify: Total paid = $100

5. **View Payment History:**
   - Should show 3 payments:
     - Cash $30
     - Check #5678 $40
     - Stripe $30
   - Total paid: $100.00
   - Balance: $0.00

---

## Troubleshooting Guide

### Common Issues

#### Payment System Won't Install
- **Symptom:** "Installation failed" error
- **Check:**
  - Database connection (config.php)
  - User has CREATE TABLE permissions
  - No existing payment tables (drop old ones)
- **Solution:** Check error logs, verify MySQL permissions

#### Payment Not Recording
- **Symptom:** "Failed to record payment" error
- **Check:**
  - Browser console for JavaScript errors
  - PHP error logs
  - Database connection
- **Solution:** Check API response in Network tab

#### Stripe Payment Link Fails
- **Symptom:** "Error generating payment link"
- **Check:**
  - Stripe API keys correct (start with pk_test_, sk_test_)
  - Test mode enabled
  - Ticket has valid fine amount
- **Solution:** Test API keys directly in Stripe Dashboard

#### Webhook Not Working
- **Symptom:** Payments complete in Stripe but not recorded in app
- **Check:**
  - Webhook URL accessible (not localhost)
  - Webhook secret matches Stripe dashboard
  - Check Stripe webhook logs for errors
- **Solution:** Use Stripe CLI for local webhook testing

#### QR Code Not Generating
- **Symptom:** "Error generating QR code"
- **Check:**
  - `qrcodes/` directory exists and writable (chmod 755)
  - Internet connection (uses Google Charts API)
  - Payment link URL is valid
- **Solution:** Create directory, check permissions

---

## Testing Checklist

Use this checklist to track your testing progress:

### Installation
- [ ] Files uploaded to hosting
- [ ] MySQL database created
- [ ] config.php configured
- [ ] Core system database installed
- [ ] Payment system installed

### Configuration
- [ ] Stripe test account created
- [ ] Payment processor configured
- [ ] Manual payment options enabled
- [ ] Settings persist correctly

### Manual Payments
- [ ] Cash payment recorded
- [ ] Partial payment recorded
- [ ] Full payment recorded and ticket closed
- [ ] Check payment with check number
- [ ] Payment history displays correctly

### Online Payments
- [ ] Payment link generated
- [ ] QR code created and accessible
- [ ] Stripe payment completes successfully
- [ ] Webhook processes payment automatically
- [ ] Duplicate payment prevention works

### UI/UX
- [ ] Payment status badges display correctly
- [ ] Payment buttons appear in correct places
- [ ] Payment modal shows accurate information
- [ ] Form validation works properly
- [ ] Error messages are user-friendly

### Security
- [ ] Authentication required for all payment APIs
- [ ] SQL injection prevented
- [ ] XSS attacks blocked
- [ ] API keys encrypted in database
- [ ] CSRF protection active

### Performance
- [ ] Multiple payments handled efficiently
- [ ] Payment history loads quickly
- [ ] No timeouts or errors under load

---

## Production Deployment Checklist

Before going live with real money:

### Critical Security Updates
- [ ] Replace XOR encryption with Defuse PHP Encryption
  ```bash
  composer require defuse/php-encryption
  ```
- [ ] Generate secure encryption key
- [ ] Update payment-settings.php to use proper encryption

### Stripe Live Mode
- [ ] Switch to live API keys (pk_live_, sk_live_)
- [ ] Enable "Live Mode" toggle in settings
- [ ] Update webhook URL to production domain
- [ ] Test with real (low amount) transaction

### Server Configuration
- [ ] HTTPS enabled (required for Stripe)
- [ ] SSL certificate valid
- [ ] Error logging configured
- [ ] Backup system in place

### Final Verification
- [ ] All test data removed
- [ ] Payment flow tested end-to-end
- [ ] Webhook receiving real events
- [ ] Payment receipts/confirmations working
- [ ] Support process in place for payment issues

---

## Support & Debugging

### Logs to Check

1. **PHP Error Logs:**
   - cPanel: Error Log viewer
   - File: `/home/user/public_html/error_log`

2. **Browser Console:**
   - Press F12 → Console tab
   - Check for JavaScript errors

3. **Network Tab:**
   - F12 → Network tab
   - Check API responses

4. **Stripe Dashboard:**
   - Logs → Events
   - Webhooks → Your endpoint → Recent deliveries

### Common Error Messages

| Error | Cause | Solution |
|-------|-------|----------|
| "Payment system not installed" | Migration not run | Run migration from Settings → Payments |
| "Failed to load payment settings" | No settings for property | Configure payment processor |
| "Invalid API key" | Wrong Stripe keys | Verify keys in Stripe Dashboard |
| "Check number required" | Missing check number | Fill in check number field |
| "Database connection failed" | Wrong config.php | Check database credentials |

---

## Conclusion

This comprehensive testing guide covers all aspects of the Payment System v2.0. Follow each test methodically to ensure the system works correctly in your production environment.

**Remember:**
- Testing MUST be done on a system with MySQL (not Replit)
- Use Stripe TEST mode until you're confident everything works
- Keep detailed records of test results
- Report any issues found during testing

For questions or issues, refer to `PAYMENT_SYSTEM_README.md` for additional documentation.
