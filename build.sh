#!/bin/bash

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Helper functions
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Get script directory for consistent paths
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BUILD_DIR="${SCRIPT_DIR}/build"
SERVER_DIR="${SCRIPT_DIR}/server"
CLIENT_DIR="${SCRIPT_DIR}/client"

# =====================================================
# Step 1: Reset Build Directory
# =====================================================
log_info "Resetting build directory..."

rm -rf "${BUILD_DIR}" "${SCRIPT_DIR}/build.zip" 2>/dev/null || true
mkdir -p "${BUILD_DIR}"

# =====================================================
# Step 2: Bundle Server
# =====================================================
log_info "Bundling server..."

# Copy server files to build directory
cp -r "${SERVER_DIR}/"* "${BUILD_DIR}/"
cp "${SERVER_DIR}/.env.example" "${BUILD_DIR}/" 2>/dev/null || log_warn ".env.example not found"

# Clean up unnecessary server files
rm -f "${BUILD_DIR}"/storage/**/*.log 2>/dev/null || true
rm -f "${BUILD_DIR}"/storage/framework/**/*.php 2>/dev/null || true
rm -rf "${BUILD_DIR}/tests" 2>/dev/null || true
rm -rf "${BUILD_DIR}/vendor" 2>/dev/null || true
rm -f "${BUILD_DIR}/phpunit.xml" 2>/dev/null || true

# Install production dependencies
log_info "Installing Composer dependencies..."
cd "${BUILD_DIR}"
composer install --no-ansi --no-dev --no-interaction --no-progress --no-scripts --optimize-autoloader
composer dump-autoload --optimize
rm -f composer.lock

cd "${SCRIPT_DIR}"

# =====================================================
# Step 3: Bundle Client
# =====================================================
log_info "Building client..."

cd "${CLIENT_DIR}"
npm run build
cd "${SCRIPT_DIR}"

# =====================================================
# Step 4: Merge Client and Server Public Directories
# =====================================================
log_info "Merging client build with server public directory..."

# Save server-specific public files before overwriting
SERVER_PUBLIC_FILES=(
    "api.php"
    "setup.php"
    ".htaccess"
    "web.config"
)

# Create temp directory for server public files
TEMP_DIR=$(mktemp -d)

for file in "${SERVER_PUBLIC_FILES[@]}"; do
    if [[ -f "${BUILD_DIR}/public/${file}" ]]; then
        cp "${BUILD_DIR}/public/${file}" "${TEMP_DIR}/"
    fi
done

# Copy client build to public directory (this will overwrite common files like favicon.ico)
cp -r "${CLIENT_DIR}/build/"* "${BUILD_DIR}/public/"

# Restore server-specific public files (they take priority)
for file in "${SERVER_PUBLIC_FILES[@]}"; do
    if [[ -f "${TEMP_DIR}/${file}" ]]; then
        cp "${TEMP_DIR}/${file}" "${BUILD_DIR}/public/"
    fi
done

# Clean up temp directory
rm -rf "${TEMP_DIR}"

# =====================================================
# Step 5: Copy README File
# =====================================================
log_info "Copying README file..."

cp "${SCRIPT_DIR}/README.md" "${BUILD_DIR}/"

# =====================================================
# Step 6: Cleanup
# =====================================================
log_info "Cleaning up..."

# Remove macOS metadata files
find "${BUILD_DIR}" -name '.DS_Store' -type f -delete 2>/dev/null || true
find "${BUILD_DIR}" -name '._*' -type f -delete 2>/dev/null || true

# =====================================================
# Step 7: Create Zip Archive
# =====================================================
log_info "Creating zip archive..."

cd "${BUILD_DIR}"

zip -r build.zip . -x \
    '.idea/*' \
    '.git/*' \
    '.github/*' \
    '.run/*' \
    'docker/*' \
    'tests/*' \
    '.editorconfig' \
    '.gitattributes' \
    '.gitignore' \
    '.phpstorm.meta.php' \
    '.prettierignore' \
    '.prettierrc.json' \
    'package-lock.json' \
    'phpunit.xml' \
    '_ide_helper_models.php' \
    '_ide_helper.php' \
    'build.sh' \
    '*.zip'

cd "${SCRIPT_DIR}"

mv "${BUILD_DIR}/build.zip" "${SCRIPT_DIR}/"

# =====================================================
# Done
# =====================================================
log_info "Build completed successfully!"
log_info "Output: ${SCRIPT_DIR}/build.zip"

