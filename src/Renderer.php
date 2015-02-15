<?php
namespace GIFEndec;

class Renderer
{
    /**
     * @var resource
     */
    protected $framePrevious = null;

    /**
     * @var resource
     */
    protected $frameCurrent = null;

    /**
     * @var int
     */
    protected $width;

    /**
     * @var int
     */
    protected $height;

    public function render(Frame $frame)
    {
        if ($this->frameCurrent === null) {
            $this->frameCurrent = $frame->createGDImage();
            $this->width = imagesx($this->frameCurrent);
            $this->height = imagesy($this->frameCurrent);
            return $this->frameCurrent;
        }

        $disposalMethod = $frame->getDisposalMethod();
        //$this->framePrevious = $this->cloneGDResource($this->frameCurrent);
        if ($disposalMethod === 1) {
            // Do not dispose
            imagecopy($this->frameCurrent, $frame->createGDImage(), 0, 0, 0, 0, $this->width, $this->height);
        } else {
            throw new \RuntimeException("Disposal method $disposalMethod is not implemented.");
        }

        return $this->frameCurrent;
    }

    private function cloneGDResource($old)
    {
        $palletSize = imagecolorstotal($old);
        $transparentIndex = imagecolortransparent($old);

        $im = imagecreate($this->width, $this->height);
        if ($transparentIndex >= 0 && $transparentIndex < $palletSize) {
            $rgb = imagecolorsforindex($old, $transparentIndex);
            imagesavealpha($im, true);
            $trans_index = imagecolorallocatealpha($im, $rgb['red'], $rgb['green'], $rgb['blue'], $rgb['alpha']);
            imagefill($im, 0, 0, $trans_index);
        }
        imagecopy($im, $old, 0, 0, 0, 0, $this->width, $this->height);
        return $im;
    }
}
