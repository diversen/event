<?php

namespace modules\event\admin;

use diversen\db\q;
use diversen\html;
use diversen\html\table;
use diversen\moduleloader;
use diversen\session;
use modules\event\import;

class module {
    
    public function checkAccess () {
        if (session::isAdmin()) {
            moduleloader::setStatus(403);
            return;
        }
    }
    
    public function indexAction () {
        $this->checkAccess(); 
        if (isset($_GET['all']) ) {
            $this->displayAll();
        }
    }
    
    public function displayAll () {
        $rows = q::select('account')->filter('admin = ', 0 )->fetch();
        
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
