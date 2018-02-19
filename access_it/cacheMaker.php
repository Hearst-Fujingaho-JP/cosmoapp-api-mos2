<?php
require_once(__DIR__."/../rover/CurlClient.php");

use  Service\Rover\CurlClient;

$maker = new CacheMaker();
$start = date_format(new Datetime(), 'Y-m-d H:i:s');
echo "start : {$start}".PHP_EOL;
$maker->doWork();
$end = date_format(new Datetime(), 'Y-m-d H:i:s');
echo "finished : {$end}".PHP_EOL;;

class CacheMaker {
    private $host = "http://sp.cosmopolitan-jp.com";

    public function doWork() {
        $curlClient = new CurlClient($this->host, 20 , "" , "");
        
        $ret = $curlClient->simpleGet("/api/json/mos2/main.json", array());
        $json = $ret;
        
        $this->accessMainItems($json);
        
        echo "access finished.". PHP_EOL;
    }

    protected function accessMainItems($main_json) {
        foreach($main_json->navigation as $nav) {
            $type = $nav->type;
            $id = $nav->id;

            if ($type != "article" && $type != "gallery") {
                // first time : depth first to update all content cache
                $this->accessOneSet($type, $id, false, true);
                // second time : update cache for this set
                $this->accessOneSet($type, $id, true, false);
            } else {
                $this->accessOneContent($type, $id);
            }
        }
    }

    protected function accessOneSet($type, $id, $updateCache, $accessChildren) {
        $params = Array(
            "type" => $type,
            "id"=> $id,
            "NOTREADCACHE" => "Y",
            "NOTUPDATECACHE" => $updateCache ? "N" : "Y"
        );
        
        $curlClient = new CurlClient($this->host, 30 , "" , "");
        $ret_one = $curlClient->simpleGet("/api/json/mos2/", $params);
        if ($ret_one != null && isset($ret_one) && $ret_one->type == $type) {
            echo "access ok: {$type} : {$id} : updateCache={$updateCache} : accessChildren={$accessChildren}". PHP_EOL;
        } else {
            echo "access failed: {$type} : {$id} : updateCache={$updateCache} : accessChildren={$accessChildren}". PHP_EOL;
        }

        if ($accessChildren) {
            $this->accessContents($ret_one->items);
            //$this->accessOneContent()
            // foreach($ret_one->items as $item) {
            //     // $this->accessOneContent($item->type, $item->id);
            // }
        }
    }

    protected function accessContents($items) {
        $urlsWithOptions = Array();
        foreach($items as $item) {
            $type  = $item->type;
            $id = $item->id;
            $url = "/api/json/mos2/";
            $params = Array(
                "type" => $type,
                "id"=> $id,
                "NOTREADCACHE" => "N", // コンテンツのキャッシュうがあれば、使う
                "NOTUPDATECACHE" => "N" // キャッシュうを必ず作成する
            );
            $urlsWithOptions[] = Array(
                0 => $url,
                1 => $params
            );
        }
        $curlClient = new CurlClient($this->host, 50 , "" , "");
        $rets = $curlClient->multi_curl_execute($urlsWithOptions);
        foreach($rets as $ret) {
            if ($ret) {
                echo "   content access ok: {$ret->type} : {$ret->id}". PHP_EOL;
            }
        }        
    }

    protected function accessOneContent($type, $id) {
        // aritcle / gallery, キャッシュうを必ず作成する
        $params = Array(
            "type" => $type,
            "id"=> $id,
            "NOTREADCACHE" => "N", // コンテンツのキャッシュうがあれば、使う
            "NOTUPDATECACHE" => "N" // キャッシュうを必ず作成する
        );

        $curlClient = new CurlClient($this->host, 60 , "" , "");
        $ret_one = $curlClient->simpleGet("/api/json/mos2/", $params);
        if ($ret_one != null && isset($ret_one) && $ret_one->type == $type) {
            echo "   content access ok: {$type} : {$id}". PHP_EOL;
        } else {
            echo "   content access failed: {$type} : {$id}". PHP_EOL;
        }
    }
}
?>