<?php

namespace Tests\AppBundle\Controller;

use M6Web\Component\RedisMock\RedisMockFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use AppBundle\Controller\WebController;
use AppBundle\Exception\UrlNotFoundException;
use AppBundle\Service\Shortener;
use AppBundle\Service\UriProvider;

class WebControllerTest extends KernelTestCase
{
    /**
     * @var Shortener
     */
    protected $shortener;

    /**
     * @var WebController
     */
    protected $controller;

    /**
     * Init a Web controller with a mocked Redis client
     */
    public function setUp()
    {
        static $factory = null;
        if ($factory === null) {
            $factory = new RedisMockFactory();
        }

        static::bootKernel();

        $redis = $factory->getAdapter('Predis\Client', true);

        $router = static::$kernel->getContainer()->get('router');
        $templating = static::$kernel->getContainer()->get('templating');
        $formFactory = static::$kernel->getContainer()->get('form.factory');
        $session = static::$kernel->getContainer()->get('session');

        $uriProvider = new UriProvider($redis, '');
        $this->shortener = new Shortener($redis, $uriProvider, 'http://localhost');
        $this->controller = new WebController($router, $templating, $formFactory, $session, $this->shortener, 'http://localhost');
    }

    public function testRedirectAction()
    {
        $this->shortener->getShortUrl('http://a.com/');

        $request = $this->getRequest('web_redirect', [], ['shortUri' => 1], ['REQUEST_URI' => '/1']);
        $response = $this->controller->redirectAction($request);

        $this->assertEquals(308, $response->getStatusCode());

        $request = $this->getRequest('web_redirect', [], ['shortUri' => 42], ['REQUEST_URI' => '/42']);
        $this->expectException(NotFoundHttpException::class);
        $response = $this->controller->redirectAction($request);
    }

    protected function getRequest($route, array $get, array $route_params = array(), array $server = array())
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
