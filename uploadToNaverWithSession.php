<?php
namespace Manga;
use ProgressTracker;

trait UploadToNaverWithSession {
function uploadToNaverWithSession($filePath, $sessionKey, $index = 0, $filename = null) {
    $tracker = ProgressTracker::getInstance();
    $taskId = "upload_file_" . md5($filePath);
    $tracker->addActiveTask($taskId, "Đang upload ảnh " . basename($filePath));

    try {
        if (!file_exists($filePath)) {
            throw new Exception("File not found: $filePath");
        }

        $randomValue = rand(1, 100);
        $uploadMethod = ($randomValue <= 70) ? 'uploadMemo' : 'uploadWithSession';
        $scriptPath = realpath(__DIR__ . '/naverUploader.js');

        $tracker->addActiveTask($taskId . "_method", "Sử dụng phương thức: {$uploadMethod}");

        $command = $uploadMethod === 'uploadMemo' 
            ? sprintf('node "%s" uploadMemo "%s" 2>&1', $scriptPath, $filePath)
            : sprintf('node "%s" uploadWithSession "%s" "%s" %d "%s" 2>&1', 
                $scriptPath, $filePath, $sessionKey, $index, $filename);

        $tracker->addActiveTask($taskId . "_exec", "Đang thực thi lệnh upload");
        $output = shell_exec($command);
        $tracker->removeActiveTask($taskId . "_exec");

        if (!$output) {
            throw new Exception("No output from upload command");
        }

        if (preg_match('/\{(?:[^{}]|(?R))*\}/', $output, $matches)) {
            $jsonStr = $matches[0];
            $result = json_decode($jsonStr, true);

            if (!$result) {
                throw new Exception("Failed to parse JSON: " . json_last_error_msg());
            }

            if (!isset($result['success']) || !$result['success']) {
                throw new Exception("Upload failed: " . ($result['error'] ?? 'Unknown error'));
            }

            $uploadUrl = $this->extractUploadUrl($result, $uploadMethod);
            if ($uploadUrl) {
                $tracker->incrementProcessedImages();
                return $uploadUrl;
            }
            
            throw new Exception("No URL in successful response");
        }

        throw new Exception("No valid JSON found in output");

    } catch (Exception $e) {
        $tracker->addError("Lỗi upload ảnh " . basename($filePath) . ": " . $e->getMessage());
        return null;
    } finally {
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
        $tracker->removeActiveTask($taskId . "_method");
        $tracker->removeActiveTask($taskId);
        }
    }
}

trait UploadBatchToNaverWithSession {
    function uploadBatchToNaverWithSession($files, $sessionKey) {
    $tracker = ProgressTracker::getInstance();
    $taskId = "batch_upload_" . md5(implode('', $files));
    $tracker->addActiveTask($taskId, "Đang upload batch " . count($files) . " ảnh");

    $results = [];
    $errors = [];
    $currentIndex = 0;
    
    try {
        $scriptPath = realpath(__DIR__ . '/naverUploader.js');
        $commands = [];

        $randomValue = rand(1, 100);
        $useMemoUpload = ($randomValue <= 70);

        $uploadMethod = $useMemoUpload ? 'Memo Batch' : 'Individual';
        $tracker->addActiveTask($taskId . "_method", "Sử dụng phương thức: {$uploadMethod}");

        if ($useMemoUpload) {
            $commands = $this->prepareMemoUploadCommands($files, $scriptPath, $tracker);
        } else {
            $commands = $this->prepareIndividualUploadCommands($files, $sessionKey, $scriptPath, $tracker);
        }

        foreach ($commands as $commandIndex => $command) {
            $commandTaskId = "{$taskId}_cmd_{$commandIndex}";
            $tracker->addActiveTask($commandTaskId, 
                "Đang thực thi lệnh upload " . ($commandIndex + 1) . "/" . count($commands));

            $uploadResult = $this->executeUploadCommand($command, $useMemoUpload, $currentIndex, $results, $errors, $tracker);
            if ($uploadResult) {
                $currentIndex = $uploadResult;
            }

            $tracker->removeActiveTask($commandTaskId);
        }

    } catch (Exception $e) {
        $tracker->addError("Lỗi batch upload: " . $e->getMessage());
    } finally {
        foreach ($files as $filePath) {
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }
        $tracker->removeActiveTask($taskId . "_method");
        $tracker->removeActiveTask($taskId);
    }

    return [
        'success' => !empty($results),
        'urls' => $results,
        'errors' => $errors
        ];
    }
}

trait PrepareMemoUploadCommands {
    function prepareMemoUploadCommands($files, $scriptPath, $tracker) {
    $commands = [];
    $batches = array_chunk($files, 10);
    foreach ($batches as $batchIndex => $batchFiles) {
        $filePathsStr = implode('" "', $batchFiles);
        $commands[] = [
            'cmd' => sprintf('node "%s" uploadMemo "%s" 2>&1', $scriptPath, $filePathsStr),
            'files' => $batchFiles
        ];
        $tracker->addActiveTask("prep_batch_{$batchIndex}", 
            "Chuẩn bị batch " . ($batchIndex + 1) . "/" . count($batches));
    }
    return $commands;
    }
}

trait PrepareIndividualUploadCommands {
    function prepareIndividualUploadCommands($files, $sessionKey, $scriptPath, $tracker) {
    $commands = [];
    foreach ($files as $index => $filePath) {
        if (file_exists($filePath)) {
            $commands[] = [
                'cmd' => sprintf('node "%s" uploadWithSession "%s" "%s" %d 2>&1',
                    $scriptPath, $filePath, $sessionKey, $index),
                'files' => [$filePath]
            ];
            $tracker->addActiveTask("prep_file_{$index}", 
                "Chuẩn bị upload file " . ($index + 1) . "/" . count($files));
        }
    }
    return $commands;
    }
}

trait ExecuteUploadCommand {
    function executeUploadCommand($command, $useMemoUpload, $currentIndex, &$results, &$errors, $tracker) {
    $output = shell_exec($command['cmd']);
    if (!$output) {
        $errors[$currentIndex] = "No output from upload command";
        return null;
    }

    if (preg_match('/\{(?:[^{}]|(?R))*\}/', $output, $matches)) {
        $result = json_decode($matches[0], true);
        if (!$this->validateUploadResult($result, $currentIndex, $errors)) {
            return null;
        }

        return $this->processUploadResult($result, $useMemoUpload, $currentIndex, $results, $tracker);
    }

    $errors[$currentIndex] = "No valid JSON found in output";
    return null;
    }
}

trait ValidateUploadResult {
    function validateUploadResult($result, $currentIndex, &$errors) {
    if (!$result) {
        $errors[$currentIndex] = "Failed to parse JSON: " . json_last_error_msg();
        return false;
    }
    if (!isset($result['success']) || !$result['success']) {
        $errors[$currentIndex] = "Upload failed: " . ($result['error'] ?? 'Unknown error');
        return false;
    }
    return true;
    }
}

trait ProcessUploadResult {
    function processUploadResult($result, $useMemoUpload, $currentIndex, &$results, $tracker) {
    if ($useMemoUpload && isset($result['urls']) && is_array($result['urls'])) {
        foreach ($result['urls'] as $idx => $url) {
            $results[$currentIndex + $idx] = $url;
            $tracker->incrementProcessedImages();
        }
        return $currentIndex + count($result['urls']);
    } elseif (isset($result['url'])) {
        $results[$currentIndex] = $result['url'];
        $tracker->incrementProcessedImages();
        return $currentIndex + 1;
    }
    return $currentIndex;
    }
}

trait ExtractUploadUrl {
    function extractUploadUrl($result, $uploadMethod) {
    if ($uploadMethod === 'uploadMemo' && isset($result['urls'][0])) {
        return $result['urls'][0];
    } elseif (isset($result['url'])) {
        return $result['url'];
    }
    return null;
    }
}