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

    public static function FromArray($content,$contentImageDir,$nid){
        $cronica = new Cronica($content,$contentImageDir,$nid);
        return $cronica;
    }
    
    public function Cronica($content,$contentImageDir,$nid=null){
        $this->loadFromNid($nid);
        $this->initFromContent($content,$contentImageDir,($nid!=null));
    }
    
    private function initFromContent($content,$contentImageDir,$updating){
        $this->syntheticOldId = Cronica::MakeSyntheticOldId($content['town'],$content['id']);
        
        // set the title to the name
        $this->setTitle($content['name']);
        // set the body
        $this->setBody($content['body']);
        // make sure comments are allowed (WTF: why doesn't this work automatically?)
        $this->node->comment = 2;   // TODO: move this to a helper function
        // set if it is published or not
        $this->setStatus($content['published']);
        // add in any pictures
        $this->addImage($content['picture'],$content['timestamp'],$contentImageDir);
        // set the location
        $this->setLocation($content['latitude'],$content['longitude'],$content['address']);
        // update the created time (updated time will set itself to now, which seems fine (to remember when we last imported it)
        $this->setCreatedTime($content['timestamp']);
        
        if(!$updating){     // to make it easier, only set these on first import
            // set the type for a vozmob report
            $this->setType(VOZMOB_STORY_TYPE);
            // set the user to anonymous
            $this->setAuthorUserId(DRUPAL_ANONYMOUS_UID);
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
            // set the town as the group
            $groupNid = $this->getGroupByName($content['town']);
            if($groupNid){
                $this->setGroup($groupNid,$content['town']);
            } else {
                Log::Write("    ERROR: Node ".$this->getSyntheticOldId()." has an unknown group named '".$content['town']."'");
            }
            // set the free-text tags
            $this->addTags($content['tags']);
        }
        
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
        $uniquePictureName = $timestamp."_".$picture;   // make sure to not overwrite a diff post's image (!)
        $destPath = $destDir.$uniquePictureName;
        // set the metadata on the node
        if(REALLY_IMPORT){
            // see http://drupal.org/node/458778#comment-1653696 (copy them to temp dir)
            $anonymousUser = new stdClass();
            $anonymousUser->uid = DRUPAL_ANONYMOUS_UID;
            $tempDir = sys_get_temp_dir();
            $tempPath = $tempDir."/".$uniquePictureName;
            $copyToTempWorked = copy($srcPath,$tempPath);   // TODO: handle this copy failing for some reason
            if(!$copyToTempWorked){
                Log::Write("    ERROR: couldn't copy image from $srcPath!");
                exit();            
            }
            switchToDrupalPath();
            $fileNode = field_file_save_file($tempPath,array(),DRUPAL_IMAGE_SUBDIR,$anonymousUser);
            if($fileNode==0){
                Log::Write("    ERROR: unable to save related file from $tempPath");
                exit();
            } else {
                Log::Write("    saved related file to $uniquePictureName");
            }
            // TODO: handle this file not getting created
            switchToScriptPath();
        } else {
            $fileNode = new stdClass();
            $fileNode->fid = Node::RandomNid();
        }
        $this->node->field_image = array(0=>$fileNode);
    }

    private function setLocation($lat,$lon,$address){
        $locData = array("latitude"=>$lat,"longitude"=>$lon,"name"=>$address);
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