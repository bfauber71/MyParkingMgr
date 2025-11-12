# MyParkingManager License System Guide

## Overview
MyParkingManager includes a subscription-based licensing system that provides:
- 30-day free trial from initial installation
- License key activation for continued access after trial
- Cryptographically signed license keys
- Feature access control based on license status

## How It Works

### Trial Period
- **Duration:** 30 days from installation
- **Features:** All features available during trial
- **Warning:** Shows notification when 7 days or less remain
- **Expiration:** Premium features blocked after trial ends

### License Keys
- **Format:** `XXXX-XXXX-XXXX-XXXX-XXXX-XXXX-XXXX-XXXX` (32 characters)
- **Security:** HMAC-SHA256 signed with secret key
- **Types:**
  - Installation-specific: Works only for a specific installation ID
  - Universal: Works for any installation

### Feature Restrictions (After Trial)
**Always Available:**
- Login/Logout
- License activation
- View profile
- Basic navigation

**Requires License:**
- Vehicle management (add/edit/delete)
- Violation system
- Property management
- User management
- Data export
- Bulk operations
- Reports & analytics

## Administrator Guide

### Generating License Keys

#### Secure Method (Recommended)
```bash
# Generate universal key (works on any installation)
php generate-license-key-secure.php customer@email.com universal

# Generate installation-specific key
php generate-license-key-secure.php customer@email.com <install-id>

# Generate universal key (default if no install ID)
php generate-license-key-secure.php customer@email.com
```

**Output includes:**
- License key
- Email template for customer
- Local JSON record in `/licenses` directory
- Database entry (if connected)

### Managing Licenses

#### Database Tables
- `license_instances` - Tracks installations and their license status
- `license_attempts` - Logs validation attempts (security)
- `license_audit` - Audit trail of license changes
- `license_keys_issued` - Tracks all issued keys

#### Key Security
- **Secret Key:** Located in `includes/license-keys.php`
- **NEVER** expose the SECRET_KEY to clients
- Store issued keys securely
- Keys are one-time use per installation

### Monitoring
```sql
-- Check license status for all installations
SELECT install_id, status, trial_expires_at, activated_at 
FROM license_instances;

-- View recent activation attempts
SELECT * FROM license_attempts 
WHERE attempted_at > DATE_SUB(NOW(), INTERVAL 1 DAY);

-- Check issued keys
SELECT key_prefix, customer_email, issued_at, is_active 
FROM license_keys_issued;
```

## Customer Guide

### Checking License Status
1. Login as admin
2. Navigate to License section
3. View current status:
   - Trial (with days remaining)
   - Licensed (activated)
   - Expired (trial ended)

### Activating a License
1. Obtain license key from vendor
2. Login as admin
3. Go to License section
4. Enter the license key
5. Optional: Enter email for recovery
6. Click "Activate License"

### Troubleshooting

#### "Invalid license key format"
- Check for typos
- Ensure all characters are included
- Format: 8 groups of 4 characters separated by dashes

#### "License key not valid for this installation"
- Key may be for different installation
- Contact vendor for correct key

#### "Too many attempts"
- Wait 1 hour before trying again
- Maximum 5 attempts per hour

## Developer Guide

### File Structure
```
includes/
  license.php           # Main license management
  license-keys.php      # Key generation/validation
  middleware.php        # Request filtering

api/
  license-status.php    # GET /api/license-status
  license-activate.php  # POST /api/license-activate

sql/
  migrate-license-system.sql  # Database schema
  add-license-keys-table.sql  # Keys tracking table

public/
  license.html          # License management UI

generate-license-key-secure.php  # CLI key generator
```

### API Endpoints

#### GET /api/license-status
Returns current license status:
```json
{
  "success": true,
  "license": {
    "status": "trial",
    "is_valid": true,
    "days_remaining": 15,
    "expires_at": "2024-11-15 10:30:00"
  }
}
```

#### POST /api/license-activate
Activates a license key (admin only):
```json
{
  "license_key": "XXXX-XXXX-...",
  "email": "customer@email.com"
}
```

### Integration Points

#### Middleware Check
In `index.php`:
```php
checkLicenseAccess(); // Blocks restricted endpoints
```

#### Feature Check
In any API endpoint:
```php
requireLicenseForFeature('vehicles_manage');
```

#### Manual Check
```php
if (License::hasFeatureAccess('export_data')) {
    // Allow export
}
```

### Security Considerations

1. **Secret Key Protection**
   - Never commit SECRET_KEY to version control
   - Use environment variables in production
   - Rotate keys periodically

2. **Key Generation**
   - Use cryptographically secure random generation
   - HMAC signatures prevent tampering
   - Keys tied to installation ID

3. **Rate Limiting**
   - Maximum 5 validation attempts per hour
   - Prevents brute force attacks
   - Logs all attempts for monitoring

4. **Database Security**
   - License keys stored as hashes
   - Sensitive data encrypted
   - Audit trail for all changes

## Migration Guide

### For Existing Installations
When migrating existing installations to the license system:

1. Run migration script:
```sql
SOURCE sql/migrate-license-system.sql;
SOURCE sql/add-license-keys-table.sql;
```

2. Existing installations get 7-day grace period
3. Generate and distribute license keys to customers
4. Monitor activation rates

### Deployment Checklist
- [ ] Update SECRET_KEY in production
- [ ] Run database migrations
- [ ] Test key generation
- [ ] Test activation process
- [ ] Verify feature restrictions work
- [ ] Set up monitoring alerts
- [ ] Document customer support process

## Support Procedures

### Common Issues

#### Reset Failed Attempts
```sql
DELETE FROM license_attempts 
WHERE install_id = 'xxx' 
AND attempted_at < DATE_SUB(NOW(), INTERVAL 1 HOUR);
```

#### Extend Trial
```sql
UPDATE license_instances 
SET trial_expires_at = DATE_ADD(NOW(), INTERVAL 7 DAY)
WHERE install_id = 'xxx';
```

#### Revoke License
```sql
UPDATE license_keys_issued 
SET is_active = FALSE, revoked_at = NOW()
WHERE key_prefix = 'XXXX-XXXX';
```

## Testing

### Test License Flow
1. Install fresh system (starts 30-day trial)
2. Check license status shows trial
3. Manually expire trial in database
4. Verify features are blocked
5. Generate test license key
6. Activate license
7. Verify features are unlocked

### Test Commands
```bash
# Generate test key
php generate-license-key-secure.php test@example.com

# Check system status
php test-license-system.php
```

## Version History
- v2.0 - Initial implementation with 30-day trial and key activation
- v2.1 - Added cryptographic key validation (current)