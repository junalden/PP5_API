<?php
// Function to convert a local file to Base64
function fileToGenerativePart($filePath, $mimeType) {
    if (file_exists($filePath)) {
        $data = file_get_contents($filePath);
        return [
            'inlineData' => [
                'data' => base64_encode($data),  // Convert to Base64
                'mimeType' => $mimeType
            ],
        ];
    } else {
        throw new Exception("File not found: " . $filePath);
    }
}

// Handle the file upload
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_FILES['imageFile']) && $_FILES['imageFile']['error'] == UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['imageFile']['tmp_name'];  // Temporary file path
        $fileName = $_FILES['imageFile']['name'];  // Original file name
        $mimeType = $_FILES['imageFile']['type']; // MIME type of the uploaded file

        try {
            // Prepare the part from the uploaded image
            $filePart = fileToGenerativePart($fileTmpPath, $mimeType);

            // Example: Preparing the data to send to the Google Generative AI API
            $apiKey = getenv('API_KEY');  // Access your API key from environment variables
            $genAIUrl = 'https://generative-ai.googleapis.com/v1/generate'; // Replace with the actual API endpoint

            $postData = [
                'parts' => [$filePart] // Combine the single part into an array
            ];

            // Initialize cURL session
            $ch = curl_init($genAIUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey // Use API key for authentication
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));

            // Execute cURL and fetch the response
            $response = curl_exec($ch);
            curl_close($ch);

            // Handle the response
            $responseData = json_decode($response, true);
            if (isset($responseData['result'])) {
                echo json_encode(['status' => 'success', 'result' => $responseData['result']]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No result returned from API.']);
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'File upload error']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>
