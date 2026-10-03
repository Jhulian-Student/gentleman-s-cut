#!/bin/bash
# Start_Server.command
# Double-click this file in Finder to launch the website and server automatically!

cd "$(dirname "$0")"

clear
echo "=========================================================="
echo "    💈 THE GENTLEMAN'S CUT - AUTOMATIC SERVER LAUNCHER   "
echo "=========================================================="

# 1. Ensure MySQL is running
if which brew >/dev/null 2>&1; then
    if ! brew services list | grep -q "mysql.*started"; then
        echo "Starting MySQL database service..."
        brew services start mysql
    fi
fi

# 2. Detect local Wi-Fi IP for phone connection
LOCAL_IP=$(ipconfig getifaddr en0 2>/dev/null || ipconfig getifaddr en1 2>/dev/null || echo "127.0.0.1")

echo ""
echo "✅ Server started successfully!"
echo "----------------------------------------------------------"
echo "💻 On your Mac:   http://localhost:8000"
echo "📱 On your Phone: http://${LOCAL_IP}:8000"
echo "----------------------------------------------------------"
echo ""
echo "Opening website in your browser..."

# 3. Automatically open the browser
(sleep 1 && open "http://localhost:8000/index.html") &

# 4. Start PHP server on all network interfaces (PC + Phone)
php -S 0.0.0.0:8000

