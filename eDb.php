<?php

namespace modules\event;

use diversen\db\connect;
use diversen\db\q;
use diversen\db\rb;
use diversen\session;
use R;

/**
 * SQL class for doing SQL ...
 */
class eDb {
    
    public function __construct() {
        rb::connect();
    }
    
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
     * Method that updates a dancers base info
     * @param int $user_id
     * @param array $ary $base info from form
     * @return boolean
     */
    public function updateDancer($user_id, $ary) {
        // Base info
        $bean = rb::getBean('dancer', 'user_id', $user_id);
        $bean = rb::arrayToBean($bean, $ary);
        $bean->user_id = $user_id;
        return R::store($bean);
    }
    
    /**
     * Update a pair when a form is submitted. 
     * Check if the pair already exists.
     * If it does not exists, then thrash pair with user_id
     * @param type $user_id
     * @param type $ary
     */
    public function updatePairs($user_id, $ary) {
        
        // Remove all pairs containing user            
        $pairs = R::find('pair', 'user_a = ? OR user_b = ?', [$user_id, $user_id]);
        R::trashAll($pairs);

        // Check for a real pair
        $pair = $this->getPairFromUserId(session::getUserId());

        if (!empty($pair)) {
            $pair = rb::getBean('pair', 'user_id', session::getUserId());
            $pair->user_a = $ary['partner'];
            $pair->user_b = session::getUserId();
            R::store($pair);
        }
    }

    /**
     * Method that will check and get a pair from dancer table
     * @return array $row a single row
     */
    public function getPairFromUserId($user_id) {
        $q_user_id = connect::$dbh->quote($user_id);
        $q = <<<EOF
SELECT DISTINCT
    b.user_id as partner,
    a.user_id as user_id 
        FROM dancer a, dancer b 
    WHERE a.user_id = b.partner AND a.partner = b.user_id AND a.user_id = $q_user_id;
EOF;

        $row = q::query($q)->fetchSingle();
        return $row;
    }
    
    /**
     * Get all pairs as an array
     * @return array $ary array of pairs
     */
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
