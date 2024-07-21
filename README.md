# Description
API that extracts credit cards in your Json files
An api that extracts the necessary information in the message box content in the Json file and presents it to you

# Usage (EXAMPLE PAGE)
This page was made with ChatGPT for speed

```
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JSON File Upload</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .container h1 {
            margin-bottom: 20px;
        }
        .container input[type="file"] {
            display: none;
        }
        .container label {
            background-color: #007bff;
            color: #fff;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        .container button {
            background-color: #28a745;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 20px;
        }
        .container button:hover,
        .container label:hover {
            opacity: 0.9;
        }
        .result {
            margin-top: 20px;
            text-align: left;
            max-height: 300px;
            overflow-y: auto;
        }
        .result pre {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>JSON File Upload</h1>
        <form id="uploadForm" method="post" enctype="multipart/form-data" action="api.php">
            <input type="file" name="jsonfile" id="jsonfile" accept=".json">
            <label for="jsonfile">Choose JSON File</label>
            <button type="submit">Upload</button>
        </form>
        <div class="result" id="result"></div>
    </div>

    <script>
        document.getElementById('uploadForm').addEventListener('submit', async function(event) {
            event.preventDefault();
            const fileInput = document.getElementById('jsonfile');
            if (!fileInput.files.length) {
                alert('Please select a file.');
                return;
            }

            const formData = new FormData();
            formData.append('jsonfile', fileInput.files[0]);

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                const resultContainer = document.getElementById('result');
                resultContainer.innerHTML = `<pre>${JSON.stringify(result, null, 2)}</pre>`;
            } catch (error) {
                console.error('Error:', error);
            }
        });
    </script>
</body>
</html>

```
