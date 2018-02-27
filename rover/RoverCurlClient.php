<?php
namespace Service\Rover;

require_once("CurlClient.php");

use Service\Rover\CurlClient;

class RoverCurlClient {
    protected $site_id = "5ceb5581-5708-43de-ad58-d2dbba312cf1";
    protected $params = array();
    protected $apiVer = "v2";
    protected $page_size = 30;
    protected $page = 1;
    /**
     * content / section / site / ...
     */
    protected $resource;

    public function __construct() {
    }

    protected function get() {
        $accessUrl = sprintf("/%s/%s", $this->apiVer, $this->resource);

        $curlClient = new CurlClient(   
            "https://prod-rover.mediaos.hearst.io",
            30,
            "361277",
            "326d6cffc3641b735c9064bb77295938");
            return $curlClient->get($accessUrl, $this->params);
    }

    public function getContents() {
        $params = array(
            "site" => $this->site_id,
            "page" => $this->page,
            "page_size" =>$this->page_size,
            "count" => "yes",
            "sort" => "-display_date",
            "status" => "3"
        );

        $this->setResource("content");
        $this->setParams($params);

        return $this->get();
    }

    public function getTops() {
        $this->resetParams();

        $url = "sites/".$this->site_id;
        $this->setResource($url);

        $ret = $this->get();

        $tops = (array)$ret->data->metadata->top_touts;
        $id_arr = Array();
        foreach ($tops as $item) {
            if ($item->type == "content") {
                $id_arr[] = $item->id;
            }
        }
        $idstr = implode(",", $id_arr);
        return $this->getByIds($idstr);
    }

    public function getByIds($idtr) {
        $this->resetParams();

        $this->setParam("id:in", $idtr);
        $this->setParam("page_size", "");
        return $this->getContents();
    }
    

    public function getContentsByPage($page, $page_size) {
        $params = array(
            "site" => $this->site_id,
            "page" => $page,
            "page_size" =>$page_size,
            "sort" => "-created_by",
            "count" => "yes",
            "status:in" => "3",
        );

        $this->setResource("content");
        $this->setParams($params);

        return $this->get();
    }
    

    public function resetParams() {
        $this->params = array();
    }

    function setParam($key, $value) {
        $this->params[$key] = $value;
        return $this;
    }

    function setParams($params) {
        $this->params = array_merge($this->params, $params);
        return $this;
    }

    function setResource($resource) {
        $this->resource = $resource;
        return $this;
    }

    public function setPageSize($size) {
        $this->page_size = $size;
        return $this;
    }

    function setPage($page) {
        $this->page = $page;
        return $this;
    }

    function getArticles($sectionSlug=null, $subSectionSlug=null) {
        $this->resetParams();

        $typesStr = "Standard Article,Long Form Article";
        $this->setParam("display_type.title:in", $typesStr);
        if ($sectionSlug != null && $sectionSlug != "") {
            $this->setParam("section.slug:in", $sectionSlug);
        }      
        if ($subSectionSlug != null && $subSectionSlug != "") {
            $this->setParam("subSection.slug:in", $subSectionSlug);
        }      

        return $this->getContents();
    }
    
    function getGalleries($sectionSlug=null, $subSectionSlug=null) {        
        $this->resetParams();
        $typesStr = "Listicle,Gallery";
        $this->setParam("display_type.title:in", $typesStr);
        if ($sectionSlug != null && $sectionSlug != "") {
            $this->setParam("section.slug:in", $sectionSlug);
        }      
        if ($subSectionSlug != null && $subSectionSlug != "") {
            $this->setParam("subSection.slug:in", $subSectionSlug);
        }      

        return $this->getContents();
    }

    function getContentsWithSection($sectionSlug=null, $subSectionSlug=null) {
        $this->resetParams();

        $typesStr = "Standard Article,Long Form Article,Listicle,Gallery";
        $this->setParam("display_type.title:in", $typesStr);

        if ($sectionSlug != null && $sectionSlug != "") {
            $this->setParam("section.slug:in", $sectionSlug);
        }      
        if ($subSectionSlug != null && $subSectionSlug != "") {
            $this->setParam("subSection.slug:in", $subSectionSlug);
        }      

        return $this->getContents();
    }
    

    function getSource($sourceId) {
        $this->resetParams();

        $url = "sources/".$sourceId;
        $this->setResource($url);

        return $this->get();
    }   

    function getCollection($collectionId) {
        $this->resetParams();

        $url = "collections/".$collectionId;
        $this->setResource($url);

        return $this->get();
    }   

    function getSection($sectionId) {
        $this->resetParams();

        $url = "sections/".$sectionId;
        $this->setResource($url);

        return $this->get();
    }   

    function getAuthor($authorId) {
        $this->resetParams();

        $url = "authors/".$authorId;
        $this->setResource($url);

        return $this->get();
    } 
    
    function getEntityById($entityName, $id) {
        $this->resetParams();

        $url = $entityName."/".$id;
        $this->setResource($url);

        return $this->get();
    }
    

    function getSectionBySlug($slug) {
        $this->resetParams();

        $url = "sections";
        $this->setResource($url);

        $this->setParam("slug", $slug);
        $this->setParam("site", $this->site_id);

        $sections = $this->get();
        if (isset($sections) && isset($sections->data) && count($sections->data) > 0) {
            return $sections->data[0];
        } else {
            return null;
        }
    }   
    
   
}
?>