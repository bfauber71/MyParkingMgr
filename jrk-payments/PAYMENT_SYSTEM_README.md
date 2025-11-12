# Payment System v2.0 - Implementation Guide

## Overview

ManageMyParking v2.0 introduces a comprehensive payment processing system supporting:
- **Multiple Payment Processors**: Stripe, Square, PayPal
- **QR Code Payment Links**: Auto-generated QR codes for easy mobile payments
- **Manual Payment Recording**: Cash, check, and manual card entry
- **Payment Tracking**: Full audit trail and reconciliation
- **Webhook Support**: Automated payment confirmation

## Installation

###  Step 1: Run Database Migration

1. Log into the application as an admin
2. Navigate to **Settings → Payments**
3. Click **"Install Payment System"** button
4. Wait for confirmation message

This creates the following tables:
- `payment_settings` - Payment processor configuration per property
- `ticket_payments` - Payment transaction records
- `qr_codes` - QR code file tracking
- Adds payment columns to `violation_tickets` table

### Step 2: Configure Payment Processor

1. In **Settings → Payments**, select your property
2. Choose a payment processor (Stripe, Square, or PayPal)
3. Enter your API credentials:
   - **Publishable Key**: Your public/publishable key
   - **Secret Key**: Your secret/private key (encrypted in database)
   - **Webhook Secret**: For payment confirmation (optional but recommended)
4. Toggle **Live Mode** when ready for production (leave unchecked for testing)
5. Enable/disable features:
   - **QR Code Generation**: Creates scannable payment links
   - **Online Payments**: Enables payment processor integration
6. Configure manual payment options (cash, check, manual card)
7. Click **Save Payment Settings**

## Payment Workflows

### 1. Online Payment with QR Code

**Workflow:**
1. Operator creates a violation ticket
2. System automatically generates payment link
3. QR code is created and stored
4. Ticket is printed with QR code (if enabled)
5. User scans QR code on ticket
6. User completes payment through payment processor
7. Webhook confirms payment
8. Ticket status automatically updated to "paid/closed"

**API Sequence:**
```
POST /api/payment-generate-link.php → Creates payment link
POST /api/payment-generate-qr.php → Generates QR code
POST /api/payment-webhook.php → Receives payment confirmation (automatic)
```

### 2. Manual Payment Recording

**Workflow:**
1. Operator searches for ticket
2. Clicks "Record Payment" button
3. Selects payment method (cash/check/card)
4. Enters amount and optional notes
5. System records payment
6. Ticket status updated based on amount paid

**Payment Status Logic:**
- `unpaid`: No payments recorded
- `partial`: Some payment received, balance remains
- `paid`: Full amount paid, ticket automatically closed

**API:**
```
POST /api/payment-record-manual.php
```

### 3. Payment History & Reconciliation

**Features:**
- View all payments for a ticket
- See payment summary (total fine, paid, balance)
- Track payment method and date
- View who recorded each payment

**API:**
```
GET /api/payment-history.php?ticket_id=123
```

## API Endpoints

### Payment Settings
```
GET  /api/payment-settings.php?property_id=1  - Get settings for property
POST /api/payment-settings.php               - Create/update settings
```

### Payment Links & QR Codes
```
POST /api/payment-generate-link.php
{
  "ticket_id": 123
}
→ Returns: { payment_link_url, payment_link_id, amount, processor }

POST /api/payment-generate-qr.php
{
  "ticket_id": 123,
  "payment_url": "https://pay.stripe.com/..."
}
→ Returns: { qr_code_path, qr_code_url }
```

### Manual Payments
```
POST /api/payment-record-manual.php
{
  "ticket_id": 123,
  "payment_method": "cash|check|card_manual",
  "amount": 50.00,
  "check_number": "1234" (if check),
  "notes": "Optional notes"
}
→ Returns: { payment_id, total_paid, payment_status, ticket_closed }
```

### Payment History
```
GET /api/payment-history.php?ticket_id=123
→ Returns: { payments: [...], summary: {...} }
```

### Webhooks
```
POST /api/payment-webhook.php
- Stripe webhook signature: HTTP_STRIPE_SIGNATURE
- Square webhook signature: HTTP_X_SQUARE_SIGNATURE
- Automatically records online payments
- Updates ticket status
```

## Database Schema

### payment_settings
| Field | Type | Description |
|-------|------|-------------|
| id | INT | Primary key |
| property_id | INT | FK to properties |
| processor_type | ENUM | stripe/square/paypal/disabled |
| api_key_encrypted | TEXT | Encrypted API key |
| api_secret_encrypted | TEXT | Encrypted secret |
| publishable_key | VARCHAR | Public key (not encrypted) |
| is_live_mode | BOOLEAN | Test vs live mode |
| enable_qr_codes | BOOLEAN | QR code generation enabled |
| enable_online_payments | BOOLEAN | Online payment enabled |

### ticket_payments
| Field | Type | Description |
|-------|------|-------------|
| id | INT | Primary key |
| ticket_id | INT | FK to violation_tickets |
| payment_method | ENUM | cash/check/card_manual/stripe_online/square_online |
| amount | DECIMAL(10,2) | Payment amount |
| payment_date | DATETIME | When payment received |
| check_number | VARCHAR | Check number (if applicable) |
| transaction_id | VARCHAR | Processor transaction ID |
| status | ENUM | pending/completed/failed/refunded |
| recorded_by_user_id | INT | User who recorded payment |

### violation_tickets (new columns)
| Field | Type | Description |
|-------|------|-------------|
| payment_status | ENUM | unpaid/partial/paid |
| amount_paid | DECIMAL(10,2) | Total amount paid |
| payment_link_id | VARCHAR | Payment link reference |
| qr_code_generated_at | DATETIME | QR code generation timestamp |

## Security Considerations

### API Key Encryption
API keys are encrypted using a simple XOR method in the demo. **For production:**

1. Install PHP Defuse Encryption library:
```bash
composer require defuse/php-encryption
```

2. Generate encryption key:
```php
use Defuse\Crypto\Key;
$key = Key::createNewRandomKey();
echo $key->saveToAsciiSafeString();
```

3. Add to config.php:
```php
define('PAYMENT_ENCRYPTION_KEY', 'your-generated-key-here');
```

4. Update payment-settings.php to use proper encryption

### Webhook Signature Verification

**For Stripe:**
```php
\Stripe\Stripe::setApiKey($secretKey);
$event = \Stripe\Webhook::constructEvent(
    $payload, $sig_header, $webhook_secret
);
```

**For Square:**
```php
use Square\Utils\WebhooksHelper;
$isValid = WebhooksHelper::isValidWebhookEventSignature(
    $payload, $sig_header, $webhook_secret, $notification_url
);
```

## Payment Processor Setup

### Stripe Configuration

1. Create Stripe account at https://stripe.com
2. Get API keys from Dashboard → Developers → API keys
3. Test Mode:
   - Publishable key: `pk_test_...`
   - Secret key: `sk_test_...`
4. Configure webhook endpoint:
   - URL: `https://yourdomain.com/api/payment-webhook.php`
   - Events: `checkout.session.completed`, `payment_intent.succeeded`
   - Copy webhook secret: `whsec_...`

**Test Cards:**
- Success: `4242 4242 4242 4242`
- Decline: `4000 0000 0000 0002`

### Square Configuration

1. Create Square account at https://squareup.com
2. Get credentials from Developer Dashboard
3. Sandbox Mode:
   - Application ID
   - Access Token
4. Configure webhook:
   - URL: `https://yourdomain.com/api/payment-webhook.php`
   - Events: `payment.created`, `payment.updated`

### PayPal Configuration

1. Create PayPal Business account
2. Get credentials from Developer Portal
3. Sandbox credentials for testing
4. Configure IPN/webhooks

## Frontend Integration (TODO)

The following JavaScript functions need to be implemented in `app-secure.js`:

### Payment Settings
```javascript
async function loadPaymentSettings(propertyId) {
    // Load settings for property
}

async function savePaymentSettings() {
    // Save payment configuration
}

function togglePaymentProcessorFields() {
    // Show/hide API key fields based on processor
}
```

### Payment Recording
```javascript
async function openRecordPaymentModal(ticketId) {
    // Open payment modal with ticket info
}

async function recordPayment(formData) {
    // Submit payment to API
}

async function loadPaymentHistory(ticketId) {
    // Load and display payment history
}
```

### UI Updates
```javascript
function addPaymentStatusColumn() {
    // Add payment status to ticket lists
}

function displayPaymentBadge(status, amount_paid, total_fine) {
    // Show payment status badges
}
```

## Testing Checklist

- [ ] Database migration runs successfully
- [ ] Payment settings save and load correctly
- [ ] Manual cash payment records properly
- [ ] Manual check payment requires check number
- [ ] Payment history displays correctly
- [ ] Partial payments calculate balance correctly
- [ ] Full payment auto-closes ticket
- [ ] QR code generation works
- [ ] Stripe test payment completes
- [ ] Webhook updates ticket status
- [ ] Payment status shows in ticket lists
- [ ] Property-specific settings work correctly

## Troubleshooting

### "Payment system not installed" error
- Run the database migration from Settings → Payments
- Check MySQL connection
- Verify migration script ran successfully

### QR code not generating
- Check that `qrcodes/` directory exists and is writable
- Verify internet connection (uses Google Charts API)
- Check error logs in browser console

### Webhook not working
- Verify webhook URL is publicly accessible
- Check webhook secret matches payment processor
- Review server error logs
- Test webhook with processor's testing tools

### Payment not recording
- Check user has proper permissions
- Verify ticket exists
- Check amount is greater than 0
- Review browser console for errors

## File Structure

```
jrk-payments/
├── api/
│   ├── migrate-payment-system.php      # Database migration
│   ├── payment-settings.php            # CRUD for settings
│   ├── payment-record-manual.php       # Manual payment recording
│   ├── payment-history.php             # Payment history
│   ├── payment-generate-link.php       # Create payment links
│   ├── payment-generate-qr.php         # Generate QR codes
│   └── payment-webhook.php             # Webhook handler
├── qrcodes/                            # QR code storage (auto-created)
├── database/
│   └── migrations/
│       └── 002-payment-system.sql      # SQL migration script
└── index.html                          # UI with Payments tab
```

## Support

For issues or questions:
1. Check error logs in browser console and server logs
2. Review this documentation
3. Test with payment processor's sandbox/test mode first
4. Verify all prerequisites are met

## Version History

- **v2.0.0** - Initial payment system release
  - Multi-processor support (Stripe, Square, PayPal)
  - QR code generation
  - Manual payment recording
  - Webhook integration
  - Payment history and reconciliation
