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

    public function __construct($bytes = "")
    {
        $this->bytes = $bytes;
        $this->length = strlen($bytes);
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

        $buffer = [];
        for ($i = 0; $i < $bytesCount; $i++) {
            $buffer[] = ord($this->bytes[$this->offset++]);
        }

        return true;
    }

    /**
     * @param array $bytes Array of ASCII bytes as integers to write
     */
    public function writeBytes(array $bytes)
    {
        foreach ($bytes as $byte) {
            $this->bytes .= chr($byte);
            $this->offset++;
        }
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
}