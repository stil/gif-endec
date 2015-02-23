<?php
namespace GIFEndec;

class Encoder
{
    /**
     * @var MemoryStream
     */
    protected $output;

    /**
     * @var bool
     */
    protected $hasHeader = false;

    /**
     * @var int
     */
    protected $frameIndex = 0;

    /**
     * @var
     */
    protected $firstFrameBytes;

    /**
     * @var string
     */
    protected $globalColorTable;

    /**
     * @var int
     */
    protected $globalColorTableSize;

    /**
     * @var int
     */
    protected $repetitions =  0;

    /**
     * @param int $repetitions By default, repeat forever
     */
    public function __construct($repetitions = 0)
    {
        $this->output = new MemoryStream();
        $this->repetitions = $repetitions;
    }

    /**
     * @param $bytes
     * @param $frameIndex
     */
    protected function checkIfNotAnimated(&$bytes, $frameIndex)
    {
        $packedFields = ord($bytes[10]);
        $globalColorTableSize = 3 * (2 << ($packedFields & 0x07));

        for ($j = (13 + $globalColorTableSize), $k = true; $k; $j++) {
            switch ($bytes[$j]) {
                case "!":
                    if ((substr($bytes, $j + 3, 8)) == "NETSCAPE") {
                        throw new \RuntimeException(
                            "Cannot make animated GIF from animated frame (#{$frameIndex})."
                        );
                    }
                    break;
                case ";":
                    $k = false;
                    break;
            }
        }
    }

    /**
     * Writes Header, Logical Screen Descriptor and Global Color Table to output
     * @param $firstFrame
     */
    protected function addHeader(Frame $firstFrame)
    {
        $this->firstFrameBytes = $firstFrame->getStream()->getContents();

        $this->output->writeString('GIF89a');
        $packedFields = ord($this->firstFrameBytes[10]);
        if ($globalColorTableFlag = $packedFields & 0x80) {
            $this->globalColorTableSize = 3 * (2 << ($packedFields & 0x07));
            $this->globalColorTable = substr($this->firstFrameBytes, 13, $this->globalColorTableSize);
            $this->output->writeString(
                substr($this->firstFrameBytes, 6, 7). // copy Logical Screen Descriptor
                $this->globalColorTable. // copy Global Color Table
                "!\377\13NETSCAPE2.0\3\1".$this->convertUnsignedShort($this->repetitions)."\0"
            );
        }

        $this->hasHeader = true;
    }

    /**
     * @param Frame $frame
     * @todo Detect transparency from frame binary source
     */
    public function addFrame(Frame $frame)
    {
        $bytes = $frame->getStream()->getContents();
        $this->checkIfNotAnimated($bytes, $this->frameIndex);

        if (!$this->hasHeader) {
            $this->addHeader($frame);
        }

        $tcolor = $frame->getTransparentColor();
        //$tcolor = $color->red | ($color->green << 8) | ($color->blue << 16);

        // ADD FRAMES START //
        $localPackedFields = ord($bytes[10]);
        $localColorTableSize = 3 * (2 << ($localPackedFields  & 0x07));
        $localColorTable = substr($bytes, 13, $localColorTableSize);
        $imgData = substr($bytes, 13 + $localColorTableSize, -1);

        // Local Graphic Control Extension
        $localGCE = new MemoryStream();
        $localGCE->writeBytes([
            0x21, // Extension Introducer, contains the fixed value 0x21.
            0xF9, // Graphic Control Label, contains the fixed value 0xF9.
            0x04,   // Block Size, contains the fixed value 4.
            ($frame->getDisposalMethod() << 2) + 0, // <Packed Fields>
        ]);
        $localGCE->writeString($this->convertUnsignedShort($frame->getDuration()));
        $localGCE->writeBytes([
            0x00, // Transparent Color Index
            0x00  // Block Terminator
        ]);

        // If transparent color exists and Global Color Table is enabled
        if ($tcolor instanceof Color && $localPackedFields & 0x80) {
            // Look for transparent color in Global Color Table
            for ($j = 0; $j < $localColorTableSize / 3; $j++) {
                if (
                    ord($localColorTable[3 * $j + 0]) == ($tcolor->red   & 0xFF) &&
                    ord($localColorTable[3 * $j + 1]) == ($tcolor->green & 0xFF) &&
                    ord($localColorTable[3 * $j + 2]) == ($tcolor->blue  & 0xFF)
                ) {
                    $localGCE->seek(3); // Enable Transparent Color Flag
                    $localGCE->writeBytes([($frame->getDisposalMethod() << 2) + 1]);

                    $localGCE->seek(6); // Set Transparent Color Index
                    $localGCE->writeBytes([$j]);
                    break;
                }
            }
        }

        switch (ord($imgData[0])) {
            case 0x21: // Graphic Control Extension, This block is OPTIONAL
                $gce = substr($imgData, 0, 8);
                $gcePackedFields = $gce[3];
                $gceTransparencyFlag = (ord($gcePackedFields) & 0x01) == 0x01;
                $gceTransparencyIndex = ord($gce[6]);
                $imgDescriptor = substr($imgData, 8, 10);
                $imgData = substr($imgData, 18);
                break;
            case 0x2C: // Image Descriptor
                $imgDescriptor = substr($imgData, 0, 10);
                $imgData = substr($imgData, 10);
                break;
            default:
                throw new \RuntimeException("Unexpected input.");
                break;
        }

        $this->output->writeString($localGCE->getContents());

        $applyLocalColorTable =
            $localPackedFields & 0x80
            && $this->hasHeader
            && ($this->globalColorTableSize != $localColorTableSize || !$this->blockCompare($localColorTable));

        if ($applyLocalColorTable) {
            $byte  = ord($imgDescriptor[9]);
            $byte |= 0x80;
            $byte &= 0xF8;
            $byte |= (ord($this->firstFrameBytes[10]) & 0x07);
            $imgDescriptor[9] = chr($byte);
            $this->output->writeString($imgDescriptor.$localColorTable);
        } else {
            $this->output->writeString($imgDescriptor);
        }

        $this->output->writeString($imgData);

        // ADD FRAMES END //

        $this->frameIndex++;
    }

    /**
     *
     */
    public function addFooter()
    {
        $this->output->writeString(";");
    }

    /**
     * @param $localBlock
     * @return bool
     */
    protected function blockCompare($localBlock)
    {
        $globalBlock = $this->globalColorTable;
        $len = $this->globalColorTableSize;
        for ($i = 0; $i < $len; $i++) {
            if (
                $globalBlock[$i] != $localBlock[$i]
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $int
     * @return string
     */
    protected function convertUnsignedShort($int)
    {
        return chr($int & 0xFF).chr(($int >> 8) & 0xFF);
    }

    /**
     * @return MemoryStream
     */
    public function getStream()
    {
        return $this->output;
    }
}