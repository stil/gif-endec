<?php
namespace GIFEndec\Tests;

class TestCase extends \PHPUnit_Framework_TestCase
{
    protected $testGifs = [
        'test1' => 'https://i.imgur.com/QWFJQR2.gif',
        'test2' => 'https://i.imgur.com/eCSiYLY.gif',
        'test3' => 'https://i.imgur.com/ay0AAt5.gif',
        'test4' => 'https://i.imgur.com/NRv75UE.gif',
        'test5' => 'https://i.imgur.com/a6u20G3.gif',
        'test6' => 'https://i.imgur.com/pd32IDd.gif'
    ];

    public function setUp()
    {
        foreach ($this->testGifs as $name => $url) {
            $path = __DIR__."/gifs/$name.gif";
            if (file_exists($path)) {
                continue;
            }

            $ch = curl_init($url);
            $fp = fopen($path, 'w');
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_exec($ch);
            curl_close($ch);
            fclose($fp);
        }
    }

    protected function createDirOrClear($dir)
    {
        if (!is_dir($dir)) {
            mkdir($dir, null, true);
        } else {
            foreach (glob("$dir/*") as $file) {
                unlink($file);
            }
        }
    }

    protected function loadChecksums($checksumPath, &$checksums)
    {
        if (file_exists($checksumPath)) {
            $hasChecksums = true;
            $checksums = json_decode(file_get_contents($checksumPath), true);
        } else {
            $hasChecksums = false;
            $checksums = [];
        }

        return $hasChecksums;
    }
}