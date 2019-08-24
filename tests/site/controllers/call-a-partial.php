<?php
return function ($site, $page, $kirby) {
    return ['cake' => $page->title()->value()];
};
