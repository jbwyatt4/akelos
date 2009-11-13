<?php

require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class AkContionController_http_authentication_TestCase extends AkWebTestCase
{

    public function test_should_access_public_action()
    {
        $this->setMaximumRedirects(0);
        $this->get(AK_TESTING_URL.'/authentication');
        $this->assertResponse(200);
        $this->assertTextMatch('Everyone can see me!');
    }

    public function test_should_show_login_with_realm()
    {
        $this->setMaximumRedirects(0);
        $this->get(AK_TESTING_URL.'/authentication/edit');
        $this->assertRealm('App name');
        $this->assertNoText("I'm only accessible if you know the password");
    }

    public function test_should_fail_login()
    {
        $this->setMaximumRedirects(0);
        $this->get(AK_TESTING_URL.'/authentication/edit');
        $this->authenticate('bermi', 'badpass');
        $this->assertResponse(401);
        $this->assertNoText("I'm only accessible if you know the password");
    }

    public function test_should_login()
    {
        $this->setMaximumRedirects(0);
        die(AK_TESTING_URL.'/authentication/edit');
        $this->get(AK_TESTING_URL.'/authentication/edit');
        $this->authenticate('bermi', 'secret');
        $this->assertResponse(200);
        $this->assertText("I'm only accessible if you know the password");

        // still logged in?
        $this->get(AK_TESTING_URL.'/authentication/edit');
        $this->assertResponse(200);
        $this->assertText("I'm only accessible if you know the password");
    }
}

ak_test_run_case_if_executed('AkContionController_http_authentication_TestCase');

