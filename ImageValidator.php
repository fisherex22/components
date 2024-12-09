<?php
namespace Manga;
use ProgressTracker;

trait ImageValidator {
    protected function isImagesEmptyOrNull($images, $nameEn = null, $chapterNumber = null, $chapterId = null) {
        $tracker = ProgressTracker::getInstance();

        if ($images === null) {
            $tracker->addError("[Chapter $chapterId] Không có dữ liệu ảnh");
            return true;
        }
        
        $decodedImages = json_decode($images, true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($decodedImages)) {
            $tracker->addError("[Chapter $chapterId] JSON ảnh không hợp lệ hoặc trống");
            return true;
        }

        return false;
    }
}
