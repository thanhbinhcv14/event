@echo off
chcp 65001 >nul
echo ========================================
echo   Test SePay Webhook
echo ========================================
echo.

echo Testing webhook endpoint...
echo URL: https://sukien.info.vn/hooks/sepay-payment.php
echo.

curl -X POST https://sukien.info.vn/hooks/sepay-payment.php ^
  -H "Content-Type: application/json" ^
  -H "Authorization: Apikey BN3FCA9DRCGR6TTHY110MIEYIKPANZBI8QZO9W0KXOEQISYSWDLMPWLFQX6HSPJP" ^
  -d "{\"gateway\":\"VietinBank\",\"transactionDate\":\"2025-11-16 23:35:00\",\"accountNumber\":\"100872918542\",\"transferType\":\"in\",\"transferAmount\":100000,\"content\":\"SK20_SEPAY_1762094590_1284\",\"id\":\"test_123\"}"

echo.
echo.
echo ========================================
echo   Test completed!
echo ========================================
echo.
echo Check results at: https://sukien.info.vn/debug-payment.php
echo.
pause

