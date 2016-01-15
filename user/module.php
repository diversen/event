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
            
            R::begin();

            $ary = db::prepareToPost();
            $bean = rb::getBean('dancer', 'user_id', session::getUserId());
            $bean = rb::arrayToBean($bean, $ary);
            $bean->user_id = session::getUserId();
            
            R::store($bean);

            $pairs = R::find('pair', 'user_id = ?', array(session::getUserId()));
            R::trashAll($pairs);
            
            $pair = rb::getBean('pair', 'user_id', session::getUserId());
            $pair->partner = $ary['partner'];
            $pair->user_id = session::getUserId();
            
            R::store($pair);
            
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
            $f = $this->formAttachHel($f);
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
Du har valgt en partner, 
men han/hun har endnu ikke verificeret dig. 
Du kan først vælge en halv kvadrille, når han/hun har verficeret dig.
EOF;
        
        if (empty($partner)) {
            $f->addHtml(html::getError($message));
            return $f;
        }
        
        
        $halv = q::select('halv')->fetch();
        $rows[0] = 'Ingen halv kvadrille';
        foreach ($halv as $a) {
            $rows[$a['id']] = $a['name'];
        }
        
        $halv = q::select('halv')->filter('user_id =', session::getUserId())->fetchSingle();
        if (!empty($halv)) {
            $label = "<hr />";
            $label.= "Halv kvadrille: <b>$halv[name]</b>";
            if ($halv['reserved'] == 1) {
                $label.= " (reserveret)";
            }
            $label.= "<br />";
            $label.= "Du har oprettet denne halv kvadrille, og du er derfor en del af den. ";
            $label.= html::createLink('/event/user/deletehalv', 'Slet');
            $label.= "<hr />";
            $f->label('halv', $label);
            $f->hidden('halv', $halv['id']);
            return $f;
        }
        
        
        $label = 'Er du en del af en halv kvadrille? Hvis ja, så vælg en fra listen eller ';
        $label.= html::createLink('/event/user/halv', 'opret en ny');
        
        
        $f->label('halv', " $label ");
        $f->selectAry('halv', $rows);
        return $f;
    }
    
    public function deletehalvAction () {
        echo helpers::confirmDeleteForm('delete', 'Slet halv kvadrille');
        
        if (isset($_POST['delete'])) {
            q::begin();
            $halv = q::select('halv')->filter('user_id =', session::getUserId())->fetchSingle();
            q::delete('halvmember')->filter('halv_id =', $halv['id'])->exec();
            q::delete('halv')->filter('user_id =', session::getUserId())->exec();
            q::commit();
            http::locationHeader('/event/user/index');
        }
    }
    
    public function deletehelAction () {
        echo helpers::confirmDeleteForm('delete', 'Slet halv kvadrille');
        
        if (isset($_POST['delete'])) {
            q::begin();
            $hel = q::select('hel')->filter('user_id =', session::getUserId())->fetchSingle();
            q::delete('helmember')->filter('hel_id =', $hel['id'])->exec();
            q::delete('hel')->filter('user_id =', session::getUserId())->exec();
            q::commit();
            http::locationHeader('/event/user/index');
        }
    }
    
    
    /**
     * Attach hel to form
     * @param object diversen\html
     * @return type
     */
    public function formAttachHel ($f) {
        $hel = q::select('hel')->fetch();

        $rows = [];
        $rows[0] = 'Ingen hel kvadrille';
        foreach ($hel as $a) {
            $rows[$a['id']] = $a['name'];
        }
        
        $hel = q::select('hel')->filter('user_id =', session::getUserId())->fetchSingle();
        if (!empty($hel)) {
            $label = "<hr />";
            $label.= "Hel kvadrille: <b>$hel[name]</b>";
            if ($hel['reserved'] == 1) {
                $label.= " (reserveret)";
            }
            $label.= "<br />";
            $label.= "Du har oprettet en hel kvadrille, og du er derfor en del af den. ";
            $label.= html::createLink('/event/user/deletehel', 'Slet');
            $label.= "<hr />";
            $f->label('hel', $label);
            $f->hidden('hel', $hel['id']);
            return $f;
        }
        
        $create = html::createLink('/event/user/hel', 'opret en ny');
        $f->label('hel', "Er du en del af en hel kvadrille? Hvis ja, så vælg fra listen herunder eller $create ");
        $f->selectAry('hel', $rows);
        
        return $f;
    }
    
    /**
     * /event/user/halv
     */
    public function halvAction () {
        $this->checkAccess();
        
        $eDb = new eDb();
        $pair = $eDb->isPaired(session::getUserId());
        if (empty($pair)) {
            http::locationHeader('/event/user/index', 'Du skal have en partner for at oprette en halv kvadrille');
        }
        
        http::prg();
        if (isset($_POST['send'])) {
            if (empty($_POST['name'])) {
                echo html::getError('Indtast et navn');
            } else {
                $ary = db::prepareToPostArray(array('name', 'reserved'), true);
                $this->dbCreateHalv($ary);
                http::locationHeader('/event/user/index');
            }
        }
        echo $this->formCreateKvadrille();
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
        $members = R::findAll('halvmember', "user_id = ?", array (session::getUserId()));
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
    
    /**
     * /event/user/create
     */
    public function helAction () {
        
        $this->checkAccess();
        
        http::prg();
        if (isset($_POST['send'])) {
            if (empty($_POST['name'])) {
                echo html::getError('Indtast et navn');
            } else {
                $ary = db::prepareToPostArray(array('name', 'reserved'), true);
                $this->dbCreateHel($ary);
                http::locationHeader('/event/user/index');
            }
        }
        echo $this->formCreateKvadrille('Opret en hel kvadrille');
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
     * Form that creates a kvadrille
     * @param string $title
     * @return string $html
     */
    public function formCreateKvadrille ($title = 'Opret en halv kvadrille') {
        $f = new html();
        $f->init(array(), 'send', true);
        $f->formStart();
        $f->legend($title);
        $f->label('name','Indtast et navn');
        $f->text('name');
        $f->label('reserved', 'Reserveret');
        $f->checkbox('reserved');
        $f->label('send');
        $f->submit('send', 'Opret');
        $f->formEnd();
        return $f->getStr();

    }


    /**
     * Get all users
     */
    public function getUsers () {
        return q::select('account')->filter('admin =' , 0)->fetch();
    }
}