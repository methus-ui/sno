#!/bin/bash

#############################################
# WebP Image Converter Script
# Converts all JPG/PNG images to WebP format
#############################################

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║           WebP Image Conversion Tool                           ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if we're in the correct directory
if [ ! -f "artisan" ]; then
    echo -e "${RED}❌ Error: artisan file not found. Please run from Laravel root directory.${NC}"
    exit 1
fi

# Function to show menu
show_menu() {
    echo -e "${GREEN}What would you like to do?${NC}"
    echo ""
    echo "1) Show current WebP statistics (no changes)"
    echo "2) Dry-run conversion (test mode - no actual changes)"
    echo "3) Convert images to WebP (files only, no database update)"
    echo "4) Convert images + update database (FULL CONVERSION)"
    echo "5) Convert images + update database + FORCE overwrite existing WebP"
    echo "6) Rollback database to original extensions (dry-run)"
    echo "7) Rollback database to original extensions (EXECUTE)"
    echo "8) Retry failed conversions only"
    echo "0) Exit"
    echo ""
}

# Main menu loop
while true; do
    show_menu
    read -p "Enter your choice [0-8]: " choice
    
    case $choice in
        1)
            echo ""
            echo -e "${YELLOW}📊 Showing comprehensive statistics...${NC}"
            echo ""
            php artisan images:convert-webp-complete --stats
            echo ""
            ;;
        2)
            echo ""
            echo -e "${YELLOW}🔍 Running DRY-RUN (no actual changes)...${NC}"
            echo ""
            php artisan images:convert-webp-complete --dry-run --quality=80
            echo ""
            ;;
        3)
            echo ""
            echo -e "${YELLOW}⚠️ This will convert images but NOT update database.${NC}"
            read -p "Continue? (yes/no): " confirm
            if [ "$confirm" = "yes" ]; then
                echo ""
                echo -e "${GREEN}🚀 Converting images to WebP (files only)...${NC}"
                echo ""
                php artisan images:convert-webp-complete --quality=80
                echo ""
                echo -e "${GREEN}✅ Conversion complete! Original files kept for safety.${NC}"
                echo -e "${YELLOW}⚠️ Remember to run option 4 to update database references!${NC}"
            else
                echo "Cancelled."
            fi
            echo ""
            ;;
        4)
            echo ""
            echo -e "${RED}⚠️ IMPORTANT: This will convert images AND update ALL database tables!${NC}"
            echo -e "${YELLOW}This is the COMPLETE conversion process.${NC}"
            echo ""
            read -p "Are you sure? Type 'YES' to proceed: " confirm
            if [ "$confirm" = "YES" ]; then
                echo ""
                echo -e "${GREEN}🚀 Starting FULL WebP conversion...${NC}"
                echo ""
                php artisan images:convert-webp-complete --update-db --quality=80
                echo ""
                echo -e "${GREEN}✅ Complete! Images converted and database updated.${NC}"
                echo -e "${YELLOW}⚠️ Original files kept. Test your site, then delete them manually.${NC}"
            else
                echo "Cancelled. Type 'YES' (uppercase) to confirm."
            fi
            echo ""
            ;;
        5)
            echo ""
            echo -e "${RED}⚠️ This will FORCE overwrite existing WebP files!${NC}"
            echo ""
            read -p "Are you sure? Type 'YES' to proceed: " confirm
            if [ "$confirm" = "YES" ]; then
                echo ""
                echo -e "${GREEN}🚀 Starting FORCED WebP conversion...${NC}"
                echo ""
                php artisan images:convert-webp-complete --update-db --force --quality=80
                echo ""
                echo -e "${GREEN}✅ Complete!${NC}"
            else
                echo "Cancelled."
            fi
            echo ""
            ;;
        6)
            echo ""
            echo -e "${YELLOW}🔍 Checking rollback impact (dry-run)...${NC}"
            echo ""
            php artisan images:convert-webp-complete --rollback-db --dry-run
            echo ""
            ;;
        7)
            echo ""
            echo -e "${RED}⚠️ WARNING: This will revert ALL database references back to original extensions!${NC}"
            echo ""
            read -p "Are you sure? Type 'YES' to proceed: " confirm
            if [ "$confirm" = "YES" ]; then
                echo ""
                echo -e "${YELLOW}♻️ Rolling back database...${NC}"
                echo ""
                php artisan images:convert-webp-complete --rollback-db
                echo ""
                echo -e "${GREEN}✅ Rollback complete!${NC}"
            else
                echo "Cancelled."
            fi
            echo ""
            ;;
        8)
            echo ""
            echo -e "${YELLOW}🔄 Retrying failed conversions...${NC}"
            echo ""
            php artisan images:convert-webp-complete --retry-failed --quality=80
            echo ""
            ;;
        0)
            echo ""
            echo -e "${GREEN}Goodbye!${NC}"
            echo ""
            exit 0
            ;;
        *)
            echo ""
            echo -e "${RED}Invalid choice. Please enter 0-8.${NC}"
            echo ""
            ;;
    esac
    
    read -p "Press Enter to continue..."
    clear
done
