<?php
require_once 'ProgressTracker.php';
require_once 'ImageValidator.php';
require_once 'extractOriginalUrl.php';
require_once 'saveChapters.php';
require_once 'updateMangaCategories.php';
require_once 'retryDownloadImage.php';
require_once 'downloadFile.php';
require_once 'uploadToNaverWithSession.php';
require_once 'downloadFiles.php';
require_once 'uploadToS3.php';
require_once 'fetchAllChapters.php';
require_once 'fetchImagesFromHtml.php';

require_once '/Users/binblacker/sg-4241725359072075-main/vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Common\Credentials\Credentials;
use Aws\Credentials\Credentials as AwsCredentials;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DomCrawler\Crawler;
use Manga\ChapterFetcher;
use Manga\ChapterManager;
use Manga\ImageValidator;

function fetch_page_data($url) {
    $datasave = base64_decode("Y3VybCAtTCAtcyAn");
    $datasave .= $url.base64_decode("JyBcCiAgLUggJ2F1dGhvcml0eTogZ29jdHJ1eWVudHJhbmh2dWk3LmNvbScgXAogIC1IICdhY2NlcHQ6IHRleHQvaHRtbCxhcHBsaWNhdGlvbi94aHRtbCt4bWwsYXBwbGljYXRpb24veG1sO3E9MC45LGltYWdlL2F2aWYsaW1hZ2Uvd2VicCxpbWFnZS9hcG5nLCovKjtxPTAuOCxhcHBsaWNhdGlvbi9zaWduZWQtZXhjaGFuZ2U7dj1iMztxPTAuNycgXAogIC1IICdhY2NlcHQtbGFuZ3VhZ2U6IGVuLVVTLGVuO3E9MC45LHZpO3E9MC44JyBcCiAgLUggJ2Nvb2tpZTogX2dhPUdBMS4xLjI0OTgyMzk4Mi4xNzMxNzA2ODI4OyBVR1Z5YzJsemRGTjBiM0poWjJVPSU3QiU3RDsgY2ZfY2xlYXJhbmNlPUQ3cnp2RUdUNTZUX1JMRXpQajBuaU1ic3huN0UyZENOUXFzR2lJU29ZVzgtMTczMzY1MTkwOC0xLjIuMS4xLTlGejlkMlI0ZGlGVWRMWmxVTjU3bmdqaUhxdldNR3RnbUZ3b1RLaERMVkR1YVRyMEFsTy5HS3FkQkRvSUZLY2NYOWM0U3lHcUJzTllCNDhmUFozWEtrbGpSRDZmS29wenZncTVFY0RkMEhfdjRqZ2RWX0NTTkpSc3lPNlZqbjFIX1dxTWw2ekpPRllDQl9xVGtsX3RyWDR0OE9CVGxSWXNHVlF2cXNYQVQxSElOMC5VQUlWZFJUNktTOHRUdkFoNTNaaE5IbDdqbG9odktyWUxZbEZDWUtieVBjU2VHZXdwTlZfanZwZTlxVUdhbjNXeU1oZDRkcnhtZlpuMXJiVXBSRFVEM3lmWUVrM1p2bnpMN3ZKbWVYTkI5dzBHX2dkVkx6ZlhickUzR3ZMVG5iMnRGb0hKNlN0SmEucC5DTkx1Ljh1TUF2RlZQWVZlSXNwWU5ZQVpfLlJlbWd5ZWFDSGFfLkhaMEtpaE82VXRURHlzSHJyM3o2WXlCd3lkSUQwMmxpTlhpRllaOTdqX3BFazYwSVdpVl9MS0NjUmFnTDZxc085aW0xLmNlc3hNaU5LcWM3cm10aVhhYnVrV1h1UFg7IF9nYV9WMUZTWjRZRkpIPUdTMS4xLjE3MzM2NTE5MDIuNi4xLjE3MzM2NTE5MzkuMC4wLjA7IHVzaWQ9QUVDNTVEQjZCMTcwQTVCQTNFQ0UxRDlGNEQ2OEEwRjcnIFwKICAtSCAnc2VjLWNoLXVhOiAiTm90L0EpQnJhbmQiO3Y9Ijk5IiwgIkdvb2dsZSBDaHJvbWUiO3Y9IjExNSIsICJDaHJvbWl1bSI7dj0iMTE1IicgXAogIC1IICdzZWMtY2gtdWEtYXJjaDogIng4NiInIFwKICAtSCAnc2VjLWNoLXVhLWJpdG5lc3M6ICI2NCInIFwKICAtSCAnc2VjLWNoLXVhLWZ1bGwtdmVyc2lvbjogIjExNS4wLjU3OTAuMTcwIicgXAogIC1IICdzZWMtY2gtdWEtZnVsbC12ZXJzaW9uLWxpc3Q6ICJOb3QvQSlCcmFuZCI7dj0iOTkuMC4wLjAiLCAiR29vZ2xlIENocm9tZSI7dj0iMTE1LjAuNTc5MC4xNzAiLCAiQ2hyb21pdW0iO3Y9IjExNS4wLjU3OTAuMTcwIicgXAogIC1IICdzZWMtY2gtdWEtbW9iaWxlOiA/MCcgXAogIC1IICdzZWMtY2gtdWEtbW9kZWw6ICIiJyBcCiAgLUggJ3NlYy1jaC11YS1wbGF0Zm9ybTogIm1hY09TIicgXAogIC1IICdzZWMtY2gtdWEtcGxhdGZvcm0tdmVyc2lvbjogIjEzLjYuMyInIFwKICAtSCAnc2VjLWZldGNoLWRlc3Q6IGRvY3VtZW50JyBcCiAgLUggJ3NlYy1mZXRjaC1tb2RlOiBuYXZpZ2F0ZScgXAogIC1IICdzZWMtZmV0Y2gtc2l0ZTogbm9uZScgXAogIC1IICdzZWMtZmV0Y2gtdXNlcjogPzEnIFwKICAtSCAndXBncmFkZS1pbnNlY3VyZS1yZXF1ZXN0czogMScgXAogIC1IICd1c2VyLWFnZW50OiBNb3ppbGxhLzUuMCAoTWFjaW50b3NoOyBJbnRlbCBNYWMgT1MgWCAxMF8xNV83KSBBcHBsZVdlYktpdC81MzcuMzYgKEtIVE1MLCBsaWtlIEdlY2tvKSBDaHJvbWUvMTE1LjAuMC4wIFNhZmFyaS81MzcuMzYnIFwKICAtLWNvbXByZXNzZWQ=");
    return shell_exec($datasave);
}

function fetchDataFromApi($url) {
    $response = fetch_page_data($url);
    return json_decode($response, true);
}


trait DateConverter {
    protected function convertDate($date) {
        return date('Y-m-d H:i:s', strtotime($date));
    }
}

class MainProcessor {
    use ImageValidator;
    use DateConverter;
    
    protected $db;
    protected $s3;
    protected $tracker;
    protected $truyen;
    protected $chapterManager;
    protected $chapterFetcher;

    public function __construct($truyen, $dbConfig, $s3Config) {
        $this->truyen = $truyen;
        $this->db = new mysqli($dbConfig['host'], $dbConfig['user'], $dbConfig['pass'], $dbConfig['db']);
        $this->s3 = new Aws\S3\S3Client($s3Config);
        $this->tracker = ProgressTracker::getInstance();
        
        $this->chapterManager = new ChapterManager();
        $this->chapterManager->truyen = $truyen;
        $this->chapterManager->db = $this->db;
        
        $this->chapterFetcher = new ChapterFetcher();
        $this->chapterFetcher->db = $this->db;
    }

    public function processComicOnly() {
        $taskId = "manga_" . $this->truyen['id'];
        $this->tracker->addActiveTask($taskId, "Processing manga: {$this->truyen['name']}");

        try {
            $this->tracker->addActiveTask($taskId . "_chapters", "Fetching chapters");
            $this->chapterManager->saveChapters();
            $this->chapterFetcher->fetchAllChapters($this->truyen['id'], $this->truyen['nameEn']);
            $this->tracker->removeActiveTask($taskId . "_chapters");
            $this->tracker->incrementProcessedManga();
        } catch (Exception $e) {
            $this->tracker->addError("Error processing manga {$this->truyen['name']}: " . $e->getMessage());
        } finally {
            $this->tracker->removeActiveTask($taskId);
        }
    }
}
function crawlData() {
    $tracker = ProgressTracker::getInstance();
    
    $dbConfig = [
        'host' => '34.87.102.62',
        'user' => 's2truyen',
        'pass' => 'FkZmcRGGpg3LQSRAzV8v',
        'db' => 'struyenc_s2truyen'
    ];

    $s3Config = [
        'credentials' => new AwsCredentials('7A8DPJZ6AITUOBCUX5SJ', 'KiBYVyCb8sqUmj2oxtYD9RUeYPWxDD1yYuXfDJLm'),
        'endpoint' => 'http://s-sgc1.cloud.gcore.lu',
        'version' => 'latest',
        'region' => 's-sgc1',
        'use_path_style_endpoint' => true,
        'suppress_php_deprecation_warning' => true
    ];

    $maxMangaProcesses = 2;
    $currentMangaProcesses = 0;
    $baseUrl = 'https://goctruyentranhvui6.com/api/v2/search?p=';
    
    try {
        while (true) {
            $response = fetchDataFromApi($baseUrl);
            if (!$response) break;

            foreach ($response['result']['data'] as $truyen) {
                while ($currentMangaProcesses >= $maxMangaProcesses) {
                    $pid = pcntl_wait($status);
                    if ($pid > 0) $currentMangaProcesses--;
                }

                $mangaPid = pcntl_fork();
                if ($mangaPid == 0) {
                    $processor = new MainProcessor($truyen, $dbConfig, $s3Config);
                    $processor->processComicOnly();
                    exit(0);
                } else {
                    $currentMangaProcesses++;
                }
            }
        }
    } catch (Exception $e) {
        $tracker->addError("Main error: " . $e->getMessage());
    } finally {
        $tracker->saveStats();
    }
}

crawlData();
