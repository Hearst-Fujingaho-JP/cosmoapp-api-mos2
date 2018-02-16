<?php
class ItemTransformer {
    // https://github.com/HearstCorp/rover/wiki/User-guide
    const MEDIA_ROLE_SLIDE = 1;
    const MEDIA_ROLE_SOCIAL = 2;
    const MEDIA_ROLE_LEAD = 3;
    const MEDIA_ROLE_BODY = 4;
    const MEDIA_ROLE_INDEX = 12;

    protected $item_rover;
    protected $authors_rover;
    public function __construct($item_rover, $authors_rover) {
        $this->item_rover = $item_rover;
        $this->authors_rover = $authors_rover;
    }

    public function go() {
        $item_rover = $this->item_rover;
        $new_item = Array();
        $new_item["id"] = $item_rover->display_id;

        $display_type = $item_rover->display_type->title;
        if ($display_type == "Standard Article" or $display_type == "Long Form Article") {
            $new_item["type"] = "article";
        } else if ($display_type == "Listicle" or $display_type == "Gallery") {
            $new_item["type"] = "gallery";
        } else {
            $new_item["type"] = "unknown";
        }
        $new_item["title"] = $item_rover->title;
        $new_item["feed_title"] = $item_rover->metadata->index_title;
        $new_item["lead"] = $item_rover->metadata->dek;
        $new_item["feed_lead"] = $item_rover->metadata->social_dek;

        if (isset($this->authors_rover) && count($this->authors_rover) > 0 && $this->authors_rover[0] != null) {
            $new_item["author_id"] = $this->authors_rover[0]->profile->id;
            $new_item["author"] = $this->authors_rover[0]->profile->display_name;
        } else {
            $new_item["author_id"] = 0;
            $new_item["author"] = "";
        }

        $indexImage = $this->getFirstImageByRole($item_rover, ItemTransformer::MEDIA_ROLE_INDEX);
        if ($indexImage != null) {
            $indexImageUrl = $indexImage->media_object->hips_url;
            $new_item["image"] = $indexImageUrl."?resize=640:*&crop=640:320";
        } else {
            $new_item["image"] = "";
        }

        $ts = new DateTime($item_rover->created_at, new DateTimeZone("UTC"));
        $ts->setTimezone(new DateTimeZone('Asia/Tokyo'));
        $pubdate = $ts->getTimestamp();
        $new_item["pubdate"] = $pubdate;
        
        return $new_item;
    }

    protected function getFirstImageByRole($content, $role) {
        $result = null;
        $images = array_filter($content->media, 
            function($media) use($role) { 
                return $media->media_type == 'image' && $media->role == $role;});
        if ($images != null && is_array($images) && count($images) > 0) {
            $images = array_values($images);
            $result = $images[0];
        }
        return $result;
    }
    
    
}
?>