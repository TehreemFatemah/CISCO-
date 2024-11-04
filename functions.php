<?php

// Function to sanitize user input
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Function to log API requests and responses
function logApiResponse($url, $headers, $data, $response, $type) {
    // Create log directory if it doesn't exist
    if (!is_dir('logs')) {
        mkdir('logs', 0777, true);
    }

    // Log API request and response
    $logFile = 'logs/api_log_' . date('Y_m_d') . '.txt';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "=============================================\n";
    $logMessage .= "[$timestamp] $type Request:\n";
    $logMessage .= "URL: $url\n";
    $logMessage .= "Headers: " . json_encode($headers, JSON_PRETTY_PRINT) . "\n";
    if (!empty($data)) {
        $logMessage .= "Request Data: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    }
    $logMessage .= "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n";
    $logMessage .= "=============================================\n\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// Function to make API requests (GET, POST, DELETE)
function makeApiRequest($url, $headers, $method = 'GET', $data = []) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));

    if ($method === 'POST' || $method === 'DELETE') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (curl_errno($ch)) {
        return ['error' => 'Curl error: ' . curl_error($ch)];
    }

    curl_close($ch);
    return ['http_code' => $httpCode, 'response' => json_decode($response, true)];
}

// Centralized response handler
function handleApiResponse($response, $action) {
    if (isset($response['response']['error'])) {
        return "Error in $action: " . $response['response']['error']['message'];
    }
    $totalAffected = $response['response']['data']['totalCount'] ?? 0;
    return $totalAffected > 0 ? "Success! Action '$action' completed." : "No emails affected by the $action action.";
}
?>
