<?php
//ini_set( 'display_errors', 1 );
date_default_timezone_set('Asia/Tokyo');


$start_date=date('Y-m-d', strtotime('-1 day'));
$end_date=date('Y-m-d', strtotime('-1 day'));



require_once('/opt/ezpublish/sites/theweddingjpz/ez/extension/hfjelleutils/classes/google-api-php-client/src/Google_Client.php');
require_once('/opt/ezpublish/sites/theweddingjpz/ez/extension/hfjelleutils/classes/google-api-php-client/src/contrib/Google_AnalyticsService.php');

define('CLIENT_ID', '759322767988-69p9ar75fnpsjgsrhotuep78e6s9kn9n.apps.googleusercontent.com');
define('SERVICE_ACCOUNT_NAME', 'cosmo-popular@cosmo-151905.iam.gserviceaccount.com');
define('KEY_FILE', '/opt/ezpublish/sites/theweddingjpz/ez/extension/hfjelleutils/classes/google-api-php-client/key/Cosmo-3e6f53218d88.p12');

define('PROFILE_ID', '111471321');

$client = new Google_Client();
$client->setApplicationName("Google Analytics Cosmo");
$client->setClientId(CLIENT_ID);

   if(isset($_SESSION["ga_service_token"])){
            $client->setAccessToken($_SESSION["ga_service_token"]);
        }

        $credentials = new Google_AssertionCredentials(SERVICE_ACCOUNT_NAME,
                                                       array('https://www.googleapis.com/auth/analytics'),
                                                       file_get_contents(KEY_FILE));
        $client->setAssertionCredentials($credentials);

        if($client->getAuth()->isAccessTokenExpired()){
            $client->getAuth()->refreshTokenWithAssertion($credentials);
        }
        $_SESSION["ga_service_token"] = $client->getAccessToken();


$pfilter="ga:pagePath=~/*";
$pfilter.=";ga:sourceMedium!~(social|cpc|display|email|recommend)$|ycd.*|.*trc.taboola.com.*|((elle|hearst).co|(harpersbazaar|wedding|mensclub|25ans)).jp;ga:sourceMedium!~(yn / external)$|(/ organic)$";

$service = new Google_AnalyticsService($client);
$data = $service->data_ga->get(
                                         'ga:' . PROFILE_ID,
                                         $start_date,
                                         $end_date,
                                         'ga:uniquePageviews,ga:pageviews',
                                         array(
                                             'dimensions'  => 'ga:pagePath',
                                             'sort'        => '-ga:uniquePageviews,ga:pageviews',
                                             'filters' => $pfilter,
                                             'max-results' => '100' // 件数
                                              )
                                         );

$ranking_legacy = array();
$ranking_display = array();
// TODO:for testing:
$ranking_display[] = "16022087";
foreach($data['rows'] as $item){

    if( $item[0] == '/' ){
        continue;
    }

		if( strpos( $item[0], '/love/sex/' ) !== false){
				continue;
		}

    $pattern = '/\/(a|g)\d+/';
    preg_match($pattern, $item[0], $matches);
		if(!$matches){
			continue;
		}
    $tmp = explode($matches[1],$matches[0]);
    $id = $tmp[1];

    if (strlen($id) > 4) {
        // mos2: display_id
        $ranking_display[] = $id;
    } else {
        // rams: legacy_id
        $legacy_id = $id;
        if ($matches[1] == 'g') {
            $legacy_id = "-".$legacy_id;
        }
        $ranking_legacy[] = $legacy_id;
    }

    if( count($ranking_legacy) + count($ranking_display) >= 15 ){
        break;
    }
}

header("Content-Type: application/json; charset=utf-8");

require_once("rover/RoverCurlClient.php");
require_once("itemTransformer.php");

use  Service\Rover\RoverCurlClient;

$client_legacy = new RoverCurlClient();
$client_legacy->setParam("legacy_id:in", implode(",", $ranking_legacy));
$client_legacy->setParam("page_size", "");
$ret_legacy = $client_legacy->getContents();

$client_display = new RoverCurlClient();
$client_display->setParam("display_id:in", implode(",", $ranking_display));
$client_display->setParam("page_size", "");
$ret_display = $client_display->getContents();

$client = new RoverCurlClient();

$data_new = array_merge($ret_legacy->data, $ret_display->data);

$ret = (object)array("data" => $data_new);

$result = Array(
    "type" => "ranking",
    "id" => "0",
    "name" => "RANKING",
    "total" => (int)$ret_display->meta->result_count + (int)$ret_legacy->meta->result_count,
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


?>
