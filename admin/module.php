<?php

namespace modules\event\admin;

use modules\event\import;
use diversen\html;

class module {
    public function indexAction () {
        echo "Velkommen lærer";
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
