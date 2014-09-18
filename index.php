<?php
// make sure these settings are set in php.ini
// display_errors = On
//phpinfo() 

require 'vendor/autoload.php';
use OpenTok\OpenTok;
use OpenTok\Session;
use OpenTok\Role;

// mysql - replace user/pw and database name
// Set env vars in /Applications/MAMP/Library/bin/envvars if you are using MAMP
// MYSQL env: export CLEARDB_DATABASE_URL="mysql://root:root@localhost/tb_schedule
// MYSQL formate: username:pw@url/database
$mysql_url = parse_url(getenv("CLEARDB_DATABASE_URL"));
$dbname = substr($mysql_url['path'],1);
$con = mysqli_connect($mysql_url['host'], $mysql_url['user'], $mysql_url['pass']);

// Check connection
if (mysqli_connect_errno()) {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

// Create database - only do once if db does not exist
// Use our database and create table
$sql="CREATE DATABASE IF NOT EXISTS $dbname";
if (!mysqli_query($con,$sql)) {
  echo "Error creating database: " . mysqli_error($con);
}
mysqli_select_db($con, $dbname);
$sql="CREATE TABLE IF NOT EXISTS `Rooms` (
  `Name` CHAR(255),
  `Sessionid` CHAR(255),
  `User2` CHAR(255),
  `User1` CHAR(255))";
if (!mysqli_query($con,$sql)) {
  echo "Error creating table: " . mysqli_error($con);
}

function getBaseURL(){
  $pageURL = 'http';
  $pageURL .= "://".$_SERVER["SERVER_NAME"];
  if ($_SERVER["SERVER_PORT"] != "80") {
    $pageURL .= ":".$_SERVER["SERVER_PORT"];
  }
  return $pageURL;
}


// opentok
$apiKey = getenv('TB_KEY');
$apiSecret = getenv('TB_SECRET');
$opentok = new OpenTok($apiKey, $apiSecret);

// setup slim framework
$app = new \Slim\Slim(array(
  'templates.path' => './templates'
));

// variables
$date = new DateTime();

// routes
$app->get('/wat', function () use ($app, $date) {
  echo $date->getTimestamp();
});
$app->get('/', function () use ($app) {
  $app->render('createRoom.php');
});
$app->get('/:roomname', function ($roomname) use ($app, $con, $opentok, $apiKey, $date) {
  $roomAvailable = false;
  $userPos = 'User1';
  $sql = "SELECT * FROM Rooms WHERE Name='$roomname'";
  $result = mysqli_query($con, $sql);
  if (!$result) {
    die('Error: ' . mysqli_error($con));
  }
  $row = mysqli_fetch_assoc($result);

  if(!$row){
    $session = $opentok->createSession();
    $sessionId = $session->getSessionId();

    // escape variables for security
    $roomname = mysqli_real_escape_string($con, $roomname);
    $sessionId = mysqli_real_escape_string($con, $sessionId);
    $currentTime = $date->getTimestamp();

    $sql = "INSERT INTO Rooms (Name, Sessionid, User1) VALUES ('$roomname', '$sessionId', '$currentTime')";
    if (!mysqli_query($con,$sql)) {
      die('Error: ' . mysqli_error($con));
    }

    $row = array();
    $row['Name'] = $roomname;
    $row['Sessionid'] = $sessionId;
    $roomAvailable = true;
  }else{
    if(intval($date->getTimestamp() - intval($row['User1'])) > 180){
      // user1 has not been in the room for > 180 seconds
      $roomAvailable = true;
    }elseif(intval($date->getTimestamp() - intval($row['User2'])) > 180){
      $user = 'User2';
      $roomAvailable = true;
    }
  }
  if($roomAvailable){
    $row['apiKey'] = $apiKey;
    $row['roomname'] = $roomname;
    $row['token'] = $opentok->generateToken($row['Sessionid'], array(
      'data' => $userPos
    ));

    $app->render('chat.php', $row);
  }else{
    echo "Room is full at the moment. Please try again in a few minutes";
  }
});

$app->run();

?>
