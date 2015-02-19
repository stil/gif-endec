<?php
namespace GIFEndec\Tests;

use GIFEndec\Decoder;
use GIFEndec\MemoryStream;
use GIFEndec\Renderer;

class RendererTest extends TestCase
{
    public function testRender()
    {
        for ($i = 1; $i <= 6; $i++) {
            $this->render("test$i");
        }
    }

    protected function render($name)
    {
        $action = "render";
        $animation = __DIR__."/gifs/$name.gif";
        $checksumPath = __DIR__."/gifs/$name.$action.json";
        $dir = __DIR__."/output/$action/$name";

        $frameCount = 0;
        $checksums = [];
        $hasChecksums = $this->loadChecksums($checksumPath, $checksums);
        $this->createDirOrClear($dir);

        $stream = new MemoryStream();
        $stream->loadFromFile($animation);
        $decoder = new Decoder($stream);
        $renderer = new Renderer($decoder);
        $renderer->start(function ($gd, $index) use (&$checksums, $hasChecksums, &$frameCount, $name, $dir) {
            $frameCount++;
            $paddedIndex = str_pad($index, 3, '0', STR_PAD_LEFT);

            $outputPath = "$dir/frame{$paddedIndex}.png";
            imagepng($gd, $outputPath, 4, PNG_ALL_FILTERS);

            $checksum = sha1(file_get_contents($outputPath));
            if ($hasChecksums) {
                $this->assertEquals($checksum, $checksums[$index]);
            }
            $checksums[$index] = $checksum;
        });

        file_put_contents("$dir/$name.$action.json", json_encode($checksums, JSON_PRETTY_PRINT));
    }
}
