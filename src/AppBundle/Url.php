<?php

namespace AppBundle;

class Url
{
    /** @var string */
    public $original;

    /** @var string */
    public $short;

    /** @var string */
    public $shortUri;

    public function __construct($original, $short, $shortUri)
    {
        $this->original = $original;
        $this->short = $short;
        $this->shortUri = $shortUri;
    }
}
