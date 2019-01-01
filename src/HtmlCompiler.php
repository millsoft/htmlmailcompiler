<?php

/**
 *    HTML E-Mail Compiler
 *    by Michael Milawski - Last Update: 01.01.2019
 *   https://github.com/millsoft/htmlmailcompiler
 */

class HtmlCompiler
{

    public static $SettingsFile = "compile.json";
    static $settings            = array();

    //For output we use climate lib:
    static $climate = null;

    public static function run($path = '')
    {

        if (empty($path)) {
            //no path was specified, try to read the current dir:
            $path = '.';
        }

        //check if the path exists and we can access it:
        if (!file_exists($path)) {
            self::writeLog("The path seems to be invalid: $path", "error");
            return false;
        }

        //Switch to the path
        chdir($path);

        //does the settings file exist?
        $settingsFilePath = self::$SettingsFile;

        if (!file_exists($settingsFilePath)) {
            die("Settings file " . self::$SettingsFile . " not found in " . $path);
        }

        $settings       = json_decode(file_get_contents($settingsFilePath), true);
        self::$settings = $settings;

        $template_file = $settings['template_file'];
        $css_file      = $settings['css_file'];

        if (isset($settings['output_dir'])) {
            $outputDir = $path . "/" . $settings['output_dir'];
            if (!file_exists($outputDir)) {
                $created = mkdir($outputDir);
                if (!$created) {
                    self::writeLog("Could not create output dir", "error");
                    return false;
                }
            }
        } else {
            $outputDir = $path;
        }

        if ($outputDir != $path) {
            //copy static files to dist dir:
            self::copyFilesToDist($settings['zip']['files'], $path, $outputDir);
        }

        //include own helper php file which will be always included and is available in your template . php file
        if (isset($settings['helper'])) {
            //include helper php:
            $helper_php = $path . "/" . $settings['helper'];

            if (file_exists($helper_php)) {
                require_once $helper_php;
            }
        }

        $nr     = isset($settings['start_nr']) ? $settings['start_nr'] : 0;
        $minify = isset($settings['minify']) ? $settings['minify'] : false;

        foreach ($settings['generate'] as $n => $output_file) {
            self::writeLog("Compiling HTML template $output_file (nr: $nr)");

            $output_file = $outputDir . "/" . $output_file;

            //prepare all params for the current index ($nr)
            $params = array(
                "html_file" => $template_file,
                "css_file"  => $css_file,
                "save_as"   => $output_file,
                "nr"        => $nr,
                "minify"    => $minify,
            );

            $compiled = self::compile($params);

            if ($compiled === false) {
                //Something went wrong, abort, abort..
                return false;
            }
            $nr++;
        }

        if (isset($settings['zip'])) {
            self::zip($settings['zip'], $outputDir);
        }

        self::writeLog("DONE @" . date("Y-m-d H:i:s"), "green");
        return true;
    }

    /**
     * Minify HTML code
     * @param  string $html input html source code
     * @return string       minified html
     */
    public static function minify($html)
    {
        $search = array(
            '/\>[^\S ]+/s', // strip whitespaces after tags, except space
            '/[^\S ]+\</s', // strip whitespaces before tags, except space
            '/(\s)+/s', // shorten multiple whitespace sequences
            '/<!--(.|\s)*?-->/', // Remove HTML comments
        );

        $replace = array(
            '>',
            '<',
            '\\1',
            '',
        );

        $html = preg_replace($search, $replace, $html);

        return $html;

    }

    /**
     * Copy all files from Source to Dist
     *
     * @param $files     - array with file names
     * @param $sourceDir - copy from where?
     * @param $outDir    - copy to where?
     */
    private static function copyFilesToDist($files, $sourceDir, $outDir)
    {

        self::writeLog("Copying Source Files to $outDir");

        foreach ($files as $file) {
            if (strpos($file, '/') !== false) {
                $ex         = explode("/", $file);
                $dir        = $ex[0];
                $newPath    = $outDir . "/" . $dir;
                $outFile    = $outDir . "/" . $file;
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
     * @param $params - array all possible parameters
     * @param $css_file  - which CSS file to use?
     * @param $save_as   - save the generated file as..
     * @param int $nr    - this is available globally, you can use it in your template.php to generate various version
     *                   of your template.
     * @return bool
     */
    public static function compile($params)
    {
        extract($params);

        $emogrifier = new \Pelago\Emogrifier();
        $errors     = array();

        if (!file_exists($html_file)) {
            $errors[] = $html_file . " not found!";
        }

        if (is_array($css_file)) {
            $all_css_files = $css_file;
        } else {
            //Deprecated: multiple css files in one string, separated by comma:
            if (strpos($css_file, ',') !== false) {
                //user provided multiple CSS files separated by comma
                $cf = explode(",", $css_file);
                foreach ($cf as $_current_css_file) {
                    $all_css_files[] = trim($_current_css_file);
                }

            } else {
                $all_css_files = [$css_file];
            }
        }

        foreach ($all_css_files as $_cssfile) {
            if (!file_exists($_cssfile)) {
                $errors[] = $_cssfile . " not found!";
            }
        }

        if (!empty($errors)) {
            self::writeLog(implode("\n", $errors), "error");
            return false;
        }

        //include the php file:
        ob_start();
        include $html_file;
        $html_file_content = ob_get_clean();

        $css_file_content = '';
        foreach ($all_css_files as $css_file) {
            $css_file_content .= file_get_contents($css_file) . "\n";
        }

        $emogrifier->setHtml($html_file_content);
        $emogrifier->setCss($css_file_content);

        $mergedHtml = $emogrifier->emogrify();

        //remove linked css:
        $re         = '/<link.*>/';
        $mergedHtml = preg_replace($re, '', $mergedHtml);
        $mergedHtml = str_ireplace("<!-- HEAD_INFO -->", self::getMetaInfo(), $mergedHtml);

        $head_css_file = "css/head.css";
        if (file_exists($head_css_file)) {
            $head_css_content = file_get_contents($head_css_file) . "\n";
            $head_css         = "<style>\n{$head_css_content}\n</style>";
            $mergedHtml       = str_ireplace("<!-- HEAD_CSS -->", $head_css, $mergedHtml);

        }

        $mergedHtml = self::processPlaceholders($mergedHtml);

        //Minify
        if (isset($minify) && $minify === true) {
            $mergedHtml = self::minify($mergedHtml);
        }

        file_put_contents($save_as, $mergedHtml);
        return true;
    }

    /**
     * Replace the HEAD_INFO comment in HTML with generated time / date
     * @return string
     */
    private static function getMetaInfo()
    {
        $timestamp = date("Y-m-d H:i");
        $info      = <<<INFO
        <!-- Last update: $timestamp  -->
INFO;

        return $info;
    }

    /**
     * Process custom Placeholders
     * @source string - html template
     * @return string
     */
    private static function processPlaceholders($source)
    {
        $settings = self::$settings;

        if (!isset($settings['placeholders'])) {
            //no placeholders in json file found, return the unprocessed source code
            return $source;
        }

        $placeholders = $settings['placeholders'];
        foreach ($placeholders as $placeholder => $replacement) {
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
    private static function zip($settings, $path)
    {
        self::writeLog("Generating ZIP file...");

        $zip_file = $path . '/' . $settings['filename'];

        $path = str_replace('\\', '/', $path);

        //generate file list:
        $files = array();
        foreach ($settings['files'] as $file) {
            $f = $path . "/" . $file;
            if (file_exists($f)) {
                $files[] = $f;
            } else {
                self::writeLog("Warning! File $f does not exist", "error");
            }

        }

        self::create_zip($files, $zip_file, true, $path);
    }

    private static function create_zip($files = array(), $destination = '', $overwrite = false, $path)
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

    /**
     * Write output to terminal
     *
     * @param string $txt - the message that should be shown
     * @param string $type (default $climate->out())
     * @return void
     */
    public static function writeLog($txt, $type = 'out')
    {
        if (self::$climate === null) {
            self::$climate = new \League\CLImate\CLImate;
        }

        self::$climate->$type($txt);
    }

}
