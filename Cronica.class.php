<?php

define("ORGANIC_GROUP_NODE_TYPE", "group");
define("VOZMOB_STORY_TYPE", "blog");
define("DRUPAL_IMAGE_SUBDIR", "sites/default/files/image/1/");
define("DRUPAL_LANGUAGE_ENGLISH","en");
define("DRUPAL_LANGUAGE_SPANISH","es");

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
        $this->syntheticOldId = Cronica::MakeSyntheticOldId($content['town'],$content['id']);
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
        // set the language (using "the" as a proxy for english works suprisingly well on this dataset)
        $language = null;
        if(strpos($content['body'],"the")===false){
            $language = DRUPAL_LANGUAGE_SPANISH;
        } else {
            $language = DRUPAL_LANGUAGE_ENGLISH;
        }
        if($language){
            $this->node->language = $language;
        }
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
            return;
        }
        $destDir = DRUPAL_BASE.DRUPAL_IMAGE_SUBDIR;
        $destPath = $destDir.$picture;
        $this->picturesToCopy[$srcPath] = $destPath;    //queue it up to copy while saving
        // set the metadata on the node
        if(REALLY_IMPORT){
            // see http://drupal.org/node/458778#comment-1653696
            $anonymousUser = new stdClass();
            $anonymousUser->uid = DRUPAL_ANONYMOUS_UID;
            $tempDir = sys_get_temp_dir();
            $tempPath = $tempDir."/".$picture;
            copy($srcPath,$tempPath);   // TODO: handle this copy failing for some reason
            switchToDrupalPath();
            $fileNode = field_file_save_file($tempPath,array(),DRUPAL_IMAGE_SUBDIR,$anonymousUser);
            // TODO: handle this file not getting created
            switchToScriptPath();
        } else {
            $fileNode = new stdClass();
            $fileNode->fid = Node::RandomNid();
        }
        $this->node->field_image = array(0=>$fileNode);
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
    
    public static function MakeSyntheticOldId($town,$id){
        return $town."-".$id;
    }
        
}
?>