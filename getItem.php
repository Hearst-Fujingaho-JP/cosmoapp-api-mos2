<?php
header("Content-Type: application/json; charset=utf-8");

require_once("rover/RoverCurlClient.php");
require_once("itemTransformer.php");
require_once("itemDeepTransformer.php");

use  Service\Rover\RoverCurlClient;

if( $TYPE != 'article' && $TYPE != 'gallery' ){
    exit;
}

$client = new RoverCurlClient();
if (strlen($ID) > 4) {
    $client->setParam("display_id", $ID);
} else  {
    if ($TYPE == "article") {
        $client->setParam("legacy_id", $ID);
    } else {
        $client->setParam("legacy_id", "-".$ID);
    }
}
$client->setPageSize(1);
$ret = $client->getContents();

if (isset($ret->data) and count($ret->data) == 1) {
    $item = $ret->data[0];
    $author_count = count($item->authors);
    $authors = Array();
    for ($i = 0; $i < 3; $i++) {
        if ($i < $author_count ) {
            $authors_ret = $client->getAuthor($item->authors[$i]->id);
            $authors [] = $authors_ret->data;
        } else {
            $authors [] = null;
        }
    }    

    $trans = new ItemDeepTransformer($item, $authors);
    $new_item = $trans->go();
    $json = json_encode($new_item);
    // $trans = new ItemTransformer($item, $authors_new);
        // $new_item = $trans->go();
        // $new_items[] = $new_item;

    // $result["items"] = $new_items;
} else {
    exit;
}



?>
