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

VERSION=$(sed -n "s/.*'version' => env('APP_VERSION', '\\([^']*\\)').*/\\1/p" "${SERVER_DIR}/config/app.php")
if [[ -z "${VERSION}" ]]; then
    log_error "Could not read the version from server/config/app.php"
    exit 1
fi
ZIP_FILE="${SCRIPT_DIR}/plainpad-${VERSION}.zip"

# Re-run inside the dev container (has php, composer, node, zip) unless already there, then sign the archive.
if [[ ! -f /.dockerenv ]]; then
    docker compose up -d
    docker compose exec -T php-fpm bash build.sh "$@"

    # Installs refuse auto updates without a valid "<archive>.sig". The private key is piped into the container and
    # never written to the project directory. Create one with:
    #   umask 077; php -r '$k = sodium_crypto_sign_keypair(); echo base64_encode(sodium_crypto_sign_secretkey($k)), "\n";' \
    #     > ~/.config/plainpad/update-signing.key
    # and put its public key (sodium_crypto_sign_publickey_from_secretkey) in server/config/app.php. Installs also
    # accept the backup key listed there, sign with it if the release key is lost:
    #   PLAINPAD_SIGNING_KEY=/path/to/update-signing-backup.key ./build.sh
    SIGNING_KEY="${PLAINPAD_SIGNING_KEY:-${HOME}/.config/plainpad/update-signing.key}"
    ZIP_NAME="$(basename "${ZIP_FILE}")"
    PUBLIC_KEYS=$(sed -n "/'APP_UPDATE_PUBLIC_KEYS',/{n;s/.*'\([^']*\)'.*/\1/p}" "${SERVER_DIR}/config/app.php")

    rm -f "${ZIP_FILE}.sig"

    if [[ ! -f "${SIGNING_KEY}" ]]; then
        log_warn "No signing key at ${SIGNING_KEY}, ${ZIP_NAME} is unsigned and auto updates will refuse it"
        exit 0
    fi

    docker compose exec -T php-fpm php -r \
        'echo base64_encode(sodium_crypto_sign_detached(file_get_contents($argv[1]), base64_decode(trim(stream_get_contents(STDIN)), true))), "\n";' \
        "${ZIP_NAME}" < "${SIGNING_KEY}" > "${ZIP_FILE}.sig"

    # Catch a key that does not match the public keys shipped in the app before the archive gets published.
    if ! docker compose exec -T php-fpm php -r \
        '$s = base64_decode(trim(file_get_contents($argv[1].".sig"))); $z = file_get_contents($argv[1]);
        foreach (explode(",", $argv[2]) as $k) { if (sodium_crypto_sign_verify_detached($s, $z, base64_decode($k))) exit(0); }
        exit(1);' \
        "${ZIP_NAME}" "${PUBLIC_KEYS}"; then
        rm -f "${ZIP_FILE}.sig"
        log_error "The signature does not match the public keys in server/config/app.php"
        exit 1
    fi

    log_info "Signed: ${ZIP_FILE}.sig (upload it next to the archive)"
    exit 0
fi

# =====================================================
# Step 1: Reset Build Directory
# =====================================================
log_info "Resetting build directory..."

rm -rf "${BUILD_DIR}" "${ZIP_FILE}" 2>/dev/null || true
mkdir -p "${BUILD_DIR}"

# =====================================================
# Step 2: Bundle Server
# =====================================================
log_info "Bundling server..."

# Copy server files to build directory
cp -r "${SERVER_DIR}/"* "${BUILD_DIR}/"
cp "${SERVER_DIR}/.env.example" "${BUILD_DIR}/" 2>/dev/null || log_warn ".env.example not found"

# Clean up unnecessary server files
# Drop local runtime files (logs, sessions, cache, compiled views), only the .gitignore placeholders ship
find "${BUILD_DIR}/storage" -type f ! -name '.gitignore' -delete
find "${BUILD_DIR}/storage" -mindepth 2 -type d -empty -path '*/cache/data/*' -delete
rm -rf "${BUILD_DIR}/tests" 2>/dev/null || true
rm -rf "${BUILD_DIR}/vendor" 2>/dev/null || true
rm -f "${BUILD_DIR}/phpunit.xml" 2>/dev/null || true
rm -f "${BUILD_DIR}"/bootstrap/cache/*.php 2>/dev/null || true

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

mv "${BUILD_DIR}/build.zip" "${ZIP_FILE}"

# =====================================================
# Done
# =====================================================
log_info "Build completed successfully!"
log_info "Output: ${ZIP_FILE}"

