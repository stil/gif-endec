<?php
namespace GIFEndec;

use GIFEndec\Geometry\Point;
use GIFEndec\Geometry\Rectangle;

class Decoder implements DecoderInterface
{
    /**
     * @var MemoryStream
     */
    protected $stream;

    /**
     * Loop repetitions
     * @var int
     */
    protected $repetitions =  0;

    /**
     * Currently processed frame
     * @var Frame
     */
    protected $currentFrame;

    /**
     * Byte array buffer
     * @var int[]
     */
    protected $buffer = [];

    /**
     * Byte array of Logical Screen Descriptor
     * @var int[]
     */
    protected $screen = [];

    /**
     * @var Rectangle
     */
    protected $screenSize;

    /**
     * @var int Global Color Table Flag
     *      0 - No Global Color Table follows,
     *          the Background Color Index field is meaningless.
     *      1 - A Global Color Table will immediately follow,
     *          the Background Color Index field is meaningful.
     */
    protected $gctFlag;

    /**
     * @var int Raw size of Global Color Table, stored in 3 least significant bits of byte (0000 0111)
     */
    protected $gctSize;

    /**
     * Byte array of Global Color Table
     * @var int[]
     */
    protected $globalColorTable = [];

    /**
     * @var int Sort Flag
     *      0 - Not ordered.
     *      1 - Ordered by decreasing importance, most important color first.
     */
    protected $sortFlag;

    /**
     * @param MemoryStream $gifStream
     */
    public function __construct(MemoryStream $gifStream)
    {
        $this->stream = $gifStream;
    }

    /**
     * @param callable $onFrameDecoded
     */
    public function decode(callable $onFrameDecoded)
    {
        $this->readHeader();
        $this->readLogicalScreenDescriptor();
        $this->readGlobalColorTable();

        $frameIndex = 0;
        $cycle = true;
        do {
            $this->readBytes(1);
            if (!$this->stream->hasReachedEOF()) {
                switch ($this->buffer[0]) {
                    case 0x21:
                        $this->readGraphicControlExtension();
                        break;
                    case 0x2C:
                        $this->readImageDescriptor();
                        $onFrameDecoded($this->currentFrame, $frameIndex++);
                        break;
                    case 0x3B:
                        $cycle = false;
                        break;
                }
            } else {
                $cycle = false;
            }
        } while ($cycle);

        /**
         * Cleanup of internal variables
         */
        unset(
            $this->buffer,
            $this->currentFrame,
            $this->globalColorTable,
            $this->gctSize,
            $this->gctFlag,
            $this->sortFlag,
            $this->screen
        );
    }

    /**
     * @return Rectangle
     */
    public function getScreenSize()
    {
        return $this->screenSize;
    }

    /**
     * @return int
     */
    public function getLoopRepetitions()
    {
        return $this->repetitions;
    }

    /**
     * The Header identifies the GIF Data Stream in context.
     */
    protected function readHeader()
    {
        $this->readBytes(6); // GIF89a or GIF87a
    }

    /**
     * The Logical Screen Descriptor contains the parameters necessary to define
     * the area of the display device within which the images will be rendered.
     */
    protected function readLogicalScreenDescriptor()
    {
        $this->readBytes(7);

        $this->screenSize = new Rectangle(
            $this->getUnsignedShort($this->buffer, 0),
            $this->getUnsignedShort($this->buffer, 2)
        );

        $this->screen   = $this->buffer;
        $this->gctFlag  = $this->buffer[4] & 0x80 ? 1 : 0; // 1000 0000
        $this->sortFlag = $this->buffer[4] & 0x08 ? 1 : 0; // 0000 1000
        $this->gctSize  = $this->buffer[4] & 0x07;         // 0000 0111
    }

    /**
     * This block contains a color table, which is a sequence
     * of bytes representing red-green-blue color triplets.
     */
    protected function readGlobalColorTable()
    {
        if ($this->gctFlag == 1) {
            $this->readBytes(3 * (2 << $this->gctSize));
            $this->globalColorTable = $this->buffer;
        }
    }

    /**
     * The Graphic Control Extension contains parameters used
     * when processing a graphic rendering block.
     */
    protected function readGraphicControlExtension()
    {
        $this->readBytes(1);
        $switch = $this->buffer[0] == 0xFF;

        while (true) {
            $this->readBytes(1);
            if (($u = $this->buffer[0]) == 0x00) {
                break;
            }
            $this->readBytes($u);

            if ($switch) {
                if ($u == 0x03) {
                    $this->repetitions = ($this->buffer[1] | $this->buffer[2] << 8);
                }
            } elseif ($u == 0x04) {
                $this->currentFrame = new Frame();

                $packedFields = $this->buffer[0];
                $this->currentFrame->setDisposalMethod(
                    (isset($this->buffer[4]) ? $this->buffer[4] : 0) & 0x80
                    ? ($packedFields >> 2) - 1
                    : ($packedFields >> 2) - 0
                );

                $this->currentFrame->setDuration(
                    $this->getUnsignedShort($this->buffer, 1)
                );

                $this->currentFrame->setTransparent(
                    ($packedFields & 0x1) === 0x1
                );

                if ($this->currentFrame->isTransparent()) {
                    $color = new Color();
                    $color->index = $this->buffer[3];
                    $this->currentFrame->setTransparentColor($color);
                }
            }
        }
    }

    /**
     * Each image in the Data Stream is composed of an Image
     * Descriptor, an optional Local Color Table, and the image data.
     */
    protected function readImageDescriptor()
    {
        $this->readBytes(9);
        $screen = $this->buffer;

        $this->currentFrame->setOffset(new Point(
            $this->getUnsignedShort($screen, 0),
            $this->getUnsignedShort($screen, 2)
        ));

        $this->currentFrame->setSize(new Rectangle(
            $this->getUnsignedShort($screen, 4),
            $this->getUnsignedShort($screen, 6)
        ));


        $gctFlag = ($screen[8] & 0x80) == 0x80;
        if ($gctFlag) {
            $code = $screen[8] & 0x07;
            $sort = $screen[8] & 0x20 ? 1 : 0;
        } else {
            $code = $this->gctSize;
            $sort = $this->sortFlag;
        }
        $size = 2 << $code;
        $this->screen[4] &= 0x70;
        $this->screen[4] |= 0x80;
        $this->screen[4] |= $code;
        if ($sort) {
            $this->screen[4] |= 0x08;
        }

        /**
         * GIF Data Begin
         */
        $stream = $this->currentFrame->getStream();
        $stream->writeString(
            $this->currentFrame->isTransparent() ? "GIF89a" : "GIF87a"
        );

        $stream->writeBytes($this->screen);
        $color = $this->currentFrame->getTransparentColor();
        if ($gctFlag) {
            $this->readBytes(3 * $size);
            if ($this->currentFrame->isTransparent()) {
                $color->red   = $this->buffer[3 * $color->index + 0];
                $color->green = $this->buffer[3 * $color->index + 1];
                $color->blue  = $this->buffer[3 * $color->index + 2];
            }
            $stream->writeBytes($this->buffer);
        } else {
            if ($this->currentFrame->isTransparent()) {
                $color->red   = $this->globalColorTable[3 * $color->index + 0];
                $color->green = $this->globalColorTable[3 * $color->index + 1];
                $color->blue  = $this->globalColorTable[3 * $color->index + 2];
            }
            $stream->writeBytes($this->globalColorTable);
        }

        if ($this->currentFrame->isTransparent()) {
            $stream->writeString("!\xF9\x04\x1\x0\x0".chr($color->index)."\x0");
        }

        $stream->writeBytes([0x2C]);
        $screen[8] &= 0x40;
        $stream->writeBytes($screen);

        $this->readBytes(1);
        $stream->writeBytes($this->buffer);

        $srcPhpStream = $this->stream->getPhpStream();
        $dstPhpStream = $stream->getPhpStream();

        $blockSize = null;
        $blockSizeRaw = null;
        while (true) {
            $blockSizeRaw = fread($srcPhpStream, 1);
            $blockSize = ord($blockSizeRaw);
            fwrite($dstPhpStream, $blockSizeRaw);
            if ($blockSize == 0x00) {
                break;
            }

            fwrite($dstPhpStream, fread($srcPhpStream, $blockSize));
        }

        $stream->writeBytes([0x3B]);
    }

    /**
     * @param int $bytesCount How many bytes to read
     * @return bool
     */
    protected function readBytes($bytesCount)
    {
        $this->stream->readBytes($bytesCount, $this->buffer);
    }

    /**
     * Extracts 16-bit, little endian unsigned short integer from byte array
     * @param array $buffer Byte array
     * @param int $offset Starting position in byte array
     * @return int
     */
    protected function getUnsignedShort(&$buffer, $offset)
    {
        return ($buffer[$offset] | $buffer[$offset + 1] << 8);
    }
}
