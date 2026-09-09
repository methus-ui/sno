#!/bin/bash
cd /var/www/html/new_public/new/snocart-web

echo "Starting Next.js on port 3000..."
node_modules/.bin/next start --port 3000 > /tmp/next-chat-test.log 2>&1 &
NEXT_PID=$!

echo "Waiting for server to start..."
sleep 15

echo "Testing chat page..."
curl -s "http://localhost:3000/chat?token=test123" > /tmp/chat-response.html

echo "Response preview:"
head -50 /tmp/chat-response.html

echo ""
echo "Checking if page loaded correctly..."
if grep -q "Employee Chat" /tmp/chat-response.html; then
    echo "✅ Chat page loaded successfully!"
else
    echo "❌ Chat page did not load properly"
fi

echo ""
echo "Killing test server..."
kill $NEXT_PID 2>/dev/null

echo "Done!"
