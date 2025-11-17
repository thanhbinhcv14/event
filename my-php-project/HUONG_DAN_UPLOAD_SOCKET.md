# Hướng dẫn Upload socket-server.js lên VPS

## Phương pháp 1: Sử dụng SCP (Khuyến nghị)

### Bước 1: Mở PowerShell hoặc Command Prompt
- Nhấn `Win + R`
- Gõ `powershell` hoặc `cmd`
- Nhấn Enter

### Bước 2: Di chuyển đến thư mục project
```powershell
cd C:\xampp\htdocs\event\my-php-project
```

### Bước 3: Upload file lên VPS
```powershell
scp socket-server.js root@152.42.246.239:/root/socket-server/
```

**Lưu ý:**
- Lần đầu tiên sẽ hỏi xác nhận SSH key, gõ `yes` và nhấn Enter
- Nhập mật khẩu VPS khi được yêu cầu
- File sẽ được upload vào thư mục `/root/socket-server/`

### Bước 4: SSH vào VPS để restart server
```powershell
ssh root@152.42.246.239
```

Sau khi đăng nhập, chạy các lệnh sau:

```bash
# Kiểm tra PM2 process
pm2 list

# Restart socket server
pm2 restart socket-server

# Hoặc nếu tên process khác, kiểm tra:
pm2 list

# Xem logs để đảm bảo server chạy tốt
pm2 logs socket-server --lines 50
```

## Phương pháp 2: Sử dụng Batch Script (Tự động)

### Chạy file batch script:
```powershell
.\upload-socket-server.bat
```

Script sẽ tự động:
1. Upload file lên VPS
2. SSH vào VPS
3. Restart PM2 process
4. Hiển thị logs

## Phương pháp 3: Sử dụng FileZilla hoặc WinSCP (GUI)

### Bước 1: Mở FileZilla/WinSCP
- Host: `152.42.246.239`
- Username: `root`
- Password: [Mật khẩu VPS]
- Port: `22`

### Bước 2: Upload file
- Kéo thả file `socket-server.js` từ máy local vào thư mục `/root/socket-server/` trên VPS
- Sau đó SSH vào VPS và restart PM2 như Bước 4 ở trên

## Kiểm tra sau khi upload

### 1. Kiểm tra file đã được upload:
```bash
ssh root@152.42.246.239
ls -lh /root/socket-server/socket-server.js
```

### 2. Kiểm tra PM2 process:
```bash
pm2 list
pm2 logs socket-server --lines 20
```

### 3. Kiểm tra server đang chạy:
- Mở browser và test WebSocket connection
- Kiểm tra console logs trong browser
- Kiểm tra PM2 logs trên VPS

## Troubleshooting

### Lỗi "Permission denied"
```bash
# Kiểm tra quyền file
ls -l /root/socket-server/socket-server.js

# Nếu cần, set quyền
chmod 644 /root/socket-server/socket-server.js
```

### Lỗi "PM2 process not found"
```bash
# Kiểm tra tất cả processes
pm2 list

# Nếu không có, start lại:
cd /root/socket-server
pm2 start socket-server.js --name socket-server
pm2 save
```

### Lỗi "Connection refused"
```bash
# Kiểm tra port đang được sử dụng
netstat -tulpn | grep 3000

# Kiểm tra firewall
ufw status

# Kiểm tra Nginx config
nginx -t
systemctl status nginx
```

## Lưu ý quan trọng

1. **Luôn backup file cũ trước khi upload:**
   ```bash
   cp /root/socket-server/socket-server.js /root/socket-server/socket-server.js.backup
   ```

2. **Kiểm tra syntax trước khi upload:**
   ```bash
   node -c socket-server.js
   ```

3. **Sau khi upload, luôn restart PM2:**
   ```bash
   pm2 restart socket-server
   ```

4. **Kiểm tra logs ngay sau khi restart:**
   ```bash
   pm2 logs socket-server --lines 50
   ```

## Quick Command (Copy & Paste)

```bash
# Upload file
scp socket-server.js root@152.42.246.239:/root/socket-server/

# SSH và restart
ssh root@152.42.246.239 "cd /root/socket-server && pm2 restart socket-server && pm2 logs socket-server --lines 20"
```

