<?php

namespace AppBundle\Service;

use Predis;

/**
 * Provides unique URIs for the Shortener service.
 */
class UriProvider
{
    /**
     * @var Predis\ClientInterface
     */
    protected $redis;

    /**
     * @var string
     */
    protected $redisKeyPrefix;

    /**
     * @param Predis\ClientInterface $redis
     * @param string $redisKeyPrefix
     */
    public function __construct(Predis\ClientInterface $redis, $redisKeyPrefix)
    {
        $this->redis = $redis;
        $this->redisKeyPrefix = $redisKeyPrefix;
    }

    /**
     * Returns a new unique URI.
     *
     * @return string
     */
    public function getNextUri()
    {
        $key = $this->redisKeyPrefix.'sequence';
        $value = $this->redis->incr($key);

        return '/'.base_convert($value, 10, 36);
    }
}
