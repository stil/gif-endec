<?php
namespace GIFEndec;

interface DecoderInterface
{
    public function __construct(MemoryStream $gifStream);
    public function decode(callable $onFrameDecoded);
    public function getLoopRepetitions();
}
