<?php

namespace modules\event;

use diversen\db\connect;
use diversen\db\q;
use diversen\db\rb;
use diversen\session;
use diversen\html;
use R;

/**
 * SQL class for doing SQL ...
 */
class eDb {
    
    public function __construct() {
        rb::connect();
    }
    
    /**
     * Method which will get all pairs from dancer
     * @return array $rows
     */
    public function getAllPairsFromDancers() {
        
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
     * Delete 'halv' from user_id
     * @param int $user_id
     * @return boolean $res
     */
    public function deleteHalvFromUserId($user_id) {
        
        // Delete all 'halve' if user owns them
        $halve = R::findAll('halv', 'user_id = ?', [$user_id]);
        return R::trashAll($halve);

    }
    
    /**
     * Delete pairs with user
     * @param int $user_id
     * @return boolean $res
     */
    public function deletePairByUserId($user_id) {
        // No pair - we thrash all pairs with user
        //return;
        $pairs = R::findAll('pair', 'user_a = ? OR user_b = ?', [$user_id, $user_id]);
        R::trashAll($pairs);
        
        return $this->deleteHalvFromUserId($user_id);
        
        
        
        return;

    }

    
    /**
     * Return a pair from user_id
     * @param int $user_id
     * @return array $row
     */
    public function getUserPairFromUserId ($user_id) {
        return q::select('pair')->filter('user_a =', $user_id)->condition('OR')->filter('user_b =', $user_id)->fetchSingle();
    }
    
    /**
     * Get pair from pair - based on pair id
     * @param int $id
     * @return array $pair
     */
    public function getPair ($id) {
        return q::select('pair')->filter('id =', $id)->fetchSingle();
    }
    
    /**
     * Get other member of pair - based on session::getUserId
     * @param int $id
     * @return int $user_id
     */
    public function getPairPartnerUserId ($user_id) {
        $pair = $this->getUserPairFromUserId($user_id);
        if (empty($pair)) {
            return array();
        }
        
        if ($pair['user_a'] == session::getUserId()) {
            return $pair['user_b'];
        } 
        return $pair['user_a'];
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

            $this->deletePairByUserId($user_id);
            
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
     * Attach halvmembers
     * @param object $halv
     * @param array $ary
     * @return object $halv 
     */
    public function attachMembersForHalv ($halv, $ary) {
        
        $e = new eDb();

        $a_b = $e->getPairPartnerUserId(session::getUserId());
        
        // Selected pair
        $pair_b = $e->getPair($ary['pair']);
        $b_a = $pair_b['user_a'];
        $b_b = $pair_b['user_b'];
        
        $halv->xownMemberList = array();
        
        // Owner is a member and confirmed
        $member = R::dispense('halvmember');
        $member->user_id = session::getUserId();
        $member->confirmed = 1;
        $halv->xownMemberList[] = $member;
        
        $member_2 = R::dispense('halvmember');
        $member_2->user_id = $a_b;
        $member_2->confirmed = 1;
        $halv->xownMemberList[] = $member_2;
        
        $member_3 = R::dispense('halvmember');
        $member_3->user_id = $b_a;
        $member_3->confirmed = 0;
        $halv->xownMemberList[] = $member_3;
        
        $member_4 = R::dispense('halvmember');
        $member_4->user_id = $b_b;
        $member_4->confirmed = 0;
        $halv->xownMemberList[] = $member_4;
        
        return $halv;
    }
    
    /**
     * Get 'halve' where user is invited
     * @param int $user_id
     * @return array $rows
     */
    public function getHalvUserInvites ($user_id, $confirmed = 0) {
        $user_id = connect::$dbh->quote($user_id);
        $q = <<<EOF
SELECT halv_id FROM halvmember WHERE user_id = $user_id AND confirmed = $confirmed AND halv_id IS NOT NULL
EOF;
        return q::query($q)->fetch();
    }
    
    /**
     * Create a 'halv' and all 'halvmembers'
     * @param array $ary _POST
     * @return boolean $res result from R::store
     */
    public function createHalv($ary) {
        
        
        // create halv
        $halv = rb::getBean('halv');
        $halv->name = html::specialDecode($ary['name']);
        $halv->reserved = html::specialDecode($ary['reserved']);
        $halv->user_id = session::getUserId();
        
        // Attach all 4 members
        $halv = $this->attachMembersForHalv($halv, $ary);
        return R::store($halv);
    }
}
