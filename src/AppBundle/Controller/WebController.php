<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Templating\EngineInterface;

use AppBundle\Exception\UrlNotFoundException;
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
     * @var Shortener
     */
    protected $shortener;

    /**
     * @param Shortener $shortener
     * @param string $baseRedirectionUrl
     */
    public function __construct(RouterInterface $router, EngineInterface $templating, Shortener $shortener)
    {
        $this->router = $router;
        $this->templating = $templating;
        $this->shortener = $shortener;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function indexAction(Request $request)
    {
        // replace this example code with whatever you need
        return $this->templating->renderResponse('default/index.html.twig', [
            'base_dir' => 'somewhere on your drive I guess?',
        ]);
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
