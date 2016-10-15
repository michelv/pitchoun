<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use AppBundle\Exception\UrlNotFoundException;
use AppBundle\Service\Shortener;

class ApiController
{
    /**
     * @var Shortener
     */
    protected $shortener;

    /**
     * @var string
     */
    protected $baseRedirectionUrl;

    /**
     * @var string
     */
    protected $secret;

    /**
     * @param Shortener $shortener
     * @param string $baseRedirectionUrl
     * @param string $secret
     */
    public function __construct(Shortener $shortener, $baseRedirectionUrl, $secret)
    {
        $this->shortener = $shortener;
        $this->baseRedirectionUrl = $baseRedirectionUrl;
        $this->secret = $secret;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function shortenAction(Request $request)
    {
        $originalUrl = trim($request->get('url'));

        $response = new JsonResponse();
        $response->setCache(array(
            'etag' => md5('shorten'.$this->secret.$originalUrl),
        ));

        if ($response->isNotModified($request)) {
            return $response;
        }

        try {
            $url = $this->shortener->getShortUrl($originalUrl);
        } catch (\InvalidArgumentException $e) {
            return $this->getErrorResponse('Invalid URL.', 400);
        }

        $response->setData(['url' => $url->short]);
        $response->setStatusCode(200);

        return $response;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function lengthenAction(Request $request)
    {
        $shortUrl = trim($request->get('url'));

        $response = new JsonResponse();
        $response->setCache(array(
            'etag' => md5('lengthen'.$this->secret.$shortUrl),
        ));

        if ($response->isNotModified($request)) {
            return $response;
        }

        try {
            if (strpos($shortUrl, $this->baseRedirectionUrl) !== 0) {
                throw new \InvalidArgumentException('Base redirection URL not found.');
            }
            $parts = parse_url($shortUrl);

            $url = $this->shortener->getFromShortUri($parts['path']);
        } catch (UrlNotFoundException $e) {
            return $this->getErrorResponse('URL not found.', 404);
        } catch (\InvalidArgumentException $e) {
            return $this->getErrorResponse('Invalid URL.', 400);
        }

        $response->setData(['url' => $url->original]);
        $response->setStatusCode(200);

        return $response;
    }

    /**
     * @param string $message
     * @param int $http_code
     * @return JsonResponse
     */
    protected function getErrorResponse($message, $http_code)
    {
        return new JsonResponse(['error' => $message], $http_code);
    }
}
