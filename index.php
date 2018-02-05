<?php
header("Content-Type: application/json; charset=utf-8");

$TYPE = $_GET{'type'};
$ID = $_GET{'id'};

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
    }


    $URL = "http://www.cosmopolitan-jp.com/api/json/all.xml?type={$TYPE}&id={$ID}{$NUM}{$RANKING}&dynamic";
    //$USERNAME = "stagingarea";
    //$PASSWORD = "hearst57";
 
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //curl_setopt($ch, CURLOPT_USERPWD, $USERNAME . ":" . $PASSWORD);
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    $json = curl_exec($ch);

    curl_close($ch);

    $cache->save($json, $CACHE_ID);

}

echo $json;

?>
