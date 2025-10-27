#!/bin/bash
#
# Build registration package (license files only)
# This package should be kept separate and NOT distributed with deployment
#

echo "Building MyParkingManager Registration Package..."

# Create build directory
BUILD_DIR="build/registration"
rm -rf "$BUILD_DIR"
mkdir -p "$BUILD_DIR"
mkdir -p "$BUILD_DIR/includes"
mkdir -p "$BUILD_DIR/api"

# Copy ONLY registration/license files
echo "Copying registration files..."
cp jrk/includes/license.php "$BUILD_DIR/includes/" 2>/dev/null || echo "Warning: license.php not found"
cp jrk/includes/license-keys.php "$BUILD_DIR/includes/" 2>/dev/null || echo "Warning: license-keys.php not found"
cp jrk/api/license-status.php "$BUILD_DIR/api/" 2>/dev/null || echo "Warning: license-status.php not found"
cp jrk/api/license-activate.php "$BUILD_DIR/api/" 2>/dev/null || echo "Warning: license-activate.php not found"

# Create installation instructions
cat > "$BUILD_DIR/INSTALL.txt" << 'EOF'
REGISTRATION FILES INSTALLATION INSTRUCTIONS
===========================================

These files enable the license/registration system.

DO NOT distribute this package with your deployment!

Installation Steps:
1. Upload deployment package to server first
2. After deployment is complete, upload these files:
   - includes/license.php → /includes/
   - includes/license-keys.php → /includes/
   - api/license-status.php → /api/
   - api/license-activate.php → /api/

3. Ensure file permissions are set correctly (644 for PHP files)

4. Test license system by logging into the application

Security Notes:
- Keep this package secure and private
- Only install on authorized deployments
- Do not commit these files to public repositories
EOF

# Create version info
VERSION=$(grep -oP "v\d+\.\d+\.\d+" jrk/index.html | head -1)
echo "MyParkingManager $VERSION" > "$BUILD_DIR/VERSION.txt"
echo "Built on: $(date '+%Y-%m-%d %H:%M:%S')" >> "$BUILD_DIR/VERSION.txt"
echo "Registration Package (License Files Only)" >> "$BUILD_DIR/VERSION.txt"

# Create zip package
cd build
ZIP_NAME="MyParkingManager-${VERSION}-Registration.zip"
echo "Creating zip package: $ZIP_NAME"
zip -r "$ZIP_NAME" registration/ -q

echo ""
echo "✓ Registration package created: build/$ZIP_NAME"
echo "✓ Size: $(du -h "$ZIP_NAME" | cut -f1)"
echo ""
echo "⚠️  IMPORTANT: Keep this package SECURE and PRIVATE!"
echo "⚠️  Do NOT distribute with deployment packages!"
