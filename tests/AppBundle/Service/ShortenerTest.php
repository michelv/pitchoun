<?php

namespace Tests\AppBundle\Service;

use M6Web\Component\RedisMock\RedisMockFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

use AppBundle\Exception\UrlAlreadyShortenedException;
use AppBundle\Exception\UrlNotFoundException;
use AppBundle\Service\Shortener;
use AppBundle\Service\UriProvider;

class ShortenerTest extends KernelTestCase
{
    /**
     * @var Shortener
     */
    protected $shortener;

    /**
     * Init a shortener with a mocked Redis client
     */
    public function setUp()
    {
        static $factory = null;
        if ($factory === null) {
            $factory = new RedisMockFactory();
        }

        $redis = $factory->getAdapter('Predis\Client', true);

        $uriProvider = new UriProvider($redis, '');
        $this->shortener = new Shortener($redis, $uriProvider, 'http://localhost');
    }

    public function testGetShortUrl()
    {
        $url = $this->shortener->getShortUrl('http://a.com/');
        $url_no_path = $this->shortener->getShortUrl('http://a.com');
        $url_uppercase_scheme = $this->shortener->getShortUrl('HTTP://a.com/');
        $url_no_scheme = $this->shortener->getShortUrl('a.com/');
        $url_no_scheme_no_path = $this->shortener->getShortUrl('a.com');
        $url_long_path = $this->shortener->getShortUrl('http://a.com/never/gonna/give/you/up');
        $url_no_scheme_long_path = $this->shortener->getShortUrl('a.com/never/gonna/give/you/up');
        $url_https = $this->shortener->getShortUrl('https://a.com/');
        $url_idn = $this->shortener->getShortUrl('http://chezmémé.com/');
        $url_idn_encoded = $this->shortener->getShortUrl('http://xn--chezmm-fvab.com/');
        $other_url = $this->shortener->getShortUrl('http://b.com/');

        $this->assertEquals($url, $url_uppercase_scheme);
        $this->assertEquals($url, $url_no_scheme);
        $this->assertNotEquals($url, $url_no_path);
        $this->assertNotEquals($url_no_scheme, $url_no_scheme_no_path);
        $this->assertEquals($url_long_path, $url_no_scheme_long_path);
        $this->assertNotEquals($url_https, $url_no_scheme);
        $this->assertEquals($url_idn, $url_idn_encoded);
        $this->assertNotEquals($url, $other_url);
    }

    public function testGetFromShortUri()
    {
        $url1 = $this->shortener->getShortUrl('http://a.com/');
        $url2 = $this->shortener->getFromShortUri($url1->shortUri);

        $this->assertEquals($url1, $url2);

        $url1 = $this->shortener->getShortUrl('b.com/');
        $url2 = $this->shortener->getFromShortUri($url1->shortUri);

        $this->assertEquals($url1, $url2);

        $url1 = $this->shortener->getShortUrl('http://a.com/#fragment');
        $url2 = $this->shortener->getFromShortUri($url1->shortUri);
        $parts = parse_url($url2->original);

        $this->assertEquals('fragment', $parts['fragment']);
    }

    public function testUrlAlreadyShortened()
    {
        $url = $this->shortener->getShortUrl('http://a.com/');

        $this->expectException(UrlAlreadyShortenedException::class);
        $url = $this->shortener->getShortUrl($url->short);
    }

    public function testUrlNotFound()
    {
        $this->expectException(UrlNotFoundException::class);
        $url = $this->shortener->getFromShortUri('/youpi');
    }

    /**
     * @dataProvider providerTestSanitizeUrl
     */
    public function testSanitizeUrl($originalUrl, $expectedUrl)
    {
        $url = $this->shortener->getShortUrl($originalUrl);
        $this->assertEquals($expectedUrl, $url->original);
    }

    public function providerTestSanitizeUrl()
    {
        return array(
            array('http://a.com/', 'http://a.com/'),
            array('http://user:pass@a.com/', 'http://user:pass@a.com/'),
            array('http://user@a.com/', 'http://user:@a.com/'),
            array('a.com/', 'http://a.com/'),
            array('http://a.com:8000/', 'http://a.com:8000/'),
        );
    }

    /**
     * @dataProvider providerTestInvalidUrl
     */
    public function testInvalidUrl($originalUrl)
    {
        $this->expectException(\InvalidArgumentException::class);
        $url = $this->shortener->getShortUrl($originalUrl);
    }

    public function providerTestInvalidUrl()
    {
        return array(
            array('telnet://never.com'),
            array('urn:gonna:let'),
            array('/you'),
            array('#down'),
            array(''),
            array('!'),
            array('-'),
            array(':42'),
        );
    }
}
