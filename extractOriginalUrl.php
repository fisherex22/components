<?php

function extractOriginalUrl($imageUrl) {
    $tracker = ProgressTracker::getInstance();
    $taskId = "extract_url_" . md5($imageUrl);
    $tracker->addActiveTask($taskId, "Đang trích xuất URL gốc: " . basename($imageUrl));

    try {
        // Extract parameters from current URL
        if (preg_match('/s2truyen\/truyen-tranh\/(.*?)\/(.*?)\//', $imageUrl, $matches)) {
            $nameEn = $matches[1];
            $chapterNumber = $matches[2];
            
            $tracker->addActiveTask($taskId . "_db", "Đang truy vấn manga ID cho $nameEn");
            
            // Get manga_id from database
            $query = "SELECT id FROM manga WHERE slug = ?";
            $stmt = $this->db->prepare($query);
            if ($stmt === false) {
                $tracker->addError("Failed to prepare statement for $nameEn: " . $this->db->error);
                return null;
            }

            if (!$stmt->bind_param("s", $nameEn)) {
                $tracker->addError("Failed to bind parameters for $nameEn: " . $stmt->error);
                $stmt->close();
                return null;
            }

            if (!$stmt->execute()) {
                $tracker->addError("Failed to execute statement for $nameEn: " . $stmt->error);
                $stmt->close();
                return null;
            }

            $result = $stmt->get_result();
            if (!$result) {
                $tracker->addError("Failed to get result for $nameEn: " . $stmt->error);
                $stmt->close();
                return null;
            }

            $row = $result->fetch_assoc();
            $stmt->close();
            $tracker->removeActiveTask($taskId . "_db");
            
            if ($row) {
                $comicId = $row['id'];
                $tracker->addActiveTask($taskId . "_api", 
                    "Đang lấy dữ liệu chapter $chapterNumber của manga $nameEn");

                // Call API to get original URLs
                $chapterUrl = 'https://goctruyentranhvui7.com/api/chapter/auth';
                $postData = "comicId={$comicId}&chapterNumber={$chapterNumber}&nameEn={$nameEn}";
                
                $datasave = base64_decode("Y3VybCAtTCAtcyAn");
                $datasave .= $chapterUrl.base64_decode("JyBcCiAgLUggJ2F1dGhvcml0eTogZ29jdHJ1eWVudHJhbmh2dWk2LmNvbScgXAogIC1IICdhY2NlcHQ6IGFwcGxpY2F0aW9uL2pzb24sIHRleHQvamF2YXNjcmlwdCwgKi8qOyBxPTAuMDEnIFwKICAtSCAnYWNjZXB0LWxhbmd1YWdlOiBlbi1VUyxlbjtxPTAuOSx2aTtxPTAuOCcgXAogIC1IICdhdXRob3JpemF0aW9uOiBCZWFyZXIgZXlKaGJHY2lPaUpJVXpVeE1pSjkuZXlKemRXSWlPaUpLYjJVZ1RtZDFlV1Z1SWl3aVkyOXRhV05KWkhNaU9sdGRMQ0p5YjJ4bFNXUWlPbTUxYkd3c0ltZHliM1Z3U1dRaU9tNTFiR3dzSW1Ga2JXbHVJanBtWVd4elpTd2ljbUZ1YXlJNk1Dd2ljR1Z5YldsemMybHZiaUk2VzEwc0ltbGtJam9pTURBd01EWXdORE0wTlNJc0luUmxZVzBpT21aaGJITmxMQ0pwWVhRaU9qRTNNekEwTVRrM016VXNJbVZ0WVdsc0lqb2liblZzYkNKOS5PYUliOGh1VEt6eGM3ZjZXMEJXMU1zSEMxSHdOb1FITWNWMVdkUU9iREJWMElEdlZhZXYwMFFjNEhkdFdLSWxVX3V6LVJLZ241VUdDNThuUkRoQzVVQScgXAogIC1IICdjb250ZW50LXR5cGU6IGFwcGxpY2F0aW9uL3gtd3d3LWZvcm0tdXJsZW5jb2RlZDsgY2hhcnNldD1VVEYtOCcgXAogIC1IICdjb29raWU6IF9nYT1HQTEuMS4xNzQ4OTg1OTc3LjE3MzA0MTk2NTE7IFVHVnljMmx6ZEZOMGIzSmhaMlU9JTdCJTdEOyB1c2lkPUQ5QzEwQUQwRUY2MTMwRURCMEI1NDAxQTg0QzVEQTVCOyBfX1BQVV9wcHVjbnQ9NjsgX2dhX1YxRlNaNFlGSkg9R1MxLjEuMTczMTQ5ODcyMS40LjEuMTczMTQ5ODk1NS4wLjAuMCcgXAogIC1IICdvcmlnaW46IGh0dHBzOi8vZ29jdHJ1eWVudHJhbmh2dWk2LmNvbScgXAogIC1IICdyZWZlcmVyOiBodHRwczovL2dvY3RydXllbnRyYW5odnVpNi5jb20vdHJ1eWVuL2NhY2gtc29uZy1uaHUtbW90LWtlLXBoYW4tZGllbi9jaHVvbmctMTU3JyBcCiAgLUggJ3NlYy1jaC11YTogIk5vdC9BKUJyYW5kIjt2PSI5OSIsICJHb29nbGUgQ2hyb21lIjt2PSIxMTUiLCAiQ2hyb21pdW0iO3Y9IjExNSInIFwKICAtSCAnc2VjLWNoLXVhLW1vYmlsZTogPzAnIFwKICAtSCAnc2VjLWNoLXVhLXBsYXRmb3JtOiAibWFjT1MiJyBcCiAgLUggJ3NlYy1mZXRjaC1kZXN0OiBlbXB0eScgXAogIC1IICdzZWMtZmV0Y2gtbW9kZTogY29ycycgXAogIC1IICdzZWMtZmV0Y2gtc2l0ZTogc2FtZS1vcmlnaW4nIFwKICAtSCAndXNlci1hZ2VudDogTW96aWxsYS81LjAgKE1hY2ludG9zaDsgSW50ZWwgTWFjIE9TIFggMTBfMTVfNykgQXBwbGVXZWJLaXQvNTM3LjM2IChLSFRNTCwgbGlrZSBHZWNrbykgQ2hyb21lLzExNS4wLjAuMCBTYWZhcmkvNTM3LjM2JyBcCiAgLUggJ3gtcmVxdWVzdGVkLXdpdGg6IFhNTEh0dHBSZXF1ZXN0JyBcCiAgLS1kYXRhLXJhdyAn");
                $datasave .= $postData.base64_decode("JyBcCiAgLS1jb21wcmVzc2Vk");
                
                $response = shell_exec($datasave);
                
                if (!$response) {
                    $tracker->addError("API request failed for chapter $chapterNumber of $nameEn");
                    return null;
                }
                
                $responseData = json_decode($response, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $tracker->addError("Failed to parse API response for $nameEn chapter $chapterNumber: " . json_last_error_msg());
                    return null;
                }
                
                if ($responseData && isset($responseData['result']['data'])) {
                    // Extract index from current URL
                    if (preg_match('/-(\d+)\.jpg$/', $imageUrl, $indexMatch)) {
                        $index = (int)$indexMatch[1] - 1;
                        if (isset($responseData['result']['data'][$index])) {
                            $tracker->removeActiveTask($taskId . "_api");
                            return $responseData['result']['data'][$index];
                        }
                        $tracker->addError("Image index $index not found in API response for chapter $chapterNumber");
                    } else {
                        $tracker->addError("Failed to extract index from URL: $imageUrl");
                    }
                } else {
                    $tracker->addError("Invalid API response structure for chapter $chapterNumber");
                }
                $tracker->removeActiveTask($taskId . "_api");
            } else {
                $tracker->addError("No manga found for nameEn: $nameEn");
            }
        } else {
            $tracker->addError("Failed to extract manga info from URL: $imageUrl");
        }
    } catch (Exception $e) {
        $tracker->addError("Error extracting original URL: " . $e->getMessage());
    } finally {
        $tracker->removeActiveTask($taskId);
    }
    
    return null;
}