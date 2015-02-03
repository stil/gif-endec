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
}