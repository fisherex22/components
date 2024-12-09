<?php

function retryDownloadImage($imageUrl, $s3Path, $chapterId, $maxRetries = 3) {
    $tracker = ProgressTracker::getInstance();
    $taskId = "retry_download_" . md5($imageUrl);
    $tracker->addActiveTask($taskId, "Đang thử tải lại ảnh cho chapter $chapterId");

    try {
        for ($i = 0; $i < $maxRetries; $i++) {
            $attemptId = $taskId . "_attempt_" . ($i + 1);
            $tracker->addActiveTask($attemptId, 
                "Lần thử " . ($i + 1) . "/" . $maxRetries . " tải ảnh: " . basename($imageUrl));
            
            $localPath = __DIR__ . '/downloads/' . basename($s3Path);
            
            if ($this->downloadFile($imageUrl, $localPath)) {
                if (filesize($localPath) > 0) {
                    $tracker->addActiveTask($attemptId . "_upload", "Đang upload ảnh lên S3");
                    $uploadedUrl = $this->uploadToS3FromLocalFile($localPath, $s3Path, $chapterId);
                    
                    if ($uploadedUrl) {
                        $updatedUrl = 'https://list-manga.s-sgc1.cloud.gcore.lu/s2truyen/truyen-tranh/' . basename($s3Path);
                        $tracker->incrementProcessedImages();
                        return $updatedUrl;
                    }
                    $tracker->removeActiveTask($attemptId . "_upload");
                } else {
                    $tracker->addError("File downloaded nhưng rỗng: " . basename($localPath));
                }
            }
            
            $tracker->removeActiveTask($attemptId);
            
            if ($i < $maxRetries - 1) {
                $tracker->addActiveTask($attemptId . "_wait", "Đợi 2s trước lần thử tiếp theo");
                sleep(2);
                $tracker->removeActiveTask($attemptId . "_wait");
            }
        }
        
        $tracker->addError("Không thể tải lại ảnh sau $maxRetries lần thử: " . basename($imageUrl));
        return null;

    } catch (Exception $e) {
        $tracker->addError("Lỗi khi thử tải lại ảnh: " . $e->getMessage());
        return null;
    } finally {
        $tracker->removeActiveTask($taskId);
    }
}

function updateChapterImages($chapterId, $images) {
    $tracker = ProgressTracker::getInstance();
    $taskId = "update_chapter_$chapterId";
    $tracker->addActiveTask($taskId, "Đang cập nhật thông tin ảnh cho chapter $chapterId");

    try {
        $imagesJson = json_encode($images, JSON_UNESCAPED_SLASHES);
        $query = "UPDATE chapters SET images = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        if (!$stmt->bind_param("si", $imagesJson, $chapterId)) {
            throw new Exception("Lỗi bind param: " . $stmt->error);
        }
        if (!$stmt->execute()) {
            throw new Exception("Lỗi execute: " . $stmt->error);
        }
        
        $tracker->incrementProcessedChapters();
        return true;

    } catch (Exception $e) {
        $tracker->addError("Lỗi cập nhật chapter $chapterId: " . $e->getMessage());
        return false;
    } finally {
        $tracker->removeActiveTask($taskId);
    }
}

function chapterExists($chapterId) {
    $tracker = ProgressTracker::getInstance();
    $taskId = "check_chapter_$chapterId";
    $tracker->addActiveTask($taskId, "Đang kiểm tra chapter $chapterId");

    try {
        if (!$chapterId) {
            $tracker->addError("Chapter ID không hợp lệ");
            return false;
        }

        $query = "SELECT ch.id, ch.images, ch.manga_id, ch.chapter_number, m.nameEn 
                 FROM chapters ch 
                 JOIN manga m ON ch.manga_id = m.id 
                 WHERE ch.id = ?";
        
        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            throw new Exception("Lỗi prepare statement: " . $this->db->error);
        }

        $stmt->bind_param("i", $chapterId);
        $stmt->execute();
        $result = $stmt->get_result();
        $chapter = $result->fetch_assoc();

        if (!$chapter) {
            $tracker->addError("Không tìm thấy chapter: $chapterId");
            return false;
        }

        // Kiểm tra ảnh
        $isEmpty = $this->isImagesEmptyOrNull(
            $chapter['images'],
            $chapter['nameEn'],
            $chapter['chapter_number'],
            $chapter['id']
        );

        return !$isEmpty;

    } catch (Exception $e) {
        $tracker->addError("Lỗi kiểm tra chapter $chapterId: " . $e->getMessage());
        return false;
    } finally {
        $tracker->removeActiveTask($taskId);
    }
}

function processComicOnly() {
    $tracker = ProgressTracker::getInstance();
    $mangaId = $this->truyen['id'];
    $taskId = "process_manga_$mangaId";
    
    try {
        $tracker->addActiveTask($taskId, "Đang xử lý manga: " . $this->truyen['name']);

        // Save manga basic info
        $tracker->addActiveTask($taskId . "_basic", "Đang lưu thông tin cơ bản manga");
        $mangaUrl = $this->saveManga();
        $tracker->removeActiveTask($taskId . "_basic");

        if ($mangaUrl !== null) {
            // Process categories
            $tracker->addActiveTask($taskId . "_categories", "Đang xử lý categories");
            $categories = $this->fetchCategoriesFromHtml($mangaUrl);
            $this->updateMangaCategories($this->truyen['id'], $categories);
            $tracker->removeActiveTask($taskId . "_categories");
        }

        // Process chapters
        $tracker->addActiveTask($taskId . "_chapters", "Đang xử lý chapters");
        $this->saveChapters();
        $this->fetchAllChapters($this->truyen['id'], $this->truyen['nameEn']);
        $tracker->removeActiveTask($taskId . "_chapters");

        $tracker->incrementProcessedManga();

    } catch (Exception $e) {
        $tracker->addError("Lỗi xử lý manga {$this->truyen['name']}: " . $e->getMessage());
    } finally {
        $tracker->removeActiveTask($taskId);
    }
}