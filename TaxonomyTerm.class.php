<?php

/**
 * Manage a Drupal TaxonomyTerm
 */
class TaxonomyTerm {

    const NO_PARENT = 0;
    const VOZMOB_VOCABULARY_ID = 1;
    
    public $name;
    public $tid;
    public $vid;    //vocabulary id

    public function TaxonomyTerm($id,$vid,$text){
        $this->tid = $id;
        $this->vid = $vid;
        $this->name = $text;
    }

    public function __toString(){
        return $this->name." / ".$this->tid;
    }
    
    /**
     * Return a TaxonomyTerm based on the name passed in, return null if the
     * name doesn't exist as a term in the DB
     */    
    public static function FindByName($tag){
        $found = null;
        $sql = "SELECT tid,vid,name FROM {term_data} WHERE name='%s'";
        $results = db_query($sql,$tag);
        $found_obj = db_fetch_object($results);
        if($found_obj==null) return null;
        return new TaxonomyTerm($found_obj->tid,$found_obj->vid,$found_obj->name);        
    }

    public static function FindOrCreateByName($tag){
        $existingTerm = TaxonomyTerm::FindByName($tag);
        if($existingTerm){
            return $existingTerm;
        }
        return TaxonomyTerm::Create($tag);
    }
    
    public static function Create($tag,$vocabulary=null){
        switchToDrupalPath();
        if($vocabulary==null){
            $vocabulary = TaxonomyTerm::VOZMOB_VOCABULARY_ID;
        }
        $term = array(
            "vid" => $vocabulary,
            "name" => $tag,
        );
        $toReturn = null;
        if(REALLY_IMPORT){
            taxonomy_save_term($term);
            $toReturn = TaxonomyTerm::FindByName($tag);
        } else {
            $toReturn = new TaxonomyTerm(Node::RandomNid(),$vocabulary,$tag);
        }
        switchToScriptPath();
        return $toReturn;        
    }

}
?>