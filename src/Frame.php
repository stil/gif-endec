<?php
namespace GIFEndec;

use GIFEndec\Geometry\Point;
use GIFEndec\Geometry\Rectangle;

class Frame
{
    /**
     * @var MemoryStream Stream storing GIF byte array
     */
    protected $stream;

    /**
     * @var int GIF frame duration in hundreds of second (1/100s)
     */
    protected $duration;

    /**
     * @var int Disposal method
     * Values :
     *   0 - No disposal specified. The decoder is not required to take any action.
     *   1 - Do not dispose. The graphic is to be left in place.
     *   2 - Restore to background color. The area used by the graphic must be restored to the background color.
     *   3 - Restore to previous. The decoder is required to restore the area overwritten by the graphic with
     *       what was there prior to rendering the graphic.
     */
    protected $disposalMethod;

    /**
     * @var bool
     */
    protected $isTransparent;

    /**
     * @var Color
     */
    protected $transparentColor;

    /**
     * @var Rectangle
     */
    protected $size;

    /**
     * @var Point
     */
    protected $offset;

    public function __construct()
    {
        $this->stream = new MemoryStream();
    }

    public function setTransparentColor(Color $color)
    {
        $this->transparentColor = $color;
    }

    public function isTransparent()
    {
        return $this->isTransparent;
    }

    public function setTransparent($bool)
    {
        $this->isTransparent = $bool;
    }

    public function getTransparentColor()
    {
        return $this->transparentColor;
    }

    /**
     * @param Rectangle $rectangle
     */
    public function setSize(Rectangle $rectangle)
    {
        $this->size = $rectangle;
    }

    /**
     * @return Rectangle
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param Point $offset
     */
    public function setOffset(Point $offset)
    {
        $this->offset = $offset;
    }

    /**
     * @return Point
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @param MemoryStream $stream
     */
    public function setStream(MemoryStream $stream)
    {
        $this->stream  = $stream;
    }

    /**
     * @return MemoryStream
     */
    public function getStream()
    {
        return $this->stream;
    }

    /**
     * @return resource
     */
    public function createGDImage()
    {
        return imagecreatefromstring($this->stream->getContents());
    }

    /**
     * @param int $time Hundreds of second (1/100s)
     */
    public function setDuration($time)
    {
        $this->duration = $time;
    }

    /**
     * @return int Hundreds of second (1/100s)
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @param $method
     */
    public function setDisposalMethod($method)
    {
        $this->disposalMethod = $method;
    }

    /**
     * @return int
     */
    public function getDisposalMethod()
    {
        return $this->disposalMethod;
    }
}
