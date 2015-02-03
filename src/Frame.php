<?php
namespace GIFEndec;

class Frame
{
    /**
     * @var MemoryStream Stream storing GIF byte array
     */
    protected $stream;

    /**
     * @var int GIF frame delay in hundreds of second (1/100)
     */
    protected $delay;

    public function __construct()
    {
        $this->stream = new MemoryStream();
    }

    public function getStream()
    {
        return $this->stream;
    }

    public function createGDImage()
    {
        return imagecreatefromstring($this->stream->getContents());
    }

    public function setDuration($seconds)
    {
        $this->delay = $seconds;
    }

    public function getDuration()
    {
        return $this->delay;
    }
}