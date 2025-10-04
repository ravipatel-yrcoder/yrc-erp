<?php
class Scripts_TestController extends TinyPHP_Controller
{
    public function testAction()
    {
        $this->setNoRenderer(true);
        phpinfo();       
    }   
}