##Introduction
###What is that?
gif-endec is a GIF encoder and decoder. It allows you to split animated GIFs into separate frames. You can also extract frame durations and disposal method (disposal method indicates the way in which the graphic is to be treated after being displayed).

###Performance
Thanks to some code optimizations, this library decodes animated GIFs much faster than [Sybio/GifFrameExtractor](https://github.com/Sybio/GifFrameExtractor). It also optimizes memory usage, allowing you to process decoded frames one after another. It doesn't load all frames to memory at once.

###Installation
Install this package with Composer.
```json
{
    "require": {
        "stil/gif-endec": "*"
    }
}
```

Split animated GIF into frames
------------------------------
In this example we'll split this animated GIF into separate frames.
![](https://i.imgur.com/QWFJQR2.gif)


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
    $frame->getStream()->copyContentsToFile(
        __DIR__."/frames/frame{$paddedIndex}.gif"
    );
    // Or get binary data as string:
    // $frame->getStream()->getContents()
    
    /**
     * You can access frame duration using Frame::getDuration() method, ex.:
     */
     echo $frame->getDuration()."\n";
});
```

The result frames will be written to directory:
![](http://i.imgur.com/NLwHdo4.png)

##Render animated GIFs' frames
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


##Create an animation
Assume now, that we want to slow down this skateboarder animation a little bit, so we can see how to make such trick.
We already have splitted frames in `skateboarder/frame*.gif` directory.

```php
<?php
use GIFEndec\Color;
use GIFEndec\Encoder;
use GIFEndec\Frame;
use GIFEndec\MemoryStream;

$gif = new Encoder();

foreach (glob('skateboarder/frame*.gif') as $file) {
    $stream = new MemoryStream();
    $stream->loadFromFile($file);
    $frame = new Frame();
    $frame->setDisposalMethod(1);
    $frame->setStream($stream);
    $frame->setDuration(30); // 0.30s
    $frame->setTransparentColor(new Color(255, 255, 255));
    $gif->addFrame($frame);
}

$gif->addFooter(); // Required after you're done with adding frames

// Copy result animation to file
$gif->getStream()->copyContentsToFile('skateboarder/animation.gif');
```

This is how our slowed down animation would look like:
![](http://i.imgur.com/iddzN5M.gif)

##Disposal methods explained
Disposal Method indicates the way in which the graphic is to be treated after being displayed.
```
Values :    0 -   No disposal specified. The decoder is
                  not required to take any action.
            1 -   Do not dispose. The graphic is to be left
                  in place.
            2 -   Restore to background color. The area used by the
                  graphic must be restored to the background color.
            3 -   Restore to previous. The decoder is required to
                  restore the area overwritten by the graphic with
                  what was there prior to rendering the graphic.
         4-7 -    To be defined.
```
Source: http://www.w3.org/Graphics/GIF/spec-gif89a.txt
