<?php

/**
 *    HTML E-Mail Compiler
 *    Entry Script - this will be always called first.
 *    by Michael Milawski - Last Update: 01.01.2019
 *   https://github.com/millsoft/htmlmailcompiler
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$app_version = "1.1.0";

$autoload_file        = __DIR__ . "/../vendor/autoload.php";
$autoload_file_global = __DIR__ . "/../../../autoload.php";

if (file_exists($autoload_file_global)) {
    //this script is installed in the composer installation
    require_once $autoload_file_global;
} else {
    if (!file_exists($autoload_file)) {
        throw new Exception("Composer autoload not found.");
    }
    require_once $autoload_file;

}

$climate = new \League\CLImate\CLImate;

$head = <<<HHH
***************************************
Html Mail Compiler V $app_version
(c) 2017 - 2019 by Michael Milawski
***************************************

HHH;

$climate->bold()->yellow($head);

if (count($argv) < 2) {

    //No params were entered!
    $out = <<<INFO

Generates a HTML mail with inline CSS.

Call:
    hmc [path]

Example:
    hmc .

    The path should contain compile.json file with all needed settings for compilation.
    You can also specify a different json file by using the --config option.
    Please see the documentation for more information.


INFO;

    $climate->out($out);

    exit(1);
}

$lopts = array(
    "config::",
    "path::",
);
$options = getopt("o::", $lopts);

//Set defaults:
$config_file = null;

if (empty($options)) {
    $path = $argv[1];
} else {
    $path        = isset($options['path']) ? $options['path'] : getcwd();
    $config_file = isset($options['config']) ? $options['config'] : null;
}

if ($config_file !== null) {
//set a different config file
    HtmlCompiler::$SettingsFile = $config_file;
}

$exit_code = 0;
$done      = HtmlCompiler::run($path);
if ($done === false) {
    $exit_code = 1;
}
exit($exit_code = 1);
