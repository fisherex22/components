<?php

function processChapterImages($chapterResponse, $nameEn, $title, $chapterId, $sessionKey) {
    $tracker = ProgressTracker::getInstance();
    $taskId = "process_chapter_{$chapterId}";
    $tracker->addActiveTask($taskId, "Đang xử lý chapter {$title} của manga {$nameEn}");

    try {
        if (!isset($chapterResponse['result']['data']) || empty($chapterResponse['result']['data'])) {
            $tracker->addError("Không có dữ liệu ảnh cho chapter {$title}");
            return [];
        }

        $imageUrls = $chapterResponse['result']['data'];
        $totalImages = count($imageUrls);
        $tracker->addImages($totalImages);
        $tracker->addActiveTask("{$taskId}_prep", "Chuẩn bị xử lý {$totalImages} ảnh cho chapter {$title}");

        $updatedImages = [];
        $fileDetails = [];

        // Chuẩn bị thông tin download
        foreach ($imageUrls as $imgIndex => $imageUrl) {
            try {
                $imageNumber = $imgIndex + 1;
                $s3Path = "s2truyen/truyen-tranh/{$nameEn}/{$title}/{$nameEn}-{$title}-{$imageNumber}.jpg";
                $localPath = __DIR__ . '/downloads/' . basename($s3Path);
                $fileDetails[$imageUrl] = $localPath;
                
                $imgTaskId = "{$taskId}_img_{$imgIndex}";
                $tracker->addActiveTask($imgTaskId, "Chuẩn bị download ảnh {$imageNumber}/{$totalImages}");
                
            } catch (Exception $e) {
                $errorMessage = "Lỗi chuẩn bị ảnh {$imageNumber} của chapter {$title}: " . $e->getMessage();
                $tracker->addError($errorMessage);
            } finally {
                if (isset($imgTaskId)) {
                    $tracker->removeActiveTask($imgTaskId);
                }
            }
        }

        $tracker->removeActiveTask("{$taskId}_prep");

        // Thực hiện download
        if (!empty($fileDetails)) {
            $downloadTaskId = "{$taskId}_download";
            $tracker->addActiveTask($downloadTaskId, "Đang download {$totalImages} ảnh của chapter {$title}");

            $downloadResults = $this->downloadFiles($fileDetails);
            $tracker->removeActiveTask($downloadTaskId);

            if ($downloadResults) {
                // Kiểm tra files đã download
                $successFiles = [];
                $verifyTaskId = "{$taskId}_verify";
                $tracker->addActiveTask($verifyTaskId, "Đang kiểm tra files đã download");

                foreach ($fileDetails as $imageUrl => $localPath) {
                    if (file_exists($localPath) && filesize($localPath) > 0) {
                        $successFiles[] = $localPath;
                        $tracker->incrementProcessedImages();
                    } else {
                        $tracker->addError("File không hợp lệ hoặc rỗng: " . basename($localPath));
                    }
                }

                $tracker->removeActiveTask($verifyTaskId);

                // Upload lên Naver
                if (!empty($successFiles)) {
                    $uploadTaskId = "{$taskId}_upload";
                    $successCount = count($successFiles);
                    $tracker->addActiveTask($uploadTaskId, "Đang upload {$successCount} ảnh lên Naver");

                    $uploadResults = $this->uploadBatchToNaverWithSession($successFiles, $sessionKey);
                    
                    if ($uploadResults['success']) {
                        foreach ($uploadResults['urls'] as $url) {
                            $updatedImages[] = ['url' => $url];
                        }
                        
                        // Update database
                        if (!empty($updatedImages)) {
                            $dbTaskId = "{$taskId}_db";
                            $updateCount = count($updatedImages);
                            $tracker->addActiveTask($dbTaskId, "Đang cập nhật {$updateCount} ảnh vào database");

                            $this->updateChapterImages($chapterId, $updatedImages);
                            $tracker->incrementProcessedChapters();

                            $tracker->removeActiveTask($dbTaskId);
                        }
                    } else {
                        foreach ($uploadResults['errors'] as $index => $error) {
                            $tracker->addError("Lỗi upload ảnh {$index} của chapter {$title}: {$error}");
                        }
                    }

                    $tracker->removeActiveTask($uploadTaskId);
                } else {
                    $tracker->addError("Không có file hợp lệ để upload cho chapter {$title}");
                }
            } else {
                $tracker->addError("Lỗi download ảnh cho chapter {$title}");
            }
        } else {
            $tracker->addError("Không có thông tin ảnh để download cho chapter {$title}");
        }

        return $updatedImages;

    } catch (Exception $e) {
        $tracker->addError("Lỗi xử lý chapter {$title}: " . $e->getMessage());
        return [];
    } finally {
        // Cleanup
        foreach ($fileDetails as $localPath) {
            if (file_exists($localPath)) {
                @unlink($localPath);
            }
        }
        $tracker->removeActiveTask($taskId);
    }
}