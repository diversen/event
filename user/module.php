<?php

namespace modules\event\user;

use diversen\db;
use diversen\db\q;
use diversen\db\rb;
use diversen\html;
use diversen\html\helpers;
use diversen\http;
use diversen\log;
use diversen\moduleloader;
use diversen\session;
use diversen\user;
use modules\event\eDb;
use R;

rb::connect();

class module {
    
    /**
     * Check if user has access 
     * @return void
     */
    public function checkAccess () {
        if (!session::isUser()) {
            moduleloader::setStatus(403);
            return;
        }
    }
    
    /**
     * /event/user/index
     * @return void
     */
    public function indexAction () {
        $this->checkAccess();
        
        $e = new eDb();
        
        if (isset($_POST['submit'])) {
            
            $user_id = session::getUserId();
            R::begin();

            $ary = db::prepareToPost();
            
            // Base info
            $bean = rb::getBean('dancer', 'user_id', $user_id);
            $bean = rb::arrayToBean($bean, $ary);
            $bean->user_id = $user_id;
            R::store($bean);

            // Remove all pairs containing user            
            $pairs = R::find('pair', 'user_a = ? OR user_b = ?', [$user_id, $user_id]);
            R::trashAll($pairs);
            
            // Check for a real pair
            $e = new eDb();
            $pair = $e->isPaired(session::getUserId());
            if (!empty($pair)) {
                $pair = rb::getBean('pair', 'user_id', session::getUserId());
                $pair->user_a = $ary['partner'];
                $pair->user_b = session::getUserId();
                R::store($pair);            
            }

            R::commit();

            http::locationHeader('/event/user/index', 'Dine data blev opdateret');

        }
        echo $this->formBase();
    }
    
    /**
     * User base form
     * @return string $html
     */
    public function formBase () {
        
        $ary = q::select('dancer')->filter('user_id =', session::getUserId())->fetchSingle();
        
        $f = new html();
        $f->init($ary, 'submit', true);
        $f->formStart();
        $f->legend('Basis data - ret eller indsæt');
        
        $account = user::getAccount();
        
        $opt = array ('disabled' => 'disabled');
        $f->label('username', 'Dit navn');
        $f->text('username', $account['username'], $opt);
        
        $sex = array (
            '0' => 'Vælg køn',
            '1' => 'Kvinde',
            '2' => 'Mand'
        );
        
        $f->label('sex', 'Dit køn');
        $f->selectAry('sex', $sex);
        
        $f->label('comment', 'Evt. kommentar');
        $f->textareaSmall('comment');
        
        // Partner
        $partners = $this->getUsers();
        $rows = [];
        $rows[0] = 'Ingen partner';
        
        foreach($partners as $partner) {
            if ($partner['username'] == $account['username']) { 
                continue;
            }
            $rows[$partner['id']] = $partner['username'];
        }
        
        log::debug("Mit bruger ID: " . session::getUserId());

        $eDb = new eDb();
        $partner = $eDb->isPaired(session::getUserId());
        if (!empty($partner)) {
            $user = session::getAccount($partner['partner']);
            $label = "Du har en partner: '$user[username]'"; 
        } else {
            $label = 'Har du en partner, så vælg en fra listen'; 
        }
        
        $f->label('partner', $label);
        $f->selectAry('partner', $rows);
        
        //if (!empty($partner)) {
            $f = $this->formAttachHalv ($f);
            // $f = $this->formAttachHel($f);
        //}
        
        
        $f->label('base');
        $f->submit('submit', 'Opdater');
        
        $f->formEnd();
        
        return $f->getStr();    
    }
    
    /**
     * 
     * @param Object $f \diversen\html
     * @return type
     */
    public function formAttachHalv($f) {
        
        $eDb = new eDb();
        $partner = $eDb->isPaired(session::getUserId());

        $message = <<<EOF
Du kan først vælge en halv kvadrille, når du har dannet et verficeret par.
EOF;
        
        if (empty($partner)) {
            $f->addHtml(html::getError($message));
            return $f;
        }
        
        
        $pairs = $eDb->formPairsAry();
        print_r($pairs);
        
        
        $f->label('halv', " Vælg et par - og opret derved en halv kvadrille ");
        $f->selectAry('halv', $pairs);
        return $f;
    }

    
    /**
     * Delete a 'helmember' based on session::getUserId
     * @return boolean $res result of R::thrashAll
     */
    public function dbDeleteHelMember(){
        $members = R::findAll('helmember', "user_id = ?", array (session::getUserId()));
        return R::trashAll($members);
    }
    
    /**
     * Delete a 'halvmember' based on session::getUserId
     * @return boolean $res result of R::thrashAll
     */
    public function dbDeleteHalvMember(){
        $members = R::fifndAll('halvmember', "user_id = ?", array (session::getUserId()));
        return R::trashAll($members);
    }
    
    /**
     * Create a 'halv' member
     * @param array $ary
     * @return boolean $res result from R::store
     */
    public function dbCreateHalv($ary) {
         
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
    


    
    public function dbCreateHel($ary) {
        
        $this->dbDeleteHelMember();
        
        $hel = rb::getBean('hel');
        $hel->name = html::specialDecode($ary['name']);
        $hel->reserved = html::specialDecode($ary['reserved']);
        $hel->user_id = session::getUserId();

        $member = R::dispense('helmember');
        $member->user_id = session::getUserId();

        $hel->ownMemberList[] = $member;
        return R::store($hel);
    }




    /**
     * Get all users
     */
    public function getUsers () {
        return q::select('account')->filter('admin =' , 0)->fetch();
    }
}