<?php

$TYPE = $_GET{'type'};
$ID = $_GET{'id'};
$CACHE_ID = $TYPE.'-'.$ID;

if($json = $cache->get($CACHE_ID)){

}else{

$URL = "http://www.cosmopolitan-jp.com/api/json/{$TYPE}.{$ID}/?dynamic";
//$USERNAME = "stagingarea";
//$PASSWORD = "hearst57";

 $ch = curl_init();
 curl_setopt($ch, CURLOPT_URL, $URL);
 curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
 //curl_setopt($ch, CURLOPT_USERPWD, $USERNAME . ":" . $PASSWORD);
 curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
 $json = curl_exec($ch);

//$targetUrl = "http://www.cosmopolitan-jp.com/api/json/{$TYPE}.{$ID}/";


//$json = file_get_contents($targetUrl);
if ($json === false) {
    throw new \RuntimeException('file not found.');
}

$data = json_decode($json, true);

if(!$data){

		$to      = 'michiyasu.ishizaka@hearst.co.jp';
		$subject = 'Error!!!';
		$message = $CACHE_ID;
		$headers = 'From: api@sp-cosmopolitan-jp.com' . "\r\n";

		mail($to, $subject, $message, $headers);

		//Try One More!
		sleep(2);
		$URL = $URL . '?dynamic';
    curl_setopt($ch, CURLOPT_URL, $URL);
		$json = curl_exec($ch);
		$data = json_decode($json, true);

}


$subsection_id = "0";
$subsection = "";
if($data['subsection']){
    $subsection_id = $data['subsection']['subsection_id'];
    $subsection = $data['subsection']['subsection_ramsname'];
}

$author2_id = "0";
$author2 = "";
if($data['editors'][1]){
    $author2_id = $data['editors'][1]['ed_id'];
    $author2 = $data['editors'][1]['ed_fullname'];
}

$author3_id = "0";
$author3 = "";
if($data['editors'][2]){
    $author3_id = $data['editors'][2]['ed_id'];
    $author3 = $data['editors'][2]['ed_fullname'];
}

$enddate = "0";
if($data['raw'][print_issue_date]){
  $enddate = $data['raw'][print_issue_date];
}

$image = "";
if($data['images'][0]['hd-aspect']['640']){
    $image = $data['images'][0]['hd-aspect']['640']['url'];
}else if($data['images'][0]['hd-aspect']['480']){
    $image = $data['images'][0]['hd-aspect']['480']['url'];
}else if($data['images'][0]['hd-aspect']['320']){
    $image = $data['images'][0]['hd-aspect']['320']['url'];
}else if($data['images'][0]['hd-aspect']['160']){
    $image = $data['images'][0]['hd-aspect']['160']['url'];
}else if($data['images'][0]['landscape']['640']){
    $image = $data['images'][0]['landscape']['640']['url'];
}else if($data['images'][0]['landscape']['480']){
    $image = $data['images'][0]['landscape']['480']['url'];
}else if($data['images'][0]['landscape']['320']){
    $image = $data['images'][0]['landscape']['320']['url'];
}else if($data['images'][0]['landscape']['160']){
    $image = $data['images'][0]['landscape']['160']['url'];
}
$image = makeItSsl($image);

$tags = array();
foreach($data['content_topics'] as $tag){
    if($tag['topic_id']){
        $tags[] = array('topic_id' => $tag['topic_id'], 'topic' => $tag['topic']); 
    }
}

$collections = array();
foreach($data['collections'] as $collection){
    if($collection['collection_id']){
            $collections[] = array('collection_id' => $collection['collection_id'], 'collection_name' => $collection['collection_name']);
    }
}

$sponsor = (object)null;
if($data['sponsor']){
    $sponsor = array( 'sponsor_type' => $data['sponsor']['sponsor_type'], 
                      'sponsor_id' => $data['sponsor']['sponsor']['sponsor_id'],
                      'sponsor_name' => $data['sponsor']['sponsor']['sponsor_display_name'],
                      'sponsor_url' => $data['sponsor']['sponsor']['sponsor_url'],
                      'sponsor_image' => "https://cjp--h-cdn--co.global.ssl.fastly.net/assets/".$data['sponsor']['sponsor']['sponsor_image']
                      );
}


/*** Related content from Cxence ***/

$url = $data['metadata']['complete_url'];
$ch = curl_init();
$cxenseApiUrl = "http://api.cxense.com/public/widget/data?json={%22widgetId%22:%22866e94bd6764bec3d53e6d6185a60961de0108a2%22,%22context%22:{%22url%22:%22" . $url . "%22}}";
curl_setopt($ch, CURLOPT_URL, $cxenseApiUrl);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
$result = curl_exec($ch);
$items = json_decode( $result, true );
$related = array();
foreach($items['items'] as $item){

    $pattern = '/\/(a|g)\d+/';
    preg_match($pattern, $item['url'], $matches);
    $tmp = explode($matches[1],$matches[0]);
    $id = $tmp[1];

    $type = 'article';
    if($matches[1] == 'g'){
        $type = 'gallery';
    }

    $thumbnail = explode('/',$item['dominantimage']);
    if( count($thumbnail) == 7 ){
        if( preg_match("/^square-/i", $thumbnail[6] )) {
            $file = 'landscape-' . ltrim($thumbnail[6], 'square-');
        }else if( preg_match("/^gallery-/i", $thumbnail[6] )) {
            $file = 'landscape-' . ltrim($thumbnail[6], 'gallery-');
        }else if( preg_match("/^\d/i", $thumbnail[6] )) {
            $file = 'landscape-' . $thumbnail[6];
        }else{
						$file = $thumbnail[6];
				}
        $thumbnail = "http://{$thumbnail[2]}/{$thumbnail[3]}/{$thumbnail[4]}/{$thumbnail[5]}/320x160/{$file}";
    }else if( count($thumbnail) == 8 ){
        if( preg_match("/^square-/i", $thumbnail[7] )) {
                    $file = 'landscape-' . ltrim($thumbnail[7], 'square-');
        }else if( preg_match("/^gallery-/i", $thumbnail[7] )) {
                    $file = 'landscape-' . ltrim($thumbnail[7], 'gallery-');
        }else if( preg_match("/^\d/i", $thumbnail[7] )) {
                    $file = 'landscape-' . $thumbnail[7];
        }else{
										$file = $thumbnail[7];
				}
        $thumbnail = "http://{$thumbnail[2]}/{$thumbnail[3]}/{$thumbnail[4]}/{$thumbnail[5]}/320x160/{$file}";
    }

    $thumbnail = makeItSsl($thumbnail);
    $related[] = array( 'thumbnail' => $thumbnail, 'title' => $item['title'], 'id' => $id, 'type' => $type);
}

/*** Related content from Cxence ***/


$id = $data['raw']['id'];
$images = array();
if($TYPE == 'gallery'){
    $id = $data['raw']['group_id'];
    $imgs = $data['images'];
    $img_urls = array();
    foreach($imgs as $img){
        if($img['gallery']['980']){
            $img_urls[$img['gallery']['980']['id']] = $img['gallery']['980']['url'];
        }else if($img['gallery']['768']){
            $img_urls[$img['gallery']['768']['id']] = $img['gallery']['768']['url'];
        }else if($img['gallery']['640']){
            $img_urls[$img['gallery']['640']['id']] = $img['gallery']['640']['url'];
        }else if($img['gallery']['480']){
            $img_urls[$img['gallery']['480']['id']] = $img['gallery']['480']['url'];
        }
        
    }
    foreach($data['gallery_image_data'] as $img){
        $img_url = makeItSsl($img_urls[$img['image_id']]);
        $images[] = array('headline' => $img['site_title'], 'body' => $img['site_caption'], 'url' => $img_url);
    }
}

$url = $data['metadata']['complete_url'];


// Adding caption for images in body
$raw_body = $data['raw']['body'];
preg_match_all('/\[image (.*?)\]/', $raw_body, $raw_match);
$captions = array();
foreach($raw_match[0] as $caption){
  $caption = explode('caption="',$caption);
  $caption = explode('" loc=',$caption[1]);
  $captions[] = $caption[0];
}

$body = $data['body'];
preg_match_all('/<img data-id=(.*?)>/', $body, $match);
$mids[] = array();
$srcs[] = array();
foreach($match[0] as $k => $item){
  $mid = explode('data-id="',$item);
  $mid = explode('"',$mid[1]);
  $src = explode('src="',$item);
  $src = explode('"',$src[1]);

	if($captions[$k]){
  $tag_img_replaced = '<img data-id="' . $mid[0] . '" src="' . makeItSsl($src[0]) . '">' . '<div class="standard-body-el-text caption">' . $captions[$k] . '</div>';
	}else{
  $tag_img_replaced = '<img data-id="' . $mid[0] . '" src="' . makeItSsl($src[0]) . '">';
	}
  $body = preg_replace( "<{$item}>", $tag_img_replaced, $body, 1);
}

// Removing Content Links
preg_match_all('/<p class="body-el-text standard-body-el-text">(<em data-redactor-tag="em" data-verified="redactor">|)(<em data-redactor-tag="em" data-verified="redactor">|)<a class="body-el-link standard-body-el-link" href="(.*?)[^次ページ<\/a>]<\/p>/', $body, $match);
foreach($match[0] as $k => $item){
  $body = mb_ereg_replace( $item, '', $body);
}

// Making img for lazy
if($TYPE == 'article'){
	$body = str_replace( ' src="https://cjp--h-cdn--co.global.ssl.fastly.net/', ' class="lazy" data-original="https://cjp--h-cdn--co.global.ssl.fastly.net/', $body);

	$body = '<style>.lazy{display:none;}</style><link rel="stylesheet" href="https://sp--cosmopolitan-jp--com.global.ssl.fastly.net/api/json/stage/css/main.css" /><script src="https://sp--cosmopolitan-jp--com.global.ssl.fastly.net/api/json/stage/js/jquery-3.2.1.min.js"></script><script src="https://sp--cosmopolitan-jp--com.global.ssl.fastly.net/api/json/stage/js/jquery.lazyload.js"></script><script>$(function(){$("img.lazy").lazyload({threshold:200});var links = document.getElementsByTagName("a");for (i = 0; i < links.length; i++) {if (links[i].innerText == "次ページ" || links[i].innerText == ">前ページ"){links[i].style.backgroundColor = "#EC008C";links[i].style.color = "#FFFFFF";links[i].style.padding = "2% 5%";links[i].style.fontWeight = "bold";links[i].parentNode.style.textAlign = "center";links[i].parentNode.style.padding = "5%";if(links[i].innerText == "次ページ"){links[i].innerText = "続きを読む >";}else{links[i].style.display = "none";}}}});</script><div class="buzzing-recirc" data-section="beauty-fashion"><div id="site-wrapper" class="site-wrapper"><div class="standard-article " itemprop="articleBody"><div class="standard-article-body--container standard-article-body--container-main"><div class="standard-article-body--content"><div class="standard-article-body--text">' . $body . '</div></div></div></div></div></div>';

}


// Adding js for Instagram
if( strpos($body,'instagram-media') !== false ){
	$body = '<script async defer src="https://platform.instagram.com/en_US/embeds.js"></script>' . $body;
}

// Adding js for Twitter
if( strpos($body,'embed--twitter') !== false ){
	$body = str_replace( 'blockquote class=""', 'blockquote class="twitter-tweet"', $body);
  $body = '<script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>' . $body;
}

// Adding js for Facebook
if( strpos($body,'fb-post') !== false ){
	$body = '<div id="fb-root"></div><script>(function(d, s, id) {var js, fjs = d.getElementsByTagName(s)[0];if (d.getElementById(id)) return;js = d.createElement(s); js.id = id;js.src = "https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.9";fjs.parentNode.insertBefore(js, fjs);}(document, \'script\', \'facebook-jssdk\'));</script>' . $body;
}


//Adding player.hearstdigitalstudios.com
if( strpos($body,'player.hearstdigitalstudios.com') !== false ){
  $body = str_replace( '//player.hearstdigitalstudios.com/', 'https://player.hearstdigitalstudios.com/', $body);
}


$json = array(  'id' => $id,
                'pubdate' => $data['raw']['date'],
                'enddate' => $enddate,
                'section_id' => $data['section']['section_id'],
                'section' => $data['section']['section_ramsname'],
                'subsection_id' => $subsection_id,
                'subsection' => $subsection,
                'url' => $url,
                'title' => $data['raw']['title'],
                'feed_title' => $data['raw']['short_title'],
                'lead' => $data['raw']['sub_heading'],
                'feed_lead' => $data['raw']['abs'],
                'author_byline' => $data['raw']['editor_byline'],
                'author_id' => $data['editors'][0]['ed_id'],
                'author' => $data['editors'][0]['ed_fullname'],
                'author2_id' => $author2_id,
                'author2' => $author2,
                'author3_id' => $author3_id,
                'author3' => $author3,
                'image' => $image,
                'body' => $body,
                'tags' => $tags,
                'collections' => $collections,
                'sponsor' => $sponsor,
                'related' => $related,
                'gallery' => $images
                );

$json = json_encode( $json, true );

	if($id){
		$cache->save($json, $CACHE_ID);
	}



curl_close($ch);

}

echo $json;



function makeItSsl($url){
    //return str_replace( 'http://cjp.h-cdn.co', 'https://cjp--h-cdn--co.global.ssl.fastly.net', $url);
		$url = str_replace( 'http://stage-cjp.h-cdn.co', 'https://cjp--h-cdn--co.global.ssl.fastly.net', $url);
    $url = str_replace( 'http://cjp.h-cdn.co', 'https://cjp--h-cdn--co.global.ssl.fastly.net', $url);
    return $url;
}


?>
