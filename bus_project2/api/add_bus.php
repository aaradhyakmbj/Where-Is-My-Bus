<?php
include 'db_config.php';

// Get the raw POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data received.']);
    exit;
}

$busName = $data['name'] ?? null;
$source = $data['source'] ?? null;
$destination = $data['destination'] ?? null;
$stops = $data['stops'] ?? [];

if (!$busName || !$source || !$destination || count($stops) < 2) {
    echo json_encode(['success' => false, 'message' => 'Invalid data provided.']);
    exit;
}

// Start a transaction
$conn->begin_transaction();

try {
    // 1. Insert into 'buses' table
    $stmt = $conn->prepare("INSERT INTO buses (bus_name, source, destination) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $busName, $source, $destination);
    $stmt->execute();

    // 2. Get the new bus_id
    $busId = $conn->insert_id;

    // 3. Insert into 'stops' table
    $stmt_stops = $conn->prepare("INSERT INTO stops (bus_id, stop_name, stop_time) VALUES (?, ?, ?)");
    
    foreach ($stops as $stop) {
        // Ensure time is in 'YYYY-MM-DD HH:MM:SS' format
        $stop_time = date('Y-m-d H:i:s', strtotime($stop['time']));
        $stmt_stops->bind_param("iss", $busId, $stop['name'], $stop_time);
        $stmt_stops->execute();
    }

    // If all successful, commit the transaction
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Bus route added successfully.', 'new_bus_id' => $busId]);

} catch (mysqli_sql_exception $exception) {
    // If any part fails, roll back
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Failed to add bus route: ' . $exception->getMessage()]);
}

$stmt->close();
$stmt_stops->close();
$conn->close();
?>