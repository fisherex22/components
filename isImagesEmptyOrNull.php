<?php 

function isImagesEmptyOrNull($images, $nameEn = null, $chapterNumber = null, $chapterId = null) {
    $tracker = ProgressTracker::getInstance();

    if ($images === null) {
        $tracker->addError("[Chapter $chapterId] Images is null");
        return true;
    }
    
    $decodedImages = json_decode($images, true);
    if (json_last_error() !== JSON_ERROR_NONE || empty($decodedImages)) {
        $tracker->addError("[Chapter $chapterId] Invalid or empty images JSON");
        return true;
    }

    // Kiểm tra cần xử lý lại không (có ImageKit hoặc không phải Naver)
    $needsReprocessing = false;
    foreach ($decodedImages as $image) {
        $imageUrl = is_string($image) ? $image : ($image['url'] ?? null);
        if (!$imageUrl) continue;

        if (strpos($imageUrl, 'ik.imagekit.io') !== false || 
            (strpos($imageUrl, 'blogfiles.pstatic.net') === false)) {
            $needsReprocessing = true;
            break;
        }
    }

    // Nếu tất cả đều là ảnh Naver hợp lệ
    if (!$needsReprocessing) {
        return false;
    }

    $tracker->addActiveTask("process_chapter_$chapterId", 
        "Đang xử lý lại ảnh cho chapter $chapterNumber");
    
    try {
        // Lấy session key cho Naver
        $sessionKey = $this->getNaverSessionKey();
        if (!$sessionKey) {
            throw new Exception("Failed to get Naver session key");
        }

        $updatedImages = [];
        $tracker->addImages(count($decodedImages));
        
        foreach ($decodedImages as $index => $image) {
            $imageUrl = is_string($image) ? $image : ($image['url'] ?? null);
            if (!$imageUrl) {
                $tracker->addError("[Chapter $chapterId] Invalid image at index $index");
                continue;
            }

            $taskId = "image_{$chapterId}_{$index}";
            $tracker->addActiveTask($taskId, "Đang xử lý ảnh $index của chapter $chapterNumber");

            // Giữ nguyên ảnh Naver
            if (strpos($imageUrl, 'blogfiles.pstatic.net') !== false) {
                $updatedImages[] = ['url' => $imageUrl];
                $tracker->incrementProcessedImages();
                $tracker->removeActiveTask($taskId);
                continue;
            }

            try {
                // Setup paths
                $newS3Path = "{$nameEn}/{$chapterNumber}/{$nameEn}-{$chapterNumber}-" . ($index + 1) . ".jpg";
                $downloadDir = __DIR__ . '/downloads/';
                if (!is_dir($downloadDir)) {
                    mkdir($downloadDir, 0777, true);
                }
                $localPath = $downloadDir . basename($newS3Path);

                // Download ảnh
                if ($this->downloadFile($imageUrl, $localPath)) {
                    clearstatcache();
                    $fileSize = @filesize($localPath);
                    
                    if ($fileSize && $fileSize > 0) {
                        // Random between Memo and Blog upload
                        $naverUrl = null;
                        if (rand(1, 100) <= 70) { // 70% chance for Memo
                            $tracker->addActiveTask($taskId . "_memo", "Đang upload lên Naver Memo");
                            $scriptPath = realpath(__DIR__ . '/naverUploader.js');
                            $command = sprintf(
                                'node "%s" uploadMemo "%s" 2>&1',
                                $scriptPath,
                                $localPath
                            );
                            $output = shell_exec($command);
                            $result = json_decode($output, true);
                            if ($result && $result['success'] && !empty($result['urls'])) {
                                $naverUrl = $result['urls'][0];
                            }
                            $tracker->removeActiveTask($taskId . "_memo");
                        }

                        // Fallback to Blog upload if Memo failed
                        if (!$naverUrl) {
                            $tracker->addActiveTask($taskId . "_blog", "Đang upload lên Naver Blog");
                            $naverUrl = $this->uploadToNaverWithSession($localPath, $sessionKey, $index);
                            $tracker->removeActiveTask($taskId . "_blog");
                        }

                        if ($naverUrl) {
                            $updatedImages[] = ['url' => $naverUrl];
                            $tracker->incrementProcessedImages();

                            // Xóa ảnh cũ từ S3 nếu cần
                            if (strpos($imageUrl, 'list-manga.s-sgc1.cloud.gcore.lu') !== false) {
                                $s3Key = str_replace([
                                    'https://list-manga.s-sgc1.cloud.gcore.lu/',
                                    'https://ik.imagekit.io/6vnjnemu6/'
                                ], '', $imageUrl);
                                
                                try {
                                    $this->s3->deleteObject([
                                        'Bucket' => 'list-manga',
                                        'Key' => $s3Key
                                    ]);
                                } catch (Exception $e) {
                                    $tracker->addError("Failed to delete from S3: $s3Key - " . $e->getMessage());
                                }
                            }
                        } else {
                            $tracker->addError("[Chapter $chapterId] Failed to upload image $index to Naver");
                        }
                    } else {
                        $tracker->addError("[Chapter $chapterId] Downloaded file is empty: $localPath");
                    }
                    @unlink($localPath);
                }
            } catch (Exception $e) {
                $tracker->addError("[Chapter $chapterId] Error processing image $index: " . $e->getMessage());
            } finally {
                $tracker->removeActiveTask($taskId);
            }
        }

        // Update chapter nếu có ảnh mới
        if (!empty($updatedImages)) {
            $this->updateChapterImages($chapterId, $updatedImages);
            $tracker->incrementProcessedChapters();
        }

        return empty($updatedImages);

    } catch (Exception $e) {
        $tracker->addError("[Chapter $chapterId] Error in image verification: " . $e->getMessage());
        return true;
    } finally {
        $tracker->removeActiveTask("process_chapter_$chapterId");
    }
} 