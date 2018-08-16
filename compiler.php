<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 *    HTML E-Mail Compiler
 *    by Michael Milawski - Last Update: 16.08.2018
 *   https://github.com/millsoft/htmlmailcompiler
 */

$autoload_file = __DIR__ . "/vendor/autoload.php";
if (!file_exists($autoload_file)) {
    die("Composer autoload file not found. Please execute 'composer install' to install all dependencies.");
}

require_once("vendor/autoload.php");
$app_version = "0.0.4";

class HtmlCompiler
{

    public static $SettingsFile = "compile.json";
    static $settings = array();
	
	
	//Are we in CLI or in web?
	public static function isCli(){
		return php_sapi_name() === 'cli' ? true : false;
	}
	
	
    public static function run ($path = '')
    {
        //check if the path exists and we can access it:

        if (empty($path)) {
            //no path was specified, try to read the current dir:
            $path = '.';
        }
        if (!file_exists($path)) {
            die("The path seems to be invalid: $path");
        }

		
		//Switch to the path
		chdir($path);
		
        //does the settings file exist?
        //$settingsFilePath = $path . "/" . self::$SettingsFile;
		$settingsFilePath = self::$SettingsFile;
		
        if (!file_exists($settingsFilePath)) {
            die("Settings file " . self::$SettingsFile . " not found in " . $path);
        }

        $settings = json_decode(file_get_contents($settingsFilePath), true);
        self::$settings = $settings;

		/*
        $template_file = $path . "/" . $settings[ 'template_file' ];
        $css_file = $path . "/" . $settings[ 'css_file' ];
		*/

		$template_file = $settings[ 'template_file' ];
        $css_file = $settings[ 'css_file' ];
		
        if (isset($settings[ 'output_dir' ])) {
            $outputDir = $path . "/" . $settings[ 'output_dir' ];
            if (!file_exists($outputDir)) {
                mkdir($outputDir);
            }
        } else {
            $outputDir = $path;
        }

        if ($outputDir != $path) {
            //copy static files to dist dir:
            self::copyFilesToDist($settings[ 'zip' ][ 'files' ], $path, $outputDir);
        }


        /**
         * Include own helper php file which will be always included and is available in your template.php file
         */
        if (isset($settings[ 'helper' ])) {
            //include helper php:
            $helper_php = $path . "/" . $settings[ 'helper' ];

            if (file_exists($helper_php)) {
                require_once($helper_php);
            }
        }


        $nr = isset($settings[ 'start_nr' ]) ? $settings[ 'start_nr' ] : 0;

        foreach ($settings[ 'generate' ] as $n => $output_file) {
            echo "Compiling HTML template $output_file (nr: $nr)\n";
            //$output_file = $outputDir . "/" . $output_file;
			$output_file = $output_file;
			
            self::compile($template_file, $css_file, $output_file, $nr);
            $nr++;
        }

		$outputDir = '.';
        if (isset($settings[ 'zip' ])) {
            self::zip($settings[ 'zip' ], $outputDir);
        }


        echo("DONE! :-) - " . date("Y-m-d H:i:s"));

    }


    /**
     * Copy all files from Source to Dist
     *
     * @param $files     - array with file names
     * @param $sourceDir - copy from where?
     * @param $outDir    - copy to where?
     */
    private static function copyFilesToDist ($files, $sourceDir, $outDir)
    {

        echo "Copying Source Files to $outDir\n";

        foreach ($files as $file) {
            if (strpos($file, '/') !== false) {
                $ex = explode("/", $file);
                $dir = $ex[ 0 ];
                $newPath = $outDir . "/" . $dir;
                $outFile = $outDir . "/" . $file;
                $sourceFile = $sourceDir . "/" . $file;

                if (!file_exists($newPath)) {
                    mkdir($newPath);
                }

                if (file_exists($sourceFile)) {
                    copy($sourceFile, $outFile);
                }


            }
        }

    }

    /**
     * Compile
     *
     * @param $html_file - html template
     * @param $css_file  - which CSS file to use?
     * @param $save_as   - save the generated file as..
     * @param int $nr    - this is available globally, you can use it in your template.php to generate various version
     *                   of your template.
     */
    public static function compile ($html_file, $css_file, $save_as, $nr = 1)
    {
        $emogrifier = new \Pelago\Emogrifier();
        $errors = array();

        if (!file_exists($html_file)) {
            $errors[] = $html_file . " not found!";
        }

        if(strpos($css_file, ',') !== false){
            //user provided multiple CSS files separated by comma
            $cf = explode(",", $css_file);
            foreach($cf as $_current_css_file){
                $all_css_files[] = trim($_current_css_file);
            }

        }else{
            $all_css_files = [$css_file];
        }

        foreach($all_css_files as $_cssfile){
            if (!file_exists($_cssfile)) {
                $errors[] = $_cssfile . " not found!";
            }
        }

        if (!empty($errors)) {
            die(implode("\n", $errors));
        }

        //include the php file:
        ob_start();
        include $html_file;
        $html_file_content = ob_get_clean();

        //$html_file_content = file_get_contents($html_file);
        $css_file_content = '';
        foreach($all_css_files as $css_file){
            $css_file_content .= file_get_contents($css_file) . "\n";
        }

        $emogrifier->setHtml($html_file_content);
        $emogrifier->setCss($css_file_content);

        $mergedHtml = $emogrifier->emogrify();

        //remove linked css:
        $re = '/<link.*>/';
        $mergedHtml = preg_replace($re, '', $mergedHtml);
        $mergedHtml = str_ireplace("<!-- HEAD_INFO -->", self::getMetaInfo(), $mergedHtml);

        $mergedHtml = self::processPlaceholders($mergedHtml);


        file_put_contents($save_as, $mergedHtml);
    }

    /**
     * Replace the HEAD_INFO comment in HTML with generated time / date
     * @return string
     */
    private static function getMetaInfo ()
    {
        $timestamp = date("Y-m-d H:i");
        $info = <<<INFO
        <!-- Last update: $timestamp  -->
INFO;

        return $info;
    }


    /**
     * Process custom Placeholders
     * @source string - html template
     * @return string
     */
    private static function processPlaceholders ($source)
    {
        $settings = self::$settings;

        if(!isset($settings['placeholders'])){
            //no placeholders in json file found, return the unprocessed source code
            return $source;
        }

        $placeholders = $settings['placeholders'];
        foreach($placeholders as $placeholder => $replacement){
            $source = str_replace($placeholder, $replacement, $source);
        }

        return $source;
    }




    /**
     * Generate a ZIP file for all your files marked for export
     *
     * @param $settings - complete settings array (your compile.json file)
     * @param $path     - where should we save the ZIP file?
     */
    private static function zip ($settings, $path)
    {
        echo "Generating ZIP file...\n";

        $zip_file = $path . '/' . $settings[ 'filename' ];

        $path = str_replace('\\', '/', $path);

        //generate file list:
        $files = array();
        foreach ($settings[ 'files' ] as $file) {
            $f = $path . "/" . $file;
            if (file_exists($f)) {
                $files[] = $f;
            } else {
                echo "Warning! File $f does not exist\n";
            }

        }

        self::create_zip($files, $zip_file, true, $path);
    }


    private static function create_zip ($files = array(), $destination = '', $overwrite = false, $path)
    {
        //if the zip file already exists and overwrite is false, return false
        if (file_exists($destination) && !$overwrite) {
            return false;
        }
        //vars
        $valid_files = array();
        //if files were passed in...
        if (is_array($files)) {
            //cycle through each file
            foreach ($files as $file) {
                //make sure the file exists
                if (file_exists($file)) {
                    $valid_files[] = $file;
                }
            }
        }
        //if we have good files...
        if (count($valid_files)) {
            //create the archive
            $zip = new ZipArchive();


            //remove the zip file if already exists:
            if (file_exists($destination)) {
                unlink($destination);
            }


            $error = $zip->open($destination, ZipArchive::CREATE);
            if ($error !== true) {
                die("ERROR creating the zip file :( " . $error);
                //return false;
            }

            //add the files
            $nr = 0;
            foreach ($valid_files as $file) {
                $nr++;
                $save_as = substr($file, strlen($path) + 1);
                $zip->addFile($file, $save_as);
            }
            $zip->close();

            //check to make sure the file exists
            return file_exists($destination);
        } else {
            return false;
        }
    }
}


if(!HtmlCompiler::isCli()){
	echo "<pre>";
}

$head = "Html Compiler V " . $app_version . " by Michael Milawski";

if (php_sapi_name() === 'cli') {
    //Script aufgerufen in cli:
    if (count($argv) < 2) {
        //keine Parameter angegeben.
        echo <<<INFO
--------------------------------------------
$head
Generates a HTML with inline CSS.

Call:
php compiler.php path

The path should contain the compile.json file with all the needed settings for compilation.
--------------------------------------------

INFO;
        die();
    } else {

        $lopts = array(
            "config::",
            "path::"
        );
        $options = getopt("o::", $lopts);

        if(empty($options)){
            $path = $argv[ 1 ];
        }else{
            $path = isset($options['path']) ? $options['path'] : getcwd();
            $config_file = isset($options['config']) ? $options['config'] : null;
        }

        if($config_file !== null){
            //set a different config file
            HtmlCompiler::$SettingsFile = $config_file;
        }

        HtmlCompiler::run($path);
    }

} else {
    //Script called in browser
    if (isset($_GET[ 'path' ])) {
        HtmlCompiler::run($_GET[ 'path' ]);
    } else {
        die("missing parameter: _GET['path']");
    }


}


if(!HtmlCompiler::isCli()){
	echo "</pre>";
}