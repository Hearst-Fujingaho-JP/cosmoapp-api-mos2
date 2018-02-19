<?php
header("Content-Type: application/json; charset=utf-8");

require_once("rover/RoverCurlClient.php");

use  Service\Rover\RoverCurlClient;

$TYPE = $_GET{'type'};
$ID = $_GET{'id'};
// $TYPE = "home";
// $ID = 0;

// 基本的に、別途のシェルでcacheを作成させます
$lifetime = 10800;
if( $TYPE == 'article' || $TYPE == 'gallery' ){
	$lifetime = 604800;
} else {
    if (isset($_GET{'NOTREADCACHE'}) 
        and (strtoupper($_GET{'NOTREADCACHE'}) == "Y" or strtoupper($_GET{'NOTREADCACHE'}) == "yes")) {
        $lifetime = 0;
    }
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

$N = 0;
if(isset($_GET{'n'})){
    $NUM = "&n={$_GET{'n'}}";
    $N = $_GET{'n'};
}

$CACHE_ID = $TYPE.'-'.$ID.'-'.$N;

if($json = $cache->get($CACHE_ID)){

} else {
    if( $TYPE == 'article' || $TYPE == 'gallery' ){
        include('getItem.php');
    } else  if( $TYPE == 'ranking' ){
        include('ranking.php');
    } else if ($TYPE == 'home') {
        include('home.php');
    } else if ($TYPE == 'top5') {
        include("top5.php");
    } else if ($TYPE == 'section') {
        include("section.php");
    } else if ($TYPE == 'collection') {
        include("collection.php"); 
    } else if ($TYPE == 'subsection') {
        include("subsection.php");
    } else if ($TYPE == 'author') {
        include("author.php");
    }

    if (isset($_GET{'NOTUPDATECACHE'})  
        and (strtoupper($_GET{'NOTUPDATECACHE'}) == "Y" or strtoupper($_GET{'NOTUPDATECACHE'}) == "yes")) {
            // do nothing 
    } else {
        $cache->save($json, $CACHE_ID);
    };
}

echo $json;

?>
