![HTML Mail Compiler](https://raw.githubusercontent.com/millsoft/htmlmailcompiler/master/example/src/img/htmlmailcompiler-logo.png)


# HTML Mail Compiler

HTML Mail Compiler is a tool that will inject inline CSS styles into your html template and output compiled html as a file. It uses [emogrifier](https://github.com/jjriv/emogrifier) library for the merge process.

## How to use it?
To create a HTML template you normally used to create a new HTML file and work on it. Now you create a PHP file with your usual stuff in it and put the `link` Tag to include your CSS file. You also need to put a `compile.json` file into your folder. In that file you specify how the tool should generate the final HTML template.

Please see the example folder for an example. The source files are stored in `example/src` and after generation your files are stored in `example/dist`

## Code Example

It can either be called in the browser or in terminal:

Terminal:
`php compiler.php path_to_source_files`

Or using a Browser:
`compiler.php?path=path_to_source_files`


## Motivation

Creating HTML emails can be a nightmare. It works by using inline CSS inside HTML. Then if you need to change something you need to change all your inline CSS. With this script it will do the work for you. You have 2 files: your html template (a php file) and a css file. You can create CSS styles for  classes, IDs or just HTML tags. This script will generate one or multiple HTML templates with inlined CSS for you automatically.

## Requirements

* PHP from 5.4 or better
* Composer

## Installation

use `composer install` to install all dependencies.


## Contributors

You want to extend it? project is open for pull requests :)

## License

Open Source License.  
Developed by Michael Milawski // 29.11.2017