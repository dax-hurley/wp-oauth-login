# Building WP OAuth Login Plugin

This document explains how to build the WP OAuth Login plugin for WordPress installation.

## Prerequisites

Before building, make sure you have:

1. **Node.js and npm** installed
2. **Composer** installed (for PHP dependencies)
3. All dependencies installed:
   ```bash
   npm install
   composer install
   ```

## Build Options

You have two ways to build the plugin:

### Option 1: Using npm script (Recommended)

```bash
npm run build-plugin
```

This uses the shell script and is the recommended approach.

### Option 2: Using Node.js script

```bash
npm run build-plugin-js
```

This uses a Node.js script and may be more portable across different systems.

### Option 3: Direct execution

```bash
# Shell script
./build-plugin.sh

# Node.js script
node build-plugin.js
```

## What the build process does

1. **Builds assets**: Runs `npm run production` to compile SCSS, JavaScript, and other assets
2. **Creates build directory**: Prepares a clean `release/` directory
3. **Copies plugin files**: Copies all necessary files for WordPress installation:
   - `wp-oauth-login.php` (main plugin file)
   - `src/` (PHP source code)
   - `templates/` (template files)
   - `languages/` (translation files)
   - `assets/build/` (compiled assets)
   - `composer.json` (Composer configuration)
   - `LICENSE` (license file)
4. **Installs production dependencies**: Runs `composer install --no-dev` to install only production dependencies (excludes development tools like PHPUnit, PHP_CodeSniffer, etc.)
5. **Creates archive file**: Packages everything into a WordPress-compatible archive file (zip or tar.gz)
6. **Names the file**: Uses the version from the main plugin file (e.g., `wp-oauth-login-1.4.0.zip`)

## Size Optimization

The build process is optimized to create the smallest possible plugin package:

- **Excludes development dependencies**: Only production Composer dependencies are included
- **Excludes source files**: Only compiled assets are included
- **Excludes development tools**: Testing, linting, and documentation files are excluded
- **Result**: Plugin size is typically under 1MB (compared to 36MB+ with all dependencies)

## Installation

After building, you'll have a zip file (e.g., `wp-oauth-login-1.4.0.zip`) that you can install on your WordPress site:

1. Go to your WordPress admin panel
2. Navigate to **Plugins > Add New**
3. Click **Upload Plugin**
4. Choose the zip file you just created
5. Click **Install Now**
6. Activate the plugin

## Troubleshooting

### Build fails with asset compilation errors
- Make sure all npm dependencies are installed: `npm install`
- Check that the SCSS files don't have syntax errors
- Verify that all referenced image files exist

### Zip file is too large
- The build process automatically excludes development files
- Check that you're not including unnecessary files in the build

### Plugin doesn't work after installation
- Verify that your WordPress site meets the minimum requirements (PHP 7.4+, WordPress 5.5+)
- Check the WordPress error logs for any PHP errors
- Make sure all required PHP extensions are enabled

## Development vs Production

- **Development**: Use `npm run development` for development builds
- **Production**: Use `npm run production` or the build scripts for production-ready builds

The build scripts automatically use production mode to ensure optimized assets. 