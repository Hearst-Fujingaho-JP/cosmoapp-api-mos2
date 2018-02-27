<?php
header("Content-Type: application/json; charset=utf-8");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("rover/RoverCurlClient.php");
require_once("itemTransformer.php");


//use  Service\Rover\RoverCurlClient;

//$client = new RoverCurlClient();


$ch = curl_init();
$url = "https://www.cosmopolitan.com/jp/entertainment/celebrity/gallery/g1666/disney-channel-star-black-history/";
$cxenseApiUrl = "http://api.cxense.com/public/widget/data?json={%22widgetId%22:%2211ae96e78c9ea57a25adeee1f91e318af9f53903%22,%22context%22:{%22url%22:%22" . $url . "%22}}";
curl_setopt($ch, CURLOPT_URL, $cxenseApiUrl);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
$result = curl_exec($ch);
$items = json_decode( $result, true );

var_dump($items);
