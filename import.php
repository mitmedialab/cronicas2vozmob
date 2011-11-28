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

// load up the content to import
$pathToContent = $argv[1];
echo("Importing content from $pathToContent\n");
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
echo("Done! $row rows imported\n");

// This is the function that actually imports things into Drupal
// return true if imported, false if not
function importContent($content,$contentImageDir){

    Log::Write("  Importing ".$content['town']." Cronica id ".$content['id']);
    $cronica = Cronica::FromArray($content,$contentImageDir);

    // check if it is already imported (compound id for uniqueness made up of town and old id)
    if($cronica->exists()){
        Log::Write("    ".$cronica->getSyntheticOldId()." already exists - skipping");
        return false; 
    }
    
    $cronica->copyImages();
    $saved = $cronica->saveNode();

    if(!$saved) {
        Log::Write("    ERROR: couldn't save the node for some reason (validate failed?)");
    }
    
}

?>