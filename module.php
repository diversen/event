<?php

namespace modules\event;

use diversen\http;
use diversen\session;

// use diversen\html;

class module {
    
    public function indexAction () {
        if (session::isAdmin()) {
            http::locationHeader('/event/admin/index?all=1');
        }
        
        if (session::isUser()) {
            http::locationHeader('/event/user/index');
        }
    }
    

}