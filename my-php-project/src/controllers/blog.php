<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

$pdo = getDBConnection();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Kiểm tra quyền admin - role 1 (Quản trị viên) và role 3 (Quản lý sự kiện) được quản lý blog
function checkAdminAccess() {
    if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['ID_Role'], [1, 3])) {
        return false;
    }
    return true;
}

try {
    switch ($action) {
        case 'get_posts_by_type':
            getPostsByType($pdo);
            break;
        case 'get_post':
            getPost($pdo);
            break;
        case 'get_post_details':
            getPostDetails($pdo);
            break;
        case 'get_comments':
            getComments($pdo);
            break;
        case 'add_comment':
            addComment($pdo);
            break;
        case 'get_all_public':
            getAllPublicPosts($pdo);
            break;
        case 'get_all':
            if (!checkAdminAccess()) {
                echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập']);
                exit();
            }
            getAllPosts($pdo);
            break;
        case 'get':
            if (!checkAdminAccess()) {
                echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập']);
                exit();
            }
            getPostForAdmin($pdo);
            break;
        case 'add':
            if (!checkAdminAccess()) {
                echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập']);
                exit();
            }
            addPost($pdo);
            break;
        case 'update':
            if (!checkAdminAccess()) {
                echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập']);
                exit();
            }
            updatePost($pdo);
            break;
        case 'delete':
            if (!checkAdminAccess()) {
                echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập']);
                exit();
            }
            deletePost($pdo);
            break;
        case 'get_all_comments':
            if (!checkAdminAccess()) {
                echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập']);
                exit();
            }
            getAllComments($pdo);
            break;
        case 'approve_comment':
            if (!checkAdminAccess()) {
                echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập']);
                exit();
            }
            approveComment($pdo);
            break;
        case 'reject_comment':
            if (!checkAdminAccess()) {
                echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập']);
                exit();
            }
            rejectComment($pdo);
            break;
        case 'delete_comment':
            if (!checkAdminAccess()) {
                echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập']);
                exit();
            }
            deleteComment($pdo);
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Hành động không hợp lệ']);
            break;
    }
} catch (Exception $e) {
    error_log("Blog Controller Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}

function getPostsByType($pdo) {
    $typeId = $_GET['type_id'] ?? null;
    
    if (!$typeId) {
        echo json_encode(['success' => false, 'error' => 'Thiếu ID loại sự kiện']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                bp.*,
                ls.TenLoai,
                u.Email as AuthorEmail
            FROM blog_posts bp
            LEFT JOIN loaisukien ls ON bp.event_type_id = ls.ID_LoaiSK
            LEFT JOIN users u ON bp.author_id = u.ID_User
            WHERE bp.event_type_id = ? AND bp.status = 'published'
            ORDER BY bp.created_at DESC
        ");
        $stmt->execute([$typeId]);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Định dạng ngày tháng
        foreach ($posts as &$post) {
            $post['created_at'] = date('d/m/Y H:i', strtotime($post['created_at']));
            $post['updated_at'] = date('d/m/Y H:i', strtotime($post['updated_at']));
            $post['HinhAnhDaiDienURL'] = $post['featured_image'] ? $post['featured_image'] : 'img/logo/default-blog.jpg';
        }
        
        echo json_encode(['success' => true, 'posts' => $posts]);
    } catch (Exception $e) {
        error_log("Get Posts By Type Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi khi lấy danh sách bài viết']);
    }
}

function getAllPublicPosts($pdo) {
    try {
        $limit = $_GET['limit'] ?? null;
        
        $sql = "
            SELECT 
                bp.*,
                ls.TenLoai,
                COALESCE(nv.HoTen, kh.HoTen, u.Email, 'Admin') as TenTacGia
            FROM blog_posts bp
            LEFT JOIN loaisukien ls ON bp.event_type_id = ls.ID_LoaiSK
            LEFT JOIN users u ON bp.author_id = u.ID_User
            LEFT JOIN nhanvieninfo nv ON u.ID_User = nv.ID_User AND u.ID_Role IN (1,2,3,4)
            LEFT JOIN khachhanginfo kh ON u.ID_User = kh.ID_User AND u.ID_Role = 5
            WHERE bp.status = 'published'
            ORDER BY bp.created_at DESC
        ";
        
        if ($limit) {
            $sql .= " LIMIT " . intval($limit);
        }
        
        $stmt = $pdo->query($sql);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Định dạng ngày tháng và thêm URL hình ảnh
        foreach ($posts as &$post) {
            $post['NgayDang'] = date('d/m/Y', strtotime($post['created_at']));
            $post['HinhAnhDaiDienURL'] = $post['featured_image'] ? $post['featured_image'] : 'img/logo/default-blog.jpg';
            $post['NoiDungTomTat'] = $post['excerpt'] ?? '';
            $post['TieuDe'] = $post['title'] ?? '';
            $post['ID_BlogPost'] = $post['id'] ?? 0;
        }
        
        echo json_encode(['success' => true, 'posts' => $posts]);
    } catch (Exception $e) {
        error_log("Get All Public Posts Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi khi lấy danh sách bài viết']);
    }
}

function getPost($pdo) {
    $postId = $_GET['post_id'] ?? null;
    
    if (!$postId) {
        echo json_encode(['success' => false, 'error' => 'Thiếu ID bài viết']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                bp.*,
                ls.TenLoai,
                u.Email as AuthorEmail
            FROM blog_posts bp
            LEFT JOIN loaisukien ls ON bp.event_type_id = ls.ID_LoaiSK
            LEFT JOIN users u ON bp.author_id = u.ID_User
            WHERE bp.id = ? AND bp.status = 'published'
        ");
        $stmt->execute([$postId]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($post) {
            $post['created_at'] = date('d/m/Y H:i', strtotime($post['created_at']));
            $post['updated_at'] = date('d/m/Y H:i', strtotime($post['updated_at']));
            echo json_encode(['success' => true, 'post' => $post]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Không tìm thấy bài viết']);
        }
    } catch (Exception $e) {
        error_log("Get Post Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi khi lấy bài viết']);
    }
}

function getPostDetails($pdo) {
    $postId = $_GET['post_id'] ?? null;
    
    if (!$postId) {
        echo json_encode(['success' => false, 'error' => 'Thiếu ID bài viết']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                bp.*,
                ls.TenLoai,
                COALESCE(nv.HoTen, kh.HoTen, u.Email, 'Admin') as TenTacGia
            FROM blog_posts bp
            LEFT JOIN loaisukien ls ON bp.event_type_id = ls.ID_LoaiSK
            LEFT JOIN users u ON bp.author_id = u.ID_User
            LEFT JOIN nhanvieninfo nv ON u.ID_User = nv.ID_User AND u.ID_Role IN (1,2,3,4)
            LEFT JOIN khachhanginfo kh ON u.ID_User = kh.ID_User AND u.ID_Role = 5
            WHERE bp.id = ? AND bp.status = 'published'
        ");
        $stmt->execute([$postId]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($post) {
            // Cập nhật số lượt xem
            $updateStmt = $pdo->prepare("UPDATE blog_posts SET views = views + 1 WHERE id = ?");
            $updateStmt->execute([$postId]);
            
            $post['created_at'] = date('d/m/Y H:i', strtotime($post['created_at']));
            $post['updated_at'] = date('d/m/Y H:i', strtotime($post['updated_at']));
            $post['HinhAnhDaiDienURL'] = $post['featured_image'] ? $post['featured_image'] : 'img/logo/default-blog.jpg';
            echo json_encode(['success' => true, 'post' => $post]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Không tìm thấy bài viết']);
        }
    } catch (Exception $e) {
        error_log("Get Post Details Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi khi lấy bài viết']);
    }
}

function getComments($pdo) {
    $postId = $_GET['post_id'] ?? null;
    
    if (!$postId) {
        echo json_encode(['success' => false, 'error' => 'Thiếu ID bài viết']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                bc.*,
                u.Email as UserEmail,
                kh.HoTen as UserName,
                parent_u.Email as ParentUserEmail,
                parent_kh.HoTen as ParentUserName
            FROM blog_comments bc
            LEFT JOIN users u ON bc.user_id = u.ID_User
            LEFT JOIN khachhanginfo kh ON bc.user_id = kh.ID_User
            LEFT JOIN blog_comments parent ON bc.parent_comment_id = parent.id
            LEFT JOIN users parent_u ON parent.user_id = parent_u.ID_User
            LEFT JOIN khachhanginfo parent_kh ON parent.user_id = parent_kh.ID_User
            WHERE bc.post_id = ? AND bc.status = 'approved'
            ORDER BY bc.parent_comment_id IS NULL DESC, bc.created_at DESC
        ");
        $stmt->execute([$postId]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Tổ chức comments thành cấu trúc parent-child (hỗ trợ nested replies)
        $allComments = []; // Mảng tất cả comments để tìm parent
        $parentComments = [];
        
        foreach ($comments as $comment) {
            // Format ngày tháng an toàn
            if (isset($comment['created_at']) && $comment['created_at']) {
                try {
                    $comment['created_at'] = date('d/m/Y H:i', strtotime($comment['created_at']));
                } catch (Exception $e) {
                    // Giữ nguyên nếu format lỗi
                    error_log("Date format error: " . $e->getMessage());
                }
            }
            
            $comment['replies'] = [];
            $allComments[$comment['id']] = $comment;
            
            // Kiểm tra parent_comment_id: null, '', 0, '0' đều coi là parent comment
            $parentId = $comment['parent_comment_id'];
            $isParent = ($parentId === null || $parentId === '' || $parentId === 0 || $parentId === '0');
            
            if ($isParent) {
                $parentComments[$comment['id']] = &$allComments[$comment['id']];
            }
        }
        
        // Gán replies vào parent comments (hỗ trợ nested - reply của reply)
        foreach ($allComments as $commentId => &$comment) {
            $parentId = $comment['parent_comment_id'];
            $isParent = ($parentId === null || $parentId === '' || $parentId === 0 || $parentId === '0');
            
            if (!$isParent) {
                $parentId = (int)$parentId; // Chuyển sang int để so sánh
                if (isset($allComments[$parentId])) {
                    // Gán reply vào parent (có thể là parent comment hoặc reply comment khác)
                    $allComments[$parentId]['replies'][] = &$comment;
                } else {
                    error_log("Warning: Parent comment ID $parentId not found for reply ID " . $commentId);
                }
            }
        }
        unset($comment); // Hủy reference
        
        // Sắp xếp replies theo thời gian mới nhất (đệ quy cho nested replies)
        $sortRepliesRecursive = function(&$comment) use (&$sortRepliesRecursive) {
            if (isset($comment['replies']) && count($comment['replies']) > 1) {
                usort($comment['replies'], function($a, $b) {
                    $timeA = strtotime($a['created_at'] ?? '1970-01-01');
                    $timeB = strtotime($b['created_at'] ?? '1970-01-01');
                    return $timeB - $timeA; // Mới nhất lên trước
                });
                
                // Sắp xếp replies của replies (nested)
                foreach ($comment['replies'] as &$reply) {
                    $sortRepliesRecursive($reply);
                }
                unset($reply);
            }
        };
        
        foreach ($parentComments as &$parent) {
            $sortRepliesRecursive($parent);
        }
        unset($parent); // Hủy reference
        
        // Chuyển về array index
        $result = array_values($parentComments);
        
        echo json_encode(['success' => true, 'comments' => $result]);
    } catch (Exception $e) {
        error_log("Get Comments Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi khi lấy bình luận']);
    }
}

function addComment($pdo) {
    $postId = $_POST['post_id'] ?? null;
    $content = $_POST['content'] ?? null;
    $parentCommentId = $_POST['parent_comment_id'] ?? null;
    $userId = $_SESSION['user']['ID_User'] ?? $_SESSION['user']['id'] ?? null;
    
    if (!$postId || !$content) {
        echo json_encode(['success' => false, 'error' => 'Thiếu thông tin bình luận']);
        return;
    }
    
    if (!$userId) {
        echo json_encode(['success' => false, 'error' => 'Bạn cần đăng nhập để bình luận']);
        return;
    }
    
    try {
        // Nếu là reply, kiểm tra parent comment có tồn tại không
        if ($parentCommentId) {
            $checkStmt = $pdo->prepare("SELECT id FROM blog_comments WHERE id = ? AND post_id = ?");
            $checkStmt->execute([$parentCommentId, $postId]);
            if (!$checkStmt->fetch()) {
                echo json_encode(['success' => false, 'error' => 'Bình luận cha không tồn tại']);
                return;
            }
        }
        
        // Thêm bình luận (cột parent_comment_id đã có trong database)
        try {
            $stmt = $pdo->prepare("
                INSERT INTO blog_comments (post_id, user_id, parent_comment_id, content, status, created_at)
                VALUES (?, ?, ?, ?, 'approved', NOW())
            ");
            $stmt->execute([$postId, $userId, $parentCommentId, $content]);
            error_log("Add Comment: INSERT successful for post_id=$postId, user_id=$userId");
        } catch (PDOException $insertError) {
            // Nếu INSERT lỗi, throw lại để catch bên ngoài xử lý
            error_log("Add Comment: INSERT failed - " . $insertError->getMessage());
            throw $insertError;
        }
        
        $commentId = $pdo->lastInsertId();
        
        if (!$commentId) {
            error_log("Add Comment Error: Failed to get last insert ID");
            echo json_encode(['success' => false, 'error' => 'Lỗi khi lấy ID bình luận vừa tạo']);
            return;
        }
        
        // Lấy bình luận vừa tạo kèm thông tin người dùng (bọc trong try-catch riêng)
        $comment = null;
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    bc.*,
                    u.Email as UserEmail,
                    kh.HoTen as UserName,
                    parent_u.Email as ParentUserEmail,
                    parent_kh.HoTen as ParentUserName
                FROM blog_comments bc
                LEFT JOIN users u ON bc.user_id = u.ID_User
                LEFT JOIN khachhanginfo kh ON bc.user_id = kh.ID_User
                LEFT JOIN blog_comments parent ON bc.parent_comment_id = parent.id
                LEFT JOIN users parent_u ON parent.user_id = parent_u.ID_User
                LEFT JOIN khachhanginfo parent_kh ON parent.user_id = parent_kh.ID_User
                WHERE bc.id = ?
            ");
            $stmt->execute([$commentId]);
            $comment = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $selectError) {
            // Nếu SELECT lỗi, log nhưng vẫn trả về success vì INSERT đã thành công
            error_log("Add Comment - SELECT Error after INSERT: " . $selectError->getMessage());
            error_log("Comment ID: $commentId");
            // Trả về success với flag reload để frontend reload lại danh sách
            echo json_encode([
                'success' => true,
                'message' => 'Đã thêm bình luận thành công',
                'comment_id' => $commentId,
                'reload' => true
            ]);
            return;
        }
        
        if (!$comment) {
            error_log("Add Comment Error: Comment with ID $commentId not found after INSERT");
            // Vẫn trả về success vì INSERT đã thành công, frontend sẽ reload comments
            echo json_encode([
                'success' => true,
                'message' => 'Đã thêm bình luận thành công',
                'comment_id' => $commentId,
                'reload' => true
            ]);
            return;
        }
        
        // Format ngày tháng
        if (isset($comment['created_at'])) {
            try {
                $comment['created_at'] = date('d/m/Y H:i', strtotime($comment['created_at']));
            } catch (Exception $e) {
                error_log("Date format error: " . $e->getMessage());
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Đã thêm bình luận thành công',
            'comment' => $comment
        ]);
    } catch (PDOException $e) {
        $errorCode = $e->getCode();
        $errorMessage = $e->getMessage();
        
        error_log("Add Comment PDO Error: " . $errorMessage);
        error_log("SQL State: " . $errorCode);
        error_log("Error Info: " . print_r($e->errorInfo ?? [], true));
        
        $errorMsg = 'Lỗi khi thêm bình luận';
        
        // Kiểm tra lỗi cụ thể
        if ($errorCode == '42S22' || strpos($errorMessage, 'Unknown column') !== false) {
            // Lỗi cột không tồn tại
            if (strpos($errorMessage, 'parent_comment_id') !== false) {
                $errorMsg = 'Cột parent_comment_id chưa được thêm vào database. Vui lòng chạy ALTER TABLE để thêm cột này.';
            } else {
                $errorMsg = 'Lỗi database: Cột không tồn tại. Vui lòng kiểm tra lại cấu trúc database.';
            }
        } else if (strpos($errorMessage, 'SQLSTATE[23000]') !== false || strpos($errorMessage, 'Integrity constraint violation') !== false) {
            // Lỗi ràng buộc dữ liệu
            $errorMsg = 'Lỗi dữ liệu: Thông tin không hợp lệ. Vui lòng kiểm tra lại.';
        } else {
            // Lỗi khác - hiển thị thông báo chung
            $errorMsg = 'Lỗi khi thêm bình luận. Vui lòng thử lại sau.';
            // Log chi tiết lỗi để debug
            error_log("Unexpected PDO Error: " . $errorMessage);
        }
        
        echo json_encode(['success' => false, 'error' => $errorMsg]);
    } catch (Exception $e) {
        error_log("Add Comment Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi khi thêm bình luận: ' . $e->getMessage()]);
    }
}

// ========== CÁC HÀM DÀNH CHO ADMIN ==========

function getAllPosts($pdo) {
    try {
        $search = $_GET['search'] ?? '';
        $eventTypeId = $_GET['event_type_id'] ?? '';
        $status = $_GET['status'] ?? '';
        
        $sql = "
            SELECT 
                bp.*,
                ls.TenLoai,
                u.Email as AuthorEmail,
                COUNT(bc.id) as comment_count
            FROM blog_posts bp
            LEFT JOIN loaisukien ls ON bp.event_type_id = ls.ID_LoaiSK
            LEFT JOIN users u ON bp.author_id = u.ID_User
            LEFT JOIN blog_comments bc ON bp.id = bc.post_id
            WHERE 1=1
            GROUP BY bp.id
        ";
        
        $params = [];
        
        if ($search) {
            $sql .= " AND (bp.title LIKE ? OR bp.content LIKE ? OR bp.excerpt LIKE ?)";
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        if ($eventTypeId) {
            $sql .= " AND bp.event_type_id = ?";
            $params[] = $eventTypeId;
        }
        
        if ($status) {
            $sql .= " AND bp.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY bp.created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        if (!$stmt) {
            throw new Exception("Lỗi khi chuẩn bị query: " . implode(", ", $pdo->errorInfo()));
        }
        
        $stmt->execute($params);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Định dạng ngày tháng
        foreach ($posts as &$post) {
            if (isset($post['created_at']) && $post['created_at']) {
                try {
                    $post['created_at'] = date('d/m/Y H:i', strtotime($post['created_at']));
                } catch (Exception $e) {
                    // Giữ nguyên nếu format lỗi
                }
            }
            if (isset($post['updated_at']) && $post['updated_at']) {
                try {
                    $post['updated_at'] = date('d/m/Y H:i', strtotime($post['updated_at']));
                } catch (Exception $e) {
                    // Giữ nguyên nếu format lỗi
                }
            }
        }
        
        echo json_encode(['success' => true, 'posts' => $posts]);
    } catch (Exception $e) {
        error_log("Get All Posts Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi khi lấy danh sách bài viết']);
    }
}

function getPostForAdmin($pdo) {
    $postId = $_GET['id'] ?? $_POST['id'] ?? null;
    
    if (!$postId) {
        echo json_encode(['success' => false, 'error' => 'Thiếu ID bài viết']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                bp.*,
                ls.TenLoai,
                u.Email as AuthorEmail
            FROM blog_posts bp
            LEFT JOIN loaisukien ls ON bp.event_type_id = ls.ID_LoaiSK
            LEFT JOIN users u ON bp.author_id = u.ID_User
            WHERE bp.id = ?
        ");
        $stmt->execute([$postId]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($post) {
            $post['created_at'] = date('Y-m-d\TH:i', strtotime($post['created_at']));
            $post['updated_at'] = date('Y-m-d\TH:i', strtotime($post['updated_at']));
            echo json_encode(['success' => true, 'post' => $post]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Không tìm thấy bài viết']);
        }
    } catch (Exception $e) {
        error_log("Get Post For Admin Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi khi lấy bài viết']);
    }
}

function addPost($pdo) {
    $title = $_POST['title'] ?? null;
    $content = $_POST['content'] ?? null;
    $eventTypeId = $_POST['event_type_id'] ?? null;
    $excerpt = $_POST['excerpt'] ?? null;
    $status = $_POST['status'] ?? 'published';
    $authorId = $_SESSION['user']['ID_User'] ?? $_SESSION['user']['id'] ?? null;
    
    if (!$title || !$content || !$eventTypeId) {
        echo json_encode(['success' => false, 'error' => 'Thiếu thông tin bắt buộc']);
        return;
    }
    
    try {
        // Xử lý upload hình ảnh đại diện
        $featuredImage = null;
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../img/blog/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileExtension = pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION);
            $fileName = 'blog_' . time() . '_' . uniqid() . '.' . $fileExtension;
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $filePath)) {
                $featuredImage = 'img/blog/' . $fileName;
            }
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO blog_posts (event_type_id, title, content, excerpt, featured_image, author_id, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$eventTypeId, $title, $content, $excerpt, $featuredImage, $authorId, $status]);
        
        $postId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Đã thêm bài viết thành công',
            'post_id' => $postId
        ]);
    } catch (Exception $e) {
        error_log("Add Post Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi khi thêm bài viết: ' . $e->getMessage()]);
    }
}

function updatePost($pdo) {
    $postId = $_POST['id'] ?? null;
    $title = $_POST['title'] ?? null;
    $content = $_POST['content'] ?? null;
    $eventTypeId = $_POST['event_type_id'] ?? null;
    $excerpt = $_POST['excerpt'] ?? null;
    $status = $_POST['status'] ?? 'published';
    
    if (!$postId || !$title || !$content || !$eventTypeId) {
        echo json_encode(['success' => false, 'error' => 'Thiếu thông tin bắt buộc']);
        return;
    }
    
    try {
        // Lấy bài viết hiện tại để kiểm tra hình ảnh đã có
        $stmt = $pdo->prepare("SELECT featured_image FROM blog_posts WHERE id = ?");
        $stmt->execute([$postId]);
        $existingPost = $stmt->fetch(PDO::FETCH_ASSOC);
        $featuredImage = $existingPost['featured_image'] ?? null;
        
        // Xử lý upload hình ảnh đại diện nếu có file mới được upload
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            // Xóa hình ảnh cũ nếu tồn tại
            if ($featuredImage && file_exists(__DIR__ . '/../../' . $featuredImage)) {
                unlink(__DIR__ . '/../../' . $featuredImage);
            }
            
            $uploadDir = __DIR__ . '/../../img/blog/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileExtension = pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION);
            $fileName = 'blog_' . time() . '_' . uniqid() . '.' . $fileExtension;
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $filePath)) {
                $featuredImage = 'img/blog/' . $fileName;
            }
        }
        // Nếu không có file mới được upload, giữ nguyên hình ảnh hiện tại (không thay đổi $featuredImage)
        
        $stmt = $pdo->prepare("
            UPDATE blog_posts 
            SET event_type_id = ?, title = ?, content = ?, excerpt = ?, featured_image = ?, status = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$eventTypeId, $title, $content, $excerpt, $featuredImage, $status, $postId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Đã cập nhật bài viết thành công'
        ]);
    } catch (Exception $e) {
        error_log("Update Post Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi khi cập nhật bài viết: ' . $e->getMessage()]);
    }
}

function deletePost($pdo) {
    $postId = $_POST['id'] ?? $_GET['id'] ?? null;
    
    if (!$postId) {
        echo json_encode(['success' => false, 'error' => 'Thiếu ID bài viết']);
        return;
    }
    
    try {
        // Lấy bài viết để xóa hình ảnh
        $stmt = $pdo->prepare("SELECT featured_image FROM blog_posts WHERE id = ?");
        $stmt->execute([$postId]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($post && $post['featured_image'] && file_exists(__DIR__ . '/../../' . $post['featured_image'])) {
            unlink(__DIR__ . '/../../' . $post['featured_image']);
        }
        
        // Xóa bài viết
        $stmt = $pdo->prepare("DELETE FROM blog_posts WHERE id = ?");
        $stmt->execute([$postId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Đã xóa bài viết thành công'
        ]);
    } catch (Exception $e) {
        error_log("Delete Post Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi khi xóa bài viết']);
    }
}

// ========== CÁC HÀM QUẢN LÝ COMMENT CHO ADMIN ==========

// Lấy tất cả comment của một bài viết (bao gồm cả pending, approved, rejected)
function getAllComments($pdo) {
    $postId = $_GET['post_id'] ?? null;
    
    if (!$postId) {
        echo json_encode(['success' => false, 'error' => 'Thiếu ID bài viết']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                bc.*,
                u.Email as UserEmail,
                kh.HoTen as UserName,
                parent_u.Email as ParentUserEmail,
                parent_kh.HoTen as ParentUserName
            FROM blog_comments bc
            LEFT JOIN users u ON bc.user_id = u.ID_User
            LEFT JOIN khachhanginfo kh ON bc.user_id = kh.ID_User
            LEFT JOIN blog_comments parent ON bc.parent_comment_id = parent.id
            LEFT JOIN users parent_u ON parent.user_id = parent_u.ID_User
            LEFT JOIN khachhanginfo parent_kh ON parent.user_id = parent_kh.ID_User
            WHERE bc.post_id = ?
            ORDER BY bc.parent_comment_id IS NULL DESC, bc.created_at DESC
        ");
        $stmt->execute([$postId]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Tổ chức comments thành cấu trúc parent-child
        $parentComments = [];
        $replies = [];
        
        // Tổ chức comments thành cấu trúc parent-child (hỗ trợ nested replies)
        $allComments = []; // Mảng tất cả comments để tìm parent
        $parentComments = [];
        
        foreach ($comments as $comment) {
            $comment['created_at'] = date('d/m/Y H:i', strtotime($comment['created_at']));
            if ($comment['updated_at']) {
                $comment['updated_at'] = date('d/m/Y H:i', strtotime($comment['updated_at']));
            }
            
            $comment['replies'] = [];
            $allComments[$comment['id']] = $comment;
            
            // Kiểm tra parent_comment_id: null, '', 0, '0' đều coi là parent comment
            $parentId = $comment['parent_comment_id'];
            $isParent = ($parentId === null || $parentId === '' || $parentId === 0 || $parentId === '0');
            
            if ($isParent) {
                $parentComments[$comment['id']] = &$allComments[$comment['id']];
            }
        }
        
        // Gán replies vào parent comments (hỗ trợ nested - reply của reply)
        foreach ($allComments as $commentId => &$comment) {
            $parentId = $comment['parent_comment_id'];
            $isParent = ($parentId === null || $parentId === '' || $parentId === 0 || $parentId === '0');
            
            if (!$isParent) {
                $parentId = (int)$parentId; // Chuyển sang int để so sánh
                if (isset($allComments[$parentId])) {
                    // Gán reply vào parent (có thể là parent comment hoặc reply comment khác)
                    $allComments[$parentId]['replies'][] = &$comment;
                } else {
                    error_log("Warning: Parent comment ID $parentId not found for reply ID " . $commentId);
                }
            }
        }
        unset($comment); // Hủy reference
        
        // Sắp xếp replies theo thời gian mới nhất (đệ quy cho nested replies)
        $sortRepliesRecursiveAdmin = function(&$comment) use (&$sortRepliesRecursiveAdmin) {
            if (isset($comment['replies']) && count($comment['replies']) > 1) {
                usort($comment['replies'], function($a, $b) {
                    $timeA = strtotime($a['created_at'] ?? '1970-01-01');
                    $timeB = strtotime($b['created_at'] ?? '1970-01-01');
                    return $timeB - $timeA; // Mới nhất lên trước
                });
                
                // Sắp xếp replies của replies (nested)
                foreach ($comment['replies'] as &$reply) {
                    $sortRepliesRecursiveAdmin($reply);
                }
                unset($reply);
            }
        };
        
        foreach ($parentComments as &$parent) {
            $sortRepliesRecursiveAdmin($parent);
        }
        unset($parent); // Hủy reference
        
        // Chuyển về array index
        $result = array_values($parentComments);
        
        echo json_encode(['success' => true, 'comments' => $result]);
    } catch (Exception $e) {
        error_log("Get All Comments Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi khi lấy bình luận']);
    }
}

// Duyệt comment
function approveComment($pdo) {
    $commentId = $_POST['comment_id'] ?? null;
    
    if (!$commentId) {
        echo json_encode(['success' => false, 'error' => 'Thiếu ID bình luận']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE blog_comments 
            SET status = 'approved', updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$commentId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Đã duyệt bình luận thành công'
        ]);
    } catch (Exception $e) {
        error_log("Approve Comment Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi khi duyệt bình luận']);
    }
}

// Từ chối comment
function rejectComment($pdo) {
    $commentId = $_POST['comment_id'] ?? null;
    
    if (!$commentId) {
        echo json_encode(['success' => false, 'error' => 'Thiếu ID bình luận']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE blog_comments 
            SET status = 'rejected', updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$commentId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Đã từ chối bình luận thành công'
        ]);
    } catch (Exception $e) {
        error_log("Reject Comment Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi khi từ chối bình luận']);
    }
}

// Xóa comment
function deleteComment($pdo) {
    $commentId = $_POST['comment_id'] ?? null;
    
    if (!$commentId) {
        echo json_encode(['success' => false, 'error' => 'Thiếu ID bình luận']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM blog_comments WHERE id = ?");
        $stmt->execute([$commentId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Đã xóa bình luận thành công'
        ]);
    } catch (Exception $e) {
        error_log("Delete Comment Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi khi xóa bình luận']);
    }
}

