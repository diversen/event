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
        
        $db = new db();
        if (isset($_GET['all']) ) {
            $rows = q::select('account')->filter('admin = ', 0 )->order('username')->fetch();
            $this->displayAll($rows);
        }
        
        if (isset($_GET['par'])) {
            $eDb = new eDb();
            $eDb->getAllPairs();
            $this->displayPairs($rows);
        }
        
        if (isset($_GET['reg_minus'])) {
            $q = "select * from account where id NOT IN (select user_id from dancer) AND admin = 0 ORDER by username";
            $rows = q::query($q)->fetch();
            $this->displayAll($rows);
        }
        
        if (isset($_GET['uden'])) {
            $q = "select * from account where id IN (select user_id from dancer where partner = 0) AND admin = 0 ORDER by username";
            $rows = q::query($q)->fetch();
            $this->displayAll($rows);
        }
    }
    
    public function displayPairs ( $rows ) {
        $str = table::tableBegin(array('class' => 'uk-table uk-table-hover uk-table-striped uk-table-condensed'));
        foreach($rows as $row) {
            $a = session::getAccount($row['a']);
            $b = session::getAccount($row['b']);
            $str.=table::trBegin();
            $str.=table::td($a['username'], array ('class' => 'uk-width-3-10 uk-text-bold'));
            $str.=table::td($b['username'], array ('class' => 'uk-text-bold'));
            //$str.=table::td($a['email']);
            $str.=table::trEnd();   
        }
        $str.=table::tableEnd();
        echo $str;
        
        
    }
    
    
    public function displayAll ($rows) {
 
        $str = table::tableBegin(array('class' => 'uk-table uk-table-hover uk-table-striped uk-table-condensed'));
        foreach($rows as $row) {
            $str.=table::trBegin();
            $str.=table::td($row['username'], array ('class' => 'uk-width-3-10 uk-text-bold'));
            $str.=table::td($row['email']);
            $str.=table::trEnd();   
        }
        $str.=table::tableEnd();
        echo $str;
    }
    
    public function importAction () {
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
