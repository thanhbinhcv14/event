<?php
// SePay Configuration
// Production credentials

define('SEPAY_PARTNER_CODE', 'SP-LIVE-BT953B7A');
define('SEPAY_SECRET_KEY', 'spsk_live_dpzV8LVbzmCuMswSbVdQitHANPatgLLn');
define('SEPAY_API_TOKEN', 'BN3FCA9DRCGR6TTHY110MIEYIKPANZBI8QZO9W0KXOEQISYSWDLMPWLFQX6HSPJP');
define('SEPAY_WEBHOOK_TOKEN', 'BN3FCA9DRCGR6TTHY110MIEYIKPANZBI8QZO9W0KXOEQISYSWDLMPWLFQX6HSPJP'); // Webhook authentication token (API Token)
// Secret Key từ IPN config trong SePay Dashboard (nếu Auth Type = "Secret Key")
// Lưu ý: Nếu SePay gửi Secret Key thay vì API Token, cần cập nhật giá trị này
define('SEPAY_IPN_SECRET_KEY', 'Thanhbinh1@'); // Secret Key từ IPN config
define('SEPAY_MATCH_PATTERN', 'SEPAY'); // Pattern to match in transaction content (SEPAY + số 3-10 ký tự)
define('SEPAY_ENVIRONMENT', 'production'); // 'sandbox' or 'production'
// URL webhook - Cập nhật theo URL thực tế trong SePay
// QUAN TRỌNG: Đảm bảo URL này khớp với cấu hình trong SePay dashboard
// URL webhook: https://sukien.info.vn/hooks/sepay-payment.php
// (File thực tế có thể đã được đặt ở root hoặc có rewrite rule)
define('SEPAY_CALLBACK_URL', 'https://sukien.info.vn/hooks/sepay-payment.php');

// SePay API URLs
if (SEPAY_ENVIRONMENT === 'sandbox') {
    define('SEPAY_BASE_URL', 'https://my.sepay.vn/userapi'); // Môi trường test
    define('SEPAY_CHECKOUT_URL', 'https://pay-sandbox.sepay.vn/v1/checkout'); // Checkout page sandbox
    define('SEPAY_PGAPI_URL', 'https://pgapi-sandbox.sepay.vn/v1'); // Payment Gateway API sandbox
} else {
    // URL chính xác theo tài liệu SePay
    define('SEPAY_BASE_URL', 'https://my.sepay.vn/userapi'); // Môi trường production
    define('SEPAY_CHECKOUT_URL', 'https://pay.sepay.vn/v1/checkout'); // Checkout page production
    define('SEPAY_PGAPI_URL', 'https://pgapi.sepay.vn/v1'); // Payment Gateway API production
}
?>
