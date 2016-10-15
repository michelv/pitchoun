<?php

namespace Tests\AppBundle\Service;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use AppBundle\Exception\UrlNotFoundException;

class ShortenerTest extends KernelTestCase
{
    /**
     * @param $calls
     */
    protected function getRedisClientMock(Array $calls)
    {

    }

    /**
     * @dataProvider providerTestGetShortUrl
     */
    public function testGetShortUrl(Array $calls)
    {

    }

    /**
     * @dataProvider providerTestGetFromLongUrl
     */
    public function testGetFromLongUrl(Array $calls)
    {

    }

    public function testUrlNotFound()
    {
        // $this->expectException(UrlNotFoundException::class);
    }

    public function providerTestGetShortUrl()
    {
        return array(
            array(
                array(),
            ),
        );
    }

    public function providerTestGetFromLongUrl()
    {
        return array(
            array(
                array(),
            ),
        );
    }
}
