<?php
require 'vendor/autoload.php';

use Google\Cloud\Storage\StorageClient;

// Set up Google Cloud credentials from environment variable
putenv('GOOGLE_APPLICATION_CREDENTIALS=' . getenv('GOOGLE_APPLICATION_CREDENTIALS'));

// Google Cloud Storage configuration
$bucketName = getenv('GCS_BUCKET_NAME');  // Bucket name from environment variable
$bucketPath = 'uploads/';  // Directory inside the bucket to store files

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_FILES['imageFile']) && $_FILES['imageFile']['error'] == UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['imageFile']['tmp_name'];  // Temporary file path
        $fileName = $_FILES['imageFile']['name'];  // Original file name

        try {
            // Initialize Google Cloud Storage client
            $storage = new StorageClient();
            $bucket = $storage->bucket($bucketName);

            // Upload file to Google Cloud Storage
            $gcsObjectName = $bucketPath . $fileName;
            $bucket->upload(
                fopen($fileTmpPath, 'r'),
                [
                    'name' => $gcsObjectName
                ]
            );

            // Get the public URL of the uploaded file
            $fileUrl = sprintf('https://storage.googleapis.com/%s/%s', $bucketName, $gcsObjectName);

            // Send file URL to Gemini Vision API for processing
            $geminiResponse = sendToGeminiVision($fileUrl);

            // Return the result as JSON
            echo json_encode(['status' => 'success', 'text' => $geminiResponse]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'text' => 'Error uploading file: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'text' => 'File upload error']);
    }
}

// Function to send file to Gemini Vision API for processing
function sendToGeminiVision($fileUrl) {
    $geminiApiUrl = getenv('GEMINI_API_URL'); // API URL from environment variable

    $postData = json_encode([
        'fileUrl' => $fileUrl  // Send the GCS file URL to Gemini for processing
    ]);

    $ch = curl_init($geminiApiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . getenv('GEMINI_API_KEY') // API key from environment variable
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

    $response = curl_exec($ch);
    curl_close($ch);

    // Assuming the API returns the processed text result
    $responseData = json_decode($response, true);
    return $responseData['result'] ?? 'No result returned from Gemini Vision API';
}
