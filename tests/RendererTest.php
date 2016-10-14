<?php
namespace GIFEndec\Tests;

use GIFEndec\Decoder;
use GIFEndec\Events\FrameRenderedEvent;
use GIFEndec\IO\FileStream;
use GIFEndec\IO\MemoryStream;
use GIFEndec\Renderer;
use GIFEndec\Tests\Resources\Sample;
use GIFEndec\Tests\Resources\SampleCollection;

class RendererTest extends TestCase
{
    public function testRender()
    {
        $samples = new SampleCollection();
        foreach ($samples->read() as $sample) {
            $this->render($sample);
        }
    }

    protected function render(Sample $sample)
    {
        $frameCount = 0;
        $checksums = [];

        $dir = $sample->emptyRenderedFramesDir();

        $stream = new FileStream($sample->localPath());
        $decoder = new Decoder($stream);
        $renderer = new Renderer($decoder);

        $renderer->start(function (FrameRenderedEvent $event) use (&$checksums, &$frameCount, $dir, $sample) {
            $frameCount++;

            $stream = new MemoryStream();
            ob_start();
            imagepng($event->renderedFrame, null, 4, PNG_ALL_FILTERS);
            $stream->writeString(ob_get_contents());
            ob_end_clean();

            //$paddedIndex = str_pad($event->frameIndex, 3, '0', STR_PAD_LEFT);
            //$stream->copyContentsToFile("$dir/frame{$paddedIndex}.gif");

            $checksum = sha1($stream->getContents());
            $this->assertEquals($checksum, $sample->getFrameRenderedSHA1($event->frameIndex));
            $checksums[$event->frameIndex] = $checksum;
        });

        file_put_contents("$dir/_sha1.json", json_encode($checksums, JSON_PRETTY_PRINT));
    }
}
