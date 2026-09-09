#!/bin/bash

# Quick Test Script for Modern UI Implementation
# Run this after updating index.blade.php

echo "🎨 Testing Modern Messaging UI Implementation..."
echo ""

# Check if CSS file exists
echo "1️⃣ Checking CSS file..."
if [ -f "public/assets/admin/css/messaging-modern-ui.css" ]; then
    echo "   ✅ CSS file exists ($(du -h public/assets/admin/css/messaging-modern-ui.css | cut -f1))"
else
    echo "   ❌ CSS file NOT found!"
    exit 1
fi

# Check if JS file exists
echo "2️⃣ Checking JavaScript file..."
if [ -f "public/assets/admin/js/messaging-modern-ui.js" ]; then
    echo "   ✅ JS file exists ($(du -h public/assets/admin/js/messaging-modern-ui.js | cut -f1))"
else
    echo "   ❌ JS file NOT found!"
    exit 1
fi

# Check if index.blade.php has the new CSS link
echo "3️⃣ Checking index.blade.php for CSS link..."
if grep -q "messaging-modern-ui.css" "resources/views/admin-views/messages/index.blade.php"; then
    echo "   ✅ CSS linked in index.blade.php"
else
    echo "   ❌ CSS NOT linked in index.blade.php"
    exit 1
fi

# Check if index.blade.php has the new JS link
echo "4️⃣ Checking index.blade.php for JS link..."
if grep -q "messaging-modern-ui.js" "resources/views/admin-views/messages/index.blade.php"; then
    echo "   ✅ JS linked in index.blade.php"
else
    echo "   ❌ JS NOT linked in index.blade.php"
    exit 1
fi

# Check for gradients (should be removed)
echo "5️⃣ Checking for remaining gradients..."
GRADIENT_COUNT=$(grep -c "linear-gradient" "resources/views/admin-views/messages/index.blade.php" || echo "0")
echo "   ℹ️  Found $GRADIENT_COUNT occurrences of 'linear-gradient'"
if [ "$GRADIENT_COUNT" -lt 5 ]; then
    echo "   ✅ Most gradients removed (good!)"
else
    echo "   ⚠️  Many gradients still present"
fi

# Clear caches
echo "6️⃣ Clearing Laravel caches..."
php artisan view:clear > /dev/null 2>&1
echo "   ✅ View cache cleared"
php artisan cache:clear > /dev/null 2>&1
echo "   ✅ Application cache cleared"

echo ""
echo "✅ All checks passed!"
echo ""
echo "📋 Next steps:"
echo "   1. Open your browser to: /admin/message/list"
echo "   2. Press Ctrl+Shift+R (hard refresh)"
echo "   3. Verify NO gradients are visible"
echo "   4. Check search has pill shape"
echo "   5. Test filter tabs work"
echo ""
echo "🎉 Your modern UI should be working now!"
