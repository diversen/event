<?php

namespace modules\event\user;

use diversen\db;
use diversen\db\q;
use diversen\db\rb;
use diversen\html;
use diversen\http;
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
        if (isset($_POST['submit'])) {
            
            $user_id = session::getUserId();
            
            // Update base info
            $ary = db::prepareToPost();
            $this->updateFromForm($user_id, $ary);
            http::locationHeader('/event/user/index', 'Dine data blev opdateret');

        }
        
        echo $this->formBase();
    }
    
    public function updateFromForm ($user_id, $ary) {
                
        $e = new eDb();
        R::begin();
        
        // Update base info
        $e->updateDancer($user_id, $ary);

        // Update pair
        $e->updatePairs($user_id, $ary);
        R::commit();
    }

    public function javascript () { ?>
<script>
$(document).ready(function(){
  $("#delete_partner, #delete_halv, #delete_hel").click(function(){
    if (!confirm("Do you want to delete")){
      return false;
    }
  });
});
</script>
        <?php
    }
    
    /**
     * User base form
     * @return string $html
     */
    public function formBase () {
                
        $e = new eDb();
        $partner = $e->getUserPairFromUserId(session::getUserId());

        if (!empty($partner)) {
            $this->formDeletePartner();
            $this->formHalv();

            $halv = $e->getHalvUserInvites(session::getUserId());
            
            $confirmed = $e->getHalvAllConfirmed($halv);
            if ($confirmed ) {
                $this->formHel();
            }
                
        } else {
            $this->formPartner();
        }
    }

    /**
     * form that deletes a partner. 
     * will also delete all 'halve'
     * @return type
     */
    public function formDeletePartner() {
        
        $e = new eDb();
        
        $partner_id = $e->getPairPartnerUserId(session::getUserId());

        $this->javascript();
        $user = session::getAccount($partner_id);
        echo helpers::confirmDeleteForm(
                'delete_partner', "Du har en partner: '$user[username]'", 'Ophæv partnerskab');

        if (isset($_POST['delete_partner'])) {
            
            // Update pair - delete partner
            $this->updateFromForm(session::getUserId(), array('partner' => 0));
            
            // Delete halve 
            $e->deleteHalvFromUserId(session::getUserId());
            
            // Delete hele
            $e->deleteHelFromUserId(session::getUserId());
            
            // Location
            http::locationHeader(
                    '/event/user/index', 'Skilsmisse fuldbyrdet. Du er løst fra din partner');
        }
        return;
    }

    /**
     * for for selecting a partner
     */
    public function formPartner () {
                
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
        
        
        $label = <<<EOF
Har du en partner, så vælg en fra listen. 
Når din partner har valgt dig, så udgør i et par, og i kan vælge en halv kvadrille 
EOF;
        $f->label('partner', $label);
        $f->selectAry('partner', $rows);
        
        $f->label('base');
        $f->submit('submit', 'Opdater');
        
        $f->formEnd();
        
        echo $f->getStr(); 
    }
    
        
    /**
     * form which will confirm other pair in 'halv'
     * @param type $id
     * @return type
     */
    public function formConfirmHalv($id) {
      
        echo helpers::confirmDeleteForm(
                'confirm_halv', "", 'Bekræft halv kvadrille', $id);

    }
    
    /**
     * form which will confirm other pair in 'hel'
     * @param type $id
     * @return type
     */
    public function formConfirmHel($id) {
        echo helpers::confirmDeleteForm(
                'confirm_hel', "", 'Bekræft hel kvadrille', $id);

    }


    public function formHel () {
        $e = new eDb();
        
        // Get halv invite
        $hel = $e->getHelUserInvites(session::getUserId());

        if (isset($_POST['delete_hel'])) {
            
            // Delete hele
            $e->deleteHelFromUserId(session::getUserId());
            http::locationHeader('/event/user/index', 'Den halve kvadrille blev slettet');
        }
        
        if (isset($_POST['confirm_hel'])) {
            $e->confirmHelMembers($hel['hel_id']);
            http::locationHeader('/event/user/index', 'Den halve kvadrille blev bekræftet');
        }
                
        echo "<h3>Hel kvadrille</h3>";
        
        // Inviteret til at deltage i en halv
        $hel = $e->getHelUserInvites(session::getUserId());
        if (!empty($hel)) {

            $user = $e->getSingleUserFromHel($hel['id'], session::getUserId());
            $hel_str = $e->getUsersStrFromHel($hel['id']);
            $all_confirmed = $e->getHelAllConfirmed($hel['id']);
            
            if ($user['confirmed'] == 0) {
$confirm_mes = <<<EOF
Du er en del af en <b>ubekræftet</b> hel kvadrille. <br />
<b>$hel_str</b>
Du og din partner har endnu ikke bekræftet. Vælg bekræft eller
slet den halve kvadrille.
EOF;
                echo $confirm_mes;
                $this->formConfirmHel($hel['id']);
            } else {
                $message = <<<EOF
Du er en del af en hel kvadrille. <br />
<b>$hel_str</b>
Det kan være din partner som har valgt dig ind.<br />
Hvis du mener at det er fejl kan du slette den hele kvadrille.
                        
EOF;
                
                
                if (!$all_confirmed) {
                    $message.= '<br />Jeres hel-kvadrille partnere har <b>endnu ikke</b> bekræftet!';
                } else {
                    $message.= '<br />Jeres hel-kvadrille partnere har bekræftet!';
                }
                echo $message;
            }

            echo helpers::confirmDeleteForm(
                'delete_hel', "", 'Ophæv hel kvadrille', $hel['id']);
            return;
        }

        $label = <<<EOF
Du og din halve kvadrille er endnu ikke en del af en hel kvadille
Hvis i har en aftale med en anden halv kvadrille, så kan i forme en
kvadrille. Det anden kvadrille-del skal efterfølgende bekræfte kvadrillen. 
EOF;
        $label.= html::createLink('/event/user/hel', 'Opret en ny');
        echo $label; 
        return;
    }
    
    /**
     * Form for creating a halv
     * @return type
     */
    public function formHalv() {
        
        $e = new eDb();
        
        // Get halv invite
        $halv = $e->getHalvUserInvites(session::getUserId());
        
        if (isset($_POST['delete_halv'])) {
            
            // Delete halve 
            $e->deleteHalvFromUserId(session::getUserId());
            
            // Delete hele
            $e->deleteHelFromUserId(session::getUserId());
            
            http::locationHeader('/event/user/index', 'Den halve kvadrille blev slettet');
            
        }
        
        if (isset($_POST['confirm_halv'])) {
            $e->confirmHalvMembers($halv['halv_id']);
            http::locationHeader('/event/user/index', 'Den halve kvadrille blev bekræftet');
        }

        
        
        
        
        echo "<h3>Halv kvadrille</h3>";
        
        // Inviteret til at deltage i en halv
        $halv = $e->getHalvUserInvites(session::getUserId());
        if (!empty($halv)) {

            $user = $e->getSingleUserFromHalv($halv['id'], session::getUserId());
            $halv_str = $e->getUsersStrFromHalv($halv['id']);
            $all_confirmed = $e->getHalvAllConfirmed($halv);
            
            if ($user['confirmed'] == 0) {
$confirm_mes = <<<EOF
Du er en del af en <b>ubekræftet</b> halv kvadrille. <br />
<b>$halv_str</b>
Du og din partner har endnu ikke bekræftet. Vælg bekræft eller
slet den halve kvadrille.
EOF;
                echo $confirm_mes;
                $this->formConfirmHalv($halv['id']);
            } else {
                $message = <<<EOF
Du er en del af en halv kvadrille. <br />
<b>$halv_str</b>
Det kan være din partner som har valgt dig ind.<br />
Hvis du mener at det er fejl kan du slette den halve kvadrille.
                        
EOF;
                
                
                if (!$all_confirmed) {
                    $message.= '<br />Jeres halv-kvadrille partnere har <b>endnu ikke</b> bekræftet!';
                } else {
                    $message.= '<br />Jeres halv-kvadrille partnere har bekræftet!';
                }
                echo $message;
            }

            echo helpers::confirmDeleteForm(
                'delete_halv', "", 'Ophæv halv kvadrille', $halv['id']);
            return;
        }

        $label = <<<EOF
Du og din partner er endnu ikke en del af en halv kvadille
Hvis i har en aftale med et par, så kan et af parene forme en halv
kvadrille. Det andet par skal efterfølgende bekræfte den halve kvadrille. 
EOF;
        $label.= html::createLink('/event/user/halv', 'Opret en ny');
        
        echo $label; 
        return;

        
    }
    
    
    
    /**
     * var holding form submission errors
     * @var array $errors
     */
    public $errors = array ();
    
    public function validateHalv () {
        if ($_POST['pair'] == 0) {
            $this->errors[] = 'Du skal vælge et par som skal indgå i din halv kvadrille';
        }       
    }
    
    public function validateHel () {
        if ($_POST['halv'] == 0) {
            $this->errors[] = 'Du skal vælge en halv kvadrille, som skal indgå i din kvadrille';
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
                    
                // Prepare
                $ary = db::prepareToPostArray(array('pair'), true);
                
                // Delete other halve
                $eDb->deleteHalvFromUserId(session::getUserId());
                
                // Create
                $id = $eDb->createHalv($ary);
                
                // Set a better name
                $name = $eDb->getUsersStrFromHalv($id);
                $bean = rb::getBean('halv', 'id', $id);
                $bean->name = $name;
                R::store($bean);
                
                http::locationHeader('/event/user/index');
            } else {
                echo html::getErrors($this->errors);
            }
        }
        echo $this->formCreateHalv();
    }
    
    /**
     * /event/user/halv
     */
    public function helAction () {
        $this->checkAccess();
        
        $eDb = new eDb();
        $halv = $eDb->getUserHalvFromUserId(session::getUserId());
        
        if (empty($halv)) {
            http::locationHeader('/event/user/index', 'Du skal være del af en halv kvadrille for at oprette en hel');
        }
        
        
        http::prg();
        if (isset($_POST['send'])) {
            $this->validateHel();
            if (empty($this->errors)) {
                    
                // Prepare
                $ary = db::prepareToPostArray(array('halv'), true);
                
                // Delete other hele
                $eDb->deleteHelFromUserId(session::getUserId());
                
                // Create
                $id = $eDb->createHel($ary);
                
                // Set a better name
                $name = $eDb->getUsersStrFromHel($id);
                $bean = rb::getBean('hel', 'id', $id);
                $bean->name = $name;
                R::store($bean);
                
                http::locationHeader('/event/user/index');
            } else {
                echo html::getErrors($this->errors);
            }
        }
        echo $this->formCreateHel();
    }
    
    /**
     * Form that creates a kvadrille
     * @param string $title
     * @return string $html
     */
    public function formCreateHalv ($title = 'Opret en halv kvadrille') {
        $f = new html();
        $f->init(array(), 'send', true);
        $f->formStart();
        $f->legend($title);
        //$f->label('name','Indtast et navn');
        //$f->text('name');
        
        $h = new eHelpers();
        $ary = $h->getFormPairsAry();
        
        $f->label('pair', 'Vælg et par som skal indgå i din halv kvadrille');
        $f->selectAry('pair', $ary);
        
        $f->label('send');
        $f->submit('send', 'Opret');
        $f->formEnd();
        return $f->getStr();
    }
    
        /**
     * Form that creates a kvadrille
     * @param string $title
     * @return string $html
     */
    public function formCreateHel ($title = 'Opret en hel kvadrille') {
        $f = new html();
        $f->init(array(), 'send', true);
        $f->formStart();
        $f->legend($title);
        //$f->label('name','Indtast et navn');
        //$f->text('name');
        
        $h = new eHelpers();
        $ary = $h->getFormHalveAry();
        
        $f->label('halv', 'Vælg en halv kvadrille som skal indgå i din kvadrille');
        $f->selectAry('halv', $ary);
        
        $f->label('send');
        $f->submit('send', 'Opret');
        $f->formEnd();
        return $f->getStr();
    }
    
    
    public function deletehalvAction () {
        echo helpers::confirmDeleteForm('delete', 'Slet halv kvadrille');
        
        if (isset($_POST['delete'])) {
            $eDb = new eDb();
            $eDb->deleteHalvFromUserId(session::getUserId());
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