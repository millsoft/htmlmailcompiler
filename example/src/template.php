<!doctype html public "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=1000,  initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting"/>

    <title>Example</title>
    <link rel="stylesheet" href="style.css">
    
    <!-- DEVELOPED_BY -->
    <!-- HEAD_INFO -->
    
</head>
<body>

<img src="img/htmlmailcompiler-logo.png" alt="">

<h1>I am a simple html email</h1>

<div class="foo">
    <p>What can the compiler do for you?</p>
    <ul>
        <li>
            You can work with CSS selectors and create your styles in a CSS file. The compiler will generate a html file with inline CSS.
        </li>

        <li>
            It can generate various versions of your html by having a master template and using sub templates.
        </li>

        <li>
            It can put all your needed files in a ZIP file.
        </li>

        <li>
            It works with a simple JSON where you put all your configuration for the compilation. You can also use placeholders from your JSON here: PLACEHOLDER_EXAMPLE
        </li>

    </ul>

</div>

<div class="included">
    <?php
    //Lets include some other php files based on the "nr" variable.
    //if no nr is set it set to 0.
    //in your compile.php the index is determined by the array index of the "generate" array from compile.json

        if($nr == 0){
            include("subtemplate1.php");
        }

        if($nr == 1){
            include("subtemplate2.php");
        }

        if($nr == 2){
            include("subtemplate3.php");
        }


    ?>
</div>

<p>The compiled html file is stored in the dist directory :) <span class="but">But you can specify the destination path</span></p>

</body>
</html>