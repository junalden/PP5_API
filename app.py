import os
import base64
from flask import Flask, request, jsonify
import requests
from dotenv import load_dotenv

load_dotenv()

app = Flask(__name__)

API_KEY = os.getenv("API_KEY")
GEMINI_API_URL = "https://generative-ai.googleapis.com/v1/generate"

@app.route('/process-image', methods=['POST'])
def process_image():
    if 'image' not in request.files:
        return jsonify({"status": "error", "message": "No file uploaded."}), 400

    file = request.files['image']
    
    if file.filename == '':
        return jsonify({"status": "error", "message": "No file selected."}), 400

    try:
        # Save the uploaded file
        image_path = os.path.join('uploads', file.filename)
        file.save(image_path)

        # Prepare the request for Gemini API
        with open(image_path, "rb") as image_file:
            encoded_string = base64.b64encode(image_file.read()).decode('utf-8')

        # Make the API call to Gemini
        payload = {
            "prompt": "Describe how this product might be manufactured.",
            "parts": [
                {
                    "inlineData": {
                        "data": encoded_string,
                        "mimeType": file.content_type,
                    }
                }
            ],
            "model": "gemini-1.5-flash"
        }

        headers = {
            "Authorization": f"Bearer {API_KEY}",
            "Content-Type": "application/json"
        }

        response = requests.post(GEMINI_API_URL, json=payload, headers=headers)

        if response.status_code != 200:
            return jsonify({"status": "error", "message": "Failed to get response from Gemini API."}), 500

        result = response.json()
        return jsonify({
            "status": "success",
            "generatedText": result.get("response", {}).get("text", "No response text available.")
        })

    except Exception as e:
        return jsonify({"status": "error", "message": str(e)}), 500

if __name__ == "__main__":
    os.makedirs('uploads', exist_ok=True)  # Create uploads folder if it doesn't exist
    app.run(debug=True, host='0.0.0.0', port=5000)
