<?php

function updateMangaCategories($mangaId, $newCategories) {
    $tracker = ProgressTracker::getInstance();
    $taskId = "update_categories_{$mangaId}";
    $tracker->addActiveTask($taskId, "Đang cập nhật categories cho manga {$mangaId}");

    try {
        // Fetch existing categories
        $fetchTaskId = "{$taskId}_fetch";
        $tracker->addActiveTask($fetchTaskId, "Đang lấy categories hiện tại");
        
        $existingCategories = $this->fetchExistingCategories($mangaId);
        $tracker->removeActiveTask($fetchTaskId);

        // Process new categories
        $tracker->addActiveTask("{$taskId}_process", 
            "Đang xử lý " . count($newCategories) . " categories mới");

        foreach ($newCategories as $category) {
            $categoryTaskId = "{$taskId}_cat_" . md5($category['name']);
            $tracker->addActiveTask($categoryTaskId, 
                "Đang xử lý category: {$category['name']}");

            try {
                // Get or create category
                $categoryId = $this->getOrCreateCategory($category);
                
                // Link category to manga if not already linked
                if (!isset($existingCategories[$category['name']])) {
                    $this->linkCategoryToManga($mangaId, $categoryId, $category['name']);
                }

            } catch (Exception $e) {
                $tracker->addError("Lỗi xử lý category {$category['name']}: " . $e->getMessage());
            } finally {
                $tracker->removeActiveTask($categoryTaskId);
            }
        }

        $tracker->removeActiveTask("{$taskId}_process");

    } catch (Exception $e) {
        $tracker->addError("Lỗi cập nhật categories cho manga {$mangaId}: " . $e->getMessage());
    } finally {
        $tracker->removeActiveTask($taskId);
    }
}

function fetchExistingCategories($mangaId) {
    $query = "SELECT c.id, c.name FROM categories c
              JOIN manga_categories mc ON c.id = mc.category_id
              WHERE mc.manga_id = ?";
    
    $stmt = $this->db->prepare($query);
    if (!$stmt) {
        throw new Exception("Lỗi prepare statement: " . $this->db->error);
    }

    $stmt->bind_param("i", $mangaId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $existingCategories = [];
    while ($row = $result->fetch_assoc()) {
        $existingCategories[$row['name']] = $row['id'];
    }

    return $existingCategories;
}

function getOrCreateCategory($category) {
    // Check if category exists
    $stmt = $this->db->prepare("SELECT id FROM categories WHERE name = ?");
    if (!$stmt) {
        throw new Exception("Lỗi prepare statement check category: " . $this->db->error);
    }

    $stmt->bind_param("s", $category['name']);
    $stmt->execute();
    $result = $stmt->get_result();
    $categoryRow = $result->fetch_assoc();

    if ($categoryRow) {
        return $categoryRow['id'];
    }

    // Create new category
    $stmt = $this->db->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");
    if (!$stmt) {
        throw new Exception("Lỗi prepare statement insert category: " . $this->db->error);
    }

    $stmt->bind_param("ss", $category['name'], $category['slug']);
    if (!$stmt->execute()) {
        throw new Exception("Lỗi insert category: " . $stmt->error);
    }

    return $this->db->insert_id;
}

function linkCategoryToManga($mangaId, $categoryId, $categoryName) {
    $tracker = ProgressTracker::getInstance();
    $taskId = "link_category_{$mangaId}_{$categoryId}";
    $tracker->addActiveTask($taskId, "Đang liên kết category {$categoryName}");

    try {
        $query = "INSERT IGNORE INTO manga_categories (manga_id, category_id) VALUES (?, ?)";
        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            throw new Exception("Lỗi prepare statement link category: " . $this->db->error);
        }

        $stmt->bind_param("ii", $mangaId, $categoryId);
        if (!$stmt->execute()) {
            throw new Exception("Lỗi link category: " . $stmt->error);
        }

    } catch (Exception $e) {
        $tracker->addError("Lỗi liên kết category {$categoryName}: " . $e->getMessage());
        throw $e;
    } finally {
        $tracker->removeActiveTask($taskId);
    }
}