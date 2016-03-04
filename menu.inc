<?php

use diversen\lang;
use diversen\session;


$_MODULE_MENU = array();

$level = session::getUserLevel();

if (session::isUser()) {
    $_MODULE_MENU[] = array(
        'title' => lang::translate('Elev'),
        'url' => '/event/user/index',
        'auth' => 'user');
}

if (session::isAdmin()) {
    $_MODULE_MENU[] = array(
        'title' => lang::translate('Vis alle'),
        'url' => '/event/admin/index?all=1',
        'auth' => 'admin');
    
    $_MODULE_MENU[] = array(
        'title' => lang::translate('Par'),
        'url' => '/event/admin/index?par=1',
        'auth' => 'admin');
    
    $_MODULE_MENU[] = array(
        'title' => lang::translate('Halve kvadriller'),
        'url' => '/event/admin/index?halv=1',
        'auth' => 'admin');
    
    $_MODULE_MENU[] = array(
        'title' => lang::translate('Kvadriller'),
        'url' => '/event/admin/index?hel=1',
        'auth' => 'admin');
    
    $_MODULE_MENU[] = array(
        'title' => lang::translate('Uden partner'),
        'url' => '/event/admin/index?uden=1',
        'auth' => 'admin');
    
    $_MODULE_MENU[] = array(
        'title' => lang::translate('Ikke registreret'),
        'url' => '/event/admin/index?reg_minus=1',
        'auth' => 'admin');
    
    $_MODULE_MENU[] = array(
        'title' => lang::translate('Import'),
        'url' => '/event/admin/import',
        'auth' => 'admin');
}
