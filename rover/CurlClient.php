<?php
namespace Service\Rover;

class CurlClient {
    private $host = "";
    private $clientId = "";
    private $secret = "";
    private $timeout = 0;
    public function __construct( $host , $timeout, $clientId, $secret) {
        $this->host = $host;
        $this->clientId = $clientId;
        $this->secret = $secret;
        $this->timeout = $timeout;
    }

	public function buildAuthHeader(){
		$timestamp = time();
		$key = $this->clientId;
        $secret = $this->secret;
        
        $sig = $this->makeSig( $key, $secret, $timestamp);
		return array(
			"User-Agent:"."spcosmo.japan.api",
			"content-type:"."application/json",
			"Authorization:"."Doorman-SHA256 Credential={$key}",
			"Signature:".$sig,
			"Timestamp:".$timestamp
		);
	}
	/**
	 * Generate oauth sig
	 */
	private function makeSig( $key, $secret, $timestamp ){
        $sign = $key.$secret.$timestamp;
		// normalize
		$tosign = iconv(mb_detect_encoding($sign, mb_detect_order(), true), "UTF-8", $sign);
		return hash("sha256", $tosign);
    }

    /** 
    * Send a GET requst using cURL 
    * @param string $url to request 
    * @param array $get values to send 
    * @param array $options for cURL 
    * @return JSON object
    */ 
    function get($url, array $get = NULL, array $options = array()) {    
        $authHead = $this->buildAuthHeader();

        // 1 encode value
        // 2 make get query string
        $newGet = array_map(function($value, $key) {
            return $key . "=" . urlencode($value);
        }, array_values($get), array_keys($get));

        $defaults = array( 
            //CURLOPT_URL => $url. (strpos($url, '?') === FALSE ? '?' : ''). http_build_query($get), 
            CURLOPT_URL => $this->getFullUrl($url). (strpos($url, '?') === FALSE ? '?' : ''). implode("&", $newGet), 
            CURLOPT_HEADER => 0, 
            CURLOPT_RETURNTRANSFER => TRUE, 
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $authHead
        ); 

        $ch = curl_init(); 
        curl_setopt_array($ch, ($options + $defaults));

        if( ! $result = curl_exec($ch)) 
        { 
            trigger_error(curl_error($ch)); 
        } 
        curl_close($ch); 

        $ret = json_decode($result);
        if (property_exists($ret, "errors")) {
            //trigger_error($result, E_USER_ERROR);
            return null;
        }
        return $ret; 
    }  

    function getFullUrl($relateUrl) {
        return $this->host.$relateUrl;
    }

        /** 
    * Send a GET requst using cURL 
    * @param string $url to request 
    * @param array $get values to send 
    * @param array $options for cURL 
    * @return JSON object
    */ 
    function simpleGet($url, array $get = NULL, array $options = array()) {    
        // 1 encode value
        // 2 make get query string
        $newGet = array_map(function($value, $key) {
            return $key . "=" . urlencode($value);
        }, array_values($get), array_keys($get));

        $defaults = array( 
            CURLOPT_URL => $this->getFullUrl($url). (strpos($url, '?') === FALSE ? '?' : ''). implode("&", $newGet), 
            // CURLOPT_HEADER => 0, 
            CURLOPT_RETURNTRANSFER => TRUE, 
            CURLOPT_TIMEOUT => $this->timeout
        ); 

        $ch = curl_init(); 
        curl_setopt_array($ch, ($options + $defaults));

        if( ! $result = curl_exec($ch)) 
        { 
            trigger_error(curl_error($ch)); 
        } 
        curl_close($ch); 

        $ret = json_decode($result);
        return $ret; 
    }  

    /**
    * 複数並列で実行します。
    * 引数のurlListにアクセスしたいURLの一覧を配列で入れておきます。
    */
    function multi_curl_execute($urlWithOptions) {

        // なにもないときは戻る。
        if (empty($urlWithOptions)) {
            return false;
        }

        $defaults = array( 
            CURLOPT_HEADER => 0, 
            CURLOPT_RETURNTRANSFER => TRUE, 
            CURLOPT_TIMEOUT => $this->timeout
        ); 

        // まずはMultiCurlを実行するための配列を作る。
        $chList = Array();
        foreach($urlWithOptions as $urlWithOption) {
            $url =  $urlWithOption[0];
            $options = $urlWithOption[1];
            // CURL 初期化
            $ch = curl_init();

            // 1 encode value
            // 2 make get query string
            $newGet = array_map(function($value, $key) {
                return $key . "=" . urlencode($value);
            }, array_values($options), array_keys($options));
            
            curl_setopt($ch, CURLOPT_URL, $this->getFullUrl($url). (strpos($url, '?') === FALSE ? '?' : ''). implode("&", $newGet));
                
            curl_setopt_array($ch, $defaults);
            $chList[] = $ch;
        }

        // 存在しなければ戻る
        if (empty($chList)) {
            return false;
        }

        // マルチ cURL ハンドルを作成します
        $mh = curl_multi_init();
        foreach($chList as $ch) {
            curl_multi_add_handle($mh,$ch);
        }

        $running=null;
        // 全部実行します。すべて実行し終わるまで待ちます。
        do {
            curl_multi_exec($mh, $running);
            usleep(1000);
        } while ($running > 0);

        // 結果の取得
        $returnList = Array();
        foreach($chList as $key => $ch) {
            $return_json = curl_multi_getcontent($ch);
            $returnList[] = json_decode($return_json);
        }

        // ハンドルを閉じます
        foreach($chList as $ch) {
            curl_multi_remove_handle($mh, $ch);
        }
        curl_multi_close($mh);

        // 結果が戻ります。
        return $returnList;
    }    

    
}
?>