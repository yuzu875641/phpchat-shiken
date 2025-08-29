<?php

// Allow cross-origin requests
header('Access-Control-Allow-Origin: *');
// Set the content type to JSON
header('Content-Type: application/json');

// Handle GET requests for fetching messages
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_messages') {
    try {
        require_once('db.php');

        // Select the last 50 messages, ordered from newest to oldest
        $sql = "SELECT user_id, content FROM messages ORDER BY created_at DESC LIMIT 50";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();

        $messages = $stmt->fetchAll();

        // Send a 200 OK response with the messages as JSON
        http_response_code(200);
        echo json_encode($messages);
        exit; // Terminate the script after sending the response

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'An unexpected error occurred.']);
        exit;
    }
}

// Handle POST requests for posting a new message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Decode the JSON data from the request body
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate that both user_id and content are present
    if (!isset($data['user_id']) || !isset($data['content'])) {
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'User ID and content are required.']);
        exit;
    }

    $user_id = $data['user_id'];
    $content = $data['content'];

    try {
        require_once('db.php');

        // Prepare a statement for a secure insertion
        $sql = "INSERT INTO messages (user_id, content) VALUES (:user_id, :content)";
        $stmt = $pdo->prepare($sql);

        // Bind the parameters to prevent SQL injection
        $stmt->bindValue(':user_id', $user_id);
        $stmt->bindValue(':content', $content);

        // Execute the statement
        $stmt->execute();

        // On success, send a 201 Created response
        http_response_code(201);
        echo json_encode(['status' => 'success']);

    } catch (PDOException $e) {
        http_response_code(500); // Internal Server Error
        error_log("Database Error: " . $e->getMessage());
        echo json_encode(['error' => 'Database operation failed.']);
    } catch (Exception $e) {
        http_response_code(500); // Internal Server Error
        error_log("General Error: " . $e->getMessage());
        echo json_encode(['error' => 'An unexpected error occurred.']);
    }

} else {
    // For any other request method, return a 405 error
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
}
