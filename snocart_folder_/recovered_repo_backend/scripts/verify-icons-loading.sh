#!/bin/bash

echo "=== Icon Loading Verification ==="
echo

# Test 1: Check FontAwesome CSS loads
echo "1. Testing FontAwesome CSS accessibility..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "https://new.snocart.com/public/assets/admin/vendor/fontawesome-free/css/all.min.css")
if [ "$HTTP_CODE" = "200" ]; then
    echo "   ✓ FontAwesome CSS loads (HTTP $HTTP_CODE)"
else
    echo "   ✗ FontAwesome CSS failed (HTTP $HTTP_CODE)"
fi

# Test 2: Check webfont files load
echo "2. Testing webfont files..."
for FONT in fa-solid-900.woff2 fa-regular-400.woff2 fa-brands-400.woff2; do
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "https://new.snocart.com/public/assets/admin/vendor/fontawesome-free/webfonts/$FONT")
    if [ "$HTTP_CODE" = "200" ]; then
        echo "   ✓ $FONT loads (HTTP $HTTP_CODE)"
    else
        echo "   ✗ $FONT failed (HTTP $HTTP_CODE)"
    fi
done

# Test 3: Check delivery-stats page loads
echo "3. Testing delivery-stats page..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_CODE}" "https://new.snocart.com/admin/delivery-stats")
if [ "$HTTP_CODE" = "200" ]; then
    echo "   ✓ Delivery stats page loads (HTTP $HTTP_CODE)"
else
    echo "   ✗ Delivery stats page failed (HTTP $HTTP_CODE)"
fi

# Test 4: Check if page includes local FontAwesome
echo "4. Checking FontAwesome link in HTML..."
CONTENT=$(curl -s "https://new.snocart.com/admin/delivery-stats" 2>/dev/null)
if echo "$CONTENT" | grep -q "fontawesome-free/css/all.min.css"; then
    echo "   ✓ Local FontAwesome link found"
else
    echo "   ✗ FontAwesome link not found or still using CDN"
fi

echo
echo "=== Verification Complete ==="
echo
echo "Next steps:"
echo "1. Clear browser cache (Ctrl+Shift+Delete)"
echo "2. Hard refresh page (Ctrl+F5)"
echo "3. Open browser console (F12) and check for errors"
echo "4. All icons should now display correctly"
