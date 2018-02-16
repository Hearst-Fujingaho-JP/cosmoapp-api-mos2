<?php
header("Content-Type: application/json; charset=utf-8");

require_once("rover/RoverCurlClient.php");
require_once("itemTransformer.php");

use  Service\Rover\RoverCurlClient;


$client_subsection = new RoverCurlClient();
$ret_subsection = $client_subsection->getSection($ID);

$client = new RoverCurlClient();
$client->setPageSize(30);
$client->setParam("subsection.id", $ID);
$ret = $client->getContents();

$result = Array(
    "type" => $TYPE,
    "id" => $ID,
    "name" => $ret_subsection->data->title,
    "total" => $ret->meta->total_count,
    "author" => new stdClass(),
    "url" => "http://www.cosmopolitan.com/jp"
);

if (isset($ret->data)) {
    $new_items = Array();
    foreach($ret->data as $item) {
        $authors_new = Array();
        foreach($item->authors as $author) {
            $authors_ret = $client->getAuthor($author->id);
            $authors_new[] = $authors_ret->data;
        }
        $trans = new ItemTransformer($item, $authors_new);
        $new_item = $trans->go();
        $new_items[] = $new_item;
    }

    $result["items"] = $new_items;
}

$json = json_encode($result);

//$cache->save($json, $CACHE_ID);

//echo $json;

?>
