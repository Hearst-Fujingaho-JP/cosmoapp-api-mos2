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
			"User-Agent:"."spcosmo.test",
			"content-type:"."application/json",
			"Authorization:"."Doorman-SHA256 Credential={$key}",
			//"Authorization" => "Doorman-SHA1 Credential={$key}",
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

    
}
?>