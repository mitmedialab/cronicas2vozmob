<?php

// This file includes all the relevant drupal bootstrap files that we need to import data

require_once("Log.class.php");
require_once("Node.class.php");
require_once("Cronica.class.php");
require_once("TaxonomyTerm.class.php");
require_once("ImportHistory.class.php");

define("DRUPAL_ANONYMOUS_UID",0);
define("DRUPAL_ADMIN_UID",1);

$_SERVER['REMOTE_ADDR'] = "localhost";
$_SERVER['REQUEST_METHOD'] = "GET";
//$_SERVER['SERVER_SOFTWARE'] = "fake";
define("DRUPAL_BASE","../cronicas-vozmob/html/");

// initialize Drupal bootstrap
$importScriptPath = getcwd(); // global
switchToDrupalPath();
require_once("./includes/bootstrap.inc");
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
switchToScriptPath();

// Logging in as admin user
global $user;
$user->uid = DRUPAL_ADMIN_UID;

// setup logging facility (use Log::Write to push msgs)
Log::Initialize("logs/", false);

function switchToDrupalPath(){
    chdir(DRUPAL_BASE);   
}
function switchToScriptPath(){
    global $importScriptPath;
    chdir($importScriptPath);
}

?>