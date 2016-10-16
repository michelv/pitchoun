<?php

namespace AppBundle\Service;

use Mso\IdnaConvert\IdnaConvert;
use Predis;

use AppBundle\Exception\UrlAlreadyShortenedException;
use AppBundle\Exception\UrlNotFoundException;
use AppBundle\Service\UriProvider;
use AppBundle\Url;

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
     * @var IdnaConvert
     */
    protected $idnaConverter;

    const VALID_URL_SCHEMES = ['http', 'https', 'ftp'];

    /**
     * @param Predis\Client $redis
     * @param UriProvider $uriProvider
     * @param string $baseRedirectionUrl
     * @param string $redisKeyPrefix
     */
    public function __construct(Predis\Client $redis, UriProvider $uriProvider, $baseRedirectionUrl, $redisKeyPrefix = '')
    {
        $this->redis = $redis;
        $this->uriProvider = $uriProvider;
        $this->baseRedirectionUrl = $baseRedirectionUrl;
        $this->redisKeyPrefix = $redisKeyPrefix;
        $this->idnaConverter = new IdnaConvert();
    }

    /**
     * @param string $originalUrl
     * @return Url
     */
    public function getShortUrl($originalUrl)
    {
        $originalUrl = $this->sanitizeUrl($originalUrl);
        if (strpos($originalUrl, $this->baseRedirectionUrl) !== false) {
            throw new UrlAlreadyShortenedException('Already shortened.');
        }

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
     * @throws \InvalidArgumentException
     * @param string $originalUrl
     * @return string
     */
    protected function sanitizeUrl($originalUrl)
    {
        if (!preg_match("#^[\w]+://#", $originalUrl)) {
            $originalUrl = 'http://'.$originalUrl;
        }

        if (
            !filter_var($originalUrl, FILTER_VALIDATE_URL)
            && !filter_var($this->idnaConverter->encodeUri($originalUrl), FILTER_VALIDATE_URL)
        ) {
            throw new \InvalidArgumentException('Invalid URL1.');
        }

        $parts = parse_url(trim($originalUrl));

        if (!in_array(strtolower($parts['scheme']), static::VALID_URL_SCHEMES) || !isset($parts['host'])) {
            throw new \InvalidArgumentException('Invalid URL2.');
        }

        $credentials = '';
        if (isset($parts['user'])) {
            $credentials = $parts['user'].':';
            if (isset($parts['pass'])) {
                $credentials .= $parts['pass'];
            }
        }

        try {
            $host = $this->idnaConverter->encode($parts['host']);
        } catch (\InvalidArgumentException $e) {
            $host = $parts['host'];
        }

        $sanitizedUrl = sprintf(
            '%s://%s%s%s%s%s%s',
            strtolower($parts['scheme']),
            ($credentials !== '' ? $credentials.'@' : ''),
            $host,
            (isset($parts['port']) ? ':'.$parts['port'] : ''),
            (isset($parts['path']) ? $parts['path'] : ''),
            (isset($parts['query']) ? '?'.$parts['query'] : ''),
            (isset($parts['fragment']) ? '#'.$parts['fragment'] : '')
        );

        return $sanitizedUrl;
    }
}
