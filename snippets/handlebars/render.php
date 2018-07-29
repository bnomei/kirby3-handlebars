<?php
    $renderer = LightnCandy\LightnCandy::prepare($precompiledTemplate);
    if($renderer && is_callable($renderer)) {
        echo $renderer($data);
    }
