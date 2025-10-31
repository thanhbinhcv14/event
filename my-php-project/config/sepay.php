<?php
// SePay Configuration
// Production credentials

define('SEPAY_PARTNER_CODE', 'SP-LIVE-BT953B7A');
define('SEPAY_SECRET_KEY', 'spsk_live_dpzV8LVbzmCuMswSbVdQitHANPatgLLn');
define('SEPAY_API_TOKEN', 'BN3FCA9DRCGR6TTHY110MIEYIKPANZBI8QZO9W0KXOEQISYSWDLMPWLFQX6HSPJP');
define('SEPAY_ENVIRONMENT', 'production'); // 'sandbox' or 'production'
define('SEPAY_CALLBACK_URL', 'https://sukien.info.vn/event/my-php-project/hooks/sepay-payment.php');

// SePay API URLs
if (SEPAY_ENVIRONMENT === 'sandbox') {
    define('SEPAY_BASE_URL', 'https://my.sepay.vn/userapi'); // Môi trường test
} else {
    // URL chính xác theo tài liệu SePay
    define('SEPAY_BASE_URL', 'https://my.sepay.vn/userapi'); // Môi trường production
}
?>
