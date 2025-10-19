<?php
session_start();
require_once __DIR__ . '/../src/auth/auth.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo Giá Biến Động - Event Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-top: 80px;
        }
        
        .demo-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            margin: 20px auto;
            padding: 40px;
            max-width: 1200px;
        }
        
        .price-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .price-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .price-highlight {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            margin: 10px 0;
        }
        
        .time-slot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            margin: 5px 0;
            border-radius: 8px;
            background: #f8f9fa;
            border-left: 4px solid #667eea;
        }
        
        .time-slot.morning { border-left-color: #28a745; }
        .time-slot.afternoon { border-left-color: #ffc107; }
        .time-slot.evening { border-left-color: #fd7e14; }
        .time-slot.night { border-left-color: #6f42c1; }
        
        .savings-badge {
            background: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .premium-badge {
            background: #dc3545;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container">
        <div class="demo-container">
            <h1 class="text-center mb-4">
                <i class="fas fa-chart-line text-primary"></i>
                Demo Hệ Thống Giá Biến Động
            </h1>
            
            <div class="row">
                <!-- Input Form -->
                <div class="col-lg-4">
                    <div class="price-card">
                        <h4><i class="fas fa-cog text-primary"></i> Cài Đặt Tham Số</h4>
                        
                        <div class="mb-3">
                            <label class="form-label">Giá gốc địa điểm (VNĐ)</label>
                            <input type="number" class="form-control" id="basePrice" value="100000000" min="1000000">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Ngày bắt đầu</label>
                            <input type="date" class="form-control" id="startDate">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Giờ bắt đầu</label>
                            <input type="time" class="form-control" id="startTime" value="18:00">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Thời lượng (giờ)</label>
                            <input type="number" class="form-control" id="duration" value="4" min="1" max="24">
                        </div>
                        
                        <button class="btn btn-primary w-100" onclick="calculateDynamicPrice()">
                            <i class="fas fa-calculator"></i> Tính Giá Động
                        </button>
                    </div>
                    
                    <!-- Quick Examples -->
                    <div class="price-card">
                        <h5><i class="fas fa-lightbulb text-warning"></i> Ví Dụ Nhanh</h5>
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-success btn-sm" onclick="setExample('morning')">
                                Buổi sáng (6:00-12:00)
                            </button>
                            <button class="btn btn-outline-warning btn-sm" onclick="setExample('afternoon')">
                                Buổi chiều (12:00-18:00)
                            </button>
                            <button class="btn btn-outline-danger btn-sm" onclick="setExample('evening')">
                                Buổi tối (18:00-22:00)
                            </button>
                            <button class="btn btn-outline-dark btn-sm" onclick="setExample('weekend')">
                                Cuối tuần
                            </button>
                            <button class="btn btn-outline-primary btn-sm" onclick="setExample('holiday')">
                                Ngày lễ
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Results -->
                <div class="col-lg-8">
                    <div id="results">
                        <div class="text-center text-muted">
                            <i class="fas fa-calculator fa-3x mb-3"></i>
                            <p>Nhập thông tin và nhấn "Tính Giá Động" để xem kết quả</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set default date to today
        document.getElementById('startDate').value = new Date().toISOString().split('T')[0];
        
        function calculateDynamicPrice() {
            const basePrice = parseInt(document.getElementById('basePrice').value);
            const startDate = document.getElementById('startDate').value;
            const startTime = document.getElementById('startTime').value;
            const duration = parseInt(document.getElementById('duration').value);
            
            if (!basePrice || !startDate || !startTime || !duration) {
                alert('Vui lòng điền đầy đủ thông tin');
                return;
            }
            
            const startDateTime = startDate + ' ' + startTime;
            const endDateTime = new Date(new Date(startDateTime).getTime() + duration * 60 * 60 * 1000);
            
            // Call dynamic pricing API
            fetch('../src/controllers/dynamic-pricing.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'get_pricing_details',
                    base_price: basePrice,
                    start_datetime: startDateTime,
                    end_datetime: endDateTime.toISOString().slice(0, 19).replace('T', ' ')
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayResults(data.details);
                } else {
                    alert('Lỗi: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Lỗi kết nối đến server');
            });
        }
        
        function displayResults(details) {
            const resultsDiv = document.getElementById('results');
            
            let html = `
                <div class="price-card">
                    <h4><i class="fas fa-chart-bar text-primary"></i> Kết Quả Tính Giá</h4>
                    
                    <div class="price-highlight">
                        <h3>Tổng Giá: ${formatPrice(details.total_price)} VNĐ</h3>
                        <p class="mb-0">Tiết kiệm: ${formatPrice(details.savings)} VNĐ</p>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-info-circle text-info"></i> Thông Tin Cơ Bản</h6>
                            <p><strong>Giá gốc:</strong> ${formatPrice(details.base_price)} VNĐ/giờ</p>
                            <p><strong>Thời gian:</strong> ${details.start_time} - ${details.end_time}</p>
                            <p><strong>Thời lượng:</strong> ${details.duration_hours} giờ</p>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-calculator text-success"></i> Tính Toán</h6>
                            <p><strong>Giá gốc tổng:</strong> ${formatPrice(details.base_price * details.duration_hours)} VNĐ</p>
                            <p><strong>Giá động tổng:</strong> ${formatPrice(details.total_price)} VNĐ</p>
                            <p><strong>Chênh lệch:</strong> ${formatPrice(details.base_price * details.duration_hours - details.total_price)} VNĐ</p>
                        </div>
                    </div>
                </div>
                
                <div class="price-card">
                    <h5><i class="fas fa-clock text-primary"></i> Chi Tiết Theo Giờ</h5>
            `;
            
            details.time_breakdown.forEach(slot => {
                const multiplier = slot.multiplier;
                const isSavings = multiplier < 1;
                const isPremium = multiplier > 1.2;
                
                html += `
                    <div class="time-slot ${slot.time_type}">
                        <div>
                            <strong>${slot.time}</strong> - ${slot.date}
                            <br><small class="text-muted">${getTimeTypeName(slot.time_type)} - ${getDayTypeName(slot.day_type)}</small>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold">${formatPrice(slot.price)} VNĐ</div>
                            <div class="small text-muted">x${multiplier.toFixed(2)}</div>
                            ${isSavings ? '<span class="savings-badge">Tiết kiệm</span>' : ''}
                            ${isPremium ? '<span class="premium-badge">Cao</span>' : ''}
                        </div>
                    </div>
                `;
            });
            
            html += `
                </div>
                
                <div class="price-card">
                    <h5><i class="fas fa-lightbulb text-warning"></i> Gợi Ý Tiết Kiệm</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Thời gian tốt nhất:</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success"></i> Buổi sáng (6:00-12:00)</li>
                                <li><i class="fas fa-check text-success"></i> Ngày thường (T2-T6)</li>
                                <li><i class="fas fa-times text-danger"></i> Tránh cuối tuần</li>
                                <li><i class="fas fa-times text-danger"></i> Tránh ngày lễ</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Mẹo tiết kiệm:</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-lightbulb text-warning"></i> Đặt trước 1-2 tháng</li>
                                <li><i class="fas fa-lightbulb text-warning"></i> Chọn sự kiện ngắn hơn</li>
                                <li><i class="fas fa-lightbulb text-warning"></i> Tận dụng combo giảm giá</li>
                            </ul>
                        </div>
                    </div>
                </div>
            `;
            
            resultsDiv.innerHTML = html;
        }
        
        function setExample(type) {
            const today = new Date();
            const tomorrow = new Date(today);
            tomorrow.setDate(tomorrow.getDate() + 1);
            
            document.getElementById('startDate').value = tomorrow.toISOString().split('T')[0];
            
            switch(type) {
                case 'morning':
                    document.getElementById('startTime').value = '08:00';
                    break;
                case 'afternoon':
                    document.getElementById('startTime').value = '14:00';
                    break;
                case 'evening':
                    document.getElementById('startTime').value = '18:00';
                    break;
                case 'weekend':
                    // Set to next Saturday
                    const nextSaturday = new Date(today);
                    nextSaturday.setDate(today.getDate() + (6 - today.getDay()));
                    document.getElementById('startDate').value = nextSaturday.toISOString().split('T')[0];
                    document.getElementById('startTime').value = '18:00';
                    break;
                case 'holiday':
                    // Set to New Year 2025
                    document.getElementById('startDate').value = '2025-01-01';
                    document.getElementById('startTime').value = '18:00';
                    break;
            }
            
            calculateDynamicPrice();
        }
        
        function formatPrice(price) {
            return new Intl.NumberFormat('vi-VN').format(price);
        }
        
        function getTimeTypeName(type) {
            const names = {
                'morning': 'Buổi sáng',
                'afternoon': 'Buổi chiều', 
                'evening': 'Buổi tối',
                'night': 'Ban đêm'
            };
            return names[type] || type;
        }
        
        function getDayTypeName(type) {
            const names = {
                'weekday': 'Ngày thường',
                'weekend': 'Cuối tuần',
                'holiday': 'Ngày lễ'
            };
            return names[type] || type;
        }
    </script>
</body>
</html>
