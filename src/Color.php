<?php
namespace GIFEndec;

class Color
{
    /**
     * @var int Color index
     */
    public $index = -1;

    /**
     * @var int Red component
     */
    public $red = -1;

    /**
     * @var int Green component
     */
    public $green = -1;

    /**
     * @var int Blue component
     */
    public $blue = -1;

    /**
     * @param int $red
     * @param int $green
     * @param int $blue
     */
    public function __construct($red, $green, $blue)
    {
        $this->red = $red;
        $this->green = $green;
        $this->blue = $blue;
    }
}
