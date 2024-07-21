<?php

require_once 'helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['jsonfile'])) {
    $fileType = strtolower(pathinfo($_FILES['jsonfile']['name'], PATHINFO_EXTENSION));
    if ($fileType != "json") {
        echo json_encode(["error" => "Only JSON files are allowed."]);
        exit;
    }

    $jsonData = file_get_contents($_FILES['jsonfile']['tmp_name']);
    if ($jsonData === false) {
        echo json_encode(["error" => "Failed to read file."]);
        exit;
    }

    $data = json_decode($jsonData, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(["error" => "Invalid JSON."]);
        exit;
    }

    $jsonParser = new JsonParser();
    try {
        $jsonParser->parse($jsonData);
        $result = $jsonParser->sonuc;
        
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } catch (Exception $e) {
        echo json_encode(["error" => "Failed to parse JSON.", "message" => $e->getMessage()]);
    }
} else {
    echo json_encode(["error" => "No file uploaded or wrong request method."]);
}

?>
