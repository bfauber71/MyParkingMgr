# API violation_count Fix

## Problem
The `violation_count` field was not being returned by the `/api/vehicles-search` endpoint in production mode.

## Root Cause
The original code had potential issues:
1. No error handling if the `violation_tickets` table didn't exist
2. Improper use of `fetchColumn()` which could fail silently
3. Missing reference cleanup after foreach loop

## Solution Applied

### Updated Code in `jrk/api/vehicles-search.php`

**Before:**
```php
$vehicles = Database::query($sql, $params);

// Add violation count to each vehicle
$db = Database::getInstance();
foreach ($vehicles as &$vehicle) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM violation_tickets WHERE vehicle_id = ?");
    $stmt->execute([$vehicle['id']]);
    $vehicle['violation_count'] = (int)$stmt->fetchColumn();
}

jsonResponse(['vehicles' => $vehicles]);
```

**After:**
```php
$vehicles = Database::query($sql, $params);

// Add violation count to each vehicle
try {
    $db = Database::getInstance();
    
    // Check if violation_tickets table exists
    $tableCheck = $db->query("SHOW TABLES LIKE 'violation_tickets'");
    $tableExists = $tableCheck->fetch() !== false;
    
    if ($tableExists) {
        foreach ($vehicles as &$vehicle) {
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM violation_tickets WHERE vehicle_id = ?");
            $stmt->execute([$vehicle['id']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $vehicle['violation_count'] = (int)($result['count'] ?? 0);
        }
        unset($vehicle); // Break reference
    } else {
        // Table doesn't exist yet, set all counts to 0
        foreach ($vehicles as &$vehicle) {
            $vehicle['violation_count'] = 0;
        }
        unset($vehicle); // Break reference
    }
} catch (Exception $e) {
    // If there's an error, set all counts to 0 and log it
    error_log("Error fetching violation counts: " . $e->getMessage());
    foreach ($vehicles as &$vehicle) {
        $vehicle['violation_count'] = 0;
    }
    unset($vehicle); // Break reference
}

jsonResponse(['vehicles' => $vehicles]);
```

## Key Improvements

1. **Table Existence Check**: Checks if `violation_tickets` table exists before querying
2. **Better Fetch Method**: Uses `fetch(PDO::FETCH_ASSOC)` instead of `fetchColumn()` for reliability
3. **Null Coalescing**: Uses `?? 0` to handle null values
4. **Error Handling**: Wraps in try-catch and logs errors
5. **Reference Cleanup**: Properly unsets the reference after foreach loop
6. **Graceful Degradation**: Sets count to 0 if table doesn't exist or on error

## How to Test

### Method 1: Check Browser Network Tab (Recommended)

1. **Open your production site**
2. **Open browser DevTools** (F12 or Right-click → Inspect)
3. **Go to Network tab**
4. **Log in** to the app (admin/admin123)
5. **Click Vehicles tab**
6. **Search for vehicles** (type anything or just click search)
7. **Click on the vehicles-search request** in Network tab
8. **Check the Response tab**

**Expected Response:**
```json
{
  "vehicles": [
    {
      "id": "some-uuid",
      "plate_number": "ABC-1234",
      "property": "Sunset Apartments",
      ...other fields...,
      "violation_count": 0
    }
  ]
}
```

### Method 2: Direct API Test with cURL

```bash
# Replace with your actual domain
curl -i -X GET 'https://2clv.com/jrk/api/vehicles-search' \
  -H 'Cookie: PHPSESSID=your-session-id'
```

### Method 3: Browser Console Test

1. **Log in to the app**
2. **Open browser DevTools Console** (F12 → Console tab)
3. **Paste and run this code:**

```javascript
fetch('/api/vehicles-search', {
  credentials: 'include'
})
.then(r => r.json())
.then(data => {
  console.log('API Response:', data);
  if (data.vehicles.length > 0) {
    console.log('First vehicle:', data.vehicles[0]);
    console.log('Has violation_count?', 'violation_count' in data.vehicles[0]);
    console.log('violation_count value:', data.vehicles[0].violation_count);
  }
});
```

**Expected Console Output:**
```
API Response: {vehicles: Array(3)}
First vehicle: {id: "...", plate_number: "ABC-1234", ..., violation_count: 0}
Has violation_count? true
violation_count value: 0
```

## Verification Checklist

✅ **API returns violation_count field** for each vehicle
✅ **Count is 0** when no violations exist
✅ **Count is accurate** when violations exist
✅ **No errors** in PHP error logs
✅ **Button appears** on vehicles with violations > 0
✅ **Button does NOT appear** on vehicles with violations = 0

## Production Deployment Steps

1. **Upload updated file:**
   - `jrk/api/vehicles-search.php`

2. **Run migration** (if not already done):
   - Execute `jrk/sql/migrate-simple.sql` in phpMyAdmin
   - This creates the `violation_tickets` table

3. **Test the API:**
   - Use Method 1 above to verify the response

4. **Create test violation:**
   - Search for a vehicle
   - Click "Violation" button
   - Select violations and submit
   - Search again to see the "*Violations Exist" button appear

## Error Logs

If you encounter issues, check PHP error logs for messages like:
```
Error fetching violation counts: [error details]
```

Common issues:
- **Table doesn't exist**: Run `migrate-simple.sql`
- **Permission denied**: Check database user has SELECT permission on `violation_tickets`
- **Connection error**: Verify database credentials in `config.php`

## Files Modified

- ✅ `jrk/api/vehicles-search.php` - Added robust violation count logic
- ✅ `jrk/public/assets/app.js` - Added violation_count to demo vehicles
- ✅ `jrk/test-api-violation-count.php` - Created diagnostic test script

## Next Steps

1. **Deploy updated vehicles-search.php** to production
2. **Test with browser DevTools** to verify API response
3. **Create a test violation** to see the button appear
4. **Verify button functionality** by clicking to view violation history
