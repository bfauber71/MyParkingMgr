# MyParkingManager Build & Deployment Guide

## Overview

This system creates two separate packages:
1. **Deployment Package** - For customer installations (without registration files)
2. **Registration Package** - License system files (kept private, NOT distributed)

## Why Separate Packages?

- **Security**: License management files are kept separate and private
- **Distribution**: Customers receive clean deployment package only
- **Control**: Registration files are installed separately after deployment
- **Protection**: Prevents unauthorized license generation/bypass

## Building Packages

### 1. Build Deployment Package (Customer Distribution)

```bash
./build-deployment.sh
```

**Creates**: `build/MyParkingManager-v2.3.7-Deployment.zip`

**Includes**:
- All application files
- Database schema
- Assets, API endpoints
- Setup wizard

**Excludes**:
- License management files (`license.php`, `license-keys.php`)
- License API endpoints (`license-status.php`, `license-activate.php`)
- Development files (`.git`, `.replit`, etc.)

### 2. Build Registration Package (Private - DO NOT DISTRIBUTE)

```bash
./build-registration.sh
```

**Creates**: `build/MyParkingManager-v2.3.7-Registration.zip`

**Includes ONLY**:
- `includes/license.php`
- `includes/license-keys.php`
- `api/license-status.php`
- `api/license-activate.php`
- Installation instructions

**⚠️ SECURITY WARNING**: This package must be kept PRIVATE and SECURE!

## Deployment Process

### Customer Site Installation

1. **Upload Deployment Package** to customer server
2. **Extract** to web root or subdirectory
3. **Run Setup Wizard** at `/setup/`
4. **Configure** database and admin account
5. App starts in **30-day TRIAL mode**

### License System Installation (After Customer Purchase)

1. **Keep Registration Package** secure on your system
2. **After customer purchases**, upload registration files to their server:
   - `includes/license.php`
   - `includes/license-keys.php`
   - `api/license-status.php`
   - `api/license-activate.php`
3. **Provide License Key** to customer
4. Customer activates via License Management page

## License Status Display

The app displays license status in the header:

- **TRIAL** badge (blue) - During 30-day trial period
- **EXPIRED** badge (red) - After trial expires
- **No badge** - When properly licensed

## File Structure

```
build/
├── MyParkingManager-v2.3.7-Deployment.zip    (Distribute to customers)
├── MyParkingManager-v2.3.7-Registration.zip  (Keep private!)
├── deployment/                                (Extracted deployment files)
└── registration/                              (Extracted registration files)
```

## Security Notes

1. **Never** distribute registration package with deployment
2. **Never** commit registration files to public repositories
3. **Never** share registration package with customers directly
4. **Always** install registration files manually after purchase
5. **Keep** registration package encrypted in secure storage

## Version Management

Version is automatically detected from `jrk/index.html` and included in:
- Package filenames
- VERSION.txt files inside packages

## Testing Before Release

1. Build both packages
2. Test deployment package installation on clean server
3. Verify trial mode displays correctly
4. Install registration files
5. Test license activation
6. Verify licensed mode removes badge

## Troubleshooting

**Package build fails**:
- Ensure you're in project root directory
- Check file permissions (`chmod +x build-*.sh`)

**Registration files missing**:
- Verify files exist in `jrk/` directory
- Check build script warnings

**License status not showing**:
- Clear browser cache
- Check browser console for errors
- Verify API endpoint `/api/license-status` is accessible
