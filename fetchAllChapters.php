<?php
namespace Manga;
use ProgressTracker;

require_once 'DateConverter.php';
require_once 'uploadToS3.php';
require_once __DIR__ . '/fetchImagesFromHtml.php';

class ChapterFetcher {
    use \Manga\ImageValidator;
    use \Manga\DateConverter;
    use \Manga\DownloadFiles;
    use \Manga\CreateDirectory;
    use \Manga\InitializeDownloadHandles;
    use \Manga\InitializeCurlHandle;
    use \Manga\ExecuteMultiCurl;
    use \Manga\UploadToS3FromLocalFile;
    use \Manga\ValidateLocalFile;
    use \Manga\VerifyS3Upload;
    use \Manga\CleanupLocalFile;
    use \Manga\CleanupResources;
    use \Manga\ProcessDownloadResults;
    use \Manga\UploadToNaverWithSession;
    use \Manga\UploadBatchToNaverWithSession;
    use \Manga\PrepareMemoUploadCommands;
    use \Manga\PrepareIndividualUploadCommands;
    use \Manga\ExecuteUploadCommand;
    use \Manga\ValidateUploadResult;
    use \Manga\ProcessUploadResult;
    use \Manga\ExtractUploadUrl;
    use \Manga\FetchCategoriesFromHtml;
    use \Manga\SaveManga;
    use \Manga\SaveImage;
    use \Manga\ProcessExistingImages;
    use \Manga\FetchImagesFromHtmls;

    

    public function fetchAllChapters($comicId, $nameEn) {
    $tracker = ProgressTracker::getInstance();
    $taskId = "fetch_chapters_{$comicId}";
    $tracker->addActiveTask($taskId, "Đang lấy chapters cho manga {$nameEn}");

    try {
        // Khởi tạo API call
        $chapterListUrl = "https://goctruyentranhvui7.com/api/comic/{$comicId}/chapter?offset=0&limit=-1";
        $datasave = base64_decode("Y3VybCAtTCAtcyAn");
        $datasave .= $chapterListUrl.base64_decode("JyBcCiAgLUggJ2F1dGhvcml0eTogZ29jdHJ1eWVudHJhbmh2dWk2LmNvbScgXAogIC1IICdhY2NlcHQ6IHRleHQvaHRtbCxhcHBsaWNhdGlvbi94aHRtbCt4bWwsYXBwbGljYXRpb24veG1sO3E9MC45LGltYWdlL2F2aWYsaW1hZ2Uvd2VicCxpbWFnZS9hcG5nLCovKjtxPTAuOCxhcHBsaWNhdGlvbi9zaWduZWQtZXhjaGFuZ2U7dj1iMztxPTAuNycgXAogIC1IICdhY2NlcHQtbGFuZ3VhZ2U6IGVuLVVTLGVuO3E9MC45LHZpO3E9MC44JyBcCiAgLUggJ2Nvb2tpZTogX2dhPUdBMS4xLjE3NDg5ODU5NzcuMTczMDQxOTY1MTsgVUdWeWMybHpkRk4wYjNKaFoyVT0lN0IlN0Q7IF9fUFBVX3B1aWQ9MTY2NTIwMDU5OTUyNzY2OTExNTU7IF9nYV9WMUZTWjRZRkpIPUdTMS4xLjE3MzI0MzQzODIuOS4xLjE3MzI0MzQ0MDUuMC4wLjA7IHVzaWQ9ODkwNURBQ0ExN0Y4MjA5QzNGNEFBQjZCNDQ3M0E1RkI7IF9fUFBVX3BwdWNudD0xMScgXAogIC1IICdyZWZlcmVyOiBodHRwczovL2dvY3RydXllbnRyYW5odnVpNi5jb20vdHJ1eWVuL3RhLWJpLWtldC1jdW5nLW1vdC1uZ2F5LTEwMDAtbmFtJyBcCiAgLUggJ3NlYy1jaC11YTogIk5vdC9BKUJyYW5kIjt2PSI5OSIsICJHb29nbGUgQ2hyb21lIjt2PSIxMTUiLCAiQ2hyb21pdW0iO3Y9IjExNSInIFwKICAtSCAnc2VjLWNoLXVhLW1vYmlsZTogPzAnIFwKICAtSCAnc2VjLWNoLXVhLXBsYXRmb3JtOiAibWFjT1MiJyBcCiAgLUggJ3NlYy1mZXRjaC1kZXN0OiBkb2N1bWVudCcgXAogIC1IICdzZWMtZmV0Y2gtbW9kZTogbmF2aWdhdGUnIFwKICAtSCAnc2VjLWZldGNoLXNpdGU6IHNhbWUtb3JpZ2luJyBcCiAgLUggJ3VwZ3JhZGUtaW5zZWN1cmUtcmVxdWVzdHM6IDEnIFwKICAtSCAndXNlci1hZ2VudDogTW96aWxsYS81LjAgKE1hY2ludG9zaDsgSW50ZWwgTWFjIE9TIFggMTBfMTVfNykgQXBwbGVXZWJLaXQvNTM3LjM2IChLSFRNTCwgbGlrZSBHZWNrbykgQ2hyb21lLzExNS4wLjAuMCBTYWZhcmkvNTM3LjM2JyBcCiAgLS1jb21wcmVzc2Vk");

        $tracker->addActiveTask("api_fetch", "Đang gọi API lấy danh sách chapter");

        $response = shell_exec($datasave);
        $responseData = json_decode($response, true);

        if (!$this->isValidChapterResponse($responseData)) {
            throw new Exception("Invalid API response");
        }

        $chapters = $responseData['result']['chapters'];
        $totalChapters = count($chapters);
        $tracker->addChapters($totalChapters);

        foreach ($chapters as $chapter) {
            $this->processChapter($chapter, $comicId, $nameEn, $tracker);
        }

    } catch (Exception $e) {
        $tracker->addError("Lỗi lấy chapters cho manga {$nameEn}: " . $e->getMessage());
    } finally {
        $tracker->removeActiveTask($taskId);
    }
}

function isValidChapterResponse($response) {
    return $response && 
           isset($response['status']) && 
           $response['status'] && 
           $response['code'] == 200 &&
           isset($response['result']['chapters']) && 
           is_array($response['result']['chapters']);
}

public function processChapter($chapter, $comicId, $nameEn, $tracker) {
    $chapterId = $chapter['id'];
    $title = $chapter['numberChapter'];
    $created_at = $this->convertDate($chapter['stringUpdateTime']);
    $taskId = "process_chapter_{$chapterId}";

    try {
        $tracker->addActiveTask($taskId, "Đang xử lý chapter {$title}");

        // Kiểm tra chapter tồn tại
        if ($this->shouldProcessChapter($chapterId, $nameEn, $title)) {
            // Update chapter info
            $this->updateChapterInfo($chapterId, $comicId, $title, $created_at);
            
            // Lấy ảnh từ API
            $images = $this->fetchChapterImages($comicId, $title, $nameEn, $chapterId, $tracker);
            
            if (empty($images)) {
                // Thử lấy ảnh từ HTML nếu API thất bại
                $images = $this->fetchChapterImagesFromHtml($nameEn, $title, $chapterId, $tracker);
            }

            if (!empty($images)) {
                $this->updateChapterImages($chapterId, $images);
                $tracker->incrementProcessedChapters();
            }
        } else {
            $tracker->incrementProcessedChapters();
        }

    } catch (Exception $e) {
        $tracker->addError("Lỗi xử lý chapter {$title}: " . $e->getMessage());
    } finally {
        $tracker->removeActiveTask($taskId);
    }
}

function shouldProcessChapter($chapterId, $nameEn, $title) {
    $stmt = $this->db->prepare("SELECT id, images FROM chapters WHERE id = ?");
    $stmt->bind_param("i", $chapterId);
    $stmt->execute();
    $result = $stmt->get_result();
    $existingChapter = $result->fetch_assoc();

    return !$existingChapter || 
           $this->isImagesEmptyOrNull($existingChapter['images'], $nameEn, $title, $chapterId);
}

function updateChapterInfo($chapterId, $comicId, $title, $created_at) {
    $query = "INSERT INTO chapters (id, manga_id, title, chapter_number, views, created_at, content_type)
              VALUES (?, ?, ?, ?, 0, ?, 'manga')
              ON DUPLICATE KEY UPDATE
              manga_id=VALUES(manga_id),
              title=VALUES(title),
              chapter_number=VALUES(chapter_number),
              created_at=VALUES(created_at)";
              
    $stmt = $this->db->prepare($query);
    $stmt->bind_param("iisss", $chapterId, $comicId, $title, $title, $created_at);
    $stmt->execute();
}

function fetchChapterImages($comicId, $title, $nameEn, $chapterId, $tracker) {
    $taskId = "fetch_api_images_{$chapterId}";
    $tracker->addActiveTask($taskId, "Đang lấy ảnh từ API cho chapter {$title}");

    try {
        // Gọi API lấy ảnh chapter
        $chapterUrl = 'https://goctruyentranhvui7.com/api/chapter/auth';
        $postData = "comicId={$comicId}&chapterNumber={$title}&nameEn={$nameEn}";
        $datasave = base64_decode("Y3VybCAtTCAtcyAn");
        $datasave .= $chapterUrl.base64_decode("JyBcCiAgLUggJ2F1dGhvcml0eTogZ29jdHJ1eWVudHJhbmh2dWk3LmNvbScgXAogIC1IICdhY2NlcHQ6IGFwcGxpY2F0aW9uL2pzb24sIHRleHQvamF2YXNjcmlwdCwgKi8qOyBxPTAuMDEnIFwKICAtSCAnYWNjZXB0LWxhbmd1YWdlOiBlbi1VUyxlbjtxPTAuOSx2aTtxPTAuOCcgXAogIC1IICdhdXRob3JpemF0aW9uOiBCZWFyZXIgZXlKaGJHY2lPaUpJVXpVeE1pSjkuZXlKemRXSWlPaUpLYjJVZ1RtZDFlV1Z1SWl3aVkyOXRhV05KWkhNaU9sdGRMQ0p5YjJ4bFNXUWlPbTUxYkd3c0ltZHliM1Z3U1dRaU9tNTFiR3dzSW1Ga2JXbHVJanBtWVd4elpTd2ljbUZ1YXlJNk1Dd2ljR1Z5YldsemMybHZiaUk2VzEwc0ltbGtJam9pTURBd01EWXdORE0wTlNJc0luUmxZVzBpT21aaGJITmxMQ0pwWVhRaU9qRTNNek0wTmpJNE1EY3NJbVZ0WVdsc0lqb2liblZzYkNKOS4xc1g5cEszMDdNb0R1akdqRnJsdzNVdVNmRU11UndURTQ3bU1nU1I0WWo2YXpyTENnWUc3a1plNzQ4bUNkbnI2S093NDdYc3NBdXNId3lIeVNpb3hmZycgXAogIC1IICdjb250ZW50LXR5cGU6IGFwcGxpY2F0aW9uL3gtd3d3LWZvcm0tdXJsZW5jb2RlZDsgY2hhcnNldD1VVEYtOCcgXAogIC1IICdjb29raWU6IF9nYT1HQTEuMS4yNDk4MjM5ODIuMTczMTcwNjgyODsgVUdWeWMybHpkRk4wYjNKaFoyVT0lN0IlN0Q7IGNmX2NsZWFyYW5jZT1EN3J6dkVHVDU2VF9STEV6UGowbmlNYnN4bjdFMmRDTlFxc0dpSVNvWVc4LTE3MzM2NTE5MDgtMS4yLjEuMS05Rno5ZDJSNGRpRlVkTFpsVU41N25namlIcXZXTUd0Z21Gd29US2hETFZEdWFUcjBBbE8uR0txZEJEb0lGS2NjWDljNFN5R3FCc05ZQjQ4ZlBaM1hLa2xqUkQ2ZktvcHp2Z3E1RWNEZDBIX3Y0amdkVl9DU05KUnN5TzZWam4xSF9XcU1sNnpKT0ZZQ0JfcVRrbF90clg0dDhPQlRsUllzR1ZRdnFzWEFUMUhJTjAuVUFJVmRSVDZLUzh0VHZBaDUzWmhOSGw3amxvaHZLcllMWWxGQ1lLYnlQY1NlR2V3cE5WX2p2cGU5cVVHYW4zV3lNaGQ0ZHJ4bWZabjFyYlVwUkRVRDN5ZllFazNadm56TDd2Sm1lWE5COXcwR19nZFZMemZYYnJFM0d2TFRuYjJ0Rm9ISjZTdEphLnAuQ05MdS44dU1BdkZWUFlWZUlzcFlOWUFaXy5SZW1neWVhQ0hhXy5IWjBLaWhPNlV0VER5c0hycjN6Nll5Qnd5ZElEMDJsaU5YaUZZWjk3al9wRWs2MElXaVZfTEtDY1JhZ0w2cXNPOWltMS5jZXN4TWlOS3FjN3JtdGlYYWJ1a1dYdVBYOyB1c2lkPUU3NzVEOUNFNTI4NTU2OThCRjIzODBFRThBOUE1N0YzOyBfX1BQVV9wcHVjbnQ9MTsgX2dhX1YxRlNaNFlGSkg9R1MxLjEuMTczMzY1MTkwMi42LjEuMTczMzY1MjA0Mi4wLjAuMCcgXAogIC1IICdvcmlnaW46IGh0dHBzOi8vZ29jdHJ1eWVudHJhbmh2dWk3LmNvbScgXAogIC1IICdyZWZlcmVyOiBodHRwczovL2dvY3RydXllbnRyYW5odnVpNy5jb20vdHJ1eWVuL3RodWMtdGhpLWNvbmctbHkvY2h1b25nLTUnIFwKICAtSCAnc2VjLWNoLXVhOiAiTm90L0EpQnJhbmQiO3Y9Ijk5IiwgIkdvb2dsZSBDaHJvbWUiO3Y9IjExNSIsICJDaHJvbWl1bSI7dj0iMTE1IicgXAogIC1IICdzZWMtY2gtdWEtYXJjaDogIng4NiInIFwKICAtSCAnc2VjLWNoLXVhLWJpdG5lc3M6ICI2NCInIFwKICAtSCAnc2VjLWNoLXVhLWZ1bGwtdmVyc2lvbjogIjExNS4wLjU3OTAuMTcwIicgXAogIC1IICdzZWMtY2gtdWEtZnVsbC12ZXJzaW9uLWxpc3Q6ICJOb3QvQSlCcmFuZCI7dj0iOTkuMC4wLjAiLCAiR29vZ2xlIENocm9tZSI7dj0iMTE1LjAuNTc5MC4xNzAiLCAiQ2hyb21pdW0iO3Y9IjExNS4wLjU3OTAuMTcwIicgXAogIC1IICdzZWMtY2gtdWEtbW9iaWxlOiA/MCcgXAogIC1IICdzZWMtY2gtdWEtbW9kZWw6ICIiJyBcCiAgLUggJ3NlYy1jaC11YS1wbGF0Zm9ybTogIm1hY09TIicgXAogIC1IICdzZWMtY2gtdWEtcGxhdGZvcm0tdmVyc2lvbjogIjEzLjYuMyInIFwKICAtSCAnc2VjLWZldGNoLWRlc3Q6IGVtcHR5JyBcCiAgLUggJ3NlYy1mZXRjaC1tb2RlOiBjb3JzJyBcCiAgLUggJ3NlYy1mZXRjaC1zaXRlOiBzYW1lLW9yaWdpbicgXAogIC1IICd1c2VyLWFnZW50OiBNb3ppbGxhLzUuMCAoTWFjaW50b3NoOyBJbnRlbCBNYWMgT1MgWCAxMF8xNV83KSBBcHBsZVdlYktpdC81MzcuMzYgKEtIVE1MLCBsaWtlIEdlY2tvKSBDaHJvbWUvMTE1LjAuMC4wIFNhZmFyaS81MzcuMzYnIFwKICAtSCAneC1yZXF1ZXN0ZWQtd2l0aDogWE1MSHR0cFJlcXVlc3QnIFwKICAtLWRhdGEtcmF3ICc=");
        $datasave .= $postData.base64_decode("JyBcCiAgLS1jb21wcmVzc2Vk");

        $response = shell_exec($datasave);
        $chapterResponse = json_decode($response, true);

        if (!$this->isValidImageResponse($chapterResponse)) {
            return [];
        }

        $imageUrls = $chapterResponse['result']['data'];
        $tracker->addImages(count($imageUrls));

        // Lấy session key cho Naver
        $sessionKey = getNaverSessionKey();


        if (!$sessionKey) {
            throw new Exception("Không thể lấy session key");
        }

        // Process và upload ảnh
        return $this->processAndUploadImages(
            $imageUrls,
            $nameEn,
            $title,
            $chapterId,
            $sessionKey,
            $tracker
        );

    } catch (Exception $e) {
        $tracker->addError("Lỗi lấy ảnh API cho chapter {$title}: " . $e->getMessage());
        return [];
    } finally {
        $tracker->removeActiveTask($taskId);
    }
}

function fetchChapterImagesFromHtml($nameEn, $title, $chapterId, $tracker) {
    $taskId = "fetch_html_images_{$chapterId}";
    $tracker->addActiveTask($taskId, "Đang lấy ảnh từ HTML cho chapter {$title}");

    try {
        $chapterUrl = "https://goctruyentranhvui7.com/truyen/{$nameEn}/chuong-{$title}";
        $imageUrls = $this->fetchImagesFromHtml($chapterUrl);

        if (empty($imageUrls)) {
            $tracker->addError("Không tìm thấy ảnh từ HTML cho chapter {$title}");
            return [];
        }

        $tracker->addImages(count($imageUrls));

        // Lấy session key cho Naver
        $sessionKey = $this->getNaverSessionKey();
        if (!$sessionKey) {
            throw new Exception("Không thể lấy session key");
        }

        // Process và upload ảnh
        return $this->processAndUploadImages(
            $imageUrls,
            $nameEn,
            $title,
            $chapterId,
            $sessionKey,
            $tracker
        );

    } catch (Exception $e) {
        $tracker->addError("Lỗi lấy ảnh HTML cho chapter {$title}: " . $e->getMessage());
        return [];
    } finally {
        $tracker->removeActiveTask($taskId);
    }
}

function processAndUploadImages($imageUrls, $nameEn, $title, $chapterId, $sessionKey, $tracker) {
    $taskId = "process_images_{$chapterId}";
    $tracker->addActiveTask($taskId, "Đang xử lý ảnh cho chapter {$title}");

    try {
        $fileDetails = [];
        foreach ($imageUrls as $imgIndex => $imageUrl) {
            $s3Path = "s2truyen/truyen-tranh/{$nameEn}/{$title}/{$nameEn}-{$title}-" . ($imgIndex + 1) . ".jpg";
            $localPath = __DIR__ . '/downloads/' . basename($s3Path);
            $fileDetails[$imageUrl] = $localPath;
        }

        // Download ảnh
        $downloadResults = $this->downloadFiles($fileDetails); 



        // Upload lên Naver
        $successFiles = [];
        foreach ($fileDetails as $localPath) {
            if (file_exists($localPath) && filesize($localPath) > 0) {
                $successFiles[] = $localPath;
            }
        }

        if (!empty($successFiles)) {
            $uploadResults = $this->uploadBatchToNaverWithSession($successFiles, $sessionKey);
            if ($uploadResults['success']) {
                $updatedImages = [];
                foreach ($uploadResults['urls'] as $url) {
                    if (strpos($url, 'blogfiles.pstatic.net') !== false) {
                        $updatedImages[] = ['url' => $url];
                        $tracker->incrementProcessedImages();
                    }
                }
                return $updatedImages;
            }
        }

        return [];

    } catch (Exception $e) {
        $tracker->addError("Lỗi xử lý ảnh chapter {$title}: " . $e->getMessage());
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

function isValidImageResponse($response) {
    return $response && 
           isset($response['status']) && 
           $response['status'] && 
           $response['code'] == 200 &&
           isset($response['result']['data']) && 
           !empty($response['result']['data']);
        }
    }
