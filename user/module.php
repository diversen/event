<?php

namespace modules\event\user;

use diversen\db;
use diversen\db\q;
use diversen\db\rb;
use diversen\html;
use diversen\http;
use diversen\session;

rb::connect();

class module {
    
    public function indexAction () {
        echo "Velkommen!";
    }
    public function baseAction () {
        echo $this->baseForm();
    }
    
    public function baseForm () {
        
        $f = new html();
        
        $f->formStart();
        $f->legend('Basis data - ret eller indsæt');
        
        $f->label('username', 'Dit navn');
        $f->text('username');
        
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
            $rows[$partner['id']] = $partner['username'];
        }
        
        $f->label('partner', 'Har du en partner, så vælg en fra listen');
        $f->selectAry('partner', $rows);
        
        $halv = q::select('halvkvadrille')->fetch();
        unset($rows);
        $rows[0] = 'Ingen halv kvadrille';
        foreach ($halv as $a) {
            $rows[$a['id']] = $a['name'];
        }
        
        $create = html::createLink('/event/user/create', 'opret en ny');
        $f->label('halvkvadrille', "Er du en del af en halv kvadrille? Hvis ja, så vælg en fra listen eller $create ");
        $f->selectAry('halvkvadrille', $rows);
        
        $hel = q::select('helkvadrille')->fetch();
        unset($rows);
        $rows[0] = 'Ingen hel kvadrille';
        foreach ($hel as $a) {
            $rows[$a['id']] = $a['name'];
        }
        
        $create = html::createLink('/event/user/create2', 'opret en ny');
        $f->label('helkvadrille', "Er du en del af en hel kvadrille? Hvis ja, så vælg fra listen herunder eller $create ");
        $f->selectAry('helkvadrille', $rows);
        
        
        $f->label('base');
        $f->submit('base', 'Opdater');
        
        $f->formEnd();
        
        return $f->getStr();    
    }
    
    public function createAction () {
        
        http::prg();
        
        if (isset($_POST['send'])) {
            if (empty($_POST['name'])) {
                echo html::getError('Indtast et navn');
            } else {
                $ary = db::prepareToPostArray(array('name', 'reserved'), true);
                $bean = rb::getBean('halvkvadrille');
                $bean->name = html::specialDecode($ary['name']);
                $bean->reserved = html::specialDecode($ary['reserved']);
                $bean->user = session::getUserId();
                rb::commitBean($bean);
                http::locationHeader('/event/user/base');
            }
        }
        echo $this->formCreateKvadrille();
    }
    
    public function create2Action () {
        
        http::prg();
        
        if (isset($_POST['send'])) {
            if (empty($_POST['name'])) {
                echo html::getError('Indtast et navn');
            } else {
                $ary = db::prepareToPostArray(array('name', 'reserved'), true);
                $bean = rb::getBean('helkvadrille');
                $bean->name = html::specialDecode($ary['name']);
                $bean->reserved = html::specialDecode($ary['reserved']);
                $bean->user = session::getUserId();
                rb::commitBean($bean);
                http::locationHeader('/event/user/base');
            }
        }
        echo $this->formCreateKvadrille('Opret en hel kvadrille');
    }
    
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