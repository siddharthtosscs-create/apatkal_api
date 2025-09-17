<?php
header("Content-Type: application/json");

// Database connection
$host = "localhost";
$dbname = "edueyeco_apatkal";
$username = "root";
$password = "";

$conn = new mysqli($host, $username, $password, $dbname);

// Check DB connection
if ($conn->connect_error) {
    die(json_encode([
        "success" => false,
        "message" => "Database connection failed: " . $conn->connect_error
    ]));
}

// Fetch accident reports
$sql = "SELECT id, fullname, phone, vehicle, location, latitude, longitude, description, created_at 
        FROM accidents 
        ORDER BY id DESC";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $accidents = [];

    while ($row = $result->fetch_assoc()) {
        $accidentId = $row['id'];

        // Fetch related photos
        $photoSql = "SELECT photo FROM accident_photos WHERE accident_id = " . intval($accidentId);
        $photoResult = $conn->query($photoSql);

        $photos = [];
        if ($photoResult && $photoResult->num_rows > 0) {
            while ($p = $photoResult->fetch_assoc()) {
                // build proper path if photos stored in uploads/ directory
                $photos[] = $p['photo'];
            }
        }

        $row['photos'] = $photos;
        $accidents[] = $row;
    }

    echo json_encode([
        "success" => true,
        "data" => $accidents
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "No accident reports found"
    ]);
}

$conn->close();
?>
