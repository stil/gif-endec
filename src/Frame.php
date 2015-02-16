<?php
namespace GIFEndec;

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

    /* @var int */
    protected $posLeft;

    /* @var int */
    protected $posTop;

    /* @var int */
    protected $width;

    /* @var int */
    protected $height;


    public function __construct()
    {
        $this->stream = new MemoryStream();
    }

    /**
     * @param int $left
     * @param int $top
     */
    public function setPosition($left, $top)
    {
        $this->posLeft = $left;
        $this->posTop = $top;
    }

    /**
     * @param int $width
     * @param int $height
     */
    public function setSize($width, $height)
    {
        $this->width = $width;
        $this->height = $height;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @return int
     */
    public function getPositionLeft()
    {
        return $this->posLeft;
    }

    /**
     * @return int
     */
    public function getPositionTop()
    {
        return $this->posTop;
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
