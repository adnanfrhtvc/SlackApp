<?php
require_once '../config.php';

function sendToSlack($channel, $message) {
    $url = "https://slack.com/api/chat.postMessage";
    $token = "xoxb-your-slack-bot-token"; // Replace with your Slack bot token

    $data = [
        "channel" => $channel,
        "text" => $message, // For simple text
        // Optionally, use blocks for advanced formatting
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

    return json_decode($response, true);
}

// Example usage
$channel = "#general";
$message = "*Hello, world!* :tada: Here's a rich text example.";
$result = sendToSlack($channel, $message);

echo json_encode($result);
?>
