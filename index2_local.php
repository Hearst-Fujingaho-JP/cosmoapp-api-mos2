<?php
header("Content-Type: application/json; charset=utf-8");

require_once("rover/RoverCurlClient.php");

use  Service\Rover\RoverCurlClient;

include('top5.php');

echo $json;

?>
