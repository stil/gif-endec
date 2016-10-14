<?php
namespace GIFEndec\Tests;

use GIFEndec\Decoder;
use GIFEndec\Events\FrameDecodedEvent;
use GIFEndec\IO\FileStream;
use GIFEndec\Tests\Resources\Sample;
use GIFEndec\Tests\Resources\SampleCollection;

class DecoderTest extends TestCase
{
    public function testDecode()
    {
        $samples = new SampleCollection();
        foreach ($samples->read() as $sample) {
            $this->decode($sample);
        }
    }

    protected function decode(Sample $sample)
    {
        $frameCount = 0;
        $checksums = [];

        $dir = $sample->emptyRawFramesDir();

        $stream = new FileStream($sample->localPath());
        $decoder = new Decoder($stream);

        $decoder->decode(function (FrameDecodedEvent $event) use (&$checksums, &$frameCount, $dir, $sample) {
            $frameCount++;

            $stream = $event->decodedFrame->getStream();
            //$paddedIndex = str_pad($event->frameIndex, 3, '0', STR_PAD_LEFT);
            //$stream->copyContentsToFile("$dir/frame{$paddedIndex}.gif");

            $checksum = sha1($stream->getContents());
            $this->assertEquals($checksum, $sample->getFrameRawSHA1($event->frameIndex));
            $checksums[$event->frameIndex] = $checksum;
        });

        file_put_contents("$dir/_sha1.json", json_encode($checksums, JSON_PRETTY_PRINT));
    }
}
