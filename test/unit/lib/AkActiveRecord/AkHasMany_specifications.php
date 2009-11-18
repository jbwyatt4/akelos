<?php

require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class ActiveRecord_has_many_specifications_TestCase extends AkUnitTest
{
    /**
     * @hasMany    pictures, :dependent => 'destroy'
     * @var ActiveRecord
     */
    public $Property;

    /**
     * @belongsTo  property
     * @var ActiveRecord
     */
    public $Picture;
    public function setUp()
    {
        $this->rebaseAppPaths();
        $this->installAndIncludeModels(array('Property','Picture'));
    }

    public function test_deletion_from_collection_should_destroy_the_active_record()
    {
        $Property = new Property(array('description'=>'This is a Property'));
        $Picture = $Property->picture->create(array('title'=>'Front'));
        $this->assertTrue($Property->save());

        $this->assertTrue($Picture instanceof AkActiveRecord);

        $Property->picture->delete($Picture);

        $StoredProperty = $this->Property->find('first');
        $this->assertEqual($Property->getId(), $StoredProperty->getId());
        $this->assertFalse($this->Picture->find('first'));
    }

    public function test_destroying_should_cascade()
    {
        $Property = new Property(array('description'=>'This is a Property'));
        $Picture = $Property->picture->create(array('title'=>'Front'));

        $Property->destroy();

        $this->assertFalse($this->Property->find('first'));
        $this->assertFalse($this->Picture->find('first'));
    }
}

ak_test_case('ActiveRecord_has_many_specifications_TestCase');

