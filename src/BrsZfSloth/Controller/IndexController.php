<?php
namespace BrsZfSloth\Controller;

use Zend\Mvc\Controller\AbstractActionController;
//use Zend\View\Model\ViewModel;

// TODO

class GeneratorController extends AbstractActionController
{
    public function indexAction()
    {
        // return new ViewModel();
    }

    public function timeAction(){
        return 'Current time: '.date('H:i:s T');
    }

    public function exceptionAction(){
        throw new \Exception('Boom! An example exception. ');
    }
}