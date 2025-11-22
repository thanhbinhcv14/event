        </div> <!-- End content-area -->
    </div> <!-- End main-content -->

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Socket.IO -->
    <script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
    
    <!-- Custom Admin JS -->
    <script src="assets/js/admin-script.js"></script>
    
    <!-- Ensure DataTables is available -->
    <script>
        // Kiểm tra DataTables đã được tải chưa và thử lại nếu cần
        if (typeof $.fn.DataTable === 'undefined') {
            console.warn('DataTables chưa được tải đúng cách, sẽ thử lại...');
            // Thử lại khởi tạo DataTables
            if (typeof retryDataTablesInit === 'function') {
                retryDataTablesInit();
            }
        } else {
            console.log('DataTables đã được tải thành công');
        }
    </script>
    
    <script>
        // Ẩn overlay loading khi trang đã tải xong
        window.addEventListener('load', function() {
            const pageLoading = document.getElementById('pageLoading');
            if (pageLoading) {
                pageLoading.style.display = 'none';
            }
        });
        
        // Khởi tạo Socket.IO cho thông báo real-time
        // Tự động phát hiện URL server Socket.IO
        const getSocketServerURL = function() {
            // Hybrid: WebSocket chạy trên VPS riêng (ws.sukien.info.vn)
            if (window.location.hostname.includes('sukien.info.vn')) {
                // ✅ QUAN TRỌNG: Dùng wss:// (secure WebSocket) cho production
                const protocol = window.location.protocol;
                // Nếu trang web dùng HTTPS, dùng wss:// cho WebSocket
                if (protocol === 'https:') {
                    return 'wss://ws.sukien.info.vn';  // Secure WebSocket
                } else {
                    return 'ws://ws.sukien.info.vn';   // Non-secure WebSocket (chỉ cho development)
                }
            }
            return 'http://localhost:3000';  // Phát triển localhost
        };
        
        const socketServerURL = getSocketServerURL();
        let socket;
        try {
            socket = io(socketServerURL, {
                path: '/socket.io'
            });
            
            socket.on('connect', function() {
                console.log('Connected to server:', socketServerURL);
            });
            
            socket.on('notification', function(data) {
                AdminPanel.showNotification(data.message, data.type || 'info');
            });
            
            socket.on('disconnect', function() {
                console.log('Disconnected from server');
            });
        } catch (error) {
            console.log('Socket.IO not available:', error);
        }
        
        // Xử lý lỗi AJAX toàn cục
        $(document).ajaxError(function(event, xhr, settings, thrownError) {
            console.error('AJAX Error:', thrownError);
            if (xhr.status === 401) {
                AdminPanel.showError('Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.');
                setTimeout(() => {
                    window.location.href = '../login.php';
                }, 2000);
            } else if (xhr.status === 403) {
                AdminPanel.showError('Bạn không có quyền thực hiện hành động này.');
            } else if (xhr.status === 500) {
                AdminPanel.showError('Lỗi máy chủ. Vui lòng thử lại sau.');
            } else {
                AdminPanel.showError('Có lỗi xảy ra khi tải dữ liệu.');
            }
        });
        
        // Tự động ẩn thông báo sau 5 giây
        setTimeout(function() {
            $('.success-message, .warning-message, .info-message').fadeOut();
        }, 5000);
        
        // Xác nhận trước khi rời trang nếu form có thay đổi chưa lưu
        let formChanged = false;
        $('form input, form select, form textarea').on('change', function() {
            formChanged = true;
        });
        
        $('form').on('submit', function() {
            formChanged = false;
        });
        
        // Tắt cảnh báo beforeunload để tránh popup không mong muốn
        // window.addEventListener('beforeunload', function(e) {
        //     if (formChanged) {
        //         e.preventDefault();
        //         e.returnValue = 'Bạn có chắc muốn rời khỏi trang? Các thay đổi chưa được lưu sẽ bị mất.';
        //     }
        // });
        
        // Phím tắt bàn phím
        document.addEventListener('keydown', function(e) {
            // Ctrl + S để lưu form
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                const saveBtn = document.querySelector('button[type="submit"], .btn-save');
                if (saveBtn) {
                    saveBtn.click();
                }
            }
            
            // Ctrl + N để thêm mục mới
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                const addBtn = document.querySelector('.btn-add, .btn-new');
                if (addBtn) {
                    addBtn.click();
                }
            }
            
            // Escape để đóng modal
            if (e.key === 'Escape') {
                const openModal = document.querySelector('.modal.show');
                if (openModal) {
                    const modal = bootstrap.Modal.getInstance(openModal);
                    if (modal) {
                        modal.hide();
                    }
                }
            }
        });
        
        // Thêm trạng thái loading cho nút
        $('button[type="submit"]').on('click', function() {
            const btn = $(this);
            const originalText = btn.html();
            btn.html('<i class="fas fa-spinner fa-spin me-2"></i>Đang xử lý...');
            btn.prop('disabled', true);
            
            // Kích hoạt lại nút sau 10 giây như dự phòng
            setTimeout(function() {
                btn.html(originalText);
                btn.prop('disabled', false);
            }, 10000);
        });
        
        // Cải thiện validation form
        $('form').on('submit', function(e) {
            const form = $(this);
            let isValid = true;
            
            // Xóa validation trước đó
            form.find('.is-invalid').removeClass('is-invalid');
            form.find('.invalid-feedback').remove();
            
            // Validate các trường bắt buộc
            form.find('[required]').each(function() {
                const field = $(this);
                if (!field.val().trim()) {
                    field.addClass('is-invalid');
                    field.after('<div class="invalid-feedback">Trường này là bắt buộc.</div>');
                    isValid = false;
                }
            });
            
            // Validate các trường email
            form.find('input[type="email"]').each(function() {
                const field = $(this);
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (field.val() && !emailRegex.test(field.val())) {
                    field.addClass('is-invalid');
                    field.after('<div class="invalid-feedback">Email không hợp lệ.</div>');
                    isValid = false;
                }
            });
            
            // Validate các trường số điện thoại
            form.find('input[type="tel"]').each(function() {
                const field = $(this);
                const phoneRegex = /^[0-9+\-\s()]+$/;
                if (field.val() && !phoneRegex.test(field.val())) {
                    field.addClass('is-invalid');
                    field.after('<div class="invalid-feedback">Số điện thoại không hợp lệ.</div>');
                    isValid = false;
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                AdminPanel.showError('Vui lòng kiểm tra lại thông tin đã nhập.');
                $('.is-invalid').first().focus();
            }
        });
        
        // Chức năng tự động lưu bản nháp
        let autoSaveTimeout;
        $('form textarea, form input[type="text"]').on('input', function() {
            clearTimeout(autoSaveTimeout);
            autoSaveTimeout = setTimeout(function() {
                // Logic tự động lưu có thể được triển khai ở đây
                console.log('Đang tự động lưu bản nháp...');
            }, 30000); // Tự động lưu mỗi 30 giây
        });
        
        // Chức năng in
        window.printPage = function() {
            window.print();
        };
        
        // Chức năng xuất dữ liệu
        window.exportData = function(format = 'csv') {
            const table = $('.table').DataTable();
            if (table) {
                if (format === 'csv') {
                    table.button('.buttons-csv').trigger();
                } else if (format === 'excel') {
                    table.button('.buttons-excel').trigger();
                } else if (format === 'pdf') {
                    table.button('.buttons-pdf').trigger();
                }
            }
        };
        
        // Chức năng làm mới dữ liệu
        window.refreshData = function() {
            if (typeof loadData === 'function') {
                loadData();
            } else if ($.fn.DataTable) {
                $('.table').DataTable().ajax.reload();
            }
        };
        
        // Khởi tạo tooltip trên nội dung động
        $(document).on('mouseenter', '[data-bs-toggle="tooltip"]', function() {
            const tooltip = new bootstrap.Tooltip(this);
        });
        
        // Giám sát hiệu suất
        if ('performance' in window) {
            window.addEventListener('load', function() {
                setTimeout(function() {
                    const perfData = performance.getEntriesByType('navigation')[0];
                    console.log('Page load time:', perfData.loadEventEnd - perfData.loadEventStart, 'ms');
                }, 0);
            });
        }
    </script>
</body>
</html>
