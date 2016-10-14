<?php
namespace GIFEndec\IO;

class MemoryStream extends PhpStream
{
    public function __construct()
    {
        $this->phpStream = fopen('php://memory', 'wb+');
    }
}
