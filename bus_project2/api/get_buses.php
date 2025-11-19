<?php
include 'db_config.php';

// Get search parameters from URL
$source = $_GET['source'] ?? '';
$destination = $_GET['destination'] ?? '';
$date = $_GET['date'] ?? ''; // YYYY-MM-DD

if (empty($date)) {
    echo json_encode([]);
    exit;
}

// Prepare search terms
$source_like = "%" . $source . "%";
$destination_like = "%" . $destination . "%";

// Find buses that match source, destination, AND have their first stop on the given date.
$sql = "
    SELECT b.bus_id, b.bus_name, b.source, b.destination
    FROM buses b
    WHERE
        b.source LIKE ?
        AND b.destination LIKE ?
        AND b.bus_id IN (
            SELECT s.bus_id
            FROM stops s
            WHERE s.bus_id = b.bus_id
              AND s.stop_name = b.source
              AND DATE(s.stop_time) = ?
        )
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $source_like, $destination_like, $date);
$stmt->execute();
$result = $stmt->get_result();

$buses = [];

if ($result->num_rows > 0) {
    while($bus_row = $result->fetch_assoc()) {
        $bus_id = $bus_row['bus_id'];
        $stops = [];
        
        // Get all stops for this matching bus
        $stmt_stops = $conn->prepare("SELECT stop_name, stop_time FROM stops WHERE bus_id = ? ORDER BY stop_time ASC");
        $stmt_stops->bind_param("i", $bus_id);
        $stmt_stops->execute();
        $stops_result = $stmt_stops->get_result();

        while ($stop_row = $stops_result->fetch_assoc()) {
            $stops[] = [
                'name' => $stop_row['stop_name'],
                'time' => date('Y-m-d\TH:i:s', strtotime($stop_row['stop_time']))
            ];
        }
        $stmt_stops->close();

        // Add the complete bus object
        $buses[] = [
            'id' => (string)$bus_id,
            'name' => $bus_row['bus_name'],
            'source' => $bus_row['source'],
            'destination' => $bus_row['destination'],
            'stops' => $stops
        ];
    }
}
$stmt->close();

echo json_encode($buses);
$conn->close();
?>