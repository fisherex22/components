<?php

function uploadToS3($imageUrl, $s3Path, $chapId) {
    $tracker = ProgressTracker::getInstance();
    $taskId = "upload_s3_" . md5($imageUrl);
    $tracker->addActiveTask($taskId, "Đang upload ảnh lên S3: " . basename($s3Path));

    try {
        $downloadDir = __DIR__ . '/downloads/';
        if (!is_dir($downloadDir)) {
            mkdir($downloadDir, 0777, true);
        }
        $filePath = $downloadDir . basename($s3Path);
         
        if ($this->downloadFile($imageUrl, $filePath)) {
            try {
                if (!file_exists($filePath) || filesize($filePath) == 0) {
                    $tracker->addError("File rỗng hoặc không tồn tại: " . basename($filePath));
                    return null;
                }

                $tracker->addActiveTask($taskId . "_naver", "Đang upload lên Naver");
                $naverUrl = $this->uploadToNaver($filePath);
                if (!$naverUrl) {
                    $tracker->addError("Lỗi upload lên Naver: " . basename($filePath));
                    return null;
                }
                $tracker->removeActiveTask($taskId . "_naver");

                if (file_exists($filePath)) {
                    unlink($filePath);
                }

                if ($chapId) {
                    $this->saveImage($chapId, $naverUrl);
                }
                
                $tracker->incrementProcessedImages();
                return $naverUrl;

            } catch (Exception $e) {
                $tracker->addError("Lỗi upload: " . $e->getMessage());
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
        } else {
            $tracker->addError("Lỗi download file: " . basename($imageUrl));
        }
         
        return null;
    } finally {
        $tracker->removeActiveTask($taskId);
    }
}

function getNaverSessionKey() {
    $tracker = ProgressTracker::getInstance();
    $taskId = "get_naver_session";
    $tracker->addActiveTask($taskId, "Đang lấy session key Naver");

    try {
        $scriptPath = realpath(__DIR__ . '/naverUploader.js');
        $command = sprintf('node "%s" getSessionKey', $scriptPath);
        
        $output = trim(shell_exec($command));
        if (!$output) {
            throw new Exception("Không có response từ getSessionKey");
        }

        $result = json_decode($output, true);
        
        if (!$result || !isset($result['success']) || !$result['success'] || !isset($result['sessionKey'])) {
            throw new Exception("Response không hợp lệ");
        }

        if (strlen($result['sessionKey']) < 20) { 
            throw new Exception("Session key quá ngắn");
        }

        return $result['sessionKey'];

    } catch (Exception $e) {
        $tracker->addError("Lỗi lấy session key: " . $e->getMessage());
        return null;
    } finally {
        $tracker->removeActiveTask($taskId);
    }
}

function uploadToNaver($filePath) {
    $tracker = ProgressTracker::getInstance();
    $taskId = "upload_naver_" . md5($filePath);
    $tracker->addActiveTask($taskId, "Đang upload lên Naver: " . basename($filePath));

    try {
        if (!file_exists($filePath)) {
            throw new Exception("File không tồn tại");
        }

        $tracker->addActiveTask($taskId . "_session", "Đang lấy session key");
        $sessionKey = trim(shell_exec(sprintf(
            'node "%s" getSessionKey 2>&1 | python3 -c "import sys, json; print(json.load(sys.stdin)[\'sessionKey\'])"',
            realpath(__DIR__ . '/naverUploader.js')
        )));

        if (empty($sessionKey)) {
            throw new Exception("Không lấy được session key");
        }
        $tracker->removeActiveTask($taskId . "_session");

        $tracker->addActiveTask($taskId . "_upload", "Đang thực hiện upload");
        $uploadOutput = shell_exec(sprintf(
            'node "%s" uploadWithSession "%s" "%s" %d 2>&1',
            realpath(__DIR__ . '/naverUploader.js'),
            $filePath,
            $sessionKey,
            0
        ));

        if (!$uploadOutput) {
            throw new Exception("Không có response từ upload command");
        }

        $result = json_decode($uploadOutput, true);
        if (!$result) {
            throw new Exception("Không parse được JSON response");
        }

        if (!isset($result['success']) || !$result['success']) {
            throw new Exception("Upload thất bại: " . ($result['error'] ?? 'Unknown error'));
        }

        if (!isset($result['url'])) {
            throw new Exception("Không có URL trong response");
        }

        $tracker->removeActiveTask($taskId . "_upload");
        return $result['url'];

    } catch (Exception $e) {
        $this->logUploadError($e, $filePath);
        $tracker->addError("Lỗi upload Naver: " . $e->getMessage());
        return null;
    } finally {
        $tracker->removeActiveTask($taskId);
    }
}

function uploadBatchToNaver($files) {
    $tracker = ProgressTracker::getInstance();
    $taskId = "batch_upload_" . md5(implode('', $files));
    $tracker->addActiveTask($taskId, "Đang upload batch " . count($files) . " files");

    try {
        $tracker->addActiveTask($taskId . "_session", "Đang lấy session key cho batch");
        $sessionKey = trim(shell_exec(sprintf(
            'node "%s" getSessionKey 2>&1 | python3 -c "import sys, json; print(json.load(sys.stdin)[\'sessionKey\'])"',
            realpath(__DIR__ . '/naverUploader.js')
        )));

        if (empty($sessionKey)) {
            throw new Exception("Không lấy được session key cho batch");
        }
        $tracker->removeActiveTask($taskId . "_session");

        $results = [];
        foreach ($files as $index => $filePath) {
            $fileTaskId = sprintf("%s_file_%d", $taskId, $index);
            $tracker->addActiveTask($fileTaskId, 
                sprintf("Đang upload file %d/%d: %s", 
                    $index + 1, 
                    count($files), 
                    basename($filePath)
                )
            );

            try {
                if (!file_exists($filePath)) {
                    $tracker->addError("File không tồn tại: " . basename($filePath));
                    continue;
                }

                $output = shell_exec(sprintf(
                    'node "%s" uploadWithSession "%s" "%s" %d 2>&1',
                    realpath(__DIR__ . '/naverUploader.js'),
                    $filePath,
                    $sessionKey,
                    $index
                ));

                $result = json_decode($output, true);
                if ($result && $result['success'] && isset($result['url'])) {
                    $results[$filePath] = $result['url'];
                    $tracker->incrementProcessedImages();
                } else {
                    $tracker->addError("Lỗi upload file " . basename($filePath));
                }

                usleep(500000); // Delay between uploads

            } catch (Exception $e) {
                $tracker->addError("Lỗi xử lý file " . basename($filePath) . ": " . $e->getMessage());
            } finally {
                $tracker->removeActiveTask($fileTaskId);
            }
        }

        return $results;

    } catch (Exception $e) {
        $tracker->addError("Lỗi batch upload: " . $e->getMessage());
        return [];
    } finally {
        $tracker->removeActiveTask($taskId);
    }
}

function checkEnvironment() {
    $tracker = ProgressTracker::getInstance();
    $taskId = "check_environment";
    $tracker->addActiveTask($taskId, "Đang kiểm tra môi trường");

    try {
        // Check Node.js
        $nodeVersion = trim(shell_exec('node -v'));
        if (!$nodeVersion) {
            throw new Exception("Không tìm thấy Node.js");
        }

        // Check script file
        $scriptPath = __DIR__ . '/naverUploader.js';
        if (!file_exists($scriptPath)) {
            throw new Exception("Không tìm thấy naverUploader.js");
        }

        // Check Node.js modules
        $moduleCheck = shell_exec(
            'node -e "try { require(\'axios\'); require(\'form-data\'); console.log(\'OK\'); } catch(e) { console.error(e); }"'
        );
        if (strpos($moduleCheck, 'OK') === false) {
            throw new Exception("Thiếu Node.js modules");
        }

        return true;

    } catch (Exception $e) {
        $tracker->addError("Lỗi kiểm tra môi trường: " . $e->getMessage());
        return false;
    } finally {
        $tracker->removeActiveTask($taskId);
    }
}

function logUploadError(Exception $e, $filePath) {
    if (file_exists($filePath)) {
        echo sprintf(
            "File info:\nPath: %s\nSize: %d bytes\nPermissions: %s\n",
            $filePath,
            filesize($filePath),
            substr(sprintf('%o', fileperms($filePath)), -3)
        );
    }
    
    echo sprintf(
        "Node.js: %s (%s)\n",
        trim(shell_exec('which node')),
        trim(shell_exec('node -v'))
    );
}