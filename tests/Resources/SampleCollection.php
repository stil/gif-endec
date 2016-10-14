<?php
namespace GIFEndec\Tests\Resources;

class SampleCollection
{
    /**
     * @var Sample[]
     */
    private $samples = [];

    public function __construct()
    {
        $samples = json_decode(file_get_contents(__DIR__ . '/gifs.json'), true);
        foreach ($samples as $name => $data) {
            if (!$data['enabled']) {
                continue;
            }
            $this->samples[] = new Sample($name, $data);
        }

        $this->downloadAll();
    }

    /**
     * @return Sample[]
     */
    public function read()
    {
        return $this->samples;
    }

    private function downloadAll()
    {
        foreach ($this->samples as $sample) {
            $sample->download();
        }
    }
}
