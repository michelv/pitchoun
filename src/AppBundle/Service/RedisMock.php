<?php

namespace AppBundle\Service;

use M6Web\Component\RedisMock\RedisMock as BaseRedisMock;
use Predis\ClientInterface;
use Predis\Command\CommandInterface;

class RedisMock extends BaseRedisMock implements ClientInterface
{
    public function connect()
    {
    }

    public function disconnect()
    {
    }

    public function getConnection()
    {
    }

    public function getOptions()
    {
    }

    public function getProfile()
    {
    }

    public function createCommand($method, $arguments = array())
    {
    }

    public function executeCommand(CommandInterface $command)
    {
    }

    public function __call($method, $arguments)
    {
    }
}
