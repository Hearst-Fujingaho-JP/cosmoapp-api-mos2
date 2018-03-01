<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=utf-8");


$start = new Datetime();

//require_once("rover/RoverCurlClient.php");

//use  Service\Rover\RoverCurlClient;

// include('top5.php');
// $TYPE = "tag";
// $ID = "8f7286b6-4b22-41f7-a711-871e674e600d";

// $TYPE = "article";
$TYPE = "article";
// $TYPE = "collection";
//$ID = "16022087";
$ID = "6960";
// $ID = "496bbf30-5091-422f-a23e-ff49b9dcdbdc";
// $N=30;

include('getItem.php');
// include("collection.php");
// var_dump($new_item);

$end = new Datetime();

//echo $end->getTimestamp() - $start->getTimestamp();

echo $json;
?>
