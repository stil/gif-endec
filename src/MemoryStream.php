<?php
namespace GIFEndec;

class MemoryStream
{
    /**
     * @var string Bytes array
     */
    protected $bytes;

    /**
     * @var int Length of bytes array
     */
    protected $length;

    /**
     * @var int Current stream position
     */
    protected $offset = 0;

    /**
     * @var resource
     */
    protected $phpStream;

    public function __construct($bytes = "")
    {
        $this->bytes = $bytes;
        $this->length = strlen($bytes);
        $this->phpStream = fopen("php://memory", "w+");
        fwrite($this->phpStream, $bytes);
    }

    /**
     * @param int $bytesCount How many bytes to read
     * @param array $buffer Reference to buffer array where read bytes will be written
     * @return bool TRUE if succeeded, FALSE if reached end of stream
     */
    public function readBytes($bytesCount, &$buffer)
    {
        if ($this->offset + $bytesCount > $this->length) {
            return false;
        }

        if ($bytesCount === 1) {
            $buffer = [ord($this->bytes[$this->offset])];
        } else {
            // microptimizations
            $bytes = substr($this->bytes, $this->offset, $bytesCount);
            $buffer = array_values(unpack('C*', $bytes));
        }

        $this->offset += $bytesCount;
        return true;
    }

    /**
     * Reads little-endian unsigned 16 bit integer from stream
     * @return int|bool Decoded integer or FALSE if end of stream
     */
    public function readUnsignedShort()
    {
        $bytesCount = 2;
        if ($this->offset + $bytesCount > $this->length) {
            return false;
        }

        $bytes = substr($this->bytes, $this->offset, $bytesCount);
        $this->offset += $bytesCount;
        return unpack('v', $bytes)[1];
    }

    /**
     * @param array $bytes Array of ASCII bytes as integers to write
     */
    public function writeBytes(array $bytes)
    {
        $count = count($bytes);
        if ($count == 1) {
            $this->bytes .= chr($bytes[0]);
        } else {
            // microptimizations
            $this->bytes .= call_user_func_array("pack", array_merge(["C*"], $bytes));
        }

        $this->offset += $count;
    }

    /**
     * @param string $str String to write
     */
    public function writeString($str)
    {
        $this->bytes .= $str;
        $this->offset += strlen($str);
    }

    /**
     * @return string Whole contents of stream
     */
    public function getContents()
    {
        return $this->bytes;
    }

    /**
     * @return string Returns reference to internal byte array
     */
    public function &getOffsetPointer()
    {
        return $this->offset;
    }

    /**
     * @return string Returns reference to internal byte array
     */
    public function &getBytesPointer()
    {
        return $this->bytes;
    }

    /**
     * @return resource
     */
    public function getPhpStream()
    {
        return $this->phpStream;
    }

    /**
     * Sets stream position
     * @param $offset
     * @param int $whence
        SEEK_SET - Set position equal to offset bytes.
        SEEK_CUR - Set position to current location plus offset.
        SEEK_END - Set position to end-of-file plus offset.
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        switch ($whence) {
            case SEEK_SET:
                $this->offset = $offset;
                break;
            case SEEK_CUR:
                $this->offset += $offset;
                break;
            case SEEK_END:
                $this->offset = strlen($this->bytes) - 1 + $offset;
                break;
        }
    }
}