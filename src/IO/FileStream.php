<?php
namespace GIFEndec\IO;

class FileStream extends PhpStream
{
    public function __construct($path, $mode = 'rb')
    {
        $this->phpStream = fopen($path, $mode);
        stream_set_read_buffer($this->phpStream, 1024*1024);
        $this->seek(0);
    }
}
