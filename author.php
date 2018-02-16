<?php
header("Content-Type: application/json; charset=utf-8");

require_once("rover/RoverCurlClient.php");
require_once("itemTransformer.php");

use  Service\Rover\RoverCurlClient;

$client_author = new RoverCurlClient();
$ret_author = $client_author->getAuthor($ID);

$client = new RoverCurlClient();
$client->setPageSize(30);
$client->setParam("authors.id", $ID);
$ret = $client->getContents();

$result = Array(
    "type" => $TYPE,
    "id" => $ID,
    "name" => $ret_author->data->title,
    "total" => $ret->meta->total_count,
    "author" => getAuthorInfo($ret_author->data),
    "url" => "http://www.cosmopolitan.com/jp/"
);

if (isset($ret->data)) {
    $new_items = Array();
    foreach($ret->data as $item) {
        $authors_new[] = $ret_author->data;
        $trans = new ItemTransformer($item, $authors_new);
        $new_item = $trans->go();
        $new_items[] = $new_item;
    }

    $result["items"] = $new_items;
}

$json = json_encode($result);

//$cache->save($json, $CACHE_ID);

//echo $json;

function getAuthorInfo($author_rover) {
    $ret = Array();
    $ret["id"] = $author_rover->id;
    $ret["name"] = $author_rover->profile->display_name;
    $ret["title"] = $author_rover->profile->job_title;
    $ret["profile"] = $author_rover->profile->bio;
    $ret["url"] = "http://www.cosmopolitan.com/jp/author/".$author_rover->profile->legacy_id;
    $ret["image"] = $author_rover->profile->photo;
    return $ret;
}
?>
