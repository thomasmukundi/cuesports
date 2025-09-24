<?php

echo "Getting fresh JWT token...\n";

$url = 'http://127.0.0.1:8000/api/admin/login';
$credentials = [
    'email' => 'admin@cuesports.com',
    'password' => 'password'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($credentials));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "Login Response Code: $httpCode\n";
if ($error) {
    echo "CURL Error: $error\n";
}

echo "Response:\n";
echo $response . "\n";

$data = json_decode($response, true);
if (isset($data['token'])) {
    echo "\n✅ New Token: " . $data['token'] . "\n";
    
    // Test the new token immediately
    echo "\nTesting new token with tournament initialization...\n";
    
    $testUrl = 'http://127.0.0.1:8000/api/admin/tournaments/2/initialize';
    $payload = json_encode(['level' => 'special']);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $testUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $data['token'],
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    $testResponse = curl_exec($ch);
    $testHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Tournament Init Response Code: $testHttpCode\n";
    echo "Tournament Init Response:\n";
    echo $testResponse . "\n";
    
} else {
    echo "\n❌ Failed to get token\n";
}

?>
