<?php

return [
    /*
     * dompdf resolves a base path via realpath(public_path or base_path('public')).
     * In our split production layout the app folder has no public/ directory
     * (the public assets live in the web root, e.g. public_html), so the default
     * base_path('public') doesn't exist and dompdf throws "Cannot resolve public
     * path". The report PDF uses no relative/external assets, so pointing this at
     * the app root (always resolvable, in web and CLI) is safe.
     */
    'public_path' => base_path(),
];
