<?php

namespace AppBundle\Event;
 
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;
 
class VisitorEvent extends Event
{
    protected $request;

    public function setRequest(Request $request)
    {
        $this->request = $request;
    }
 
    public function getRequest()
    {
        return $this->request;
    }
}
