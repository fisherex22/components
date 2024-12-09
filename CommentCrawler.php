<?php
require_once '/Users/binblacker/sg-4241725359072075-main/vendor/autoload.php';

if (!function_exists('create_slug')) {
    function create_slug($string)
    {
        $search = array(
            '#(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)#',
            '#(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)#',
            '#(ì|í|ị|ỉ|ĩ)#',
            '#(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)#',
            '#(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)#',
            '#(ỳ|ý|ỵ|ỷ|ỹ)#',
            '#(đ)#',
            '#(À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ)#',
            '#(È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ)#',
            '#(Ì|Í|Ị|Ỉ|Ĩ)#',
            '#(Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ)#',
            '#(Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ)#',
            '#(Ỳ|Ý|Ỵ|Ỷ|Ỹ)#',
            '#(Đ)#',
            "/[^a-zA-Z0-9\-\_]/",
        );
        $replace = array(
            'a',
            'e',
            'i',
            'o',
            'u',
            'y',
            'd',
            'A',
            'E',
            'I',
            'O',
            'U',
            'Y',
            'D',
            '-',
        );
        $string = preg_replace($search, $replace, $string);
        $string = preg_replace('/(-)+/', '-', $string);
        $string = strtolower($string);
        return $string;
    }
}

class CommentCrawler {
    private $db;

    public function __construct($dbConfig) {
        $this->db = new mysqli($dbConfig['host'], $dbConfig['user'], $dbConfig['pass'], $dbConfig['dbname']);
        if ($this->db->connect_error) {
            die("Connection failed: " . $this->db->connect_error);
        }
    }

    public function __destruct() {
        $this->db->close();
    }

    public function crawlComments($mangaId) {
        $page = 0;
        $headers = [
            'authority: goctruyentranhvui6.com',
            'authorization: Bearer eyJhbGciOiJIUzUxMiJ9.eyJzdWIiOiJKb2UgTmd1eWVuIiwiY29taWNJZHMiOltdLCJyb2xlSWQiOm51bGwsImdyb3VwSWQiOm51bGwsImFkbWluIjpmYWxzZSwicmFuayI6MCwicGVybWlzc2lvbiI6W10sImlkIjoiMDAwMDYwNDM0NSIsInRlYW0iOmZhbHNlLCJpYXQiOjE3MzA0MTk3MzUsImVtYWlsIjoibnVsbCJ9.OaIb8huTKzxc7f6W0BW1MsHC1HwNoQHMcV1WdQObDBV0IDvVaev00Qc4HdtWKIlU_uz-RKgn5UGC58nRDhC5UA',
            'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'accept-language: en-US,en;q=0.9,vi;q=0.8',
            'cache-control: max-age=0',
            'cookie: _ga=GA1.1.1748985977.1730419651; UGVyc2lzdFN0b3JhZ2U=%7B%7D; usid=92F594C4A8D52C1AD8DEC32DAE9759FF; __PPU_ppucnt=1; _ga_V1FSZ4YFJH=GS1.1.1730419650.1.1.1730419773.0.0.0',
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
            'sec-fetch-site: none',
            'sec-fetch-user: ?1',
            'upgrade-insecure-requests: 1',
            'user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36',
        ];
        
        do {
            $url = "https://goctruyentranhvui6.com/api/comic/comments?value={$mangaId}&extraData=&p={$page}&commentId=";
            echo "Crawling page: $url\n";

            $response = $this->fetchData($url);
            if ($response && isset($response['result']['comments']) && !empty($response['result']['comments'])) {
                foreach ($response['result']['comments'] as $comment) {
                    $this->processComment($comment, $mangaId);

                    // Xử lý các bình luận con
                    if (isset($comment['childrenComments']) && is_array($comment['childrenComments'])) {
                        foreach ($comment['childrenComments'] as $childComment) {
                            $this->processComment($childComment, $mangaId, $comment['id']); // Pass API parent ID
                        }
                    }
                }
                $hasMore = $response['result']['hasMore'];
            } else {
                $hasMore = false;
                echo "No more comments found.\n";
            }
            $page++;
        } while ($hasMore);
    }

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
        curl_close($ch);
        return json_decode($response, true);
    }

    private function processComment($comment, $mangaId, $apiParentId = null) {
        try {
            // Kiểm tra comment đã tồn tại chưa 
            $apiCommentId = $comment['id'];
            $checkQuery = "SELECT id FROM comments WHERE api_comment_id = ? FOR UPDATE"; // Thêm FOR UPDATE
            
            // Bắt đầu transaction
            $this->db->begin_transaction();
            
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->bind_param("s", $apiCommentId);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            // Nếu comment đã tồn tại
            if ($result->num_rows > 0) {
                $existingComment = $result->fetch_assoc();
                echo "Comment {$apiCommentId} already exists with ID: {$existingComment['id']}\n";
                
                // Commit transaction và return
                $this->db->commit();
                return;
            }
    
            // Xử lý comment mới
            $parentCommentDbId = null;
            if ($apiParentId) {
                $parentQuery = "SELECT id FROM comments WHERE api_comment_id = ?";
                $parentStmt = $this->db->prepare($parentQuery);
                $parentStmt->bind_param("s", $apiParentId);
                $parentStmt->execute();
                $parentResult = $parentStmt->get_result();
                $parentRow = $parentResult->fetch_assoc();
                $parentCommentDbId = $parentRow ? $parentRow['id'] : null;
            }
    
            // Process comment data
            $username = $comment['username'];
            $content = $this->cleanContent($comment['content']);
            $dateTime = $this->convertDate($comment['dateTime']);
            $chapterNumber = $comment['chapterNumber'];
            
            // Get or create user
            $userId = $this->getOrCreateUser($username);
            
            // Get chapter ID
            $chapterId = $this->getChapterId($chapterNumber, $mangaId);
            
            // Insert new comment
            $insertQuery = "INSERT INTO comments (user_id, manga_id, chapter_id, content, created_at, parent_id, api_comment_id) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)";
            $insertStmt = $this->db->prepare($insertQuery);
            $insertStmt->bind_param("iiissss", $userId, $mangaId, $chapterId, $content, $dateTime, $parentCommentDbId, $apiCommentId);
            
            if (!$insertStmt->execute()) {
                throw new Exception("Failed to insert comment: " . $insertStmt->error);
            }
            
            $insertedCommentId = $this->db->insert_id;
            
            // Commit transaction
            $this->db->commit();
            
            echo "Successfully inserted new comment:\n";
            echo "- DB ID: $insertedCommentId\n";
            echo "- API ID: $apiCommentId\n";
            echo "- User: $username\n";
            
            // Process children comments after commit
            if (isset($comment['childrenComments']) && is_array($comment['childrenComments'])) {
                foreach ($comment['childrenComments'] as $childComment) {
                    $this->processComment($childComment, $mangaId, $apiCommentId);
                }
            }
            
        } catch (Exception $e) {
            // Rollback nếu có lỗi
            $this->db->rollback();
            echo "Error processing comment: " . $e->getMessage() . "\n";
        }
    }
    
    
    // Thêm hàm để hiển thị số liệu thống kê
public function showStats($mangaId) {
    // Tổng số comments
    $totalQuery = "SELECT COUNT(*) as total FROM comments WHERE manga_id = ?";
    $stmt = $this->db->prepare($totalQuery);
    $stmt->bind_param("i", $mangaId);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    
    // Số parent comments
    $parentQuery = "SELECT COUNT(*) as parent_count FROM comments 
                   WHERE manga_id = ? AND parent_id IS NULL";
    $stmt = $this->db->prepare($parentQuery);
    $stmt->bind_param("i", $mangaId);
    $stmt->execute();
    $parentCount = $stmt->get_result()->fetch_assoc()['parent_count'];
    
    // Số child comments
    $childCount = $total - $parentCount;
    
    echo "\nStatistics for manga $mangaId:\n";
    echo "Total comments: $total\n";
    echo "Parent comments: $parentCount\n";
    echo "Child comments: $childCount\n";
}

// Thêm hàm để kiểm tra và hiển thị cấu trúc comments
private function getCommentChildren($parentId) {
    $query = "
        SELECT 
            c.id,
            c.content,
            c.api_comment_id,
            u.username,
            c.created_at
        FROM comments c
        JOIN users u ON c.user_id = u.id 
        WHERE c.parent_id = ?
        ORDER BY c.created_at ASC
    ";
    
    $stmt = $this->db->prepare($query);
    if ($stmt === false) {
        echo "Prepare failed: " . $this->db->error . "\n";
        return [];
    }
    
    $stmt->bind_param("i", $parentId);
    if (!$stmt->execute()) {
        echo "Execute failed: " . $stmt->error . "\n";
        return [];
    }
    
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

public function verifyCommentStructure($mangaId) {
    $query = "
        SELECT 
            c.id,
            c.api_comment_id,
            c.content,
            c.parent_id,
            u.username,
            c.created_at,
            (SELECT COUNT(*) FROM comments WHERE parent_id = c.id) as child_count
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.manga_id = ? AND c.parent_id IS NULL
        ORDER BY c.created_at DESC
    ";
    
    $stmt = $this->db->prepare($query);
    if ($stmt === false) {
        echo "Prepare failed: " . $this->db->error . "\n";
        return;
    }
    
    $stmt->bind_param("i", $mangaId);
    if (!$stmt->execute()) {
        echo "Execute failed: " . $stmt->error . "\n";
        return;
    }
    
    $result = $stmt->get_result();
    
    echo "\nComment structure for manga $mangaId:\n";
    echo "----------------------------------------\n";
    
    $totalParents = 0;
    $totalChildren = 0;
    
    while ($row = $result->fetch_assoc()) {
        $totalParents++;
        echo "\nParent Comment #{$totalParents}:\n";
        echo "ID: {$row['id']} (API ID: {$row['api_comment_id']})\n";
        echo "Author: {$row['username']}\n";
        echo "Content: {$row['content']}\n";
        echo "Created: {$row['created_at']}\n";
        echo "Child comments: {$row['child_count']}\n";
        
        if ($row['child_count'] > 0) {
            $children = $this->getCommentChildren($row['id']);
            echo "\nReplies:\n";
            foreach ($children as $index => $child) {
                $totalChildren++;
                echo "  " . ($index + 1) . ". Reply by {$child['username']}\n";
                echo "     ID: {$child['id']} (API ID: {$child['api_comment_id']})\n";
                echo "     Content: {$child['content']}\n";
                echo "     Created: {$child['created_at']}\n";
            }
        }
        echo "----------------------------------------\n";
    }
    
    echo "\nSummary:\n";
    echo "Total parent comments: $totalParents\n";
    echo "Total child comments: $totalChildren\n";
    echo "Total comments: " . ($totalParents + $totalChildren) . "\n";
}
    
    
    
        
    
    private function getParentCommentId($apiParentId) {
        $query = "SELECT id FROM comments WHERE api_comment_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $apiParentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row ? $row['id'] : null;
    }
    
    
    

    private function cleanContent($content) {
        return strip_tags(html_entity_decode($content));
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

    private function getOrCreateUser($username) {
        // Kiểm tra nếu người dùng đã tồn tại
        $query = "SELECT id FROM users WHERE username = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row) {
            return $row['id']; // Nếu người dùng đã tồn tại, trả về user_id
        } else {
            // Tạo email từ slug của username
            $slugUsername = create_slug($username);  // Sử dụng hàm create_slug thay vì slugify
            $email = $slugUsername . '@gmail.com';

            // Tạo mật khẩu mặc định và ngày tạo
            $password = password_hash('password123', PASSWORD_BCRYPT);
            $createdAt = date('Y-m-d H:i:s');

            // Chèn người dùng mới
            $insertUserQuery = "INSERT INTO `users` (`username`, `email`, `password`, `role`, `created_at`, `is_bot`)
                                VALUES (?, ?, ?, 'user', ?, 1)";
            $stmt = $this->db->prepare($insertUserQuery);
            $stmt->bind_param("ssss", $username, $email, $password, $createdAt);
            $stmt->execute();

            return $this->db->insert_id; // Trả về ID của người dùng vừa được tạo
        }
    }

    private function getChapterId($chapterNumber, $mangaId) {
        $query = "SELECT id FROM chapters WHERE chapter_number = ? AND manga_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ii", $chapterNumber, $mangaId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row) {
            return $row['id'];
        } else {
            $createdAt = date('Y-m-d H:i:s');
            $insertChapterQuery = "INSERT INTO `chapters` (`manga_id`, `chapter_number`, `created_at`)
                                   VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($insertChapterQuery);
            $stmt->bind_param("iis", $mangaId, $chapterNumber, $createdAt);
            $stmt->execute();
            return $this->db->insert_id;
        }
    }
}

$dbConfig = [
    'host' => '64.71.152.17',
    'user' => 'root',
    'pass' => 'FkZmcRGGpg3LQSRAzV8v',
    'dbname' => 'struyenc_s2truyen'
];

$crawler = new CommentCrawler($dbConfig);
//$crawler->crawlComments($mangaId);

?>
