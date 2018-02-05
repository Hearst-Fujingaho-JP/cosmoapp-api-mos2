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

$ranking = array();
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

    $type = 'article';
    if($matches[1] == 'g'){
        $type = 'gallery';
    }

    $ranking[] = "{$type}.{$id}";
    if( count($ranking) >= 15 ){
        break;
    }

}

$RANKING = '&ranking=' .implode( ',', $ranking );

?>
