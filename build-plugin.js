#!/usr/bin/env node

const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');
const archiver = require('archiver');

// Configuration
const PLUGIN_NAME = 'wp-oauth-login';
const BUILD_DIR = 'release';
const EXCLUDED_PATTERNS = [
    '*.DS_Store',
    '*/.*',
    '*/node_modules/*',
    '*/tests/*',
    '*/docs/*',
    '*/bin/*',
    '*.git*',
    '*.distignore',
    '*.eslintignore',
    '*.phpunit.result.cache',
    'phpunit.xml.dist',
    'phpcs.xml',
    'README.md',
    'GENERALIZATION_SUMMARY.md',
    'wp-assets/*',
    '.github/*',
    'assets/src/*',
    'build-plugin.sh',
    'build-plugin.js',
    'package.json',
    'package-lock.json',
    'webpack.mix.js',
    'mix-manifest.json'
];

// Get version from main plugin file
function getVersion() {
    const pluginFile = fs.readFileSync('wp-oauth-login.php', 'utf8');
    const versionMatch = pluginFile.match(/Version:\s*([^\n\r]+)/);
    return versionMatch ? versionMatch[1].trim() : '1.0.0';
}

// Clean and create build directory
function prepareBuildDir() {
    if (fs.existsSync(BUILD_DIR)) {
        console.log('Cleaning previous build...');
        fs.rmSync(BUILD_DIR, { recursive: true, force: true });
    }
    fs.mkdirSync(BUILD_DIR, { recursive: true });
}

// Copy directory recursively
function copyDir(src, dest) {
    if (!fs.existsSync(dest)) {
        fs.mkdirSync(dest, { recursive: true });
    }
    
    const entries = fs.readdirSync(src, { withFileTypes: true });
    
    for (const entry of entries) {
        const srcPath = path.join(src, entry.name);
        const destPath = path.join(dest, entry.name);
        
        if (entry.isDirectory()) {
            copyDir(srcPath, destPath);
        } else {
            fs.copyFileSync(srcPath, destPath);
        }
    }
}

// Check if path should be excluded
function shouldExclude(filePath) {
    return EXCLUDED_PATTERNS.some(pattern => {
        const regex = pattern
            .replace(/\*/g, '.*')
            .replace(/\?/g, '.')
            .replace(/\./g, '\\.');
        return new RegExp(regex).test(filePath);
    });
}

// Copy files to build directory
function copyPluginFiles() {
    console.log('Copying plugin files...');
    
    const filesToCopy = [
        'wp-oauth-login.php',
        'src',
        'templates',
        'languages',
        'assets/build',
        'composer.json',
        'LICENSE'
    ];
    
    filesToCopy.forEach(item => {
        if (fs.existsSync(item)) {
            if (fs.statSync(item).isDirectory()) {
                copyDir(item, path.join(BUILD_DIR, item));
            } else {
                fs.copyFileSync(item, path.join(BUILD_DIR, item));
            }
        }
    });
}

// Install production dependencies
function installProductionDependencies() {
    console.log('Installing production dependencies...');
    try {
        execSync('composer install --no-dev --optimize-autoloader --no-interaction', {
            cwd: BUILD_DIR,
            stdio: 'inherit'
        });
    } catch (error) {
        console.error('Failed to install production dependencies:', error.message);
        throw error;
    }
}

// Create zip file
function createZip() {
    return new Promise((resolve, reject) => {
        const version = getVersion();
        const zipName = `${PLUGIN_NAME}-${version}.zip`;
        const output = fs.createWriteStream(zipName);
        const archive = archiver('zip', { zlib: { level: 9 } });
        
        output.on('close', () => {
            console.log('Build complete!');
            console.log(`Plugin zip file: ${zipName}`);
            console.log(`Size: ${(archive.pointer() / 1024 / 1024).toFixed(2)} MB`);
            console.log('');
            console.log(`You can now upload ${zipName} to your WordPress site via Plugins > Add New > Upload Plugin`);
            resolve();
        });
        
        archive.on('error', (err) => {
            reject(err);
        });
        
        archive.pipe(output);
        
        // Add files from build directory
        archive.directory(BUILD_DIR, false);
        
        archive.finalize();
    });
}

// Main build process
async function buildPlugin() {
    try {
        const version = getVersion();
        console.log(`Building WP OAuth Login Plugin v${version}...`);
        
        // Build assets
        console.log('Building assets...');
        execSync('npm run production', { stdio: 'inherit' });
        
        // Prepare build directory
        prepareBuildDir();
        
        // Copy files
        copyPluginFiles();
        
        // Install production dependencies
        installProductionDependencies();
        
        // Create zip
        console.log('Creating zip file...');
        await createZip();
        
    } catch (error) {
        console.error('Build failed:', error.message);
        process.exit(1);
    }
}

// Run the build
buildPlugin(); 