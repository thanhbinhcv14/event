# 🚀 Hướng Dẫn Deploy Hybrid: PHP (Shared Hosting) + WebSocket (VPS)

## 🎯 Tổng Quan

**Phương án Hybrid:**
- ✅ **PHP:** Giữ nguyên trên shared hosting (cPanel) - `sukien.info.vn`
- ✅ **WebSocket:** Deploy trên VPS riêng - `ws.sukien.info.vn`
- ✅ **Frontend:** Kết nối đến cả 2 server

**Ưu điểm:**
- Tận dụng shared hosting hiện có
- Chỉ cần VPS nhỏ cho WebSocket ($5/tháng)
- Dễ migrate (chỉ sửa URL WebSocket)
- Hoạt động 100%

---

## 📋 Bước 1: Setup VPS cho WebSocket

### **1.1. Mua VPS**

**Nhà cung cấp khuyến nghị:**
- DigitalOcean ($5/tháng - 1GB RAM)
- Linode ($5/tháng - 1GB RAM)
- Vultr ($5/tháng - 1GB RAM)

**Yêu cầu tối thiểu:**
- RAM: 1GB (đủ cho Socket.IO)
- CPU: 1 core
- Storage: 25GB
- OS: Ubuntu 20.04+ hoặc CentOS 7+

### **1.2. SSH vào VPS**

```bash
ssh root@your-vps-ip
```

### **1.3. Cài Node.js**

**Ubuntu/Debian:**
```bash
# Cài Node.js 22.x
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt-get install -y nodejs

# Kiểm tra
node -v  # Phải >= 22.0.0
npm -v
```

**CentOS/RHEL:**
```bash
# Cài Node.js 22.x
curl -fsSL https://rpm.nodesource.com/setup_22.x | sudo bash -
sudo yum install -y nodejs

# Kiểm tra
node -v
npm -v
```

### **1.4. Cài PM2**

```bash
sudo npm install -g pm2
pm2 -v
```

### **1.5. Cài Nginx**

**Ubuntu/Debian:**
```bash
sudo apt-get update
sudo apt-get install -y nginx
```

**CentOS/RHEL:**
```bash
sudo yum install -y nginx
```

---

## 📋 Bước 2: Upload Code WebSocket lên VPS

### **2.1. Tạo Thư Mục**

```bash
mkdir -p /var/www/socket-server
cd /var/www/socket-server
```

### **2.2. Upload Files**

**Cách 1: SCP (từ máy local)**
```bash
# Từ máy local
scp socket-server.js package.json root@your-vps-ip:/var/www/socket-server/
```

**Cách 2: Git Clone**
```bash
# Trên VPS
cd /var/www/socket-server
git clone your-repo-url .
# Chỉ lấy files cần thiết
```

**Cách 3: Manual Upload**
- Dùng FileZilla hoặc SFTP client
- Upload vào `/var/www/socket-server/`

### **2.3. Files Cần Upload**

```
/var/www/socket-server/
├── socket-server.js      ✅ (file chính)
├── package.json          ✅ (dependencies)
└── package-lock.json     ✅ (optional, nhưng nên có)
```

**⚠️ KHÔNG cần:**
- `.passenger.json` (chỉ dùng cho cPanel)
- PHP files (giữ trên shared hosting)

### **2.4. Cài Dependencies**

```bash
cd /var/www/socket-server
npm install
```

**Kiểm tra:**
```bash
ls -la node_modules/express
ls -la node_modules/socket.io
```

---

## 📋 Bước 3: Sửa socket-server.js cho VPS

### **3.1. Sửa APP_BASE_PATH**

Mở `socket-server.js`, tìm dòng:
```javascript
const APP_BASE_PATH = process.env.APP_BASE_PATH 
    ? process.env.APP_BASE_PATH.replace(/\/$/, '')
    : (isLocalhost ? '' : '/nodeapp');
```

**Sửa thành:**
```javascript
// Hybrid: WebSocket chạy trên VPS riêng, không có base path
const APP_BASE_PATH = '';
```

### **3.2. Sửa CORS Origins**

Tìm dòng:
```javascript
const CORS_ORIGINS = (process.env.CORS_ORIGINS || 'https://sukien.info.vn,http://localhost,...')
```

**Sửa thành:**
```javascript
const CORS_ORIGINS = (process.env.CORS_ORIGINS || 'https://sukien.info.vn,https://www.sukien.info.vn,http://localhost,http://localhost:80,http://localhost:3000,http://localhost:3001,http://127.0.0.1,http://127.0.0.1:80')
    .split(',')
    .map(s => s.trim())
    .filter(Boolean);
```

**⚠️ QUAN TRỌNG:** Thêm domain của bạn vào CORS origins:
- `https://sukien.info.vn`
- `https://www.sukien.info.vn`
- Các subdomain khác nếu có

### **3.3. Sửa PORT (nếu cần)**

Giữ nguyên:
```javascript
const PORT = process.env.PORT || 3000;
```

---

## 📋 Bước 4: Setup PM2

### **4.1. Tạo PM2 Config**

Tạo file `ecosystem.config.js`:

```javascript
module.exports = {
  apps: [{
    name: 'socket-server',
    script: 'socket-server.js',
    instances: 1,
    exec_mode: 'fork',
    env: {
      NODE_ENV: 'production',
      PORT: 3000,
      CORS_ORIGINS: 'https://sukien.info.vn,https://www.sukien.info.vn'
    },
    error_file: './logs/err.log',
    out_file: './logs/out.log',
    log_date_format: 'YYYY-MM-DD HH:mm:ss Z',
    merge_logs: true,
    autorestart: true,
    watch: false,
    max_memory_restart: '500M'
  }]
};
```

### **4.2. Tạo Thư Mục Logs**

```bash
mkdir -p /var/www/socket-server/logs
```

### **4.3. Start với PM2**

```bash
cd /var/www/socket-server
pm2 start ecosystem.config.js
pm2 save
pm2 startup  # Tự động start khi server reboot
```

### **4.4. Kiểm Tra**

```bash
pm2 status
pm2 logs socket-server
```

**Kết quả mong đợi:**
```
✅ Socket.IO server started successfully!
📡 Server running on port: 3000
🔗 Socket.IO path: /socket.io
```

---

## 📋 Bước 5: Setup Nginx Reverse Proxy

### **5.1. Tạo Nginx Config**

Tạo file `/etc/nginx/sites-available/socket-server`:

```nginx
server {
    listen 80;
    server_name ws.sukien.info.vn;  # Subdomain cho WebSocket

    # WebSocket upgrade headers
    location /socket.io/ {
        proxy_pass http://127.0.0.1:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
        
        # Timeouts for WebSocket
        proxy_connect_timeout 7d;
        proxy_send_timeout 7d;
        proxy_read_timeout 7d;
    }

    # Health check endpoint
    location /health {
        proxy_pass http://127.0.0.1:3000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    }

    # API endpoints
    location /api/ {
        proxy_pass http://127.0.0.1:3000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    }
}
```

### **5.2. Enable Site**

```bash
sudo ln -s /etc/nginx/sites-available/socket-server /etc/nginx/sites-enabled/
sudo nginx -t  # Test config
sudo systemctl reload nginx
```

### **5.3. Setup SSL (Let's Encrypt)**

```bash
sudo apt-get install -y certbot python3-certbot-nginx
sudo certbot --nginx -d ws.sukien.info.vn
```

**Sau khi setup SSL, Nginx config sẽ tự động update với HTTPS.**

---

## 📋 Bước 6: Setup DNS

### **6.1. Tạo Subdomain**

Trong cPanel hoặc DNS provider:

1. Tạo **A Record:**
   - **Name:** `ws` (hoặc `ws.sukien.info.vn`)
   - **Type:** A
   - **Value:** IP của VPS
   - **TTL:** 3600 (hoặc default)

### **6.2. Kiểm Tra DNS**

```bash
# Từ máy local
nslookup ws.sukien.info.vn
# Hoặc
dig ws.sukien.info.vn
```

**Kết quả mong đợi:** Trỏ đến IP của VPS

---

## 📋 Bước 7: Sửa Frontend (chat.php & admin/chat.php)

### **7.1. Sửa getSocketServerURL() trong chat.php**

Tìm function `getSocketServerURL()` (khoảng dòng 1494):

**Trước:**
```javascript
const getSocketServerURL = function() {
    const hostname = window.location.hostname;
    const protocol = window.location.protocol;
    const port = window.location.port;
    
    // Production domain
    if (hostname.includes('sukien.info.vn') || hostname.includes('sukien')) {
        return protocol + '//' + hostname + '/nodeapp' + (port ? ':' + port : '');
    }
    
    // Localhost development
    return 'http://localhost:3000';
};
```

**Sau (Hybrid):**
```javascript
const getSocketServerURL = function() {
    const protocol = window.location.protocol;
    
    // Hybrid: WebSocket chạy trên VPS riêng
    // Thay 'ws.sukien.info.vn' bằng subdomain của bạn
    if (window.location.hostname.includes('sukien.info.vn')) {
        return protocol + '//ws.sukien.info.vn';  // VPS WebSocket server
    }
    
    // Localhost development
    return 'http://localhost:3000';
};
```

### **7.2. Sửa getSocketPath() trong chat.php**

**Giữ nguyên:**
```javascript
const getSocketPath = function() {
    return '/socket.io';
};
```

### **7.3. Sửa admin/chat.php**

Làm tương tự như `chat.php`:
- Sửa `getSocketServerURL()` (khoảng dòng 1139)
- Giữ nguyên `getSocketPath()`

---

## 📋 Bước 8: Test

### **8.1. Test Health Endpoint**

Truy cập trong browser:
```
https://ws.sukien.info.vn/health
```

**Kết quả mong đợi:**
```json
{
  "status": "ok",
  "timestamp": "...",
  "server": "Socket.IO Server",
  "path": "/socket.io",
  ...
}
```

### **8.2. Test Socket.IO**

Truy cập trong browser:
```
https://ws.sukien.info.vn/socket.io/?EIO=4&transport=polling
```

**Kết quả mong đợi:** Socket.IO handshake JSON (không phải "Cannot GET")

### **8.3. Test Từ Frontend**

1. Upload `chat.php` và `admin/chat.php` đã sửa lên shared hosting
2. Mở `https://sukien.info.vn/chat.php` (hoặc trang có chat)
3. Mở Console (F12)
4. Kiểm tra kết nối Socket.IO

**Console mong đợi:**
```
📡 Connecting to Socket.IO server: https://ws.sukien.info.vn
📡 Socket.IO path: /socket.io
✅ Connected to Socket.IO server
```

**Nếu có lỗi CORS:**
- Kiểm tra CORS origins trong `socket-server.js`
- Đảm bảo domain của bạn đã được thêm vào

---

## 📋 Bước 9: Firewall (Nếu Cần)

### **9.1. Mở Port 3000 (Nếu dùng IP trực tiếp)**

```bash
sudo ufw allow 3000/tcp
```

### **9.2. Mở Port 80/443 (Nginx)**

```bash
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
```

---

## 📋 Checklist

- [ ] VPS đã mua và setup
- [ ] Node.js đã cài (22.x)
- [ ] PM2 đã cài
- [ ] Nginx đã cài
- [ ] Code đã upload lên VPS
- [ ] `npm install` đã chạy
- [ ] `socket-server.js` đã sửa (APP_BASE_PATH, CORS)
- [ ] PM2 đã start
- [ ] Nginx config đã tạo
- [ ] SSL đã setup (Let's Encrypt)
- [ ] DNS đã trỏ subdomain đến VPS
- [ ] Frontend đã sửa (`chat.php`, `admin/chat.php`)
- [ ] Test health endpoint → OK
- [ ] Test Socket.IO → OK
- [ ] Test từ frontend → OK

---

## 🔧 Troubleshooting

### **Lỗi: ERR_CONNECTION_TIMED_OUT**

**Nguyên nhân:**
- VPS firewall chặn port
- PM2 chưa start
- Nginx chưa config đúng

**Giải pháp:**
```bash
# Kiểm tra PM2
pm2 status
pm2 logs socket-server

# Kiểm tra Nginx
sudo nginx -t
sudo systemctl status nginx

# Kiểm tra firewall
sudo ufw status
```

---

### **Lỗi: CORS Error**

**Nguyên nhân:**
- Domain chưa được thêm vào CORS origins

**Giải pháp:**
1. Sửa `socket-server.js`:
   ```javascript
   const CORS_ORIGINS = (process.env.CORS_ORIGINS || 'https://sukien.info.vn,https://www.sukien.info.vn,...')
   ```
2. Restart PM2:
   ```bash
   pm2 restart socket-server
   ```

---

### **Lỗi: DNS không trỏ đúng**

**Kiểm tra:**
```bash
nslookup ws.sukien.info.vn
```

**Nếu chưa trỏ:**
- Đợi 5-10 phút (DNS propagation)
- Hoặc kiểm tra DNS config trong cPanel

---

### **Lỗi: PM2 không tự start sau reboot**

**Giải pháp:**
```bash
pm2 startup
# Chạy lệnh mà PM2 hiển thị
pm2 save
```

---

## 💡 Tips

1. **Monitor:**
   - Dùng `pm2 monit` để monitor real-time
   - Setup UptimeRobot để monitor health endpoint

2. **Backup:**
   - Backup code thường xuyên
   - Backup PM2 config

3. **Logs:**
   - Xem logs: `pm2 logs socket-server`
   - Xem Nginx logs: `sudo tail -f /var/log/nginx/error.log`

4. **Performance:**
   - Monitor memory: `pm2 monit`
   - Nếu cần, tăng instances: `instances: 2` trong ecosystem.config.js

---

## 🎯 Kết Quả

Sau khi hoàn thành:
- ✅ PHP chạy trên shared hosting: `https://sukien.info.vn`
- ✅ WebSocket chạy trên VPS: `https://ws.sukien.info.vn`
- ✅ Frontend kết nối đến cả 2 server
- ✅ Real-time chat hoạt động 100%

---

## 📞 Hỗ Trợ

Nếu gặp vấn đề:
1. Kiểm tra logs: `pm2 logs socket-server`
2. Kiểm tra Nginx: `sudo nginx -t`
3. Kiểm tra DNS: `nslookup ws.sukien.info.vn`
4. Test health endpoint: `https://ws.sukien.info.vn/health`

Chúc bạn deploy thành công! 🎉

