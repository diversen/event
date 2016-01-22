<?php

namespace modules\event\user;

use diversen\db;
use diversen\db\q;
use diversen\db\rb;
use diversen\html;
use diversen\http;
use diversen\log;
use diversen\moduleloader;
use diversen\session;
use diversen\user;
use diversen\html\helpers;
use modules\event\eDb;
use modules\event\eHelpers;
use R;



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

            // Update base info
            $ary = db::prepareToPost();
            $e->updateDancer($user_id, $ary);

            // Update pair
            $e->updatePairs($user_id, $ary);
            


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
        
        log::debug("User id: $ary[user_id]");
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

        $eDb = new eDb();
        $partner = $eDb->getUserPairFromUserId(session::getUserId());
        if (!empty($partner)) {   
            $user = session::getAccount($ary['partner']);
            $label = "Du har en partner: '$user[username]'"; 
        } else {
            $label = 'Har du en partner, så vælg en fra listen'; 
        }
        
        $f->label('partner', $label);
        $f->selectAry('partner', $rows);
        
        $f = $this->formAttachHalv ($f);
        
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
        $partner = $eDb->getUserPairFromUserId(session::getUserId());

        $message = <<<EOF
Du kan først vælge / oprette en halv kvadrille, når du har dannet et verficeret par.
EOF;
        
        if (empty($partner)) {
            $f->addHtml(html::getError($message));
            return $f;
        }
        
        /*
        $pairs = $eDb->formPairsAry();
        
        
        
        $f->label('halv', " Vælg et par - og opret derved en halv kvadrille ");
        $f->selectAry('halv', $pairs);
        return $f; */
        
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
    
    /**
     * var holding form submission errors
     * @var array $errors
     */
    public $errors = array ();
    
    public function validateHalv () {
        if (empty($_POST['name'])) {
            $this->errors[] = 'Indtast et navn';
        }
        if ($_POST['pair'] == 0) {
            $this->errors[] = 'Du skal vælge et par som skal indgå i din halv kvadrille';
        }
        
    }
    
    /**
     * /event/user/halv
     */
    public function halvAction () {
        $this->checkAccess();
        
        $eDb = new eDb();
        $pair = $eDb->getUserPairFromUserId(session::getUserId());
        if (empty($pair)) {
            http::locationHeader('/event/user/index', 'Du skal have en partner for at oprette en halv kvadrille');
        }
        
        http::prg();
        if (isset($_POST['send'])) {
            $this->validateHalv();
            if (empty($this->errors)) {
                $ary = db::prepareToPostArray(array('name', 'reserved', 'pair'), true);
                $eDb->createHalv($ary);
                http::locationHeader('/event/user/index');
            } else {
                echo html::getErrors($this->errors);
            }
        }
        echo $this->formCreateKvadrille();
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
        
        $h = new eHelpers();
        $ary = $h->getFormPairsAry();
        
        $f->label('pair', 'Vælg et par som skal indgå i din halv kvadrille');
        $f->selectAry('pair', $ary);
        
        $f->label('send');
        $f->submit('send', 'Opret');
        $f->formEnd();
        return $f->getStr();
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

    
    /**
     * Delete a 'helmember' based on session::getUserId
     * @return boolean $res result of R::thrashAll
     */
    public function dbDeleteHelMember(){
        $members = R::findAll('helmember', "user_id = ?", array (session::getUserId()));
        return R::trashAll($members);
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