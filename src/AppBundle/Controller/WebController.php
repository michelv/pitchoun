<?php

namespace AppBundle\Controller;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Templating\EngineInterface;

use AppBundle\Exception\UrlNotFoundException;
use AppBundle\Form\MinifyType;
use AppBundle\Service\Shortener;

class WebController
{
    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var EngineInterface
     */
    protected $templating;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var Shortener
     */
    protected $shortener;

    /**
     * @var string
     */
    protected $baseRedirectionUrl;

    /**
     * @param RouterInterface $router
     * @param EngineInterface $templating
     * @param FormFactoryInterface $formFactory
     * @param SessionInterface $session
     * @param Shortener $shortener
     */
    public function __construct(RouterInterface $router, EngineInterface $templating, FormFactoryInterface $formFactory, SessionInterface $session, Shortener $shortener, $baseRedirectionUrl)
    {
        $this->router = $router;
        $this->templating = $templating;
        $this->formFactory = $formFactory;
        $this->session = $session;
        $this->shortener = $shortener;
        $this->baseRedirectionUrl = $baseRedirectionUrl;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $form = $this->formFactory->create(MinifyType::class);

        return $this->templating->renderResponse('web/index.html.twig', [
            'form' => $form->createView(),
            'uri' => $request->get('uri'),
            'original' => $request->get('original'),
            'baseRedirectionUrl' => $this->baseRedirectionUrl,
        ]);
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function shortenAction(Request $request)
    {
        $form = $this->formFactory->create(MinifyType::class);
        $form->handleRequest($request);
        $params = [];

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            try {
                $shortUrl = $this->shortener->getShortUrl($data['url']);
                $params = ['uri' => $shortUrl->shortUri, 'original' => $shortUrl->original];
            } catch (\InvalidArgumentException $e) {
                $this->session->getFlashBag()->add('error', 'Invalid URL.');
            }
        } else {
            $this->session->getFlashBag()->add('error', 'Hidden monsters have eaten your request. Please try again.');
        }

        return new RedirectResponse($this->router->generate('web_index', $params), 302);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function redirectAction(Request $request)
    {
        try {
            $url = $this->shortener->getFromShortUri(rtrim($request->getPathInfo(), '/'));
        } catch (UrlNotFoundException $e) {
            throw new NotFoundHttpException();
        }

        $response = new RedirectResponse($url->original, 308, ['X-PS' => 'Thank you for using Pitchoun!']);
        $response->setCache(array(
            'max_age' => 3600,
            'private' => true,
        ));

        return $response;
    }
}
