<?php

/**
 * Simple log of which nodes have been imported and to what node ids.
 */
class ImportHistory {

    const FILENAME = "import-history.bin";

    private $oldToNew;

    public function ImportHistory($reset=false){
        if(!$reset && file_exists(ImportHistory::FILENAME)){
            $this->oldToNew = unserialize(file_get_contents(ImportHistory::FILENAME));
            Log::Write("  Loading history from ".ImportHistory::FILENAME." (".count($this->oldToNew)." existing records)");
        } else {
            $this->oldToNew = array();
            Log::Write("  Created new history file ".ImportHistory::FILENAME);
        }
    }

    public function writeToFile(){
        if($f = fopen(ImportHistory::FILENAME,"w")) { 
            if(fwrite($f,serialize($this->oldToNew))) { 
                @fclose($f); 
                Log::Write("  Wrote history to ".ImportHistory::FILENAME);
            } else {
                Log::Write("  ERROR: Could not write to ".ImportHistory::FILENAME." file!"); 
                return false;
            }
            return true;
        }
        return false;
    }

    public function put($oldSyntheticKey, $newNid){
        $this->oldToNew[$oldSyntheticKey] = $newNid;
    }

    public function alreadyImported($oldSyntheticKey){
        return array_key_exists($oldSyntheticKey,$this->oldToNew);
    }

}

?>