# Tổng hợp các điều kiện đã có trong hệ thống

## 📋 REGISTER.PHP - Đăng ký sự kiện

### ✅ STEP 1: Thông tin cơ bản

#### 1. Validation trường bắt buộc
- ✅ **Tên sự kiện** (`eventName`) - Required
- ✅ **Loại sự kiện** (`eventType`) - Required
- ✅ **Ngày bắt đầu** (`eventDate`) - Required
- ✅ **Giờ bắt đầu** (`eventTime`) - Required
- ✅ **Ngày kết thúc** (`eventEndDate`) - Required
- ✅ **Giờ kết thúc** (`eventEndTime`) - Required

#### 2. Validation ngày tháng
- ✅ **Ngày bắt đầu không được là quá khứ**
  ```javascript
  if (eventStartDateObj < today) {
      showError('Ngày bắt đầu không được là ngày trong quá khứ');
  }
  ```

- ✅ **Ngày kết thúc không được trước ngày bắt đầu**
  ```javascript
  if (eventEndDateObj < eventStartDateObj) {
      showError('Ngày kết thúc không được trước ngày bắt đầu');
  }
  ```

- ✅ **Giờ kết thúc phải sau giờ bắt đầu (khi cùng ngày)**
  ```javascript
  if (startDate === endDate && startTime && endTime && endTime <= startTime) {
      showError('Giờ kết thúc phải sau giờ bắt đầu khi cùng ngày');
  }
  ```

- ✅ **Thời gian kết thúc không được trong quá khứ**
  ```javascript
  if (eventEndDateTime < now) {
      showError('Cảnh báo: Thời gian kết thúc sự kiện đã qua...');
  }
  ```

#### 3. Điều kiện thời gian đăng ký
- ✅ **Phải đăng ký trước ít nhất 12 giờ**
  ```javascript
  function checkMinimum12Hours(eventDate, eventTime) {
      const eventStartDateTime = new Date(eventDate + 'T' + eventTime);
      const now = new Date();
      const minDateTime = new Date(now.getTime() + (12 * 60 * 60 * 1000));
      
      if (eventStartDateTime < minDateTime) {
          return { valid: false, hoursLeft: hoursLeft };
      }
      return { valid: true };
  }
  ```
  - Kiểm tra khi thay đổi ngày bắt đầu
  - Kiểm tra khi thay đổi giờ bắt đầu
  - Kiểm tra khi submit form

### ✅ STEP 2: Chọn địa điểm

#### 1. Validation địa điểm
- ✅ **Phải chọn địa điểm**
  ```javascript
  if (!selectedLocation) {
      showError('Vui lòng chọn địa điểm');
      return false;
  }
  ```

#### 2. Validation cho địa điểm trong nhà
- ✅ **Phải chọn loại thuê TRƯỚC khi chọn phòng**
  ```javascript
  if (isIndoor && !selectedLocation.selectedRoomRentalType) {
      showError('Vui lòng chọn loại thuê (theo giờ hoặc theo ngày) trước khi chọn phòng');
  }
  ```

- ✅ **Phải chọn phòng cho địa điểm trong nhà**
  ```javascript
  if (isIndoor && !selectedLocation.selectedRoomId) {
      showError('Vui lòng chọn phòng cho địa điểm trong nhà');
  }
  ```

- ✅ **Double check: Phải có loại thuê khi đã chọn phòng**
  ```javascript
  if (isIndoor && selectedLocation.selectedRoom && !selectedLocation.selectedRoomRentalType) {
      showError('Vui lòng chọn loại thuê (theo giờ hoặc theo ngày) cho phòng');
  }
  ```

#### 3. Validation cho địa điểm ngoài trời
- ✅ **Phải chọn loại thuê nếu địa điểm có "Cả hai"**
  ```javascript
  if (!isIndoor && selectedLocation.LoaiThue === 'Cả hai' && !selectedLocation.selectedRentalType) {
      showError('Vui lòng chọn loại thuê (theo giờ hoặc theo ngày) cho địa điểm này');
  }
  ```

#### 4. Kiểm tra phòng có sẵn
- ✅ **Lọc phòng đã được đặt trong khoảng thời gian**
  - Loại trừ sự kiện có trạng thái "Từ chối" hoặc "Đã hủy"
  - Kiểm tra overlap thời gian
  - Chỉ hiển thị phòng còn trống

### ✅ STEP 3: Thiết bị & Xác nhận

#### 1. Validation thiết bị (Optional)
- ✅ **Thiết bị là tùy chọn** - Không bắt buộc chọn

#### 2. Kiểm tra số lượng thiết bị có sẵn
- ✅ **Kiểm tra số lượng thiết bị đã được đặt trong ngày**
  ```javascript
  function checkEquipmentAvailability(equipmentId) {
      // Gọi API check_equipment_availability
      // Tính: available = total - booked
      // Loại trừ sự kiện "Từ chối" và "Đã hủy"
  }
  ```

- ✅ **Tự động tắt checkbox nếu hết hàng**
  ```javascript
  if (available <= 0) {
      $('#equipment_' + equipmentId).prop('checked', false);
      toggleEquipment(equipmentId, '', 0);
  }
  ```

- ✅ **Tự động điều chỉnh số lượng nếu vượt quá số có sẵn**
  ```javascript
  if (currentQuantity > available) {
      quantityInput.val(available);
      updateEquipmentQuantity(equipmentId, available);
  }
  ```

#### 3. Kiểm tra combo có sẵn
- ✅ **Kiểm tra tất cả thiết bị trong combo có đủ không**
  ```javascript
  function checkAllComboAvailability() {
      // Gọi API check_combo_availability
      // Kiểm tra từng thiết bị trong combo
      // Tắt combo nếu không đủ thiết bị
  }
  ```

- ✅ **Tắt combo nếu không đủ thiết bị**
  ```javascript
  if (combo.available === false) {
      // Hiển thị overlay "Không đủ thiết bị"
      // Disable combo card
      // Hiển thị thông báo chi tiết thiết bị thiếu
  }
  ```

- ✅ **Ngăn chọn combo không khả dụng**
  ```javascript
  if (combo && combo.available === false) {
      showError('Combo này không đủ thiết bị trong khoảng thời gian đã chọn...');
      return;
  }
  ```

#### 4. Tự động kiểm tra lại khi thay đổi
- ✅ **Khi thay đổi ngày/giờ → Re-check equipment availability**
- ✅ **Khi thay đổi ngày/giờ → Re-check combo availability**
- ✅ **Khi vào step 3 → Re-check tất cả equipment và combo**

---

## 📋 MY-EVENTS.PHP - Quản lý sự kiện

### ✅ Điều kiện hiển thị sự kiện

#### 1. Kiểm tra sự kiện đã hết hạn
- ✅ **Kiểm tra thời gian kết thúc đã qua**
  ```javascript
  function isEventExpired(event) {
      if (!event.NgayKetThuc) return false;
      const eventEndTime = new Date(event.NgayKetThuc);
      const now = new Date();
      return eventEndTime < now;
  }
  ```

- ✅ **Hiển thị cảnh báo nếu sự kiện đã hết hạn và chưa thanh toán đủ**
  ```javascript
  const isExpired = isEventExpired(event);
  const isFullyPaid = (event.TrangThaiThanhToan || 'Chưa thanh toán') === 'Đã thanh toán đủ';
  const showExpiredWarning = isExpired && !isFullyPaid;
  ```

#### 2. Điều kiện hiển thị nút hành động

**Nút "Sửa" và "Hủy":**
- ✅ Chỉ hiển thị khi `TrangThaiDuyet === 'Chờ duyệt'`

**Nút "Thanh toán":**
- ✅ `TrangThaiDuyet === 'Đã duyệt'`
- ✅ `TrangThaiThanhToan === 'Chưa thanh toán'`
- ✅ `PendingPayments == 0` (không có thanh toán đang chờ)
- ✅ `!isExpired` (chưa hết hạn)

**Nút "Hết hạn thanh toán":**
- ✅ `isExpired && !isFullyPaid` (đã hết hạn và chưa thanh toán đủ)
- ✅ Button bị disabled

**Nút "Đánh giá":**
- ✅ `TrangThaiDuyet === 'Đã duyệt'`
- ✅ `TrangThaiThanhToan === 'Đã thanh toán đủ'`

#### 3. Điều kiện thanh toán
- ✅ **Kiểm tra phương thức thanh toán đã chọn**
  ```javascript
  if (!paymentMethod) {
      alert('Vui lòng chọn phương thức thanh toán');
      return;
  }
  ```

- ✅ **Xác nhận trước khi thanh toán**
  ```javascript
  if (confirm(`Xác nhận thanh toán ${amount} VNĐ qua ${method}?`)) {
      // Process payment
  }
  ```

#### 4. Điều kiện đánh giá
- ✅ **Điểm đánh giá bắt buộc (1-5 sao)**
  ```javascript
  if (overallRating == 0) {
      alert('Vui lòng chọn điểm đánh giá tổng thể!');
      return;
  }
  ```

- ✅ **Nội dung đánh giá bắt buộc**
  ```javascript
  if (!comment.trim()) {
      alert('Vui lòng nhập nội dung đánh giá!');
      return;
  }
  ```

- ✅ **Giới hạn độ dài: Tối đa 1000 ký tự**

---

## 🔒 BACKEND VALIDATION (event-register.php)

### ✅ Validation khi đăng ký sự kiện

#### 1. Kiểm tra đăng nhập
- ✅ **Phải đăng nhập**
  ```php
  if (!isset($_SESSION['user'])) {
      echo json_encode(['success' => false, 'error' => 'Chưa đăng nhập']);
      exit();
  }
  ```

#### 2. Validation trường bắt buộc
- ✅ **Các trường bắt buộc:**
  - `event_name`
  - `event_date`
  - `event_time`
  - `event_end_date`
  - `event_end_time`
  - `location_id`

#### 3. Validation ngày tháng
- ✅ **Ngày bắt đầu không được là quá khứ**
  ```php
  if ($eventDate < $today) {
      echo json_encode(['success' => false, 'error' => 'Ngày bắt đầu không được là ngày trong quá khứ']);
      exit();
  }
  ```

- ✅ **Ngày kết thúc không được trước ngày bắt đầu**
  ```php
  if ($eventEndDate < $eventDate) {
      echo json_encode(['success' => false, 'error' => 'Ngày kết thúc không được trước ngày bắt đầu']);
      exit();
  }
  ```

#### 4. Điều kiện thời gian đăng ký
- ✅ **Phải đăng ký trước ít nhất 12 giờ**
  ```php
  $eventStartDateTime = new DateTime($eventDate . ' ' . $eventTime);
  $now = new DateTime();
  $minDateTime = clone $now;
  $minDateTime->modify('+12 hours');
  
  if ($eventStartDateTime < $minDateTime) {
      echo json_encode(['success' => false, 'error' => 'Sự kiện phải được đăng ký trước ít nhất 12 giờ']);
      exit();
  }
  ```

---

## 📊 KIỂM TRA SỐ LƯỢNG THIẾT BỊ

### ✅ API: check_equipment_availability

**Điều kiện:**
- ✅ Loại trừ sự kiện có `TrangThaiDuyet = 'Từ chối'`
- ✅ Loại trừ sự kiện có `TrangThaiDuyet = 'Đã hủy'`
- ✅ Kiểm tra overlap thời gian:
  ```sql
  WHERE (
      (dl.NgayBatDau <= ? AND dl.NgayKetThuc >= ?) OR
      (dl.NgayBatDau <= ? AND dl.NgayKetThuc >= ?) OR
      (dl.NgayBatDau >= ? AND dl.NgayKetThuc <= ?)
  )
  ```
- ✅ Loại trừ sự kiện đang chỉnh sửa (nếu có `event_id`)
- ✅ Tính: `available = total - booked`

### ✅ API: check_combo_availability

**Điều kiện:**
- ✅ Kiểm tra từng thiết bị trong combo
- ✅ Mỗi thiết bị phải có `available >= required`
- ✅ Nếu một thiết bị không đủ → Combo không khả dụng
- ✅ Trả về chi tiết từng thiết bị: required, available, total, booked, sufficient

---

## 🎯 TÓM TẮT CÁC ĐIỀU KIỆN CHÍNH

### ✅ Đăng ký sự kiện (register.php)
1. ✅ Tất cả trường bắt buộc phải điền
2. ✅ Ngày bắt đầu không được là quá khứ
3. ✅ Ngày kết thúc không được trước ngày bắt đầu
4. ✅ Giờ kết thúc phải sau giờ bắt đầu (cùng ngày)
5. ✅ Thời gian kết thúc không được trong quá khứ
6. ✅ **Phải đăng ký trước ít nhất 12 giờ**
7. ✅ Địa điểm trong nhà: Phải chọn loại thuê và phòng
8. ✅ Địa điểm ngoài trời "Cả hai": Phải chọn loại thuê
9. ✅ Thiết bị: Kiểm tra số lượng có sẵn
10. ✅ Combo: Kiểm tra đủ thiết bị, tắt nếu không đủ

### ✅ Quản lý sự kiện (my-events.php)
1. ✅ Kiểm tra sự kiện đã hết hạn
2. ✅ Hiển thị cảnh báo nếu hết hạn và chưa thanh toán
3. ✅ Điều kiện hiển thị nút: Sửa, Hủy, Thanh toán, Đánh giá
4. ✅ Kiểm tra phương thức thanh toán đã chọn
5. ✅ Đánh giá: Điểm và nội dung bắt buộc

### ✅ Backend (event-register.php)
1. ✅ Phải đăng nhập
2. ✅ Validation tất cả trường bắt buộc
3. ✅ Validation ngày tháng
4. ✅ **Phải đăng ký trước ít nhất 12 giờ**

---

## 🔄 TỰ ĐỘNG KIỂM TRA LẠI

### ✅ Khi thay đổi ngày/giờ
- ✅ Re-check equipment availability
- ✅ Re-check combo availability
- ✅ Re-check room availability (cho địa điểm trong nhà)

### ✅ Khi vào step 3
- ✅ Re-check tất cả equipment đã chọn
- ✅ Re-check tất cả combo

---

## 📝 GHI CHÚ

- ✅ Tất cả validation đều có thông báo lỗi rõ ràng bằng tiếng Việt
- ✅ Validation được thực hiện ở cả frontend (JavaScript) và backend (PHP)
- ✅ Có double-check để đảm bảo tính nhất quán
- ✅ Tự động cập nhật khi thay đổi dữ liệu liên quan

