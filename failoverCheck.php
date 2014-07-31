<?php
require __DIR__ . '/vendor/autoload.php';

// include a library

use FuseSource\Stomp\Stomp;

if (isset($_GET['servers'])) {
  $servers = explode(',', $_GET['servers']);
}
else {
  print "Please enter server(s) details as comma separated value. e.g: servers=tcp://amq1.server.com:61616,tcp://amq2.server.com:61616";
  exit(1);
}

if (isset($_GET['id'])) {
  $id = $_GET['id'];
}
else {
  print "Please enter a random correlation ID. e.g: id=ABC-53d7caacba921";
  exit(1);
}

// make a connection
$con = new Stomp("failover://(" . implode(',',  $servers) . ")?randomize=false");
$con->setReadTimeout(10);
// connect
$con->connect();

$correlation_id = $id . '-' . rand();
$request_queue = '/queue/com.sbe.timetable.request';
$response_queue = '/queue/com.sbe.timetable.response';
$selector = array('selector' => "JMSCorrelationID='$correlation_id'");

// Not used for this test.
$headers = array('ServiceName' => 'getTrainSchedule', 'Market' => 'UK', 'POS' => 'GBZXA', 'Channel' => 'WEB', 'correlation-id' => $correlation_id, 'reply-to' => $response_queue);
$date = date('d/m/Y', time() + (2* 24 * 60 *60));
$message = '<getTrainSchedule><TrainScheduleInput><commonService><correlationId>'. $correlation_id .'</correlationId></commonService><basicJourneyWish><itinerary><od><originCode>FRPAR</originCode><destinationCode>GBLON</destinationCode></od></itinerary><travelDate><date>' . $date . '</date></travelDate></basicJourneyWish></TrainScheduleInput></getTrainSchedule>';
// send a message to the queue
$con->send($request_queue, $message, $headers);
echo "Sent message with body\n";
// subscribe to the queue
$con->subscribe($response_queue, $selector);
$read_ts = microtime(TRUE);
// receive a message from the queue
$msg = $con->readFrame();
print "Time spent for readFrame:" . (microtime(TRUE) - $read_ts) . "<br />";
// do what you want with the message
if ( $msg != null) {
    echo "Received message with body\n";
    var_dump($msg);
    // mark the message as received in the queue
    $con->ack($msg);
} else {
    echo "Failed to receive a message\n";
}

// disconnect
$con->disconnect();
?>