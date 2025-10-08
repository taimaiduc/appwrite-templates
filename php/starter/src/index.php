<?php

require_once(__DIR__ . '/../vendor/autoload.php');

return function ($context) {
    $req = $context->req;
    $res = $context->res;

    $method = $req->method;
    $uploadPath = '/tmp/upload.png';
    $maxSize = 10 * 1024 * 1024; // 10MB

    if ($method === 'POST') {
        // Log header để xem Appwrite gửi gì
        $context->log('Headers: ' . json_encode($req->headers));

        // Đọc toàn bộ request body (raw multipart)
        $body = $req->body;
        if (empty($body)) {
            return $res->json(['error' => 'Body trống, không có dữ liệu upload'], 400);
        }

        // Lấy boundary từ header Content-Type
        $contentType = $req->headers['content-type'] ?? '';
        if (!preg_match('/boundary=(.*)$/', $contentType, $matches)) {
            return $res->json(['error' => 'Không tìm thấy boundary trong Content-Type'], 400);
        }

        $boundary = $matches[1];

        // Tách các phần multipart
        $parts = explode("--$boundary", $body);

        $foundFile = false;

        foreach ($parts as $part) {
            // Mỗi part chứa header + dữ liệu
            if (strpos($part, 'Content-Disposition: form-data;') !== false) {
                // Lấy tên field
                if (preg_match('/name="([^"]+)"/', $part, $nameMatch)) {
                    $name = $nameMatch[1];
                    if ($name === 'file') {
                        $foundFile = true;
                        // Tách header và nội dung file
                        $sections = explode("\r\n\r\n", $part, 2);
                        if (count($sections) === 2) {
                            $fileData = rtrim($sections[1], "\r\n");
                            file_put_contents($uploadPath, $fileData);
                            break;
                        }
                    }
                }
            }
        }

        if (!$foundFile) {
            return $res->json(['error' => 'Không tìm thấy file trong multipart'], 400);
        }

        // Validate kích thước file
        if (filesize($uploadPath) > $maxSize) {
            unlink($uploadPath);
            return $res->json(['error' => 'File quá lớn, tối đa 10MB'], 400);
        }

        // Validate là ảnh
        $fileInfo = @getimagesize($uploadPath);
        if ($fileInfo === false) {
            unlink($uploadPath);
            return $res->json(['error' => 'File không phải là ảnh hợp lệ'], 400);
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

    return $res->json(['error' => 'Phương thức không được hỗ trợ'], 405);
};