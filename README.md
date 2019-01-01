![HTML Mail Compiler](https://raw.githubusercontent.com/millsoft/htmlmailcompiler/master/example/src/img/htmlmailcompiler-logo.png)

# HTML Mail Compiler (hmc)

HTML Mail Compiler is a tool that will inject inline CSS styles into your html template and output compiled html as a file. It uses [emogrifier](https://github.com/jjriv/emogrifier) library for the merge process.

## Motivation

Creating HTML emails can be a nightmare. It works by using inline CSS inside HTML. Then if you need to change something you need to change all your inline CSS. With this script it will do the work for you. You have 2 files: your html template (a php file) and a css file. You can create CSS styles for classes, IDs or just HTML tags. This script will generate one or multiple HTML templates with inlined CSS for you automatically.

## Requirements

- PHP from 5.4 or better
- Composer

## Installation

`composer global require millsoft/htmlmailcompiler`

After the installation a new global command is available: `hmc` (= html mail compiler). Open your terminal and type it in your console to check if it works. If not, check if your global composer path is defined in your PATH envirnonment variable.

## How to use it?

To create a HTML template you normally used to create a new HTML file and work on it. Now you create a PHP file with your usual stuff in it and put the `link` Tag to include your CSS file. You also need to put a `compile.json` file into your folder. In that file you specify how the tool should generate the final HTML template.

Please see the example folder for an example. The source files are stored in `example/src` and after generation your files are stored in `example/dist`

## Example Usage

Terminal:
`hmc .`

This command will load `compile.json` file in the current directory and do whatever is specified in that file. You can also specify a different directory, eg.: `hmc ~/abc/def`

## Configuration file

You need to create a `compile.json` file first (or look at the [example file](example/src/compile.json) ). Put it in your folder where you create your HTML E-Mail. The file can look like this:

| key           | value                                                                                                                                                                   |
| ------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| template_file | Your html template. Should be a php file, eg. "template.php" The file is a normal HTML file with linked CSS file                                                        |
| css_file      | Name of your CSS file. eg. "style.css". This can be one or more files. Use array form when using multiple css files                                                     |
| start_nr      | Start the index from this number (default: 0)                                                                                                                           |
| minify        | Minify the html file (default: false)                                                                                                                                   |
| generate      | an array of files to generate. The index of the array will be passed in the generation process and is available in the \$nr variable                                    |
| placeholders  | (object) - You can use placeholders which will be replaced with the values in this file.                                                                                |
| output_dir    | Where should be the generated files be stored? eg. "../dist". If not specified, the source directory will be used as target directory.                                  |
| zip           | (object) settings for the ZIP file generation. Key "filename" is used for the output filename. Key "files" is an array of which files should be stored in the ZIP file. |

## Parameters

Usually the only parameter you need is the path, eg `hmc .`.

There are also some additional parameters you can enter:  
`--config=compile2.json` : Uses a different config file (default is compile.json)  
`--path=another_path` : specify another directory where your files are.

## Contributors

You want to extend it? project is open for pull requests :)

## License

Open Source License.  
Developed by MilMike // 29.11.2017 (https://www.milmike.com)
Last Update: 01.01.2018 (Happy New Year!)

## Further Reading

More details about this project on my blog: https://www.milmike.com/how-to-create-html-e-mail-templates
