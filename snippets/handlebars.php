<?php
    // expecting $template:string, $data:array
    echo Bnomei\Handlebars::r(
        (isset($template) && is_string($template) ? $template : 'default'),
        (isset($data) && is_array($data) ? $data : [])
    );
