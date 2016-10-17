<?php

namespace AppBundle;

/**
 * An object that holds an original URL, its shortened version, and the URI
 * of its shortened version.
 */
class Url
{
    /**
     * @var string
     */
    public $original;

    /**
     * @var string
     */
    public $short;

    /**
     * @var string
     */
    public $shortUri;

    /**
     * @param string $original
     * @param string $short
     * @param string $shortUri
     */
    public function __construct($original, $short, $shortUri)
    {
        $this->original = $original;
        $this->short = $short;
        $this->shortUri = $shortUri;
    }
}
