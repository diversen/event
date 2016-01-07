<?php

namespace modules\event;

use diversen\db\rb;
use R;

rb::connect();

class import {

    public function getAryFromTxt($txt) {
        $final = [];
        $ary = explode(',', $txt);
        foreach ($ary as $val) {
            $a = explode('" ', $val);
            $a[0] = trim(str_replace('"', '', $a[0]));
            $a[1] = trim($a[1]);
            $final[] = $a;
        }
        return $final;
    }
    
    public function addToDb ($ary) {
        
        foreach($ary as $val) {
            //R::begin();
            $b = rb::getBean('account', 'email', $val[1]);
            $b->username = $val[0];
            $b->email = $val[1];
            rb::commitBean($b);
        }
    }
}
