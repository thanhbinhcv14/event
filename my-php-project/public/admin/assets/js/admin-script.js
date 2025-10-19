// Admin Panel Common JavaScript Functions

// Global variables
let sidebarCollapsed = false;
let currentPage = '';

// Initialize admin panel
document.addEventListener('DOMContentLoaded', function() {
    initializeAdminPanel();
    initializeTooltips();
    initializeModals();
    initializeNotifications();
    initializeResponsiveMenu();
    
    // Initialize DataTables after a short delay to ensure all scripts are loaded
    setTimeout(function() {
        initializeDataTables();
    }, 100);
});

// Function to safely initialize DataTables when needed
function safeInitializeDataTables() {
    if (typeof $ !== 'undefined' && typeof $.fn.DataTable !== 'undefined') {
        initializeDataTables();
        return true;
    }
    return false;
}

// Retry DataTables initialization if it fails
function retryDataTablesInit(maxRetries = 5, delay = 200) {
    let retries = 0;
    
    function attemptInit() {
        if (safeInitializeDataTables()) {
            console.log('DataTables initialized successfully');
            return;
        }
        
        retries++;
        if (retries < maxRetries) {
            console.log(`Retrying DataTables initialization (${retries}/${maxRetries})`);
            setTimeout(attemptInit, delay * retries);
        } else {
            console.warn('Failed to initialize DataTables after maximum retries');
        }
    }
    
    attemptInit();
}

// Initialize admin panel components
function initializeAdminPanel() {
    // Sidebar toggle
    const toggleBtn = document.querySelector('.toggle-btn');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (toggleBtn && sidebar && mainContent) {
        toggleBtn.addEventListener('click', function() {
            sidebarCollapsed = !sidebarCollapsed;
            sidebar.classList.toggle('collapsed', sidebarCollapsed);
            mainContent.classList.toggle('expanded', sidebarCollapsed);
            
            // Add animation class to button
            toggleBtn.classList.toggle('collapsed', sidebarCollapsed);
            
            // Change icon
            const icon = toggleBtn.querySelector('i');
            if (sidebarCollapsed) {
                icon.className = 'fas fa-chevron-right';
                toggleBtn.title = 'Mở rộng menu';
            } else {
                icon.className = 'fas fa-chevron-left';
                toggleBtn.title = 'Thu gọn menu';
            }
            
            // Save state to localStorage
            localStorage.setItem('sidebarCollapsed', sidebarCollapsed);
        });
        
        // Restore sidebar state
        const savedState = localStorage.getItem('sidebarCollapsed');
        if (savedState === 'true') {
            sidebarCollapsed = true;
            sidebar.classList.add('collapsed');
            mainContent.classList.add('expanded');
            toggleBtn.classList.add('collapsed');
            
            // Update icon
            const icon = toggleBtn.querySelector('i');
            icon.className = 'fas fa-chevron-right';
            toggleBtn.title = 'Mở rộng menu';
        }
    }
    
    // Mobile sidebar toggle
    const mobileToggleBtn = document.querySelector('.mobile-toggle-btn');
    if (mobileToggleBtn && sidebar) {
        mobileToggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
    }
    
    // Close mobile sidebar when clicking outside
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768) {
            if (sidebar && mobileToggleBtn && 
                !sidebar.contains(e.target) && !mobileToggleBtn.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        }
    });
}

// Initialize responsive menu
function initializeResponsiveMenu() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    
    if (!sidebar || !mainContent) return;
    
    function handleResize() {
        const width = window.innerWidth;
        
        if (width <= 1200 && width > 768) {
            // Tablet view - collapse sidebar
            sidebar.classList.add('collapsed');
            mainContent.classList.add('expanded');
            sidebarCollapsed = true;
        } else if (width <= 768) {
            // Mobile view - hide sidebar
            sidebar.classList.remove('collapsed', 'show');
            mainContent.classList.remove('expanded');
            sidebarCollapsed = false;
        } else {
            // Desktop view - restore saved state
            const savedState = localStorage.getItem('sidebarCollapsed');
            if (savedState === 'true') {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('expanded');
                sidebarCollapsed = true;
            } else {
                sidebar.classList.remove('collapsed');
                mainContent.classList.remove('expanded');
                sidebarCollapsed = false;
            }
        }
    }
    
    // Initial call
    handleResize();
    
    // Listen for resize events
    window.addEventListener('resize', handleResize);
}

// Initialize DataTables with common settings
function initializeDataTables() {
    // Wait for jQuery and DataTables to be loaded
    if (typeof $ !== 'undefined' && typeof $.fn.DataTable !== 'undefined') {
        try {
            // Common DataTables configuration
            $.extend($.fn.dataTable.defaults, {
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
                },
                responsive: true,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Tất cả"]],
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                     '<"row"<"col-sm-12"tr>>' +
                     '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                processing: true,
                serverSide: false,
                autoWidth: false,
                scrollX: true,
                order: [[0, 'desc']],
                columnDefs: [
                    {
                        targets: '_all',
                        className: 'text-center'
                    },
                    {
                        targets: 'no-sort',
                        orderable: false
                    }
                ],
                error: function(xhr, error, thrown) {
                    console.error('DataTables error:', error, thrown);
                }
            });
        } catch (error) {
            console.error('Error initializing DataTables:', error);
        }
    } else {
        console.warn('DataTables not available yet');
    }
}

// Initialize Bootstrap tooltips
function initializeTooltips() {
    if (typeof bootstrap !== 'undefined') {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
}

// Initialize modals
function initializeModals() {
    // Auto-focus first input in modals
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('shown.bs.modal', function() {
            const firstInput = modal.querySelector('input, select, textarea');
            if (firstInput) {
                firstInput.focus();
            }
        });
    });
}

// Initialize notifications
function initializeNotifications() {
    // Create notification container if it doesn't exist
    if (!document.querySelector('.notification-container')) {
        const container = document.createElement('div');
        container.className = 'notification-container';
        container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
        `;
        document.body.appendChild(container);
    }
}

// Show notification
function showNotification(message, type = 'info', duration = 5000) {
    const container = document.querySelector('.notification-container');
    if (!container) return;
    
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show notification-item`;
    notification.style.cssText = `
        margin-bottom: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border: none;
        border-radius: 8px;
    `;
    
    notification.innerHTML = `
        <i class="fas fa-${getNotificationIcon(type)} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    container.appendChild(notification);
    
    // Auto remove after duration
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, duration);
}

// Get notification icon based on type
function getNotificationIcon(type) {
    const icons = {
        'success': 'check-circle',
        'danger': 'exclamation-circle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    };
    return icons[type] || 'info-circle';
}

// Show loading spinner
function showLoading(element) {
    if (typeof element === 'string') {
        element = document.querySelector(element);
    }
    
    if (element) {
        element.innerHTML = `
            <div class="loading-spinner">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Đang tải...</span>
                </div>
                <p class="mt-2">Đang tải dữ liệu...</p>
            </div>
        `;
    }
}

// Hide loading spinner
function hideLoading(element) {
    if (typeof element === 'string') {
        element = document.querySelector(element);
    }
    
    if (element) {
        const spinner = element.querySelector('.loading-spinner');
        if (spinner) {
            spinner.remove();
        }
    }
}

// Show error message
function showError(message, element = '.error-message') {
    const errorElement = document.querySelector(element);
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.style.display = 'block';
        errorElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    showNotification(message, 'danger');
}

// Show success message
function showSuccess(message, element = '.success-message') {
    const successElement = document.querySelector(element);
    if (successElement) {
        successElement.textContent = message;
        successElement.style.display = 'block';
        setTimeout(() => {
            successElement.style.display = 'none';
        }, 5000);
    }
    showNotification(message, 'success');
}

// Show warning message
function showWarning(message, element = '.warning-message') {
    const warningElement = document.querySelector(element);
    if (warningElement) {
        warningElement.textContent = message;
        warningElement.style.display = 'block';
        setTimeout(() => {
            warningElement.style.display = 'none';
        }, 5000);
    }
    showNotification(message, 'warning');
}

// Show info message
function showInfo(message, element = '.info-message') {
    const infoElement = document.querySelector(element);
    if (infoElement) {
        infoElement.textContent = message;
        infoElement.style.display = 'block';
        setTimeout(() => {
            infoElement.style.display = 'none';
        }, 5000);
    }
    showNotification(message, 'info');
}

// Hide all messages
function hideMessages() {
    const messages = document.querySelectorAll('.error-message, .success-message, .warning-message, .info-message');
    messages.forEach(msg => {
        msg.style.display = 'none';
    });
}

// AJAX helper function
function makeAjaxRequest(url, data = {}, method = 'GET', options = {}) {
    const defaultOptions = {
        method: method,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    };
    
    // Handle FormData
    if (data instanceof FormData) {
        // Don't set Content-Type for FormData, let browser set it with boundary
        if (method === 'POST' || method === 'PUT') {
            defaultOptions.body = data;
        }
    } else if (method === 'POST' || method === 'PUT') {
        defaultOptions.headers['Content-Type'] = 'application/json';
        defaultOptions.body = JSON.stringify(data);
    } else if (Object.keys(data).length > 0) {
        const params = new URLSearchParams(data);
        url += (url.includes('?') ? '&' : '?') + params.toString();
    }
    
    const finalOptions = { ...defaultOptions, ...options };
    
    return fetch(url, finalOptions)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .catch(error => {
            console.error('AJAX Error:', error);
            showError('Có lỗi xảy ra khi tải dữ liệu: ' + error.message);
            throw error;
        });
}

// Form validation helper
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

// Clear form
function clearForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.reset();
        const invalidFields = form.querySelectorAll('.is-invalid');
        invalidFields.forEach(field => {
            field.classList.remove('is-invalid');
        });
    }
}

// Format date for display
function formatDate(dateString, format = 'dd/mm/yyyy') {
    if (!dateString) return '';
    
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return dateString;
    
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    
    switch (format) {
        case 'dd/mm/yyyy':
            return `${day}/${month}/${year}`;
        case 'dd/mm/yyyy hh:mm':
            return `${day}/${month}/${year} ${hours}:${minutes}`;
        case 'yyyy-mm-dd':
            return `${year}-${month}-${day}`;
        default:
            return dateString;
    }
}

// Format currency
function formatCurrency(amount, currency = 'VND') {
    if (!amount) return '0 ' + currency;
    
    const formatter = new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: currency === 'VND' ? 'VND' : 'USD'
    });
    
    return formatter.format(amount);
}

// Debounce function
function debounce(func, wait, immediate) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            timeout = null;
            if (!immediate) func(...args);
        };
        const callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func(...args);
    };
}

// Throttle function
function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// Copy to clipboard
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            showSuccess('Đã sao chép vào clipboard');
        }).catch(() => {
            showError('Không thể sao chép vào clipboard');
        });
    } else {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        try {
            document.execCommand('copy');
            showSuccess('Đã sao chép vào clipboard');
        } catch (err) {
            showError('Không thể sao chép vào clipboard');
        }
        document.body.removeChild(textArea);
    }
}

// Export data to CSV
function exportToCSV(data, filename = 'export.csv') {
    if (!data || data.length === 0) {
        showWarning('Không có dữ liệu để xuất');
        return;
    }
    
    const headers = Object.keys(data[0]);
    const csvContent = [
        headers.join(','),
        ...data.map(row => headers.map(header => `"${row[header] || ''}"`).join(','))
    ].join('\n');
    
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', filename);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Print element
function printElement(elementId) {
    const element = document.getElementById(elementId);
    if (!element) {
        showError('Không tìm thấy phần tử để in');
        return;
    }
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head>
                <title>In tài liệu</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; }
                    @media print {
                        body { margin: 0; }
                        .no-print { display: none; }
                    }
                </style>
            </head>
            <body>
                ${element.innerHTML}
            </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

// Confirm dialog
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Sweet Alert confirm (if SweetAlert2 is loaded)
function sweetConfirm(title, text, callback) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Xác nhận',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                callback();
            }
        });
    } else {
        confirmAction(text, callback);
    }
}

// Auto refresh data
function autoRefresh(interval = 30000) {
    setInterval(() => {
        if (typeof refreshData === 'function') {
            refreshData();
        }
    }, interval);
}

// Confirm logout function
function confirmLogout() {
    return confirm('Bạn có chắc chắn muốn đăng xuất?');
}

// Handle window resize
window.addEventListener('resize', debounce(() => {
    // Handle responsive adjustments
    const sidebar = document.querySelector('.sidebar');
    if (window.innerWidth <= 768 && sidebar) {
        sidebar.classList.remove('show');
    }
}, 250));

// Handle page visibility change
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        // Page is hidden, pause auto-refresh
        console.log('Page hidden, pausing auto-refresh');
    } else {
        // Page is visible, resume auto-refresh
        console.log('Page visible, resuming auto-refresh');
    }
});

// Global error handler
window.addEventListener('error', (event) => {
    // Only log and show critical errors, filter out common non-critical errors
    if (event.error && event.error.message) {
        const errorMessage = event.error.message.toLowerCase();
        const isNonCriticalError = errorMessage.includes('datatables') || 
                                  errorMessage.includes('jquery') ||
                                  errorMessage.includes('cannot read properties of undefined') ||
                                  errorMessage.includes('script error') ||
                                  errorMessage.includes('resizeobserver') ||
                                  errorMessage.includes('non-passive event listener') ||
                                  errorMessage.includes('loading chunk') ||
                                  errorMessage.includes('loading css chunk');
        
        if (!isNonCriticalError) {
            console.error('Critical error:', event.error);
            showError('Có lỗi xảy ra trong ứng dụng');
        } else {
            // Only log non-critical errors in development mode
            if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                console.warn('Non-critical error (filtered):', event.error);
            }
        }
    } else {
        // Log errors without message only in development
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            console.warn('Error without message:', event);
        }
    }
});

// Unhandled promise rejection handler
window.addEventListener('unhandledrejection', (event) => {
    // Only log and show critical promise rejections
    if (event.reason && event.reason.message) {
        const errorMessage = event.reason.message.toLowerCase();
        const isNonCriticalError = errorMessage.includes('datatables') || 
                                  errorMessage.includes('jquery') ||
                                  errorMessage.includes('cannot read properties of undefined') ||
                                  errorMessage.includes('script error') ||
                                  errorMessage.includes('loading chunk') ||
                                  errorMessage.includes('network error') ||
                                  errorMessage.includes('aborted');
        
        if (!isNonCriticalError) {
            console.error('Critical promise rejection:', event.reason);
            showError('Có lỗi xảy ra khi xử lý dữ liệu');
        } else {
            // Only log non-critical rejections in development mode
            if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                console.warn('Non-critical promise rejection (filtered):', event.reason);
            }
        }
    } else {
        // Log rejections without message only in development
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            console.warn('Promise rejection without message:', event.reason);
        }
    }
});

// Debug function for controlled error logging
function debugError(error, context = '') {
    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
        console.group(`🐛 Debug Error${context ? ` - ${context}` : ''}`);
        console.error('Error:', error);
        console.error('Stack:', error?.stack);
        console.error('Context:', context);
        console.groupEnd();
    }
}

// Enhanced DataTables initialization with better error handling
function initializeDataTablesWithValidation(config) {
    try {
        // Validate config
        if (!config || !config.ajax || !config.ajax.url) {
            throw new Error('Invalid DataTables configuration: missing ajax.url');
        }

        // Add default dataSrc if not provided
        if (!config.ajax.dataSrc) {
            config.ajax.dataSrc = function(json) {
                // Default dataSrc function
                if (Array.isArray(json)) {
                    return json;
                } else if (json && Array.isArray(json.data)) {
                    return json.data;
                } else if (json && json.success && Array.isArray(json.data)) {
                    return json.data;
                } else {
                    console.error('DataTables: Invalid data format received:', json);
                    return [];
                }
            };
        }

        // Add error handling if not provided
        if (!config.ajax.error) {
            config.ajax.error = function(xhr, error, thrown) {
                console.error('DataTables AJAX Error:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    error: error,
                    thrown: thrown,
                    responseText: xhr.responseText
                });
                AdminPanel.showError('Không thể tải dữ liệu');
            };
        }

        return $(config.table).DataTable(config);
    } catch (error) {
        console.error('DataTables initialization error:', error);
        AdminPanel.showError('Lỗi khởi tạo bảng dữ liệu');
        return null;
    }
}

// Export functions for global use
window.AdminPanel = {
    showNotification,
    showError,
    debugError,
    showSuccess,
    showWarning,
    showInfo,
    hideMessages,
    showLoading,
    initializeDataTablesWithValidation,
    hideLoading,
    makeAjaxRequest,
    validateForm,
    clearForm,
    formatDate,
    formatCurrency,
    debounce,
    throttle,
    copyToClipboard,
    exportToCSV,
    printElement,
    confirmAction,
    sweetConfirm,
    autoRefresh,
    confirmLogout
};
