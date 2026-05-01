<?php  
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST request received.");

    if (isset($_FILES['file'])) {
        $fileError = $_FILES['file']['error'];
        error_log("File upload error code: $fileError");

        if ($fileError === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/';
            $fileName = basename($_FILES['file']['name']);
            $uploadFilePath = $uploadDir . $fileName;

            error_log("Uploaded file path: $uploadFilePath");

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
                error_log("Upload directory created: $uploadDir");
            }

            if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadFilePath)) {
                error_log("File moved to: $uploadFilePath");

                // Ensure Python path is correct
                $pythonPath = "python3"; // Adjust if needed for your system
                $command = escapeshellcmd("$pythonPath forecastmodel.py " . escapeshellarg($uploadFilePath) . " 2>&1");
                error_log("Executing command: $command");

                $output = shell_exec($command);
                error_log("Python script raw output: " . $output);

                // Assuming the Python script saves the image as 'graph/graph_image.png'
                $imagePath = 'graph/graph_image.png'; // Adjust path if needed

                // Send response with image path and output
                header('Content-Type: application/json'); // Send JSON response
                echo json_encode(["status" => "success", "output" => $output, "imagePath" => $imagePath]);
                exit;
            } else {
                $errorMessage = "Failed to move uploaded file. Please try again.";
                error_log($errorMessage);
            }
        } else {
            switch ($fileError) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $errorMessage = "The uploaded file exceeds the allowed size.";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errorMessage = "The file was only partially uploaded.";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $errorMessage = "No file was uploaded.";
                    break;
                default:
                    $errorMessage = "An unknown error occurred during the upload.";
            }
            error_log($errorMessage);
            header('Content-Type: application/json'); // Send JSON response
            echo json_encode(["status" => "error", "message" => $errorMessage]);
            exit;
        }
    } else {
        $errorMessage = "No file selected for upload.";
        error_log($errorMessage);
        header('Content-Type: application/json'); // Send JSON response
        echo json_encode(["status" => "error", "message" => $errorMessage]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DataDrip - File Upload</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
        }

        /* General Styles for the Upload Section */
.upload-section {
    max-width: 600px;
    margin: 50px auto;
    padding: 30px 20px;
    background-color: #fff;
    border: 2px solid #007bff;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

/* Title Styling */
.upload-section h4 {
    font-size: 26px;
    font-weight: bold;
    color: #343a40;
    margin-bottom: 20px;
}

/* File Input Styling */
.upload-section .form-control {
    border-radius: 8px;
    border: 1px solid #ccc;
    padding: 12px;
    font-size: 16px;
    width: 100%;
    margin-bottom: 20px;
}

/* Button Styling */
.upload-section button {
    background-color: #28a745;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 12px 30px;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.3s ease;
}

.upload-section button i {
    margin-right: 8px;
}

/* Button Hover Effects */
.upload-section button:hover {
    background-color: #218838;
    transform: scale(1.05);
}

/* Spinner (Loading Indicator) */
.spinner {
    display: none;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #007bff;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    animation: spin 1s linear infinite;
    margin-top: 20px;
}

/* Keyframes for Spinner */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
        .result-section {
            max-width: 100%;
            margin: 30px auto;
            padding: 20px;
            background-color: #e9ecef;
            border-radius: 8px;
            display: none;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }

        h1, h4, h5 {
            color: #343a40;
        }

        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
            background-color: #fff;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 14px;
            color: #333;
        }

        .graph-container {
            text-align: center;
            margin-top: 20px;
        }

        img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }

        button {
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 10px 20px;
            cursor: pointer;
        }

        button:hover {
            background-color: #0056b3;
        }

        #errorMessage {
            color: #dc3545;
            font-weight: bold;
            text-align: center;
            margin-top: 20px;
        }

        .spinner {
            display: none;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #007bff;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            text-align: center;
            background-color: #f8f9fa;
            padding: 10px 0;
            box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .forecast-section {
            margin-top: 50px;
            padding: 30px;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }

        .forecast-section h3 {
            color: #343a40;
        }

        .forecast-section p {
            font-size: 16px;
            color: #555;
        }
    </style>
</head>
<body>
<header class="bg-primary text-white p-3 text-center d-flex justify-content-between align-items-center">
    <h1>DataDrip</h1>
    <button id="logoutButton" class="btn btn-danger">Logout</button>
</header>

    <div class="container mt-5">
        <div class="upload-section">
            <h4>Upload Your Dataset</h4>
            <form id="uploadForm" enctype="multipart/form-data">
                <input type="file" id="file-upload" name="file" accept=".csv, .xlsx" class="form-control mb-3" required>
                <button type="submit" class="btn btn-success">Upload</button>
            </form>
            <div id="loadingSpinner" class="spinner"></div>
        </div>

        <div class="result-section" id="resultSection">
            <h5>Analysis Result:</h5>
            <pre id="resultOutput"></pre>
            <div class="graph-container" id="graphContainer">
                <img id="resultImage" src="Correlation Heat Map.png" alt="">
            </div>
        </div>

        <div class="forecast-section">
            <h3>Sales Forecasting</h3>
            <p>Sales forecasting helps businesses predict future sales based on historical data, trends, and patterns. By understanding these trends, businesses can make better decisions, allocate resources effectively, and maximize profits. Upload your dataset to analyze the trends and get insights into future sales.</p>
        </div>

        <div id="errorMessage"></div>
    </div>

    <footer class="footer bg-light text-center p-3">
        <p>© 2024 DataDrip | All Rights Reserved</p>
    </footer>

    <script>
        const uploadForm = document.getElementById('uploadForm');
        const resultSection = document.getElementById('resultSection');
        const resultOutput = document.getElementById('resultOutput');
        const resultImage = document.getElementById('resultImage');
        const graphContainer = document.getElementById('graphContainer');
        const loadingSpinner = document.getElementById('loadingSpinner');
        const errorMessageElement = document.getElementById('errorMessage');

        uploadForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Show the spinner and hide the result section initially
            loadingSpinner.style.display = 'block';
            resultSection.style.display = 'none';
            errorMessageElement.style.display = 'none';
            const logoutButton = document.getElementById('logoutButton');
                  logoutButton.addEventListener('click', () => {// Perform logout action (e.g., clear session, redirect to login)
                  window.location.href = '.html'; // Replace 'login.html' with the correct URL
               });

            const formData = new FormData(uploadForm);

            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.status === 'success') {
                    resultOutput.textContent = data.output;
                    resultSection.style.display = 'block';

                    // Show the graph if available
                    if (data.imagePath) {
                        resultImage.src = data.imagePath;
                        graphContainer.style.display = 'block';
                    }
                } else {
                    resultOutput.textContent = `Error: ${data.message}`;
                    errorMessageElement.style.display = 'block';
                    resultSection.style.display = 'block';
                }
            } catch (error) {
                resultOutput.textContent = 'An error occurred while uploading the file.';
                errorMessageElement.style.display = 'block';
                resultSection.style.display = 'block';
            } finally {
                // Hide the loading spinner
                loadingSpinner.style.display = 'none';
            }
        });
    </script>
</body>
</html>
