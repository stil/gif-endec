<?php
namespace GIFEndec;

class Decoder implements DecoderInterface
{
    /**
     * @var MemoryStream
     */
    protected $stream;

    /**
     * @var int Loop repetitions
     */
    protected $repetitions =  0;

    /**
     * @var int Frame index
     */
    protected $frameIndex = 0;

    /**
     * Current processed frame
     * @var Frame
     */
    protected $currentFrame;

    /**
     * Bytes buffer
     * @var int[]
     */
    protected $buffer = [];

    /**
     * Byte array of Logical Screen Descriptor
     * @var int[]
     */
    protected $screen = [];

    /**
     * Byte array of Global Color Table
     * @var int[]
     */
    protected $globalColorTable = [];

    /**
     * @var int Global Color Table Flag
     *      0 - No Global Color Table follows,
     *          the Background Color Index field is meaningless.
     *      1 - A Global Color Table will immediately follow,
     *          the Background Color Index field is meaningful.
     */
    protected $gctFlag;

    /**
     * @var int Sort Flag
     *      0 - Not ordered.
     *      1 - Ordered by decreasing importance, most important color first.
     */
    protected $sortFlag;

    /**
     * @var int Raw size of Global Color Table, stored in 3 least significant bits of byte (0000 0111)
     */
    protected $gctSize;

    /**
     * @var callable
     */
    protected $onFrameDecoded;

    protected $transparentR = -1;
    protected $transparentG = -1;
    protected $transparentB = -1;
    protected $transparentI =  0;

    /**
     * @param MemoryStream $gifStream
     */
    public function __construct(MemoryStream $gifStream)
    {
        $this->stream = $gifStream;
    }

    public function decode(callable $onFrameDecoded)
    {
        $this->onFrameDecoded = $onFrameDecoded;
        $this->readHeader();
        $this->readLogicalScreenDescriptor();
        $this->readGlobalColorTable();

        $cycle = true;
        do {
            if ($this->readBytes(1)) {
                switch ($this->buffer[0]) {
                    case 0x21:
                        $this->readGraphicControlExtension();
                        break;
                    case 0x2C:
                        $this->readImageDescriptor();
                        break;
                    case 0x3B:
                        $cycle = false;
                        break;
                }
            } else {
                $cycle = false;
            }
        } while ($cycle);

        /*
         * cleanup of internal variables
         */
        unset(
            $this->buffer,
            $this->currentFrame,
            $this->stream,
            $this->globalColorTable,
            $this->gctSize,
            $this->gctFlag,
            $this->sortFlag,
            $this->screen
        );
    }

    /**
     * The Header identifies the GIF Data Stream in context.
     */
    protected function readHeader()
    {
        $this->readBytes(6); // Magical number GIF89a or GIF87a
    }

    /**
     * The Logical Screen Descriptor contains the parameters necessary to define
     * the area of the display device within which the images will be rendered.
     */
    protected function readLogicalScreenDescriptor()
    {
        $this->readBytes(7);

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
            } else {
                if ($u == 0x04) {
                    $this->currentFrame = new Frame();

                    $this->currentFrame->setDisposalMethod(
                        (isset($this->buffer[4]) ? $this->buffer[4] : 0) & 0x80
                        ? ($this->buffer[0] >> 2) - 1
                        : ($this->buffer[0] >> 2) - 0
                    );

                    $this->currentFrame->setDuration(
                        ($this->buffer[1] | $this->buffer[2] << 8)
                    );

                    if ($this->buffer[3]) {
                        $this->transparentI = $this->buffer[3];
                    }
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
        $gctFlag = $this->buffer[8] & 0x80 ? 1 : 0;
        if ($gctFlag) {
            $code = $this->buffer[8] & 0x07;
            $sort = $this->buffer[8] & 0x20 ? 1 : 0;
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

        /*
         * GIF Data Begin
         */

        $stream = $this->currentFrame->getStream();
        $stream->writeString(
            $this->transparentI ? "GIF89a" : "GIF87a"
        );

        $stream->writeBytes($this->screen);
        if ($gctFlag == 1) {
            $this->readBytes(3 * $size);
            if ( $this->transparentI ) {
                $this->transparentR = $this->buffer[3 * $this->transparentI + 0];
                $this->transparentG = $this->buffer[3 * $this->transparentI + 1];
                $this->transparentB = $this->buffer[3 * $this->transparentI + 2];
            }
            $stream->writeBytes($this->buffer);
        } else {
            if ($this->transparentI) {
                $this->transparentR = $this->globalColorTable[3 * $this->transparentI + 0];
                $this->transparentG = $this->globalColorTable[3 * $this->transparentI + 1];
                $this->transparentB = $this->globalColorTable[3 * $this->transparentI + 2];
            }
            $stream->writeBytes($this->globalColorTable);
        }
        if ($this->transparentI) {
            $stream->writeString("!\xF9\x04\x1\x0\x0".chr($this->transparentI)."\x0");
        }
        $stream->writeBytes([0x2C]);
        $screen[8] &= 0x40;
        $stream->writeBytes($screen);
        $this->readBytes(1);
        $stream->writeBytes($this->buffer);
        while (true) {
            $this->readBytes(1);
            $stream->writeBytes($this->buffer);
            if (($u = $this->buffer[0]) == 0x00) {
                break;
            }
            $this->readBytes($u);
            $stream->writeBytes($this->buffer);
        }

        $stream->writeBytes([0x3B]);

        /*
         * GIF Data end
         */

        $onFrameDecoded = $this->onFrameDecoded;
        $onFrameDecoded($this->currentFrame, $this->frameIndex++);
    }

    /**
     * @param $bytesCount
     * @return bool
     */
    protected function readBytes($bytesCount)
    {
        return $this->stream->readBytes($bytesCount, $this->buffer);
    }

    /**
     * @return int
     */
    public function getLoopRepetitions()
    {
        return $this->repetitions;
    }
}