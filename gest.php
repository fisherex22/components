<?php
require_once '/Users/binblacker/sg-4241725359072075-main/vendor/autoload.php';
//require_once 'vendor/autoload.php';
require_once 'ProgressTracker.php';

use Aws\S3\S3Client;
use Aws\Common\Credentials\Credentials;
use Aws\Credentials\Credentials as AwsCredentials;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DomCrawler\Crawler;

// Bao gồm file chứa lớp config
include_once 'down.php';
include_once 'CommentCrawler.php';

function fetch_page_data($url) {
    $datasave = base64_decode("Y3VybCAtTCAtcyAn");
    $datasave .= $url.base64_decode("JyBcCiAgLUggJ2F1dGhvcml0eTogZ29jdHJ1eWVudHJhbmh2dWk2LmNvbScgXAogIC1IICdhY2NlcHQ6IHRleHQvaHRtbCxhcHBsaWNhdGlvbi94aHRtbCt4bWwsYXBwbGljYXRpb24veG1sO3E9MC45LGltYWdlL2F2aWYsaW1hZ2Uvd2VicCxpbWFnZS9hcG5nLCovKjtxPTAuOCxhcHBsaWNhdGlvbi9zaWduZWQtZXhjaGFuZ2U7dj1iMztxPTAuNycgXAogIC1IICdhY2NlcHQtbGFuZ3VhZ2U6IGVuLVVTLGVuO3E9MC45LHZpO3E9MC44JyBcCiAgLUggJ2Nvb2tpZTogX2dhPUdBMS4xLjE3NDg5ODU5NzcuMTczMDQxOTY1MTsgVUdWeWMybHpkRk4wYjNKaFoyVT0lN0IlN0Q7IF9fUFBVX3B1aWQ9MTY2NTIwMDU5OTUyNzY2OTExNTU7IF9nYV9WMUZTWjRZRkpIPUdTMS4xLjE3MzMzNDU2MzIuMTEuMC4xNzMzMzQ1NjMyLjAuMC4wOyBjZl9jbGVhcmFuY2U9YkxMY1g5WkZObGQxcXhKZFpHaHpLMkVHMDF3aFdhdmFKd011Ll8uVV8zay0xNzMzNzQzNDI0LTEuMi4xLjEtSnkzN0pvaElLNTd4ZW1GSnhrMnpBOUtLVGxzczYuOGJQTDdjN2RMeElpUTlycmdsa3BfUWZlNUttTWxOeEJ5dTl3STNYNlBoUHBWTi5hd0Y4ZmdsUGtELm8wSWZfeXdDRzg5UzVDMFZKbWxCQVpTQnQ3UlliWS5oSG5SVkY3LmVpNzdSYkhrZWNpdnU0SVFiYzdLT1dUcHJWbVdmVnJUWmlfYzJmaF9mTDhqV2YuSXY0elhEY0NFenJBejRYVUlOdkl1ajVWQjZMRGtMSm55R1BjSGlxbmNYNExwbVM2em50dXFJRU1aWGRCMDlfUnFfQmFGaGU3bjFUZVZJZHdsR3dtcUdKMXZpdUlGTjR1LmZhYlBPLkNGYWVncFVhbFlYY0htYU8xVHNEaVFkOTc3T0JHZnh6Q09xR3hzMWNLNkNyVHl5OS5ETjJ4OGxRUGh5U3dKWTZucUZiQnd3aG5fWUhZWTNSUVVYZDV0dFFjLjZROGhGWjFyeDhRMWlMYXg5VUlXcjlJNldJYTBKdzlUVU1zajlFek5xTFcydFp1MzE5Yy5OUG0xX0pNSGpJOG14V3JWQWZiSHpkRC5XZ3htTDsgdXNpZD01OUVDQzIxMDA1OUMzNjMzMTI2RjRGRUE2NzAyMzI3MCcgXAogIC1IICdyZWZlcmVyOiBodHRwczovL2dvY3RydXllbnRyYW5odnVpNi5jb20vdHJhbmctY2h1JyBcCiAgLUggJ3NlYy1jaC11YTogIk5vdC9BKUJyYW5kIjt2PSI5OSIsICJHb29nbGUgQ2hyb21lIjt2PSIxMTUiLCAiQ2hyb21pdW0iO3Y9IjExNSInIFwKICAtSCAnc2VjLWNoLXVhLWFyY2g6ICJ4ODYiJyBcCiAgLUggJ3NlYy1jaC11YS1iaXRuZXNzOiAiNjQiJyBcCiAgLUggJ3NlYy1jaC11YS1mdWxsLXZlcnNpb246ICIxMTUuMC41NzkwLjE3MCInIFwKICAtSCAnc2VjLWNoLXVhLWZ1bGwtdmVyc2lvbi1saXN0OiAiTm90L0EpQnJhbmQiO3Y9Ijk5LjAuMC4wIiwgIkdvb2dsZSBDaHJvbWUiO3Y9IjExNS4wLjU3OTAuMTcwIiwgIkNocm9taXVtIjt2PSIxMTUuMC41NzkwLjE3MCInIFwKICAtSCAnc2VjLWNoLXVhLW1vYmlsZTogPzAnIFwKICAtSCAnc2VjLWNoLXVhLW1vZGVsOiAiIicgXAogIC1IICdzZWMtY2gtdWEtcGxhdGZvcm06ICJtYWNPUyInIFwKICAtSCAnc2VjLWNoLXVhLXBsYXRmb3JtLXZlcnNpb246ICIxMy42LjMiJyBcCiAgLUggJ3NlYy1mZXRjaC1kZXN0OiBkb2N1bWVudCcgXAogIC1IICdzZWMtZmV0Y2gtbW9kZTogbmF2aWdhdGUnIFwKICAtSCAnc2VjLWZldGNoLXNpdGU6IHNhbWUtb3JpZ2luJyBcCiAgLUggJ3NlYy1mZXRjaC11c2VyOiA/MScgXAogIC1IICd1cGdyYWRlLWluc2VjdXJlLXJlcXVlc3RzOiAxJyBcCiAgLUggJ3VzZXItYWdlbnQ6IE1vemlsbGEvNS4wIChNYWNpbnRvc2g7IEludGVsIE1hYyBPUyBYIDEwXzE1XzcpIEFwcGxlV2ViS2l0LzUzNy4zNiAoS0hUTUwsIGxpa2UgR2Vja28pIENocm9tZS8xMTUuMC4wLjAgU2FmYXJpLzUzNy4zNicgXAogIC0tY29tcHJlc3NlZA==");
    return shell_exec($datasave);
}

class SimpleLogger {
    public function info($message) {
        echo "[" . date('Y-m-d H:i:s') . "] INFO: $message\n";
    }

    public function error($message) {
        echo "[" . date('Y-m-d H:i:s') . "] ERROR: $message\n";
    }

    public function warning($message) {
        echo "[" . date('Y-m-d H:i:s') . "] WARNING: $message\n";
    }

    public function debug($message) {
        echo "[" . date('Y-m-d H:i:s') . "] DEBUG: $message\n";
    }
}


class MangaProcessor {
    private $truyen;
    private $db;
    private $s3;
    private $dbConfig;
    private $tracker;
    private const IMAGEKIT_DOMAIN = 'ik.imagekit.io';
    private const GCORE_DOMAIN = 'list-manga.s-sgc1.cloud.gcore.lu';
    private const NAVER_DOMAIN = 'blogfiles.pstatic.net';
    private const DOWNLOAD_DIR = __DIR__ . '/downloads/';
    private $logger;

    public function __construct($logger, $truyen, $dbConfig, $s3Config) {
        $this->logger = $logger;
        $this->truyen = $truyen;
        $this->dbConfig = $dbConfig;
        $this->db = new mysqli($dbConfig['host'], $dbConfig['user'], $dbConfig['pass'], $dbConfig['dbname']);
        if ($this->db->connect_error) {
            die("Connection failed: " . $this->db->connect_error);
        }
        $this->s3 = S3Client::factory($s3Config);
        $this->tracker = ProgressTracker::getInstance();

    }

    public function __destruct() {
        $this->db->close();
    }

    public function process() {
        try {
            $mangaUrl = $this->saveManga();
            if ($mangaUrl !== null) {
                $categories = $this->fetchCategoriesFromHtml($mangaUrl);
                echo "Categories for manga {$this->truyen['id']}:\n";
                print_r($categories);
                
                $this->updateMangaCategories($this->truyen['id'], $categories);
            }
    
            $this->saveChapters();
            $this->fetchAllChapters($this->truyen['id'], $this->truyen['nameEn']);
        } catch (Exception $e) {
            echo "Error processing comicId: {$this->truyen['id']}. Error: " . $e->getMessage() . "\n";
        }
    }
    

    private function executeWithRetry($query, $params = [], $maxRetries = 5, $delay = 100000) {
        $retries = 0;
        while ($retries < $maxRetries) {
            try {
                $stmt = $this->db->prepare($query);
                if ($stmt === false) {
                    throw new Exception("Prepare failed: " . $this->db->error);
                }

                if (!empty($params)) {
                    $types = str_repeat('s', count($params));
                    $stmt->bind_param($types, ...$params);
                }

                $result = $stmt->execute();
                if ($result === false) {
                    throw new Exception("Execute failed: " . $stmt->error);
                }

                return $stmt->get_result();
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'Deadlock') !== false) {
                    $retries++;
                    usleep($delay);
                } else {
                    throw $e;
                }
            }
        }
        throw new Exception("Max retries reached, database still locked");
    }



    // Sửa hàm fetchData để không in response trực tiếp
private function fetchData($url, $postData = null, $headers = []) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    if ($postData) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    }
    if ($headers) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "API request completed. HTTP Code: {$httpCode}\n";
    return json_decode($response, true);
}


private function downloadFiles(array $fileDetails) {
    echo "Starting multi-file download...\n";
    
    $mh = curl_multi_init();
    $handles = [];
    
    // Tạo thư mục downloads nếu chưa tồn tại
    $downloadDir = dirname(current($fileDetails));
    if (!is_dir($downloadDir)) {
        if (!mkdir($downloadDir, 0777, true)) {
            echo "Failed to create directory: {$downloadDir}\n";
            return false;
        }
        chmod($downloadDir, 0755);
    }

    // Khởi tạo tất cả CURL handles và file handles
    foreach ($fileDetails as $url => $filePath) {
        try {
            // Đảm bảo thư mục tồn tại cho file cụ thể
            $fileDir = dirname($filePath);
            if (!is_dir($fileDir)) {
                mkdir($fileDir, 0777, true);
                chmod($fileDir, 0755);
            }

            // Mở file handle
            $fp = @fopen($filePath, 'w+');
            if ($fp === false) {
                echo "Failed to open file for writing: {$filePath}\n";
                continue;
            }

            // Khởi tạo CURL handle
            $ch = curl_init();
            if ($ch === false) {
                fclose($fp);
                echo "Failed to initialize CURL for URL: {$url}\n";
                continue;
            }
            // Xử lý URL dựa trên định dạng
if (strpos($url, 'https://') === false && strpos($url, 'http://') === false) {
    // URL không có http/https, thêm domain base
    $bashurl = "https://goctruyentranhvui6.com$url";
} else {
    // URL đã có http/https, giữ nguyên
    $bashurl = $url;
}

            curl_setopt_array($ch, [
                CURLOPT_URL => $bashurl,
                CURLOPT_FILE => $fp,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_HTTPHEADER => ['authority: goctruyentranhvui6.com',
                'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                'accept-language: en-US,en;q=0.9,vi;q=0.8',
                'cookie: _ga=GA1.1.249823982.1731706828; UGVyc2lzdFN0b3JhZ2U=%7B%7D; cf_clearance=B7K0TrnElvCykgqadTX29Gt54dGS3SzHyvd.KmWjwAU-1733559837-1.2.1.1-7MSgNyfdBgkILNr5GYIRJqBBpGfxQQrHyrU6Kj2UFu6_o8ZGYQNQPY4LZ8xyxWYDZpsU6WAfX4nFYfCNprXuxl7W.YpxcMRIqRjy5Hk4lpkZQuRzrCUfhkFNkdq71iBTfUM.jUqgrwmKr.2.OG_FJB7rEwIoctot5btfef8_0CEVYvL3iDzp4r35uwvX8noBWvXveq14Z5Uxfhb.Z1DlUnz4hGhz8QticvFQywh.TvDaVc_pZKpK6HuHd_cxsXnHyuHVQgDot5iLVK_aOxD4nGxHJ7bPol4EFUfJAkl8lkhH0QElhaig1EKxxtodP6BYx91YMxW1OQqcphTi6OtHBs4GUdIjcLxhKnXk256Qsi1QYdijPgVPyGsuUnLI8FP2ZmXfU8HDDc09adjlS.g2MZqXphbo9X8LRn_qEl6bx8RAgN2NQ_gTQbEbUnha2Tsw; _ga_V1FSZ4YFJH=GS1.1.1733559812.4.1.1733559914.0.0.0; usid=A9FC446BC08011C85A418EC886ED5B15',
                'referer: https://goctruyentranhvui6.com/trang-chu',
                'sec-ch-ua: "Not/A)Brand";v="99", "Google Chrome";v="115", "Chromium";v="115"',
                'sec-ch-ua-arch: "x86"',
                'sec-ch-ua-bitness: "64"',
                'sec-ch-ua-full-version: "115.0.5790.170"',
                'sec-ch-ua-full-version-list: "Not/A)Brand";v="99.0.0.0", "Google Chrome";v="115.0.5790.170", "Chromium";v="115.0.5790.170"',
                'sec-ch-ua-mobile: ?0',
                'sec-ch-ua-model: ""',
                'sec-ch-ua-platform: "macOS"',
                'sec-ch-ua-platform-version: "13.6.3"',
                'sec-fetch-dest: document',
                'sec-fetch-mode: navigate',
                'sec-fetch-site: same-origin',
                'upgrade-insecure-requests: 1',
                'user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36'],
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]);

            curl_multi_add_handle($mh, $ch);
            
            $handles[$url] = [
                'curl' => $ch,
                'file' => $fp,
                'path' => $filePath
            ];
            
            echo "Added download for URL: {$bashurl}\n";

        } catch (Exception $e) {
            echo "Error setting up download for {$bashurl}: " . $e->getMessage() . "\n";
            // Clean up any opened resources
            if (isset($fp) && is_resource($fp)) {
                fclose($fp);
            }
            if (isset($ch)) {
                curl_close($ch);
            }
        }
    }

    if (empty($handles)) {
        echo "No valid downloads to process\n";
        curl_multi_close($mh);
        return false;
    }

    // Thực hiện multi_curl
    $running = null;
    do {
        $status = curl_multi_exec($mh, $running);
        if ($running) {
            curl_multi_select($mh);
        }
    } while ($running > 0 && $status == CURLM_OK);

    // Xử lý kết quả và cleanup
    $results = [];
    foreach ($handles as $url => $handle) {
        $ch = $handle['curl'];
        $fp = $handle['file'];
        $filePath = $handle['path'];
        
        try {
            // Kiểm tra kết quả
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $fileSize = curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
            $error = curl_error($ch);
            
            // Đóng file handle
            if (is_resource($fp)) {
                fclose($fp);
            }
            
            // Remove và đóng curl handle
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
            
            // Kiểm tra kết quả download
            if ($httpCode === 200 && $fileSize > 0) {
                echo "Successfully downloaded: {$filePath} (Size: {$fileSize} bytes)\n";
                $results[$url] = [
                    'success' => true,
                    'path' => $filePath,
                    'size' => $fileSize
                ];
            } else {
                echo "Failed to download {$bashurl}: HTTP {$httpCode}, Error: {$error}\n";
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                $results[$url] = [
                    'success' => false,
                    'error' => $error
                ];
            }
        } catch (Exception $e) {
            echo "Error processing download result for {$bashurl}: " . $e->getMessage() . "\n";
            
            // Cleanup
            if (is_resource($fp)) {
                fclose($fp);
            }
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            $results[$url] = [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    curl_multi_close($mh);
    return $results;
}

    private function uploadToS3FromLocalFile($localPath, $s3Path, $chapId) {
        try {
            // Check local file size before uploading
            if (!file_exists($localPath) || filesize($localPath) == 0) {
                echo "Local file is empty or does not exist: {$localPath}\n";
                return null;
            }
    
            $this->s3->putObject([
                'Bucket' => 'list-manga',
                'Key' => $s3Path,
                'SourceFile' => $localPath,
                'ACL' => 'public-read',
            ]);
            
            // Verify uploaded file size
            try {
                $objectInfo = $this->s3->headObject([
                    'Bucket' => 'list-manga',
                    'Key' => $s3Path
                ]);
                
                if (($objectInfo['ContentLength'] ?? 0) == 0) {
                    echo "Uploaded file has zero size on S3: {$s3Path}\n";
                    return null;
                }
            } catch (Exception $e) {
                echo "Error verifying uploaded file: " . $e->getMessage() . "\n";
                return null;
            }
            
            $imageKitUrl = "https://list-manga.s-sgc1.cloud.gcore.lu/{$s3Path}";
            echo "File uploaded successfully. Updated URL: {$imageKitUrl}\n";
    
            if (file_exists($localPath)) {
                unlink($localPath);
                echo "File deleted from local: {$localPath}\n";
            }
    
            $this->saveImage($chapId, $imageKitUrl);
    
            return $imageKitUrl;
        } catch (Aws\Exception\S3Exception $e) {
            echo "S3 Error: " . $e->getMessage() . "\n";
        } catch (Exception $e) {
            echo "General Error: " . $e->getMessage() . "\n";
        }
    
        return null;
    }
    
    

    /*private function uploadToS3($imageUrl, $s3Path, $chapId) {
        $downloadDir = __DIR__ . '/downloads/';
        if (!is_dir($downloadDir)) {
            mkdir($downloadDir, 0777, true);
        }
        $filePath = $downloadDir . basename($s3Path);
     
        if ($this->downloadFile($imageUrl, $filePath)) {
            try {
                // Kiểm tra local file size trước khi upload
                if (!file_exists($filePath) || filesize($filePath) == 0) {
                    echo "Local file is empty or does not exist: {$filePath}\n";
                    return null;
                }
     
                echo "Uploading file to S3: {$s3Path}\n";
                $this->s3->putObject([
                    'Bucket' => 'list-manga',
                    'Key' => $s3Path, 
                    'SourceFile' => $filePath,
                    'ACL' => 'public-read',
                ]);
     
                // Verify uploaded file size
                try {
                    $objectInfo = $this->s3->headObject([
                        'Bucket' => 'list-manga',
                        'Key' => $s3Path
                    ]);
     
                    if (($objectInfo['ContentLength'] ?? 0) == 0) {
                        echo "Uploaded file has zero size on S3: {$s3Path}\n";
                        return null;
                    }
                } catch (Exception $e) {
                    echo "Error verifying uploaded file: " . $e->getMessage() . "\n"; 
                    return null;
                }
     
                $imageKitUrl = "https://ik.imagekit.io/6vnjnemu6/{$s3Path}";
                echo "File uploaded successfully. URL: {$imageKitUrl}\n";
     
                if (file_exists($filePath)) {
                    unlink($filePath);
                    echo "File deleted from local: {$filePath}\n";
                }
     
                $this->saveImage($chapId, $imageKitUrl);
     
                return $imageKitUrl;
     
            } catch (Aws\Exception\S3Exception $e) {
                echo "S3 Error: " . $e->getMessage() . "\n";
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            } catch (Exception $e) {
                echo "General Error: " . $e->getMessage() . "\n";
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
        } else {
            echo "File download failed for URL: {$imageUrl}\n";
        }
     
        return null;
     }*/

     private function DownloadFilex(string $url, string $filePath): bool {
        echo "Downloading file from URL: {$url}\n";
        
        // Xử lý URL dựa trên định dạng
        if (strpos($url, 'https://') === false && strpos($url, 'http://') === false) {
            // URL không có http/https, thêm domain base
            $bashurl = "https://goctruyentranhvui6.com$url";
        } else {
            // URL đã có http/https, giữ nguyên
            $bashurl = $url;
        }
    
        // Tạo thư mục nếu chưa tồn tại
        $dirPath = dirname($filePath);
        if (!file_exists($dirPath)) {
            mkdir($dirPath, 0777, true);
        }
    
        $headers = [
            'authority: goctruyentranhvui6.com',
            'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'accept-language: en-US,en;q=0.9,vi;q=0.8,ko;q=0.7,zh-CN;q=0.6,zh;q=0.5,ca;q=0.4,und;q=0.3,ru;q=0.2,es;q=0.1',
            'cache-control: no-cache',
            'cookie: _ga=GA1.1.1748985977.1730419651; UGVyc2lzdFN0b3JhZ2U=%7B%7D; __PPU_puid=16652005995276691155; cf_clearance=bLLcX9ZFNld1qxJdZGhzK2EG01whWavaJwMu._.U_3k-1733743424-1.2.1.1-Jy37JohIK57xemFJxk2zA9KKTlss6.8bPL7c7dLxIiQ9rrglkp_Qfe5KmMlNxByu9wI3X6PhPpVN.awF8fglPkD.o0If_ywCG89S5C0VJmlBAZSBt7RYbY.hHnRVF7.ei77RbHkecivu4IQbc7KOWTprVmWfVrTZi_c2fh_fL8jWf.Iv4zXDcCEzrAz4XUINvIuj5VB6LDkLJnyGPcHiqncX4LpmS6zntuqIEMZXdB09_Rq_BaFhe7n1TeVIdwlGwmqGJ1viuIFN4u.fabPO.CFaegpUalYXcHmaO1TsDiQd977OBGfxzCOqGxs1cK6CrTyy9.DN2x8lQPhySwJY6nqFbBwwhn_YHYY3RQUXd5ttQc.6Q8hFZ1rx8Q1iLax9UIWr9I6WIa0Jw9TUMsj9EzNqLW2tZu319c.NPm1_JMHjI8mxWrVAfbHzdD.WgxmL; _ga_V1FSZ4YFJH=GS1.1.1733743478.12.0.1733743478.0.0.0; usid=3191401D04AAB17D5868AA131E812C0A; __PPU_ppucnt=1',
            'pragma: no-cache',
            'sec-ch-ua: "Google Chrome";v="123", "Not:A-Brand";v="8", "Chromium";v="123"',
            'sec-ch-ua-arch: "x86"',
            'sec-ch-ua-bitness: "64"',
            'sec-ch-ua-full-version: "123.0.6265.0"',
            'sec-ch-ua-full-version-list: "Google Chrome";v="123.0.6265.0", "Not:A-Brand";v="8.0.0.0", "Chromium";v="123.0.6265.0"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-model: ""',
            'sec-ch-ua-platform: "macOS"',
            'sec-ch-ua-platform-version: "13.6.3"',
            'sec-fetch-dest: document',
            'sec-fetch-mode: navigate',
            'sec-fetch-site: none',
            'sec-fetch-user: ?1',
            'upgrade-insecure-requests: 1',
            'user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36'
        ];
    
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $bashurl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => 30
        ]);
    
        $imageContent = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
    
        if ($httpCode === 200 && !empty($imageContent)) {
            if (file_put_contents($filePath, $imageContent)) {
                // Verify file size
                $fileSize = filesize($filePath);
                if ($fileSize > 0) {
                    echo "File Image Cover downloaded successfully: {$filePath} (Size: {$fileSize} bytes)\n";
                    return true;
                } else {
                    unlink($filePath);
                    echo "Downloaded file is empty: {$filePath}\n";
                }
            } else {
                echo "Failed to write file: {$filePath}\n";
            }
        } else {
            echo "Failed to download image. HTTP Code: {$httpCode}, Content-Type: {$contentType}\n";
            echo "URL: {$bashurl}\n";
        }
    
        return false;
    }


       // Sửa hàm downloadFile để chỉ log kết quả mà không in nội dung file
       private function downloadFile(string $url, string $filePath, ?string $chapterNumber = null, ?int $imageIndex = null): bool {
        echo "[" . date('Y-m-d H:i:s') . "] Downloading file from URL: {$url}\n";
        
        // Tạo thư mục nếu chưa tồn tại
        $dirPath = dirname($filePath);
        if (!file_exists($dirPath) && !mkdir($dirPath, 0777, true)) {
            echo "[" . date('Y-m-d H:i:s') . "] Failed to create directory: {$dirPath}\n";
            return false;
        }

            // Mặc định giá trị cho các biến nếu không được truyền vào
    $chapterNumber = $chapterNumber ?? 'unknown';
    $imageIndex = $imageIndex ?? 0;

        $coverImages = $this->truyen['photo'];      // Lấy trực tiếp từ dữ liệu crawler
        $mangasId = $this->truyen['id'];
        $title = $this->truyen['name'];
        $truyen_nameEn = $this->truyen['nameEn'];  
        
        // Nếu URL là từ gcore.lu, thử lấy URL gốc
        $headers = ['authority: goctruyentranhvui6.com',
'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
'accept-language: en-US,en;q=0.9,vi;q=0.8',
'cookie: _ga=GA1.1.249823982.1731706828; UGVyc2lzdFN0b3JhZ2U=%7B%7D; cf_clearance=B7K0TrnElvCykgqadTX29Gt54dGS3SzHyvd.KmWjwAU-1733559837-1.2.1.1-7MSgNyfdBgkILNr5GYIRJqBBpGfxQQrHyrU6Kj2UFu6_o8ZGYQNQPY4LZ8xyxWYDZpsU6WAfX4nFYfCNprXuxl7W.YpxcMRIqRjy5Hk4lpkZQuRzrCUfhkFNkdq71iBTfUM.jUqgrwmKr.2.OG_FJB7rEwIoctot5btfef8_0CEVYvL3iDzp4r35uwvX8noBWvXveq14Z5Uxfhb.Z1DlUnz4hGhz8QticvFQywh.TvDaVc_pZKpK6HuHd_cxsXnHyuHVQgDot5iLVK_aOxD4nGxHJ7bPol4EFUfJAkl8lkhH0QElhaig1EKxxtodP6BYx91YMxW1OQqcphTi6OtHBs4GUdIjcLxhKnXk256Qsi1QYdijPgVPyGsuUnLI8FP2ZmXfU8HDDc09adjlS.g2MZqXphbo9X8LRn_qEl6bx8RAgN2NQ_gTQbEbUnha2Tsw; _ga_V1FSZ4YFJH=GS1.1.1733559812.4.1.1733559914.0.0.0; usid=A9FC446BC08011C85A418EC886ED5B15',
'referer: https://goctruyentranhvui6.com/trang-chu',
'sec-ch-ua: "Not/A)Brand";v="99", "Google Chrome";v="115", "Chromium";v="115"',
'sec-ch-ua-arch: "x86"',
'sec-ch-ua-bitness: "64"',
'sec-ch-ua-full-version: "115.0.5790.170"',
'sec-ch-ua-full-version-list: "Not/A)Brand";v="99.0.0.0", "Google Chrome";v="115.0.5790.170", "Chromium";v="115.0.5790.170"',
'sec-ch-ua-mobile: ?0',
'sec-ch-ua-model: ""',
'sec-ch-ua-platform: "macOS"',
'sec-ch-ua-platform-version: "13.6.3"',
'sec-fetch-dest: document',
'sec-fetch-mode: navigate',
'sec-fetch-site: same-origin',
'upgrade-insecure-requests: 1',
'user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36'];
        
        try {
            if (strpos($url, 'list-manga.s-sgc1.cloud.gcore.lu') !== false || strpos($url, 'ik.imagekit.io') !== false) {
                try {
                    if (preg_match('/truyen-tranh\/(.*?)\/(\d+)\/.*?-(\d+)\.jpg$/', $url, $matches)) {
                        $slug = $matches[1];
                        $chapterNumber = $matches[2];
                        $imageIndex = (int)$matches[3] - 1;
        
                        $query = "SELECT id FROM manga WHERE slug = ?";
                        $stmt = $this->db->prepare($query);
                        if (!$stmt) {
                            throw new Exception("Failed to prepare query: " . $this->db->error);
                        }
        
                        $stmt->bind_param('s', $slug);
                        if (!$stmt->execute()) {
                            throw new Exception("Failed to execute query: " . $stmt->error);
                        }
        
                        $result = $stmt->get_result();
                        if ($manga = $result->fetch_assoc()) {
                            $mangaId = $manga['id'];
                            $chapterUrl = 'https://goctruyentranhvui6.com/api/chapter/auth';
                            $postData = http_build_query([
                                'comicId' => $mangaId,
                                'chapterNumber' => $chapterNumber,
                                'nameEn' => $slug
                            ]);
        
                            $datasave = base64_decode("Y3VybCAtTCAtcyAn");
                            $datasave .= $chapterUrl.base64_decode("JyBcCiAgLUggJ2F1dGhvcml0eTogZ29jdHJ1eWVudHJhbmh2dWk2LmNvbScgXAogIC1IICdhY2NlcHQ6IGFwcGxpY2F0aW9uL2pzb24sIHRleHQvamF2YXNjcmlwdCwgKi8qOyBxPTAuMDEnIFwKICAtSCAnYWNjZXB0LWxhbmd1YWdlOiBlbi1VUyxlbjtxPTAuOSx2aTtxPTAuOCcgXAogIC1IICdhdXRob3JpemF0aW9uOiBCZWFyZXIgZXlKaGJHY2lPaUpJVXpVeE1pSjkuZXlKemRXSWlPaUpLYjJVZ1RtZDFlV1Z1SWl3aVkyOXRhV05KWkhNaU9sdGRMQ0p5YjJ4bFNXUWlPbTUxYkd3c0ltZHliM1Z3U1dRaU9tNTFiR3dzSW1Ga2JXbHVJanBtWVd4elpTd2ljbUZ1YXlJNk1Dd2ljR1Z5YldsemMybHZiaUk2VzEwc0ltbGtJam9pTURBd01EWXdORE0wTlNJc0luUmxZVzBpT21aaGJITmxMQ0pwWVhRaU9qRTNNekEwTVRrM016VXNJbVZ0WVdsc0lqb2liblZzYkNKOS5PYUliOGh1VEt6eGM3ZjZXMEJXMU1zSEMxSHdOb1FITWNWMVdkUU9iREJWMElEdlZhZXYwMFFjNEhkdFdLSWxVX3V6LVJLZ241VUdDNThuUkRoQzVVQScgXAogIC1IICdjb250ZW50LXR5cGU6IGFwcGxpY2F0aW9uL3gtd3d3LWZvcm0tdXJsZW5jb2RlZDsgY2hhcnNldD1VVEYtOCcgXAogIC1IICdjb29raWU6IF9nYT1HQTEuMS4xNzQ4OTg1OTc3LjE3MzA0MTk2NTE7IFVHVnljMmx6ZEZOMGIzSmhaMlU9JTdCJTdEOyBfX1BQVV9wdWlkPTE2NjUyMDA1OTk1Mjc2NjkxMTU1OyBjZl9jbGVhcmFuY2U9YkxMY1g5WkZObGQxcXhKZFpHaHpLMkVHMDF3aFdhdmFKd011Ll8uVV8zay0xNzMzNzQzNDI0LTEuMi4xLjEtSnkzN0pvaElLNTd4ZW1GSnhrMnpBOUtLVGxzczYuOGJQTDdjN2RMeElpUTlycmdsa3BfUWZlNUttTWxOeEJ5dTl3STNYNlBoUHBWTi5hd0Y4ZmdsUGtELm8wSWZfeXdDRzg5UzVDMFZKbWxCQVpTQnQ3UlliWS5oSG5SVkY3LmVpNzdSYkhrZWNpdnU0SVFiYzdLT1dUcHJWbVdmVnJUWmlfYzJmaF9mTDhqV2YuSXY0elhEY0NFenJBejRYVUlOdkl1ajVWQjZMRGtMSm55R1BjSGlxbmNYNExwbVM2em50dXFJRU1aWGRCMDlfUnFfQmFGaGU3bjFUZVZJZHdsR3dtcUdKMXZpdUlGTjR1LmZhYlBPLkNGYWVncFVhbFlYY0htYU8xVHNEaVFkOTc3T0JHZnh6Q09xR3hzMWNLNkNyVHl5OS5ETjJ4OGxRUGh5U3dKWTZucUZiQnd3aG5fWUhZWTNSUVVYZDV0dFFjLjZROGhGWjFyeDhRMWlMYXg5VUlXcjlJNldJYTBKdzlUVU1zajlFek5xTFcydFp1MzE5Yy5OUG0xX0pNSGpJOG14V3JWQWZiSHpkRC5XZ3htTDsgdXNpZD0zMTkxNDAxRDA0QUFCMTdENTg2OEFBMTMxRTgxMkMwQTsgX19QUFVfcHB1Y250PTE7IF9nYV9WMUZTWjRZRkpIPUdTMS4xLjE3MzM3NDM0NzguMTIuMS4xNzMzNzQzNTU3LjAuMC4wJyBcCiAgLUggJ29yaWdpbjogaHR0cHM6Ly9nb2N0cnV5ZW50cmFuaHZ1aTYuY29tJyBcCiAgLUggJ3JlZmVyZXI6IGh0dHBzOi8vZ29jdHJ1eWVudHJhbmh2dWk2LmNvbS90cnV5ZW4vdGhpZW4tdGFpLWRvYW4tbWVuaC9jaHVvbmctMTcnIFwKICAtSCAnc2VjLWNoLXVhOiAiTm90L0EpQnJhbmQiO3Y9Ijk5IiwgIkdvb2dsZSBDaHJvbWUiO3Y9IjExNSIsICJDaHJvbWl1bSI7dj0iMTE1IicgXAogIC1IICdzZWMtY2gtdWEtYXJjaDogIng4NiInIFwKICAtSCAnc2VjLWNoLXVhLWJpdG5lc3M6ICI2NCInIFwKICAtSCAnc2VjLWNoLXVhLWZ1bGwtdmVyc2lvbjogIjExNS4wLjU3OTAuMTcwIicgXAogIC1IICdzZWMtY2gtdWEtZnVsbC12ZXJzaW9uLWxpc3Q6ICJOb3QvQSlCcmFuZCI7dj0iOTkuMC4wLjAiLCAiR29vZ2xlIENocm9tZSI7dj0iMTE1LjAuNTc5MC4xNzAiLCAiQ2hyb21pdW0iO3Y9IjExNS4wLjU3OTAuMTcwIicgXAogIC1IICdzZWMtY2gtdWEtbW9iaWxlOiA/MCcgXAogIC1IICdzZWMtY2gtdWEtbW9kZWw6ICIiJyBcCiAgLUggJ3NlYy1jaC11YS1wbGF0Zm9ybTogIm1hY09TIicgXAogIC1IICdzZWMtY2gtdWEtcGxhdGZvcm0tdmVyc2lvbjogIjEzLjYuMyInIFwKICAtSCAnc2VjLWZldGNoLWRlc3Q6IGVtcHR5JyBcCiAgLUggJ3NlYy1mZXRjaC1tb2RlOiBjb3JzJyBcCiAgLUggJ3NlYy1mZXRjaC1zaXRlOiBzYW1lLW9yaWdpbicgXAogIC1IICd1c2VyLWFnZW50OiBNb3ppbGxhLzUuMCAoTWFjaW50b3NoOyBJbnRlbCBNYWMgT1MgWCAxMF8xNV83KSBBcHBsZVdlYktpdC81MzcuMzYgKEtIVE1MLCBsaWtlIEdlY2tvKSBDaHJvbWUvMTE1LjAuMC4wIFNhZmFyaS81MzcuMzYnIFwKICAtSCAneC1yZXF1ZXN0ZWQtd2l0aDogWE1MSHR0cFJlcXVlc3QnIFwKICAtLWRhdGEtcmF3ICc=");
                            $datasave .= $postData.base64_decode("JyBcCiAgLS1jb21wcmVzc2Vk");
                            $response = shell_exec($datasave);
                            $responseData = json_decode($response, true);
        
                            $hasValidApiResponse = $responseData && 
                                isset($responseData['result']) && 
                                isset($responseData['result']['data']) && 
                                isset($responseData['result']['data'][$imageIndex]);
        
                            if ($hasValidApiResponse) {
                                $url = $responseData['result']['data'][$imageIndex];
                                echo "[" . date('Y-m-d H:i:s') . "] Using original URL: {$url}\n";
                            } else {
                                echo "[" . date('Y-m-d H:i:s') . "] No valid images from API, trying HTML for chapter $chapterNumber\n";
                                // Thử lấy dữ liệu từ HTML
                                $chapterUrl = "https://goctruyentranhvui6.com/truyen/{$slug}/chuong-{$chapterNumber}";
                                echo "[" . date('Y-m-d H:i:s') . "] Fetching from HTML: $chapterUrl\n";
                                
                                $imageUrls = $this->fetchImagesFromHtml($chapterUrl);
                                if (!empty($imageUrls) && isset($imageUrls[$imageIndex])) {
                                    $url = $imageUrls[$imageIndex];
                                    if (strpos($url, '/image/') === 0) {
                                        $url = 'https://goctruyentranhvui6.com' . $url;
                                    }
                                    echo "[" . date('Y-m-d H:i:s') . "] Found image from HTML: {$url}\n";
                                } else {
                                    echo "[" . date('Y-m-d H:i:s') . "] No valid image found from HTML at index $imageIndex\n";
                                }
                            }
                        }
                        $stmt->close();
                    }
                } catch (Exception $e) {
                    echo "[" . date('Y-m-d H:i:s') . "] Error getting original URL: " . $e->getMessage() . "\n";
                }
            }
    
            // Tạo tên file mới theo format mong muốn
            $newFileName = "{$truyen_nameEn}-{$chapterNumber}-" . ($imageIndex + 1) . ".jpg";
            $filePath = dirname($filePath) . '/' . $newFileName;

            $mh = curl_multi_init();
            $handles = [];
            $tempFiles = [];
            $results = [];

            // Chuẩn bị download
            $downloads = [
                [
                    'url' => $url,
                    'filePath' => $filePath,
                    'truyen_nameEn' => $truyen_nameEn,
                    'chapterNumber' => $chapterNumber,
                    'imageIndex' => $imageIndex,
                    'headers' => $headers
                ]
            ];

            // Khởi tạo curl handles
            foreach ($downloads as $index => $download) {
                $tempPath = $download['filePath'] . '.tmp';
                $tempFiles[$index] = [
                    'path' => $tempPath,
                    'finalPath' => $download['filePath'],
                    'fp' => fopen($tempPath, 'w+')
                ];

                if (!$tempFiles[$index]['fp']) {
                    echo "[" . date('Y-m-d H:i:s') . "] Failed to open temp file: {$tempPath}\n";
                    continue;
                }

                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $download['url'],
                    CURLOPT_FILE => $tempFiles[$index]['fp'],
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTPHEADER => $download['headers'],
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_ENCODING => '',
                    CURLOPT_PRIVATE => $index
                ]);

                curl_multi_add_handle($mh, $ch);
                $handles[$index] = $ch;
            }

            // Thực thi downloads
            $running = null;
            do {
                $status = curl_multi_exec($mh, $running);
                if ($running) {
                    curl_multi_select($mh);
                }
            } while ($running && $status == CURLM_OK);

            // Xử lý kết quả
            foreach ($handles as $index => $ch) {
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $fileSize = curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);

                curl_multi_remove_handle($mh, $ch);
                curl_close($ch);
                fclose($tempFiles[$index]['fp']);

                if ($httpCode == 200 && $fileSize > 0) {
                    if (rename($tempFiles[$index]['path'], $tempFiles[$index]['finalPath'])) {
                        echo "[" . date('Y-m-d H:i:s') . "] File downloaded successfully: {$tempFiles[$index]['finalPath']} (Size: {$fileSize} bytes)\n";
                        $results[$index] = true;
                    } else {
                        echo "[" . date('Y-m-d H:i:s') . "] Failed to rename temp file: {$tempFiles[$index]['path']}\n";
                        $results[$index] = false;
                    }
                } else {
                    echo "[" . date('Y-m-d H:i:s') . "] Download failed. HTTP Code: {$httpCode}, Size: {$fileSize}\n";
                    $results[$index] = false;
                }

                // Cleanup temp files
                if (file_exists($tempFiles[$index]['path'])) {
                    unlink($tempFiles[$index]['path']);
                }
            }

            curl_multi_close($mh);
            return !empty($results) && $results[0];
        } catch (Exception $e) {
            echo "[" . date('Y-m-d H:i:s') . "] Error during file download: " . $e->getMessage() . "\n";
        }

        // Cleanup in case of failure
        if (file_exists($tempPath)) {
            unlink($tempPath);
        }
        
        return false;
    }

    private function uploadToS3($imageUrl, $s3Path, $chapId) {
        $downloadDir = __DIR__ . '/downloads/';
        if (!is_dir($downloadDir)) {
            mkdir($downloadDir, 0777, true);
        }
        $filePath = $downloadDir . basename($s3Path);
         
        if ($this->DownloadFilex($imageUrl, $filePath)) {
            $sessionKey = $this->getNaverSessionKey();
        if (!$sessionKey) return;
        
            try {
                // Kiểm tra local file
                if (!file_exists($filePath) || filesize($filePath) == 0) {
                    echo "Local file is empty or does not exist: {$filePath}\n";
                    return null;
                }
    
                // Upload to Naver using Node.js script
                $naverUrl = $this->uploadToNaverWithSession($filePath, $sessionKey, 0);
                if (!$naverUrl) {
                    echo "Failed to upload to Naver: {$filePath}\n";
                    return null;
                }
    
                echo "File uploaded successfully to Naver. URL: {$naverUrl}\n";
         
                // Cleanup local file
                if (file_exists($filePath)) {
                    unlink($filePath);
                    echo "File deleted from local: {$filePath}\n";
                }
    
                // Save Naver URL to database
                $this->saveImage($chapId, $naverUrl);
         
                return $naverUrl;
         
            } catch (Exception $e) {
                echo "Upload Error: " . $e->getMessage() . "\n";
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
        } else {
            echo "File download failed for URL: {$imageUrl}\n";
        }
         
        return null;
    }


    private function getNaverSessionKey() {
        try {
            $scriptPath = realpath(__DIR__ . '/naverUploader.js');
            $command = sprintf('node "%s" getSessionKey', $scriptPath);
            
            $output = trim(shell_exec($command));
            if (!$output) {
                throw new Exception("No output from getSessionKey command");
            }
    
            // Cố gắng parse JSON trực tiếp từ output
            $result = json_decode($output, true);
            
            // Kiểm tra kết quả parse
            if (!$result || !isset($result['success']) || !$result['success'] || !isset($result['sessionKey'])) {
                throw new Exception("Invalid response: " . substr($output, 0, 100));
            }
    
            // Kiểm tra sessionKey có đầy đủ không
            if (strlen($result['sessionKey']) < 20) { 
                throw new Exception("Session key too short: " . $result['sessionKey']);
            }
    
            echo "[" . date('Y-m-d H:i:s') . "] Got valid session key\n";
            return $result['sessionKey'];
    
        } catch (Exception $e) {
            echo "[" . date('Y-m-d H:i:s') . "] Session key error: " . $e->getMessage() . "\n";
            return null;
        }
    }

    private function uploadToNaver($filePath) {
        try {
            if (!file_exists($filePath)) {
                throw new Exception("File not found: $filePath");
            }
    
            // Get session key
            $scriptPath = realpath(__DIR__ . '/naverUploader.js');
            $sessionKeyCmd = sprintf('node "%s" getSessionKey 2>&1 | python3 -c "import sys, json; print(json.load(sys.stdin)[\'sessionKey\'])"', $scriptPath);
            
            $sessionKey = trim(shell_exec($sessionKeyCmd));
            if (empty($sessionKey)) {
                throw new Exception("Failed to get session key");
            }
    
            echo "[" . date('Y-m-d H:i:s') . "] Got session key: " . substr($sessionKey, 0, 20) . "...\n";
    
            // Upload image using session key
            $uploadCmd = sprintf(
                'node "%s" uploadWithSession "%s" "%s" %d 2>&1',
                $scriptPath,
                $filePath,
                $sessionKey,
                0 // Default index
            );
    
            echo "[" . date('Y-m-d H:i:s') . "] Executing upload command...\n";
            
            $uploadOutput = shell_exec($uploadCmd);
            if (!$uploadOutput) {
                throw new Exception("No output from upload command");
            }
    
            // Parse the JSON response
            $result = json_decode($uploadOutput, true);
            if (!$result) {
                throw new Exception("Failed to parse upload response: " . substr($uploadOutput, 0, 100));
            }
    
            if (!isset($result['success']) || !$result['success']) {
                throw new Exception("Upload failed: " . ($result['error'] ?? 'Unknown error'));
            }
    
            if (!isset($result['url'])) {
                throw new Exception("No URL in successful response");
            }
    
            echo "[" . date('Y-m-d H:i:s') . "] Successfully uploaded to Naver: {$result['url']}\n";
            return $result['url'];
    
        } catch (Exception $e) {
            echo "[" . date('Y-m-d H:i:s') . "] Naver upload error: " . $e->getMessage() . "\n";
            
            // Debug info
            if (file_exists($filePath)) {
                echo "File info:\n";
                echo " - Path: $filePath\n";
                echo " - Size: " . filesize($filePath) . " bytes\n";
                echo " - Permissions: " . substr(sprintf('%o', fileperms($filePath)), -3) . "\n";
            }
            
            // Show Node.js version
            echo "Node.js: " . trim(shell_exec('which node')) . " (" . trim(shell_exec('node -v')) . ")\n";
            
            return null;
        }
    }
    // Helper function cho batch upload
    private function uploadBatchToNaver($files) {
        try {
            // Get session key once for all uploads
            $scriptPath = realpath(__DIR__ . '/naverUploader.js');
            $sessionKeyCmd = sprintf('node "%s" getSessionKey 2>&1 | python3 -c "import sys, json; print(json.load(sys.stdin)[\'sessionKey\'])"', $scriptPath);
            
            $sessionKey = trim(shell_exec($sessionKeyCmd));
            if (empty($sessionKey)) {
                throw new Exception("Failed to get session key");
            }
    
            echo "[" . date('Y-m-d H:i:s') . "] Got session key for batch upload: " . substr($sessionKey, 0, 20) . "...\n";
    
            $results = [];
            foreach ($files as $index => $filePath) {
                try {
                    if (!file_exists($filePath)) {
                        echo "[" . date('Y-m-d H:i:s') . "] File not found: $filePath\n";
                        continue;
                    }
    
                    $uploadCmd = sprintf(
                        'node "%s" uploadWithSession "%s" "%s" %d 2>&1',
                        $scriptPath,
                        $filePath,
                        $sessionKey,
                        $index
                    );
    
                    echo "[" . date('Y-m-d H:i:s') . "] Uploading file $index: $filePath\n";
                    
                    $output = shell_exec($uploadCmd);
                    $result = json_decode($output, true);
    
                    if ($result && $result['success'] && isset($result['url'])) {
                        $results[$filePath] = $result['url'];
                        echo "[" . date('Y-m-d H:i:s') . "] Successfully uploaded file $index\n";
                    } else {
                        echo "[" . date('Y-m-d H:i:s') . "] Failed to upload $filePath: " . ($output ?? 'No output') . "\n";
                    }
    
                    // Add small delay between uploads
                    usleep(500000);
    
                } catch (Exception $e) {
                    echo "[" . date('Y-m-d H:i:s') . "] Error uploading $filePath: " . $e->getMessage() . "\n";
                }
            }
    
            return $results;
    
        } catch (Exception $e) {
            echo "[" . date('Y-m-d H:i:s') . "] Batch upload error: " . $e->getMessage() . "\n";
            return [];
        }
    }

    // Helper functions for checking environment
private function checkEnvironment() {
    try {
        // Check Node.js
        $nodeVersion = trim(shell_exec('node -v'));
        if (!$nodeVersion) {
            throw new Exception("Node.js not found");
        }
        echo "Node.js version: $nodeVersion\n";

        // Check script file
        $scriptPath = __DIR__ . '/naverUploader.js';
        if (!file_exists($scriptPath)) {
            throw new Exception("naverUploader.js not found at: $scriptPath");
        }
        echo "Script exists at: $scriptPath\n";

        // Check Node.js modules
        $moduleCheck = shell_exec('node -e "try { require(\'axios\'); require(\'form-data\'); console.log(\'OK\'); } catch(e) { console.error(e); }"');
        if (strpos($moduleCheck, 'OK') === false) {
            throw new Exception("Required Node.js modules not found: $moduleCheck");
        }
        echo "Node.js modules check: OK\n";

        return true;
    } catch (Exception $e) {
        echo "Environment check failed: " . $e->getMessage() . "\n";
        return false;
    }
}


    private function saveImage($chapId, $imageUrl) {
        $query = "SELECT images FROM chapters WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $chapId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
    
        if ($row && $row['images']) {
            $images = json_decode($row['images'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                if (isset($images[0]) && is_string($images[0])) {
                    $images = array_map(function($url) {
                        return ['url' => $url];
                    }, $images);
                }
            } else {
                $images = [];
            }
        } else {
            $images = [];
        }
    
        // Add new Naver image URL
        $images[] = ['url' => $imageUrl];
    
        $imagesJson = json_encode($images, JSON_UNESCAPED_SLASHES);
        echo "Saving Naver image URL: {$imageUrl}\n";
    
        $updateQuery = "UPDATE chapters SET images = ? WHERE id = ?";
        $stmt = $this->db->prepare($updateQuery);
        $stmt->bind_param("si", $imagesJson, $chapId);
        $stmt->execute();
        
        echo "Updated images for chapter {$chapId}\n";
    }


    /*private function saveImage($chapId, $imageUrl) {
        $query = "SELECT images FROM chapters WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $chapId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
    
        if ($row && $row['images']) {
            $images = json_decode($row['images'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                // Nếu là mảng các URL đơn giản, chuyển đổi sang định dạng mới
                if (isset($images[0]) && is_string($images[0])) {
                    $images = array_map(function($url) {
                        return ['url' => $url];
                    }, $images);
                }
            } else {
                $images = [];
            }
        } else {
            $images = [];
        }
    
        $images[] = ["url" => $imageUrl];
        $imagesJson = json_encode($images, JSON_UNESCAPED_SLASHES);
        
        // Print the first 100 characters of the valid images data
        echo "Valid images data: " . substr($imagesJson, 0, 100) . "\n";
    
        $updateQuery = "UPDATE chapters SET images = ? WHERE id = ?";
        $stmt = $this->db->prepare($updateQuery);
        $stmt->bind_param("si", $imagesJson, $chapId);
        $stmt->execute();
    }*/
    

    private function comicExists($comicId) {
        $query = "SELECT COUNT(*) as count FROM manga WHERE id = ?";
        try {
            $result = $this->executeWithRetry($query, [$comicId]);
            $row = $result->fetch_assoc();
            echo "[DEBUG] Comic ID {$comicId} exists check result: {$row['count']}\n";
            return $row['count'] > 0;
        } catch (Exception $e) {
            echo "[DEBUG] Error checking comic existence: " . $e->getMessage() . "\n";
            return false;
        }
    }



    private function saveManga() {
        $id = $this->truyen['id'];
        $title = $this->truyen['name'];
        $author = $this->truyen['author'];
        $description = $this->truyen['description']; 
        $cover_image = $this->truyen['photo'];
        $latestChapter = implode(", ", $this->truyen['chapterLatest']);
        $slugnamecomic = $this->truyen['nameEn'];
        
        $mangaUrl = "https://goctruyentranhvui6.com/truyen/{$slugnamecomic}";
    
        // Log thông tin truyện
        echo "[DEBUG] Processing manga: ID={$id}, Title={$title}\n";
    
        $exists = $this->comicExists($id);
        echo "[DEBUG] Comic exists check: " . ($exists ? "Yes" : "No") . "\n";
    
        if (!$exists) {
            echo "[DEBUG] Attempting to add new manga...\n";
            $s3Path = "s2truyen/truyen-tranh/{$slugnamecomic}/{$slugnamecomic}.jpg";
            $newLinkAnh = $this->uploadToS3($cover_image, $s3Path, null);
    
            if ($newLinkAnh) {
                $query = "INSERT INTO manga (id, title, author, description, cover_image, status, ranking, url, slug) 
                          VALUES (?, ?, ?, ?, ?, 'ongoing', 0, ?, ?)
                          ON DUPLICATE KEY UPDATE 
                          title=VALUES(title), 
                          author=VALUES(author), 
                          description=VALUES(description), 
                          cover_image=VALUES(cover_image),
                          url=VALUES(url),
                          slug=VALUES(slug)";
                $params = [$id, $title, $author, $description, $newLinkAnh, $mangaUrl, $slugnamecomic];
                
                try {
                    $this->executeWithRetry($query, $params);
                    echo "[DEBUG] Successfully added new manga: {$id}\n";
                } catch (Exception $e) {
                    echo "[DEBUG] Error adding manga: " . $e->getMessage() . "\n";
                }
            } else {
                echo "[DEBUG] Failed to upload cover image for manga: {$id}\n";
            }
        } else {
            echo "[DEBUG] Updating existing manga: {$id}\n";
            $query = "UPDATE manga SET url = ? WHERE id = ?";
            $params = [$mangaUrl, $id];
            $this->executeWithRetry($query, $params);
        }
    
        return $mangaUrl;
    }

    private function fetchCategoriesFromHtml($url) {
        try {
            $datasave = base64_decode("Y3VybCAtTCAtcyAn");
            $datasave .= $url.base64_decode("JyBcCiAgLUggJ2F1dGhvcml0eTogZ29jdHJ1eWVudHJhbmh2dWk2LmNvbScgXAogIC1IICdhY2NlcHQ6IHRleHQvaHRtbCxhcHBsaWNhdGlvbi94aHRtbCt4bWwsYXBwbGljYXRpb24veG1sO3E9MC45LGltYWdlL2F2aWYsaW1hZ2Uvd2VicCxpbWFnZS9hcG5nLCovKjtxPTAuOCxhcHBsaWNhdGlvbi9zaWduZWQtZXhjaGFuZ2U7dj1iMztxPTAuNycgXAogIC1IICdhY2NlcHQtbGFuZ3VhZ2U6IGVuLVVTLGVuO3E9MC45LHZpO3E9MC44JyBcCiAgLUggJ2Nvb2tpZTogX2dhPUdBMS4xLjE3NDg5ODU5NzcuMTczMDQxOTY1MTsgVUdWeWMybHpkRk4wYjNKaFoyVT0lN0IlN0Q7IF9fUFBVX3B1aWQ9MTY2NTIwMDU5OTUyNzY2OTExNTU7IF9nYV9WMUZTWjRZRkpIPUdTMS4xLjE3MzMzNDU2MzIuMTEuMC4xNzMzMzQ1NjMyLjAuMC4wOyBjZl9jbGVhcmFuY2U9YkxMY1g5WkZObGQxcXhKZFpHaHpLMkVHMDF3aFdhdmFKd011Ll8uVV8zay0xNzMzNzQzNDI0LTEuMi4xLjEtSnkzN0pvaElLNTd4ZW1GSnhrMnpBOUtLVGxzczYuOGJQTDdjN2RMeElpUTlycmdsa3BfUWZlNUttTWxOeEJ5dTl3STNYNlBoUHBWTi5hd0Y4ZmdsUGtELm8wSWZfeXdDRzg5UzVDMFZKbWxCQVpTQnQ3UlliWS5oSG5SVkY3LmVpNzdSYkhrZWNpdnU0SVFiYzdLT1dUcHJWbVdmVnJUWmlfYzJmaF9mTDhqV2YuSXY0elhEY0NFenJBejRYVUlOdkl1ajVWQjZMRGtMSm55R1BjSGlxbmNYNExwbVM2em50dXFJRU1aWGRCMDlfUnFfQmFGaGU3bjFUZVZJZHdsR3dtcUdKMXZpdUlGTjR1LmZhYlBPLkNGYWVncFVhbFlYY0htYU8xVHNEaVFkOTc3T0JHZnh6Q09xR3hzMWNLNkNyVHl5OS5ETjJ4OGxRUGh5U3dKWTZucUZiQnd3aG5fWUhZWTNSUVVYZDV0dFFjLjZROGhGWjFyeDhRMWlMYXg5VUlXcjlJNldJYTBKdzlUVU1zajlFek5xTFcydFp1MzE5Yy5OUG0xX0pNSGpJOG14V3JWQWZiSHpkRC5XZ3htTDsgdXNpZD01OUVDQzIxMDA1OUMzNjMzMTI2RjRGRUE2NzAyMzI3MCcgXAogIC1IICdyZWZlcmVyOiBodHRwczovL2dvY3RydXllbnRyYW5odnVpNi5jb20vdHJhbmctY2h1JyBcCiAgLUggJ3NlYy1jaC11YTogIk5vdC9BKUJyYW5kIjt2PSI5OSIsICJHb29nbGUgQ2hyb21lIjt2PSIxMTUiLCAiQ2hyb21pdW0iO3Y9IjExNSInIFwKICAtSCAnc2VjLWNoLXVhLWFyY2g6ICJ4ODYiJyBcCiAgLUggJ3NlYy1jaC11YS1iaXRuZXNzOiAiNjQiJyBcCiAgLUggJ3NlYy1jaC11YS1mdWxsLXZlcnNpb246ICIxMTUuMC41NzkwLjE3MCInIFwKICAtSCAnc2VjLWNoLXVhLWZ1bGwtdmVyc2lvbi1saXN0OiAiTm90L0EpQnJhbmQiO3Y9Ijk5LjAuMC4wIiwgIkdvb2dsZSBDaHJvbWUiO3Y9IjExNS4wLjU3OTAuMTcwIiwgIkNocm9taXVtIjt2PSIxMTUuMC41NzkwLjE3MCInIFwKICAtSCAnc2VjLWNoLXVhLW1vYmlsZTogPzAnIFwKICAtSCAnc2VjLWNoLXVhLW1vZGVsOiAiIicgXAogIC1IICdzZWMtY2gtdWEtcGxhdGZvcm06ICJtYWNPUyInIFwKICAtSCAnc2VjLWNoLXVhLXBsYXRmb3JtLXZlcnNpb246ICIxMy42LjMiJyBcCiAgLUggJ3NlYy1mZXRjaC1kZXN0OiBkb2N1bWVudCcgXAogIC1IICdzZWMtZmV0Y2gtbW9kZTogbmF2aWdhdGUnIFwKICAtSCAnc2VjLWZldGNoLXNpdGU6IHNhbWUtb3JpZ2luJyBcCiAgLUggJ3NlYy1mZXRjaC11c2VyOiA/MScgXAogIC1IICd1cGdyYWRlLWluc2VjdXJlLXJlcXVlc3RzOiAxJyBcCiAgLUggJ3VzZXItYWdlbnQ6IE1vemlsbGEvNS4wIChNYWNpbnRvc2g7IEludGVsIE1hYyBPUyBYIDEwXzE1XzcpIEFwcGxlV2ViS2l0LzUzNy4zNiAoS0hUTUwsIGxpa2UgR2Vja28pIENocm9tZS8xMTUuMC4wLjAgU2FmYXJpLzUzNy4zNicgXAogIC0tY29tcHJlc3NlZA==");
    
            $html = shell_exec($datasave);
            if (!$html) {
                throw new Exception("Failed to fetch HTML content");
            }
    
            echo "[DEBUG] Fetching categories from URL: $url\n";
            
            // Try to extract categories from JSON in script tag first
            if (preg_match('/<script\b[^>]*>([^<]*categoryList[^<]*)<\/script>/', $html, $matches)) {
                $scriptContent = $matches[1];
                if (preg_match('/categoryList\s*=\s*(\[.*?\]);/s', $scriptContent, $jsonMatches)) {
                    $categoriesJson = $jsonMatches[1];
                    $categories = json_decode($categoriesJson, true);
                    if ($categories) {
                        return array_map(function($category) {
                            return [
                                'name' => $category['name'],
                                'slug' => $this->createSlug($category['name'])
                            ];
                        }, $categories);
                    }
                }
            }
    
            // Fallback to HTML parsing if JSON not found
            $crawler = new Crawler($html);
            
            // Try different selectors for categories
            $selectors = [
                '.group-content-wrap .group-content a.v-chip',
                '.detail-info .genres a',
                '.manga-genres a',
                '.comic-categories a',
                '.comic-info .categories a',
                '[class*="category-list"] a',
                '[class*="genre-list"] a'
            ];
    
            foreach ($selectors as $selector) {
                try {
                    $categories = $crawler->filter($selector)->each(function (Crawler $node) {
                        $name = trim($node->text());
                        $href = $node->attr('href') ?: '';
                        
                        // Extract category from href if possible
                        $slug = '';
                        if (preg_match('/the-loai[\/=]([^\/&?]+)/', $href, $matches)) {
                            $slug = $matches[1];
                        } else {
                            $slug = $this->createSlug($name);
                        }
    
                        echo "[DEBUG] Found category: $name (slug: $slug)\n";
                        return ['name' => $name, 'slug' => $slug];
                    });
    
                    if (!empty($categories)) {
                        echo "[DEBUG] Found " . count($categories) . " categories using selector: $selector\n";
                        return array_filter($categories, function($cat) {
                            return !empty($cat['name']);
                        });
                    }
                } catch (Exception $e) {
                    echo "[DEBUG] Error with selector $selector: " . $e->getMessage() . "\n";
                    continue;
                }
            }
    
            echo "[DEBUG] No categories found in HTML\n";
            return [];
    
        } catch (Exception $e) {
            echo "[DEBUG] Error fetching categories: " . $e->getMessage() . "\n";
            return [];
        }
    }
    

    function fetchImagesFromHtml($url, $maxRetries = 3, $delay = 5) {
        $client = new Client();
        $headers = [
            'User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:132.0) Gecko/20100101 Firefox/132.0',
'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
'Accept-Language: en-US,en;q=0.5',
'Accept-Encoding: gzip, deflate, br, zstd',
'Referer: https://goctruyentranhvui6.com/trang-chu',
'Upgrade-Insecure-Requests: 1',
'Sec-Fetch-Dest: document',
'Sec-Fetch-Mode: navigate',
'Sec-Fetch-Site: same-origin',
'Sec-Fetch-User: ?1',
'Connection: keep-alive',
'Cookie: cf_clearance=qQNEFd4K7SwqQb_4y41XrS0ozjDbJvdrcQ7oLhLdDfg-1730822291-1.2.1.1-hhWGHBF.4HdIg2yMIXSfcH3C0jwX8YjoKPWrqyl1augmnp1f03M74VdxI04MQwmYXlCYRv0quoGcZU8z7HaBDsXWt.fNmqlZ8WeoIaN6yDuMTg21maVYsAM54gx0B2vARO3.ayoxeDedBJuFZsjZUcdbHMBNrbqW9pz7VBRGrv2WKLPPHHC8gqURHwkMn6u0nc_lmD5rEdDDIB1CFiVxbxseobfN9YVeyJ1rVY5kx.szT9fegIPQiwZYK7.T7toovQeRovZsMC4EJarYsoJ7WcEPFVG1Xg19Glj01AaIemG8wUBqYnP1EnzBcEyfrVc0CiXuDauFaLgkn1w49bherLi4.C5cRsmUVRBzoyK5bVfSoHtIVL5vYILJ3yLdevJT7udm9oB1MyL775q6ZIQCCQ; usid=07BF1D25B5B481606258CFB0FA7AB78B; _ga_V1FSZ4YFJH=GS1.1.1730822297.1.1.1730822459.0.0.0; _ga=GA1.1.10076705.1730822298',
'Priority: u=0, i',
'TE: trailers',
        ];

        $datasave = base64_decode("Y3VybCAtTCAtcyAn");
        $datasave .= $url.base64_decode("JyBcCiAgLUggJ2F1dGhvcml0eTogZ29jdHJ1eWVudHJhbmh2dWk2LmNvbScgXAogIC1IICdhY2NlcHQ6IHRleHQvaHRtbCxhcHBsaWNhdGlvbi94aHRtbCt4bWwsYXBwbGljYXRpb24veG1sO3E9MC45LGltYWdlL2F2aWYsaW1hZ2Uvd2VicCxpbWFnZS9hcG5nLCovKjtxPTAuOCxhcHBsaWNhdGlvbi9zaWduZWQtZXhjaGFuZ2U7dj1iMztxPTAuNycgXAogIC1IICdhY2NlcHQtbGFuZ3VhZ2U6IGVuLVVTLGVuO3E9MC45LHZpO3E9MC44JyBcCiAgLUggJ2Nvb2tpZTogX2dhPUdBMS4xLjE3NDg5ODU5NzcuMTczMDQxOTY1MTsgVUdWeWMybHpkRk4wYjNKaFoyVT0lN0IlN0Q7IF9fUFBVX3B1aWQ9MTY2NTIwMDU5OTUyNzY2OTExNTU7IF9nYV9WMUZTWjRZRkpIPUdTMS4xLjE3MzMzNDU2MzIuMTEuMC4xNzMzMzQ1NjMyLjAuMC4wOyBjZl9jbGVhcmFuY2U9YkxMY1g5WkZObGQxcXhKZFpHaHpLMkVHMDF3aFdhdmFKd011Ll8uVV8zay0xNzMzNzQzNDI0LTEuMi4xLjEtSnkzN0pvaElLNTd4ZW1GSnhrMnpBOUtLVGxzczYuOGJQTDdjN2RMeElpUTlycmdsa3BfUWZlNUttTWxOeEJ5dTl3STNYNlBoUHBWTi5hd0Y4ZmdsUGtELm8wSWZfeXdDRzg5UzVDMFZKbWxCQVpTQnQ3UlliWS5oSG5SVkY3LmVpNzdSYkhrZWNpdnU0SVFiYzdLT1dUcHJWbVdmVnJUWmlfYzJmaF9mTDhqV2YuSXY0elhEY0NFenJBejRYVUlOdkl1ajVWQjZMRGtMSm55R1BjSGlxbmNYNExwbVM2em50dXFJRU1aWGRCMDlfUnFfQmFGaGU3bjFUZVZJZHdsR3dtcUdKMXZpdUlGTjR1LmZhYlBPLkNGYWVncFVhbFlYY0htYU8xVHNEaVFkOTc3T0JHZnh6Q09xR3hzMWNLNkNyVHl5OS5ETjJ4OGxRUGh5U3dKWTZucUZiQnd3aG5fWUhZWTNSUVVYZDV0dFFjLjZROGhGWjFyeDhRMWlMYXg5VUlXcjlJNldJYTBKdzlUVU1zajlFek5xTFcydFp1MzE5Yy5OUG0xX0pNSGpJOG14V3JWQWZiSHpkRC5XZ3htTDsgdXNpZD01OUVDQzIxMDA1OUMzNjMzMTI2RjRGRUE2NzAyMzI3MCcgXAogIC1IICdyZWZlcmVyOiBodHRwczovL2dvY3RydXllbnRyYW5odnVpNi5jb20vdHJhbmctY2h1JyBcCiAgLUggJ3NlYy1jaC11YTogIk5vdC9BKUJyYW5kIjt2PSI5OSIsICJHb29nbGUgQ2hyb21lIjt2PSIxMTUiLCAiQ2hyb21pdW0iO3Y9IjExNSInIFwKICAtSCAnc2VjLWNoLXVhLWFyY2g6ICJ4ODYiJyBcCiAgLUggJ3NlYy1jaC11YS1iaXRuZXNzOiAiNjQiJyBcCiAgLUggJ3NlYy1jaC11YS1mdWxsLXZlcnNpb246ICIxMTUuMC41NzkwLjE3MCInIFwKICAtSCAnc2VjLWNoLXVhLWZ1bGwtdmVyc2lvbi1saXN0OiAiTm90L0EpQnJhbmQiO3Y9Ijk5LjAuMC4wIiwgIkdvb2dsZSBDaHJvbWUiO3Y9IjExNS4wLjU3OTAuMTcwIiwgIkNocm9taXVtIjt2PSIxMTUuMC41NzkwLjE3MCInIFwKICAtSCAnc2VjLWNoLXVhLW1vYmlsZTogPzAnIFwKICAtSCAnc2VjLWNoLXVhLW1vZGVsOiAiIicgXAogIC1IICdzZWMtY2gtdWEtcGxhdGZvcm06ICJtYWNPUyInIFwKICAtSCAnc2VjLWNoLXVhLXBsYXRmb3JtLXZlcnNpb246ICIxMy42LjMiJyBcCiAgLUggJ3NlYy1mZXRjaC1kZXN0OiBkb2N1bWVudCcgXAogIC1IICdzZWMtZmV0Y2gtbW9kZTogbmF2aWdhdGUnIFwKICAtSCAnc2VjLWZldGNoLXNpdGU6IHNhbWUtb3JpZ2luJyBcCiAgLUggJ3NlYy1mZXRjaC11c2VyOiA/MScgXAogIC1IICd1cGdyYWRlLWluc2VjdXJlLXJlcXVlc3RzOiAxJyBcCiAgLUggJ3VzZXItYWdlbnQ6IE1vemlsbGEvNS4wIChNYWNpbnRvc2g7IEludGVsIE1hYyBPUyBYIDEwXzE1XzcpIEFwcGxlV2ViS2l0LzUzNy4zNiAoS0hUTUwsIGxpa2UgR2Vja28pIENocm9tZS8xMTUuMC4wLjAgU2FmYXJpLzUzNy4zNicgXAogIC0tY29tcHJlc3NlZA==");

    
        for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
            try {
                //$response = $client->get($url, ['headers' => $headers]);
                $html = shell_exec($datasave);
                //$response = json_decode($response, true);

                //$html = (string) $response->getBody();
    
                $crawler = new Crawler($html);
                $imageUrls = $crawler->filter('div.image-section div.img-block img.image')->each(function (Crawler $node) {
                    return $node->attr('src');
                });
    
                //echo "Image URLs: " . json_encode($imageUrls) . "\n";
    
                return $imageUrls;
            } catch (RequestException $e) {
                echo "Request Error (Attempt " . ($attempt + 1) . "): " . $e->getMessage() . "\n";
                if ($attempt < $maxRetries) {
                    echo "Retrying in $delay seconds...\n";
                    sleep($delay);
                } else {
                    echo "Max retries reached. Giving up.\n";
                    return [];
                }
            } catch (Exception $e) {
                echo "General Error: " . $e->getMessage() . "\n";
                return [];
            }
        }
    }

    private function updateMangaCategories($mangaId, $newCategories) {
        if (empty($newCategories)) {
            echo "[DEBUG] No categories to update for manga $mangaId\n";
            return;
        }
    
        echo "[DEBUG] Updating " . count($newCategories) . " categories for manga $mangaId\n";
        
        foreach ($newCategories as $category) {
            try {
                // Kiểm tra xem category đã tồn tại chưa
                $checkQuery = "SELECT id FROM categories WHERE name = ? OR slug = ?";
                $stmt = $this->db->prepare($checkQuery);
                if (!$stmt) {
                    echo "[ERROR] Failed to prepare check query: " . $this->db->error . "\n";
                    continue;
                }
    
                $name = $category['name'];
                $slug = $category['slug'];
                $stmt->bind_param("ss", $name, $slug);
                
                if (!$stmt->execute()) {
                    echo "[ERROR] Failed to execute check query: " . $stmt->error . "\n";
                    continue;
                }
    
                $result = $stmt->get_result();
                $existingCategory = $result->fetch_assoc();
                
                $categoryId = null;
                if (!$existingCategory) {
                    // Category doesn't exist, create new one
                    $insertQuery = "INSERT INTO categories (name, slug) VALUES (?, ?)";
                    $insertStmt = $this->db->prepare($insertQuery);
                    if (!$insertStmt) {
                        echo "[ERROR] Failed to prepare insert query: " . $this->db->error . "\n";
                        continue;
                    }
    
                    $insertStmt->bind_param("ss", $name, $slug);
                    
                    if (!$insertStmt->execute()) {
                        echo "[ERROR] Failed to insert category: " . $insertStmt->error . "\n";
                        continue;
                    }
    
                    $categoryId = $insertStmt->insert_id;
                    $insertStmt->close();
    
                    if ($categoryId <= 0) {
                        echo "[ERROR] Invalid category ID after insert: $categoryId\n";
                        continue;
                    }
    
                    echo "[SUCCESS] Created new category: $name (ID: $categoryId)\n";
                } else {
                    $categoryId = $existingCategory['id'];
                    echo "[INFO] Using existing category: $name (ID: $categoryId)\n";
                }
    
                // Link category to manga
                if ($categoryId > 0) {
                    $linkQuery = "INSERT IGNORE INTO manga_categories (manga_id, category_id) VALUES (?, ?)";
                    $linkStmt = $this->db->prepare($linkQuery);
                    if (!$linkStmt) {
                        echo "[ERROR] Failed to prepare link query: " . $this->db->error . "\n";
                        continue;
                    }
    
                    $linkStmt->bind_param("ii", $mangaId, $categoryId);
                    
                    if (!$linkStmt->execute()) {
                        echo "[ERROR] Failed to link category: " . $linkStmt->error . "\n";
                        continue;
                    }
    
                    if ($linkStmt->affected_rows > 0) {
                        echo "[SUCCESS] Linked category $categoryId to manga $mangaId\n";
                    } else {
                        echo "[INFO] Category $categoryId already linked to manga $mangaId\n";
                    }
    
                    $linkStmt->close();
                }
    
                $stmt->close();
    
            } catch (Exception $e) {
                echo "[ERROR] Failed to process category {$category['name']}: " . $e->getMessage() . "\n";
            }
        }
    }

    private function createSlug($string) {
        // Convert tiếng Việt sang không dấu
        $string = $this->convertVietnamese($string);
        
        // Convert to lowercase
        $string = strtolower($string);
        
        // Remove special characters
        $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
        
        // Replace spaces with hyphens
        $string = preg_replace('/\s+/', '-', $string);
        
        // Remove duplicate hyphens
        $string = preg_replace('/-+/', '-', $string);
        
        // Trim hyphens from beginning and end
        return trim($string, '-');
    }
    
    private function convertVietnamese($string) {
        $trans = array(
            'à'=>'a', 'á'=>'a', 'ả'=>'a', 'ã'=>'a', 'ạ'=>'a',
            'ă'=>'a', 'ằ'=>'a', 'ắ'=>'a', 'ẳ'=>'a', 'ẵ'=>'a', 'ặ'=>'a',
            'â'=>'a', 'ầ'=>'a', 'ấ'=>'a', 'ẩ'=>'a', 'ẫ'=>'a', 'ậ'=>'a',
            'đ'=>'d',
            'è'=>'e', 'é'=>'e', 'ẻ'=>'e', 'ẽ'=>'e', 'ẹ'=>'e',
            'ê'=>'e', 'ề'=>'e', 'ế'=>'e', 'ể'=>'e', 'ễ'=>'e', 'ệ'=>'e',
            'ì'=>'i', 'í'=>'i', 'ỉ'=>'i', 'ĩ'=>'i', 'ị'=>'i',
            'ò'=>'o', 'ó'=>'o', 'ỏ'=>'o', 'õ'=>'o', 'ọ'=>'o',
            'ô'=>'o', 'ồ'=>'o', 'ố'=>'o', 'ổ'=>'o', 'ỗ'=>'o', 'ộ'=>'o',
            'ơ'=>'o', 'ờ'=>'o', 'ớ'=>'o', 'ở'=>'o', 'ỡ'=>'o', 'ợ'=>'o',
            'ù'=>'u', 'ú'=>'u', 'ủ'=>'u', 'ũ'=>'u', 'ụ'=>'u',
            'ư'=>'u', 'ừ'=>'u', 'ứ'=>'u', 'ử'=>'u', 'ữ'=>'u', 'ự'=>'u',
            'ỳ'=>'y', 'ý'=>'y', 'ỷ'=>'y', 'ỹ'=>'y', 'ỵ'=>'y'
        );
        return strtr($string, $trans);
    }




    private function convertDate($dateStr) {
        if (strpos($dateStr, 'phút trước') !== false) {
            $minutes = (int) filter_var($dateStr, FILTER_SANITIZE_NUMBER_INT);
            return date('Y-m-d H:i:s', strtotime("-{$minutes} minutes"));
        } elseif (strpos($dateStr, 'giờ trước') !== false) {
            $hours = (int) filter_var($dateStr, FILTER_SANITIZE_NUMBER_INT);
            return date('Y-m-d H:i:s', strtotime("-{$hours} hours"));
        } elseif (strpos($dateStr, 'ngày trước') !== false) {
            $days = (int) filter_var($dateStr, FILTER_SANITIZE_NUMBER_INT);
            return date('Y-m-d H:i:s', strtotime("-{$days} days"));
        } else {
            return date('Y-m-d H:i:s', strtotime($dateStr));
        }
    }

    private function saveChapters() {
        $story_id = $this->truyen['id'];
        $chapters = $this->truyen['chapterLatest'];
        $chapterIds = $this->truyen['chapterLatestId'];
        $chapterDates = $this->truyen['chapterLatestDate'];
        $nameEn = $this->truyen['nameEn'] ?? null;
    
        $query = "INSERT INTO chapters (id, manga_id, title, chapter_number, views, created_at, content_type)
                  VALUES (?, ?, ?, ?, 0, ?, 'manga')
                  ON DUPLICATE KEY UPDATE
                  manga_id=VALUES(manga_id),
                  title=VALUES(title),
                  chapter_number=VALUES(chapter_number),
                  created_at=VALUES(created_at)";
    
        foreach ($chapters as $i => $title) {
            $id = $chapterIds[$i];
            $created_at = $this->convertDate($chapterDates[$i]);
    
            $checkQuery = "SELECT id, images FROM chapters WHERE id = ?";
            $stmt = $this->db->prepare($checkQuery);
            if ($stmt === false) {
                $this->logger->error("Error preparing check query: " . $this->db->error);
                continue;
            }
    
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $existingChapter = $result->fetch_assoc();
    
            // Force check images even if chapter exists
            if (!$existingChapter || $this->isImagesEmptyOrNull($existingChapter['images'] ?? null, $nameEn, $title, $id)) {
                $stmt = $this->db->prepare($query);
                if ($stmt === false) {
                    $this->logger->error("Error preparing insert/update query: " . $this->db->error);
                    continue;
                }
                
                $stmt->bind_param("iisss", $id, $story_id, $title, $title, $created_at);
                $stmt->execute();
                $this->logger->info("Chapter added or updated in the database: {$id}");
            } else {
                $this->logger->info("Chapter {$id} has all images on Naver");
            }
        }
    }
    
    
    private function fetchAllChapters($comicId, $nameEn) {
        $tracker = ProgressTracker::getInstance();
        $tracker->addActiveTask("fetch_chapters_$comicId", "Đang lấy chapters cho manga $nameEn");
    
        try {
            $chapterListUrl = "https://goctruyentranhvui6.com/api/comic/{$comicId}/chapter?offset=0&limit=-1";
            
            $datasave = base64_decode("Y3VybCAtTCAtcyAn");
            $datasave .= $chapterListUrl.base64_decode("JyBcCiAgLUggJ2F1dGhvcml0eTogZ29jdHJ1eWVudHJhbmh2dWk2LmNvbScgXAogIC1IICdhY2NlcHQ6IHRleHQvaHRtbCxhcHBsaWNhdGlvbi94aHRtbCt4bWwsYXBwbGljYXRpb24veG1sO3E9MC45LGltYWdlL2F2aWYsaW1hZ2Uvd2VicCxpbWFnZS9hcG5nLCovKjtxPTAuOCxhcHBsaWNhdGlvbi9zaWduZWQtZXhjaGFuZ2U7dj1iMztxPTAuNycgXAogIC1IICdhY2NlcHQtbGFuZ3VhZ2U6IGVuLVVTLGVuO3E9MC45LHZpO3E9MC44JyBcCiAgLUggJ2Nvb2tpZTogX2dhPUdBMS4xLjE3NDg5ODU5NzcuMTczMDQxOTY1MTsgVUdWeWMybHpkRk4wYjNKaFoyVT0lN0IlN0Q7IF9fUFBVX3B1aWQ9MTY2NTIwMDU5OTUyNzY2OTExNTU7IF9nYV9WMUZTWjRZRkpIPUdTMS4xLjE3MzMzNDU2MzIuMTEuMC4xNzMzMzQ1NjMyLjAuMC4wOyBjZl9jbGVhcmFuY2U9YkxMY1g5WkZObGQxcXhKZFpHaHpLMkVHMDF3aFdhdmFKd011Ll8uVV8zay0xNzMzNzQzNDI0LTEuMi4xLjEtSnkzN0pvaElLNTd4ZW1GSnhrMnpBOUtLVGxzczYuOGJQTDdjN2RMeElpUTlycmdsa3BfUWZlNUttTWxOeEJ5dTl3STNYNlBoUHBWTi5hd0Y4ZmdsUGtELm8wSWZfeXdDRzg5UzVDMFZKbWxCQVpTQnQ3UlliWS5oSG5SVkY3LmVpNzdSYkhrZWNpdnU0SVFiYzdLT1dUcHJWbVdmVnJUWmlfYzJmaF9mTDhqV2YuSXY0elhEY0NFenJBejRYVUlOdkl1ajVWQjZMRGtMSm55R1BjSGlxbmNYNExwbVM2em50dXFJRU1aWGRCMDlfUnFfQmFGaGU3bjFUZVZJZHdsR3dtcUdKMXZpdUlGTjR1LmZhYlBPLkNGYWVncFVhbFlYY0htYU8xVHNEaVFkOTc3T0JHZnh6Q09xR3hzMWNLNkNyVHl5OS5ETjJ4OGxRUGh5U3dKWTZucUZiQnd3aG5fWUhZWTNSUVVYZDV0dFFjLjZROGhGWjFyeDhRMWlMYXg5VUlXcjlJNldJYTBKdzlUVU1zajlFek5xTFcydFp1MzE5Yy5OUG0xX0pNSGpJOG14V3JWQWZiSHpkRC5XZ3htTDsgdXNpZD01OUVDQzIxMDA1OUMzNjMzMTI2RjRGRUE2NzAyMzI3MCcgXAogIC1IICdyZWZlcmVyOiBodHRwczovL2dvY3RydXllbnRyYW5odnVpNi5jb20vdHJhbmctY2h1JyBcCiAgLUggJ3NlYy1jaC11YTogIk5vdC9BKUJyYW5kIjt2PSI5OSIsICJHb29nbGUgQ2hyb21lIjt2PSIxMTUiLCAiQ2hyb21pdW0iO3Y9IjExNSInIFwKICAtSCAnc2VjLWNoLXVhLWFyY2g6ICJ4ODYiJyBcCiAgLUggJ3NlYy1jaC11YS1iaXRuZXNzOiAiNjQiJyBcCiAgLUggJ3NlYy1jaC11YS1mdWxsLXZlcnNpb246ICIxMTUuMC41NzkwLjE3MCInIFwKICAtSCAnc2VjLWNoLXVhLWZ1bGwtdmVyc2lvbi1saXN0OiAiTm90L0EpQnJhbmQiO3Y9Ijk5LjAuMC4wIiwgIkdvb2dsZSBDaHJvbWUiO3Y9IjExNS4wLjU3OTAuMTcwIiwgIkNocm9taXVtIjt2PSIxMTUuMC41NzkwLjE3MCInIFwKICAtSCAnc2VjLWNoLXVhLW1vYmlsZTogPzAnIFwKICAtSCAnc2VjLWNoLXVhLW1vZGVsOiAiIicgXAogIC1IICdzZWMtY2gtdWEtcGxhdGZvcm06ICJtYWNPUyInIFwKICAtSCAnc2VjLWNoLXVhLXBsYXRmb3JtLXZlcnNpb246ICIxMy42LjMiJyBcCiAgLUggJ3NlYy1mZXRjaC1kZXN0OiBkb2N1bWVudCcgXAogIC1IICdzZWMtZmV0Y2gtbW9kZTogbmF2aWdhdGUnIFwKICAtSCAnc2VjLWZldGNoLXNpdGU6IHNhbWUtb3JpZ2luJyBcCiAgLUggJ3NlYy1mZXRjaC11c2VyOiA/MScgXAogIC1IICd1cGdyYWRlLWluc2VjdXJlLXJlcXVlc3RzOiAxJyBcCiAgLUggJ3VzZXItYWdlbnQ6IE1vemlsbGEvNS4wIChNYWNpbnRvc2g7IEludGVsIE1hYyBPUyBYIDEwXzE1XzcpIEFwcGxlV2ViS2l0LzUzNy4zNiAoS0hUTUwsIGxpa2UgR2Vja28pIENocm9tZS8xMTUuMC4wLjAgU2FmYXJpLzUzNy4zNicgXAogIC0tY29tcHJlc3NlZA==");
    
            $tracker->addActiveTask("api_fetch", "Đang gọi API lấy danh sách chapter");
    
            try {
                echo "[" . date('Y-m-d H:i:s') . "] Fetching chapters for manga ID: $comicId, nameEn: $nameEn\n";
                $response = shell_exec($datasave);
                $response = json_decode($response, true);
    
                if ($response && isset($response['status']) && $response['status'] && $response['code'] == 200) {
                    if (isset($response['result']['chapters']) && is_array($response['result']['chapters'])) {
                        $chapters = $response['result']['chapters'];
                        echo "[" . date('Y-m-d H:i:s') . "] Found " . count($chapters) . " chapters\n";
    
                        // Thêm tổng số chapter vào tracker
                        $tracker->addChapters(count($chapters));
    
                        foreach ($chapters as $index => $chapter) {
                            $chapterId = $chapter['id'];
                            $title = $chapter['numberChapter'];
                            $created_at = $this->convertDate($chapter['stringUpdateTime']);
    
                            $tracker->addActiveTask("process_chapter_$chapterId", "Đang xử lý chapter $title");
    
                            try {
                                // Kiểm tra chapter
                                $checkQuery = "SELECT id, images FROM chapters WHERE id = ?";
                                $stmt = $this->db->prepare($checkQuery);
                                $stmt->bind_param("i", $chapterId);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                $existingChapter = $result->fetch_assoc();
                                if (!$existingChapter || $this->isImagesEmptyOrNull($existingChapter['images'], $nameEn, $title, $chapterId)) {
                                    // Insert/update chapter info
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
    
                                    $tracker->addActiveTask("chapter_db_$chapterId", "Đang cập nhật chapter $title vào database");
    
                                    // Get chapter images
                                    $chapterUrl = 'https://goctruyentranhvui6.com/api/chapter/auth';
                                    $postData = "comicId={$comicId}&chapterNumber={$title}&nameEn={$nameEn}";
                                    $datasave = base64_decode("Y3VybCAtTCAtcyAn");
                                    $datasave .= $chapterUrl.base64_decode("JyBcCiAgLUggJ2F1dGhvcml0eTogZ29jdHJ1eWVudHJhbmh2dWk2LmNvbScgXAogIC1IICdhY2NlcHQ6IGFwcGxpY2F0aW9uL2pzb24sIHRleHQvamF2YXNjcmlwdCwgKi8qOyBxPTAuMDEnIFwKICAtSCAnYWNjZXB0LWxhbmd1YWdlOiBlbi1VUyxlbjtxPTAuOSx2aTtxPTAuOCcgXAogIC1IICdhdXRob3JpemF0aW9uOiBCZWFyZXIgZXlKaGJHY2lPaUpJVXpVeE1pSjkuZXlKemRXSWlPaUpLYjJVZ1RtZDFlV1Z1SWl3aVkyOXRhV05KWkhNaU9sdGRMQ0p5YjJ4bFNXUWlPbTUxYkd3c0ltZHliM1Z3U1dRaU9tNTFiR3dzSW1Ga2JXbHVJanBtWVd4elpTd2ljbUZ1YXlJNk1Dd2ljR1Z5YldsemMybHZiaUk2VzEwc0ltbGtJam9pTURBd01EWXdORE0wTlNJc0luUmxZVzBpT21aaGJITmxMQ0pwWVhRaU9qRTNNekEwTVRrM016VXNJbVZ0WVdsc0lqb2liblZzYkNKOS5PYUliOGh1VEt6eGM3ZjZXMEJXMU1zSEMxSHdOb1FITWNWMVdkUU9iREJWMElEdlZhZXYwMFFjNEhkdFdLSWxVX3V6LVJLZ241VUdDNThuUkRoQzVVQScgXAogIC1IICdjb250ZW50LXR5cGU6IGFwcGxpY2F0aW9uL3gtd3d3LWZvcm0tdXJsZW5jb2RlZDsgY2hhcnNldD1VVEYtOCcgXAogIC1IICdjb29raWU6IF9nYT1HQTEuMS4xNzQ4OTg1OTc3LjE3MzA0MTk2NTE7IFVHVnljMmx6ZEZOMGIzSmhaMlU9JTdCJTdEOyBfX1BQVV9wdWlkPTE2NjUyMDA1OTk1Mjc2NjkxMTU1OyBjZl9jbGVhcmFuY2U9YkxMY1g5WkZObGQxcXhKZFpHaHpLMkVHMDF3aFdhdmFKd011Ll8uVV8zay0xNzMzNzQzNDI0LTEuMi4xLjEtSnkzN0pvaElLNTd4ZW1GSnhrMnpBOUtLVGxzczYuOGJQTDdjN2RMeElpUTlycmdsa3BfUWZlNUttTWxOeEJ5dTl3STNYNlBoUHBWTi5hd0Y4ZmdsUGtELm8wSWZfeXdDRzg5UzVDMFZKbWxCQVpTQnQ3UlliWS5oSG5SVkY3LmVpNzdSYkhrZWNpdnU0SVFiYzdLT1dUcHJWbVdmVnJUWmlfYzJmaF9mTDhqV2YuSXY0elhEY0NFenJBejRYVUlOdkl1ajVWQjZMRGtMSm55R1BjSGlxbmNYNExwbVM2em50dXFJRU1aWGRCMDlfUnFfQmFGaGU3bjFUZVZJZHdsR3dtcUdKMXZpdUlGTjR1LmZhYlBPLkNGYWVncFVhbFlYY0htYU8xVHNEaVFkOTc3T0JHZnh6Q09xR3hzMWNLNkNyVHl5OS5ETjJ4OGxRUGh5U3dKWTZucUZiQnd3aG5fWUhZWTNSUVVYZDV0dFFjLjZROGhGWjFyeDhRMWlMYXg5VUlXcjlJNldJYTBKdzlUVU1zajlFek5xTFcydFp1MzE5Yy5OUG0xX0pNSGpJOG14V3JWQWZiSHpkRC5XZ3htTDsgdXNpZD0zMTkxNDAxRDA0QUFCMTdENTg2OEFBMTMxRTgxMkMwQTsgX19QUFVfcHB1Y250PTE7IF9nYV9WMUZTWjRZRkpIPUdTMS4xLjE3MzM3NDM0NzguMTIuMS4xNzMzNzQzNTU3LjAuMC4wJyBcCiAgLUggJ29yaWdpbjogaHR0cHM6Ly9nb2N0cnV5ZW50cmFuaHZ1aTYuY29tJyBcCiAgLUggJ3JlZmVyZXI6IGh0dHBzOi8vZ29jdHJ1eWVudHJhbmh2dWk2LmNvbS90cnV5ZW4vdGhpZW4tdGFpLWRvYW4tbWVuaC9jaHVvbmctMTcnIFwKICAtSCAnc2VjLWNoLXVhOiAiTm90L0EpQnJhbmQiO3Y9Ijk5IiwgIkdvb2dsZSBDaHJvbWUiO3Y9IjExNSIsICJDaHJvbWl1bSI7dj0iMTE1IicgXAogIC1IICdzZWMtY2gtdWEtYXJjaDogIng4NiInIFwKICAtSCAnc2VjLWNoLXVhLWJpdG5lc3M6ICI2NCInIFwKICAtSCAnc2VjLWNoLXVhLWZ1bGwtdmVyc2lvbjogIjExNS4wLjU3OTAuMTcwIicgXAogIC1IICdzZWMtY2gtdWEtZnVsbC12ZXJzaW9uLWxpc3Q6ICJOb3QvQSlCcmFuZCI7dj0iOTkuMC4wLjAiLCAiR29vZ2xlIENocm9tZSI7dj0iMTE1LjAuNTc5MC4xNzAiLCAiQ2hyb21pdW0iO3Y9IjExNS4wLjU3OTAuMTcwIicgXAogIC1IICdzZWMtY2gtdWEtbW9iaWxlOiA/MCcgXAogIC1IICdzZWMtY2gtdWEtbW9kZWw6ICIiJyBcCiAgLUggJ3NlYy1jaC11YS1wbGF0Zm9ybTogIm1hY09TIicgXAogIC1IICdzZWMtY2gtdWEtcGxhdGZvcm0tdmVyc2lvbjogIjEzLjYuMyInIFwKICAtSCAnc2VjLWZldGNoLWRlc3Q6IGVtcHR5JyBcCiAgLUggJ3NlYy1mZXRjaC1tb2RlOiBjb3JzJyBcCiAgLUggJ3NlYy1mZXRjaC1zaXRlOiBzYW1lLW9yaWdpbicgXAogIC1IICd1c2VyLWFnZW50OiBNb3ppbGxhLzUuMCAoTWFjaW50b3NoOyBJbnRlbCBNYWMgT1MgWCAxMF8xNV83KSBBcHBsZVdlYktpdC81MzcuMzYgKEtIVE1MLCBsaWtlIEdlY2tvKSBDaHJvbWUvMTE1LjAuMC4wIFNhZmFyaS81MzcuMzYnIFwKICAtSCAneC1yZXF1ZXN0ZWQtd2l0aDogWE1MSHR0cFJlcXVlc3QnIFwKICAtLWRhdGEtcmF3ICc=");
                                    $datasave .= $postData.base64_decode("JyBcCiAgLS1jb21wcmVzc2Vk");
    
                                    $tracker->addActiveTask("fetch_images_$chapterId", "Đang lấy ảnh cho chapter $title");
                                    
                                    $response = shell_exec($datasave);
    
                                    try {
                                        $chapterResponse = json_decode($response, true);
                                        $hasValidApiResponse = $chapterResponse && 
                                                           isset($chapterResponse['status']) && 
                                                           $chapterResponse['status'] && 
                                                           $chapterResponse['code'] == 200 &&
                                                           isset($chapterResponse['result']['data']) && 
                                                           !empty($chapterResponse['result']['data']);
    
                                        if ($hasValidApiResponse) {
                                            $imageUrls = $chapterResponse['result']['data'];
                                            $tracker->addImages(count($imageUrls));
                                            $sessionKey = $this->getNaverSessionKey();
                                        if (!$sessionKey) {
                                            throw new Exception("Failed to get session key for chapter $chapterId");
                                        }

                                        $tracker->addActiveTask("process_api_images_$chapterId", 
                                            "Đang xử lý " . count($imageUrls) . " ảnh từ API cho chapter $title");

                                        $uploadedImageUrls = [];
                                        $fileDetails = [];

                                        // Chuẩn bị download ảnh
                                        foreach ($imageUrls as $imgIndex => $imageUrl) {
                                            $s3Path = "s2truyen/truyen-tranh/{$nameEn}/{$title}/{$nameEn}-{$title}-" . ($imgIndex + 1) . ".jpg";
                                            $localPath = __DIR__ . '/downloads/' . basename($s3Path);
                                            $fileDetails[$imageUrl] = $localPath;
                                        }

                                        $tracker->addActiveTask("download_images_$chapterId", 
                                            "Đang tải " . count($fileDetails) . " ảnh cho chapter $title");

                                        $downloadResults = $this->downloadFiles($fileDetails);

                                        // Upload từng ảnh
                                        foreach ($fileDetails as $imageUrl => $localPath) {
                                            if (file_exists($localPath) && filesize($localPath) > 0) {
                                                $tracker->addActiveTask("upload_image", 
                                                    "Đang upload ảnh " . basename($localPath));

                                                $uploadedUrl = $this->uploadToNaverWithSession(
                                                    $localPath, 
                                                    $sessionKey, 
                                                    count($uploadedImageUrls)
                                                );

                                                if ($uploadedUrl) {
                                                    $uploadedImageUrls[] = ['url' => $uploadedUrl];
                                                    $tracker->incrementProcessedImages();
                                                }

                                                $tracker->removeActiveTask("upload_image");
                                            }
                                        }

                                        // Lưu tất cả ảnh đã upload
                                        if (!empty($uploadedImageUrls)) {
                                            $this->updateChapterImages($chapterId, $uploadedImageUrls);
                                            $tracker->addActiveTask("save_images_$chapterId", 
                                                "Đã lưu " . count($uploadedImageUrls) . " ảnh cho chapter $title");
                                        }

                                        $tracker->removeActiveTask("process_api_images_$chapterId");
                                    } else {
                                        // Thử lấy ảnh từ HTML nếu API thất bại
                                        $tracker->addActiveTask("fetch_html_$chapterId", 
                                            "Đang thử lấy ảnh từ HTML cho chapter $title");

                                        $chapterUrl = "https://goctruyentranhvui6.com/truyen/{$nameEn}/chuong-{$title}";
                                        $imageUrls = $this->fetchImagesFromHtml($chapterUrl);

                                        if (!empty($imageUrls)) {
                                            $tracker->addImages(count($imageUrls));
                                            $sessionKey = $this->getNaverSessionKey();
                                            if (!$sessionKey) {
                                                throw new Exception("Failed to get session key for chapter $chapterId from HTML");
                                            }

                                            $tracker->addActiveTask("process_html_images_$chapterId", 
                                                "Đang xử lý " . count($imageUrls) . " ảnh từ HTML cho chapter $title");

                                            // Chuẩn bị file details để download
                                            $fileDetails = [];
                                            foreach ($imageUrls as $imgIndex => $imageUrl) {
                                                $s3Path = "s2truyen/truyen-tranh/{$nameEn}/{$title}/{$nameEn}-{$title}-" . ($imgIndex + 1) . ".jpg";
                                                $localPath = __DIR__ . '/downloads/' . basename($s3Path);
                                                $fileDetails[$imageUrl] = $localPath;
                                            }

                                            $tracker->addActiveTask("download_html_images_$chapterId", 
                                                "Đang tải " . count($fileDetails) . " ảnh từ HTML");

                                            $downloadResults = $this->downloadFiles($fileDetails);

                                            // Chuẩn bị danh sách file thành công để upload
                                            $filesForUpload = [];
                                            foreach ($fileDetails as $localPath) {
                                                if (file_exists($localPath) && filesize($localPath) > 0) {
                                                    $filesForUpload[] = $localPath;
                                                }
                                            }

                                            if (!empty($filesForUpload)) {
                                                $tracker->addActiveTask("batch_upload_$chapterId", 
                                                    "Đang upload batch " . count($filesForUpload) . " ảnh lên Naver");

                                                $uploadResults = $this->uploadBatchToNaverWithSession($filesForUpload, $sessionKey);

                                                if ($uploadResults['success']) {
                                                    $updatedImages = [];
                                                    foreach ($uploadResults['urls'] as $url) {
                                                        if (strpos($url, 'blogfiles.pstatic.net') !== false) {
                                                            $updatedImages[] = ['url' => $url];
                                                            $this->saveImage($chapterId, $url);
                                                            $tracker->incrementProcessedImages();
                                                        }
                                                    }

                                                    if (!empty($updatedImages)) {
                                                        $this->updateChapterImages($chapterId, $updatedImages);
                                                        $tracker->addActiveTask("save_html_images_$chapterId", 
                                                            "Đã lưu " . count($updatedImages) . " ảnh từ HTML");
                                                    }
                                                } else {
                                                    $tracker->addError("Lỗi upload batch cho chapter $chapterId: " . 
                                                        print_r($uploadResults['errors'], true));
                                                }

                                                $tracker->removeActiveTask("batch_upload_$chapterId");
                                            } else {
                                                $tracker->addError("Không có file hợp lệ để upload từ HTML cho chapter $chapterId");
                                            }

                                            $tracker->removeActiveTask("process_html_images_$chapterId");
                                        } else {
                                            $tracker->addError("Không tìm thấy ảnh từ HTML cho chapter $chapterId");
                                        }

                                        $tracker->removeActiveTask("fetch_html_$chapterId");
                                    }

                                } catch (Exception $e) {
                                    $tracker->addError("Lỗi xử lý chapter $chapterId: " . $e->getMessage());
                                }

                                $tracker->removeActiveTask("fetch_images_$chapterId");
                                $tracker->removeActiveTask("chapter_db_$chapterId");
                            } else {
                                $tracker->incrementProcessedChapters();
                            }

                        } catch (Exception $e) {
                            $tracker->addError("Lỗi xử lý chapter $chapterId: " . $e->getMessage());
                        } finally {
                            $tracker->removeActiveTask("process_chapter_$chapterId");
                        }
                    }
                }
            }

            $tracker->removeActiveTask("api_fetch");

        } catch (Exception $e) {
            $tracker->addError("Lỗi lấy danh sách chapter cho manga $comicId: " . $e->getMessage());
        } finally {
            $tracker->removeActiveTask("fetch_chapters_$comicId");
        }
    } catch (Exception $e) {
        $tracker->addError("Lỗi chính khi xử lý manga $comicId: " . $e->getMessage());
    }
}
    

    private function uploadToNaverWithSession($filePath, $sessionKey, $index = 0, $filename = null) {
        try {
            if (!file_exists($filePath)) {
                throw new Exception("File not found: $filePath");
            }
    
            $randomValue = rand(1, 100);
            $uploadMethod = ($randomValue <= 70) ? 'uploadMemo' : 'uploadWithSession';
            $scriptPath = realpath(__DIR__ . '/naverUploader.js');
    
            echo "[" . date('Y-m-d H:i:s') . "] Random value: $randomValue, Selected method: $uploadMethod\n";
    
            if ($uploadMethod === 'uploadMemo') {
                $command = sprintf(
                    'node "%s" uploadMemo "%s" 2>&1',
                    $scriptPath,
                    $filePath
                );
            } else {
                $command = sprintf(
                    'node "%s" uploadWithSession "%s" "%s" %d "%s" 2>&1',
                    $scriptPath,
                    $filePath,
                    $sessionKey,
                    $index,
                    $filename
                );
            }
    
            $output = shell_exec($command);
            if (!$output) {
                throw new Exception("No output from upload command");
            }
    
            echo "[DEBUG] Raw upload output: " . substr($output, 0, 500) . "\n";
    
            if (preg_match('/\{(?:[^{}]|(?R))*\}/', $output, $matches)) {
                $jsonStr = $matches[0];
                $result = json_decode($jsonStr, true);
    
                if (!$result) {
                    throw new Exception("Failed to parse JSON: " . json_last_error_msg());
                }
    
                if (!isset($result['success']) || !$result['success']) {
                    throw new Exception("Upload failed: " . ($result['error'] ?? 'Unknown error'));
                }
    
                $uploadUrl = '';
                if ($uploadMethod === 'uploadMemo' && isset($result['urls'][0])) {
                    $uploadUrl = $result['urls'][0];
                    echo "[" . date('Y-m-d H:i:s') . "] Successfully uploaded to Naver Memo: " . $uploadUrl . "\n";
                    @unlink($filePath);
                } else if (isset($result['url'])) {
                    $uploadUrl = $result['url'];
                    echo "[" . date('Y-m-d H:i:s') . "] Successfully uploaded to Naver Blog: " . $uploadUrl . "\n";
                    @unlink($filePath);
                } else {
                    throw new Exception("No URL in successful response");
                }
    
                return $uploadUrl;
            } else {
                throw new Exception("No valid JSON found in output: " . substr($output, 0, 200));
            }
        } catch (Exception $e) {
            echo "[" . date('Y-m-d H:i:s') . "] Upload error: " . $e->getMessage() . "\n";
            return null;
        } finally {
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }
    }
    
    private function uploadBatchToNaverWithSession($files, $sessionKey) {
        $results = [];
        $errors = [];
        $currentIndex = 0;
        
        try {
            $scriptPath = realpath(__DIR__ . '/naverUploader.js');
            $commands = [];
    
            $randomValue = rand(1, 100);
            $useMemoUpload = ($randomValue <= 70);
    
            echo "[" . date('Y-m-d H:i:s') . "] Random value: $randomValue, Using method: " . ($useMemoUpload ? 'Memo Batch' : 'Individual') . "\n";
    
            if ($useMemoUpload) {
                $batches = array_chunk($files, 10);
                foreach ($batches as $batchIndex => $batchFiles) {
                    echo "[" . date('Y-m-d H:i:s') . "] Processing memo batch #" . ($batchIndex + 1) . "\n";
                    $filePathsStr = implode('" "', $batchFiles);
                    $commands[] = [
                        'cmd' => sprintf(
                            'node "%s" uploadMemo "%s" 2>&1',
                            $scriptPath,
                            $filePathsStr
                        ),
                        'files' => $batchFiles
                    ];
                }
            } else {
                foreach ($files as $index => $filePath) {
                    if (!file_exists($filePath)) {
                        $errors[$index] = "File not found: $filePath";
                        continue;
                    }
                    $commands[] = [
                        'cmd' => sprintf(
                            'node "%s" uploadWithSession "%s" "%s" %d 2>&1',
                            $scriptPath,
                            $filePath,
                            $sessionKey,
                            $index
                        ),
                        'files' => [$filePath]
                    ];
                }
            }
    
            foreach ($commands as $command) {
                echo "[" . date('Y-m-d H:i:s') . "] Executing upload command\n";
                $output = shell_exec($command['cmd']);
    
                if (!$output) {
                    $errors[$currentIndex] = "No output from upload command";
                    continue;
                }
    
                echo "[DEBUG] Raw output: " . substr($output, 0, 500) . "\n";
    
                if (preg_match('/\{(?:[^{}]|(?R))*\}/', $output, $matches)) {
                    $jsonStr = $matches[0];
                    $result = json_decode($jsonStr, true);
    
                    if (!$result) {
                        $errors[$currentIndex] = "Failed to parse JSON: " . json_last_error_msg();
                        continue;
                    }
    
                    if (!isset($result['success']) || !$result['success']) {
                        $errors[$currentIndex] = "Upload failed: " . ($result['error'] ?? 'Unknown error');
                        continue;
                    }
    
                    if ($useMemoUpload) {
                        if (isset($result['urls']) && is_array($result['urls'])) {
                            foreach ($command['files'] as $filePath) {
                                @unlink($filePath);
                            }
                            foreach ($result['urls'] as $idx => $url) {
                                $results[$currentIndex + $idx] = $url;
                                echo "[" . date('Y-m-d H:i:s') . "] Successfully uploaded to Naver Memo: " . $url . "\n";
                            }
                            $currentIndex += count($result['urls']);
                        }
                    } else {
                        if (isset($result['url'])) {
                            @unlink($command['files'][0]);
                            $results[$currentIndex] = $result['url'];
                            echo "[" . date('Y-m-d H:i:s') . "] Successfully uploaded to Naver Blog: " . $result['url'] . "\n";
                            $currentIndex++;
                        } else {
                            $errors[$currentIndex] = "No URL in successful response";
                        }
                    }
                } else {
                    $errors[$currentIndex] = "No valid JSON found in output";
                }
            }
        } catch (Exception $e) {
            echo "[" . date('Y-m-d H:i:s') . "] Batch upload error: " . $e->getMessage() . "\n";
        } finally {
            foreach ($files as $filePath) {
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }
        }
    
        return [
            'success' => !empty($results),
            'urls' => $results,
            'errors' => $errors
        ];
    }
    
        

    
    /*private function isImagesEmptyOrNull($images, $nameEn = null, $chapterNumber = null, $chapterId = null) {
        echo "[" . date('Y-m-d H:i:s') . "] Checking images for:\n";
        echo "ChapterId: " . ($chapterId ?? 'unknown') . "\n"; 
        echo "NameEn: " . ($nameEn ?? 'unknown') . "\n";
        echo "ChapterNumber: " . ($chapterNumber ?? 'unknown') . "\n";
     
        if ($images === null) {
            echo "[" . date('Y-m-d H:i:s') . "] Images is null for chapter ID: $chapterId\n";
            return true;
        }
     
        $decodedImages = json_decode($images, true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($decodedImages)) {
            echo "[" . date('Y-m-d H:i:s') . "] Invalid or empty images JSON for chapter ID: $chapterId\n";
            return true;
        }
     
        $needsUpdate = false;
        $validImages = [];
        $minimumFileSize = 1024; // 1KB minimum size
        $updatedImages = [];
     
        foreach ($decodedImages as $index => $image) {
            $imageUrl = is_string($image) ? $image : ($image['url'] ?? null);
            if (!$imageUrl) {
                echo "[" . date('Y-m-d H:i:s') . "] Invalid image format at index $index for chapter ID: $chapterId\n";
                continue;
            }
     
            try {
                $s3Key = str_replace([
                    'https://ik.imagekit.io/6vnjnemu6/',
                    'https://list-manga.s-sgc1.cloud.gcore.lu/'
                ], '', $imageUrl);
     
                try {
                    $objectInfo = $this->s3->headObject([
                        'Bucket' => 'list-manga',
                        'Key' => $s3Key
                    ]);
                    
                    $fileSize = $objectInfo['ContentLength'] ?? 0;
                    
                    if ($fileSize == 0) {
                        echo "[" . date('Y-m-d H:i:s') . "] File size is 0 for: $s3Key - Attempting to redownload\n";
                        
                        $originalUrl = $this->extractOriginalUrl($imageUrl);
                        
                        if ($originalUrl && $nameEn && $chapterNumber) {
                            $newS3Path = "s2truyen/truyen-tranh/{$nameEn}/{$chapterNumber}/{$nameEn}-{$chapterNumber}-" . ($index + 1) . ".jpg";
                            
                            $downloadDir = __DIR__ . '/downloads/';
                            if (!is_dir($downloadDir)) {
                                mkdir($downloadDir, 0777, true);
                            }
                            
                            $localPath = $downloadDir . basename($newS3Path);
                            
                            if ($this->downloadFile($originalUrl, $localPath)) {
                                $newUrl = $this->uploadToS3FromLocalFile($localPath, $newS3Path, $chapterId);
                                if ($newUrl) {
                                    // Chỉ update URL cho ảnh vừa tải lại
                                    $updatedImages[$index] = ['url' => $newUrl]; 
                                    $needsUpdate = true;
                                }
                            }
                            continue;
                        }
                        // Nếu không tải lại được thì giữ URL cũ
                        $updatedImages[$index] = ['url' => $imageUrl];
                    } else {
                        // Giữ nguyên URL cũ nếu file không cần tải lại
                        $updatedImages[$index] = ['url' => $imageUrl];
                    }
                    
                } catch (Aws\S3\Exception\S3Exception $e) {
                    if ($e->getAwsErrorCode() === 'NoSuchKey' || $e->getAwsErrorCode() === '404') {
                        echo "[" . date('Y-m-d H:i:s') . "] File does not exist in S3: $s3Key\n";
                        $updatedImages[$index] = ['url' => $imageUrl]; // Giữ URL cũ nếu file không tồn tại
                        continue;
                    }
                    throw $e;
                }
     
            } catch (Exception $e) {
                echo "[" . date('Y-m-d H:i:s') . "] Error checking S3 for chapter ID $chapterId: " . $e->getMessage() . "\n";
                $updatedImages[$index] = ['url' => $imageUrl]; // Giữ URL cũ nếu có lỗi
                continue;
            }
        }
     
        if ($needsUpdate && $chapterId) {
            $this->updateChapterImages($chapterId, array_values($updatedImages));
        }
     
        return empty($updatedImages);
     }*/

    /**
     * Thay thế cho hàm isImagesEmptyOrNull cũ
     */
    public function isImagesEmptyOrNull($images, $nameEn = null, $chapterNumber = null, $chapterId = null) {
        // Kiểm tra null hoặc chuỗi JSON rỗng 
        if ($images === null || $images === '[]' || $images === '') {
            $this->logger->info("Images is null or empty");
            return true;
        }
    
        $decodedImages = json_decode($images, true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($decodedImages)) {
            $this->logger->error("Invalid or empty images JSON");
            return true;
        }
    
        // Xử lý ảnh bìa nếu cần
        if ($nameEn && isset($this->truyen['photo'])) {
            $this->handleCoverImage($nameEn, $this->truyen['photo']);
        }
    
        // Kiểm tra xem có ảnh nào không phải từ Naver không
        $needsProcessing = false;
        foreach ($decodedImages as $image) {
            $imageUrl = is_string($image) ? $image : ($image['url'] ?? null);
            if (!$imageUrl) continue;
    
            if (strpos($imageUrl, self::NAVER_DOMAIN) === false) {
                $needsProcessing = true;
                break;
            }
        }
    
        // Nếu có ít nhất một ảnh không phải từ Naver, xử lý toàn bộ chapter
        if ($needsProcessing) {
            return $this->processImages($decodedImages, $nameEn, $chapterNumber, $chapterId);
        }
    
        return false;
    }

    /**
     * Xử lý ảnh bìa
     */
    private function handleCoverImage($nameEn, $coverImages) {
        $query = "SELECT cover_image FROM manga WHERE slug = ?";
        $result = $this->executeWithRetry($query, [$nameEn]);
        
        if ($result && $row = $result->fetch_assoc()) {
            $coverImage = $row['cover_image'];
            if ($this->needsProcessing($coverImage)) {
                $this->processCoverImage($nameEn, $coverImages, $coverImage);
            }
        }
    }

    /**
     * Kiểm tra xem ảnh có cần xử lý không
     */
    private function needsProcessing($imageUrl) {
        return $imageUrl && (
            strpos($imageUrl, self::IMAGEKIT_DOMAIN) !== false || 
            strpos($imageUrl, self::GCORE_DOMAIN) !== false
        );
    }

    /**
     * Xử lý upload ảnh bìa
     */
    private function processCoverImage($nameEn, $coverImages, $oldCoverImage) {
        $sessionKey = $this->getNaverSessionKey();
        if (!$sessionKey) return;

        $downloadDir = __DIR__ . '/downloads/';
        if (!is_dir($downloadDir)) {
            mkdir($downloadDir, 0777, true);
        }

        $localPath = $downloadDir . $nameEn . "-cover.jpg";

        if ($this->DownloadFilex($coverImages, $localPath)) {
            clearstatcache();
            $currentFileSize = @filesize($localPath);
            
            if ($currentFileSize && $currentFileSize > 0) {
                $naverUrl = $this->uploadToNaverWithSession($localPath, $sessionKey, 0);
                if ($naverUrl) {
                    // Update DB
                    $updateQuery = "UPDATE manga SET cover_image = ? WHERE slug = ?";
                    $this->executeWithRetry($updateQuery, [$naverUrl, $nameEn]);
                    
                    // Cleanup old S3 file if needed
                    if (strpos($oldCoverImage, self::GCORE_DOMAIN) !== false) {
                        $this->deleteFromS3($oldCoverImage);
                    }
                }
            }
            @unlink($localPath);
        }
    }

    /**
     * Xử lý images chương
     */
    private function processImages($images, $nameEn, $chapterNumber, $chapterId) {
        $sessionKey = $this->getNaverSessionKey();
        if (!$sessionKey) return true;

        $updatedImages = [];
        $needsUpdate = false;

        foreach ($images as $index => $image) {
            $imageUrl = is_string($image) ? $image : ($image['url'] ?? null);
            if (!$imageUrl) continue;

            if (strpos($imageUrl, self::NAVER_DOMAIN) !== false) {
                $updatedImages[] = ['url' => $imageUrl];
                continue;
            }

            $result = $this->processChapterImage(
                $imageUrl, 
                $nameEn, 
                $chapterNumber, 
                $index, 
                $sessionKey
            );

            if ($result) {
                $updatedImages[] = ['url' => $result];
                $needsUpdate = true;
            }
        }

        if ($needsUpdate && !empty($updatedImages)) {
            $this->updateChapterImages($chapterId, $updatedImages);
        }

        return empty($updatedImages);
    }

    /**
     * Xử lý một ảnh của chapter
     */
    private function processChapterImage($imageUrl, $nameEn, $chapterNumber, $index, $sessionKey) {
        $downloadDir = __DIR__ . '/downloads/';
        if (!is_dir($downloadDir)) {
            mkdir($downloadDir, 0777, true);
        }

        $newS3Path = "{$nameEn}/{$chapterNumber}/{$nameEn}-{$chapterNumber}-" . ($index + 1) . ".jpg";
        $localPath = $downloadDir . basename($newS3Path);

        if ($this->downloadFile($imageUrl, $localPath, $chapterNumber, $index + 1)) {
            clearstatcache();
            $currentFileSize = @filesize($localPath);
            
            if ($currentFileSize && $currentFileSize > 0) {
                $naverUrl = $this->uploadToNaverWithSession($localPath, $sessionKey, $index);
                if ($naverUrl) {
                    // Cleanup old S3 file if needed
                    if (strpos($imageUrl, self::GCORE_DOMAIN) !== false) {
                        $this->deleteFromS3($imageUrl);
                    }
                    @unlink($localPath);
                    return $naverUrl;
                }
            }
            @unlink($localPath);
        }
        return null;
    }

    /**
     * Delete file from S3
     */
    private function deleteFromS3($imageUrl) {
        if (strpos($imageUrl, self::GCORE_DOMAIN) !== false) {
            try {
                $s3Key = str_replace('https://' . self::GCORE_DOMAIN . '/', '', $imageUrl);
                $this->s3->deleteObject([
                    'Bucket' => 'list-manga',
                    'Key' => $s3Key
                ]);
                $this->logger->info("Deleted file from S3: $s3Key");
            } catch (Exception $e) {
                $this->logger->error("Failed to delete from S3: " . $e->getMessage());
            }
        }
    }

    // Add this helper method for better S3 connectivity
    private function getS3Client() {
        if (!$this->s3) {
            $this->s3 = new S3Client([
                'credentials' => new AwsCredentials(
                    '7A8DPJZ6AITUOBCUX5SJ',
                    'KiBYVyCb8sqUmj2oxtYD9RUeYPWxDD1yYuXfDJLm'
                ),
                'endpoint' => 'http://s-sgc1.cloud.gcore.lu',
                'version' => 'latest',
                'region' => 's-sgc1',
                'use_path_style_endpoint' => true,
                'http' => [
                    'connect_timeout' => 5,
                    'timeout' => 10,
                    'retry_count' => 3
                ]
            ]);
        }
        return $this->s3;
    }    
    private function extractOriginalUrl($imageUrl) {
        try {
            // Extract parameters from current URL
            if (preg_match('/s2truyen\/truyen-tranh\/(.*?)\/(.*?)\//', $imageUrl, $matches)) {
                $nameEn = $matches[1];
                $chapterNumber = $matches[2];
                
                // Get manga_id from database
                $query = "SELECT id FROM manga WHERE slug = ?";
                $stmt = $this->db->prepare($query);
                if ($stmt === false) {
                    echo "[" . date('Y-m-d H:i:s') . "] Failed to prepare statement: " . $this->db->error . "\n";
                    return null;
                }
    
                // Kiểm tra bind_param
                if (!$stmt->bind_param("s", $nameEn)) {
                    echo "[" . date('Y-m-d H:i:s') . "] Failed to bind parameters: " . $stmt->error . "\n";
                    $stmt->close();
                    return null;
                }
    
                // Kiểm tra execute
                if (!$stmt->execute()) {
                    echo "[" . date('Y-m-d H:i:s') . "] Failed to execute statement: " . $stmt->error . "\n";
                    $stmt->close();
                    return null;
                }
    
                $result = $stmt->get_result();
                if (!$result) {
                    echo "[" . date('Y-m-d H:i:s') . "] Failed to get result: " . $stmt->error . "\n";
                    $stmt->close();
                    return null;
                }
    
                $row = $result->fetch_assoc();
                $stmt->close();
                
                if ($row) {
                    $comicId = $row['id'];
                    // Call API to get original URLs
                    $chapterUrl = 'https://goctruyentranhvui6.com/api/chapter/auth';
                    $postData = "comicId={$comicId}&chapterNumber={$chapterNumber}&nameEn={$nameEn}";
                    
                    $datasave = base64_decode("Y3VybCAtTCAtcyAn");
                    $datasave .= $chapterUrl.base64_decode("JyBcCiAgLUggJ2F1dGhvcml0eTogZ29jdHJ1eWVudHJhbmh2dWk2LmNvbScgXAogIC1IICdhY2NlcHQ6IGFwcGxpY2F0aW9uL2pzb24sIHRleHQvamF2YXNjcmlwdCwgKi8qOyBxPTAuMDEnIFwKICAtSCAnYWNjZXB0LWxhbmd1YWdlOiBlbi1VUyxlbjtxPTAuOSx2aTtxPTAuOCcgXAogIC1IICdhdXRob3JpemF0aW9uOiBCZWFyZXIgZXlKaGJHY2lPaUpJVXpVeE1pSjkuZXlKemRXSWlPaUpLYjJVZ1RtZDFlV1Z1SWl3aVkyOXRhV05KWkhNaU9sdGRMQ0p5YjJ4bFNXUWlPbTUxYkd3c0ltZHliM1Z3U1dRaU9tNTFiR3dzSW1Ga2JXbHVJanBtWVd4elpTd2ljbUZ1YXlJNk1Dd2ljR1Z5YldsemMybHZiaUk2VzEwc0ltbGtJam9pTURBd01EWXdORE0wTlNJc0luUmxZVzBpT21aaGJITmxMQ0pwWVhRaU9qRTNNekEwTVRrM016VXNJbVZ0WVdsc0lqb2liblZzYkNKOS5PYUliOGh1VEt6eGM3ZjZXMEJXMU1zSEMxSHdOb1FITWNWMVdkUU9iREJWMElEdlZhZXYwMFFjNEhkdFdLSWxVX3V6LVJLZ241VUdDNThuUkRoQzVVQScgXAogIC1IICdjb250ZW50LXR5cGU6IGFwcGxpY2F0aW9uL3gtd3d3LWZvcm0tdXJsZW5jb2RlZDsgY2hhcnNldD1VVEYtOCcgXAogIC1IICdjb29raWU6IF9nYT1HQTEuMS4xNzQ4OTg1OTc3LjE3MzA0MTk2NTE7IFVHVnljMmx6ZEZOMGIzSmhaMlU9JTdCJTdEOyBfX1BQVV9wdWlkPTE2NjUyMDA1OTk1Mjc2NjkxMTU1OyBjZl9jbGVhcmFuY2U9YkxMY1g5WkZObGQxcXhKZFpHaHpLMkVHMDF3aFdhdmFKd011Ll8uVV8zay0xNzMzNzQzNDI0LTEuMi4xLjEtSnkzN0pvaElLNTd4ZW1GSnhrMnpBOUtLVGxzczYuOGJQTDdjN2RMeElpUTlycmdsa3BfUWZlNUttTWxOeEJ5dTl3STNYNlBoUHBWTi5hd0Y4ZmdsUGtELm8wSWZfeXdDRzg5UzVDMFZKbWxCQVpTQnQ3UlliWS5oSG5SVkY3LmVpNzdSYkhrZWNpdnU0SVFiYzdLT1dUcHJWbVdmVnJUWmlfYzJmaF9mTDhqV2YuSXY0elhEY0NFenJBejRYVUlOdkl1ajVWQjZMRGtMSm55R1BjSGlxbmNYNExwbVM2em50dXFJRU1aWGRCMDlfUnFfQmFGaGU3bjFUZVZJZHdsR3dtcUdKMXZpdUlGTjR1LmZhYlBPLkNGYWVncFVhbFlYY0htYU8xVHNEaVFkOTc3T0JHZnh6Q09xR3hzMWNLNkNyVHl5OS5ETjJ4OGxRUGh5U3dKWTZucUZiQnd3aG5fWUhZWTNSUVVYZDV0dFFjLjZROGhGWjFyeDhRMWlMYXg5VUlXcjlJNldJYTBKdzlUVU1zajlFek5xTFcydFp1MzE5Yy5OUG0xX0pNSGpJOG14V3JWQWZiSHpkRC5XZ3htTDsgdXNpZD0zMTkxNDAxRDA0QUFCMTdENTg2OEFBMTMxRTgxMkMwQTsgX19QUFVfcHB1Y250PTE7IF9nYV9WMUZTWjRZRkpIPUdTMS4xLjE3MzM3NDM0NzguMTIuMS4xNzMzNzQzNTU3LjAuMC4wJyBcCiAgLUggJ29yaWdpbjogaHR0cHM6Ly9nb2N0cnV5ZW50cmFuaHZ1aTYuY29tJyBcCiAgLUggJ3JlZmVyZXI6IGh0dHBzOi8vZ29jdHJ1eWVudHJhbmh2dWk2LmNvbS90cnV5ZW4vdGhpZW4tdGFpLWRvYW4tbWVuaC9jaHVvbmctMTcnIFwKICAtSCAnc2VjLWNoLXVhOiAiTm90L0EpQnJhbmQiO3Y9Ijk5IiwgIkdvb2dsZSBDaHJvbWUiO3Y9IjExNSIsICJDaHJvbWl1bSI7dj0iMTE1IicgXAogIC1IICdzZWMtY2gtdWEtYXJjaDogIng4NiInIFwKICAtSCAnc2VjLWNoLXVhLWJpdG5lc3M6ICI2NCInIFwKICAtSCAnc2VjLWNoLXVhLWZ1bGwtdmVyc2lvbjogIjExNS4wLjU3OTAuMTcwIicgXAogIC1IICdzZWMtY2gtdWEtZnVsbC12ZXJzaW9uLWxpc3Q6ICJOb3QvQSlCcmFuZCI7dj0iOTkuMC4wLjAiLCAiR29vZ2xlIENocm9tZSI7dj0iMTE1LjAuNTc5MC4xNzAiLCAiQ2hyb21pdW0iO3Y9IjExNS4wLjU3OTAuMTcwIicgXAogIC1IICdzZWMtY2gtdWEtbW9iaWxlOiA/MCcgXAogIC1IICdzZWMtY2gtdWEtbW9kZWw6ICIiJyBcCiAgLUggJ3NlYy1jaC11YS1wbGF0Zm9ybTogIm1hY09TIicgXAogIC1IICdzZWMtY2gtdWEtcGxhdGZvcm0tdmVyc2lvbjogIjEzLjYuMyInIFwKICAtSCAnc2VjLWZldGNoLWRlc3Q6IGVtcHR5JyBcCiAgLUggJ3NlYy1mZXRjaC1tb2RlOiBjb3JzJyBcCiAgLUggJ3NlYy1mZXRjaC1zaXRlOiBzYW1lLW9yaWdpbicgXAogIC1IICd1c2VyLWFnZW50OiBNb3ppbGxhLzUuMCAoTWFjaW50b3NoOyBJbnRlbCBNYWMgT1MgWCAxMF8xNV83KSBBcHBsZVdlYktpdC81MzcuMzYgKEtIVE1MLCBsaWtlIEdlY2tvKSBDaHJvbWUvMTE1LjAuMC4wIFNhZmFyaS81MzcuMzYnIFwKICAtSCAneC1yZXF1ZXN0ZWQtd2l0aDogWE1MSHR0cFJlcXVlc3QnIFwKICAtLWRhdGEtcmF3ICc=");
                    $datasave .= $postData.base64_decode("JyBcCiAgLS1jb21wcmVzc2Vk");
                    //echo $datasave."\n";
                    
                    echo "[" . date('Y-m-d H:i:s') . "] Executing API request for comicId: $comicId, chapter: $chapterNumber\n";
                    $response = shell_exec($datasave);
                    
                    if (!$response) {
                        echo "[" . date('Y-m-d H:i:s') . "] API request failed - no response\n";
                        return null;
                    }
                    
                    $responseData = json_decode($response, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        echo "[" . date('Y-m-d H:i:s') . "] Failed to parse API response: " . json_last_error_msg() . "\n";
                        return null;
                    }
                    
                    if ($responseData && isset($responseData['result']['data'])) {
                        // Extract index from current URL
                        if (preg_match('/-(\d+)\.jpg$/', $imageUrl, $indexMatch)) {
                            $index = (int)$indexMatch[1] - 1;
                            if (isset($responseData['result']['data'][$index])) {
                                echo "[" . date('Y-m-d H:i:s') . "] Successfully retrieved original URL for index: $index\n";
                                return $responseData['result']['data'][$index];
                            }
                            echo "[" . date('Y-m-d H:i:s') . "] Index $index not found in API response\n";
                        }
                        echo "[" . date('Y-m-d H:i:s') . "] Failed to extract index from URL: $imageUrl\n";
                    }
                    echo "[" . date('Y-m-d H:i:s') . "] Invalid API response structure\n";
                }
                echo "[" . date('Y-m-d H:i:s') . "] No manga found for nameEn: $nameEn\n";
            }
            echo "[" . date('Y-m-d H:i:s') . "] Failed to extract nameEn and chapterNumber from URL: $imageUrl\n";
        } catch (Exception $e) {
            echo "[" . date('Y-m-d H:i:s') . "] Error extracting original URL: " . $e->getMessage() . "\n";
        }
        
        return null;
    }

    private function processChapterImages($chapterResponse, $nameEn, $title, $chapterId, $sessionKey) {
        $imageUrls = $chapterResponse['result']['data'];
        $this->tracker->addImages(count($imageUrls));
        $updatedImages = [];
        $fileDetails = [];
    
        // Download tất cả images trước
        foreach ($imageUrls as $imgIndex => $imageUrl) {
            try {

            $s3Path = "s2truyen/truyen-tranh/{$nameEn}/{$title}/{$nameEn}-{$title}-" . ($imgIndex + 1) . ".jpg";
            $localPath = __DIR__ . '/downloads/' . basename($s3Path);
            $fileDetails[$imageUrl] = $localPath;
            $this->tracker->incrementProcessedImages();
        } catch (Exception $e) {
            $this->tracker->addError("Lỗi xử lý ảnh $imgIndex của chapter $chapterId: " . $e->getMessage());
        }
    }

    
        // Download files
        $downloadResults = $this->downloadFiles($fileDetails);
        if ($downloadResults) {
            // Lấy danh sách file đã download thành công
            $successFiles = [];
            foreach ($fileDetails as $imageUrl => $localPath) {
                if (file_exists($localPath) && filesize($localPath) > 0) {
                    $successFiles[] = $localPath;
                }
            }
    
            // Upload batch lên Naver
            if (!empty($successFiles)) {
                $uploadResults = $this->uploadBatchToNaverWithSession($successFiles, $sessionKey);
                
                if ($uploadResults['success']) {
                    foreach ($uploadResults['urls'] as $url) {
                        $updatedImages[] = ['url' => $url];
                    }
                    
                    // Update vào database
                    if (!empty($updatedImages)) {
                        $this->updateChapterImages($chapterId, $updatedImages);
                        echo "[" . date('Y-m-d H:i:s') . "] Updated chapter $chapterId with " . count($updatedImages) . " images\n";
                    }
                } else {
                    echo "[" . date('Y-m-d H:i:s') . "] Failed to upload some images. Errors: " . print_r($uploadResults['errors'], true) . "\n";
                }
            }
        }
    
        return $updatedImages;
    }
    
    private function validateImageUrl($url) {
        $headers = @get_headers($url);
        return $headers && strpos($headers[0], '200') !== false;
    }

    

    private function retryDownloadImage($imageUrl, $s3Path, $chapterId, $maxRetries = 3) {
        for ($i = 0; $i < $maxRetries; $i++) {
            echo "Attempt " . ($i + 1) . " to redownload image: {$imageUrl}\n";
            
            $localPath = __DIR__ . '/downloads/' . basename($s3Path);
            
            if ($this->downloadFile($imageUrl, $localPath)) {
                if (filesize($localPath) > 0) {
                    $uploadedUrl = $this->uploadToS3FromLocalFile($localPath, $s3Path, $chapterId);
                    
                    if ($uploadedUrl) {
                        // Cập nhật URL với định dạng yêu cầu
                        $updatedUrl = 'https://list-manga.s-sgc1.cloud.gcore.lu/s2truyen/truyen-tranh/' . basename($s3Path);
                        echo "Successfully redownloaded and uploaded image. Updated URL: {$updatedUrl}\n";
                        return $updatedUrl;
                    }
                }
            }
            
            if ($i < $maxRetries - 1) {
                sleep(2);
            }
        }
        
        return null;
    }
    

    private function updateChapterImages($chapterId, $images) {
        $imagesJson = json_encode($images, JSON_UNESCAPED_SLASHES);
        $query = "UPDATE chapters SET images = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("si", $imagesJson, $chapterId);
        $stmt->execute();
        echo "Updated images for chapter {$chapterId}\n";
    }

    private function chapterExists($chapterId) {
        if (!$chapterId) {
            echo "[" . date('Y-m-d H:i:s') . "] Invalid chapter ID provided\n";
            return false;
        }

        try {
            $query = "SELECT ch.id, ch.images, ch.manga_id, ch.chapter_number, m.nameEn 
                     FROM chapters ch 
                     JOIN manga m ON ch.manga_id = m.id 
                     WHERE ch.id = ?";
            
            $stmt = $this->db->prepare($query);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $this->db->error);
            }

            $stmt->bind_param("i", $chapterId);
            $stmt->execute();
            $result = $stmt->get_result();
            $chapter = $result->fetch_assoc();

            if (!$chapter) {
                echo "[" . date('Y-m-d H:i:s') . "] Chapter not found: $chapterId\n";
                return false;
            }

            // Pass all required parameters to isImagesEmptyOrNull
            $isEmpty = $this->isImagesEmptyOrNull(
                $chapter['images'],
                $chapter['nameEn'],
                $chapter['chapter_number'],
                $chapter['id']
            );

            echo "[" . date('Y-m-d H:i:s') . "] Checked Chapter ID: $chapterId - isEmpty: " . 
                 ($isEmpty ? 'true' : 'false') . "\n";

            return !$isEmpty;

        } catch (Exception $e) {
            echo "[" . date('Y-m-d H:i:s') . "] Error checking chapter: " . $e->getMessage() . "\n";
            return false;
        }
    }


    public function processComicOnly() {
        try {
            $mangaUrl = $this->saveManga();
            if ($mangaUrl !== null) {
                $categories = $this->fetchCategoriesFromHtml($mangaUrl);
                echo "[" . date('Y-m-d H:i:s') . "] Categories for manga {$this->truyen['id']}:\n";
                //print_r($categories);
                $this->updateMangaCategories($this->truyen['id'], $categories);
            }
            $this->saveChapters();
            $this->fetchAllChapters($this->truyen['id'], $this->truyen['nameEn']);
        } catch (Exception $e) {
            echo "[" . date('Y-m-d H:i:s') . "] Error processing comicId: {$this->truyen['id']}. Error: " . $e->getMessage() . "\n";
        }
    }

    
}
    

function crawlData() {
    $tracker = ProgressTracker::getInstance();
    $logger = new SimpleLogger();

    $dbConfig = [
        'host' => '64.71.152.17',
        'user' => 'root',
        'pass' => 'FkZmcRGGpg3LQSRAzV8v',
        'dbname' => 'struyenc_s2truyen'
    ];
    $s3Config = [
        'credentials' => new AwsCredentials('7A8DPJZ6AITUOBCUX5SJ', 'KiBYVyCb8sqUmj2oxtYD9RUeYPWxDD1yYuXfDJLm'),
        'endpoint' => 'http://s-sgc1.cloud.gcore.lu',
        'version' => 'latest',
        'region' => 's-sgc1',
        'use_path_style_endpoint' => true,
        'suppress_php_deprecation_warning' => true
    ];
    
    $baseUrl = 'https://goctruyentranhvui6.com/api/v2/search?p=';
    $page = 0;
    
    $stateFile = 'crawl_state.json';
    if (file_exists($stateFile)) {
        $state = json_decode(file_get_contents($stateFile), true);
        $page = $state['page'];
    } else {
        $state = ['page' => 0];
    }
    
    // Giới hạn số lượng tiến trình
    $maxMangaProcesses = 1;
    $maxCommentProcesses = 2;
    $currentMangaProcesses = 0;
    $currentCommentProcesses = 0;
    
    try {
        // Lấy và thiết lập tổng số manga
        $tracker->addActiveTask("init", "Đang khởi tạo crawl dữ liệu");
        $initialUrl = $baseUrl . '0&searchValue=&orders%5B%5D=createdAt';
        $datasave = base64_decode("Y3VybCAtTCAtcyAn");
        $datasave .= $initialUrl.base64_decode("JyBcCiAgLUggJ2F1dGhvcml0eTogZ29jdHJ1eWVudHJhbmh2dWk2LmNvbScgXAogIC1IICdhY2NlcHQ6IHRleHQvaHRtbCxhcHBsaWNhdGlvbi94aHRtbCt4bWwsYXBwbGljYXRpb24veG1sO3E9MC45LGltYWdlL2F2aWYsaW1hZ2Uvd2VicCxpbWFnZS9hcG5nLCovKjtxPTAuOCxhcHBsaWNhdGlvbi9zaWduZWQtZXhjaGFuZ2U7dj1iMztxPTAuNycgXAogIC1IICdhY2NlcHQtbGFuZ3VhZ2U6IGVuLVVTLGVuO3E9MC45LHZpO3E9MC44JyBcCiAgLUggJ2Nvb2tpZTogX2dhPUdBMS4xLjE3NDg5ODU5NzcuMTczMDQxOTY1MTsgVUdWeWMybHpkRk4wYjNKaFoyVT0lN0IlN0Q7IF9fUFBVX3B1aWQ9MTY2NTIwMDU5OTUyNzY2OTExNTU7IF9nYV9WMUZTWjRZRkpIPUdTMS4xLjE3MzMzNDU2MzIuMTEuMC4xNzMzMzQ1NjMyLjAuMC4wOyBjZl9jbGVhcmFuY2U9YkxMY1g5WkZObGQxcXhKZFpHaHpLMkVHMDF3aFdhdmFKd011Ll8uVV8zay0xNzMzNzQzNDI0LTEuMi4xLjEtSnkzN0pvaElLNTd4ZW1GSnhrMnpBOUtLVGxzczYuOGJQTDdjN2RMeElpUTlycmdsa3BfUWZlNUttTWxOeEJ5dTl3STNYNlBoUHBWTi5hd0Y4ZmdsUGtELm8wSWZfeXdDRzg5UzVDMFZKbWxCQVpTQnQ3UlliWS5oSG5SVkY3LmVpNzdSYkhrZWNpdnU0SVFiYzdLT1dUcHJWbVdmVnJUWmlfYzJmaF9mTDhqV2YuSXY0elhEY0NFenJBejRYVUlOdkl1ajVWQjZMRGtMSm55R1BjSGlxbmNYNExwbVM2em50dXFJRU1aWGRCMDlfUnFfQmFGaGU3bjFUZVZJZHdsR3dtcUdKMXZpdUlGTjR1LmZhYlBPLkNGYWVncFVhbFlYY0htYU8xVHNEaVFkOTc3T0JHZnh6Q09xR3hzMWNLNkNyVHl5OS5ETjJ4OGxRUGh5U3dKWTZucUZiQnd3aG5fWUhZWTNSUVVYZDV0dFFjLjZROGhGWjFyeDhRMWlMYXg5VUlXcjlJNldJYTBKdzlUVU1zajlFek5xTFcydFp1MzE5Yy5OUG0xX0pNSGpJOG14V3JWQWZiSHpkRC5XZ3htTDsgdXNpZD01OUVDQzIxMDA1OUMzNjMzMTI2RjRGRUE2NzAyMzI3MCcgXAogIC1IICdyZWZlcmVyOiBodHRwczovL2dvY3RydXllbnRyYW5odnVpNi5jb20vdHJhbmctY2h1JyBcCiAgLUggJ3NlYy1jaC11YTogIk5vdC9BKUJyYW5kIjt2PSI5OSIsICJHb29nbGUgQ2hyb21lIjt2PSIxMTUiLCAiQ2hyb21pdW0iO3Y9IjExNSInIFwKICAtSCAnc2VjLWNoLXVhLWFyY2g6ICJ4ODYiJyBcCiAgLUggJ3NlYy1jaC11YS1iaXRuZXNzOiAiNjQiJyBcCiAgLUggJ3NlYy1jaC11YS1mdWxsLXZlcnNpb246ICIxMTUuMC41NzkwLjE3MCInIFwKICAtSCAnc2VjLWNoLXVhLWZ1bGwtdmVyc2lvbi1saXN0OiAiTm90L0EpQnJhbmQiO3Y9Ijk5LjAuMC4wIiwgIkdvb2dsZSBDaHJvbWUiO3Y9IjExNS4wLjU3OTAuMTcwIiwgIkNocm9taXVtIjt2PSIxMTUuMC41NzkwLjE3MCInIFwKICAtSCAnc2VjLWNoLXVhLW1vYmlsZTogPzAnIFwKICAtSCAnc2VjLWNoLXVhLW1vZGVsOiAiIicgXAogIC1IICdzZWMtY2gtdWEtcGxhdGZvcm06ICJtYWNPUyInIFwKICAtSCAnc2VjLWNoLXVhLXBsYXRmb3JtLXZlcnNpb246ICIxMy42LjMiJyBcCiAgLUggJ3NlYy1mZXRjaC1kZXN0OiBkb2N1bWVudCcgXAogIC1IICdzZWMtZmV0Y2gtbW9kZTogbmF2aWdhdGUnIFwKICAtSCAnc2VjLWZldGNoLXNpdGU6IHNhbWUtb3JpZ2luJyBcCiAgLUggJ3NlYy1mZXRjaC11c2VyOiA/MScgXAogIC1IICd1cGdyYWRlLWluc2VjdXJlLXJlcXVlc3RzOiAxJyBcCiAgLUggJ3VzZXItYWdlbnQ6IE1vemlsbGEvNS4wIChNYWNpbnRvc2g7IEludGVsIE1hYyBPUyBYIDEwXzE1XzcpIEFwcGxlV2ViS2l0LzUzNy4zNiAoS0hUTUwsIGxpa2UgR2Vja28pIENocm9tZS8xMTUuMC4wLjAgU2FmYXJpLzUzNy4zNicgXAogIC0tY29tcHJlc3NlZA==");

        $initialResponse = shell_exec($datasave);
        $initialData = json_decode($initialResponse, true);
        
        if ($initialData && isset($initialData['result']['total'])) {
            $totalManga = $initialData['result']['total'];
            $tracker->setTotalManga($totalManga);
        }
        $tracker->removeActiveTask("init");

        do {
            $url = $baseUrl . $page . '&searchValue=&orders%5B%5D=createdAt';
            $tracker->addActiveTask("page_$page", "Đang xử lý trang $page");
            
            $datasave = base64_decode("Y3VybCAtTCAtcyAn");
            $datasave .= $url.base64_decode("JyBcCiAgLUggJ2F1dGhvcml0eTogZ29jdHJ1eWVudHJhbmh2dWk2LmNvbScgXAogIC1IICdhY2NlcHQ6IHRleHQvaHRtbCxhcHBsaWNhdGlvbi94aHRtbCt4bWwsYXBwbGljYXRpb24veG1sO3E9MC45LGltYWdlL2F2aWYsaW1hZ2Uvd2VicCxpbWFnZS9hcG5nLCovKjtxPTAuOCxhcHBsaWNhdGlvbi9zaWduZWQtZXhjaGFuZ2U7dj1iMztxPTAuNycgXAogIC1IICdhY2NlcHQtbGFuZ3VhZ2U6IGVuLVVTLGVuO3E9MC45LHZpO3E9MC44JyBcCiAgLUggJ2Nvb2tpZTogX2dhPUdBMS4xLjE3NDg5ODU5NzcuMTczMDQxOTY1MTsgVUdWeWMybHpkRk4wYjNKaFoyVT0lN0IlN0Q7IF9fUFBVX3B1aWQ9MTY2NTIwMDU5OTUyNzY2OTExNTU7IF9nYV9WMUZTWjRZRkpIPUdTMS4xLjE3MzMzNDU2MzIuMTEuMC4xNzMzMzQ1NjMyLjAuMC4wOyBjZl9jbGVhcmFuY2U9YkxMY1g5WkZObGQxcXhKZFpHaHpLMkVHMDF3aFdhdmFKd011Ll8uVV8zay0xNzMzNzQzNDI0LTEuMi4xLjEtSnkzN0pvaElLNTd4ZW1GSnhrMnpBOUtLVGxzczYuOGJQTDdjN2RMeElpUTlycmdsa3BfUWZlNUttTWxOeEJ5dTl3STNYNlBoUHBWTi5hd0Y4ZmdsUGtELm8wSWZfeXdDRzg5UzVDMFZKbWxCQVpTQnQ3UlliWS5oSG5SVkY3LmVpNzdSYkhrZWNpdnU0SVFiYzdLT1dUcHJWbVdmVnJUWmlfYzJmaF9mTDhqV2YuSXY0elhEY0NFenJBejRYVUlOdkl1ajVWQjZMRGtMSm55R1BjSGlxbmNYNExwbVM2em50dXFJRU1aWGRCMDlfUnFfQmFGaGU3bjFUZVZJZHdsR3dtcUdKMXZpdUlGTjR1LmZhYlBPLkNGYWVncFVhbFlYY0htYU8xVHNEaVFkOTc3T0JHZnh6Q09xR3hzMWNLNkNyVHl5OS5ETjJ4OGxRUGh5U3dKWTZucUZiQnd3aG5fWUhZWTNSUVVYZDV0dFFjLjZROGhGWjFyeDhRMWlMYXg5VUlXcjlJNldJYTBKdzlUVU1zajlFek5xTFcydFp1MzE5Yy5OUG0xX0pNSGpJOG14V3JWQWZiSHpkRC5XZ3htTDsgdXNpZD01OUVDQzIxMDA1OUMzNjMzMTI2RjRGRUE2NzAyMzI3MCcgXAogIC1IICdyZWZlcmVyOiBodHRwczovL2dvY3RydXllbnRyYW5odnVpNi5jb20vdHJhbmctY2h1JyBcCiAgLUggJ3NlYy1jaC11YTogIk5vdC9BKUJyYW5kIjt2PSI5OSIsICJHb29nbGUgQ2hyb21lIjt2PSIxMTUiLCAiQ2hyb21pdW0iO3Y9IjExNSInIFwKICAtSCAnc2VjLWNoLXVhLWFyY2g6ICJ4ODYiJyBcCiAgLUggJ3NlYy1jaC11YS1iaXRuZXNzOiAiNjQiJyBcCiAgLUggJ3NlYy1jaC11YS1mdWxsLXZlcnNpb246ICIxMTUuMC41NzkwLjE3MCInIFwKICAtSCAnc2VjLWNoLXVhLWZ1bGwtdmVyc2lvbi1saXN0OiAiTm90L0EpQnJhbmQiO3Y9Ijk5LjAuMC4wIiwgIkdvb2dsZSBDaHJvbWUiO3Y9IjExNS4wLjU3OTAuMTcwIiwgIkNocm9taXVtIjt2PSIxMTUuMC41NzkwLjE3MCInIFwKICAtSCAnc2VjLWNoLXVhLW1vYmlsZTogPzAnIFwKICAtSCAnc2VjLWNoLXVhLW1vZGVsOiAiIicgXAogIC1IICdzZWMtY2gtdWEtcGxhdGZvcm06ICJtYWNPUyInIFwKICAtSCAnc2VjLWNoLXVhLXBsYXRmb3JtLXZlcnNpb246ICIxMy42LjMiJyBcCiAgLUggJ3NlYy1mZXRjaC1kZXN0OiBkb2N1bWVudCcgXAogIC1IICdzZWMtZmV0Y2gtbW9kZTogbmF2aWdhdGUnIFwKICAtSCAnc2VjLWZldGNoLXNpdGU6IHNhbWUtb3JpZ2luJyBcCiAgLUggJ3NlYy1mZXRjaC11c2VyOiA/MScgXAogIC1IICd1cGdyYWRlLWluc2VjdXJlLXJlcXVlc3RzOiAxJyBcCiAgLUggJ3VzZXItYWdlbnQ6IE1vemlsbGEvNS4wIChNYWNpbnRvc2g7IEludGVsIE1hYyBPUyBYIDEwXzE1XzcpIEFwcGxlV2ViS2l0LzUzNy4zNiAoS0hUTUwsIGxpa2UgR2Vja28pIENocm9tZS8xMTUuMC4wLjAgU2FmYXJpLzUzNy4zNicgXAogIC0tY29tcHJlc3NlZA==");

            try {
                $response = shell_exec($datasave);
                $response = json_decode($response, true);

                if ($response && isset($response['status']) && $response['status'] && $response['code'] == 200) {
                    if (isset($response['result']['data']) && is_array($response['result']['data'])) {
                        $truyens = $response['result']['data'];
                        $mangaPids = [];
                        $commentPids = [];

                        foreach ($truyens as $truyen) {
                            $mangaId = $truyen['id'];
                            $taskId = "manga_$mangaId";

                            try {
                                $tracker->addActiveTask($taskId, "Đang xử lý manga: {$truyen['name']}");

                                while ($currentMangaProcesses >= $maxMangaProcesses) {
                                    $pid = pcntl_wait($status);
                                    if ($pid > 0) {
                                        $currentMangaProcesses--;
                                    }
                                }

                                $mangaPid = pcntl_fork();
                                if ($mangaPid == -1) {
                                    throw new Exception('Could not fork manga process');
                                } else if ($mangaPid == 0) { // Child process
                                    try {
                                        $processor = new MangaProcessor($logger, $truyen, $dbConfig, $s3Config);
                                        $chapterCount = count($truyen['chapterLatest']);
                                        $tracker->addChapters($chapterCount);
                                        $processor->processComicOnly();
                                        $tracker->incrementProcessedManga();
                                        exit(0);
                                    } catch (Exception $e) {
                                        $tracker->addError("Lỗi xử lý manga {$truyen['name']}: " . $e->getMessage());
                                        exit(1);
                                    }
                                } else { // Parent process
                                    $mangaPids[] = $mangaPid;
                                    $currentMangaProcesses++;
                                }

                            } catch (Exception $e) {
                                $tracker->addError("Lỗi fork process cho manga {$truyen['name']}: " . $e->getMessage());
                            } finally {
                                $tracker->removeActiveTask($taskId);
                            }
                        }

                        // Đợi tất cả process manga hoàn thành
                        foreach ($mangaPids as $pid) {
                            $status = 0;
                            pcntl_waitpid($pid, $status);
                            if ($status !== 0) {
                                $tracker->addError("Process $pid kết thúc với mã lỗi: $status");
                            }
                            $currentMangaProcesses--;
                        }
                    }
                    
                    $hasNextPage = isset($response['result']['next']) ? $response['result']['next'] : false;
                    $page++;
                    
                    $state['page'] = $page;
                    file_put_contents($stateFile, json_encode($state));
                    
                    $tracker->removeActiveTask("page_$page");
                } else {
                    $tracker->addError("Không tìm thấy truyện ở trang: $page");
                    $hasNextPage = false;
                }
            } catch (Exception $e) {
                $tracker->addError("Lỗi xử lý trang $page: " . $e->getMessage());
            }
        } while ($hasNextPage);

    } catch (Exception $e) {
        $tracker->addError("Lỗi chính: " . $e->getMessage());
    } finally {
        $tracker->saveStats();
    }
}


// Run the crawler
crawlData();

?>
