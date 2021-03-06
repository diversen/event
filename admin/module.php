<?php

namespace modules\event\admin;

use diversen\db\q;
use diversen\html;
use diversen\html\table;
use diversen\moduleloader;
use diversen\session;
use modules\event\import;
use modules\event\eDb;
use diversen\prg;
use diversen\http;

class module {
    
    public function checkAccess () {
        if (!session::isAdmin()) {
            moduleloader::setStatus(403);
            return false;
        }
        return true;
    }
    
    public function deleteAction () {
        
        if (!$this->checkAccess()) {
            return;
        } 
        
 	q::delete('account')->filter('username != ', 'admin')->exec();

 	q::delete('hel')->exec();
 	q::delete('helmember')->exec();
 	q::delete('halv')->exec();
 	q::delete('halvmember')->exec();
 	q::delete('dancer')->exec();
 	q::delete('pair')->exec();
 	q::delete('allowed')->exec();
 	q::delete('account_sub')->exec();

    }   
    
    public function indexAction () {
        
        if (!$this->checkAccess()) {
            return;
        } 
        
        $eDb = new eDb();
        if (isset($_GET['all']) ) {
            $rows = q::select('account')->filter('admin = ', 0 )->order('tag, username')->fetch();
            $this->displayAll($rows);
        }
        
        if (isset($_GET['par'])) {
            $rows = $eDb->getAllPairsFromPairs();
            $this->displayPairs($rows);
        }
        
        if (isset($_GET['par_loose'])) {
            $rows = $eDb->getAllPairsNotInHalve();
            $this->displayPairs($rows);
        }
        
        if (isset($_GET['halv'])) {
            $q = "SELECT * FROM halv WHERE confirmed = 1";
            $rows = q::query($q)->fetch();
            $this->displayHalve($rows);
        }
        
        if (isset($_GET['halv_loose'])) {
            $rows = $eDb->getAllHalveNotInHele(session::getUserId());
            // $rows = q::query($q)->fetch();
            $this->displayHalve($rows);
        }
        
        if (isset($_GET['hel'])) {
            
            $q = "SELECT * FROM hel WHERE confirmed = 1";
            $rows = q::query($q)->fetch();
            $this->displayHele($rows);
        }
        
        if (isset($_GET['reg_minus'])) {
            
            echo $this->message("Brugere som er importeret, men som endnu ikke har foretaget en opdatering på sitet.");
            $q = "SELECT * from account WHERE id NOT IN (select user_id from dancer) AND admin = 0 ORDER by tag, username";
            $rows = q::query($q)->fetch();
            $this->displayAll($rows);
        }
        
        if (isset($_GET['uden'])) {
            
            echo $this->message("Brugere som er importeret og har foretaget en opdatering på sitet, men som endnu ikke har en verificeret partner.");
            $q = "SELECT * from account WHERE `admin` = 0 AND "
                    . "id NOT IN (SELECT user_a from pair UNION SELECT user_b from pair) AND "
                    . "id IN (SELECT user_id FROM dancer)"
                    . "ORDER by tag, username";
            $rows = q::query($q)->fetch();
            $this->displayAll($rows);
        }
    }
    
    public function message($mes) {
        echo $mes . "<br />";
    }

    public function getUserTagStr ($row) {
        $row = html::specialEncode($row);
        return $row['username'] . " ($row[tag])";
    }

    /**
     * Display pairs as a HTML table
     * @param array $rows
     */
    public function displayPairs ( $rows ) {
        $str = table::tableBegin(array('class' => 'uk-table uk-table-hover uk-table-striped uk-table-condensed'));
        foreach($rows as $row) {
            $a = session::getAccount($row['user_a']);
            $b = session::getAccount($row['user_b']);
            $str.=table::trBegin();
            $str.=table::td($this->getUserTagStr($a), array ('class' => 'uk-width-3-10'));
            $str.=table::td($this->getUserTagStr($b), array ('class' => ''));
            $str.=table::trEnd();   
        }
        $str.=table::tableEnd();
        echo $str;
    }

    
    public function displayHalve ( $rows ) {
        $str = table::tableBegin(array('class' => 'uk-table uk-table-hover uk-table-striped uk-table-condensed'));
        foreach($rows as $row) {
            $row = html::specialEncode($row);
            $str.=table::trBegin();
            $str.=table::td($row['name']);
            $str.=table::trEnd();   
        }
        $str.=table::tableEnd();
        echo $str;  
    }
    
    public function displayHele ( $rows ) {
        $str = table::tableBegin(array('class' => 'uk-table uk-table-hover uk-table-striped uk-table-condensed'));
        foreach($rows as $row) {
            $str.=table::trBegin();
            $str.=table::td($row['name']);
            $str.=table::trEnd();   
        }
        $str.=table::tableEnd();
        echo $str;
        
    }
    
    public function displayAll ($rows) {
 
        $str = table::tableBegin(array('class' => 'uk-table uk-table-hover uk-table-striped uk-table-condensed'));
        foreach($rows as $row) {
            $str.=table::trBegin();
            $str.=table::td($row['username'], array ('class' => 'uk-width-3-10'));
            $str.=table::td($row['tag']);
            $str.=table::td($row['email']);
            
            // Comment
            $dancer = q::select('dancer')->filter('user_id= ', $row['id'])->fetchSingle();
            if (!empty($dancer)) {
                $str.=table::td(html::specialEncode($dancer['comment']));
            } else {
                $str.=table::td('');
            }
            
            $str.=table::trEnd();   
        }
        $str.=table::tableEnd();
        echo $str;
    }
    
    private $errors = [];
    
    public function validate() {
        if (empty($_POST['tag'])) {
            $this->errors[] = 'Indtast et tag, fx 3y';
        }
    }
    
    public function importAction () {
        
        prg::prg();
        
        if (!$this->checkAccess()) {
            return;
        } 
        
        echo "I den følgende form kan du indsætte brugere. <br />";
        
        $i = new import();
        if (isset($_POST['submit'])) {
            
            $this->validate();
            if (!empty($this->errors)) {
                echo html::getErrors($this->errors);
            } else {
                $_POST['users'] = html::specialDecode($_POST['users']);
                $ary = $i->getAryFromTxt($_POST['users']);
                $i->addToDb($ary, $_POST['tag']);
                session::setActionMessage('Brugere tilføjet');
                http::locationHeader('/event/admin/import');

            }   
            
        }

        echo $this->helpImport();
        echo $this->importForm();
    }
    
    public function importForm () {
        $f = new html();
        $f->init([], 'submit', true);
        $f->formStart();
        
        $f->legend('Indsæt brugere');
        $f->label('tag', 'Indtast et tag, fx klasse');
        $f->text('tag');
        $f->label('users', 'Indsæt brugere:');
        $f->textarea('users');
        $f->submit('submit', 'Send');
        $f->formEnd();
        return $f->getStr();
    }

    public function helpImport() {
        $str = <<<EOF

Formattet er som følger: 
"Elevens navn" <email@apps.egaa-gym.dk>, "En anden elev" <email2@apps.egaa-gym.dk>, 
EOF;
        return nl2br(html::specialEncode($str));

    }
}
