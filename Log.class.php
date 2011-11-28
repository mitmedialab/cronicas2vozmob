<?php

$myLogInstance = null;

/**
 * Simple logging facility
 */
class Log {

    public $logFile;
    public $fileName;
    public $echo = true;

    public static function Initialize($dir, $timestampFileName) {
        global $myLogInstance;
        $myLogInstance = new Log($dir,$timestampFileName);
        echo("  (logging to $myLogInstance->fileName)\n");
    }

    public static function Write($str) {
        global $myLogInstance;
        $myLogInstance->writeLine($str."\n");
    }
    
    public function writeLine($str){
        fwrite($this>logFile, $str);
        if($this->echo) echo($str);
    }
    
    public function Log($dir, $timestampFileName) {
        $logFileName = "import";
        $postpend = ($timestampFileName) ? "-".time() : "";
        $this->fileName = $dir.$logFileName."$postpend.log";
        $this->logFile = fopen($$this->fileName,'w');
    }

}

?>