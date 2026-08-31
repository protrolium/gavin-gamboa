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

// Render FormBuilder forms once — avoid double render (double emails, and on Stripe forms,
// a double-processed return from checkout that clears its own session state on the 2nd pass).
// contact-form is used site-wide (contact.latte) so it's rendered here unconditionally.
// purchase-aelfie-impromptus is NOT rendered here — store-item.php renders whichever form
// a given page's form_selection names (often this one), so pre-rendering it here too would
// process it twice per request.
$contactForm = $forms->render('contact-form');

// FormBuilder's ->styles/->scripts reflect the whole $config->styles/scripts queue at
// access time (not per-form assets) — see store-item.php, which overrides these on pages
// that render their own form, since that render adds more to the same queue afterward.
$formStyles = $contactForm->styles;
$formScripts = $contactForm->scripts;

// expose to Latte via wire fuel
wire()->set('contactFormHtml', (string) $contactForm);