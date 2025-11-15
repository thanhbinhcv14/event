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
        // Check if DataTables is loaded and retry if needed
        if (typeof $.fn.DataTable === 'undefined') {
            console.warn('DataTables not loaded properly, will retry...');
            // Retry DataTables initialization
            if (typeof retryDataTablesInit === 'function') {
                retryDataTablesInit();
            }
        } else {
            console.log('DataTables loaded successfully');
        }
    </script>
    
    <script>
        // Hide page loading overlay when page is fully loaded
        window.addEventListener('load', function() {
            const pageLoading = document.getElementById('pageLoading');
            if (pageLoading) {
                pageLoading.style.display = 'none';
            }
        });
        
        // Initialize Socket.IO for real-time notifications
        // Auto-detect Socket.IO server URL
        const getSocketServerURL = function() {
            const protocol = window.location.protocol;
            if (window.location.hostname.includes('sukien.info.vn')) {
                return protocol + '//ws.sukien.info.vn';  // VPS WebSocket server
            }
            return 'http://localhost:3000';  // Localhost development
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
        
        // Global AJAX error handler
        $(document).ajaxError(function(event, xhr, settings, thrownError) {
            console.error('AJAX Error:', thrownError);
            if (xhr.status === 401) {
                AdminPanel.showError('Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.');
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 2000);
            } else if (xhr.status === 403) {
                AdminPanel.showError('Bạn không có quyền thực hiện hành động này.');
            } else if (xhr.status === 500) {
                AdminPanel.showError('Lỗi máy chủ. Vui lòng thử lại sau.');
            } else {
                AdminPanel.showError('Có lỗi xảy ra khi tải dữ liệu.');
            }
        });
        
        // Auto-hide messages after 5 seconds
        setTimeout(function() {
            $('.success-message, .warning-message, .info-message').fadeOut();
        }, 5000);
        
        // Confirm before leaving page if form has unsaved changes
        let formChanged = false;
        $('form input, form select, form textarea').on('change', function() {
            formChanged = true;
        });
        
        $('form').on('submit', function() {
            formChanged = false;
        });
        
        // Disabled beforeunload warning to prevent unwanted popups
        // window.addEventListener('beforeunload', function(e) {
        //     if (formChanged) {
        //         e.preventDefault();
        //         e.returnValue = 'Bạn có chắc muốn rời khỏi trang? Các thay đổi chưa được lưu sẽ bị mất.';
        //     }
        // });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl + S to save form
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                const saveBtn = document.querySelector('button[type="submit"], .btn-save');
                if (saveBtn) {
                    saveBtn.click();
                }
            }
            
            // Ctrl + N to add new item
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                const addBtn = document.querySelector('.btn-add, .btn-new');
                if (addBtn) {
                    addBtn.click();
                }
            }
            
            // Escape to close modal
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
        
        // Add loading state to buttons
        $('button[type="submit"]').on('click', function() {
            const btn = $(this);
            const originalText = btn.html();
            btn.html('<i class="fas fa-spinner fa-spin me-2"></i>Đang xử lý...');
            btn.prop('disabled', true);
            
            // Re-enable button after 10 seconds as fallback
            setTimeout(function() {
                btn.html(originalText);
                btn.prop('disabled', false);
            }, 10000);
        });
        
        // Form validation enhancement
        $('form').on('submit', function(e) {
            const form = $(this);
            let isValid = true;
            
            // Clear previous validation
            form.find('.is-invalid').removeClass('is-invalid');
            form.find('.invalid-feedback').remove();
            
            // Validate required fields
            form.find('[required]').each(function() {
                const field = $(this);
                if (!field.val().trim()) {
                    field.addClass('is-invalid');
                    field.after('<div class="invalid-feedback">Trường này là bắt buộc.</div>');
                    isValid = false;
                }
            });
            
            // Validate email fields
            form.find('input[type="email"]').each(function() {
                const field = $(this);
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (field.val() && !emailRegex.test(field.val())) {
                    field.addClass('is-invalid');
                    field.after('<div class="invalid-feedback">Email không hợp lệ.</div>');
                    isValid = false;
                }
            });
            
            // Validate phone fields
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
        
        // Auto-save draft functionality
        let autoSaveTimeout;
        $('form textarea, form input[type="text"]').on('input', function() {
            clearTimeout(autoSaveTimeout);
            autoSaveTimeout = setTimeout(function() {
                // Auto-save logic can be implemented here
                console.log('Auto-saving draft...');
            }, 30000); // Auto-save every 30 seconds
        });
        
        // Print functionality
        window.printPage = function() {
            window.print();
        };
        
        // Export functionality
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
        
        // Refresh data functionality
        window.refreshData = function() {
            if (typeof loadData === 'function') {
                loadData();
            } else if ($.fn.DataTable) {
                $('.table').DataTable().ajax.reload();
            }
        };
        
        // Initialize tooltips on dynamic content
        $(document).on('mouseenter', '[data-bs-toggle="tooltip"]', function() {
            const tooltip = new bootstrap.Tooltip(this);
        });
        
        // Performance monitoring
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
