<?php

require_once(__DIR__ . '/../vendor/autoload.php');

return function ($context) {
    $req = $context->req;
    $res = $context->res;

    $method = $req->method;
    $uploadPath = '/tmp/upload.png';
    $maxSize = 10 * 1024 * 1024; // 10MB

    if ($method === 'POST') {
        // Kiểm tra có file upload
        if (empty($_FILES['file'])) {
            return $res->json(['error' => 'Không tìm thấy file upload'], 400);
        }

        $file = $_FILES['file'];

        // Kiểm tra lỗi upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return $res->json(['error' => 'Lỗi upload: ' . $file['error']], 400);
        }

        // Kiểm tra dung lượng
        if ($file['size'] > $maxSize) {
            return $res->json(['error' => 'File quá lớn, tối đa 10MB'], 400);
        }

        // Kiểm tra định dạng ảnh
        $fileInfo = getimagesize($file['tmp_name']);
        if ($fileInfo === false) {
            return $res->json(['error' => 'File không phải là ảnh hợp lệ'], 400);
        }

        // Ghi file vào /tmp (overwrite)
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return $res->json(['error' => 'Không thể lưu file'], 500);
        }

        return $res->json(['success' => true, 'message' => 'Upload thành công']);
    }

    if ($method === 'GET') {
        if (!file_exists($uploadPath)) {
            return $res->json(['error' => 'Chưa có file nào được upload'], 404);
        }

        $imageData = file_get_contents($uploadPath);
        $res->addHeader('Content-Type', 'image/png');
        return $res->send($imageData);
    }

    // Mặc định: Phương thức không hợp lệ
    return $res->json(['error' => 'Phương thức không được hỗ trợ'], 405);
};
