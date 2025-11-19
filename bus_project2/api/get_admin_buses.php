<?php
include 'db_config.php';

/**
 * Fetches all buses and their stops, structured like the original JS object.
 */
function getAllBuses($conn) {
    $buses = [];
    $sql = "SELECT bus_id, bus_name, source, destination FROM buses ORDER BY bus_id DESC";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while($bus_row = $result->fetch_assoc()) {
            $bus_id = $bus_row['bus_id'];
            $stops = [];
            
            // Get stops for this bus
            $stmt = $conn->prepare("SELECT stop_name, stop_time FROM stops WHERE bus_id = ? ORDER BY stop_time ASC");
            $stmt->bind_param("i", $bus_id);
            $stmt->execute();
            $stops_result = $stmt->get_result();

            while ($stop_row = $stops_result->fetch_assoc()) {
                $stops[] = [
                    'name' => $stop_row['stop_name'],
                    // Format time to ISO 8601 (like JS 'datetime-local')
                    'time' => date('Y-m-d\TH:i:s', strtotime($stop_row['stop_time'])) 
                ];
            }
            $stmt->close();

            // Add the complete bus object
            $buses[] = [
                'id' => (string)$bus_id, // Keep JS happy with string ID
                'name' => $bus_row['bus_name'],
                'source' => $bus_row['source'],
                'destination' => $bus_row['destination'],
                'stops' => $stops
            ];
        }
    }
    return $buses;
}

$allBuses = getAllBuses($conn);
echo json_encode($allBuses);

$conn->close();
?>