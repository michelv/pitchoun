<?php

namespace Tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use AppBundle\Controller\WebController;
use AppBundle\Exception\UrlNotFoundException;

class WebControllerTest extends WebTestCase
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

    protected function fillShortenForm($client, $url)
    {
        $crawler = $client->request('GET', '/');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $crawler->selectButton('Minify')->form();
        $form->setValues(array('minify[url]' => $url));
        $crawler = $client->submit($form);
        $response = $client->getResponse();
        $this->assertTrue($response->isRedirect());

        $crawler = $client->request('GET', $response->getTargetUrl());

        return $crawler;
    }

    public function testShortenAction()
    {
        $client = static::createClient();

        $crawler = $this->fillShortenForm($client, 'http://a.com/');

        $this->assertGreaterThan(0, $crawler->filter('div#result')->count());
    }

    public function testShortenAlreadyShortenedUrl()
    {
        $client = static::createClient();

        $crawler = $this->fillShortenForm($client, 'http://localhost/42');

        $this->assertEquals(0, $crawler->filter('div#result')->count());
        $this->assertGreaterThan(0, $crawler->filter('div.flash-error')->count());
        $this->assertGreaterThan(0, $crawler->filter('html:contains("This URL is already shortened.")')->count());
    }

    public function testShortenInvalidUrl()
    {
        $client = static::createClient();

        $crawler = $this->fillShortenForm($client, 'urn:test');

        $this->assertEquals(0, $crawler->filter('div#result')->count());
        $this->assertGreaterThan(0, $crawler->filter('div.flash-error')->count());
        $this->assertGreaterThan(0, $crawler->filter('html:contains("Invalid URL.")')->count());
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
