#gif-endec

###Introduction
gif-endec is a GIF encoder and decoder.


###Installation
Install this package with Composer
```json
{
    "require": {
        "stil/gif-endec": "*"
    }
}
```

###Split animated GIF into frames
In this example we'll split this animated GIF into separate frames.
![](https://raw.githubusercontent.com/stil/gif-endec/master/tests/gifs/test1.gif)



```php
<?php
require __DIR__.'/../vendor/autoload.php';

use GIFEndec\Decoder;
use GIFEndec\MemoryStream;
use GIFEndec\Frame;

/**
 * Load GIF to MemoryStream
 */
$gifStream = new MemoryStream(file_get_contents("path/to/animation.gif"));

/**
 * Create Decoder instance from MemoryStream
 */
$gifDecoder = new Decoder($gifStream);

/**
 * Run decoder. Pass callback function to process decoded Frames when they're ready.
 */
$gifDecoder->decode(function (Frame $frame, $index) {
    /**
     * Convert frame index to zero-padded strings (001, 002, 003)
     */
    $paddedIndex = str_pad($index, 3, '0', STR_PAD_LEFT);
    
    /**
     * Write frame images to directory
     */
    file_put_contents(
        __DIR__."/frames/frame{$paddedIndex}.gif",
        $frame->getStream()->getContents()
    );
    
    /**
     * You can access frame duration using Frame::getDuration() method, ex.:
     */
     echo $frame->getDuration()."\n";
});
```

The result frames will be written to directory:
![](http://i.imgur.com/NLwHdo4.png)
