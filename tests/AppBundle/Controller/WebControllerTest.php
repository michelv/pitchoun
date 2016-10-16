<?php

namespace Tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use AppBundle\Controller\WebController;
use AppBundle\Exception\UrlNotFoundException;

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
        static::bootKernel();

        $router = static::$kernel->getContainer()->get('router');
        $templating = static::$kernel->getContainer()->get('templating');
        $formFactory = static::$kernel->getContainer()->get('form.factory');
        $event_dispatcher = static::$kernel->getContainer()->get('event_dispatcher');
        $this->session = static::$kernel->getContainer()->get('session');
        $this->shortener = static::$kernel->getContainer()->get('shortener');
        $this->controller = new WebController($router, $templating, $formFactory, $event_dispatcher, $this->session, $this->shortener, 'http://localhost');
    }

    public function testIndexAction()
    {
        $this->shortener->getShortUrl('http://a.com/');

        $request = Request::create('/', 'GET');
        $response = $this->controller->indexAction($request);

        $this->assertFalse(strpos($response->getContent(), '<div id="result"'));

        $request = Request::create('/', 'GET', ['uri' => '/1', 'original' => 'http://a.com/']);
        $response = $this->controller->indexAction($request);

        $this->assertNotFalse(strpos($response->getContent(), '<div id="result"'));
    }

    public function testShortenAction()
    {
    }

    public function testShortenAlreadyShortenedUrl()
    {
    }

    public function testShortenWithoutPost()
    {
        $request = Request::create('/', 'POST');
        $response = $this->controller->shortenAction($request);

        $this->assertEquals(1, count($this->session->getFlashBag()->get('error')));
    }

    public function testRedirectAction()
    {
        $this->shortener->getShortUrl('http://a.com/');

        $request = Request::create('/1', 'GET');
        $response = $this->controller->redirectAction($request);

        $this->assertEquals(308, $response->getStatusCode());

        $request = Request::create('/42', 'GET');
        $this->expectException(NotFoundHttpException::class);
        $response = $this->controller->redirectAction($request);
    }
}
