<?php

namespace AppBundle\Service;

use Predis;
use League\Uri;

use AppBundle\Exception\UrlAlreadyShortenedException;
use AppBundle\Exception\UrlNotFoundException;
use AppBundle\Service\UriProvider;
use AppBundle\Url;

/**
 * Shortens/lengthens URLs.
 */
class Shortener
{
    /**
     * @var Predis\Client
     */
    protected $redis;

    /**
     * @var UriProvider
     */
    protected $uriProvider;

    /**
     * @var string
     */
    protected $baseRedirectionUrl;

    /**
     * @var string
     */
    protected $redisKeyPrefix;

    /**
     * @var Uri\UriParser
     */
    protected $uriParser;

    /**
     * @var Uri\Formatter
     */
    protected $uriFormatter;

    /**
     * A list of whitelisted URL schemes
     */
    const VALID_URL_SCHEMES = ['http', 'https', 'ftp'];

    /**
     * @param Predis\ClientInterface $redis
     * @param UriProvider $uriProvider
     * @param string $baseRedirectionUrl
     * @param string $redisKeyPrefix
     */
    public function __construct(Predis\ClientInterface $redis, UriProvider $uriProvider, $baseRedirectionUrl, $redisKeyPrefix = '')
    {
        $this->redis = $redis;
        $this->uriProvider = $uriProvider;
        $this->baseRedirectionUrl = $baseRedirectionUrl;
        $this->redisKeyPrefix = $redisKeyPrefix;
        $this->uriParser = new Uri\UriParser();
        $this->uriFormatter = new Uri\Formatter();
        $this->uriFormatter->setHostEncoding(Uri\Formatter::HOST_AS_ASCII);
    }

    /**
     * Given a URL, returns a Url object that contains the sanitized version
     * of the URL, the shortened URL, and the URI of the shortened URL.
     * Throws an exception if the URL is already shortened.
     *
     * @throws UrlAlreadyShortenedException
     * @param string $originalUrl
     * @return Url
     */
    public function getShortUrl($originalUrl)
    {
        if (strpos($originalUrl, $this->baseRedirectionUrl) !== false) {
            throw new UrlAlreadyShortenedException('Already shortened.');
        }
        $originalUrl = $this->sanitizeUrl($originalUrl);

        $key = sprintf('%sshort:%s', $this->redisKeyPrefix, $originalUrl);

        $shortUri = $this->redis->get($key);
        if ($shortUri === null) {
            $shortUri = $this->uriProvider->getNextUri();
            $reverseKey = sprintf('%slong:%s', $this->redisKeyPrefix, $shortUri);

            $this->redis->pipeline()->set($key, $shortUri)->set($reverseKey, $originalUrl)->execute();
        }

        $url = new Url($originalUrl, $this->baseRedirectionUrl.$shortUri, $shortUri);

        return $url;
    }

    /**
     * Given a shortened URL, returns a Url object that contains the original
     * URL, the shortened URL, and the URI of the shortened URL.
     * Throws an exception if the URL is not found in the database.
     *
     * @throws UrlNotFoundException
     * @param string $originalUrl
     * @return Url
     */
    public function getFromShortUri($shortUri)
    {
        $key = sprintf('%slong:%s', $this->redisKeyPrefix, $shortUri);

        $originalUrl = $this->redis->get($key);
        if ($originalUrl === null) {
            throw new UrlNotFoundException('Unable to find a URL that matches the given short URI.');
        }

        $url = new Url($originalUrl, $this->baseRedirectionUrl.$shortUri, $shortUri);

        return $url;
    }

    /**
     * Validates and sanitizes URLs.
     * Returns the sanitized version of the given URL.
     * Throws an exception if the URL is not valid.
     *
     * @throws \InvalidArgumentException
     * @param string $originalUrl
     * @return string
     */
    protected function sanitizeUrl($originalUrl)
    {
        if (!preg_match("#^[\w]+://#", $originalUrl)) {
            $originalUrl = 'http://'.$originalUrl;
        }

        $parts = $this->uriParser->parse($originalUrl);
        if (
            !in_array(strtolower($parts['scheme']), static::VALID_URL_SCHEMES)
            || !isset($parts['host'])
        ) {
            throw new \InvalidArgumentException('Invalid URL.');
        }

        $host = new Uri\Components\Host($parts['host']);
        if (!$host->isIp() && !$host->isPublicSuffixValid()) {
            throw new \InvalidArgumentException('Invalid URL.');
        }

        if ($parts['scheme'] == 'ftp') {
            $uri = Uri\Schemes\Ftp::createFromComponents($parts);
        } else {
            $uri = Uri\Schemes\Http::createFromComponents($parts);
        }

        return $this->uriFormatter->format($uri);
    }
}
