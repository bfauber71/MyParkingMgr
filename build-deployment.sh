#!/bin/bash
#
# Build deployment package for installations (without registration files)
# This creates a clean distribution package for end users
#

echo "Building MyParkingManager Deployment Package..."

# Create build directory
BUILD_DIR="build/deployment"
rm -rf "$BUILD_DIR"
mkdir -p "$BUILD_DIR"

# Copy main application files (excluding dev config)
echo "Copying application files..."
cp -r jrk/* "$BUILD_DIR/"

# Replace dev config with production template
echo "Creating production config file..."
rm -f "$BUILD_DIR/config.php"
cp jrk/config-template.php "$BUILD_DIR/config.php"
cp jrk/DEPLOYMENT-INSTRUCTIONS.md "$BUILD_DIR/README.txt"

# Remove registration/license management files (keep these separate)
echo "Removing registration files from deployment..."
rm -f "$BUILD_DIR/includes/license.php"
rm -f "$BUILD_DIR/includes/license-keys.php"
rm -f "$BUILD_DIR/api/license-status.php"
rm -f "$BUILD_DIR/api/license-activate.php"

# Remove development files
echo "Removing development files..."
rm -rf "$BUILD_DIR/.git"
rm -f "$BUILD_DIR/.gitignore"
rm -f "$BUILD_DIR/.replit"
rm -f "$BUILD_DIR/replit.nix"

# Clean up any backup or temp files
find "$BUILD_DIR" -name "*.bak" -delete
find "$BUILD_DIR" -name "*~" -delete
find "$BUILD_DIR" -name ".DS_Store" -delete

# Create version info
VERSION=$(grep -oP "v\d+\.\d+\.\d+" jrk/index.html | head -1)
echo "MyParkingManager $VERSION" > "$BUILD_DIR/VERSION.txt"
echo "Built on: $(date '+%Y-%m-%d %H:%M:%S')" >> "$BUILD_DIR/VERSION.txt"
echo "Deployment Package (No Registration Files)" >> "$BUILD_DIR/VERSION.txt"

# Create zip package
cd build
ZIP_NAME="MyParkingManager-${VERSION}-Deployment.zip"
echo "Creating zip package: $ZIP_NAME"
zip -r "$ZIP_NAME" deployment/ -q

echo ""
echo "✓ Deployment package created: build/$ZIP_NAME"
echo "✓ Size: $(du -h "$ZIP_NAME" | cut -f1)"
echo ""
echo "This package does NOT include registration/license files."
echo "Use build-registration.sh to create the registration package separately."
