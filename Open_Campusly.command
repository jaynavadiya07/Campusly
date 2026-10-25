#!/bin/bash
# One-click Launcher for Campusly Academic Portal
cd "$(dirname "$0")"

# Check if port 8000 is running
if ! lsof -i :8000 > /dev/null 2>&1; then
    echo "Starting PHP local server on port 8000..."
    php -S 0.0.0.0:8000 > /dev/null 2>&1 &
    sleep 1
fi

echo "Campusly Portal is active at: http://localhost:8000"
open "http://localhost:8000/login.php"
