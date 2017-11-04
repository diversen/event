<?php

namespace modules\event;

use modules\event\eDb;
use diversen\session;
use diversen\html;

class eHelpers {

    public function getUserTagStr ($row) {
        $row = html::specialEncode($row);
        return $row['username'] . " ($row[tag])";
    }
    /**
     * Get all pairs as an array excluding pair with user
     * @return array $ary array of pairs
     */
    public function getFormPairsAry () {
        
        $eDb = new eDb();
        $pairs = $eDb->getAllPairsNotInHalve();
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
            
            $pair_str = $this->getUserTagStr($a) . " - " . $this->getUserTagStr($b);
            $ary[$pair['id']] = $pair_str;
        }
        return $ary;
    }
    
    /**
     * Get all pairs as an array excluding pair with user
     * @return array $ary array of pairs
     */
    public function getFormHalveAry () {
        
        $eDb = new eDb();
        $halve = $eDb->getAllHalveNotInHele(session::getUserId());
        
        $ary = [];
        $ary[0] = 'Ingen halv valgt';
        foreach ($halve as $halv) {
            $ary[$halv['id']] = $halv['name'];
        }
        return $ary;
    }
} 
