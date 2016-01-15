<?php

namespace modules\event;

use diversen\db;
use diversen\db\q;
use diversen\db\connect;

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

        $rows = q::query($q)->fetch();
        return $rows;
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
}