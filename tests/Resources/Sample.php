<?php
namespace GIFEndec\Tests\Resources;

class Sample
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $data;

    /**
     * Sample constructor.
     * @param string $name
     * @param array $data
     */
    public function __construct($name, array $data)
    {
        $this->name = $name;
        $this->data = $data;
    }

    public function name()
    {
        return $this->name;
    }

    public function url()
    {
        return $this->data['url'];
    }

    private function localDir()
    {
        $dir = __DIR__ . "/output/{$this->name}";
        if (!is_dir($dir)) {
            mkdir($dir, null, true);
        }

        return $dir;
    }

    public function localPath()
    {
        return $this->localDir() . "/original.gif";
    }

    public function getFrameRenderedSHA1($index)
    {
        return $this->data["frames_rendered_sha1"][$index];
    }

    public function getFrameRawSHA1($index)
    {
        return $this->data["frames_raw_sha1"][$index];
    }

    public function emptyRenderedFramesDir()
    {
        return $this->openOrClearOutputDir('rendered');
    }

    public function emptyRawFramesDir()
    {
        return $this->openOrClearOutputDir('raw');
    }

    public function download()
    {
        if (file_exists($this->localPath())) {
            return;
        }

        $ch = curl_init($this->url());
        $fp = fopen($this->localPath(), 'w');
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
    }

    private function openOrClearOutputDir($type)
    {
        $dir = "{$this->localDir()}/$type/";
        $this->createDirOrClear($dir);
        return $dir;
    }

    private function createDirOrClear($dir)
    {
        if (!is_dir($dir)) {
            mkdir($dir, null, true);
        } else {
            foreach (glob("$dir/*") as $file) {
                unlink($file);
            }
        }
    }
}
