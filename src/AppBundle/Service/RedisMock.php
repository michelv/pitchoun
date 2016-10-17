<?php

namespace AppBundle\Service;

use M6Web\Component\RedisMock\RedisMock as BaseRedisMock;
use PHPUnit\Framework\TestCase;
use Predis\ClientInterface;
use Predis\Command\CommandInterface;

/**
 * Fake Redis client for tests.
 * All data is stored in memory.
 *
 * @codeCoverageIgnore
 */
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
