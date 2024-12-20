<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        // Fetch all messages
        $query = "SELECT * FROM slack_messages ORDER BY created_at DESC";
        $result = $conn->query($query);

        $messages = [];
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }

        echo json_encode($messages);
        break;

    case 'POST':
        // Add a new message
        $message = $conn->real_escape_string($input['message']);
        $created_at = date('Y-m-d H:i:s');

        $query = "INSERT INTO slack_messages (message, created_at, sent) VALUES ('$message', '$created_at', 0)";
        if ($conn->query($query)) {
            echo json_encode(['success' => true, 'id' => $conn->insert_id]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => $conn->error]);
        }
        break;

        case 'PUT':
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
            break;
        

        case 'DELETE':
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
            break;
        

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

$conn->close();
