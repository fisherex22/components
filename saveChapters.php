<?php
namespace Manga;
use ProgressTracker;
require_once 'DateConverter.php';

class ChapterManager {
    use \Manga\ImageValidator;
    use \Manga\DateConverter;

    public function saveChapters() {
        $tracker = ProgressTracker::getInstance();
        $story_id = $this->truyen['id'];
        $taskId = "save_chapters_{$story_id}";
        
        $tracker->addActiveTask($taskId, "Đang lưu chapters cho manga {$story_id}");

        try {
            $chapters = $this->truyen['chapterLatest'];
            $chapterIds = $this->truyen['chapterLatestId'];
            $chapterDates = $this->truyen['chapterLatestDate'];
            
            $totalChapters = count($chapters);
            $tracker->addChapters($totalChapters);
            
            $query = "INSERT INTO chapters (id, manga_id, title, chapter_number, views, created_at, content_type) 
                      VALUES (?, ?, ?, ?, 0, ?, 'manga')
                      ON DUPLICATE KEY UPDATE 
                      manga_id=VALUES(manga_id), 
                      title=VALUES(title), 
                      chapter_number=VALUES(chapter_number), 
                      created_at=VALUES(created_at)";

            for ($i = 0; $i < $totalChapters; $i++) {
                $chapterTaskId = sprintf("%s_chapter_%d", $taskId, $i);
                $title = $chapters[$i];
                $id = $chapterIds[$i];
                $currentIndex = $i + 1;

                $progressMessage = sprintf(
                    "Đang xử lý chapter %s (%d/%d)", 
                    $title, 
                    $currentIndex, 
                    $totalChapters
                );
                
                $tracker->addActiveTask($chapterTaskId, $progressMessage);

                try {
                    if ($this->shouldSaveChapter($id)) {
                        $this->insertOrUpdateChapter(
                            $query, 
                            $id, 
                            $story_id, 
                            $title, 
                            $this->convertDate($chapterDates[$i])
                        );
                        $tracker->incrementProcessedChapters();
                    }
                } catch (Exception $e) {
                    $errorMessage = sprintf("Lỗi lưu chapter %s: %s", $title, $e->getMessage());
                    $tracker->addError($errorMessage);
                } finally {
                    $tracker->removeActiveTask($chapterTaskId);
                }
            }

        } catch (Exception $e) {
            $errorMessage = sprintf("Lỗi lưu chapters cho manga %s: %s", $story_id, $e->getMessage());
            $tracker->addError($errorMessage);
        } finally {
            $tracker->removeActiveTask($taskId);
        }
    }
    
    private function shouldSaveChapter($chapterId) {
        $tracker = ProgressTracker::getInstance();
        $taskId = "check_chapter_{$chapterId}";
        
        try {
            $tracker->addActiveTask($taskId, "Kiểm tra chapter {$chapterId}");
            
            $checkQuery = "SELECT id, images FROM chapters WHERE id = ?";
            $stmt = $this->db->prepare($checkQuery);
            
            if (!$stmt) {
                throw new Exception("Lỗi prepare statement: " . $this->db->error);
            }

            $stmt->bind_param("i", $chapterId);
            if (!$stmt->execute()) {
                throw new Exception("Lỗi execute statement: " . $stmt->error);
            }

            $result = $stmt->get_result();
            $existingChapter = $result->fetch_assoc();

            // Return true if chapter doesn't exist or has no images
            return !$existingChapter || $this->isImagesEmptyOrNull($existingChapter['images']);

        } catch (Exception $e) {
            $tracker->addError("Lỗi kiểm tra chapter {$chapterId}: " . $e->getMessage());
            return false;
        } finally {
            $tracker->removeActiveTask($taskId);
        }
    }
    
    private function insertOrUpdateChapter($query, $id, $story_id, $title, $created_at) {
        $tracker = ProgressTracker::getInstance();
        $taskId = "insert_chapter_{$id}";
        
        try {
            $tracker->addActiveTask($taskId, "Đang lưu chapter {$title}");

            $stmt = $this->db->prepare($query);
            if (!$stmt) {
                throw new Exception("Lỗi prepare statement: " . $this->db->error);
            }

            $stmt->bind_param("iisss", $id, $story_id, $title, $title, $created_at);
            if (!$stmt->execute()) {
                throw new Exception("Lỗi execute statement: " . $stmt->error);
            }

            if ($this->db->affected_rows > 0) {
                $tracker->addActiveTask("{$taskId}_success", 
                    "Chapter {$title} đã được thêm/cập nhật thành công");
                $tracker->removeActiveTask("{$taskId}_success");
            }

        } catch (Exception $e) {
            throw new Exception("Lỗi lưu chapter {$title}: " . $e->getMessage());
        } finally {
            $tracker->removeActiveTask($taskId);
        }
    }
}