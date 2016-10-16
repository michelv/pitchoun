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

    public function __construct(Predis\ClientInterface $redis, $redisKeyPrefix)
    {
        $this->redis = $redis;
        $this->redisKeyPrefix = $redisKeyPrefix;
    }

    public static function getSubscribedEvents()
    {
        return array(
            'app.events.visitor' => array(
                array('onVisitorEvent', 10),
            )
        );
    }

    public function onVisitorEvent(VisitorEvent $event)
    {
        $request = $event->getRequest();
        $key = sprintf('%svisits:%s', $this->redisKeyPrefix, $request->getPathInfo());
        $this->redis->incr($key);
    }
}
