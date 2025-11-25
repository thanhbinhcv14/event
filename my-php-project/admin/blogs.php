<?php
// Bao gồm header admin
include 'includes/admin-header.php';

// Kiểm tra quyền - role 1 (Quản trị viên) và role 3 (Quản lý sự kiện) được quản lý blog
if (!in_array($user['ID_Role'], [1, 3])) {
    echo '<script>alert("Bạn không có quyền truy cập trang này!"); window.location.href = "index.php";</script>';
    exit;
}
?>
    
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-blog"></i>
                Quản lý bài viết
            </h1>
            <p class="page-subtitle">Quản lý các bài viết blog trong hệ thống</p>
        </div>
            
        <!-- Error/Success Messages -->
        <div class="error-message" id="errorMessage"></div>
        <div class="success-message" id="successMessage"></div>
        
        <!-- Statistics Cards -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stat-number" id="totalPosts">0</div>
                <div class="stat-label">Tổng bài viết</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon approved">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-number" id="publishedPosts">0</div>
                <div class="stat-label">Đã xuất bản</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number" id="draftPosts">0</div>
                <div class="stat-label">Bản nháp</div>
            </div>
        </div>
        
        <!-- Filter Section -->
        <div class="filter-section mb-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Tìm kiếm</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" class="form-control" id="searchInput" 
                               placeholder="Nhập tiêu đề, nội dung...">
                        <button class="btn btn-outline-secondary" type="button" onclick="clearSearch()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Loại sự kiện</label>
                    <select class="form-select" id="eventTypeFilter">
                        <option value="">Tất cả</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Trạng thái</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">Tất cả</option>
                        <option value="published">Đã xuất bản</option>
                        <option value="draft">Bản nháp</option>
                        <option value="archived">Đã lưu trữ</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" onclick="applyFilters()">
                            <i class="fas fa-filter"></i> Lọc
                        </button>
                        <button class="btn btn-outline-secondary" onclick="clearFilters()">
                            <i class="fas fa-times"></i> Xóa
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table Container -->
        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="mb-0">
                    <i class="fas fa-list"></i>
                    Danh sách bài viết
                </h3>
                <div class="action-buttons">
                    <button class="btn btn-success" onclick="showAddPostModal()">
                        <i class="fas fa-plus"></i> Thêm bài viết
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover" id="blogsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tiêu đề</th>
                            <th>Loại sự kiện</th>
                            <th>Tác giả</th>
                            <th>Trạng thái</th>
                            <th>Lượt xem</th>
                            <th>Bình luận</th>
                            <th>Ngày tạo</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    
    <!-- Add/Edit Post Modal -->
    <div class="modal fade" id="postModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="postModalTitle">
                        <i class="fas fa-plus"></i> Thêm bài viết
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="postForm" enctype="multipart/form-data">
                        <input type="hidden" id="postId" name="id">
                        
                        <div class="row">
                            <!-- Cột trái: Form fields -->
                            <div class="col-lg-8">
                                <div class="mb-3">
                                    <label for="postTitle" class="form-label">Tiêu đề <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="postTitle" name="title" required 
                                           placeholder="Nhập tiêu đề bài viết">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="postContent" class="form-label">Nội dung <span class="text-danger">*</span></label>
                                    <div id="postContentEditor" style="height: 400px;"></div>
                                    <textarea id="postContent" name="content" style="display: none;" required></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="postEventType" class="form-label">Danh mục <span class="text-danger">*</span></label>
                                            <select class="form-select" id="postEventType" name="event_type_id" required>
                                                <option value="">Chọn danh mục</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="postStatus" class="form-label">Trạng thái <span class="text-danger">*</span></label>
                                            <select class="form-select" id="postStatus" name="status" required>
                                                <option value="draft">Bản nháp</option>
                                                <option value="published">Đã xuất bản</option>
                                                <option value="archived">Đã lưu trữ</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="postExcerpt" class="form-label">Tóm tắt</label>
                                    <textarea class="form-control" id="postExcerpt" name="excerpt" rows="3" 
                                              placeholder="Nhập tóm tắt bài viết (tùy chọn)"></textarea>
                                </div>
                            </div>
                            
                            <!-- Cột phải: Upload ảnh -->
                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label for="postFeaturedImage" class="form-label">Hình ảnh thumbnail</label>
                                    <input type="file" class="form-control" id="postFeaturedImage" name="featured_image" 
                                           accept="image/*" onchange="previewImage(this)">
                                    <small class="form-text text-muted">Chọn ảnh đại diện cho bài viết</small>
                                    <div id="imagePreview" class="mt-3" style="display: none;">
                                        <img id="previewImg" src="" alt="Preview" class="img-fluid rounded" style="max-width: 100%; border: 2px solid #dee2e6; border-radius: 8px;">
                                        <button type="button" class="btn btn-sm btn-danger mt-2" onclick="removeImagePreview()">
                                            <i class="fas fa-trash"></i> Xóa ảnh
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" onclick="savePost()">
                        <i class="fas fa-save"></i> Lưu
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Post Modal -->
    <div class="modal fade" id="viewPostModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-eye"></i>
                        Chi tiết bài viết
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewPostModalBody">
                    <!-- Content will be loaded via AJAX -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Manage Comments Modal -->
    <div class="modal fade" id="commentsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-comments"></i>
                        Quản lý bình luận
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3 d-flex justify-content-between align-items-center">
                        <h6 id="commentsPostTitle" class="text-muted mb-0"></h6>
                        <?php if (in_array($user['ID_Role'], [1, 3])): ?>
                        <a href="#" id="goToBlogLink" class="btn btn-sm btn-primary" target="_blank">
                            <i class="fas fa-external-link-alt"></i> Trả lời trên trang blog
                        </a>
                        <?php endif; ?>
                    </div>
                    <div id="commentsList" class="comments-list">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Đang tải...</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Quill Editor CSS -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    
    <style>
        .ql-container {
            font-size: 16px;
        }
        .ql-editor {
            min-height: 350px;
        }
        
        /* Style cho phần upload ảnh bên phải */
        #imagePreview {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 2px dashed #dee2e6;
        }
        
        #imagePreview img {
            max-width: 100%;
            max-height: 300px;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        #imagePreview.show {
            display: block !important;
        }
        
        /* Hiệu ứng phóng to hình ảnh khi hover - giống locations.php */
        .blog-image-hover {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: zoom-in;
            display: inline-block;
        }
        
        .blog-image-hover:hover {
            transform: scale(2.5);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            z-index: 1000;
            position: relative;
            border-radius: 8px;
        }
        
        /* Đảm bảo modal hiển thị trên sidebar */
        .modal {
            z-index: 10000 !important;
        }
        
        .modal-backdrop {
            z-index: 9999 !important;
        }
        
        .modal.show {
            z-index: 10000 !important;
        }
        
        /* Thu gọn và dịch modal sang phải để không bị che bởi sidebar */
        .modal.show .modal-dialog {
            z-index: 10001 !important;
            max-width: 900px !important;
            width: 90% !important;
            margin-left: 280px !important;
            margin-right: auto !important;
        }
        
        /* Modal xem chi tiết - không thu gọn */
        #viewPostModal.show .modal-dialog {
            max-width: 800px !important;
            margin-left: 280px !important;
        }
        
        .modal.show .modal-content {
            z-index: 10002 !important;
        }
        
        /* Thu gọn form - giảm padding */
        #postModal .modal-body {
            padding: 1.25rem !important;
        }
        
        #postModal .row {
            margin-left: -0.5rem;
            margin-right: -0.5rem;
        }
        
        #postModal .col-lg-8,
        #postModal .col-lg-4 {
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }
        
        #postModal .mb-3 {
            margin-bottom: 1rem !important;
        }
        
        #postModal #postContentEditor {
            height: 300px !important;
        }
        
        #postModal .form-label {
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        #postModal .form-control,
        #postModal .form-select {
            font-size: 0.9rem;
            padding: 0.5rem 0.75rem;
        }
        
        /* Đảm bảo sidebar không chồng lên modal */
        .sidebar {
            z-index: 1030 !important;
        }
        
        /* Responsive cho modal */
        @media (max-width: 1200px) {
            .modal.show .modal-dialog {
                max-width: 95% !important;
                margin-left: 280px !important;
            }
        }
        
        @media (max-width: 992px) {
            .modal.show .modal-dialog {
                margin-left: 0 !important;
                margin: 1rem auto !important;
                max-width: 95% !important;
            }
            
            .modal-xl .col-lg-4 {
                margin-top: 20px;
            }
        }
        
        /* Comments Modal Styles */
        .comments-list {
            max-height: 500px;
            overflow-y: auto;
        }
        
        .comments-list .list-group-item {
            border-left: 3px solid #dee2e6;
            transition: all 0.3s ease;
        }
        
        .comments-list .list-group-item:hover {
            border-left-color: #667eea;
            background-color: #f8f9fa;
        }
        
        #commentsModal .modal-dialog {
            max-width: 800px;
            margin-left: 280px;
        }
        
        @media (max-width: 992px) {
            #commentsModal .modal-dialog {
                margin-left: 0;
                margin: 1rem auto;
            }
        }
    </style>

    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <script>
        let postsTable;
        let currentFilters = {};
        let quillEditor;
        let eventTypes = [];

        document.addEventListener('DOMContentLoaded', function() {
            initializeQuillEditor();
            loadEventTypes();
            initializeDataTable();
            loadStatistics();
            setupEventListeners();
        });

        function initializeQuillEditor() {
            quillEditor = new Quill('#postContentEditor', {
                theme: 'snow',
                modules: {
                    toolbar: [
                        [{ 'header': [1, 2, 3, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        [{ 'indent': '-1'}, { 'indent': '+1' }],
                        [{ 'align': [] }],
                        ['link', 'image'],
                        ['clean']
                    ]
                },
                placeholder: 'Nhập nội dung bài viết...'
            });
        }

        function loadEventTypes() {
            AdminPanel.makeAjaxRequest('../src/controllers/event-types.php', {
                action: 'get_all_public'
            })
            .then(response => {
                if (response.success && response.event_types) {
                    eventTypes = response.event_types;
                    const select = document.getElementById('postEventType');
                    const filterSelect = document.getElementById('eventTypeFilter');
                    
                    response.event_types.forEach(type => {
                        const option1 = new Option(type.TenLoai, type.ID_LoaiSK);
                        const option2 = new Option(type.TenLoai, type.ID_LoaiSK);
                        select.appendChild(option1);
                        filterSelect.appendChild(option2);
                    });
                }
            })
            .catch(error => {
                console.error('Error loading event types:', error);
            });
        }

        function initializeDataTable() {
            if (typeof $.fn.DataTable === 'undefined') {
                console.error('DataTables not available');
                AdminPanel.showError('DataTables không khả dụng');
                return;
            }

            try {
                postsTable = $('#blogsTable').DataTable({
                    processing: true,
                    serverSide: false,
                    ajax: {
                        url: '../src/controllers/blog.php',
                        type: 'GET',
                        data: function(d) {
                            return $.extend({
                                action: 'get_all'
                            }, currentFilters);
                        },
                        dataSrc: function(json) {
                            if (json.success && json.posts) {
                                return json.posts;
                            } else {
                                return [];
                            }
                        },
                        error: function(xhr, error, thrown) {
                            console.error('DataTable AJAX Error:', error);
                            AdminPanel.showError('Không thể tải dữ liệu bài viết');
                        }
                    },
                    columns: [
                        { 
                            data: 'id', 
                            className: 'text-center',
                            render: function(data) {
                                return `<strong>#${data}</strong>`;
                            }
                        },
                        { 
                            data: 'title',
                            render: function(data, type, row) {
                                const excerpt = row.excerpt ? `<small class="text-muted d-block">${row.excerpt.substring(0, 50)}...</small>` : '';
                                return `<strong>${data || 'N/A'}</strong>${excerpt}`;
                            }
                        },
                        { 
                            data: 'TenLoai',
                            render: function(data) {
                                return `<span class="badge bg-info">${data || 'N/A'}</span>`;
                            }
                        },
                        { 
                            data: 'AuthorEmail',
                            render: function(data) {
                                return data || 'Admin';
                            }
                        },
                        { 
                            data: 'status',
                            render: function(data) {
                                const badges = {
                                    'published': 'bg-success',
                                    'draft': 'bg-warning',
                                    'archived': 'bg-secondary'
                                };
                                const labels = {
                                    'published': 'Đã xuất bản',
                                    'draft': 'Bản nháp',
                                    'archived': 'Đã lưu trữ'
                                };
                                const badge = badges[data] || 'bg-secondary';
                                return `<span class="badge ${badge}">${labels[data] || data}</span>`;
                            }
                        },
                        { 
                            data: 'views',
                            className: 'text-center',
                            render: function(data) {
                                return data || 0;
                            }
                        },
                        { 
                            data: 'comment_count',
                            className: 'text-center',
                            render: function(data, type, row) {
                                const count = data || 0;
                                return `
                                    <span class="badge bg-primary position-relative">
                                        <i class="fas fa-comments"></i> ${count}
                                    </span>
                                `;
                            }
                        },
                        { 
                            data: 'created_at',
                            render: function(data) {
                                return data || 'N/A';
                            }
                        },
                        { 
                            data: null,
                            orderable: false,
                            className: 'text-center',
                            render: function(data, type, row) {
                                return `
                                    <div class="action-buttons d-flex gap-1 justify-content-center">
                                        <button class="btn btn-info btn-sm" onclick="viewPost(${row.id})" title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-primary btn-sm" onclick="manageComments(${row.id}, '${row.title}')" title="Quản lý bình luận">
                                            <i class="fas fa-comments"></i>
                                        </button>
                                        <button class="btn btn-warning btn-sm" onclick="editPost(${row.id})" title="Chỉnh sửa">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm" onclick="deletePost(${row.id})" title="Xóa">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                `;
                            }
                        }
                    ],
                    order: [[0, 'desc']],
                    language: {
                        processing: "Đang xử lý...",
                        search: "Tìm kiếm:",
                        lengthMenu: "Hiển thị _MENU_ bản ghi",
                        info: "Hiển thị _START_ đến _END_ trong tổng số _TOTAL_ bản ghi",
                        infoEmpty: "Không có dữ liệu",
                        infoFiltered: "(lọc từ _TOTAL_ bản ghi)",
                        zeroRecords: "Không tìm thấy kết quả",
                        paginate: {
                            first: "Đầu",
                            previous: "Trước",
                            next: "Sau",
                            last: "Cuối"
                        }
                    },
                    pageLength: 10,
                    responsive: true
                });
            } catch (error) {
                console.error('DataTable initialization error:', error);
                AdminPanel.showError('Lỗi khởi tạo bảng dữ liệu');
            }
        }

        function setupEventListeners() {
            $('#searchInput').on('keyup', function(e) {
                if (e.key === 'Enter') {
                    applyFilters();
                }
            });
        }

        function loadStatistics() {
            AdminPanel.makeAjaxRequest('../src/controllers/blog.php', {
                action: 'get_all'
            })
            .then(response => {
                if (response.success && response.posts) {
                    const total = response.posts.length;
                    const published = response.posts.filter(p => p.status === 'published').length;
                    const draft = response.posts.filter(p => p.status === 'draft').length;
                    
                    document.getElementById('totalPosts').textContent = total || 0;
                    document.getElementById('publishedPosts').textContent = published || 0;
                    document.getElementById('draftPosts').textContent = draft || 0;
                }
            })
            .catch(error => {
                console.error('Statistics load error:', error);
            });
        }

        function applyFilters() {
            currentFilters = {
                search: $('#searchInput').val(),
                event_type_id: $('#eventTypeFilter').val(),
                status: $('#statusFilter').val()
            };
            
            if (postsTable) {
                postsTable.ajax.reload();
            }
        }

        function clearFilters() {
            $('#searchInput').val('');
            $('#eventTypeFilter').val('');
            $('#statusFilter').val('');
            currentFilters = {};
            
            if (postsTable) {
                postsTable.ajax.reload();
            }
        }

        function clearSearch() {
            $('#searchInput').val('');
            applyFilters();
        }

        function showAddPostModal() {
            $('#postForm')[0].reset();
            $('#postId').val('');
            $('#postModalTitle').html('<i class="fas fa-plus"></i> Thêm bài viết');
            $('#imagePreview').hide();
            quillEditor.setContents([]);
            
            const modalElement = document.getElementById('postModal');
            if (!modalElement) {
                console.error('Modal element not found');
                return;
            }
            
            try {
                let modal = bootstrap.Modal.getInstance(modalElement);
                if (!modal) {
                    modal = new bootstrap.Modal(modalElement, {
                        backdrop: true,
                        keyboard: true,
                        focus: true
                    });
                }
                modal.show();
            } catch (error) {
                console.error('Error showing modal:', error);
                AdminPanel.showError('Lỗi khi mở modal');
            }
        }

        function viewPost(id) {
            AdminPanel.showLoading('#viewPostModalBody');
            
            const modalElement = document.getElementById('viewPostModal');
            if (!modalElement) {
                console.error('View modal element not found');
                AdminPanel.showError('Không tìm thấy modal xem chi tiết');
                return;
            }
            
            try {
                let modal = bootstrap.Modal.getInstance(modalElement);
                if (!modal) {
                    modal = new bootstrap.Modal(modalElement, {
                        backdrop: true,
                        keyboard: true,
                        focus: true
                    });
                }
                modal.show();
            } catch (error) {
                console.error('Error showing view modal:', error);
                AdminPanel.showError('Lỗi khi mở modal xem chi tiết');
                return;
            }

            AdminPanel.makeAjaxRequest('../src/controllers/blog.php', {
                action: 'get',
                id: id
            })
            .then(response => {
                if (response.success && response.post) {
                    const post = response.post;
                    const imageUrl = post.featured_image ? `../${post.featured_image}` : '../img/logo/default-blog.jpg';
                    const statusLabels = {
                        'published': 'Đã xuất bản',
                        'draft': 'Bản nháp',
                        'archived': 'Đã lưu trữ'
                    };
                    const statusBadges = {
                        'published': 'success',
                        'draft': 'warning',
                        'archived': 'secondary'
                    };
                    
                    $('#viewPostModalBody').html(`
                        <div class="row" style="font-size: 0.9rem;">
                            <div class="col-md-6">
                                <h6 style="font-size: 0.95rem;"><i class="fas fa-file-alt"></i> Thông tin cơ bản</h6>
                                <table class="table table-sm table-borderless" style="font-size: 0.9rem;">
                                    <tr><td style="width: 40%; padding: 0.3rem 0;"><strong>Tiêu đề:</strong></td><td style="padding: 0.3rem 0;">${post.title || 'N/A'}</td></tr>
                                    <tr><td style="padding: 0.3rem 0;"><strong>Loại sự kiện:</strong></td><td style="padding: 0.3rem 0;"><span class="badge bg-info">${post.TenLoai || 'N/A'}</span></td></tr>
                                    <tr><td style="padding: 0.3rem 0;"><strong>Tác giả:</strong></td><td style="padding: 0.3rem 0;">${post.AuthorEmail || 'Admin'}</td></tr>
                                    <tr><td style="padding: 0.3rem 0;"><strong>Trạng thái:</strong></td><td style="padding: 0.3rem 0;"><span class="badge bg-${statusBadges[post.status] || 'secondary'}">${statusLabels[post.status] || post.status || 'N/A'}</span></td></tr>
                                    <tr><td style="padding: 0.3rem 0;"><strong>Lượt xem:</strong></td><td style="padding: 0.3rem 0;">${post.views || 0}</td></tr>
                                    <tr><td style="padding: 0.3rem 0;"><strong>Ngày tạo:</strong></td><td style="padding: 0.3rem 0;">${post.created_at || 'N/A'}</td></tr>
                                    <tr><td style="padding: 0.3rem 0;"><strong>Cập nhật:</strong></td><td style="padding: 0.3rem 0;">${post.updated_at || 'N/A'}</td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6 style="font-size: 0.95rem;"><i class="fas fa-info-circle"></i> Thông tin khác</h6>
                                <table class="table table-sm table-borderless" style="font-size: 0.9rem;">
                                    <tr><td style="width: 40%; padding: 0.3rem 0;"><strong>Tóm tắt:</strong></td><td style="padding: 0.3rem 0;">${post.excerpt || 'Không có tóm tắt'}</td></tr>
                                    <tr><td style="padding: 0.3rem 0;"><strong>Hình ảnh:</strong></td><td style="padding: 0.3rem 0;">${post.featured_image ? `<img src="${imageUrl}" alt="${post.title}" class="img-fluid rounded blog-image-hover" style="max-width: 150px; max-height: 120px; object-fit: cover;" onerror="this.src='../img/logo/default-blog.jpg'">` : 'Không có hình ảnh'}</td></tr>
                                </table>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6 style="font-size: 0.95rem;"><i class="fas fa-align-left"></i> Nội dung</h6>
                                <div class="border rounded p-3" style="max-height: 400px; overflow-y: auto; background: #f8f9fa;">
                                    ${post.content || 'Không có nội dung'}
                                </div>
                            </div>
                        </div>
                    `);
                } else {
                    $('#viewPostModalBody').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            ${response.error || 'Không thể tải chi tiết bài viết'}
                        </div>
                    `);
                }
            })
            .catch(error => {
                $('#viewPostModalBody').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        Có lỗi xảy ra khi tải chi tiết bài viết
                    </div>
                `);
            });
        }

        function editPost(id) {
            AdminPanel.makeAjaxRequest('../src/controllers/blog.php', {
                action: 'get',
                id: id
            })
            .then(response => {
                if (response.success && response.post) {
                    const post = response.post;
                    
                    $('#postId').val(post.id);
                    $('#postTitle').val(post.title);
                    $('#postExcerpt').val(post.excerpt || '');
                    $('#postEventType').val(post.event_type_id);
                    $('#postStatus').val(post.status);
                    
                    // Set Quill editor content
                    if (post.content) {
                        quillEditor.root.innerHTML = post.content;
                    }
                    
                    // Reset file input completely (file input cannot show old filename)
                    const fileInput = document.getElementById('postFeaturedImage');
                    if (fileInput) {
                        fileInput.value = '';
                        // Create a new file input to completely reset it
                        const newFileInput = fileInput.cloneNode(true);
                        fileInput.parentNode.replaceChild(newFileInput, fileInput);
                        // Re-attach event listener
                        newFileInput.addEventListener('change', function() {
                            previewImage(this);
                        });
                    }
                    
                    // Show current image if exists (for reference only, not from file input)
                    if (post.featured_image) {
                        $('#previewImg').attr('src', '../' + post.featured_image);
                        $('#imagePreview').addClass('show').show();
                        // Store current image path for reference
                        $('#postId').data('current-image', post.featured_image);
                    } else {
                        $('#previewImg').attr('src', '');
                        $('#imagePreview').removeClass('show').hide();
                        $('#postId').data('current-image', '');
                    }
                    
                    $('#postModalTitle').html('<i class="fas fa-edit"></i> Chỉnh sửa bài viết');
                    
                    const modalElement = document.getElementById('postModal');
                    if (!modalElement) {
                        console.error('Modal element not found');
                        AdminPanel.showError('Không tìm thấy modal');
                        return;
                    }
                    
                    try {
                        let modal = bootstrap.Modal.getInstance(modalElement);
                        if (!modal) {
                            modal = new bootstrap.Modal(modalElement, {
                                backdrop: true,
                                keyboard: true,
                                focus: true
                            });
                        }
                        modal.show();
                    } catch (error) {
                        console.error('Error showing edit modal:', error);
                        AdminPanel.showError('Lỗi khi mở modal chỉnh sửa');
                    }
                } else {
                    AdminPanel.showError(response.error || 'Không tìm thấy bài viết');
                }
            })
            .catch(error => {
                AdminPanel.showError('Lỗi khi tải thông tin bài viết');
            });
        }

        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#previewImg').attr('src', e.target.result);
                    $('#imagePreview').addClass('show').show();
                    // Clear the stored current image when new file is selected
                    $('#postId').data('current-image', '');
                };
                reader.readAsDataURL(input.files[0]);
            } else {
                // If no file selected, check if there's a current image to show
                const currentImage = $('#postId').data('current-image');
                if (currentImage) {
                    $('#previewImg').attr('src', '../' + currentImage);
                    $('#imagePreview').addClass('show').show();
                } else {
                    $('#imagePreview').removeClass('show').hide();
                }
            }
        }
        
        function removeImagePreview() {
            const fileInput = document.getElementById('postFeaturedImage');
            if (fileInput) {
                fileInput.value = '';
                // Create a new file input to completely reset it
                const newFileInput = fileInput.cloneNode(true);
                fileInput.parentNode.replaceChild(newFileInput, fileInput);
                // Re-attach event listener
                newFileInput.addEventListener('change', function() {
                    previewImage(this);
                });
            }
            $('#previewImg').attr('src', '');
            $('#imagePreview').removeClass('show').hide();
            // Clear stored current image
            $('#postId').data('current-image', '');
        }

        async function savePost() {
            // Get content from Quill editor
            const content = quillEditor.root.innerHTML;
            $('#postContent').val(content);
            
            if (!AdminPanel.validateForm('postForm')) {
                return;
            }
            
            if (!content || content.trim() === '<p><br></p>') {
                AdminPanel.showError('Vui lòng nhập nội dung bài viết');
                return;
            }

            const formData = new FormData(document.getElementById('postForm'));
            const isEdit = document.getElementById('postId').value !== '';
            const action = isEdit ? 'update' : 'add';
            
            formData.append('action', action);
            formData.append('content', content);
            
            // Use fetch directly for FormData with CSRF token
            const csrfToken = window.CSRFHelper ? await window.CSRFHelper.getToken() : null;
            if (csrfToken) {
                formData.append('csrf_token', csrfToken);
            }
            
            fetch('../src/controllers/blog.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(response => {
                if (response.success) {
                    AdminPanel.showSuccess(isEdit ? 'Đã cập nhật bài viết thành công' : 'Đã thêm bài viết thành công');
                    
                    const modalElement = document.getElementById('postModal');
                    if (modalElement) {
                        try {
                            const modal = bootstrap.Modal.getInstance(modalElement);
                            if (modal) {
                                modal.hide();
                            }
                        } catch (error) {
                            console.error('Error hiding modal:', error);
                        }
                    }
                    
                    postsTable.ajax.reload();
                    loadStatistics();
                } else {
                    AdminPanel.showError(response.error || response.message || 'Có lỗi xảy ra khi lưu bài viết');
                }
            })
            .catch(error => {
                console.error('Error saving post:', error);
                AdminPanel.showError('Có lỗi xảy ra khi lưu bài viết');
            });
        }

        function manageComments(postId, postTitle) {
            $('#commentsPostTitle').text(`Bài viết: ${postTitle}`);
            $('#commentsPostTitle').data('post-id', postId);
            $('#goToBlogLink').attr('href', `../blog.php?post_id=${postId}`);
            $('#commentsList').html(`
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                </div>
            `);
            
            const modalElement = document.getElementById('commentsModal');
            if (modalElement) {
                try {
                    let modal = bootstrap.Modal.getInstance(modalElement);
                    if (!modal) {
                        modal = new bootstrap.Modal(modalElement, {
                            backdrop: true,
                            keyboard: true,
                            focus: true
                        });
                    }
                    modal.show();
                } catch (error) {
                    console.error('Error showing comments modal:', error);
                }
            }
            
            loadComments(postId);
        }
        
        function loadComments(postId) {
            AdminPanel.makeAjaxRequest('../src/controllers/blog.php', {
                action: 'get_all_comments',
                post_id: postId
            })
            .then(response => {
                if (response.success && response.comments) {
                    displayComments(response.comments);
                } else {
                    $('#commentsList').html(`
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            ${response.error || 'Không thể tải bình luận'}
                        </div>
                    `);
                }
            })
            .catch(error => {
                console.error('Error loading comments:', error);
                $('#commentsList').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        Có lỗi xảy ra khi tải bình luận
                    </div>
                `);
            });
        }
        
        function displayComments(comments) {
            const container = $('#commentsList');
            
            if (comments.length === 0) {
                container.html(`
                    <div class="text-center py-4">
                        <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Chưa có bình luận nào</p>
                    </div>
                `);
                return;
            }
            
            let html = '<div class="list-group">';
            
            comments.forEach(comment => {
                html += renderCommentForAdmin(comment);
            });
            
            html += '</div>';
            container.html(html);
        }
        
        function renderCommentForAdmin(comment, level = 0) {
            const statusBadges = {
                'approved': 'success',
                'pending': 'warning',
                'rejected': 'danger'
            };
            const statusLabels = {
                'approved': 'Đã duyệt',
                'pending': 'Chờ duyệt',
                'rejected': 'Đã từ chối'
            };
            const statusBadge = statusBadges[comment.status] || 'secondary';
            const statusLabel = statusLabels[comment.status] || comment.status;
            const indent = level * 30;
            const postId = comment.post_id || $('#commentsPostTitle').data('post-id');
            const userRole = <?php echo json_encode($user['ID_Role'] ?? null); ?>;
            const canReply = userRole === 1 || userRole === 3;
            
            let html = `
                <div class="list-group-item" style="margin-left: ${indent}px; ${level > 0 ? 'border-left: 3px solid #667eea; padding-left: 15px; margin-top: 10px; background-color: #f8f9fa;' : ''}">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-user-circle text-primary me-2"></i>
                                <strong>${comment.UserName || comment.UserEmail || 'Người dùng'}</strong>
                                <span class="badge bg-${statusBadge} ms-2">${statusLabel}</span>
                                ${comment.parent_comment_id ? `<small class="text-muted ms-2"><i class="fas fa-reply"></i> Trả lời ${comment.ParentUserName || comment.ParentUserEmail || 'Người dùng'}</small>` : ''}
                            </div>
                            <p class="mb-2">${comment.content}</p>
                            <small class="text-muted">
                                <i class="fas fa-clock"></i> ${comment.created_at}
                            </small>
                        </div>
                        <div class="d-flex gap-1">
                            ${comment.status === 'pending' ? `
                                <button class="btn btn-sm btn-success" onclick="approveComment(${comment.id}, ${postId})" title="Duyệt">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="rejectComment(${comment.id}, ${postId})" title="Từ chối">
                                    <i class="fas fa-times"></i>
                                </button>
                            ` : ''}
                            ${comment.status === 'rejected' ? `
                                <button class="btn btn-sm btn-success" onclick="approveComment(${comment.id}, ${postId})" title="Duyệt">
                                    <i class="fas fa-check"></i>
                                </button>
                            ` : ''}
                            ${canReply ? `
                            <a href="../blog.php?post_id=${postId}#comment-${comment.id}" class="btn btn-sm btn-info" title="Trả lời trên trang blog" target="_blank">
                                <i class="fas fa-reply"></i>
                            </a>
                            ` : ''}
                            <button class="btn btn-sm btn-danger" onclick="deleteComment(${comment.id}, ${postId})" title="Xóa">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            // Hiển thị replies nếu có
            if (comment.replies && comment.replies.length > 0) {
                comment.replies.forEach(reply => {
                    html += renderCommentForAdmin(reply, level + 1);
                });
            }
            
            return html;
        }
        
        function approveComment(commentId, postId) {
            const formData = new FormData();
            formData.append('action', 'approve_comment');
            formData.append('comment_id', commentId);
            
            AdminPanel.makeAjaxRequest('../src/controllers/blog.php', formData, 'POST')
            .then(response => {
                if (response.success) {
                    AdminPanel.showSuccess('Đã duyệt bình luận thành công');
                    loadComments(postId);
                } else {
                    AdminPanel.showError(response.error || 'Không thể duyệt bình luận');
                }
            })
            .catch(error => {
                console.error('Error approving comment:', error);
                AdminPanel.showError('Có lỗi xảy ra khi duyệt bình luận');
            });
        }
        
        function rejectComment(commentId, postId) {
            AdminPanel.sweetConfirm(
                'Xác nhận từ chối',
                'Bạn có chắc chắn muốn từ chối bình luận này?',
                () => {
                    const formData = new FormData();
                    formData.append('action', 'reject_comment');
                    formData.append('comment_id', commentId);
                    
                    AdminPanel.makeAjaxRequest('../src/controllers/blog.php', formData, 'POST')
                    .then(response => {
                        if (response.success) {
                            AdminPanel.showSuccess('Đã từ chối bình luận thành công');
                            loadComments(postId);
                        } else {
                            AdminPanel.showError(response.error || 'Không thể từ chối bình luận');
                        }
                    })
                    .catch(error => {
                        console.error('Error rejecting comment:', error);
                        AdminPanel.showError('Có lỗi xảy ra khi từ chối bình luận');
                    });
                }
            );
        }
        
        function deleteComment(commentId, postId) {
            AdminPanel.sweetConfirm(
                'Xác nhận xóa',
                'Bạn có chắc chắn muốn xóa bình luận này? Hành động này không thể hoàn tác.',
                () => {
                    const formData = new FormData();
                    formData.append('action', 'delete_comment');
                    formData.append('comment_id', commentId);
                    
                    AdminPanel.makeAjaxRequest('../src/controllers/blog.php', formData, 'POST')
                    .then(response => {
                        if (response.success) {
                            AdminPanel.showSuccess('Đã xóa bình luận thành công');
                            loadComments(postId);
                            postsTable.ajax.reload();
                        } else {
                            AdminPanel.showError(response.error || 'Không thể xóa bình luận');
                        }
                    })
                    .catch(error => {
                        console.error('Error deleting comment:', error);
                        AdminPanel.showError('Có lỗi xảy ra khi xóa bình luận');
                    });
                }
            );
        }
        
        function deletePost(id) {
            AdminPanel.sweetConfirm(
                'Xác nhận xóa',
                'Bạn có chắc chắn muốn xóa bài viết này? Hành động này không thể hoàn tác.',
                () => {
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('id', id);
                    
                    AdminPanel.makeAjaxRequest('../src/controllers/blog.php', formData, 'POST')
                    .then(response => {
                        if (response.success) {
                            AdminPanel.showSuccess('Đã xóa bài viết thành công');
                            postsTable.ajax.reload();
                            loadStatistics();
                        } else {
                            AdminPanel.showError(response.error || response.message || 'Có lỗi xảy ra khi xóa bài viết');
                        }
                    })
                    .catch(error => {
                        AdminPanel.showError('Có lỗi xảy ra khi xóa bài viết');
                    });
                }
            );
        }
    </script>

<?php include 'includes/admin-footer.php'; ?>

