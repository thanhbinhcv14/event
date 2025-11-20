# Hướng dẫn cài đặt và cấu hình LiveKit

## Tổng quan

Hệ thống đã được tích hợp LiveKit để xử lý voice call và video call thay vì WebRTC thuần. LiveKit cung cấp giải pháp real-time communication ổn định và dễ quản lý hơn.

## Bước 1: Cài đặt LiveKit Server SDK cho PHP

```bash
cd my-php-project
composer require agence104/livekit-server-sdk
```

## Bước 2: Cấu hình LiveKit Server

Bạn có 2 lựa chọn:

### Lựa chọn 1: Sử dụng LiveKit Cloud (Khuyến nghị)

1. Đăng ký tài khoản tại [LiveKit Cloud](https://cloud.livekit.io/)
2. Tạo project mới
3. Lấy API Key và API Secret từ Dashboard
4. Cập nhật file `config/livekit.php`:

```php
define('LIVEKIT_URL', 'https://your-project.livekit.cloud');
define('LIVEKIT_API_KEY', 'your-api-key');
define('LIVEKIT_API_SECRET', 'your-api-secret');
```

### Lựa chọn 2: Self-hosted LiveKit Server

1. Cài đặt LiveKit Server (xem [LiveKit Documentation](https://docs.livekit.io/))
2. Cập nhật file `config/livekit.php`:

```php
define('LIVEKIT_URL', 'https://your-livekit-server.com');
define('LIVEKIT_API_KEY', 'your-api-key');
define('LIVEKIT_API_SECRET', 'your-api-secret');
```

## Bước 3: Cấu hình Environment Variables (Tùy chọn)

Thay vì chỉnh sửa trực tiếp trong `config/livekit.php`, bạn có thể sử dụng environment variables:

```bash
export LIVEKIT_URL="https://your-project.livekit.cloud"
export LIVEKIT_API_KEY="your-api-key"
export LIVEKIT_API_SECRET="your-api-secret"
export LIVEKIT_WS_URL="wss://your-project.livekit.cloud"
export LIVEKIT_TOKEN_TTL="21600"
```

## Bước 4: Kiểm tra cài đặt

1. Chạy `composer install` để cài đặt dependencies
2. Kiểm tra file `config/livekit.php` đã được cấu hình đúng
3. Test tạo token bằng cách truy cập:
   ```
   https://your-domain.com/src/controllers/livekit-controller.php?action=get_token&room_name=test
   ```

## Cấu trúc Files

- `config/livekit.php`: Cấu hình LiveKit (URL, API Key, Secret)
- `src/controllers/livekit-controller.php`: Controller tạo access token và quản lý rooms
- `chat.php`: Client-side JavaScript sử dụng LiveKit Client SDK

## API Endpoints

### GET/POST `/src/controllers/livekit-controller.php?action=get_token`

Tạo LiveKit access token cho người dùng join room.

**Parameters:**
- `room_name` (optional): Tên room
- `call_id` (optional): ID cuộc gọi (sẽ tự động tạo room_name từ call_id)
- `conversation_id` (optional): ID cuộc trò chuyện

**Response:**
```json
{
  "success": true,
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "room_name": "call_123_conv_456",
  "ws_url": "wss://your-project.livekit.cloud",
  "identity": "user_1",
  "display_name": "Người dùng"
}
```

### POST `/src/controllers/livekit-controller.php?action=create_room`

Tạo LiveKit room (nếu cần).

**Parameters:**
- `room_name`: Tên room
- `conversation_id` (optional): ID cuộc trò chuyện

## Client-side Usage

LiveKit Client SDK đã được tích hợp vào `chat.php`. Khi người dùng:
- Gọi video/voice: Hệ thống tự động lấy token và join LiveKit room
- Chấp nhận cuộc gọi: Tự động join room và bắt đầu stream
- Kết thúc cuộc gọi: Tự động disconnect và cleanup

## Troubleshooting

### Lỗi: "LiveKit chưa được cấu hình"
- Kiểm tra `LIVEKIT_API_KEY` và `LIVEKIT_API_SECRET` đã được set trong `config/livekit.php`
- Đảm bảo các giá trị không rỗng

### Lỗi: "Cannot connect to LiveKit room"
- Kiểm tra `LIVEKIT_URL` và `LIVEKIT_WS_URL` đúng
- Kiểm tra firewall/network có chặn WebSocket connection không
- Kiểm tra LiveKit server đang chạy

### Lỗi: "Token invalid"
- Kiểm tra API Key và Secret đúng
- Kiểm tra token chưa hết hạn (mặc định 6 giờ)

## Tài liệu tham khảo

- [LiveKit Documentation](https://docs.livekit.io/)
- [LiveKit Server SDK PHP](https://github.com/agence104/livekit-server-sdk-php)
- [LiveKit Client SDK JS](https://docs.livekit.io/reference/client-sdk-js/)

