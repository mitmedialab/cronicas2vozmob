<?php

define("DRUPAL_NODE_OLD_ID_FIELD","field_old_id");  // TODO: ???

/**
 * Based class to wrap operations scripts want to perform on drupal nodes.
 */
class Node {

    public $node;

    public function __contruct() {
    }

    /**
     * Set the old Nid on the field, useful for historical purposes
     */
    public function setOldNid($oldNid){
        $this->node->{DRUPAL_NODE_OLD_ID_FIELD} = array(
                0=>array('value'=>$oldNid)
            );
    }
    public function getOldNid($oldNid){
        return $this->node->{DRUPAL_NODE_OLD_ID_FIELD}[0]['value'];
    }

    public function __toString(){
        return $this->node->title." (".$this->node->nid.")";
    }

    public function setType($type){
        $this->node->type = $type;
    }
    
    public function setTitle($title){
        $this->node->title = $title;
    }
    
    public function setBody($body){
        $this->node->body = $body;
    }
    
    /**
     * Need to set the "date" property, _not_ created
     * @see http://api.drupal.org/api/drupal/modules--node--node.module/function/node_submit/6
     */
    public function setCreatedTime($timestamp){
        $this->node->date = date(DATE_RFC822,$timestamp);
    }
    
    public function setAuthorUserId($userId){
        $this->node->uid = $userId;
    }

    public function setStatus($published){
        $this->node->status = $published;
    }

    /**
     * What is this node's new nid?
     * @return     new nid if found, null if not in db
     */
    public static function NidFromOldId($oldId){
        $found = null;
        $oldIdCckTableName = "content_".DRUPAL_NODE_OLD_ID_FIELD;
        $oldIdCckFieldName = DRUPAL_NODE_OLD_ID_FIELD."_value";
        $sql = "SELECT nid FROM {".$oldIdCckTableName."} WHERE ".$oldIdCckFieldName.".='%d'";
        $results = db_query($sql,$oldId);
        $foundObj = db_fetch_object($results);
        $found = $foundObj->nid;
        unset($foundObj);        
        unset($results);
        return $found;
    }

    /**
     * Set the specified field to an empty string
     *     (doesn't save)
     */
    protected function clearNodeField($nodeFieldName){
        $this->node->$nodeFieldName = "";
    }

    /**
     * Set the specified field to an empty array
     *     (doesn't save)
     * @return true if there were some things removed, false if there weren't
     */
    protected function emptyNodeField($nodeFieldName){
        $wereSomeThere = count($this->node->{$nodeFieldName});
        $this->node->{$nodeFieldName} = array();
        return $wereSomeThere;
    }
    /**
     * Save the node in memory to the db
     * @return        true if saved, false if not saved (true if faked out)
     */
    public function saveNode(){
        if(REALLY_IMPORT){
            $this->node = node_submit($this->node);
            if ($this->node->validated) {
                node_save($this->node);
                $this->saveGroups();
                return true;
            }
            return false;
        }
        // if faked out
        if($this->node->nid==null) $this->node->nid=Node::RandomNid();
        return true;
    }

    /**
     * Save the associations between this node and any organic groups.  This is meant to be called
     * after the saveNode method.
     * WTF: not sure why this doesn't happen automatically via og's nodeapi insert hook...
     * @see http://api.lullabot.com/og_save_ancestry
     */
    private function saveGroups(){
        og_save_ancestry($this->node);
    }
    
    public static function RandomNid(){
        return rand(-1000,-1);
    }
    
    /**
     * Look up the id of a node based on it's title and type
     */
    public static function FindByTitleAndType($title, $type){
        $nid = null;
        $sql = "SELECT nid FROM {node} WHERE title='%s' and type='%s'";
        $results = db_query($sql,$title,$type);
        $node = db_fetch_object($results);
        if($node){
            $nid = $node->nid;
        }
        return $nid;
    }
    
    /** 
     * Do a quick query to see if this node is of the right type
     */
     public static function IsOfType($nid, $type){
        $toReturn = false;
        $sql = "SELECT nid FROM {node} WHERE nid='%d' and type='%s'";
        $results = db_query($sql,$nid,$type);
        $foundObj = db_fetch_object($results);
        //print_r($foundObj);exit();
        $toReturn = $foundObj!=null;
        return $toReturn;
     }

    /**
     * Load up and initialize a new lecture from it's nid
     */
    public static function FromNid($nid){
        $node = new Node();
        $node->node = node_load($nid);
        return $node;
    }
    
    /**
     * Attempt to fix the fact that recursive references leak memory:
     *     http://bugs.php.net/bug.php?id=33595
     * Call this before unsetting!
     */
    public function __destruct(){
        unset($this->node);
    }
    
}

?>