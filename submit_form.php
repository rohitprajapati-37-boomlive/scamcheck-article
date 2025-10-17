<?php
// CORS headers सबसे पहले
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Error reporting (production में बंद करें)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ✅ CORRECT Database configuration
$db_host = 'coolify.vps.boomlive.in'; // बिना https:// के, बस hostname
// या अगर internal connection है तो
// $db_host = 'localhost'; 
// या IP address: $db_host = '192.168.x.x';

$db_user = 'root';
$db_pass = 'abcd';
$db_name = 'scamcheck_db';
$db_port = 3306;

// Response array
$response = [];

try {
    // Create connection
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Set charset
    $conn->set_charset("utf8mb4");

    // ✅ POST REQUEST → Insert new record
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $article_url = isset($_POST['article_url']) ? trim($_POST['article_url']) : '';
        $message_content = isset($_POST['message_content']) ? trim($_POST['message_content']) : '';

        if (empty($article_url) || empty($message_content)) {
            throw new Exception("Please fill all required fields.");
        }

        if (!filter_var($article_url, FILTER_VALIDATE_URL)) {
            throw new Exception("Please enter a valid URL.");
        }

        // Use prepared statements (more secure)
        $stmt = $conn->prepare("INSERT INTO article_submissions (article_url, message_content, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("ss", $article_url, $message_content);

        if ($stmt->execute()) {
            $response = [
                'status' => 'success',
                'message' => 'Form submitted successfully!',
                'insert_id' => $conn->insert_id
            ];
        } else {
            throw new Exception("Database error: " . $stmt->error);
        }
        
        $stmt->close();
    }

    // ✅ GET REQUEST → Retrieve all records
    elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $sql = "SELECT id, article_url, message_content, created_at 
                FROM article_submissions ORDER BY created_at DESC";
        $result = $conn->query($sql);

        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }

        $response = [
            'status' => 'success',
            'count' => count($data),
            'data' => $data
        ];
    }

    // ❌ Invalid Method
    else {
        throw new Exception("Invalid request method.");
    }

    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    $response = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
}

// Return JSON
echo json_encode($response);
exit;
