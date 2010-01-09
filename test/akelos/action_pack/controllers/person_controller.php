<?php

class MethodNotAllowedException extends Exception 
{ } 

class PersonController extends ApplicationController
{
    var $Request;
    
    public function index() {
        switch ($this->respondTo()) {
        	case 'xml':
        	    $this->renderXml($_SERVER);
            	break;
        	default:
        	    throw new MethodNotAllowedException();
        	    break;
        }
    }
    
    public function create() {
        $Steves_name = $this->params['person']['name'];
	    $this->renderText($Steves_name);
    }
    
    public function update() {
        $Steves_name = $this->params['person']['name'];
	    $this->renderText($Steves_name);
    }
    
    public function upload_photo() {
        $Title    = $this->params['photo']['title'];
        $Filename = $this->params['photo']['name'];
        $this->renderText("$Title|$Filename");
    }
    
    protected function renderXml($xml_string,$status=null,$location=null) {
        $this->Response->addHeader(array('content-type'=>'text/xml'));
        $this->renderText($xml_string,$status);
    }
    
}
