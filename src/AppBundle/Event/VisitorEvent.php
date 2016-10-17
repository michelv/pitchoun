<?php

namespace AppBundle\Event;
 
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

class VisitorEvent extends Event
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @param Request $request
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }
}
