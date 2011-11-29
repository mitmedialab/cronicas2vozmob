<?php

define("REALLY_IMPORT",true);

require_once('setup.inc.php');

// This file let you import the content of a CakePHP-based Crónicas de Héroes 
// website export.  I could make this more of an OO, tested thing, but this is a one-off
// process so a script-like approach will probably suffice.

if($argc!=2){
    echo("ERROR: You must provide 1 argument - the path to the exported content\n");
    exit();
}

// Feedback that we started up
Log::Write("");
Log::Write("-------------------- Initializing -----------------------------------");
Log::Write("  running from $importScriptPath");

$history = new ImportHistory();

Log::Write("");
Log::Write("-------------------- Importing -----------------------------------");
// load up the content to import
$pathToContent = $argv[1];
Log::Write("  Importing content from $pathToContent\n");
$contentCsvFile = $pathToContent."/data.csv";
$contentImageDir = $pathToContent."/images/";

// walk the content, importing each one
$colNames = array();
$row = 1;
$worked = 0;
$failed = 0;
if (($handle = fopen($contentCsvFile, "r")) !== FALSE) {

    // iterate over rows
    while (($data = fgetcsv($handle)) !== FALSE) {
        // skip first row (column headers)
        if($row==1) {
            $colNames = $data;
            $row++;
            continue;   // skip column headers
        }
        // data sanity checks/cleanup
        if(count($data)!=count($colNames)){
            print_r($colNames);
            echo("ERROR! Row $row has the wrong number of columns (".count($data).")");
            print_r($data);
            exit();
        }
        $content = array_combine($colNames,$data);
        //print_r($content); exit();
        $importedOk = importContent($content,$contentImageDir);
        if ($importedOk) $worked++;
        else $failed++;
        $row++;
    }
    fclose($handle);
}

Log::Write("");
Log::Write("-------------------- Done  -----------------------------------");
Log::Write("  $worked rows imported, $failed rows failed");
$history->writeToFile();
Log::Write("");

// This is the function that actually imports things into Drupal
// return true if imported, false if not
function importContent($content,$contentImageDir){
    global $history;

    $syntheticOldId = Cronica::MakeSyntheticOldId($content['town'], $content['id']);
    Log::Write("  Importing Cronica ".$syntheticOldId);
    
    // check if it is already imported (compound id for uniqueness made up of town and old id)
    if($history->alreadyImported($syntheticOldId)){
        Log::Write("    ".$syntheticOldId." already exists - skipping");
        return false; 
    }

    $cronica = Cronica::FromArray($content,$contentImageDir);

    //print_r($cronica);exit();
    
    $cronica->copyImages();
    $saved = $cronica->saveNode();

    if(!$saved) {
        Log::Write("    ERROR: couldn't save the node for some reason (validate failed?)");
    } else {
        Log::Write("    imported to node id ".$cronica->node->nid);
        $history->put($cronica->getSyntheticOldId(), $cronica->node->nid);
    }

    return $saved;
    
}

?>