<?php

namespace Tests\AppBundle\Controller;

use M6Web\Component\RedisMock\RedisMockFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;

use AppBundle\Controller\ApiController;
use AppBundle\Exception\UrlNotFoundException;
use AppBundle\Service\Shortener;
use AppBundle\Service\UriProvider;

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
        static $factory = null;
        if ($factory === null) {
            $factory = new RedisMockFactory();
        }

        $redis = $factory->getAdapter('Predis\Client', true);

        $uriProvider = new UriProvider($redis, '');
        $this->shortener = new Shortener($redis, $uriProvider, 'http://localhost');
        $this->controller = new ApiController($this->shortener, 'http://localhost', 'secret');
    }

    public function testShortenAction()
    {
        $request = $this->getRequest('api_shorten', ['url' => 'http://a.com/']);
        $response = $this->controller->shortenAction($request);
        $first_content = $response->getContent();
        $etag = $response->headers->get('etag');

        $response = $this->controller->shortenAction($request);
        $second_content = $response->getContent();

        $this->assertEquals($first_content, $second_content);
        $this->assertNotNull($etag);

        $request = $this->getRequest('api_shorten', ['url' => 'http://a.com/'], [], ['HTTP_If-None-Match' => $etag]);
        $response = $this->controller->shortenAction($request);

        $this->assertEquals(304, $response->getStatusCode());

        $request = $this->getRequest('api_shorten', ['url' => 'http://b.com/'], [], ['HTTP_If-None-Match' => $etag]);
        $response = $this->controller->shortenAction($request);

        $this->assertEquals(200, $response->getStatusCode());

        $request = $this->getRequest('api_shorten', ['url' => 'telnet://a.com/']);
        $response = $this->controller->shortenAction($request);

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testLengthenAction()
    {
        $this->shortener->getShortUrl('http://a.com/');

        $request = $this->getRequest('api_lengthen', ['url' => 'http://localhost/1']);
        $response = $this->controller->lengthenAction($request);
        $first_content = $response->getContent();
        $etag = $response->headers->get('etag');

        $this->assertNotNull($etag);

        $request = $this->getRequest('api_lengthen', ['url' => 'http://localhost/1'], [], ['HTTP_If-None-Match' => $etag]);
        $response = $this->controller->lengthenAction($request);

        $this->assertEquals(304, $response->getStatusCode());

        $request = $this->getRequest('api_lengthen', ['url' => 'http://localhost/42'], [], ['HTTP_If-None-Match' => $etag]);
        $response = $this->controller->lengthenAction($request);

        $this->assertEquals(404, $response->getStatusCode());

        $request = $this->getRequest('api_lengthen', ['url' => 'http://otherhost/1']);
        $response = $this->controller->lengthenAction($request);

        $this->assertEquals(400, $response->getStatusCode());
    }

    protected function getRequest($route, array $get, array $route_params = array(), $server = array())
    {
        $server = array_merge([
            'SERVER_NAME' => 'localhost',
            'HOST' => 'localhost',
        ], $server);
        $attributes = [
            '_route_params' => $route_params,
            '_route' => $route,
        ];

        return new Request($get, $post = array(), $attributes, $cookies = array(), $files = array(), $server, $content = null);
    }
}
