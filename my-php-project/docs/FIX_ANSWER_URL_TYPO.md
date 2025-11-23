# 🔧 Fix: Answer URL Typo trong Stringee Dashboard

## ❌ Vấn đề phát hiện

Từ hình ảnh Stringee Dashboard, phát hiện **TYPO** trong Answer URL:

**❌ SAI:**
```
https://sukien.info.vn/src/controllers/stringee-callback.php?type=answe
```
- `type=answe` → **THIẾU chữ "r"**

**✅ ĐÚNG:**
```
https://sukien.info.vn/src/controllers/stringee-callback.php?type=answer
```
- `type=answer` → **ĐÚNG**

## 🔍 Nguyên nhân

Typo trong Answer URL khiến:
- ❌ Stringee gọi callback với `type=answe` (không hợp lệ)
- ❌ Backend không nhận diện được callback type
- ❌ SCCO response không được trả về đúng
- ❌ Lỗi `ANSWER_URL_SCCO_INCORRECT_FORMAT`

## ✅ Giải pháp

### **Bước 1: Sửa Answer URL trong Stringee Dashboard**

1. **Đăng nhập Stringee Dashboard:** https://dashboard.stringee.com/
2. **Vào Project → Settings → Config URL**
3. **Tìm Answer URL:**
   - Hiện tại: `https://sukien.info.vn/src/controllers/stringee-callback.php?type=answe`
4. **Click nút "Edit" (biểu tượng bút chì)**
5. **Sửa thành:**
   ```
   https://sukien.info.vn/src/controllers/stringee-callback.php?type=answer
   ```
   - Thêm chữ **"r"** vào cuối: `answe` → `answer`
6. **Lưu** (Save)

### **Bước 2: Kiểm tra Event URL**

Event URL đã đúng:
```
https://sukien.info.vn/src/controllers/stringee-callback.php?type=event
```
- ✅ Không cần sửa

### **Bước 3: Xác nhận Programmable Contact Center**

Từ hình ảnh:
- ✅ Checkbox "Enable Programmable Contact Center API for this Project" đang **UNCHECKED** (tắt)
- ✅ Đây là đúng cho Web-to-Web call

### **Bước 4: Test lại**

Sau khi sửa Answer URL:
1. Test Web-to-Web call
2. Kiểm tra logs xem callback có được gọi với `type=answer` không
3. Kiểm tra SCCO response có được trả về đúng không

## 📋 Checklist

- [ ] Đăng nhập Stringee Dashboard
- [ ] Vào Project → Settings → Config URL
- [ ] Tìm Answer URL
- [ ] Click "Edit" (biểu tượng bút chì)
- [ ] Sửa `type=answe` → `type=answer`
- [ ] Lưu (Save)
- [ ] Kiểm tra Event URL (đã đúng)
- [ ] Xác nhận PCC đang tắt (đã đúng)
- [ ] Test lại Web-to-Web call

## 🔍 Lưu ý

### **Nếu chỉ dùng Web-to-Web call:**

Bạn có thể **XÓA** Answer URL và Event URL:
1. Click nút "Delete" (biểu tượng X đỏ) bên cạnh Answer URL
2. Click nút "Delete" (biểu tượng X đỏ) bên cạnh Event URL
3. Lưu

**Lý do:**
- Web-to-Web call không cần Answer URL
- Web-to-Web call không cần Event URL
- Chỉ cần: Access Token + Web SDK

### **Nếu cần giữ Answer URL/Event URL:**

- ✅ Sửa typo: `type=answe` → `type=answer`
- ✅ Đảm bảo callback trả về đúng format SCCO
- ✅ Đảm bảo PCC đang tắt (đã đúng)

## ✅ Kết luận

**Nguyên nhân chính:**
- ❌ Answer URL có typo: `type=answe` (thiếu chữ "r")
- ✅ PCC đã tắt (đúng)
- ✅ Event URL đã đúng

**Giải pháp:**
1. ✅ Sửa Answer URL: `type=answe` → `type=answer`
2. ✅ Test lại Web-to-Web call
3. ⚠️ (Tùy chọn) Xóa Answer URL/Event URL nếu chỉ dùng Web-to-Web

