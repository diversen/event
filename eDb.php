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
    public function getAllPairsFromPairs() {
        $q = "SELECT * FROM pair";
        $rows = q::query($q)->fetch();
        return $rows;
    }
    
        
    /**
     * Method which will get all pairs from dancer
     * @return array $rows
     */
    public function getAllPairsNotInHalve() {
        $q = "SELECT * FROM pair WHERE id NOT IN ( select pair_a from halv WHERE confirmed = 1 UNION select pair_b FROM halv WHERE confirmed = 1)";
        $rows = q::query($q)->fetch();
        return $rows;
    }
    
    /**
     * Get all halve except halve where user is in
     * @return type
     */
    public function getAllHalve ($user_id) {
        $user_id = connect::$dbh->quote($user_id);
        $q = "SELECT * FROM halv WHERE confirmed = 1 AND id NOT IN (SELECT halv_id FROM halvmember WHERE user_id = $user_id AND halv_id IS NOT NULL )";
        $rows = q::query($q)->fetch();
        return $rows;
    }
    
        /**
     * Get all halve except halve where user is in
     * @return type
     */
    public function getAllHalveNotInHele ($user_id) {
        $user_id = connect::$dbh->quote($user_id);
        $q = <<<EOF
SELECT * FROM halv WHERE confirmed = 1 AND id NOT IN 
    (SELECT halv_id FROM halvmember WHERE user_id = $user_id AND halv_id IS NOT NULL ) AND id NOT IN 
    (SELECT halv_a FROM hel WHERE confirmed = 1 UNION SELECT halv_b FROM hel WHERE confirmed = 1)
EOF;
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
    public function getPairFromPairUsers ($user_a, $user_b) {
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
     * Get pair from a user_d
     * @param int $user_id
     * @return array $row
     */
    public function getPairFromUserId ($user_id) {
        $row = q::select('pair')->filter('user_b =', $user_id)->condition('OR')->
                filter('user_a =', $user_id)->fetchSingle();
        return $row;
    }
    
    

    /**
     * Delete 'halv' from user_id
     * @param int $user_id
     * @return boolean $res
     */
    public function deleteHalvFromUserId($user_id) {

        $user_id = connect::$dbh->quote($user_id);
        $q = "SELECT DISTINCT(halv_id) FROM halvmember WHERE user_id = $user_id AND halv_id IS NOT NULL";
       
        $halve = q::query($q)->fetch();
        
        foreach ($halve as $halv) {
            // Delete all 'halve' if user owns them
            $halv = R::findOne('halv', 'id = ?', [$halv['halv_id']]);
            R::trash($halv);
        }
    }
    
    
    /**
     * Delete 'hel' from user_id
     * @param int $user_id
     * @return boolean $res
     */
    public function deleteHelFromUserId($user_id) {

        $user_id = connect::$dbh->quote($user_id);
        $q = "SELECT DISTINCT(hel_id) FROM helmember WHERE user_id = $user_id AND hel_id IS NOT NULL";
       
        $hele = q::query($q)->fetch(); 
        foreach ($hele as $hel) {
            // Delete all 'hele' if user is part of them them
            $hel = R::findOne('hel', 'id = ?', [$hel['hel_id']]);
            R::trash($hel);
        }
    }
    
    /**
     * Delete 'halv' from user_id
     * @param int $id user_id
     * @return boolean $res
     */
    public function deleteHalvFromId($id) {
        
        
        // Only allow members to delete from hel.
        $row = $this->getSingleUserFromHalv($id, session::getUserId());
        if (empty($row)) {
            return false;
        }
        
        // Delete all 'halve' if user owns them
        $halve = R::findAll('halv', 'id = ?', [$id]);
        return R::trashAll($halve);

    }
    
    
     /**
     * Delete 'hel' from user_id
     * @param int $user_id
     * @return boolean $res
     */
    public function deleteHelFromId($id) {
        
        // Only allow members to delete from hel.
        $row = $this->getSingleUserFromHel($id, session::getUserId());
        if (empty($row)) {
            return false;
        }
        
        // Delete all 'hel' if user owns them
        $hele = R::findAll('hel', 'id = ?', [$id]);
        return R::trashAll($hele);

    }
    
    
    /**
     * Confirm 'halv' by user_id
     * @param int $id
     * @return boolean $res
     */
    public function confirmHalvMembers($id) {
        R::begin();
        $bean = rb::getBean('halv', 'id', $id);
        $bean->confirmed = 1;
        R::store($bean);
        q::update('halvmember')->values(array('confirmed' => 1))->filter('halv_id =', $id)->exec();
        
        if (R::commit()) {
            return true;
        } else {
            R::rollback();
            return false;
        }
    }
    
    /**
     * Confirm 'hel' by user_id
     * @param int $id
     * @return boolean $res
     */
    public function confirmHelMembers($id) {
        R::begin();
        $bean = rb::getBean('hel', 'id', $id);
        $bean->confirmed = 1;
        R::store($bean);
        q::update('helmember')->values(array('confirmed' => 1))->filter('hel_id =', $id)->exec();
        
        if (R::commit()) {
            return true;
        } else {
            R::rollback();
            return false;
        }
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
     * Return a 'halv' from user_id
     * @param int $user_id
     * @return array $row
     */
    public function getUserHalvFromUserId ($user_id) {
        $halv = $this->getHalvUserInvites($user_id, 1);
        return $halv;
    }
    
    /**
     * Return a confirmed 'hel' from user_id
     * @param int $user_id
     * @return array $row
     */
    public function getUserHelFromUserId ($user_id) {
        $hel = $this->getHelUserInvites($user_id, 1);
        return $hel;
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
     * Get partners user id from a users id
     * @param int $user_id
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
        
        // Fetch an existing pair from pairs
        $row = $this->getPairFromPairUsers($user_id, $ary['partner']);
        
        // No existing pair 
        if (empty($row)) {

            // Delete all pair with user_id
            $this->deletePairByUserId($user_id);
            
            // Check for a new pair in dancers table
            $pair = $this->getPairFromDancers(session::getUserId());

            if (!empty($pair)) {
                
                // And add new pair
                $pair = rb::getBean('pair');
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
     * Attach halvmembers
     * @param object $hel
     * @param array $ary
     * @return object $halv 
     */
    public function attachMembersForHel ($hel, $ary) {
        
        $e = new eDb();
        $users_a = $e->getUsersFromHalv($ary['halv']);
        $my_halv = $e->getUserHalvFromUserId(session::getUserId());
        $users_b = $e->getUsersFromHalv($my_halv['id']);
        
        $hel->xownMemberList = array();
        foreach($users_a as $user) {
            
            // Owner is a member but not confirmed
            $member = R::dispense('helmember');
            $member->user_id = $user['user_id'];
            $member->confirmed = 0;
            $hel->xownMemberList[] = $member;
        }
        
        foreach($users_b as $user) {
            
            // Owner is a member but not confirmed
            $member = R::dispense('helmember');
            $member->user_id = $user['user_id'];
            $member->confirmed = 1;
            $hel->xownMemberList[] = $member;
        }
        
        return $hel;
    }
    
    /**
     * Get 'hel' where user is invited
     * @param int $user_id
     * @param int $confirmed
     * @return array $row
     */
    public function getHalvUserInvites ($user_id, $confirmed = 0) {
        $user_id = connect::$dbh->quote($user_id);
        
        if ($confirmed == 1) {
            $opt = "AND m.confirmed = $confirmed";
        } else {
            $opt = '';
        }
        
        $q = <<<EOF
SELECT DISTINCT
    m.halv_id,
    h.id, h.name, h.confirmed as confirmed 
        FROM halvmember m, halv h 
    WHERE m.halv_id = h.id AND m.user_id = $user_id $opt  AND m.halv_id IS NOT NULL
EOF;
        return q::query($q)->fetchSingle();
    }
    
    
    /**
     * Get 'halve' where user is invited
     * @param int $user_id
     * @param int $confirmed
     * @return array $row
     */
    public function getHelUserInvites ($user_id, $confirmed = 0) {
        $user_id = connect::$dbh->quote($user_id);
        
        if ($confirmed == 1) {
            $opt = "AND m.confirmed = $confirmed";
        } else {
            $opt = '';
        }
        
        $q = <<<EOF
SELECT DISTINCT
    m.hel_id,
    h.id, h.name as title 
        FROM helmember m, hel h 
    WHERE m.hel_id = h.id AND m.user_id = $user_id $opt  AND m.hel_id IS NOT NULL
EOF;
        return q::query($q)->fetchSingle();
    }

    
    /**
     * Get all users that belongs to a 'halv'
     * @param int $halv
     * @return array $rows
     */
    public function getUsersFromHalv($id) {
        return q::select('halvmember')->filter('halv_id =', $id)->fetch();
    }
    
    /**
     * Get all users that belongs to a 'hel'
     * @param int $halv
     * @return array $rows
     */
    public function getUsersFromHel($id) {
        return q::select('helmember')->filter('hel_id =', $id)->fetch();
    }
    
    /**
     * Get all users that belongs to a 'halv'
     * @param int $halv
     * @return array $rows
     */
    public function getSingleUserFromHalv($id, $user_id) {
        return q::select('halvmember')->
                filter('halv_id =', $id)->condition('AND')->
                filter('user_id =', $user_id)->fetchSingle();
    }
    
        
    /**
     * Get all users that belongs to a 'halv'
     * @param int $halv
     * @return array $rows
     */
    public function getSingleUserFromHel($id, $user_id) {
        return q::select('helmember')->
                filter('hel_id =', $id)->condition('AND')->
                filter('user_id =', $user_id)->fetchSingle();
    }
    
    
    /**
     * Get a readable string of users in a halv
     * @param int $halv
     * @return string $str
     */
    public function getUsersStrFromHalv($halv) {
        $users = $this->getUsersFromHalv($halv);
        $ary = [];
        foreach($users as $user) {
            $account = session::getAccount($user['user_id']);
            $ary[] = $account['username'];
        }
        return implode(' - ', $ary);
    }
    
    /**
     * Get a readable string of users in a 'hel'
     * @param int $hel
     * @return string $str
     */
    public function getUsersStrFromHel($hel) {
        $users = $this->getUsersFromHel($hel);
        $ary = [];
        foreach($users as $user) {
            $account = session::getAccount($user['user_id']);
            $ary[] = $account['username'];
        }
        return implode(' - ', $ary);
    }
    
    /**
     * Checks if all users in a halv is confirmed
     * @param int $halv
     * @return boolean $res
     */
    public function getHalvAllConfirmed($halv) {
        if (empty($halv)) {
            return false;
        }
        
        $users = $this->getUsersFromHalv($halv['id']);
        foreach($users as $user) {
            if ($user['confirmed'] == 0) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Checks if all users in a hel is confirmed
     * @param int $hel
     * @return boolean $res
     */
    public function getHelAllConfirmed($halv) {
        $users = $this->getUsersFromHel($halv);
        foreach($users as $user) {
            if ($user['confirmed'] == 0) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Create a 'halv' and all 'halvmembers'
     * @param array $ary _POST
     * @return boolean $res result from R::store
     */
    public function createHalv($ary) {
        
        // create halv
        $halv = rb::getBean('halv');

        // User id
        $halv->user_id = session::getUserId();
        
        // Attach pair ids
        $pair = $this->getPairFromUserId(session::getUserId());
        $halv->pair_a = $pair['id'];
        
        $halv->pair_b = $ary['pair'];
        
        // Attach all 4 members
        $halv = $this->attachMembersForHalv($halv, $ary);
        return R::store($halv);
    }
    
    /**
     * Create a 'hel' and all 'helmembers'
     * @param array $ary _POST
     * @return boolean $res result from R::store
     */
    public function createHel($ary) {
        
        $e = new eDb();
        
        // create hel
        $hel = rb::getBean('hel');

        $hel->user_id = session::getUserId();

        // Attach halve ids
        $my_halv = $e->getUserHalvFromUserId(session::getUserId());
        $hel->halv_a = $ary['halv'];
        $hel->halv_b = $my_halv['id'];

        // Attach all 8 members
        $hel = $this->attachMembersForHel($hel, $ary);
        return R::store($hel);
    }
}
