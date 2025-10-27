MyParkingManager v2.3.0 - Deployment Packages
==============================================
Generated: 2024-10-27

PACKAGE CONTENTS:
----------------

1. mpm-full-v2.3.0.zip (136KB)
   - Complete installation package
   - Includes setup wizard, all files, documentation
   - For: New installations
   SHA256: 05539f2762fbca8d5a9669ec18a2e393a622ab79769aaaef7bba0228f522382d

2. mpm-update-v2.3.0.zip (111KB)
   - Update package for existing installations
   - Excludes: config.php, setup files, install SQL
   - For: Updating from v2.0+
   SHA256: 35c29be9a6da1fb35a0027d5cac74acf256d572c0d64475e9e0ecb87d220813a

3. mpm-minimal-v2.3.0.zip (112KB)
   - Core application files only
   - No documentation or setup wizard
   - For: Expert users, custom deployments
   SHA256: 147b7e00e25266e6e3764ab5d7856e097e3f9b7815d12202889ca30a9d385ca5

4. mpm-docs-v2.3.0.zip (23KB)
   - Documentation and SQL scripts only
   - Includes guides, changelog, migration scripts
   - For: Reference, offline documentation
   SHA256: ff7ee984908ae7c48fc8d08bf7aa7fe5cf51c0aa49cdae788f5ebbb5b480fd3b

INSTALLATION INSTRUCTIONS:
-------------------------
See deploy/README-DEPLOYMENT.md for detailed installation and update instructions.

QUICK START:
-----------
1. Extract mpm-full-v2.3.0.zip to your web directory
2. Navigate to /setup-wizard.php in your browser
3. Follow the installation steps
4. Remove setup files after completion

WHAT'S NEW IN v2.3.0 (Updated):
-------------------------------
- **FIXED:** Setup.php database connection test (no more 500 errors)
- **FIXED:** Missing setup-test-db.php file added
- **IMPROVED:** Database error handling with user-friendly messages
- **REMOVED:** All hardcoded "jrk" path references - now fully configurable
- Flexible installation paths (install in ANY directory)
- Dynamic configuration loader with auto-detection
- Fine management for violations with monetary amounts
- Tow deadline system with automatic warnings
- Advanced printer configuration with logo support
- Subscription licensing (30-day trial with HMAC keys)

SUPPORT:
-------
Check CHANGELOG.md for version history
Review LICENSE-SYSTEM-GUIDE.md for licensing setup
See README-DEPLOYMENT.md for deployment guidance