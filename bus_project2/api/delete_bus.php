<?php
include 'db_config.php';

// Get the raw POST data
$data = json_decode(file_get_contents('php://input'), true);
$busId = $data['bus_id'] ?? null;

if (!$busId) {
    echo json_encode(['success' => false, 'message' => 'No Bus ID provided.']);
    exit;
}

// The database is set to "ON DELETE CASCADE",
// so deleting the bus will automatically delete its stops.
$stmt = $conn->prepare("DELETE FROM buses WHERE bus_id = ?");
$stmt->bind_param("i", $busId);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Bus route deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Bus ID not found.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete bus route.']);
}

$stmt->close();
$conn->close();
?>