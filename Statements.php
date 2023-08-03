 <?php

// Enable CORS (Cross-Origin Resource Sharing) to allow requests from different domains
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With"); 

date_default_timezone_set('Africa/Nairobi');
header('Content-Type: text/plain');

$host = "localhost";
$username = "root";
$password = "";
$database = "penzi";

$conn = new PDO("mysql:host=$host;dbname=$database", $username, $password);

$fetchStmt = $conn->prepare("SELECT * FROM messages WHERE message_status = 'pending'");
$fetchStmt->execute();
$messages = $fetchStmt->fetchAll(PDO::FETCH_ASSOC);
session_start();

$countRecords = 0;

if (count($messages) > 0) {
    foreach ($messages as $message) {
        $messageId = $message['message_id'];
        $shortCode = $message['short_code'];
        $senderMobile = $message['sender_mobile'];
        $messageText = $message['message'];
        $messageTime = $message['time_received'];
        $messageStatus = $message['message_status'];

        if ($messageStatus == 'Pending') {
            if ($messageText == 'Penzi') {
                $welcomeMessage = 'Welcome to our dating service with 6000 potential dating partners. To register, SMS Start#name#age#gender#county#town to 22141.';
                $timeSent = date('Y-m-d H:i:s');

                $selectSenderMobileStmt = $conn->prepare("SELECT sender_mobile FROM messages WHERE message_id = ?");
                $selectSenderMobileStmt->execute([$messageId]);
                $senderMobileRow = $selectSenderMobileStmt->fetch(PDO::FETCH_ASSOC);
                $senderMobile = $senderMobileRow['sender_mobile'];
            
                $insertStmt = $conn->prepare("INSERT INTO outgoing_messages (message, received_at, mobile_sent) VALUES (?, ?, ?)");
                $insertStmt->execute([$welcomeMessage, $timeSent, $senderMobile]);
            
                $updateStmt = $conn->prepare("UPDATE messages SET message_status = 'Processed' WHERE message_id = ?");
                $updateStmt->execute([$messageId]);

                $countRecords++;

                // Set the response headers
                

                // Send the response message
                echo $welcomeMessage;
            } elseif (strpos($messageText, 'Start#') === 0) {
                    $userInfo = extractUserInfo($messageText);
                    if ($userInfo !== null) {
                        $name = $userInfo['name'];
                        $age = $userInfo['age'];
                        $gender = $userInfo['gender'];
                        $county = $userInfo['county'];
                        $town = $userInfo['town'];

                        $insertUserStmt = $conn->prepare("INSERT INTO users (user_name, age, gender, county, town, phone) VALUES (?, ?, ?, ?, ?, ?)");
                        $insertUserStmt->execute([$name, $age, $gender, $county, $town, $senderMobile]);

                        $profileCreatedMessage = 'Your profile has been created successfully. SMS Details#levelOfEducation#Profession#MaritalStatus#religion#ethnicity to 22141.';
                        $timeSent = date('Y-m-d H:i:s');

                        $insertOutgoingStmt = $conn->prepare("INSERT INTO outgoing_messages (message, received_at, mobile_sent) VALUES (?, ?, ?)");
                        $insertOutgoingStmt->execute([$profileCreatedMessage, $timeSent, $senderMobile]);

                        $updateStmt = $conn->prepare("UPDATE messages SET message_status = 'Processed' WHERE message_id = ?");
                        $updateStmt->execute([$messageId]);

                        $countRecords++;
                        echo $profileCreatedMessage;
                   }

            }elseif (strpos($messageText, 'Details#') === 0){
                    $userInfo = extractUserInfo($messageText);
                    if($userInfo !== null){
                        $levelOfEducation = $userInfo['levelOfEducation'];
                        $profession = $userInfo['profession'];
                        $maritalStatus = $userInfo['maritalStatus'];
                        $religion = $userInfo['religion'];
                        $ethnicity = $userInfo['ethnicity'];

                        $insertUserStmt = $conn->prepare("UPDATE users SET Level_of_Education = ?, Profession = ?, Marital_Status = ?, Religion = ?, Ethnicity = ? WHERE phone = ?");
                        $insertUserStmt->execute([$levelOfEducation, $profession, $maritalStatus, $religion, $ethnicity, $senderMobile]);

                        $lastReisterMessage = 'This is the last stage of registration. SMS a brief description of yourself to 22141 starting with the word MYSELF.';

                        $timeSent = date('Y-m-d H:i:s');
                        $insertOutgoingStmt = $conn->prepare("INSERT INTO outgoing_messages(message, received_at, mobile_sent) VALUES (?, ?, ?)");
                        $insertOutgoingStmt->execute([$lastReisterMessage, $timeSent, $senderMobile]);

                        $updateStmt = $conn->prepare("UPDATE messages SET message_status = 'Processed' WHERE message_id = ?");
                        $updateStmt->execute([$messageId]);

                        $countRecords++;
                        echo $lastReisterMessage;
                    }
            } elseif (strpos($messageText, 'Start#') === 0 && $elapsedTime >= 60){
                $checkDetailsStmt = $conn->prepare("SELECT * FROM messages WHERE sender_mobile = ? AND message_text LIKE 'details#%'");
                $checkDetailsStmt->execute([$senderMobile]);
                $detailsMessage = $checkDetailsStmt->fetch();
            
                if (!$detailsMessage) {
                    $reminderMessage = "You were registered for dating with your initial details. Please send the 'details' message to complete your registration.";
                    $timeSent = date('Y-m-d H:i:s');
            
                    $insertOutgoingStmt = $conn->prepare("INSERT INTO outgoing_messages (message, received_at, mobile_sent) VALUES (?, ?, ?)");
                    $insertOutgoingStmt->execute([$reminderMessage, $timeSent, $senderMobile]);
            
                    $updateStmt = $conn->prepare("UPDATE messages SET message_status = 'Processed' WHERE message_id = ?");
                    $updateStmt->execute([$messageId]);
            
                    $countRecords++;
                    echo $reminderMessage;
                }
            }elseif(strpos($messageText, 'MYSELF') === 0){
                $description = substr($messageText,7);

                $updateUserStmt = $conn->prepare("UPDATE users SET description = ? WHERE phone = ?");
                $updateUserStmt->execute([$description, $senderMobile]);
               
                $selectSenderMobileStmt = $conn->prepare("SELECT sender_mobile FROM messages WHERE message_id = ?");
                $selectSenderMobileStmt->execute([$messageId]);
                $senderMobileRow = $selectSenderMobileStmt->fetch(PDO::FETCH_ASSOC);
                $senderMobile = $senderMobileRow['sender_mobile'];

                $descriptionMessage = 'You are now registered for dating. To search for a MPENZI, SMS Match#age#town to 22141 and meet the person of your dreams.';
                $timeSent = date('Y-m-d H:i:s');

                $insertOutgoingStmt = $conn->prepare("INSERT INTO outgoing_messages (message, received_at, mobile_sent) VALUES (?, ?, ?)");
                $insertOutgoingStmt->execute([$descriptionMessage, $timeSent, $senderMobile]);

                $updateStmt = $conn->prepare("UPDATE messages SET message_status = 'Processed' WHERE message_id = ?");
                $updateStmt->execute([$messageId]);

                $countRecords++;
                echo $descriptionMessage;
            }elseif(strpos($messageText, 'Match#') === 0){
                    $userInfo = extractUserInfo($messageText);
                    if ($userInfo !== null) {
                        $ageRange = explode('-', $userInfo['age']);
                        $minAge = intval($ageRange[0]);
                        $maxAge = intval($ageRange[1]);
                        $town = $userInfo['town'];

                        $selectUserGenderStmt = $conn->prepare("SELECT gender FROM users WHERE phone = ?");
                        $selectUserGenderStmt->execute([$senderMobile]);
                        $userGender = $selectUserGenderStmt->fetchColumn();

                        $genderFilter = ($userGender === 'Female') ? 'Male' : 'Female';

                        $selectMatchingUsersStmt = $conn->prepare("SELECT * FROM users WHERE town = ? AND (age BETWEEN ? AND ?) AND phone != ? AND gender = ?");
                        $selectMatchingUsersStmt->execute([$town, $minAge, $maxAge, $senderMobile, $genderFilter]);
                        $matchingUsers = $selectMatchingUsersStmt->fetchAll(PDO::FETCH_ASSOC);
                        $totalMatchingUsers = count($matchingUsers);

                        if ($totalMatchingUsers > 0) {
                            $responseMessageFound = "We have " . $totalMatchingUsers . " records that match your choice! We will send you details of three of them shortly. To get more details about an interested candidate, SMS their number to 22141.\n";

                            $responseMessageDetails = "";
                            $responseMessageRemaining = "";

                            $displayUsers = array_slice($matchingUsers, 0, 3);
                            foreach ($displayUsers as $index => $result) {
                                $responseMessageDetails .= $result['user_name'] . " aged " . $result['age'] . ", " . $result['phone'] . ".\n";
                            }

                            $remainingUsers = max($totalMatchingUsers - 3, 0);
                            if ($remainingUsers > 0) {
                                $responseMessageRemaining = "Send NEXT to 22141 to see the remaining " . $remainingUsers . " entries.\n";
                            }

                            $timeSent = date('Y-m-d H:i:s');
                            $insertOutgoingStmt = $conn->prepare("INSERT INTO outgoing_messages(message, received_at, mobile_sent) VALUES (?, ?, ?)");
                            $insertOutgoingStmt->execute([$responseMessageFound, $timeSent, $senderMobile]);

                            $responseMessage = $responseMessageDetails . $responseMessageRemaining;
                            $insertOutgoingStmt->execute([$responseMessage, $timeSent, $senderMobile]);

                            $updateStmt = $conn->prepare("UPDATE messages SET message_status = 'Processed' WHERE message_id = ?");
                            $updateStmt->execute([$messageId]);

                            $countRecords = 1;
                            $_SESSION['countRecords'] = $countRecords;
                            $_SESSION['matchingUsers'] = $matchingUsers;
                            $_SESSION['town'] = $town;
                            $_SESSION['minAge'] = $minAge;
                            $_SESSION['maxAge'] = $maxAge;
                            $_SESSION['genderFilter'] = $genderFilter;
                            echo $responseMessageFound;
                            echo $responseMessage;
                        } else {
                            $errorMessage = 'No matching records found.';
                            $timeSent = date('Y-m-d H:i:s');

                            $insertOutgoingStmt = $conn->prepare("INSERT INTO outgoing_messages(message, received_at, mobile_sent) VALUES (?, ?, ?)");
                            $insertOutgoingStmt->execute([$errorMessage, $timeSent, $senderMobile]);

                            $updateStmt = $conn->prepare("UPDATE messages SET message_status = 'Processed' WHERE message_id = ?");
                            $updateStmt->execute([$messageId]);

                            $countRecords = 1;
                            $_SESSION['countRecords'] = $countRecords;
                            $_SESSION['matchingUsers'] = $matchingUsers;
                            $_SESSION['town'] = $town;
                            $_SESSION['minAge'] = $minAge;
                            $_SESSION['maxAge'] = $maxAge;
                            $_SESSION['genderFilter'] = $genderFilter;
                            echo $errorMessage;
                        }
                    }
            } elseif ($messageText == 'NEXT') {    
                if (isset($_SESSION['matchingUsers']) && isset($_SESSION['town']) && isset($_SESSION['minAge']) && isset($_SESSION['maxAge']) && isset($_SESSION['genderFilter'])) {
                    $matchingUsers = $_SESSION['matchingUsers'];
                    $town = $_SESSION['town'];
                    $minAge = $_SESSION['minAge'];
                    $maxAge = $_SESSION['maxAge'];
                    $genderFilter = $_SESSION['genderFilter'];
            
                    $totalMatchingUsers = count($matchingUsers);
            
                    if ($totalMatchingUsers > 0) {
                        $startIndex = $_SESSION['countRecords'] * 3;
                        $remainingUsers = max($totalMatchingUsers - $startIndex - 3, 0);
            
                        if ($remainingUsers > 0) {
                            $responseMessageRemaining = "Send NEXT to see the remaining " . $remainingUsers . " entries.\n";
                        } else {
                            $responseMessageRemaining = "No more users match your description.Send Match to get matched.";
                        }
            
                        if ($startIndex < $totalMatchingUsers) {
                            $displayUsers = array_slice($matchingUsers, $startIndex, 3);
                            $responseMessageDetails = "";
                            foreach ($displayUsers as $index => $result) {
                                $responseMessageDetails .= $result['user_name'] . " aged " . $result['age'] . ", " . $result['phone'] . ".\n";
                            }
            
                            $timeSent = date('Y-m-d H:i:s');
                            $insertOutgoingStmt = $conn->prepare("INSERT INTO outgoing_messages(message, received_at, mobile_sent) VALUES (?, ?, ?)");
                            $responseMessage = $responseMessageDetails . $responseMessageRemaining;
                            $insertOutgoingStmt->execute([$responseMessage, $timeSent, $senderMobile]);
            
                            $updateStmt = $conn->prepare("UPDATE messages SET message_status = 'Processed' WHERE message_id = ?");
                            $updateStmt->execute([$messageId]);
            
                            $countRecords = 1;
                            $_SESSION['countRecords'] = $countRecords;
                            unset($_SESSION['matchingUsers']);
                            unset($_SESSION['town']);
                            unset($_SESSION['minAge']);
                            unset($_SESSION['maxAge']);
                            unset($_SESSION['genderFilter']);
                            echo $responseMessage;
                        } else {
                            $errorMessage = 'No more records to display.';
                            $timeSent = date('Y-m-d H:i:s');
            
                            $insertOutgoingStmt = $conn->prepare("INSERT INTO outgoing_messages(message, received_at, mobile_sent) VALUES (?, ?, ?)");
                            $insertOutgoingStmt->execute([$errorMessage, $timeSent, $senderMobile]);
            
                            $updateStmt = $conn->prepare("UPDATE messages SET message_status = 'Processed' WHERE message_id = ?");
                            $updateStmt->execute([$messageId]);
            
                            $countRecords = 1;
                            $_SESSION['countRecords'] = $countRecords;
                            unset($_SESSION['matchingUsers']);
                            unset($_SESSION['town']);
                            unset($_SESSION['minAge']);
                            unset($_SESSION['maxAge']);
                            unset($_SESSION['genderFilter']);
                            echo $errorMessage;
                        }
                    }
                } else {
                    $errorMessage = 'You have not sent a Match request. Please send Match first to find matching users.';
                    $timeSent = date('Y-m-d H:i:s');
        
                    $insertOutgoingStmt = $conn->prepare("INSERT INTO outgoing_messages(message, received_at, mobile_sent) VALUES (?, ?, ?)");
                    $insertOutgoingStmt->execute([$errorMessage, $timeSent, $senderMobile]);
        
                    $updateStmt = $conn->prepare("UPDATE messages SET message_status = 'Processed' WHERE message_id = ?");
                    $updateStmt->execute([$messageId]);
        
                    $countRecords = 1;
                    $_SESSION['countRecords'] = $countRecords;
                    unset($_SESSION['matchingUsers']);
                    unset($_SESSION['town']);
                    unset($_SESSION['minAge']);
                    unset($_SESSION['maxAge']);
                    unset($_SESSION['genderFilter']);
                    echo $errorMessage;
                } 
            }elseif (is_numeric($messageText)){
                $selectUserStmt = $conn->prepare("SELECT * FROM users WHERE phone = ?");
                $selectUserStmt->execute([$messageText]);
                $requestedUser = $selectUserStmt->fetch(PDO::FETCH_ASSOC);

                if($requestedUser){
                    $userDetails = $requestedUser['user_name'] . " aged " . $requestedUser['age'] . ", " . $requestedUser['county'] . " County, " . $requestedUser['town'] . " town, " . $requestedUser['Level_of_Education'] . ", " . $requestedUser['Profession'] . ", " . $requestedUser['Marital_Status'] . ", " . $requestedUser['Religion'] . ", " . $requestedUser['Ethnicity'] . ".";
                    $moreDetailsMessage = "Send DESCRIBE " . $requestedUser['phone'] . " to get more details about " . $requestedUser['user_name'] . ".";

                    $messageDetails = $userDetails . "\n" . $moreDetailsMessage;

                    $selectRequesterStmt = $conn->prepare("SELECT * FROM users WHERE phone = ?");
                    $selectRequesterStmt->execute([$senderMobile]);
                    $requester = $selectRequesterStmt->fetch(PDO::FETCH_ASSOC);

                    if($requester){
                        $requesterDetails = "Hi " . $requestedUser['user_name'] . ", " . $requester['user_name'] . " requested your details, Aged " . $requester['age'] . " based in " . $requester['county'] . ".";
                        $moreDetails = "Do you want to know more about " . $requester['user_name'] . "?  Send YES to 22141.";

                        $senderDetails = $requesterDetails . "\n" . $moreDetails;
                    }

                    $timeSent = date('Y-m-d H:i:s');
                    $insertOutgoingStmt = $conn->prepare("INSERT INTO outgoing_messages(message, received_at, mobile_sent) VALUES (?, ?, ?)");
                    $insertOutgoingStmt->execute([$messageDetails, $timeSent, $senderMobile]);

                    $timeSent = date('Y-m-d H:i:s');
                    $insertOutgoingStmt = $conn->prepare("INSERT INTO outgoing_messages(message, received_at, mobile_sent) VALUES (?, ?, ?)");
                    $insertOutgoingStmt->execute([$senderDetails, $timeSent, $requestedUser['phone']]);

                    $updateStmt = $conn->prepare("UPDATE messages SET message_status = 'Processed' WHERE message_id = ?");
                    $updateStmt->execute([$messageId]);

                    $countRecords++;
                    echo $senderDetails;
                    echo $messageDetails;
                }else{
                   $errorMessage = 'User not found.';
                   $timeSent = date('Y-m-d H:i:s');
                   
                   $insertOutgoingStmt = $conn->prepare("INSERT INTO outgoing_messages(message, received_at, mobile_sent) VALUES (?, ?, ?)");
                   $insertOutgoingStmt->execute([$errorMessage, $timeSent, $senderMobile]);

                   $updateStmt = $conn->prepare("UPDATE messages SET message_status = 'Processed' WHERE message_id = ?");
                   $updateStmt->execute([$messageId]);

                   $countRecords++;
                   echo $errorMessage;
                }
            }elseif ($messageText == 'YES'){
                $checkPhoneStmt = $conn->prepare(" SELECT DISTINCT m1.sender_mobile
                FROM messages m1
                JOIN messages m2 ON m1.message = m2.sender_mobile
                WHERE m1.message REGEXP '^[0-9]+$' AND m2.message = 'YES'
                ORDER BY m1.time_received DESC
                LIMIT 1");
                $checkPhoneStmt->execute();
                $matchingValue = $checkPhoneStmt->fetchAll(PDO::FETCH_ASSOC);

                $checkYesStmt = $conn->prepare("SELECT sender_mobile FROM messages WHERE message = 'YES' ORDER BY time_received DESC LIMIT 1");
                $checkYesStmt->execute();
                $senderMobile = $checkYesStmt->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($matchingValue)) {
                    $selectNumberStmt = $conn->prepare("SELECT * FROM users WHERE phone = ?");
                    $selectNumberStmt->execute([$matchingValue[0]['sender_mobile']]);
                    $userDetails = $selectNumberStmt->fetch(PDO::FETCH_ASSOC);

                    $timeSent = date('Y-m-d H:i:s');
                    $matchingValueDetails = $userDetails['user_name'] . " aged " . $userDetails['age'] . ", " . $userDetails['county'] . " County, " . $userDetails['town'] . " town, " . $userDetails['Level_of_Education'] . ", " . $userDetails['Profession'] . ", " . $userDetails['Marital_Status'] . ", " . $userDetails['Religion'] . ", " . $userDetails['Ethnicity'] . ".";
                    $descriptionMessage = "Send DESCRIBE " . $userDetails['phone'] . " to get more details about " . $userDetails['user_name'] . ".";

                    $messageDetails = $matchingValueDetails . "\n" . $descriptionMessage; 

                    $insertOutgoingStmt = $conn->prepare("INSERT INTO outgoing_messages(message, received_at, mobile_sent) VALUES (?, ?, ?)");
                    $insertOutgoingStmt->execute([$messageDetails, $timeSent, $senderMobile[0]['sender_mobile']]);

                    $updateStmt = $conn->prepare("UPDATE messages SET message_status = 'Processed' WHERE message = ?");
                    $updateStmt->execute([$messageText]);
                    $countRecords++;
                    echo $messageDetails;
                }

            }elseif(strpos(strtoupper($messageText), "DESCRIBE") === 0){
                $parts = explode(" ", $messageText);
                $phone = trim($parts[1]);

                $selectUserStmt = $conn->prepare("SELECT * FROM users WHERE phone = ?");
                $selectUserStmt->execute([$phone]);
                $user = $selectUserStmt->fetch(PDO::FETCH_ASSOC);

                if ($user){
                    $userDescription = $user['Description'];
                    if ($userDescription){
                        $descriptionMessage = $user['user_name'] . " describes themself as " . $userDescription. ".";
                    }else{
                        $descriptionMessage = "No description available for the user. ";
                    }
                }
                $timeSent = date('Y-m-d H:i:s');
                $insertOutgoingStmt = $conn->prepare("INSERT INTO outgoing_messages (message, received_at, mobile_sent) VALUES (?, ?, ?)");
                $insertOutgoingStmt->execute([$descriptionMessage, $timeSent, $senderMobile]);

                $updateStmt = $conn->prepare("UPDATE messages SET message_status = 'Processed' WHERE message_id = ?");
                $updateStmt->execute([$messageId]);

                $countRecords++;
                echo $descriptionMessage;

            }else{
                $errorMessage = 'Wrong input. Please provide correct format.';
                $timeSent = date('Y-m-d H:i:s');

                $insertOutgoingStmt = $conn->prepare("INSERT INTO outgoing_messages (message, received_at, mobile_sent) VALUES (?, ?, ?)");
                $insertOutgoingStmt->execute([$errorMessage, $timeSent, $senderMobile]);

                $updateStmt = $conn->prepare("UPDATE messages SET message_status = 'Processed' WHERE message_id = ?");
                $updateStmt->execute([$messageId]);

                $countRecords++;

                echo $errorMessage;
            }
        }
    }
   echo "Processing completed. $countRecords records processed.";
} else {
    echo "No pending messages found.";
}

function extractUserInfo($message) {
    $userInfo = explode('#', $message);
    if (count($userInfo) === 6 && $userInfo[0] === 'Start') {
        return [
            'name' => $userInfo[1],
            'age' => $userInfo[2],
            'gender' => $userInfo[3],
            'county' => $userInfo[4],
            'town' => $userInfo[5]
        ];
    }elseif(count($userInfo) == 6 && $userInfo[0] == 'Details'){
        return[
            'levelOfEducation' => $userInfo[1],
            'profession' => $userInfo[2],
            'maritalStatus' => $userInfo[3],
            'religion' => $userInfo[4],
            'ethnicity' => $userInfo[5]
        ];
    }elseif(count($userInfo) == 3 && $userInfo[0] == 'Match'){
        return[
            'age' => $userInfo[1],
            'town' => $userInfo[2]
        ];
    } 
    return null;
}

?>