<?php

class ProgressTracker {
    // Core attributes
    private $totalManga = 0;
    private $processedManga = 0;
    private $totalChapters = 0;
    private $processedChapters = 0;
    private $totalImages = 0;
    private $processedImages = 0;
    private $startTime;
    private $errors = [];
    private $activeTasks = [];
    private static $instance = null;

    // New attributes
    private $downloadStats = [
        'success' => 0,
        'failed' => 0,
        'retries' => 0,
        'totalBytes' => 0
    ];
    private $uploadStats = [
        'success' => 0,
        'failed' => 0,
        'totalBytes' => 0
    ];
    private $taskStats = [];
    private $lastTaskTime = [];
    private $warningMessages = [];

    private function __construct() {
        $this->startTime = time();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Core methods
    public function setTotalManga($total) {
        $this->totalManga = $total;
        $this->displayProgress();
    }

    public function incrementProcessedManga() {
        $this->processedManga++;
        $this->displayProgress();
    }

    public function addChapters($count) {
        $this->totalChapters += $count;
        $this->displayProgress();
    }

    public function incrementProcessedChapters() {
        $this->processedChapters++;
        $this->displayProgress();
    }

    public function addImages($count) {
        $this->totalImages += $count;
        $this->displayProgress();
    }

    public function incrementProcessedImages() {
        $this->processedImages++;
        $this->displayProgress();
    }

    // Enhanced error and warning tracking
    public function addError($message) {
        $this->errors[] = [
            'time' => date('Y-m-d H:i:s'),
            'message' => $message
        ];
        $this->displayProgress();
    }

    public function addWarning($message) {
        $this->warningMessages[] = [
            'time' => date('Y-m-d H:i:s'),
            'message' => $message
        ];
        $this->displayProgress();
    }

    // Enhanced statistics tracking
    public function updateDownloadStats($success = true, $bytes = 0, $retry = false) {
        if ($success) {
            $this->downloadStats['success']++;
            $this->downloadStats['totalBytes'] += $bytes;
        } else {
            $this->downloadStats['failed']++;
        }
        if ($retry) {
            $this->downloadStats['retries']++;
        }
        $this->displayProgress();
    }

    public function updateUploadStats($success = true, $bytes = 0) {
        if ($success) {
            $this->uploadStats['success']++;
            $this->uploadStats['totalBytes'] += $bytes;
        } else {
            $this->uploadStats['failed']++;
        }
        $this->displayProgress();
    }

    // Enhanced task tracking
    public function addActiveTask($taskId, $description, $parentTaskId = null) {
        $this->activeTasks[$taskId] = [
            'description' => $description,
            'startTime' => time(),
            'parentTaskId' => $parentTaskId,
            'progress' => 0
        ];
        
        if (!isset($this->taskStats[$taskId])) {
            $this->taskStats[$taskId] = [
                'totalTime' => 0,
                'count' => 0,
                'errors' => 0
            ];
        }
        
        $this->lastTaskTime[$taskId] = microtime(true);
        $this->displayActiveTasks();
    }

    public function removeActiveTask($taskId) {
        if (isset($this->activeTasks[$taskId])) {
            $endTime = microtime(true);
            $startTime = $this->lastTaskTime[$taskId] ?? $endTime;
            $duration = $endTime - $startTime;
            
            $this->taskStats[$taskId]['totalTime'] += $duration;
            $this->taskStats[$taskId]['count']++;
            
            unset($this->activeTasks[$taskId]);
            $this->displayActiveTasks();
        }
    }

    public function updateTaskProgress($taskId, $progress) {
        if (isset($this->activeTasks[$taskId])) {
            $this->activeTasks[$taskId]['progress'] = $progress;
            $this->displayProgress();
        }
    }

    // Enhanced display methods
    public function displayProgress() {
        // Clear screen first
        echo "\033[2J";  // Clear entire screen
        echo "\033[H";   // Move cursor to top-left corner
    
        // Header
        echo "\n=== TIẾN TRÌNH CRAWL DỮ LIỆU ===\n";
        echo str_repeat("=", 50) . "\n\n";
    
        // Runtime
        $runtime = time() - $this->startTime;
        $hours = floor($runtime / 3600);
        $minutes = floor(($runtime % 3600) / 60);
        $seconds = $runtime % 60;
        echo sprintf("⏱️  Thời gian chạy: %02dh %02dm %02ds\n\n", $hours, $minutes, $seconds);
    
        // Progress bars
        $this->displayProgressBar("📚 Manga", $this->processedManga, $this->totalManga);
        $this->displayProgressBar("📑 Chapters", $this->processedChapters, $this->totalChapters);
        $this->displayProgressBar("🖼️  Images", $this->processedImages, $this->totalImages);
        echo "\n";
    
        // Statistics
        echo "📊 Thống kê Download:\n";
        echo "   ✓ Thành công: {$this->downloadStats['success']}\n";
        echo "   ✗ Thất bại: {$this->downloadStats['failed']}\n";
        echo "   ↻ Số lần retry: {$this->downloadStats['retries']}\n";
        echo "   💾 Tổng dung lượng: " . $this->formatBytes($this->downloadStats['totalBytes']) . "\n\n";
    
        echo "📤 Thống kê Upload:\n";
        echo "   ✓ Thành công: {$this->uploadStats['success']}\n";
        echo "   ✗ Thất bại: {$this->uploadStats['failed']}\n";
        echo "   💾 Tổng dung lượng: " . $this->formatBytes($this->uploadStats['totalBytes']) . "\n\n";
    
        // Active tasks
        if (!empty($this->activeTasks)) {
            echo "⚙️  Công việc đang thực hiện:\n";
            echo str_repeat("-", 50) . "\n";
            foreach ($this->activeTasks as $taskId => $task) {
                $duration = time() - $task['startTime'];
                $progress = isset($task['progress']) ? " - {$task['progress']}%" : "";
                echo "   • {$task['description']} (" . $this->formatDuration($duration) . "{$progress})\n";
            }
            echo str_repeat("-", 50) . "\n\n";
        }
    
        // Errors
        if (!empty($this->errors)) {
            echo "❌ Lỗi gần đây:\n";
            echo str_repeat("-", 50) . "\n";
            $lastErrors = array_slice($this->errors, -3);  // Show only last 3 errors
            foreach ($lastErrors as $error) {
                echo "   • [{$error['time']}] {$error['message']}\n";
            }
            echo str_repeat("-", 50) . "\n\n";
        }
    
        // Warnings
        if (!empty($this->warningMessages)) {
            echo "⚠️  Cảnh báo gần đây:\n";
            echo str_repeat("-", 50) . "\n";
            $lastWarnings = array_slice($this->warningMessages, -2);  // Show only last 2 warnings
            foreach ($lastWarnings as $warning) {
                echo "   • [{$warning['time']}] {$warning['message']}\n";
            }
            echo str_repeat("-", 50) . "\n";
        }
    
        // Move cursor to bottom
        echo "\n";
    }
    
    private function displayProgressBar($label, $current, $total) {
        $percent = $total > 0 ? round(($current / $total) * 100, 2) : 0;
        $width = 40;  // Progress bar width
        $completed = round(($percent / 100) * $width);
        
        echo sprintf(
            "%s: [%s%s] %6.2f%%\n",
            str_pad($label, 15, " ", STR_PAD_RIGHT),
            str_repeat("█", $completed),
            str_repeat("░", $width - $completed),
            $percent
        );
        echo sprintf("   Đã xử lý: %d/%d\n\n", $current, $total);
    }

    private function displayActiveTasks() {
        $this->displayProgress();
    }

    private function getProgressBar($percent, $length = 50) {
        $completed = round(($percent / 100) * $length);
        return str_repeat("█", $completed) . str_repeat("░", $length - $completed);
    }

    // Utility methods
    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $level = 0;
        while ($bytes >= 1024 && $level < count($units) - 1) {
            $bytes /= 1024;
            $level++;
        }
        return round($bytes, 2) . ' ' . $units[$level];
    }

    private function formatDuration($seconds) {
        return sprintf(
            '%02d:%02d:%02d',
            floor($seconds / 3600),
            floor($seconds / 60 % 60),
            $seconds % 60
        );
    }

    // Statistics and reporting
    private function calculateSuccessRate($processed, $total) {
        return $total > 0 ? round(($processed / $total) * 100, 2) : 0;
    }

    private function calculateAverageSpeed() {
        $runtime = time() - $this->startTime ?: 1;
        return [
            'manga_per_hour' => round(($this->processedManga / $runtime) * 3600, 2),
            'chapters_per_hour' => round(($this->processedChapters / $runtime) * 3600, 2),
            'images_per_hour' => round(($this->processedImages / $runtime) * 3600, 2)
        ];
    }

    public function saveStats() {
        $stats = [
            'totalRuntime' => time() - $this->startTime,
            'manga' => [
                'total' => $this->totalManga,
                'processed' => $this->processedManga
            ],
            'chapters' => [
                'total' => $this->totalChapters,
                'processed' => $this->processedChapters
            ],
            'images' => [
                'total' => $this->totalImages,
                'processed' => $this->processedImages
            ],
            'downloads' => $this->downloadStats,
            'uploads' => $this->uploadStats,
            'taskStats' => $this->taskStats,
            'errors' => $this->errors,
            'warnings' => $this->warningMessages,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        $filename = 'crawl_stats_' . date('Y-m-d_H-i-s') . '.json';
        file_put_contents($filename, json_encode($stats, JSON_PRETTY_PRINT));
        
        $this->generateSummaryReport();
    }

    private function generateSummaryReport() {
        $summary = [
            'runtime' => $this->formatDuration(time() - $this->startTime),
            'success_rate' => [
                'manga' => $this->calculateSuccessRate($this->processedManga, $this->totalManga),
                'chapters' => $this->calculateSuccessRate($this->processedChapters, $this->totalChapters),
                'images' => $this->calculateSuccessRate($this->processedImages, $this->totalImages)
            ],
            'error_rate' => $this->processedManga > 0 ? 
                round(count($this->errors) / $this->processedManga, 4) : 0,
            'average_speed' => $this->calculateAverageSpeed()
        ];

        file_put_contents(
            'crawl_summary_' . date('Y-m-d_H-i-s') . '.txt',
            print_r($summary, true)
        );
    }
}