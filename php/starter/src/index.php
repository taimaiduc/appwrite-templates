<?php

require_once(__DIR__ . '/../vendor/autoload.php');


return function ($context) {
    $req = $context->req;
    $res = $context->res;
    $log = $context->log;

    $uploadPath = '/tmp/upload.png';
    $maxBytes = 10 * 1024 * 1024; // 10 MB

    $method = strtoupper($req->method ?? 'GET');

    // ---------------------- POST (UPLOAD BASE64) ----------------------
    if ($method === 'POST') {
        $raw = $req->body ?? file_get_contents('php://input');

        if (!$raw) {
            return $res->json([
                'success' => false,
                'error' => 'Không tìm thấy dữ liệu body'
            ], 400);
        }

        // Decode JSON
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return $res->json([
                'success' => false,
                'error' => 'Body không phải JSON hợp lệ'
            ], 400);
        }

        if (empty($data['fileData'])) {
            return $res->json([
                'success' => false,
                'error' => 'Thiếu field fileData (base64)'
            ], 400);
        }

        // Lấy base64 string, loại bỏ prefix "data:image/png;base64,"
        $base64 = preg_replace('#^data:image/\w+;base64,#i', '', $data['fileData']);
        $binary = base64_decode($base64);

        if ($binary === false) {
            return $res->json([
                'success' => false,
                'error' => 'Không thể decode base64'
            ], 400);
        }

        // Validate dung lượng
        if (strlen($binary) > $maxBytes) {
            return $res->json([
                'success' => false,
                'error' => 'File vượt quá 10MB'
            ], 400);
        }

        // Validate định dạng ảnh
        $imageInfo = @getimagesizefromstring($binary);
        if ($imageInfo === false) {
            return $res->json([
                'success' => false,
                'error' => 'File không phải ảnh hợp lệ'
            ], 400);
        }

        // Lưu file tạm vào /tmp
        file_put_contents($uploadPath, $binary);

        return $res->json([
            'success' => true,
            'message' => 'Upload thành công',
            'mime' => $imageInfo['mime'],
            'width' => $imageInfo[0],
            'height' => $imageInfo[1],
            'path' => $uploadPath
        ]);
    }

    // ---------------------- GET (STREAM IMAGE) ----------------------
    if ($method === 'GET') {
        if (!file_exists($uploadPath)) {
            return $res->json([
                'success' => false,
                'error' => 'Chưa có file nào được upload'
            ], 404);
        }

        $mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $uploadPath);
        $binary = file_get_contents($uploadPath);

        // Trả về binary image
        $res->setHeader('Content-Type', $mime);
        return $res->send($binary);
    }

    // ---------------------- DEFAULT ----------------------
    return $res->json([
        'success' => false,
        'error' => 'Method không hỗ trợ'
    ], 405);
};