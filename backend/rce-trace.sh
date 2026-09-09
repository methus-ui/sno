#!/bin/bash
# ============================================================
# RCE ATTACK PATH TRACER
# Run from project root:
#   cd /home/kamill/Desktop/snocart-clean
#   bash rce-trace.sh
# ============================================================

echo "========== 1. VULNERABLE UPLOAD CODE (Vendor Web) =========="
echo "--- app/Http/Controllers/Vendor/AdvertisementController.php:100-200 ---"
sed -n '100,200p' app/Http/Controllers/Vendor/AdvertisementController.php

echo ""
echo "========== 2. VULNERABLE UPLOAD CODE (Vendor API - Mobile App) =========="
echo "--- app/Http/Controllers/Api/V1/Vendor/AdvertisementController.php:130-170 ---"
sed -n '130,170p' app/Http/Controllers/Api/V1/Vendor/AdvertisementController.php

echo ""
echo "========== 3. ROUTES - What URLs trigger the upload? =========="
echo "--- All advertisement routes ---"
grep -rn "advertisement\|Advertisement" routes/ --include="*.php" | grep -i "Route::" | head -30

echo ""
echo "========== 4. MIDDLEWARE - Is auth enforced? =========="
echo "--- Vendor web route group ---"
grep -n -B3 -A15 "prefix.*'vendor'" routes/web.php | grep -i "middleware\|prefix\|Route::\|group"

echo ""
echo "--- Vendor API route group ---"
grep -n -B3 -A15 "prefix.*'vendor'" routes/api/v1/api.php | grep -i "middleware\|prefix\|Route::\|group"

echo ""
echo "========== 5. THE ROOT CAUSE: Helpers::upload() =========="
echo "--- app/CentralLogics/helpers.php:2253-2276 ---"
sed -n '2253,2276p' app/CentralLogics/helpers.php

echo ""
echo "========== 6. WHERE FILES ARE SAVED (public disk) =========="
grep -n -A15 "'public'" config/filesystems.php

echo ""
echo "========== 7. PHP EXECUTION IN STORAGE? =========="
find storage/ public/ -name ".htaccess" -exec echo "=== {} ===" \; -exec cat {} \; 2>/dev/null || echo "(none found)"
echo "--- public/.htaccess ---"
cat public/.htaccess 2>/dev/null || echo "(none)"

echo ""
echo "========== 8. ADVERTISEMENT MODEL (DB fields) =========="
ADVMODEL=$(find app/Models/ -name "Advertisement.php" | head -1)
echo "Model: $ADVMODEL"
grep -n "fillable\|full_url\|cover_image\|profile_image\|video_attachment" "$ADVMODEL" 2>/dev/null | head -20

echo ""
echo "========== DONE =========="
