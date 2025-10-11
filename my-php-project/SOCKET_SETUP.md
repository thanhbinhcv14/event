# Socket.IO Real-time Setup Guide

## 🚀 Cài đặt Socket.IO cho hệ thống Event Management

### 1. Cài đặt Node.js và npm

Đảm bảo bạn đã cài đặt Node.js (phiên bản 14.0.0 trở lên):
```bash
node --version
npm --version
```

### 2. Cài đặt Dependencies

Trong thư mục `my-php-project`, chạy lệnh:
```bash
npm install
```

### 3. Khởi động Socket.IO Server

```bash
# Khởi động server
npm start

# Hoặc sử dụng nodemon để auto-reload (development)
npm run dev
```

Server sẽ chạy trên port 3000: `http://localhost:3000`

### 4. Cấu hình

#### Socket.IO Server (socket-server.js)
- **Port**: 3000 (có thể thay đổi trong file)
- **CORS**: Đã cấu hình để cho phép tất cả origins
- **Events**: Hỗ trợ các event real-time

#### PHP Integration (src/socket/socket-client.php)
- **URL**: http://localhost:3000
- **Port**: 3000
- **Auto-notification**: Tích hợp với các controller

### 5. Các tính năng Real-time

#### 🔔 Thông báo tự động
- **Đăng ký sự kiện mới**: Admin nhận thông báo ngay lập tức
- **Cập nhật trạng thái**: User nhận thông báo khi admin duyệt/từ chối
- **Ghi chú admin**: User nhận thông báo khi admin thêm ghi chú
- **Thông báo hệ thống**: Broadcast đến tất cả users

#### 💬 Chat Real-time
- **Tin nhắn tức thì**: Gửi/nhận tin nhắn real-time
- **Typing indicators**: Hiển thị ai đang nhập
- **User roles**: Phân biệt admin và user trong chat

#### 📊 Connection Status
- **Live status**: Hiển thị trạng thái kết nối
- **Auto-reconnect**: Tự động kết nối lại khi mất kết nối
- **Ping/Pong**: Kiểm tra kết nối định kỳ

### 6. Cách sử dụng

#### Test Socket.IO
1. Mở browser và truy cập: `http://localhost:3000`
2. Chọn role và đăng nhập
3. Test các tính năng real-time

#### Tích hợp vào trang hiện tại
```php
// Include Socket.IO client
require_once __DIR__ . '/../../src/socket/socket-client.php';
$socketClient = new SocketClient();

// Thêm script vào HTML
echo $socketClient->getClientScript($userId, $userRole, $userName);
```

#### Gửi thông báo từ PHP
```php
// Thông báo đăng ký sự kiện
notifyEventRegistration($eventId, $eventName, $userName, $userId);

// Thông báo cập nhật trạng thái
notifyEventStatusUpdate($eventId, $eventName, $status, $userName, $adminName, $userId);

// Thông báo ghi chú admin
notifyAdminComment($eventId, $eventName, $comment, $adminName, $userId);
```

### 7. Cấu trúc Files

```
my-php-project/
├── socket-server.js          # Socket.IO server
├── package.json              # Node.js dependencies
├── public/
│   └── socket-test.html      # Test interface
└── src/
    └── socket/
        └── socket-client.php # PHP integration
```

### 8. Events được hỗ trợ

#### Client → Server
- `authenticate`: Xác thực user
- `event_registered`: Thông báo đăng ký sự kiện
- `event_status_updated`: Cập nhật trạng thái sự kiện
- `admin_comment_added`: Thêm ghi chú admin
- `chat_message`: Gửi tin nhắn chat
- `typing_start/stop`: Typing indicators
- `ping`: Kiểm tra kết nối

#### Server → Client
- `authenticated`: Xác nhận xác thực
- `new_event_registration`: Thông báo sự kiện mới
- `event_status_change`: Thay đổi trạng thái sự kiện
- `admin_comment`: Ghi chú từ admin
- `admin_notification`: Thông báo admin
- `system_notification`: Thông báo hệ thống
- `chat_message`: Tin nhắn chat
- `user_typing`: Typing indicator
- `pong`: Phản hồi ping

### 9. Troubleshooting

#### Lỗi kết nối
```bash
# Kiểm tra port có bị chiếm không
netstat -an | grep 3000

# Thay đổi port trong socket-server.js
const PORT = process.env.PORT || 3001;
```

#### Lỗi CORS
```javascript
// Trong socket-server.js, cấu hình CORS
const io = socketIo(server, {
    cors: {
        origin: "http://localhost", // Thay đổi theo domain của bạn
        methods: ["GET", "POST"]
    }
});
```

#### Lỗi PHP integration
```php
// Kiểm tra URL và port trong socket-client.php
private $socketUrl = 'http://localhost';
private $socketPort = 3000;
```

### 10. Production Deployment

#### Sử dụng PM2
```bash
# Cài đặt PM2
npm install -g pm2

# Khởi động với PM2
pm2 start socket-server.js --name "event-socket"

# Auto-restart
pm2 startup
pm2 save
```

#### Nginx Proxy
```nginx
location /socket.io/ {
    proxy_pass http://localhost:3000;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}
```

### 11. Monitoring

#### Logs
```bash
# Xem logs
pm2 logs event-socket

# Monitor real-time
pm2 monit
```

#### Health Check
```javascript
// Thêm endpoint health check
app.get('/health', (req, res) => {
    res.json({
        status: 'ok',
        connectedUsers: connectedUsers.size,
        adminUsers: adminUsers.size,
        uptime: process.uptime()
    });
});
```

### 12. Security

#### Authentication
- User phải xác thực trước khi sử dụng
- Role-based access control
- Session validation

#### Rate Limiting
```javascript
// Thêm rate limiting
const rateLimit = require('express-rate-limit');
const limiter = rateLimit({
    windowMs: 15 * 60 * 1000, // 15 minutes
    max: 100 // limit each IP to 100 requests per windowMs
});
app.use(limiter);
```

### 13. Performance

#### Connection Pooling
- Tối ưu số lượng kết nối đồng thời
- Auto-disconnect inactive users
- Memory management

#### Scaling
- Sử dụng Redis adapter cho multiple servers
- Load balancing
- Cluster mode

---

## 🎯 Kết luận

Socket.IO đã được tích hợp hoàn chỉnh vào hệ thống Event Management với các tính năng:

✅ **Real-time notifications**  
✅ **Live chat system**  
✅ **Connection status monitoring**  
✅ **Role-based access control**  
✅ **Auto-reconnection**  
✅ **Mobile responsive**  
✅ **Production ready**

Hệ thống giờ đây có thể cung cấp trải nghiệm real-time cho cả admin và user, giúp tăng tính tương tác và hiệu quả quản lý sự kiện.
