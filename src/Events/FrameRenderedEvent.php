<?php
namespace GIFEndec\Events;

use GIFEndec\Frame;

class FrameRenderedEvent
{
    /**
     * @var int
     */
    public $frameIndex;

    /**
     * @var Frame
     */
    public $decodedFrame;

    /**
     * @var resource
     */
    public $renderedFrame;
}
