<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config.php';

// Parse the HTTP method
$method = $_SERVER['REQUEST_METHOD'];

// Get the input
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        // Fetch all messages
        $query = "SELECT * FROM slack_messages ORDER BY scheduled_time DESC";
        $result = $conn->query($query);

        $messages = [];
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }

        echo json_encode($messages);
        break;

    case 'POST':
        // Add a new message
        $channel = $conn->real_escape_string($input['channel']);
        $message = $conn->real_escape_string($input['message']);
        $scheduled_time = $conn->real_escape_string($input['scheduled_time']);
        
        $query = "INSERT INTO slack_messages (channel, message, scheduled_time) VALUES ('$channel', '$message', '$scheduled_time')";
        if ($conn->query($query)) {
            echo json_encode(['success' => true, 'id' => $conn->insert_id]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => $conn->error]);
        }
        break;

    case 'PUT':
        // Update an existing message
        
        $id = isset($input['id']) ? $input['id'] : null;
        $message = isset($input['message']) ? $input['message'] : null;
        
        if ($id && $message) {
            $query = "UPDATE slack_messages SET message='$message' WHERE id='$id'";
            if ($conn->query($query)) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => $conn->error]);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid input']);
        }        

    case 'DELETE':
        // Delete a message
        $id = isset($_GET['id']) ? $conn->real_escape_string($_GET['id']) : null;

        if ($id) {
            $query = "DELETE FROM slack_messages WHERE id='$id'";
            if ($conn->query($query)) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => $conn->error]);
            }
        } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid ID']);
        }


    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

// Close the connection
$conn->close();
?>
