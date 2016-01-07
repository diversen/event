<?php

use diversen\lang;
use diversen\session;


$_MODULE_MENU = array();

$level = session::getUserLevel();

if ($level == 'user') {
    $_MODULE_MENU[] = array(
        'title' => lang::translate('Elev'),
        'url' => '/event/user/index',
        'auth' => 'user');
}

if ($level =='admin') {
    $_MODULE_MENU[] = array(
        'title' => lang::translate('Lærer'),
        'url' => '/event/admin/index',
        'auth' => 'admin');
}
