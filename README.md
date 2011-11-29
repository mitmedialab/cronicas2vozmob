Crónicas de Héroes Import Script
================================

This is a simple script that can import a dump of content from a Crónicas de Héroes site from CakePHP into the VozMob-Drupal codebase.


Setup
-----

You need to make sure that you set the `DRUPAL_BASE` variable correctly in the setup.inc.php file.  This should be the relative path to the vozmob Drupal folder.

Also be sure you make all the Organic Groups before running the import script, each one should be named the same as the "Gui.CityName" variable in the Cronicas CakePHP config file.

Instructions
------------

This assumes you have a folder created by the `export` shell task in the old CakePHP codebase.  Once you have that folder, you can run something like this to import it:

`php import.php ../path/to/export/folder/with/data/csv/in/it`

