<?php
namespace GIFEndec;

class Renderer
{
    /**
     * @var resource
     */
    protected $frameCurrent = null;

    /**
     * @var Frame
     */
    protected $framePrevious = null;

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
            $im = imagecreatetruecolor($screenSize->getWidth(), $screenSize->getHeight());
            imagealphablending($im, false);
            imagesavealpha($im, true);

            $transColor = imagecolortransparent($im, imagecolorallocatealpha($im, 255, 255, 255, 127));
            imagefill($im, 0, 0, $transColor);

            $this->frameCurrent = $im;
            $this->framePrevious = $frame;
            $this->copyFrameToBuffer($frame);

            return $this->frameCurrent;
        }

        imagepalettetotruecolor($this->frameCurrent);
        $disposalMethod = $this->framePrevious->getDisposalMethod();
        if ($disposalMethod === 0 || $disposalMethod === 1) {
            $this->copyFrameToBuffer($frame);
        } elseif ($disposalMethod === 2) {
            $this->restoreToBackground($this->framePrevious, imagecolortransparent($this->frameCurrent));
            $this->copyFrameToBuffer($frame);
        } else {
            throw new \RuntimeException("Disposal method $disposalMethod is not implemented.");
        }

        $this->framePrevious = $frame;
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

    /**
     * @param Frame $frame
     * @param int $backgroundColor
     */
    protected function restoreToBackground(Frame $frame, $backgroundColor)
    {
        $offset = $frame->getOffset();
        $size = $frame->getSize();

        imagefilledrectangle(
            $this->frameCurrent,
            $offset->getX(),
            $offset->getY(),
            $offset->getX() + $size->getWidth() - 1,
            $offset->getY() + $size->getHeight() - 1,
            $backgroundColor
        );
    }
}
