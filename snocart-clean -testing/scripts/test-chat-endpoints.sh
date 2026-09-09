#!/bin/bash

echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║        Customer Support Chat - Endpoint Testing              ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo ""

BASE_URL="https://new.snocart.com"

echo "1️⃣  Testing Vendor Panel Routes..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Test vendor message list (will redirect to login if not authenticated)
echo "📍 GET ${BASE_URL}/store-panel/message/list"
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -L "${BASE_URL}/store-panel/message/list")
if [ "$STATUS" = "200" ] || [ "$STATUS" = "302" ]; then
    echo "✅ Vendor panel accessible (HTTP $STATUS)"
else
    echo "❌ Vendor panel error (HTTP $STATUS)"
fi
echo ""

echo "2️⃣  Testing Admin Panel Routes..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Test Next.js chat page
echo "📍 GET ${BASE_URL}/chat"
STATUS=$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}/chat")
if [ "$STATUS" = "200" ]; then
    echo "✅ Next.js chat page accessible (HTTP $STATUS)"
else
    echo "⚠️  Next.js chat page (HTTP $STATUS) - may require login"
fi
echo ""

# Test admin API endpoints (will return 401 without auth)
echo "📍 GET ${BASE_URL}/admin/chat/customer-conversations"
STATUS=$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}/admin/chat/customer-conversations")
if [ "$STATUS" = "401" ] || [ "$STATUS" = "302" ]; then
    echo "✅ Admin API endpoint exists (HTTP $STATUS - needs auth)"
elif [ "$STATUS" = "200" ]; then
    echo "✅ Admin API endpoint accessible (HTTP $STATUS)"
else
    echo "❌ Admin API error (HTTP $STATUS)"
fi
echo ""

echo "3️⃣  Testing Customer API Routes..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Test customer API (will return 401 without Bearer token)
echo "📍 GET ${BASE_URL}/api/v1/message/list"
STATUS=$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}/api/v1/message/list")
if [ "$STATUS" = "401" ]; then
    echo "✅ Customer API endpoint exists (HTTP $STATUS - needs Bearer token)"
elif [ "$STATUS" = "200" ]; then
    echo "✅ Customer API endpoint accessible (HTTP $STATUS)"
else
    echo "❌ Customer API error (HTTP $STATUS)"
fi
echo ""

echo "4️⃣  Route Verification..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

cd /var/www/html/new_public/new

echo "Vendor Message Routes:"
php artisan route:list 2>/dev/null | grep "vendor.message" | awk '{print "  • " $1 " " $2 " " $3}' | head -5

echo ""
echo "Admin Chat Routes:"
php artisan route:list 2>/dev/null | grep "admin.chat" | awk '{print "  • " $1 " " $2 " " $3}' | head -5

echo ""
echo "Customer API Routes:"
php artisan route:list 2>/dev/null | grep "api/v1/message" | awk '{print "  • " $1 " " $2 " " $3}' | head -5

echo ""
echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║                    Testing Complete                           ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo ""
echo "📖 Next Steps:"
echo "   1. Login to vendor panel: ${BASE_URL}/store-panel/auth/login"
echo "   2. Navigate to messages: ${BASE_URL}/store-panel/message/list"
echo "   3. Test sending messages to customers"
echo ""
echo "   For admin panel:"
echo "   1. Login to admin: ${BASE_URL}/admin/auth/login"
echo "   2. Navigate to chat: ${BASE_URL}/chat"
echo ""
