<?php

namespace modules\event;

use diversen\db\connect;
use diversen\db\q;
use diversen\session;

/**
 * SQL class for doing SQL ...
 */
class eDb {
    
    /**
     * Method which will get all pairs
     * @return array $rows
     */
    public function getAllPairs() {
        
        $q = <<<EOF
SELECT DISTINCT
    CASE WHEN a.user_id > b.user_id THEN a.user_id ELSE b.user_id END as a,
    CASE WHEN a.user_id > b.user_id THEN b.user_id ELSE a.user_id END as b 
        FROM dancer a, dancer b 
    WHERE a.user_id = b.partner AND a.partner = b.user_id;
EOF;

        $q = "SELECT * FROM pair";
        $rows = q::query($q)->fetch();
        return $rows;
    }
    
    public function getAllPairsDropDown () {
        
    }
    
    /**
     * Method that will check if a user is paired
     * @return array $rows
     */
    public function isPaired($user_id) {
        $q_user_id = connect::$dbh->quote($user_id);
        $q = <<<EOF
SELECT DISTINCT
    b.user_id as partner,
    a.user_id as user_id 
        FROM dancer a, dancer b 
    WHERE a.user_id = b.partner AND a.partner = b.user_id AND a.user_id = $q_user_id;
EOF;

        $rows = q::query($q)->fetchSingle();
        
        return $rows;
    }
    
    public function formPairsAry () {
        $pairs = $this->getAllPairs();
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