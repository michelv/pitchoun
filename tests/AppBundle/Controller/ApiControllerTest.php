<?php

namespace Tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;

use AppBundle\Controller\ApiController;
use AppBundle\Exception\UrlNotFoundException;

class ApiControllerTest extends KernelTestCase
{
    /**
     * @var Shortener
     */
    protected $shortener;

    /**
     * @var ApiController
     */
    protected $controller;

    /**
     * Init an API controller with a mocked Redis client
     */
    public function setUp()
    {
        static::bootKernel();

        $this->shortener = static::$kernel->getContainer()->get('shortener');
        $this->controller = new ApiController($this->shortener, 'http://localhost', 'secret');
    }

    public function testShortenAction()
    {
        $request = Request::create('/api/shorten', 'GET', ['url' => 'http://a.com/']);
        $response = $this->controller->shortenAction($request);
        $first_content = $response->getContent();
        $etag = $response->headers->get('etag');

        $response = $this->controller->shortenAction($request);
        $second_content = $response->getContent();

        $this->assertEquals($first_content, $second_content);
        $this->assertNotNull($etag);

        $request = Request::create('/api/shorten', 'GET', ['url' => 'http://a.com/'], [], [], ['HTTP_If-None-Match' => $etag]);
        $response = $this->controller->shortenAction($request);

        $this->assertEquals(304, $response->getStatusCode());

        $request = Request::create('/api/shorten', 'GET', ['url' => 'http://b.com/'], [], [], ['HTTP_If-None-Match' => $etag]);
        $response = $this->controller->shortenAction($request);

        $this->assertEquals(200, $response->getStatusCode());

        $request = Request::create('/api/shorten', 'GET', ['url' => 'telnet://a.com/']);
        $response = $this->controller->shortenAction($request);

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testLengthenAction()
    {
        $this->shortener->getShortUrl('http://a.com/');

        $request = Request::create('/api/lengthen', 'GET', ['url' => 'http://localhost/1']);
        $response = $this->controller->lengthenAction($request);
        $first_content = $response->getContent();
        $etag = $response->headers->get('etag');

        $this->assertNotNull($etag);

        $request = Request::create('/api/lengthen', 'GET', ['url' => 'http://localhost/1'], [], [], ['HTTP_If-None-Match' => $etag]);
        $response = $this->controller->lengthenAction($request);

        $this->assertEquals(304, $response->getStatusCode());

        $request = Request::create('/api/lengthen', 'GET', ['url' => 'http://localhost/42'], [], [], ['HTTP_If-None-Match' => $etag]);
        $response = $this->controller->lengthenAction($request);

        $this->assertEquals(404, $response->getStatusCode());

        $request = Request::create('/api/lengthen', 'GET', ['url' => 'http://otherhost/1']);
        $response = $this->controller->lengthenAction($request);

        $this->assertEquals(400, $response->getStatusCode());
    }
}
