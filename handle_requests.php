<?php
// Configuration: API credentials and headers
$username = "admin";
$password = "Uf7!Rz+-}^kL8Z!a";  // Replace with your actual password

// Authorization Headers
$headers = [
    "Authorization: Basic " . base64_encode("$username:$password"),
    "Content-Type: application/json"
];

// Function to log API requests and responses
function logApiResponse($url, $headers, $data, $response, $type) {
    $logFile = 'logs/api_log.txt'; // Ensure the logs directory is writable
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "=============================================\n";
    $logMessage .= "[$timestamp] $type Request:\n";
    $logMessage .= "URL: $url\n";
    $logMessage .= "Headers: " . json_encode($headers, JSON_PRETTY_PRINT) . "\n";
    if (!empty($data)) {
        $logMessage .= "Request Data: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    }
    $logMessage .= "Response:\n" . json_encode($response, JSON_PRETTY_PRINT) . "\n";
    $logMessage .= "=============================================\n\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// Function to make GET requests (used for searching emails)
function makeGetRequest($url, $headers) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); 
    $response = curl_exec($ch);
    
    // Check for errors
    if (curl_errno($ch)) {
        return ['error' => 'Curl error: ' . curl_error($ch)];
    }
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'http_code' => $httpCode,
        'response' => json_decode($response, true)
    ];
}

// Function to make POST requests (used for releasing emails)
function makePostRequest($url, $headers, $data) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $response = curl_exec($ch);
    
    // Check for errors
    if (curl_errno($ch)) {
        return ['error' => 'Curl error: ' . curl_error($ch)];
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'http_code' => $httpCode,
        'response' => json_decode($response, true)
    ];
}

// Function to make DELETE requests (used for deleting emails)
function makeDeleteRequest($url, $headers, $data) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); // Attach JSON body to DELETE request
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $response = curl_exec($ch);
    
    // Check for errors
    if (curl_errno($ch)) {
        return ['error' => 'Curl error: ' . curl_error($ch)];
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'http_code' => $httpCode,
        'response' => json_decode($response, true)
    ];
}

// Helper function to format datetime
function formatDateTime($datetime) {
    $date = new DateTime($datetime);
    return $date->format('Y-m-d\TH:i:00.000\Z');
}

// Function to search for quarantined emails using GET request
function searchEmails($recipient, $sender, $startDate, $endDate) {
    global $headers;
    $url = "https://ciscogateway3.nayatel.com:6443/esa/api/v2.0/message-tracking/messages";
    $queryParams = "startDate=$startDate&endDate=$endDate&searchOption=messages&offset=0&limit=20&envelopeSenderfilterValue=$sender&envelopeSenderfilterOperator=contains&envelopeRecipientfilterValue=$recipient&envelopeRecipientfilterOperator=contains";
    $fullUrl = "$url?$queryParams";

    // Make the GET request
    $response = makeGetRequest($fullUrl, $headers);
    
    // Log the request and response
    logApiResponse($fullUrl, $headers, [], $response, 'Email Search');
    
    return $response;
}

// Function to search for PVO quarantined emails
function searchPVO($recipient, $sender, $startDate, $endDate) {
    global $headers;
    $url = "https://ciscogateway3.nayatel.com:6443/esa/api/v2.0/quarantine/messages";
    $queryParams = "startDate=$startDate&endDate=$endDate&limit=25&offset=0&orderBy=received&orderDir=desc&quarantineType=pvo&quarantines=Outbreak,Virus,File+Analysis,Unclassified,Policy&envelopeRecipientFilterBy=contains&envelopeRecipientFilterValue=$recipient&envelopeSenderFilterBy=contains&envelopeSenderFilterValue=$sender";
    $fullUrl = "$url?$queryParams";

    // Make the GET request
    $response = makeGetRequest($fullUrl, $headers);
    
    // Log the request and response
    logApiResponse($fullUrl, $headers, [], $response, 'PVO Search');
    
    return $response;
}

// Function to search for Spam quarantined emails
function searchSpam($recipient, $startDate, $endDate) {
    global $headers;
    $url = "https://ciscogateway3.nayatel.com:6443/esa/api/v2.0/quarantine/messages";
    $queryParams = "offset=0&quarantineType=spam&limit=20&startDate=$startDate&endDate=$endDate&envelopeRecipientFilterOperator=contains&envelopeRecipientFilterValue=$recipient";
    $fullUrl = "$url?$queryParams";

    // Make the GET request
    $response = makeGetRequest($fullUrl, $headers);
    
    // Log the request and response
    logApiResponse($fullUrl, $headers, [], $response, 'Spam Search');
    
    return $response;
}

// Function to release an email from quarantine using POST request
function releaseEmail($mid, $quarantineType) {
    global $headers;
    $url = "https://ciscogateway3.nayatel.com:6443/esa/api/v2.0/quarantine/messages";
    
    // Set quarantine name based on type
    $quarantineName = ($quarantineType == 'pvo') ? "Outbreak,Virus,Unclassified,Policy" : "Spam";

    // Prepare the data for the API request
    $data = [
        "action" => "release",
        "mids" => [(int)$mid],
        "quarantineType" => $quarantineType,
        "quarantineName" => $quarantineName
    ];

    // Make the POST request
    $response = makePostRequest($url, $headers, $data);
    
    // Log the API request and response
    logApiResponse($url, $headers, $data, $response, 'Release Email');
    
    return $response;
}

// Function to delete an email from quarantine using DELETE request
function deleteEmail($mid, $quarantineType) {
    global $headers;
    $url = "https://ciscogateway3.nayatel.com:6443/esa/api/v2.0/quarantine/messages";
    
    // Set quarantine name based on type
    $quarantineName = ($quarantineType == 'pvo') ? "Outbreak,Virus,Unclassified,Policy" : "Spam";

    // Prepare the data for the DELETE request
    $data = [
        "mids" => [(int)$mid],
        "quarantineName" => $quarantineName,
        "quarantineType" => $quarantineType
    ];

    // Make the DELETE request
    $response = makeDeleteRequest($url, $headers, $data);
    
    // Log the API request and response
    logApiResponse($url, $headers, $data, $response, 'Delete Email');
    
    return $response;
}

?>
