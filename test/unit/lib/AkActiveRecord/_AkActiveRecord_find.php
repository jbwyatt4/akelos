<?php

require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class ActiveRecord_find_TestCase extends AkUnitTest
{
    public $Hybrid;

    public function setUp()
    {
        $this->rebaseAppPaths();
        $this->installAndIncludeModels(array('Hybrid'=>'id,name'));
        Mock::generate('AkDbAdapter');
        $Db = new MockAkDbAdapter();
        $Db->setReturnValue('select',array());
        $this->Db = $Db;
        $this->Hybrid->setConnection($Db);
    }

    public function test_find_all()
    {
        $this->Db->expectAt(0,'select',array('SELECT * FROM hybrids','selecting'));
        $this->Hybrid->find('all');
    }

    public function test_add_group_by_clause()
    {
        $this->Db->expectAt(0,'select',array('SELECT * FROM hybrids GROUP BY id','selecting'));
        $this->Hybrid->find('all',array('group'=>'id'));
    }

}

ak_test_case('ActiveRecord_find_TestCase');

