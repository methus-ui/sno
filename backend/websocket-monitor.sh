#!/bin/bash
# WebSocket Server Health Monitor

echo "=== WebSocket Server Status ==="
echo ""

# Check if service is running
if ps aux | grep -v grep | grep "websockets:serve" > /dev/null; then
    echo "✅ WebSocket Server: RUNNING"
    PID=$(ps aux | grep -v grep | grep "websockets:serve" | awk '{print $2}')
    echo "   PID: $PID"
    UPTIME=$(ps -p $PID -o etime= | xargs)
    echo "   Uptime: $UPTIME"
else
    echo "❌ WebSocket Server: NOT RUNNING"
fi

echo ""

# Check port
if netstat -tlnp 2>/dev/null | grep 6001 > /dev/null; then
    echo "✅ Port 6001: LISTENING"
else
    echo "❌ Port 6001: NOT LISTENING"
fi

echo ""

# Check SSL
echo "🔒 SSL Certificate:"
openssl x509 -in /etc/letsencrypt/live/new.snocart.com/fullchain.pem -noout -dates 2>/dev/null | grep notAfter | cut -d= -f2
echo ""

# Check recent errors (last 5 minutes)
RECENT_ERRORS=$(grep "$(date +%Y-%m-%d)" /var/www/html/new_public/new/storage/logs/laravel-$(date +%Y-%m-%d).log 2>/dev/null | grep "WebSocket error" | wc -l)
echo "📊 WebSocket Errors (today): $RECENT_ERRORS"

# Check nearby notifications
NEARBY=$(grep "Nearby notification sent" /var/www/html/new_public/new/storage/logs/laravel-$(date +%Y-%m-%d).log 2>/dev/null | wc -l)
echo "📍 Nearby Notifications (today): $NEARBY"

echo ""
echo "=== Recent Connections ==="
tail -10 /var/log/supervisor/websockets.log 2>/dev/null | grep "New connection" | tail -3

echo ""
echo "Run 'supervisorctl status websockets' for more details"
