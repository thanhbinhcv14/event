<?php
// SePay Configuration
// Production credentials

define('SEPAY_PARTNER_CODE', 'SP-LIVE-BT953B7A');
define('SEPAY_SECRET_KEY', 'spsk_live_dpzV8LVbzmCuMswSbVdQitHANPatgLLn');
define('SEPAY_API_TOKEN', 'BN3FCA9DRCGR6TTHY110MIEYIKPANZBI8QZO9W0KXOEQISYSWDLMPWLFQX6HSPJP');
define('SEPAY_ENVIRONMENT', 'production'); // 'sandbox' or 'production'
define('SEPAY_CALLBACK_URL', 'https://sukien.info.vn/event/my-php-project/payment/sepay-callback.php');

// SePay API URLs
if (SEPAY_ENVIRONMENT === 'sandbox') {
    define('SEPAY_BASE_URL', 'https://sandbox.sepay.vn'); // Môi trường test
} else {
    define('SEPAY_BASE_URL', 'https://api.sepay.vn'); // Môi trường thật
}
?>
