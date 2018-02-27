<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__."/../rover/CurlClient.php");

use  Service\Rover\CurlClient;

$maker = new CacheMaker();
$start = date_format(new Datetime(), 'Y-m-d H:i:s');
echo "start : {$start}".PHP_EOL;
$maker->doWork();
$end = date_format(new Datetime(), 'Y-m-d H:i:s');
echo "finished : {$end}".PHP_EOL;;

class CacheMaker {
    private $host = "https://sp.cosmopolitan-jp.com";
    private $base_url = "/api/json/prod/";

    public function doWork() {
        $curlClient = new CurlClient($this->host, 30 , "" , "");
        
        $ret = $curlClient->simpleGet($this->base_url."main.json", array());
        $json = $ret;
        
        $this->accessMainItems($json);
        
        echo "access finished.". PHP_EOL;
    }

    protected function accessMainItems($main_json) {
        foreach($main_json->navigation as $nav) {
            $type = $nav->type;
            $id = $nav->id;

            if ($type != "article" && $type != "gallery") {
                $n = 30;
                if ($type == "ranking") {
                    $n = 10;
                }
                // first time : depth first to update all content cache
                $this->accessOneSet($type, $id, $n, true, true);
                // second time : update cache for this set
                // $this->accessOneSet($type, $id, $n, true, false);
            } else {
                $this->accessOneContent($type, $id);
            }
        }
    }

    protected function accessOneSet($type, $id, $n, $updateCache, $accessChildren) {
        $params = Array(
            "type" => $type,
            "id"=> $id,
            "n" => $n,
            "NOTREADCACHE" => "Y",
            "NOTUPDATECACHE" => $updateCache ? "N" : "Y"
        );
        
        $curlClient = new CurlClient($this->host, 30 , "" , "");
        $ret_one = $curlClient->simpleGet($this->base_url, $params);
        if ($ret_one != null && isset($ret_one) && $ret_one->type == $type) {
            echo "access ok: {$type} : {$id} : updateCache={$updateCache} : accessChildren={$accessChildren}". PHP_EOL;
            if ($accessChildren) {
                $this->accessContents($ret_one->items);
            }    
        } else {
            echo "access failed: {$type} : {$id} : updateCache={$updateCache} : accessChildren={$accessChildren}". PHP_EOL;
        }

    }

    protected function accessContents($items, $stopOnRelated=false) {
        if (!isset($items) || !is_array($items)) {
            print_r($items);
            return;
        }
        $urlsWithOptions = Array();
        foreach($items as $item) {
            $type  = $item->type;
            $id = $item->id;
            $url = $this->base_url;
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
        if (!$rets) {
            echo "   content access error: {$urlsWithOptions}". PHP_EOL;
        } else {
            foreach($rets as $ret) {
                if ($ret) {
                    echo "   content access ok: {$ret->type} : {$ret->id} : related-{$stopOnRelated}.". PHP_EOL;
                    if (!$stopOnRelated) {
                        echo "Now accessing related". PHP_EOL;
                        $this->accessContents($ret->related, true);
                    }
                } else {
                    echo "   content access error";
                    print_r($ret);
                    echo PHP_EOL;
                }
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
        $ret_one = $curlClient->simpleGet($this->base_url, $params);
        if ($ret_one != null && isset($ret_one) && $ret_one->type == $type) {
            echo "   content access ok: {$type} : {$id}". PHP_EOL;
            $this->accessContents($ret_one->related);
        } else {
            echo "   content access failed: {$type} : {$id}". PHP_EOL;
        }
    }
    
}
?>