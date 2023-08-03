<?php

// Enable CORS (Cross-Origin Resource Sharing) to allow requests from different domains
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With"); 


date_default_timezone_set('Africa/Nairobi');
header('Content-Type: application/json');

$host = "localhost";
$username = "root";
$password = "";
$database = "penzi";

$conn = new PDO("mysql:host=$host;dbname=$database", $username, $password);

$conn->exec("ALTER TABLE messages AUTO_INCREMENT=1");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  
    $json = file_get_contents('php://input');

    $data = json_decode($json, true);

    if (isset($data['short_code'], $data['sender_mobile'], $data['message'])) {
        
        $shortCode = $data['short_code'];
        $senderMobile = $data['sender_mobile'];
        $message = $data['message'];
        $timeReceived = date('Y-m-d H:i:s');
        $notificationStatus = 'Pending';

        $stmt = $conn->prepare("INSERT INTO messages (short_code, sender_mobile, message, time_received, message_status) VALUES (?, ?, ?, ?, ?)");

        $stmt->bindParam(1, $shortCode);
        $stmt->bindParam(2, $senderMobile);
        $stmt->bindParam(3, $message);
        $stmt->bindParam(4, $timeReceived);
        $stmt->bindParam(5,$notificationStatus);


        if ($stmt->execute()) {

            $messageId = $conn->lastInsertId();

            $response = array('message' => 'Data inserted successfully');
            http_response_code(200);
            echo json_encode($response);
        } else {
            $response = array('message' => 'Error inserting data');
            http_response_code(500);
            echo json_encode($response);
        }
    } else { 
        $response = array('message' => 'Missing required fields');
        echo json_encode($response);
        http_response_code(400);
       
    } 
} 
?>