#!/bin/bash

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Plugin name and version
PLUGIN_NAME="wp-ai-chatbot"
VERSION=$(grep -oP "Version: \K[0-9]+\.[0-9]+\.[0-9]+" wp-ai-chatbot.php 2>/dev/null || echo "1.0.0")
PACKAGE_FILE="${PLUGIN_NAME}-${VERSION}.zip"

echo -e "${YELLOW}Starting packaging of ${PLUGIN_NAME} v${VERSION}${NC}"

# Create temporary directory
TEMP_DIR="./dist-tmp"
mkdir -p $TEMP_DIR
echo -e "${GREEN}✓${NC} Temporary directory created"

# Compile assets
echo -e "${YELLOW}Compiling assets...${NC}"
npm run build
echo -e "${GREEN}✓${NC} Assets compiled"

# Install production dependencies
echo -e "${YELLOW}Installing PHP dependencies...${NC}"
composer install --no-dev --optimize-autoloader
echo -e "${GREEN}✓${NC} PHP dependencies installed"

# Copy necessary files
echo -e "${YELLOW}Copying files...${NC}"
rsync -av --exclude='.*' \
  --exclude='node_modules' \
  --exclude='assets/src' \
  --exclude='package.json' \
  --exclude='package-lock.json' \
  --exclude='webpack.config.js' \
  --exclude='dist-tmp' \
  --exclude='*.zip' \
  --exclude='create-package.sh' \
  --exclude='composer.lock' \
  ./ $TEMP_DIR/
echo -e "${GREEN}✓${NC} Files copied"

# Create ZIP file
echo -e "${YELLOW}Creating ZIP file...${NC}"
cd $TEMP_DIR
zip -r ../$PACKAGE_FILE .
cd ..
echo -e "${GREEN}✓${NC} ZIP file created: ${PACKAGE_FILE}"

# Clean up
echo -e "${YELLOW}Cleaning up...${NC}"
rm -rf $TEMP_DIR
echo -e "${GREEN}✓${NC} Clean up completed"

echo -e "${GREEN}Packaging completed!${NC}"
echo -e "ZIP file available at: ${PACKAGE_FILE}"