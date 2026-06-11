<?php
require_once 'auth.php';
require_once 'db.php';
requireLogin();

header('Content-Type: application/json');

$startDate = trim($_GET['start_date'] ?? '');
$startTime = trim($_GET['start_time'] ?? '10:00');
$bType     = trim($_GET['booking_type'] ?? '');

if (!$startDate || !$bType) {
    echo json_encode(['error' => 'Missing parameters', 'booked_rooms' => []]);
    exit;
}

$startDT = $startDate . ' ' . $startTime . ':00';
$endDT   = '';

// Calculate the end time based on duration
switch ($bType) {
    case 'slot':  $endDT = date('Y-m-d H:i:s', strtotime("$startDT +4 hours")); break;
    case 'week':  $endDT = date('Y-m-d H:i:s', strtotime("$startDT +7 days"));  break;
    case 'month': $endDT = date('Y-m-d H:i:s', strtotime("$startDT +1 month")); break;
    case 'year':  $endDT = date('Y-m-d H:i:s', strtotime("$startDT +1 year"));  break;
    default: 
        echo json_encode(['error' => 'Invalid type', 'booked_rooms' => []]);
        exit;
}

// Find all workspaces that have an active booking overlapping this time frame
$stmt = mysqli_prepare($conn, 
    "SELECT workspace_id FROM booking 
     WHERE status IN ('active' , 'Pending CheckIn')
     AND NOT (end_time <= ? OR start_time >= ?)"
);
mysqli_stmt_bind_param($stmt, 'ss', $startDT, $endDT);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$bookedRooms = [];
while ($row = mysqli_fetch_assoc($result)) {
    $bookedRooms[] = (int)$row['workspace_id'];
}

echo json_encode(['booked_rooms' => $bookedRooms]);