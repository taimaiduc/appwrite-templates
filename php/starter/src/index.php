<?php

require_once(__DIR__ . '/../vendor/autoload.php');

return function ($context) {
    $req = $context->req;
    $res = $context->res;

    $method = $req->method;
    $uploadPath = '/tmp/upload.png';
    $maxSize = 10 * 1024 * 1024; // 10MB

    // === Upload file ===
    if ($method === 'POST') {
        // Appwrite PHP runtime hỗ trợ lấy file qua $req->files
        $files = $req->files ?? [];

        // Kiểm tra file gửi từ ReactNativeBlobUtil
        if (empty($files) || !isset($files['file'])) {
            $context->log('FILES: ' . json_encode($files));
            return $res->json(['error' => 'Không tìm thấy file upload'], 400);
        }

        $file = $files['file'];

        // Validate lỗi upload
        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return $res->json(['error' => 'Lỗi upload'], 400);
        }

        // Validate dung lượng
        if ($file['size'] > $maxSize) {
            return $res->json(['error' => 'File quá lớn, tối đa 10MB'], 400);
        }

        // Validate loại file (phải là ảnh)
        $fileInfo = getimagesize($file['tmp_name']);
        if ($fileInfo === false) {
            return $res->json(['error' => 'File không phải là ảnh hợp lệ'], 400);
        }

        // Ghi file (overwrite)
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return $res->json(['error' => 'Không thể lưu file'], 500);
        }

        return $res->json(['success' => true, 'message' => 'Upload thành công']);
    }

    // === Stream ảnh đã upload ===
    if ($method === 'GET') {
        if (!file_exists($uploadPath)) {
            return $res->json(['error' => 'Chưa có file nào được upload'], 404);
        }

        $imageData = file_get_contents($uploadPath);
        $res->addHeader('Content-Type', 'image/png');
        return $res->send($imageData);
    }

    // === Phương thức khác ===
    return $res->json(['error' => 'Phương thức không được hỗ trợ'], 405);
};