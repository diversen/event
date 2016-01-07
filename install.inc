<?php

use diversen\lang;

$_INSTALL['MAIN_MENU_ITEMS'] = array ();
$_INSTALL['MAIN_MENU_ITEMS'][0] = array (
    'title' => lang::translate('Elev', null, array ('no_translate' => true)),
    'url' => '/event/user/index',
    'module_name' => 'event',
    'parent' => 0,
    'weight' => 1,
    'auth' => 'user'
);

$_INSTALL['MAIN_MENU_ITEMS'][1] = array (
    'title' => lang::translate('Lærer', null, array ('no_translate' => true)),
    'url' => '/event/admin/index',
    'module_name' => 'event',
    'parent' => 0,
    'weight' => 1,
    'auth' => 'admin'
);
