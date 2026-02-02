<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

$input = json_decode(file_get_contents('php://input'), true);
$user_input = trim($input['user_input'] ?? '');

if (empty($user_input)) {
    echo json_encode(['error' => 'No message provided']);
    exit;
}

// === Call Python Flask server ===
$ch = curl_init("http://127.0.0.1:5000/chat");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['user_input' => $user_input]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($httpCode !== 200) {
    echo json_encode([
        'error' => 'Server error. Try again.',
        'debug' => ['http_code' => $httpCode, 'curl_error' => $curlError, 'response' => $response]
    ]);
    exit;
}

echo $response;
?>
