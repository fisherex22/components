<?php
namespace Manga;
use ProgressTracker;
require_once '/Users/binblacker/sg-4241725359072075-main/vendor/autoload.php';
use Symfony\Component\DomCrawler\Crawler;


trait FetchImagesFromHtmls {
    protected function fetchImagesFromHtml($url, $maxRetries = 3, $delay = 5) {
    $tracker = ProgressTracker::getInstance();
    $taskId = "fetch_html_" . md5($url);
    $tracker->addActiveTask($taskId, "Đang lấy ảnh từ HTML: " . basename($url));

    try {
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $attemptTaskId = "{$taskId}_attempt_{$attempt}";
                $tracker->addActiveTask($attemptTaskId, 
                    "Lần thử {$attempt}/{$maxRetries} lấy ảnh HTML");

                $datasave = base64_decode("Y3VybCAtTCAtcyAn");
                $datasave .= $url.base64_decode("JyBcCiAgLUggJ2F1dGhvcml0eTogZ29jdHJ1eWVudHJhbmh2dWk3LmNvbScgXAogIC1IICdhY2NlcHQ6IHRleHQvaHRtbCxhcHBsaWNhdGlvbi94aHRtbCt4bWwsYXBwbGljYXRpb24veG1sO3E9MC45LGltYWdlL2F2aWYsaW1hZ2Uvd2VicCxpbWFnZS9hcG5nLCovKjtxPTAuOCxhcHBsaWNhdGlvbi9zaWduZWQtZXhjaGFuZ2U7dj1iMztxPTAuNycgXAogIC1IICdhY2NlcHQtbGFuZ3VhZ2U6IGVuLVVTLGVuO3E9MC45LHZpO3E9MC44JyBcCiAgLUggJ2Nvb2tpZTogX2dhPUdBMS4xLjI0OTgyMzk4Mi4xNzMxNzA2ODI4OyBVR1Z5YzJsemRGTjBiM0poWjJVPSU3QiU3RDsgY2ZfY2xlYXJhbmNlPUQ3cnp2RUdUNTZUX1JMRXpQajBuaU1ic3huN0UyZENOUXFzR2lJU29ZVzgtMTczMzY1MTkwOC0xLjIuMS4xLTlGejlkMlI0ZGlGVWRMWmxVTjU3bmdqaUhxdldNR3RnbUZ3b1RLaERMVkR1YVRyMEFsTy5HS3FkQkRvSUZLY2NYOWM0U3lHcUJzTllCNDhmUFozWEtrbGpSRDZmS29wenZncTVFY0RkMEhfdjRqZ2RWX0NTTkpSc3lPNlZqbjFIX1dxTWw2ekpPRllDQl9xVGtsX3RyWDR0OE9CVGxSWXNHVlF2cXNYQVQxSElOMC5VQUlWZFJUNktTOHRUdkFoNTNaaE5IbDdqbG9odktyWUxZbEZDWUtieVBjU2VHZXdwTlZfanZwZTlxVUdhbjNXeU1oZDRkcnhtZlpuMXJiVXBSRFVEM3lmWUVrM1p2bnpMN3ZKbWVYTkI5dzBHX2dkVkx6ZlhickUzR3ZMVG5iMnRGb0hKNlN0SmEucC5DTkx1Ljh1TUF2RlZQWVZlSXNwWU5ZQVpfLlJlbWd5ZWFDSGFfLkhaMEtpaE82VXRURHlzSHJyM3o2WXlCd3lkSUQwMmxpTlhpRllaOTdqX3BFazYwSVdpVl9MS0NjUmFnTDZxc085aW0xLmNlc3hNaU5LcWM3cm10aVhhYnVrV1h1UFg7IF9nYV9WMUZTWjRZRkpIPUdTMS4xLjE3MzM2NTE5MDIuNi4xLjE3MzM2NTE5MzkuMC4wLjA7IHVzaWQ9QUVDNTVEQjZCMTcwQTVCQTNFQ0UxRDlGNEQ2OEEwRjcnIFwKICAtSCAnc2VjLWNoLXVhOiAiTm90L0EpQnJhbmQiO3Y9Ijk5IiwgIkdvb2dsZSBDaHJvbWUiO3Y9IjExNSIsICJDaHJvbWl1bSI7dj0iMTE1IicgXAogIC1IICdzZWMtY2gtdWEtYXJjaDogIng4NiInIFwKICAtSCAnc2VjLWNoLXVhLWJpdG5lc3M6ICI2NCInIFwKICAtSCAnc2VjLWNoLXVhLWZ1bGwtdmVyc2lvbjogIjExNS4wLjU3OTAuMTcwIicgXAogIC1IICdzZWMtY2gtdWEtZnVsbC12ZXJzaW9uLWxpc3Q6ICJOb3QvQSlCcmFuZCI7dj0iOTkuMC4wLjAiLCAiR29vZ2xlIENocm9tZSI7dj0iMTE1LjAuNTc5MC4xNzAiLCAiQ2hyb21pdW0iO3Y9IjExNS4wLjU3OTAuMTcwIicgXAogIC1IICdzZWMtY2gtdWEtbW9iaWxlOiA/MCcgXAogIC1IICdzZWMtY2gtdWEtbW9kZWw6ICIiJyBcCiAgLUggJ3NlYy1jaC11YS1wbGF0Zm9ybTogIm1hY09TIicgXAogIC1IICdzZWMtY2gtdWEtcGxhdGZvcm0tdmVyc2lvbjogIjEzLjYuMyInIFwKICAtSCAnc2VjLWZldGNoLWRlc3Q6IGRvY3VtZW50JyBcCiAgLUggJ3NlYy1mZXRjaC1tb2RlOiBuYXZpZ2F0ZScgXAogIC1IICdzZWMtZmV0Y2gtc2l0ZTogbm9uZScgXAogIC1IICdzZWMtZmV0Y2gtdXNlcjogPzEnIFwKICAtSCAndXBncmFkZS1pbnNlY3VyZS1yZXF1ZXN0czogMScgXAogIC1IICd1c2VyLWFnZW50OiBNb3ppbGxhLzUuMCAoTWFjaW50b3NoOyBJbnRlbCBNYWMgT1MgWCAxMF8xNV83KSBBcHBsZVdlYktpdC81MzcuMzYgKEtIVE1MLCBsaWtlIEdlY2tvKSBDaHJvbWUvMTE1LjAuMC4wIFNhZmFyaS81MzcuMzYnIFwKICAtLWNvbXByZXNzZWQ=");
                
                $html = shell_exec($datasave);
                
                $crawler = new Crawler($html);
                $imageUrls = $crawler->filter('div.image-section div.img-block img.image')
                    ->each(function (Crawler $node) {
                        return $node->attr('src');
                    });

                if (!empty($imageUrls)) {
                    $tracker->addImages(count($imageUrls));
                    return $imageUrls;
                }

                $tracker->addError("Không tìm thấy ảnh trong HTML");
                
            } catch (Exception $e) {
                $tracker->addError("Lỗi lấy ảnh HTML (Lần {$attempt}): " . $e->getMessage());
                
                if ($attempt < $maxRetries) {
                    $delayTaskId = "{$taskId}_delay_{$attempt}";
                    $tracker->addActiveTask($delayTaskId, 
                        "Đợi {$delay}s trước lần thử tiếp theo");
                    sleep($delay);
                    $tracker->removeActiveTask($delayTaskId);
                }
            } finally {
                $tracker->removeActiveTask($attemptTaskId);
            }
        }
        return [];
    } finally {
        $tracker->removeActiveTask($taskId);
        }   
    }
}

trait FetchCategoriesFromHtml {
    function fetchCategoriesFromHtml($url) {
    $tracker = ProgressTracker::getInstance();
    $taskId = "fetch_categories_" . md5($url);
    $tracker->addActiveTask($taskId, "Đang lấy categories từ HTML");

    try {
        $datasave = base64_decode("Y3VybCAtTCAtcyAn");
        $datasave .= $url.base64_decode("JyBcCiAgLUggJ2F1dGhvcml0eTogZ29jdHJ1eWVudHJhbmh2dWk3LmNvbScgXAogIC1IICdhY2NlcHQ6IHRleHQvaHRtbCxhcHBsaWNhdGlvbi94aHRtbCt4bWwsYXBwbGljYXRpb24veG1sO3E9MC45LGltYWdlL2F2aWYsaW1hZ2Uvd2VicCxpbWFnZS9hcG5nLCovKjtxPTAuOCxhcHBsaWNhdGlvbi9zaWduZWQtZXhjaGFuZ2U7dj1iMztxPTAuNycgXAogIC1IICdhY2NlcHQtbGFuZ3VhZ2U6IGVuLVVTLGVuO3E9MC45LHZpO3E9MC44JyBcCiAgLUggJ2Nvb2tpZTogX2dhPUdBMS4xLjI0OTgyMzk4Mi4xNzMxNzA2ODI4OyBVR1Z5YzJsemRGTjBiM0poWjJVPSU3QiU3RDsgY2ZfY2xlYXJhbmNlPUQ3cnp2RUdUNTZUX1JMRXpQajBuaU1ic3huN0UyZENOUXFzR2lJU29ZVzgtMTczMzY1MTkwOC0xLjIuMS4xLTlGejlkMlI0ZGlGVWRMWmxVTjU3bmdqaUhxdldNR3RnbUZ3b1RLaERMVkR1YVRyMEFsTy5HS3FkQkRvSUZLY2NYOWM0U3lHcUJzTllCNDhmUFozWEtrbGpSRDZmS29wenZncTVFY0RkMEhfdjRqZ2RWX0NTTkpSc3lPNlZqbjFIX1dxTWw2ekpPRllDQl9xVGtsX3RyWDR0OE9CVGxSWXNHVlF2cXNYQVQxSElOMC5VQUlWZFJUNktTOHRUdkFoNTNaaE5IbDdqbG9odktyWUxZbEZDWUtieVBjU2VHZXdwTlZfanZwZTlxVUdhbjNXeU1oZDRkcnhtZlpuMXJiVXBSRFVEM3lmWUVrM1p2bnpMN3ZKbWVYTkI5dzBHX2dkVkx6ZlhickUzR3ZMVG5iMnRGb0hKNlN0SmEucC5DTkx1Ljh1TUF2RlZQWVZlSXNwWU5ZQVpfLlJlbWd5ZWFDSGFfLkhaMEtpaE82VXRURHlzSHJyM3o2WXlCd3lkSUQwMmxpTlhpRllaOTdqX3BFazYwSVdpVl9MS0NjUmFnTDZxc085aW0xLmNlc3hNaU5LcWM3cm10aVhhYnVrV1h1UFg7IF9nYV9WMUZTWjRZRkpIPUdTMS4xLjE3MzM2NTE5MDIuNi4xLjE3MzM2NTE5MzkuMC4wLjA7IHVzaWQ9QUVDNTVEQjZCMTcwQTVCQTNFQ0UxRDlGNEQ2OEEwRjcnIFwKICAtSCAnc2VjLWNoLXVhOiAiTm90L0EpQnJhbmQiO3Y9Ijk5IiwgIkdvb2dsZSBDaHJvbWUiO3Y9IjExNSIsICJDaHJvbWl1bSI7dj0iMTE1IicgXAogIC1IICdzZWMtY2gtdWEtYXJjaDogIng4NiInIFwKICAtSCAnc2VjLWNoLXVhLWJpdG5lc3M6ICI2NCInIFwKICAtSCAnc2VjLWNoLXVhLWZ1bGwtdmVyc2lvbjogIjExNS4wLjU3OTAuMTcwIicgXAogIC1IICdzZWMtY2gtdWEtZnVsbC12ZXJzaW9uLWxpc3Q6ICJOb3QvQSlCcmFuZCI7dj0iOTkuMC4wLjAiLCAiR29vZ2xlIENocm9tZSI7dj0iMTE1LjAuNTc5MC4xNzAiLCAiQ2hyb21pdW0iO3Y9IjExNS4wLjU3OTAuMTcwIicgXAogIC1IICdzZWMtY2gtdWEtbW9iaWxlOiA/MCcgXAogIC1IICdzZWMtY2gtdWEtbW9kZWw6ICIiJyBcCiAgLUggJ3NlYy1jaC11YS1wbGF0Zm9ybTogIm1hY09TIicgXAogIC1IICdzZWMtY2gtdWEtcGxhdGZvcm0tdmVyc2lvbjogIjEzLjYuMyInIFwKICAtSCAnc2VjLWZldGNoLWRlc3Q6IGRvY3VtZW50JyBcCiAgLUggJ3NlYy1mZXRjaC1tb2RlOiBuYXZpZ2F0ZScgXAogIC1IICdzZWMtZmV0Y2gtc2l0ZTogbm9uZScgXAogIC1IICdzZWMtZmV0Y2gtdXNlcjogPzEnIFwKICAtSCAndXBncmFkZS1pbnNlY3VyZS1yZXF1ZXN0czogMScgXAogIC1IICd1c2VyLWFnZW50OiBNb3ppbGxhLzUuMCAoTWFjaW50b3NoOyBJbnRlbCBNYWMgT1MgWCAxMF8xNV83KSBBcHBsZVdlYktpdC81MzcuMzYgKEtIVE1MLCBsaWtlIEdlY2tvKSBDaHJvbWUvMTE1LjAuMC4wIFNhZmFyaS81MzcuMzYnIFwKICAtLWNvbXByZXNzZWQ=");

        $html = shell_exec($datasave);
        if (!$html) {
            $tracker->addError("Không lấy được HTML cho categories");
            return [];
        }

        $crawler = new Crawler($html);
        $categories = $crawler->filter('.group-content-wrap .group-content a.v-chip')
            ->each(function (Crawler $node) {
                return [
                    'name' => trim($node->filter('span')->last()->text()),
                    'slug' => $node->attr('href') ? explode('=', $node->attr('href'))[1] : null
                ];
            });

        if (!empty($categories)) {
            $tracker->addActiveTask("{$taskId}_found", 
                "Đã tìm thấy " . count($categories) . " categories");
        } else {
            $tracker->addError("Không tìm thấy categories trong HTML");
        }

        return $categories;
    } catch (Exception $e) {
        $tracker->addError("Lỗi lấy categories: " . $e->getMessage());
        return [];
    } finally {
        $tracker->removeActiveTask($taskId);
    }
}
}
trait SaveManga {
    function saveManga() {
    $tracker = ProgressTracker::getInstance();
    $id = $this->truyen['id'];
    $taskId = "save_manga_{$id}";
    $tracker->addActiveTask($taskId, "Đang lưu manga: " . $this->truyen['name']);

    try {
        $title = $this->truyen['name'];
        $author = $this->truyen['author'];
        $description = $this->truyen['description'];
        $cover_image = $this->truyen['photo'];
        $slugnamecomic = $this->truyen['nameEn'];
        $mangaUrl = "https://goctruyentranhvui7.com/truyen/{$slugnamecomic}";

        if (!$this->comicExists($id)) {
            $tracker->addActiveTask("{$taskId}_cover", "Đang xử lý ảnh bìa");
            
            $s3Path = "s2truyen/truyen-tranh/{$slugnamecomic}/{$slugnamecomic}.jpg";
            $newLinkAnh = $this->uploadToS3($cover_image, $s3Path, null);

            if ($newLinkAnh) {
                $tracker->addActiveTask("{$taskId}_insert", "Đang lưu thông tin manga vào database");
                
                $query = "INSERT INTO manga (...) VALUES (...)";
                $params = [$id, $title, $author, $description, $newLinkAnh, $mangaUrl, $slugnamecomic];
                $this->executeWithRetry($query, $params);
                
                $tracker->incrementProcessedManga();
                $tracker->removeActiveTask("{$taskId}_insert");
            } else {
                $tracker->addError("Lỗi upload ảnh bìa cho manga: {$title}");
            }
            $tracker->removeActiveTask("{$taskId}_cover");
        } else {
            $tracker->addActiveTask("{$taskId}_update", "Đang cập nhật URL manga");
            
            $query = "UPDATE manga SET url = ? WHERE id = ?";
            $params = [$mangaUrl, $id];
            $this->executeWithRetry($query, $params);
            
            $tracker->incrementProcessedManga();
            $tracker->removeActiveTask("{$taskId}_update");
        }

        return $mangaUrl;
    } catch (Exception $e) {
        $tracker->addError("Lỗi lưu manga {$title}: " . $e->getMessage());
        return null;
    } finally {
        $tracker->removeActiveTask($taskId);
    }
}
}

trait SaveImage {
    function saveImage($chapId, $imageUrl) {
    $tracker = ProgressTracker::getInstance();
    $taskId = "save_image_{$chapId}_" . md5($imageUrl);
    $tracker->addActiveTask($taskId, "Đang lưu ảnh cho chapter {$chapId}");

    try {
        $query = "SELECT images FROM chapters WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $chapId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        $images = $this->processExistingImages($row);
        $images[] = ['url' => $imageUrl];
        
        $imagesJson = json_encode($images, JSON_UNESCAPED_SLASHES);
        
        $updateQuery = "UPDATE chapters SET images = ? WHERE id = ?";
        $stmt = $this->db->prepare($updateQuery);
        $stmt->bind_param("si", $imagesJson, $chapId);
        
        if ($stmt->execute()) {
            $tracker->incrementProcessedImages();
            return true;
        }
        
        $tracker->addError("Lỗi cập nhật ảnh cho chapter {$chapId}");
        return false;

    } catch (Exception $e) {
        $tracker->addError("Lỗi lưu ảnh chapter {$chapId}: " . $e->getMessage());
        return false;
    } finally {
        $tracker->removeActiveTask($taskId);
    }
}
}

trait ProcessExistingImages { 
    function processExistingImages($row) {
    if (!$row || !$row['images']) {
        return [];
    }

    $images = json_decode($row['images'], true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [];
    }

    if (isset($images[0]) && is_string($images[0])) {
        return array_map(function($url) {
            return ['url' => $url];
        }, $images);
    }

    return $images;
    }
}
