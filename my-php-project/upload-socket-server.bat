@echo off
echo ========================================
echo Upload socket-server.js to VPS
echo ========================================
echo.

REM Kiểm tra file có tồn tại không
if not exist "socket-server.js" (
    echo ERROR: socket-server.js not found!
    echo Please run this script from the project directory.
    pause
    exit /b 1
)

echo [1/4] Uploading socket-server.js to VPS...
scp socket-server.js root@152.42.246.239:/root/socket-server/
if %errorlevel% neq 0 (
    echo ERROR: Upload failed!
    echo Please check:
    echo - SSH connection is working
    echo - VPS password is correct
    echo - File path is correct
    pause
    exit /b 1
)

echo.
echo [2/4] Upload successful!
echo.

echo [3/4] Restarting PM2 process on VPS...
ssh root@152.42.246.239 "cd /root/socket-server && pm2 restart socket-server"
if %errorlevel% neq 0 (
    echo WARNING: PM2 restart may have failed.
    echo Please SSH manually and check: pm2 list
)

echo.
echo [4/4] Showing PM2 logs (last 30 lines)...
echo.
ssh root@152.42.246.239 "pm2 logs socket-server --lines 30 --nostream"

echo.
echo ========================================
echo Upload and restart completed!
echo ========================================
echo.
echo Next steps:
echo 1. Check PM2 status: ssh root@152.42.246.239 "pm2 list"
echo 2. Check logs: ssh root@152.42.246.239 "pm2 logs socket-server"
echo 3. Test WebSocket connection in browser
echo.
pause

