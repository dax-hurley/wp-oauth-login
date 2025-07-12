#!/bin/bash

# WP OAuth Login Plugin Build Script
# This script builds the plugin for WordPress installation

set -e

# Configuration
PLUGIN_NAME="wp-oauth-login"
VERSION=$(grep "Version:" wp-oauth-login.php | cut -d' ' -f4)
BUILD_DIR="release"
ZIP_NAME="${PLUGIN_NAME}-${VERSION}.zip"

echo "Building WP OAuth Login Plugin v${VERSION}..."

# Clean previous builds
if [ -d "$BUILD_DIR" ]; then
    echo "Cleaning previous build..."
    rm -rf "$BUILD_DIR"
fi

# Create build directory
mkdir -p "$BUILD_DIR"

# Build assets
echo "Building assets..."
npm run production

# Copy plugin files to build directory
echo "Copying plugin files..."
cp -r wp-oauth-login.php "$BUILD_DIR/"
cp -r src/ "$BUILD_DIR/"
cp -r templates/ "$BUILD_DIR/"
cp -r languages/ "$BUILD_DIR/"
cp -r assets/build/ "$BUILD_DIR/assets/"
cp -r LICENSE "$BUILD_DIR/"

# Install only production dependencies
echo "Installing production dependencies..."
cp composer.json "$BUILD_DIR/"
cd "$BUILD_DIR"
composer install --no-dev --optimize-autoloader --no-interaction
cd ..

# Create zip file
echo "Creating zip file..."
cd "$BUILD_DIR"

# Try to use zip command if available, otherwise use tar
if command -v zip >/dev/null 2>&1; then
    zip -r "../$ZIP_NAME" . -x "*.DS_Store" "*/.*" "*/node_modules/*" "*/tests/*" "*/docs/*" "*/bin/*" "*.git*" "*.distignore" "*.eslintignore" "*.phpunit.result.cache" "phpunit.xml.dist" "phpcs.xml" "README.md" "GENERALIZATION_SUMMARY.md" "wp-assets/*" ".github/*"
    ARCHIVE_NAME="$ZIP_NAME"
else
    # Use tar as fallback
    tar -czf "../${ZIP_NAME%.zip}.tar.gz" .
    ARCHIVE_NAME="${ZIP_NAME%.zip}.tar.gz"
    echo "Note: zip command not found, created tar.gz instead. WordPress may require a .zip file."
    echo "To install zip: sudo apt install zip (Ubuntu/Debian) or brew install zip (macOS)"
fi

cd ..

echo "Build complete!"
echo "Plugin archive file: $ARCHIVE_NAME"
echo "Size: $(du -h "$ARCHIVE_NAME" | cut -f1)"
echo ""
if [[ "$ARCHIVE_NAME" == *.zip ]]; then
    echo "You can now upload $ARCHIVE_NAME to your WordPress site via Plugins > Add New > Upload Plugin"
else
    echo "Note: WordPress typically requires a .zip file. You may need to convert this tar.gz to zip format."
    echo "You can install zip with: sudo apt install zip"
fi 