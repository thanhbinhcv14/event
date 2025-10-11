<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/auth/auth.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getDBConnection();

function isAuthorizedRole($roleId) {
    return in_array((int)$roleId, [1, 2, 3]);
}

function getJsonInput() {
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

try {
    if ($method === 'GET') {
        if (isset($_GET['id'])) {
            $stmt = $pdo->prepare('SELECT * FROM sukien WHERE ID_SK = ?');
            $stmt->execute([$_GET['id']]);
            $event = $stmt->fetch();
            if (!$event) {
                http_response_code(404);
                echo json_encode(['error' => 'Không tìm thấy sự kiện']);
                exit;
            }
            echo json_encode($event);
            exit;
        }

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 20;
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];
        if (!empty($_GET['q'])) {
            $where[] = 'TenSuKien LIKE ?';
            $params[] = '%' . $_GET['q'] . '%';
        }
        if (!empty($_GET['status'])) {
            $where[] = 'TrangThai = ?';
            $params[] = $_GET['status'];
        }
        if (!empty($_GET['loai'])) {
            $where[] = 'ID_LoaiSK = ?';
            $params[] = (int)$_GET['loai'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM sukien $whereSql");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $sql = "SELECT * FROM sukien $whereSql ORDER BY NgayBatDau DESC LIMIT $limit OFFSET $offset";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $events = $stmt->fetchAll();

        echo json_encode([
            'data' => $events,
            'page' => $page,
            'limit' => $limit,
            'total' => $total
        ]);
        exit;
    }

    // Auth for mutating methods
    $decoded = authenticate();
    if (!isAuthorizedRole($decoded->role ?? null)) {
        http_response_code(403);
        echo json_encode(['error' => 'Không có quyền thực hiện']);
        exit;
    }

    if ($method === 'POST') {
        $data = getJsonInput();
        $required = ['TenSuKien','NgayBatDau','NgayKetThuc','ID_DD'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Thiếu trường $field"]);
                exit;
            }
        }
        $stmt = $pdo->prepare('INSERT INTO sukien (TenSuKien, MoTa, NgayBatDau, NgayKetThuc, ID_DD, ID_User, TrangThai, ID_LoaiSK) VALUES (?,?,?,?,?,?,?,?)');
        $stmt->execute([
            $data['TenSuKien'],
            $data['MoTa'] ?? null,
            $data['NgayBatDau'],
            $data['NgayKetThuc'],
            (int)$data['ID_DD'],
            (int)($data['ID_User'] ?? $decoded->sub),
            $data['TrangThai'] ?? 'Chờ duyệt',
            isset($data['ID_LoaiSK']) ? (int)$data['ID_LoaiSK'] : null
        ]);
        echo json_encode(['message' => 'Tạo sự kiện thành công', 'id' => $pdo->lastInsertId()]);
        exit;
    }

    if ($method === 'PUT') {
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Thiếu id']);
            exit;
        }
        $data = getJsonInput();
        $fields = ['TenSuKien','MoTa','NgayBatDau','NgayKetThuc','ID_DD','ID_User','TrangThai','ID_LoaiSK'];
        $sets = [];
        $params = [];
        foreach ($fields as $f) {
            if (array_key_exists($f, $data)) {
                $sets[] = "$f = ?";
                $params[] = $data[$f];
            }
        }
        if (empty($sets)) {
            echo json_encode(['message' => 'Không có gì để cập nhật']);
            exit;
        }
        $params[] = (int)$_GET['id'];
        $sql = 'UPDATE sukien SET ' . implode(', ', $sets) . ' WHERE ID_SK = ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['message' => 'Cập nhật thành công']);
        exit;
    }

    if ($method === 'DELETE') {
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Thiếu id']);
            exit;
        }
        $stmt = $pdo->prepare('DELETE FROM sukien WHERE ID_SK = ?');
        $stmt->execute([(int)$_GET['id']]);
        echo json_encode(['message' => 'Xóa sự kiện thành công']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Phương thức không được hỗ trợ']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Lỗi CSDL', 'detail' => $e->getMessage()]);
}
?>


