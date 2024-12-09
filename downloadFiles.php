<?php
namespace Manga;
use ProgressTracker;

trait DownloadFiles {
    protected function downloadFiles(array $fileDetails) {
    $tracker = ProgressTracker::getInstance();
    $taskId = "batch_download_" . md5(implode('', array_keys($fileDetails)));
    $tracker->addActiveTask($taskId, "Đang download " . count($fileDetails) . " files");

    try {
        $mh = curl_multi_init();
        $handles = [];
        
        // Tạo thư mục downloads
        $downloadDir = dirname(current($fileDetails));
        if (!$this->createDirectory($downloadDir, $tracker)) {
            return false;
        }

        // Khởi tạo handles
        $handles = $this->initializeDownloadHandles($fileDetails, $mh, $tracker);
        if (empty($handles)) {
            $tracker->addError("Không có file hợp lệ để download");
            curl_multi_close($mh);
            return false;
        }

        // Thực hiện multi_curl
        $tracker->addActiveTask($taskId . "_download", "Đang thực hiện download");
        $this->executeMultiCurl($mh);
        $tracker->removeActiveTask($taskId . "_download");

        // Xử lý kết quả
        $results = $this->processDownloadResults($handles, $mh, $tracker);
        
        return $results;

    } catch (Exception $e) {
        $tracker->addError("Lỗi batch download: " . $e->getMessage());
        return false;
    } finally {
        if (isset($mh)) {
            curl_multi_close($mh);
        }
        $tracker->removeActiveTask($taskId);
    }
}
}

trait CreateDirectory {
    function createDirectory($dirPath, $tracker) {
    if (!is_dir($dirPath)) {
        if (!mkdir($dirPath, 0777, true)) {
            $tracker->addError("Không thể tạo thư mục: " . basename($dirPath));
            return false;
        }
        chmod($dirPath, 0755);
    }
    return true;
    }
}
trait ProcessDownloadResults {
    protected function processDownloadResults($handles, $mh, $tracker) {
        $results = [];
        foreach ($handles as $url => $handle) {
            $ch = $handle['curl'];
            $fp = $handle['file'];
            $filePath = $handle['path'];
            
            try {
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $fileSize = curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
                
                if ($httpCode == 200 && $fileSize > 0) {
                    $results[$url] = [
                        'success' => true,
                        'path' => $filePath
                    ];
                } else {
                    $results[$url] = [
                        'success' => false,
                        'error' => "HTTP $httpCode, Size: $fileSize"
                    ];
                }
                
                curl_multi_remove_handle($mh, $ch);
                curl_close($ch);
                fclose($fp);
            } catch (Exception $e) {
                $results[$url] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        return $results;
    }    
}

trait InitializeDownloadHandles {
    function initializeDownloadHandles($fileDetails, $mh, $tracker) {
    $handles = [];
    foreach ($fileDetails as $url => $filePath) {
        $fileTaskId = "init_" . md5($url);
        $tracker->addActiveTask($fileTaskId, "Khởi tạo download: " . basename($filePath));

        try {
            // Tạo thư mục cho file
            $fileDir = dirname($filePath);
            if (!$this->createDirectory($fileDir, $tracker)) {
                continue;
            }

            // Mở file
            $fp = @fopen($filePath, 'w+');
            if ($fp === false) {
                $tracker->addError("Không thể mở file: " . basename($filePath));
                continue;
            }

            // Khởi tạo CURL
            $ch = $this->initializeCurlHandle($url, $fp);
            if ($ch === false) {
                fclose($fp);
                $tracker->addError("Không thể khởi tạo CURL cho: " . basename($filePath));
                continue;
            }

            curl_multi_add_handle($mh, $ch);
            $handles[$url] = ['curl' => $ch, 'file' => $fp, 'path' => $filePath];

        } catch (Exception $e) {
            $tracker->addError("Lỗi khởi tạo download: " . $e->getMessage());
            $this->cleanupResources($fp ?? null, $ch ?? null);
        } finally {
            $tracker->removeActiveTask($fileTaskId);
        }
    }
    return $handles;
    }
}
trait InitializeCurlHandle {
    function initializeCurlHandle($url, $fp) {
    $ch = curl_init();
    if ($ch === false) {
        return false;
    }

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_FILE => $fp,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER => ['Referer: https://goctruyentranhvui7.com/'],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);

    return $ch;
    }
}

trait ExecuteMultiCurl {
    function executeMultiCurl($mh) {
    $running = null;
    do {
        $status = curl_multi_exec($mh, $running);
        if ($running) {
            curl_multi_select($mh);
        }
    } while ($running > 0 && $status == CURLM_OK);
    }
}

trait UploadToS3FromLocalFile {
    function uploadToS3FromLocalFile($localPath, $s3Path, $chapId) {
    $tracker = ProgressTracker::getInstance();
    $taskId = "s3_upload_" . md5($localPath);
    $tracker->addActiveTask($taskId, "Đang upload lên S3: " . basename($localPath));

    try {
        if (!$this->validateLocalFile($localPath, $tracker)) {
            return null;
        }

        $tracker->addActiveTask($taskId . "_upload", "Đang upload file");
        $this->s3->putObject([
            'Bucket' => 'list-manga',
            'Key' => $s3Path,
            'SourceFile' => $localPath,
            'ACL' => 'public-read',
        ]);
        $tracker->removeActiveTask($taskId . "_upload");

        if (!$this->verifyS3Upload($s3Path, $tracker)) {
            return null;
        }

        $imageKitUrl = "https://list-manga.s-sgc1.cloud.gcore.lu/{$s3Path}";
        
        $this->cleanupLocalFile($localPath, $tracker);
        
        if ($chapId) {
            $this->saveImage($chapId, $imageKitUrl);
        }

        $tracker->incrementProcessedImages();
        return $imageKitUrl;

    } catch (Aws\Exception\S3Exception $e) {
        $tracker->addError("Lỗi S3: " . $e->getMessage());
    } catch (Exception $e) {
        $tracker->addError("Lỗi upload: " . $e->getMessage());
    } finally {
        $tracker->removeActiveTask($taskId);
    }

    return null;
    }
}

trait ValidateLocalFile {
    function validateLocalFile($localPath, $tracker) {
    if (!file_exists($localPath) || filesize($localPath) == 0) {
        $tracker->addError("File không tồn tại hoặc rỗng: " . basename($localPath));
        return false;
    }
    return true;
    }
}

trait VerifyS3Upload {
    function verifyS3Upload($s3Path, $tracker) {
    try {
        $objectInfo = $this->s3->headObject([
            'Bucket' => 'list-manga',
            'Key' => $s3Path
        ]);
        
        if (($objectInfo['ContentLength'] ?? 0) == 0) {
            $tracker->addError("File upload lên S3 có kích thước 0: " . basename($s3Path));
            return false;
        }
        return true;
    } catch (Exception $e) {
        $tracker->addError("Lỗi kiểm tra file trên S3: " . $e->getMessage());
        return false;
        }
    }
}
trait CleanupLocalFile {
    function cleanupLocalFile($localPath, $tracker) {
    if (file_exists($localPath)) {
        if (!unlink($localPath)) {
            $tracker->addError("Không thể xóa file local: " . basename($localPath));
            }
        }
    }
}
trait CleanupResources {
    function cleanupResources($fp, $ch) {
    if (is_resource($fp)) {
        fclose($fp);
    }
    if ($ch) {
        curl_close($ch);
        }
    }
}