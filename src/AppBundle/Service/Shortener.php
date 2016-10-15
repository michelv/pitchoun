<?php

namespace AppBundle\Service;

use Mso\IdnaConvert\IdnaConvert;
use Predis;

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

        $key = sprintf('%sshort:%s', $this->redisKeyPrefix, $originalUrl);

        $shortUri = $this->redis->get($key);
        if ($shortUri === null) {
            $shortUri = $this->uriProvider->getNextUri();
            $this->redis->set($key, $shortUri);

            $reverseKey = sprintf('%slong:%s', $this->redisKeyPrefix, $shortUri);
            $this->redis->set($reverseKey, $originalUrl);
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
     * @param Url $url
     * @param string $aliasUri
     */
    public function addAlias(Url $url, $aliasUri)
    {
    }

    /**
     * @throws \InvalidArgumentException
     * @param string $originalUrl
     * @return string
     */
    protected function sanitizeUrl($originalUrl)
    {
        if (!preg_match('#[a-zA-Z0-9]#', $originalUrl)) {
            throw new \InvalidArgumentException('Invalid URL.');
        }

        $parts = parse_url(trim($originalUrl));

        if (!isset($parts['scheme'])) {
            $parts['scheme'] = 'http';
        } elseif (!in_array(strtolower($parts['scheme']), static::VALID_URL_SCHEMES) || !isset($parts['host'])) {
            throw new \InvalidArgumentException('Invalid URL.');
        }

        if (empty($parts['host'])) {
            if (!empty($parts['path']) && strpos($parts['path'], '/') !== 0) {
                // special case, parse_url('a.com') says there is no host and that a.com is the path
                $pos = strpos($parts['path'], '/');
                if ($pos !== false) {
                    $parts['host'] = substr($parts['path'], 0, $pos);
                    $parts['path'] = substr($parts['path'], $pos);
                } else {
                    $parts['host'] = $parts['path'];
                    unset($parts['path']);
                }
            } else {
                throw new \InvalidArgumentException('Invalid URL.');
            }
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
            (isset($parts['port']) ? $parts['port'] : ''),
            (isset($parts['path']) ? $parts['path'] : ''),
            (isset($parts['query']) ? '?'.$parts['query'] : ''),
            (isset($parts['fragment']) ? '#'.$parts['fragment'] : '')
        );

        return $sanitizedUrl;
    }
}
