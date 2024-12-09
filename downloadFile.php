<?php

function downloadFile(string $url, string $filePath): bool {
    $tracker = ProgressTracker::getInstance();
    $taskId = "download_" . md5($url);
    $tracker->addActiveTask($taskId, "Đang tải file: " . basename($filePath));

    try {
        // Tạo thư mục
        $dirPath = dirname($filePath);
        if (!file_exists($dirPath)) {
            if (!mkdir($dirPath, 0777, true)) {
                $tracker->addError("Không thể tạo thư mục: " . basename($dirPath));
                return false;
            }
        }

        // Xử lý URL gcore.lu
        if (strpos($url, 'list-manga.s-sgc1.cloud.gcore.lu') !== false) {
            $tracker->addActiveTask($taskId . "_gcore", "Đang xử lý URL gcore.lu");
            $url = $this->processGcoreUrl($url, $taskId);
            $tracker->removeActiveTask($taskId . "_gcore");
        }

        // Download file
        $tracker->addActiveTask($taskId . "_download", "Đang download file");
        
        $tempPath = $filePath . '.tmp';
        $fp = fopen($tempPath, 'w+');
        if ($fp === false) {
            $tracker->addError("Không thể tạo file tạm: " . basename($tempPath));
            return false;
        }

        $ch = curl_init();
        curl_setopt_array($ch, $this->getCurlOptions($url, $fp));

        $success = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $fileSize = curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
        
        curl_close($ch);
        fclose($fp);

        if ($success && $httpCode == 200 && $fileSize > 0) {
            if (rename($tempPath, $filePath)) {
                $tracker->incrementProcessedImages();
                return true;
            }
        }

        $this->cleanupTempFile($tempPath);
        $tracker->addError(sprintf(
            "Download thất bại. HTTP Code: %d, Size: %d bytes",
            $httpCode,
            $fileSize
        ));
        
        return false;

    } catch (Exception $e) {
        $tracker->addError("Lỗi download file: " . $e->getMessage());
        return false;
    } finally {
        $tracker->removeActiveTask($taskId . "_download");
        $tracker->removeActiveTask($taskId);
    }
}

function processGcoreUrl(string $url, string $taskId) {
    $tracker = ProgressTracker::getInstance();

    try {
        if (preg_match('/truyen-tranh\/(.*?)\/(\d+)\/.*?-(\d+)\.jpg$/', $url, $matches)) {
            [$slug, $chapterNumber, $imageIndex] = [$matches[1], $matches[2], (int)$matches[3] - 1];
            
            $mangaId = $this->getMangaId($slug);
            if ($mangaId) {
                $tracker->addActiveTask($taskId . "_api", "Đang lấy URL gốc từ API");
                $originalUrl = $this->getOriginalUrlFromApi($mangaId, $chapterNumber, $slug, $imageIndex);
                
                if ($originalUrl) {
                    return $originalUrl;
                }

                $tracker->addActiveTask($taskId . "_html", "Đang thử lấy URL từ HTML");
                $originalUrl = $this->getOriginalUrlFromHtml($slug, $chapterNumber, $imageIndex);
                if ($originalUrl) {
                    return $originalUrl;
                }
            }
        }
    } catch (Exception $e) {
        $tracker->addError("Lỗi xử lý URL gcore: " . $e->getMessage());
    } finally {
        $tracker->removeActiveTask($taskId . "_api");
        $tracker->removeActiveTask($taskId . "_html");
    }

    return $url;
}

function getMangaId(string $slug) {
    $stmt = $this->db->prepare("SELECT id FROM manga WHERE slug = ?");
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('s', $slug);
    if (!$stmt->execute()) {
        return null;
    }

    $result = $stmt->get_result();
    $manga = $result->fetch_assoc();
    $stmt->close();

    return $manga ? $manga['id'] : null;
}

function getOriginalUrlFromApi($mangaId, $chapterNumber, $slug, $imageIndex) {
    $chapterUrl = 'https://goctruyentranhvui7.com/api/chapter/auth';
    $postData = http_build_query([
        'comicId' => $mangaId,
        'chapterNumber' => $chapterNumber,
        'nameEn' => $slug
    ]);

    $datasave = base64_decode("Y3VybCAtTCAtcyAn");
    $datasave .= $chapterUrl.base64_decode("JyBcCidyAn");
    $datasave .= $postData.base64_decode("JyBcCiAgLS1jb21wcmVzc2Vk");
    
    $response = shell_exec($datasave);
    $responseData = json_decode($response, true);

    if ($responseData && 
        isset($responseData['result']['data'][$imageIndex])) {
        return $responseData['result']['data'][$imageIndex];
    }

    return null;
}

function getOriginalUrlFromHtml($slug, $chapterNumber, $imageIndex) {
    $chapterUrl = "https://goctruyentranhvui7.com/truyen/{$slug}/chuong-{$chapterNumber}";
    $imageUrls = $this->fetchImagesFromHtml($chapterUrl);
    
    if (!empty($imageUrls) && isset($imageUrls[$imageIndex])) {
        $url = $imageUrls[$imageIndex];
        if (strpos($url, '/image/') === 0) {
            return 'https://goctruyentranhvui7.com' . $url;
        }
        return $url;
    }

    return null;
}

function getCurlOptions($url, $fp) {
    return [
        CURLOPT_URL => $url,
        CURLOPT_FILE => $fp,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER => [
            'authority: goctruyentranhvui7.com',
            'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
            'accept-language: en-US,en;q=0.9,vi;q=0.8',
            'cookie: _ga=GA1.1.1748985977.1730419651; UGVyc2lzdFN0b3JhZ2U=%7B%7D',
            'user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_ENCODING => ''
    ];
}

function cleanupTempFile($tempPath) {
    if (file_exists($tempPath)) {
        @unlink($tempPath);
    }
}