<?php

namespace AppBundle\Service;

use AppBundle\Exception\UrlNotFoundException;
use AppBundle\Url;

class Shortener
{
    /**
     * @var Predis\Client
     */
    protected $redis;

    /**
     * @var string
     */
    protected $baseRedirectionUrl;

    /**
     * @var string
     */
    protected $redisKeyPrefix;

    /**
     * @param Predis\Client $redis
     * @param string $baseRedirectionUrl
     * @param string $redisKeyPrefix
     */
    public function __construct(Predis\Client $redis, $baseRedirectionUrl, $redisKeyPrefix)
    {
        $this->redis = $redis;
        $this->baseRedirectionUrl = $baseRedirectionUrl;
        $this->redisKeyPrefix = $redisKeyPrefix;
    }

    /**
     * @param string $originalUrl
     * @return Url
     */
    public function getShortUrl($originalUrl)
    {

    }

    /**
     * @throws UrlNotFoundException
     * @param string $originalUrl
     * @return Url
     */
    public function getFromLongUrl($shortUrl)
    {
        
    }

    /**
     * @param Url $url
     * @param string $aliasUri
     */
    public function addAlias($url, $aliasUri)
    {

    }
}
