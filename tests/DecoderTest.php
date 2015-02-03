<?php
namespace GIFEndec\Tests;

use GIFEndec\Decoder;
use GIFEndec\MemoryStream;
use GIFEndec\Frame;

class DecoderTest extends \PHPUnit_Framework_TestCase
{
    public function testDecode()
    {
        $animation = __DIR__.'/gifs/test1.gif';

        $gifStream = new MemoryStream(file_get_contents($animation));
        $gifDecoder = new Decoder($gifStream);

        /*$md5 = [];
        $gifDecoder->decode(function (Frame $frame, $index) use (&$md5) {
            file_put_contents(
                __DIR__."/frames/frame{$index}.gif",
                $frame->getStream()->getContents()
            );
            $md5[$index] = md5($frame->getStream()->getContents());
        });
        echo json_encode($md5);*/

        $md5 = json_decode(file_get_contents(__DIR__.'/gifs/test1.json'), true);
        $gifDecoder->decode(function (Frame $frame, $index) use (&$md5) {
            file_put_contents(
                __DIR__."/frames/frame{$index}.gif",
                $frame->getStream()->getContents()
            );

            $this->assertEquals(
                md5($frame->getStream()->getContents()),
                $md5[$index]
            );
        });
    }
}
