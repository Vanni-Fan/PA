<?php

namespace web\Controllers;
use api\Models\MyTable;

class IndexController extends \Phalcon\Mvc\Controller{
    function indexAction(){
        $module = $this->dispatcher->getModuleName();
        $view_path = \PA::$config->module_path.'/'.$module.'/views/';
        echo "<pre>";
        print_r(\PA::$loader);
//        exit;
        $a = MyTable::find();
        print_r($a->toArray());
        exit;
        $this->view->setViewsDir($view_path);
        $this->view->mylist = [
            'aa'=>1,
            'bb'=>2
        ];
        $this->view->render("index", "list");
    }
}