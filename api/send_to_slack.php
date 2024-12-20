<?php
require_once '../config.php';

function convertHtmlToSlackFormat($html) {
    libxml_use_internal_errors(true);

    $dom = new DOMDocument();
    $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    libxml_clear_errors();

    $text = '';

    foreach ($dom->getElementsByTagName('p') as $paragraph) {
        $text .= $paragraph->textContent . "\n";
    }

    foreach ($dom->getElementsByTagName('img') as $img) {
        if ($img instanceof DOMElement) {
            $src = $img->getAttribute('src');
            $text .= "<" . $src . ">\n";
        }
    }

    return $text;
}

function sendToSlack($channel, $htmlMessage) {
    $url = "https://slack.com/api/chat.postMessage";
    $token = "xoxb-442172359975-8228144726832-aUHPyMgVvHTDYjaoJ2KxwYEn"; // Replace with your Slack bot token

    $message = convertHtmlToSlackFormat($htmlMessage);

    $data = [
        "channel" => $channel,
        "text" => $message,
        "blocks" => json_encode([
            [
                "type" => "section",
                "text" => [
                    "type" => "mrkdwn",
                    "text" => $message
                ]
            ]
        ])
    ];

    $headers = [
        "Content-Type: application/json",
        "Authorization: Bearer $token"
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        return ["success" => false, "error" => curl_error($ch)];
    }

    curl_close($ch);

    $responseDecoded = json_decode($response, true);
    error_log("Slack API response: " . print_r($responseDecoded, true));

    return $responseDecoded;
}

function sendMessages() {
    global $db;

    // Fetch all unsent messages
    $query = "SELECT * FROM slack_messages WHERE sent = 0";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $channel = $row['channel'];
        $message = $row['rich_text_format'];

        // Send the message to Slack
        $response = sendToSlack($channel, $message);

        // If successfully sent, mark the message as sent
        if (!empty($response['ok']) && $response['ok'] === true) {
            $updateQuery = "UPDATE slack_messages SET sent = 1 WHERE id = ?";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->bind_param("i", $id);
            $updateStmt->execute();
        } else {
            error_log("Failed to send message ID $id: " . print_r($response, true));
        }
    }

    $stmt->close();
}

// Trigger message sending
sendMessages();
?>
