<?php

namespace modules\event\admin;

use diversen\db\q;
use diversen\html;
use diversen\html\table;
use diversen\moduleloader;
use diversen\session;
use modules\event\import;
use modules\event\eDb;

class module {
    
    public function checkAccess () {
        if (!session::isAdmin()) {
            moduleloader::setStatus(403);
            return;
        }
    }
    
    
    
    public function indexAction () {
        $this->checkAccess(); 
        
        $eDb = new eDb();
        if (isset($_GET['all']) ) {
            $rows = q::select('account')->filter('admin = ', 0 )->order('username')->fetch();
            $this->displayAll($rows);
        }
        
        if (isset($_GET['par'])) {
            $rows = $eDb->getAllPairsFromDancers();
            $this->displayPairs($rows);
        }
        
        if (isset($_GET['halv'])) {
            
            $q = "SELECT * FROM halv WHERE confirmed = 1";
            $rows = q::query($q)->fetch();
            $this->displayHalve($rows);
        }
        
        if (isset($_GET['hel'])) {
            
            $q = "SELECT * FROM hel WHERE confirmed = 1";
            $rows = q::query($q)->fetch();
            $this->displayHele($rows);
        }
        
        if (isset($_GET['reg_minus'])) {
            
            echo $this->message("Brugere som er importeret, men som endnu ikke har foretaget en opdatering på sitet.");
            $q = "select * from account where id NOT IN (select user_id from dancer) AND admin = 0 ORDER by username";
            $rows = q::query($q)->fetch();
            $this->displayAll($rows);
        }
        
        if (isset($_GET['uden'])) {
            
            echo $this->message("Brugere som er importeret, men som endnu ikke har en verificeret partner.");
            $q = "SELECT * from account WHERE 'admin' != 1 AND id NOT IN (SELECT user_a from pair UNION SELECT user_b from pair);";
            $rows = q::query($q)->fetch();
            $this->displayAll($rows);
        }
    }
    
    public function message($mes) {
        echo $mes . "<br />";
    }
    
    public function displayPairs ( $rows ) {
        $str = table::tableBegin(array('class' => 'uk-table uk-table-hover uk-table-striped uk-table-condensed'));
        foreach($rows as $row) {
            $a = session::getAccount($row['user_a']);
            $b = session::getAccount($row['user_b']);
            $str.=table::trBegin();
            $str.=table::td($a['username'], array ('class' => 'uk-width-3-10'));
            $str.=table::td($b['username'], array ('class' => ''));
            $str.=table::trEnd();   
        }
        $str.=table::tableEnd();
        echo $str;
        
        
    }
    
    public function displayHalve ( $rows ) {
        $str = table::tableBegin(array('class' => 'uk-table uk-table-hover uk-table-striped uk-table-condensed'));
        foreach($rows as $row) {
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
            $str.=table::td($row['email']);
            $str.=table::trEnd();   
        }
        $str.=table::tableEnd();
        echo $str;
    }
    
    public function importAction () {
        
        $this->checkAccess(); 
        
        echo "I den følgende form kan du indsætte brugere. <br />";
        echo $this->importForm();
        
        $i = new import();
        if (isset($_POST['submit'])) {
            $ary = $i->getAryFromTxt($_POST['users']);
            $i->addToDb($ary);
        }
    }
    
    public function importForm () {
        $f = new html();
        $f->formStart();
        $f->legend('Indsæt brugere');
        $f->textarea('users');
        $f->submit('submit', 'Send');
        $f->formEnd();
        return $f->getStr();
    }
}
