<?php
// Set header to return JSON
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration for WAMP
$db_host = 'localhost'; // localhost for WAMP
$db_user = 'root'; // Default username
$db_pass = ''; // Default password (blank)
$db_name = 'scamcheck_db';
$db_port = 3306;

// Response array
$response = [];

try {
    // Create connection
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
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

        $article_url = $conn->real_escape_string($article_url);
        $message_content = $conn->real_escape_string($message_content);

        $sql = "INSERT INTO article_submissions (article_url, message_content, created_at)
                VALUES ('$article_url', '$message_content', NOW())";

        if ($conn->query($sql)) {
            $response = [
                'status' => 'success',
                'message' => 'Form submitted successfully!',
                'insert_id' => $conn->insert_id
            ];
        } else {
            throw new Exception("Database error: " . $conn->error);
        }
    }

    // ✅ GET REQUEST → Retrieve all records
    elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $sql = "SELECT id, article_url, message_content, created_at
                FROM article_submissions ORDER BY created_at DESC";
        $result = $conn->query($sql);

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
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
    $response = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
}

// Return JSON
echo json_encode($response, JSON_PRETTY_PRINT);
?>
