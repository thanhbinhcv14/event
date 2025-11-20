<?php
/**
 * LiveKit Configuration
 * Cấu hình cho LiveKit Server SDK
 */

// LiveKit Server URL (ví dụ: https://your-livekit-server.com)
// Nếu dùng LiveKit Cloud: https://your-project.livekit.cloud
// Nếu self-hosted: https://your-domain.com hoặc http://localhost:7880
define('LIVEKIT_URL', getenv('LIVEKIT_URL') ?: 'https://your-livekit-server.com');

// LiveKit API Key (từ LiveKit Dashboard)
define('LIVEKIT_API_KEY', getenv('LIVEKIT_API_KEY') ?: '');

// LiveKit API Secret (từ LiveKit Dashboard)
define('LIVEKIT_API_SECRET', getenv('LIVEKIT_API_SECRET') ?: '');

// LiveKit WebSocket URL (thường giống LIVEKIT_URL nhưng với protocol ws:// hoặc wss://)
// Nếu LIVEKIT_URL là https://, thì LIVEKIT_WS_URL sẽ là wss://
$livekitUrl = LIVEKIT_URL;
if (strpos($livekitUrl, 'https://') === 0) {
    $wsUrl = str_replace('https://', 'wss://', $livekitUrl);
} else if (strpos($livekitUrl, 'http://') === 0) {
    $wsUrl = str_replace('http://', 'ws://', $livekitUrl);
} else {
    $wsUrl = $livekitUrl;
}
define('LIVEKIT_WS_URL', getenv('LIVEKIT_WS_URL') ?: $wsUrl);

// Token expiration time (mặc định 6 giờ = 21600 giây)
define('LIVEKIT_TOKEN_TTL', getenv('LIVEKIT_TOKEN_TTL') ?: 21600);

// Room settings
define('LIVEKIT_ROOM_EMPTY_TIMEOUT', 10); // Thời gian chờ room trống trước khi xóa (giây)
define('LIVEKIT_ROOM_MAX_PARTICIPANTS', 2); // Tối đa 2 người cho voice/video call

