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
     * Get a pair row from user_a and user_b
     * @param int $user_a
     * @param int $user_b
     * @return array $row row from pair table 
     */
    public function getPairFromPartners ($user_a, $user_b) {
        $row = q::select('pair')->filter('user_a =', $user_a)->condition('AND')->filter('user_b =', $user_b)->fetchSingle();
        if (!empty($row)) {
            return $row;
        }
        $row = q::select('pair')->filter('user_b =', $user_a)->condition('AND')->filter('user_a =', $user_b)->fetchSingle();
        if (!empty($row)) {
            return $row;
        }
        return array ();
    }

    
    /**
     * Delete pairs with user
     * @param int $user_id
     * @return boolean $res
     */
    public function deletePairWithUser($user_id) {
        // No pair - we thrash all pairs with user
        $pairs = R::find('pair', 'user_a = ? OR user_b = ?', [$user_id, $user_id]);
        return R::trashAll($pairs);
    }
    
    /**
     * Delete quartet with pair
     * @param int $pair_id
     * @return boolean $res
     */
    public function deleteHalvWithPair($pair_id) {
        // No pair - we thrash all pairs with user
        $halve = R::find('halv', 'pair_a = ? OR pair_b = ?', [$pair_id, $pair_id]);
        return R::trashAll($halve);
    }
    
    /**
     * Return a pair from user_id
     * @param int $user_id
     * @return array $row
     */
    public function getPairFromPairs ($user_id) {
        return q::select('pair')->filter('user_a =', $user_id)->condition('OR')->filter('user_b =', $user_id)->fetchSingle();
    }
    
    /**
     * Update a pair when a form is submitted. 
     * Check if the pair already exists.
     * If it does not exists, then thrash pair with user_id
     * @param type $user_id
     * @param type $ary
     */
    public function updatePairs($user_id, $ary) {
        
        // Fetch an existing pair
        $row = $this->getPairFromPartners($user_id, $ary['partner']);
        
        // No existing pair 
        if (empty($row)) {

            $this->deletePairWithUser($user_id);
            
            // Check for a new pair in dancers table
            $pair = $this->getPairFromDancers(session::getUserId());

            if (!empty($pair)) {
                
                // And add new pair
                $pair = rb::getBean('pair', 'user_id', session::getUserId());
                $pair->user_a = $ary['partner'];
                $pair->user_b = session::getUserId();
                R::store($pair);
                
            }
        }
    }
    
    /**
     * Method that will check and get a pair from dancer table
     * @return array $row a single row
     */
    public function getPairFromDancers($user_id) {
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
     * @return array $row a single row
     */
    public function getHalvFromDancers($user_id, $ary) {
        
        $halv = $ary['halv'];
        
        // Get partner pair
        $partner_pair = q::select('pair')->filter('id =', $halv)->fetchSingle();
        
        $user_a = q::select('dancer')->filter('user_id =', $partner_pair['user_a'])->fetchSingle();
        // if ($user_a['halv'] == $)
        
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
    
        
    /**
     * Create a 'halv' member
     * @param array $ary
     * @return boolean $res result from R::store
     */
    public function createHalv($ary) {
         
        $this->dbDeleteHalvMember();
        
        $halv = rb::getBean('halv');
        $halv->name = html::specialDecode($ary['name']);
        $halv->reserved = html::specialDecode($ary['reserved']);
        $halv->user_id = session::getUserId();
        $member = R::dispense('halvmember');
        $member->user_id = session::getUserId();
        $halv->ownMemberList[] = $member;
        return R::store($halv);
    }
}
