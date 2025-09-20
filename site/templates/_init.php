<?php namespace ProcessWire;

// Optional initialization file, called before rendering any template file.
// This is defined by $config->prependTemplateFile in /site/config.php.
// Use this to define shared variables, functions, classes, includes, etc. 

if ($config->rockdevtools) {
    $devtools = rockdevtools();
    
    // compile all less files to CSS
    $devtools->assets()
        ->less() 
        ->add('/site/templates/uikit/src/less/uikit.theme.less')
        ->add('/site/templates/sections/**.less', 3)
        ->add('/site/templates/styles/custom.less')
        ->save('/site/templates/src/.styles.css');
  
    // merge and minify css files
    $devtools->assets()
        ->css()
        ->add('/site/templates/src/.styles.css')
        ->save('/site/templates/dst/styles.min.css');
  
    // merge and minify JS files
    $devtools->assets()
        ->js()
        ->add('/site/templates/uikit/dist/js/uikit.min.js')
        ->add('/site/templates/uikit/dist/js/uikit-icons.min.js')
        ->add('/site/templates/scripts/main.js')
        ->add('/site/templates/scripts/consenty.min.js')
        ->save('/site/templates/dst/scripts.min.js');
  }