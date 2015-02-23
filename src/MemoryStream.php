<?php
namespace GIFEndec;

class MemoryStream
{
    /**
     * @var string Bytes array
     */
    protected $bytes;

    /**
     * @var resource
     */
    protected $phpStream;

    public function __construct()
    {
        $this->phpStream = fopen("php://memory", "wb+");
    }

    public function loadFromFile($path)
    {
        $this->phpStream = fopen($path, 'r');
        stream_set_read_buffer($this->phpStream, 1024*1024);
        $this->seek(0);
    }

    /**
     * @param int $bytesCount How many bytes to read
     * @param array $buffer Reference to buffer array where read bytes will be written
     */
    public function readBytes($bytesCount, &$buffer)
    {
        if ($bytesCount === 1) {
            $buffer = [ord(fread($this->phpStream, 1))];
        } else {
            // microptimizations
            $bytes = fread($this->phpStream, $bytesCount);
            $buffer = array_values(unpack('C*', $bytes));
        }
    }

    /**
     * @param array $bytes Array of ASCII bytes as integers to write
     */
    public function writeBytes(array $bytes)
    {
        $count = count($bytes);
        if ($count == 1) {
            fwrite($this->phpStream, chr($bytes[0]));
        } else {
            fwrite($this->phpStream, call_user_func_array("pack", array_merge(["C*"], $bytes)));
        }
    }

    /**
     * @param string $str String to write
     */
    public function writeString($str)
    {
        fwrite($this->phpStream, $str);
    }

    /**
     * @return string Whole contents of stream
     */
    public function getContents()
    {
        $this->seek(0);
        return stream_get_contents($this->phpStream);
    }

    /**
     * @param string $path
     */
    public function copyContentsToFile($path)
    {
        $fp = fopen($path, 'w');
        $this->seek(0);
        stream_copy_to_stream($this->phpStream, $fp);
        fclose($fp);
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
        fseek($this->phpStream, $offset, $whence);
    }

    /**
     * @return bool TRUE if stream reached EOF, FALSE otherwise
     */
    public function hasReachedEOF()
    {
        return feof($this->phpStream);
    }
}