<?php
namespace GIFEndec\Events;

use GIFEndec\Frame;

class FrameDecodedEvent
{
    /**
     * @var int
     */
    public $frameIndex;

    /**
     * @var Frame
     */
    public $decodedFrame;
}
