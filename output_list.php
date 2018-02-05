<?php
header("Content-Type: application/json; charset=utf-8");

require_once("rover/RoverCurlClient.php");
require_once("itemTransformer.php");

use  Service\Rover\RoverCurlClient;

$client = new RoverCurlClient();

$has_more = true;
$page = 1;
$page_size = 50;
$result = Array(
    "count" => 0
);
$data_arr = Array();
$count = 0;

while($has_more) {
    $ret = $client->getContentsByPage($page, $page_size);
    if (isset($ret->errors)) {
        $has_more = false;
        break;
    }

    if (isset($ret->data)) {
        foreach($ret->data as $item) {
            $count += 1;
            if (property_exists($item->metadata, "legacy_uid")) {
                $legacy_uid = $item->metadata->legacy_uid;
            } else {
                $legacy_uid = "N/A";
            }
        $out = Array(
                "id" => $item->id,
                "display_type" => $item->display_type->title,
                "legacy_uid" => $legacy_uid
            );
            $data_arr[] = $out;
        }

        echo $page." pages processed".PHP_EOL;
        $page = $page + 1;
        if ($ret->links->next == null) {
            $has_more = false;
        }
    }

    
}

$result["count"] = $count;
$result["data"] = $data_arr;


$json = json_encode($result);

file_put_contents("legacy_id_list.json", $json);

?>
