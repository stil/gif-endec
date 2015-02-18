#gif-endec

###What is that?
gif-endec is a GIF encoder and decoder. It allows you to split animated GIFs into separate frames. You can also extract frame durations and disposal method (disposal method indicates the way in which the graphic is to be treated after being displayed).

###Performance
Thanks to some code optimizations, this library decodes animated GIFs about 2.5x faster than [Sybio/GifFrameExtractor](https://github.com/Sybio/GifFrameExtractor). It also optimizes memory usage, allowing you to process decoded frames one after another. It doesn't load all frames to memory at once.

###Installation
Install this package with Composer.
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

use GIFEndec\MemoryStream;
use GIFEndec\Decoder;
use GIFEndec\Frame;

/**
 * Load GIF to MemoryStream
 */
$gifStream = new MemoryStream();
$gifStream->loadFromFile("path/to/animation.gif");

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

###Render animated GIFs' frames
If your GIF is saved using transparency, some frames might look like this:
![](http://i.imgur.com/NIJGVnw.png)

In following example you'll see how to render GIF frames.
```php
<?php
require __DIR__.'/../vendor/autoload.php';

use GIFEndec\MemoryStream;
use GIFEndec\Decoder;
use GIFEndec\Frame;
use GIFEndec\Renderer;

/**
 * Load GIF to MemoryStream
 */
$gifStream = new MemoryStream();
$gifStream->loadFromFile("path/to/animation.gif");

/**
 * Create Decoder instance from MemoryStream
 */
$gifDecoder = new Decoder($gifStream);

/**
 * Create Renderer instance
 */
$gifRenderer = new Renderer($gifDecoder);

/**
 * Run decoder. Pass callback function to process decoded Frames when they're ready.
 */
$gifRenderer->start(function ($gdResource, $index) {
    /**
     * $gdResource is a GD image resource. See http://php.net/manual/en/book.image.php
     */
    
    /**
     * Write frame images to directory
     */
    imagepng($gdResource, __DIR__."/frames/frame{$index}.png");
});
```
