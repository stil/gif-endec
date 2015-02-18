<?php
namespace GIFEndec;

class Renderer
{
    /**
     * @var resource
     */
    protected $frameCurrent = null;

    /**
     * @param Decoder $decoder
     */
    public function __construct(Decoder $decoder)
    {
        $this->decoder = $decoder;
    }

    /**
     * @param callable $onFrameRendered
     */
    public function start(callable $onFrameRendered)
    {
        $this->decoder->decode(function (Frame $frame, $index) use ($onFrameRendered) {
            $onFrameRendered($this->render($frame, $index), $index);
        });
    }

    /**
     * @param Frame $frame
     * @param $index
     * @return resource
     */
    protected function render(Frame $frame, $index)
    {
        if ($index == 0) {
            $screenSize = $this->decoder->getScreenSize();
            $this->frameCurrent = imagecreatetruecolor(
                $screenSize->getWidth(),
                $screenSize->getHeight()
            );
        }

        $disposalMethod = $frame->getDisposalMethod();
        if ($disposalMethod === 0 || $disposalMethod === 1) {
            $this->copyFrameToBuffer($frame);
        } else {
            throw new \RuntimeException("Disposal method $disposalMethod is not implemented.");
        }

        return $this->frameCurrent;
    }

    /**
     * @param Frame $frame
     */
    protected function copyFrameToBuffer(Frame $frame)
    {
        imagecopy(
            $this->frameCurrent,
            $frame->createGDImage(),
            $frame->getOffset()->getX(),
            $frame->getOffset()->getY(),
            0,
            0,
            $frame->getSize()->getWidth(),
            $frame->getSize()->getHeight()
        );
    }
}
