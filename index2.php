<?php
header("Content-Type: application/json; charset=utf-8");

require_once("rover/RoverCurlClient.php");

use  Service\Rover\RoverCurlClient;

$TYPE = $_GET{'type'};
$ID = $_GET{'id'};
// $TYPE = "home";
// $ID = 0;

$lifetime = 3600;
if( $TYPE == 'article' || $TYPE == 'gallery' ){
	$lifetime = 604800;
}


require_once("../Cache_Lite-1.8.0/Cache/Lite.php");
$options = array(
        'cacheDir' => './cache/',
        'lifeTime' => $lifetime,
        'automaticSerialization' => 'true',
        'automaticCleaningFactor' => 200,   
        'hashedDirectoryLevel' => 1  
                  );
$cache = new Cache_Lite($options);


if( $TYPE == 'article' || $TYPE == 'gallery' ){
    include('getItem.php');
    exit;
}

$N = 0;
if(isset($_GET{'n'})){
    $NUM = "&n={$_GET{'n'}}";
    $N = $_GET{'n'};
}

$CACHE_ID = $TYPE.'-'.$ID.'-'.$N;

if($json = $cache->get($CACHE_ID)){

}else{

    $RANKING = '';
    if( $TYPE == 'ranking' ){
        include('ranking.php');
        $json = print_r($data);
    } else if ($TYPE == 'home') {
        include('home.php');
    } else if ($TYPE == 'top5') {
        include("top5.php");
    }

    //$cache->save($json, $CACHE_ID);

}

echo $json;

?>
