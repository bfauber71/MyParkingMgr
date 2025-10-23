# CSV Export Troubleshooting Guide

## The Problem
You click "Export CSV" but nothing happens or you get an error.

---

## Root Cause (99% Likely)

**YOU ARE USING OLD CACHED JAVASCRIPT!**

Even though you uploaded new files, your browser is showing you the OLD buggy version of app.js that has broken role comparisons.

---

## The Fix - CLEAR YOUR BROWSER CACHE PROPERLY

### Mobile (Safari)
1. Open Settings app
2. Scroll down to Safari
3. Tap "Clear History and Website Data"
4. Confirm
5. Close Safari completely
6. Re-open and go to https://2clv.com/jrk

### Mobile (Chrome)
1. Open Chrome
2. Tap menu (three dots)
3. Settings → Privacy → Clear Browsing Data
4. Select "Cached Images and Files"
5. Clear
6. Close Chrome completely
7. Re-open and go to https://2clv.com/jrk

### Desktop
**Windows:**
- Press `Ctrl + Shift + Delete`
- Select "Cached images and files"
- Click Clear

**Mac:**
- Press `Cmd + Shift + Delete`
- Select "Cached images and files"  
- Click Clear

**OR use Hard Refresh:**
- Windows: `Ctrl + F5`
- Mac: `Cmd + Shift + R`

---

## Verification Steps

After clearing cache:

### 1. Check Browser Console (Desktop Only)
1. Press F12 to open Developer Tools
2. Go to Console tab
3. Click "Export CSV" button
4. You should see: `Export CSV clicked - navigating to: /jrk/api/vehicles-export`
5. If you DON'T see this message → Cache not cleared properly

### 2. Check Network Tab (Desktop Only)
1. Press F12 to open Developer Tools
2. Go to Network tab
3. Click "Export CSV" button
4. You should see a request to `/jrk/api/vehicles-export`
5. Status should be 200 OK
6. Response type should be `text/csv`

### 3. What You Should See
- A file named `vehicles_2025-10-23_123456.csv` downloads
- If no vehicles exist: CSV contains only header row
- If vehicles exist: CSV contains all vehicle data

---

## Still Not Working? Check These

### Issue 1: "Nothing happens when I click Export CSV"
**Cause:** Old JavaScript cached
**Fix:** Clear cache using Private/Incognito mode

### Issue 2: "Gets redirected to login page"
**Cause:** Session expired
**Fix:** 
1. Logout
2. Clear cache
3. Login again
4. Try export

### Issue 3: "Downloads empty CSV"
**Cause:** No vehicles in database (THIS IS NORMAL!)
**Fix:** 
1. Create a property first (Properties tab → Add Property)
2. Create a vehicle (Vehicles tab → Add Vehicle)
3. Try export again

### Issue 4: "Error 401 Unauthorized"
**Cause:** Not logged in OR session cookie not being sent
**Fix:**
1. Make sure you're logged in
2. Try in a different browser
3. Check if cookies are enabled

### Issue 5: "Error 500 Internal Server Error"
**Cause:** Database connection issue
**Fix:** 
1. Check database is running
2. Check config.php has correct database credentials
3. Check error logs on server

### Issue 6: "Error 403 Forbidden"
**Cause:** Your user role doesn't have export permission
**Fix:**
- This should NOT happen - all roles can export
- If it does happen: Your user role in database is corrupted
- Check: SELECT role FROM users WHERE username = 'admin';
- Should return: 'admin' (lowercase)

---

## Manual Test (Advanced)

If you want to test the API endpoint directly:

### From Browser Console
```javascript
// Check current user
fetch('/jrk/api/user', {credentials: 'include'})
  .then(r => r.json())
  .then(console.log);

// Try export
window.location.href = '/jrk/api/vehicles-export';
```

### From Command Line (on server)
```bash
# Test the endpoint
curl -i https://2clv.com/jrk/api/vehicles-export

# Should return:
# HTTP/1.1 401 Unauthorized (because not logged in)
```

---

## What Export CSV Does

1. JavaScript: Calls `window.open('/jrk/api/vehicles-export', '_self')`
2. Browser: Navigates to that URL (sending session cookie)
3. Server: Checks if user is logged in
4. Server: Gets user's role
5. Server: Fetches vehicles based on role:
   - Admin/Operator: ALL vehicles
   - User: Only vehicles from assigned properties
6. Server: Generates CSV file
7. Server: Sends CSV with download headers
8. Browser: Downloads file as `vehicles_YYYY-MM-DD_HHmmss.csv`

---

## Expected Behavior

### If NO vehicles in database:
```csv
Property,Tag Number,Plate Number,State,Make,Model,Color,Year,Apt Number,Owner Name,Owner Phone,Owner Email,Reserved Space
```
(Just the header row - THIS IS NORMAL!)

### If vehicles exist:
```csv
Property,Tag Number,Plate Number,State,Make,Model,Color,Year,Apt Number,Owner Name,Owner Phone,Owner Email,Reserved Space
Sunset Apartments,TAG123,ABC-1234,CA,Toyota,Camry,Blue,2020,101,John Doe,555-0100,john@example.com,A-5
```

---

## Common Mistakes

❌ **Uploading new files but not clearing browser cache**
- Your browser is using old JavaScript from cache
- You must force refresh or use Private/Incognito mode

❌ **Expecting vehicles when database is empty**
- Fresh install has 0 vehicles
- Export will work but download empty CSV
- This is normal!

❌ **Testing export before logging in**
- Export requires authentication
- Must be logged in with valid session

❌ **Not waiting for page to fully load**
- JavaScript might not be loaded yet
- Wait until you see the search bar and buttons

---

## Final Checklist

Before asking for help:

- [ ] Uploaded new jrk/ folder via FTP
- [ ] **CLEARED BROWSER CACHE** (most important!)
- [ ] Used Private/Incognito mode OR hard refresh
- [ ] Logged in successfully
- [ ] Can see all 3 tabs (Vehicles, Properties, Users)
- [ ] Can see Export CSV button (gray button)
- [ ] Clicked Export CSV button
- [ ] Checked browser console for error messages (F12)
- [ ] Created at least 1 property
- [ ] Created at least 1 vehicle
- [ ] Tried export again

---

## If All Else Fails

1. Open browser in **Private/Incognito mode**
2. Go to https://2clv.com/jrk
3. Login with admin/admin123
4. Create a property
5. Create a vehicle
6. Click Export CSV
7. Should download vehicles.csv

If it works in Private mode but not in normal mode → **Your browser cache is the problem**

---

## Technical Details

**Why cache is the issue:**
- Browsers aggressively cache JavaScript files
- You made changes to app.js
- Browser still using old app.js from cache
- Old app.js has broken role comparisons
- Export button might not work at all with old code

**Why Private/Incognito works:**
- Doesn't use cache
- Loads fresh JavaScript every time
- Will immediately show if new code works

**The solution:**
```
1. Clear cache properly
2. OR use Private/Incognito mode
3. OR add ?v=2 to the end of URL: https://2clv.com/jrk?v=2
```

---

**BOTTOM LINE:** 99% of the time, CSV export problems are caused by browser cache. Clear it properly or use Private/Incognito mode!
