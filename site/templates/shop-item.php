<?php namespace ProcessWire;

// Template file for pages using the "shop-item" template

// form_selection holds a plain FormBuilder form name (e.g. "purchase-aelfie-impromptus"),
// entered by the editor. Rendered here (embed method C) so styling matches the site theme,
// rather than via FormBuilder's Easy Embed tag, which renders in an unstyled iframe.
$formSelectionName = trim((string) $page->form_selection);
if ($formSelectionName) {
    try {
        $itemForm = $forms->render($formSelectionName);
        wire()->set('storeItemFormHtml', (string) $itemForm);
        wire()->set('storeItemFormStyles', $itemForm->styles);
        wire()->set('storeItemFormScripts', $itemForm->scripts);

        // Base unit price in cents, straight from the form's Stripe config. Exposed as a
        // data attribute (not a formatted string) so onload.js's live price calculation
        // has a starting value for forms that have no score_type-style variant selector —
        // it only reads this as a fallback; a form's own score_type field, if present,
        // is still the authoritative source once selected.
        $stripeConfig = $itemForm->form->FormBuilderProcessorStripe;
        if (!empty($stripeConfig['amount'])) {
            wire()->set('storeItemUnitPriceCents', (int) $stripeConfig['amount']);
        }
    } catch (FormBuilderException $e) {
        wire()->warning("Unknown form '$formSelectionName' selected on page {$page->path}");
    }
}

?>