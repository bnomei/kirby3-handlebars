<?php
    $renderer = LightnCandy\LightnCandy::prepare($precompiledTemplate, sys_get_temp_dir());
    if ($renderer && is_callable($renderer)) {
        echo $renderer($data);
    }
