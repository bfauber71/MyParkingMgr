MyParkingManager v2.3.0 - Deployment Packages
==============================================
Generated: 2024-10-27

PACKAGE CONTENTS:
----------------

1. mpm-full-v2.3.0.zip (134KB)
   - Complete installation package
   - Includes setup wizard, all files, documentation
   - For: New installations
   SHA256: ab21f2ab9d65613e4c1477c6fec61085bb20ef4006b76168f24a21343f0e2133

2. mpm-update-v2.3.0.zip (115KB)
   - Update package for existing installations
   - Excludes: config.php, setup files, install SQL
   - For: Updating from v2.0+
   SHA256: e29bbf034b2f5dad7e7179c2c8bf3ecf2ff1ff607d1e378cdb36e095f3399aa3

3. mpm-minimal-v2.3.0.zip (96KB)
   - Core application files only
   - No documentation or setup wizard
   - For: Expert users, custom deployments
   SHA256: e71466fb36ff730538c5c337e30416b469873d8ac9f195ef95b90debd2171639

4. mpm-docs-v2.3.0.zip (23KB)
   - Documentation and SQL scripts only
   - Includes guides, changelog, migration scripts
   - For: Reference, offline documentation
   SHA256: c8fa72f01d1c9fe079ec1b4f284dd39e296f3b3dfa8524b1773d6003863cde36

INSTALLATION INSTRUCTIONS:
-------------------------
See deploy/README-DEPLOYMENT.md for detailed installation and update instructions.

QUICK START:
-----------
1. Extract mpm-full-v2.3.0.zip to your web directory
2. Navigate to /setup-wizard.php in your browser
3. Follow the installation steps
4. Remove setup files after completion

WHAT'S NEW IN v2.3.0:
--------------------
- Flexible installation paths (no more hardcoded /jrk)
- Dynamic configuration loader
- Auto-detection of installation directory
- Fine management for violations
- Tow deadline system
- Advanced printer configuration
- Subscription licensing (30-day trial)

SUPPORT:
-------
Check CHANGELOG.md for version history
Review LICENSE-SYSTEM-GUIDE.md for licensing setup
See README-DEPLOYMENT.md for deployment guidance