<?php

use diversen\lang;

$_INSTALL['MAIN_MENU_ITEMS'] = array ();
$_INSTALL['MAIN_MENU_ITEMS'][0] = array (
    'title' => lang::translate('Event', null, array ('no_translate' => true)),
    'url' => '/event/index',
    'module_name' => 'event',
    'parent' => 0,
    'weight' => 1,
    'auth' => 'user'
);
