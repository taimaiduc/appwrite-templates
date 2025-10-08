<?php

require_once(__DIR__ . '/../vendor/autoload.php');

use Appwrite\Functions\Context;

return function (Context $context) {
    $req = $context->req;
    $res = $context->res;
    $log = $context->log;

    $uploadPath = '/tmp/upload.png';

    // --- POST: Upload ảnh ---
    if ($req->method === 'POST') {
        // Đọc toàn bộ dữ liệu nhị phân từ body
        $data = file_get_contents('php://input');

        if (!$data) {
            return $res->json([
                'success' => false,
                'error' => 'Không tìm thấy dữ liệu upload hoặc file rỗng',
            ], 400);
        }

        // Kiểm tra dung lượng tối đa 10 MB
        if (strlen($data) > 10 * 1024 * 1024) {
            return $res->json([
                'success' => false,
                'error' => 'File vượt quá 10MB cho phép',
            ], 400);
        }

        // --- Validate MIME ---
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($data);

        $allowed = ['image/png', 'image/jpeg', 'image/webp'];
        if (!in_array($mimeType, $allowed)) {
            return $res->json([
                'success' => false,
                'error' => "Định dạng file không hợp lệ: $mimeType",
            ], 400);
        }

        // Ghi file đè (overwrite)
        file_put_contents($uploadPath, $data);

        $log("Đã upload file: $uploadPath ($mimeType)");

        return $res->json([
            'success' => true,
            'message' => 'Upload thành công!',
            'mime' => $mimeType,
            'size_bytes' => strlen($data),
        ]);
    }

    // --- GET: Trả về hình ảnh ---
    if ($req->method === 'GET') {
        if (!file_exists($uploadPath)) {
            return $res->json([
                'success' => false,
                'error' => 'Chưa có file nào được upload',
            ], 404);
        }

        $imageData = file_get_contents($uploadPath);

        return $res
            ->setHeader('Content-Type', 'image/png')
            ->send($imageData);
    }

    // --- Không hỗ trợ method khác ---
    return $res->json([
        'success' => false,
        'error' => 'Method không được hỗ trợ. Chỉ hỗ trợ POST và GET.',
    ], 405);
};