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
     * @param int $buffer Reference to buffer array where read byte will be written
     * @return bool TRUE if succeeded, FALSE if reached end of stream
     */
    public function readByte(&$buffer)
    {
        if ($this->offset + 1 > $this->length) {
            return false;
        }

        $buffer = ord($this->bytes[$this->offset]);

        $this->offset++;
        return true;
    }

    /**
     * @param int $bytesCount How many bytes to copy
     * @param string $streamBytes Destination stream
     * @return bool TRUE if succeeded, FALSE if reached end of stream
     */
    public function copyBytes($bytesCount, &$streamBytes)
    {
        if ($this->offset + $bytesCount > $this->length) {
            return false;
        }

        if ($bytesCount === 1) {
            $streamBytes .= $this->bytes[$this->offset];
        } else {
            $streamBytes .= substr($this->bytes, $this->offset, $bytesCount);
        }

        $this->offset += $bytesCount;
        return true;
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
    public function &getPointer()
    {
        return $this->bytes;
    }

    /**
     * Sets current position at end
     */
    public function seekToEnd()
    {
        $this->offset = strlen($this->bytes) - 1;
    }
}