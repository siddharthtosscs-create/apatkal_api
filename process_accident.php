<?php
header("Content-Type: application/json");

// Include unified database configuration
require_once 'database_config.php';

try {
    $conn = getMysqliDB();
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "DB connection failed: " . $e->getMessage()]);
    exit;
}

// Get form data safely
$fullname    = $conn->real_escape_string($_POST['fullname'] ?? '');
$phone       = $conn->real_escape_string($_POST['phone'] ?? '');
$vehicle     = $conn->real_escape_string($_POST['vehicle'] ?? '');
$date        = $_POST['date'] ?? '';
$location    = $conn->real_escape_string($_POST['location'] ?? '');
$latitude    = $_POST['latitude'] ?? null;
$longitude   = $_POST['longitude'] ?? null;
$description = $conn->real_escape_string($_POST['description'] ?? '');

// Format date
$formattedDate = null;
if (!empty($date)) {
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $formattedDate = $date;
    } else {
        $dateParts = explode('/', $date);
        if (count($dateParts) === 3) {
            $formattedDate = $dateParts[2] . '-' . $dateParts[1] . '-' . $dateParts[0];
        }
    }
}
if (!$formattedDate) {
    $formattedDate = date("Y-m-d"); // fallback today
}

// Build SQL
$sql = "INSERT INTO accidents (fullname, phone, vehicle, accident_date, location, latitude, longitude, description, photo, created_at, status)
        VALUES ('$fullname', '$phone', '$vehicle', '$formattedDate', '$location', 
        " . ($latitude !== null ? "'$latitude'" : "NULL") . ", 
        " . ($longitude !== null ? "'$longitude'" : "NULL") . ", 
        '$description', '', NOW(), 'pending')";

if ($conn->query($sql) === TRUE) {
    $accidentId = $conn->insert_id;

    // ✅ Upload photos into accident_photos
    if (!empty($_FILES['photos']['name'][0])) {
        $uploadDir = "uploads/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        foreach ($_FILES['photos']['tmp_name'] as $key => $tmpName) {
            if ($_FILES['photos']['error'][$key] === 0) {
                $fileName = time() . "_" . basename($_FILES['photos']['name'][$key]);
                $targetFile = $uploadDir . $fileName;

                if (move_uploaded_file($tmpName, $targetFile)) {
                    $conn->query("INSERT INTO accident_photos (accident_id, photo) VALUES ($accidentId, '$fileName')");
                }
            }
        }
    }

    echo json_encode(["success" => true, "accident_id" => $accidentId]);
} else {
    echo json_encode(["success" => false, "message" => "SQL Error: " . $conn->error]);
}

$conn->close();
?>
