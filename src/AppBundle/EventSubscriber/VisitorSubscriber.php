<?php

namespace AppBundle\EventSubscriber;

use Predis;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use AppBundle\Event\VisitorEvent;

class VisitorSubscriber implements EventSubscriberInterface
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
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            'app.events.visitor' => array(
                array('onVisitorEvent', 10),
            )
        );
    }

    /**
     * Tracks a visit by incrementing a counter
     *
     * @param VisitorEvent $event
     */
    public function onVisitorEvent(VisitorEvent $event)
    {
        $request = $event->getRequest();
        $key = sprintf('%svisits:%s', $this->redisKeyPrefix, $request->getPathInfo());
        $this->redis->incr($key);
    }
}
