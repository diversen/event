<?php

namespace modules\event;

use modules\event\eDb;
use diversen\session;

class eHelpers {
    /**
     * Get all pairs as an array excluding pair with user
     * @return array $ary array of pairs
     */
    public function getFormPairsAry () {
        
        $eDb = new eDb();
        $pairs = $eDb->getAllPairsFromDancers();
        $ary = [];
        $ary[0] = 'Intet par valgt';
        foreach ($pairs as $pair) {
            $a = session::getAccount($pair['user_a']);
            $b = session::getAccount($pair['user_b']);
            
            if ($a['id'] == session::getUserId()) {
                continue;
            }
            if ($b['id'] == session::getUserId()) {
                continue;
            }
            
            $pair_str = $a['username'] . ' - ' . $b['username'];
            $ary[$pair['id']] = $pair_str;
        }
        return $ary;
    }
} 