<?php

define("ORGANIC_GROUP_NODE_TYPE", "group");
define("VOZMOB_STORY_TYPE", "blog");
define("DRUPAL_IMAGE_SUBDIR", "sites/default/files/image/1/");

/**
 * Manage a Cronica for importing
 */
class Cronica extends Node {

    var $syntheticOldId;
    var $picturesToCopy = array();  // array from src to dest

    public static function FromArray($content,$contentImageDir){
        $cronica = new Cronica($content,$contentImageDir);
        return $cronica;
    }
	
	public function Cronica($content,$contentImageDir){
	    $this->initFromContent($content,$contentImageDir);
	}
	
	private function initFromContent($content,$contentImageDir){
	    $this->syntheticOldId = $content['town']."-".$content['id'];
        // set the type for a vozmob report
        $this->setType(VOZMOB_STORY_TYPE);
        // set the user to anonymous
        $this->setAuthorUserId(DRUPAL_ANONYMOUS_UID);
        // set the town as the group
        $groupNid = $this->getGroupByName($content['town']);
        if($groupNid){
            $this->setGroup($groupNid,$content['town']);
        } else {
            Log::Write("    ERROR: Node ".$this->getSyntheticOldId()." has an unknown group named '".$content['town']."'");
        }
        // set the title to the name
        $this->setTitle($content['name']);
        // set the body
        $this->setBody($content['body']);
        // set the created time and updated time
        $this->setCreatedTime($content['timestamp']);
        // set if it is published or not
        $this->setStatus($content['published']);
        // set the geo lat and long
        $this->setLatLon($content['latitude'],$content['longitude']);
        // add in any pictures
        $this->addImage($content['picture'],$content['timestamp'],$contentImageDir);
        // set the free-text tags
        $this->addTags($content['tags']);
        // set the old id (town-id)
//TODO
	}

    private function addTags($tagList){
        $tags = explode(",",$tagList);
        foreach($tags as $tag){
            $term = TaxonomyTerm::FindOrCreateByName($tag);
            $this->node->taxonomy[$term->tid] = $term;
        }
    }

    private function addImage($picture,$timestamp,$srcDir=null){
        if(empty($picture)) return;
        // copy the file over
        $srcPath = $srcDir.$picture;
        if(!file_exists($srcPath)){
            Log::Write("    ERROR: source image file doesn't exists at ".$srcPath);
        }
        $destPath = DRUPAL_BASE.DRUPAL_IMAGE_SUBDIR.$picture;
        $this->picturesToCopy[$srcPath] = $destPath;    //queue it up to copy while saving
        // set the metadata on the node
        $imageMetadata = array(
            "uid"=>DRUPAL_ANONYMOUS_UID,
            "filename"=>$picture,
            "filepath"=>DRUPAL_IMAGE_SUBDIR.$picture,
            "status"=>1,
            "timestamp"=>$timestamp
        );
        $this->node->field_image = array($imageMetadata);
    }

    public function copyImages(){
        foreach($this->picturesToCopy as $src=>$dest){
            if(REALLY_IMPORT){
                $worked = copy($src, $dest);
                if(!$worked){
                    Log::Write("    ERROR: couldn't copy image from ".$srcPath." to ".$destPath);
                    exit();
                }
            }
        }
    }

    private function setLatLon($lat,$lon){
        $locData = array("latitude"=>$lat,"longitude"=>$lon);
        $this->node->location = $locData;
        $this->node->locations = array($locData);
    }

    private function setGroup($groupNid,$groupName){
        $this->node->og_groups = array($groupNid=>$groupNid);
    }

	private function getGroupByName($name){
	    return Node::FindByTitleAndType($name,ORGANIC_GROUP_NODE_TYPE);
	}
	
	public function getSyntheticOldId(){
	    return $this->syntheticOldId;
	}
	
	/**
	 * Once 
	 */
	public function exists(){
//TODO
        return false;
	}
	
}
?>