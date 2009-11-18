<?php

require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class AkReflectionFunction_TestCase extends AkUnitTest
{
    public function test_string_constructor()
    {
        $string ='
            /**
             * comment
             * @return void
             * @param $param1
             * @param $param2
             */
            public function &method2($param1,$param2) {

            }
        }';
        $func = new AkReflectionFunction($string);
        $this->assertEqual('method2',$func->getName());

    }

    public function test_array_constructor()
    {
        $string ='
            /**
             * comment
             * @return void
             * @param $param1
             * @param $param2
             */
            public function &method2($param1,$param2) {
                $default_options = array("test"=>1,
                                         "test2"=>3,
                                         "test3"=>$this->value);
                $available_options = array("test","test2","test3","test4");
                echo $default_options;
                exit();
            }
        }';
        $func = new AkReflectionFunction($string);
        $func = new AkReflectionFunction($func->getDefinition());
        $this->assertEqual('method2',$func->getName());
        $this->assertEqual(array('test'=>1,'test2'=>3,'test3'=>'$this->value'),$func->getDefaultOptions());
        $this->assertEqual(array('test','test2','test3','test4'),$func->getAvailableOptions());

    }

    public function test_add_doc_block_tag()
    {
        $string ='
            /**
             * comment
             * @return void
             * @param $param1
             * @param $param2
             */
            public function &method2($param1,$param2) {
                $default_options = array("test"=>1,
                                         "test2"=>3,
                                         "test3"=>$this->value);
                $available_options = array("test","test2","test3","test4");
                echo $default_options;
                exit();
            }
        }';
        $func = new AkReflectionFunction($string);

        $func->setTag('WingsPluginInstaller','test');
        //var_dump($func->toString());

    }
}

ak_test_case('AkReflectionFunction_TestCase');

