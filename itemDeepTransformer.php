<?php

require_once("../Embera-1.9.3/Lib/Embera/Autoload.php");
require_once("itemTransformer.php");
require_once("rover/RoverCurlClient.php");
use  Service\Rover\RoverCurlClient;

class ItemDeepTransformer extends ItemTransformer{
    public function go() {
        $new_item = parent::go();
        $item_rover = $this->item_rover;

        $new_item["section_id"] = $this->item_rover->section->id;
        $new_item["section_name"] = $this->item_rover->section->title;

        if (isset($item_rover->subsection)) {
            $new_item["subsection_id"] = $this->item_rover->subsection->id;
            $new_item["subsection_name"] = $this->item_rover->subsection->title;
        } else {
            $new_item["subsection_id"] = "";
            $new_item["subsection_name"] = "";
        }
        $new_item["url"] = "https:".$item_rover->metadata->links->frontend->prod;
        
        $new_item["feed_title"] = property_exists($item_rover->metadata, "short_title") ? $item_rover->metadata->short_title : $item_rover->title;
        $new_item["lead"]= property_exists($item_rover->metadata, "dek") ? $item_rover->metadata->dek : $item_rover->title;
        $new_item["feed_lead"]= property_exists($item_rover->metadata, "social_dek") ? $item_rover->metadata->social_dek : $item_rover->title;
        $new_item["author_byline"] = $item_rover->metadata->editor_attribution;

        if ($this->authors_rover[1] != null) {
            $new_item['author2_id'] = $this->authors_rover[1]->id;
            $new_item['author2'] = $this->authors_rover[1]->profile->display_name;
        } else {
            $new_item['author2_id'] = 0;
            $new_item['author2'] = "";
        }
        if ($this->authors_rover[2] != null) {
            $new_item['author3_id'] = $this->authors_rover[2]->id;
            $new_item['author3'] = $this->authors_rover[2]->profile->display_name;
        } else {
            $new_item['author3_id'] = 0;
            $new_item['author3'] = "";
        }

        $new_item["body"] = $this->makeBody();

        $collections_rover = $item_rover->collections;
        $collections = array();
        foreach($collections_rover as $collection_rover) {
            $collection = array();
            $collection["collection_id"] = $collection_rover->id;
            $collection["collection_name"] = $collection_rover->title;
            $collections[] = $collection;
        }
        $new_item["collections"] = $collections;

        $new_item["tags"] = Array();

        $new_item["related"] = $this->getRelated($new_item["url"]);

        $new_item["sponsor"] = $this->getSponsor();

        $new_item["gallery"] = $this->getSlidesContent();
        return $new_item;
    }

    protected function makeBody() {
        $item_rover = $this->item_rover;
        $body = $item_rover->body;
        // Removing Content Links
        preg_match_all('/<p class="body-el-text standard-body-el-text">(<em data-redactor-tag="em" data-verified="redactor">|)(<em data-redactor-tag="em" data-verified="redactor">|)<a class="body-el-link standard-body-el-link" href="(.*?)[^次ページ<\/a>]<\/p>/', $body, $match);
        foreach($match[0] as $k => $item){
            $body = mb_ereg_replace( $item, '', $body);
        }
        
        $body = str_replace("<p>", '<p class="body-el-text standard-body-el-text">', $body);
        $body = str_replace('<p class="', '<p class="body-el-text standard-body-el-text ', $body);
        $body = str_replace('<h3>', '<h3 class="body-el-text standard-body-el-text">', $body);
        $body = str_replace('<h3 class="', '<h3 class="body-el-text standard-body-el-text ', $body);

        $body = $this->replaceSNSEmbeds($body);
        $body = $this->replaceImages($item_rover, $body);
        $body = $this->replaceAllEmbeds($body);

        // make lazyload env
        $body = '<style>.lazy{display:none;}</style><link rel="stylesheet" href="https://sp--cosmopolitan-jp--com.global.ssl.fastly.net/api/json/stage/css/main.css" /><script src="https://sp--cosmopolitan-jp--com.global.ssl.fastly.net/api/json/stage/js/jquery-3.2.1.min.js"></script><script src="https://sp--cosmopolitan-jp--com.global.ssl.fastly.net/api/json/stage/js/jquery.lazyload.js"></script><script>$(function(){$("img.lazy").lazyload({threshold:200});var links = document.getElementsByTagName("a");for (i = 0; i < links.length; i++) {if (links[i].innerText == "次ページ" || links[i].innerText == ">前ページ"){links[i].style.backgroundColor = "#EC008C";links[i].style.color = "#FFFFFF";links[i].style.padding = "2% 5%";links[i].style.fontWeight = "bold";links[i].parentNode.style.textAlign = "center";links[i].parentNode.style.padding = "5%";if(links[i].innerText == "次ページ"){links[i].innerText = "続きを読む >";}else{links[i].style.display = "none";}}}});</script><div class="buzzing-recirc" data-section="beauty-fashion"><div id="site-wrapper" class="site-wrapper"><div class="standard-article " itemprop="articleBody"><div class="standard-article-body--container standard-article-body--container-main"><div class="standard-article-body--content"><div class="standard-article-body--text">' . $body . '</div></div></div></div></div></div>';

        return $body;        
    }

    protected function replaceOneSNS($pattern, $body, $prefix, $suffix, $embera) {
        preg_match_all($pattern, $body, $match, PREG_SET_ORDER);
        foreach ($match as $k => $item) {
            $sns_url = $item[1];
            $info = $embera->autoEmbed($sns_url);
            if ($info != $sns_url) {
                $body = str_replace($item[0], $prefix . $info . $suffix, $body);
            } else {
                $body = str_replace($item[0], "", $body);
            }
        }
        return $body;
    }

    protected function replaceSNSEmbeds($body) {
        $embera = new \Embera\Embera();

        $pattern = "|<p>\[facebook[^\]]+\](.*)\[/facebook\]</p>|U";
        $prefix = '<div class="embed embed--center embed--facebook"><div class="embed--inner">';
        $suffix = '</div></div>';
        $body = $this->replaceOneSNS($pattern, $body, $prefix, $suffix, $embera);

        $pattern = "|<p>\[twitter[^\]]+\](.*)\[/twitter\]</p>|U";
        $prefix = '<div class="embed embed--center embed--twitter"><div class="embed--inner">';
        $suffix = '</div></div>';
        $body = $this->replaceOneSNS($pattern, $body, $prefix, $suffix, $embera);

        $pattern = "|<p>\[instagram[^\]]+\](.*)\[/instagram\]</p>|U";
        $prefix = '<div class="embed embed--center embed--instagram"><div class="embed--inner">';
        $suffix = '</div></div>';
        $body = $this->replaceOneSNS($pattern, $body, $prefix, $suffix, $embera);

        $pattern = "|<p>\[youtube[^\]]+\](.*)\[/youtube\]</p>|U";
        $prefix = '<div class="embed embed--center embed--youtube"><div class="embed--inner">';
        $suffix = '</div></div>';
        $body = $this->replaceOneSNS($pattern, $body, $prefix, $suffix, $embera);

        $pattern = "|<p>\[pinterest[^\]]+\](.*)\[/pinterest\]</p>|U";
        $prefix = '<div class="embed embed--center embed--pinterest"><div class="embed--inner">';
        $suffix = '</div></div>';
        $body = $this->replaceOneSNS($pattern, $body, $prefix, $suffix, $embera);

        $pattern = "|\[facebook[^\]]+\](.*)\[/facebook\]|U";
        $prefix = '<div class="embed embed--center embed--facebook"><div class="embed--inner">';
        $suffix = '</div></div>';
        $body = $this->replaceOneSNS($pattern, $body, $prefix, $suffix, $embera);

        $pattern = "|\[twitter[^\]]+\](.*)\[/twitter\]|U";
        $prefix = '<div class="embed embed--center embed--twitter"><div class="embed--inner">';
        $suffix = '</div></div>';
        $body = $this->replaceOneSNS($pattern, $body, $prefix, $suffix, $embera);

        $pattern = "|\[instagram[^\]]+\](.*)\[/instagram\]|U";
        $prefix = '<div class="embed embed--center embed--instagram"><div class="embed--inner">';
        $suffix = '</div></div>';
        $body = $this->replaceOneSNS($pattern, $body, $prefix, $suffix, $embera);

        $pattern = "|\[youtube[^\]]+\](.*)\[/youtube\]|U";
        $prefix = '<div class="embed embed--center embed--youtube"><div class="embed--inner">';
        $suffix = '</div></div>';
        $body = $this->replaceOneSNS($pattern, $body, $prefix, $suffix, $embera);

        $pattern = "|\[pinterest[^\]]+\](.*)\[/pinterest\]|U";
        $prefix = '<div class="embed embed--center embed--pinterest"><div class="embed--inner">';
        $suffix = '</div></div>';
        $body = $this->replaceOneSNS($pattern, $body, $prefix, $suffix, $embera);
        
        $pattern_script = "<script async defer src=\"//www.instagram.com/embed.js\"></script>";
        if (strpos($body, $pattern_script) >= 0) {
            $body = str_replace($pattern_script, "", $body) . $pattern_script;
        }

        $pq_pattern = "|\[pullquote[^\]]+\](.*)\[/pullquote\]|U";
        preg_match_all($pq_pattern, $body, $match, PREG_SET_ORDER);
        foreach ($match as $k => $item) {
            $body = str_replace($item[0], "<blockquote>".$item[1]."</blockquote>", $body);
        }
    return $body;
    }


	protected function replaceAllEmbeds($bodyData)
	{
		// I used a copy of the shortcode from core, for a documented version of it refer the next link:
		// https://github.com/Hearst-Digital/Rams-CS/blob/6c0d07dbd77a618195ceb9fa09adb92c4b134caf/global/classes/Plugins.php#L428
		$embedsPattern = '#\[(\[?)(\w+)(?![\w-])([^\]\/]*(?:\/(?!\])[^\]\/]*)*?)(?:(\/)\]|\](?:([^\[]*(?:\[(?!\/\2\])[^\[]*)*)\[\/\2\])?)(\]?)#';
		// Replace all shortcodes
		if ( preg_match_all($embedsPattern, $bodyData, $matches) ) {
			$bodyData = preg_replace( $embedsPattern,'', $bodyData );
		}
		return $bodyData;
    }
    
	protected function replaceImages($content, $bodyData)
	{
        $imagePattern = "|\[image[^\]]+\](.*)\[/image\]|U";
		// Replace image placeholders
		if ( preg_match_all($imagePattern, $bodyData, $matches, PREG_SET_ORDER) ) {
			foreach ( $matches as $key => $match) {
                $mediaId = $this->stripMediaIdFromImageTag($match[0]);                
                $media = $this->getMediaById($content, $mediaId);
                if ($media == null) {
                    continue;
                }
                $metadata = $media->metadata;
                $selectedCrop = $metadata->selected_crop;
                $imgUrl = "";
                if (!property_exists($metadata->crops, $selectedCrop)) {
                    // legacy
                    $imgUrl = $metadata->legacy_crops->$selectedCrop;
                    $imgUrl = $this->makeItSsl($imgUrl);
                } else {
                    $crop = $metadata->crops->$selectedCrop;
                    $imgUrl = $media->media_object->hips_url . "?crop=" . $crop;
                }
                $imgTag = '<img class="lazy" data-original="'.$imgUrl.'" />';
                $bodyData = str_replace($match[0], $imgTag, $bodyData);
			}
		}
		return $bodyData;
	}
    
    protected function makeItSsl($url){
        $url = str_replace( 'http://stage-cjp.h-cdn.co', 'https://cjp--h-cdn--co.global.ssl.fastly.net', $url);
        $url = str_replace( 'http://cjp.h-cdn.co', 'https://cjp--h-cdn--co.global.ssl.fastly.net', $url);
        return $url;
    }

    protected function getMediaById($content, $mediaId) {
        $result = null;
        $images = array_filter($content->media, 
            function($media) use($mediaId) { 
                return $media->media_type == 'image' && $media->id == $mediaId;});
        if ($images != null && is_array($images) && count($images) > 0) {
            $images = array_values($images);
            $result = $images[0];
        }
        return $result;
    }
    protected function stripMediaIdFromImageTag($imageTag) {
        $pattern = "|mediaId=\'([^\']+)\'|";        
        if ( preg_match_all($pattern, $imageTag, $matches, PREG_SET_ORDER) ) {
            return $matches[0][1];
        }
        return null;                
    }

    protected function getRelated($url) {
        $ch = curl_init();
        $cxenseApiUrl = "http://api.cxense.com/public/widget/data?json={%22widgetId%22:%2211ae96e78c9ea57a25adeee1f91e318af9f53903%22,%22context%22:{%22url%22:%22" . $url . "%22}}";
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

            $client = new RoverCurlClient();
            if (strlen($id) > 4) {
                $client->setParam("display_id", $id);
            } else  {
                if ($type == "article") {
                    $client->setParam("legacy_id", $id);
                } else {
                    $client->setParam("legacy_id", "-".$id);
                }
            }
            $client->setPageSize(1);
            $ret = $client->getContents();
            if ($ret->meta->result_count <= 0) {
                continue;
            }
        
            $thumbnail = $item['dominantimage'];
            if (strpos($item['dominantimage'], "//cjp.h-cdn.co")) {
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
            } else if (strpos($item['dominantimage'], "//hips.hearstapps.com"))  {
                if (!strpos($thumbnail, "?")) {
                    $thumbnail .= "?resize=320:*&crop=320:160";
                }
            }
        
            $thumbnail = $this->makeItSsl($thumbnail);
            $related[] = array( 'thumbnail' => $thumbnail, 'title' => $item['title'], 'id' => $id, 'type' => $type);
        }

        return $related;
                
    }

    protected function getSponsor() {
        $sponsor_rover = $this->item_rover->sponsor;
        if (isset($sponser_rover) && $sponser_rover != null) {
            $sponsor = Array();
            $sponsor["sponsor_type"] = $this->item_rover->metadata->sponsor->type;
            $sponsor["sponsor_id"] = $sponsor_rover->id;
            $sponsor["sponsor_name"] = $sponsor_rover->title;
            $sponsor["sponsor_url"] = $sponsor_rover->url;
    
            $client = new RoverCurlClient();
            $ret = $client->getEntityById("images", $sponsor_rover->logo);
            if (isset($ret->data)) {
                $sponsor["sponsor_image"] = $ret->data->hips_url;            
            } else {
                $sponsor["sponsor_image"] = "";            
            }
        } else {
            $sponsor = new stdClass();
        } 

        return $sponsor;
    }

    protected function getSlidesContent() {
        $content = $this->item_rover;
        $slides = Array();
        // role = 1 -> slide
        $slideMediaArr = array_filter($content->media, function ($slide) {
            // slides
            return $slide->role == 1;
        });
        foreach ($slideMediaArr as $slide_rover) {
            $slide = Array();
            $slide["url"] = $slide_rover->media_object->hips_url;
            if (property_exists($slide_rover->metadata, "headline")) {
                $slide["headline"] =$slide_rover->metadata->headline;
            } else {
                $slide["headline"] ="";
            }
            $slide["body"] = $slide_rover->metadata->dek;
            $slides[] = $slide;
        }

        return $slides;
    }
    

}
?>