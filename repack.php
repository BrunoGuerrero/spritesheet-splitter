<?php 

    class Sprite {

        public $image;
        public $name;
        public $x;
        public $y;
        public $width;
        public $height;

        function __construct($image, $name, $x, $y, $width, $height) {
            $this->image = $image;
            $this->name = $name;
            $this->x = $x;
            $this->y = $y; 
            $this->width = $width;
            $this->height = $height;
        }
    }

    disable_ob();

    $cur = [0, 0];
    $maxHeight = 0;
    $maxY = 0;
    $maxX = 0;
    $sprites = [];

    $files = scandir('input');
    foreach($files as $file) {
        if(strlen($file) > 4 && substr($file, -4) == '.png') {
            $filepath = 'input/' . $file;
            $image = imageCreateFromPNG($filepath);
            list($width, $height, $type, $attr) = getimagesize($filepath);
            $name = substr($file, 0, -4);
            
            if($cur[0] + $width > 256) {
                $cur[0] = 0;
                $cur[1] += $maxHeight;
                $maxHeight = 0;
            }

            $maxX = max($cur[0] + $width, $maxX);
            $maxY = max($cur[1] + $height, $maxY);

            $sprites[] = new Sprite($image, $name, $cur[0], $cur[1], $width, $height);

            $maxHeight = max($maxHeight, $height);
            $cur[0] += $width;
        }
    }

	$folderName = 'output/_repacks/' . date('Y-m-d_His');
    mkdir($folderName, 0777, true);

    $outputImage = imagecreate($maxX, $maxY);

    foreach($sprites as $sprite) {
        imagecopy($outputImage, $sprite->image,  $sprite->x,  $sprite->y, 0, 0, $sprite->width, $sprite->height);
    }

    imagepng($outputImage, $folderName . "/spritesheet.png");
    buildCss($sprites, $folderName);

	readline("Spritesheet and CSS file created.");

    /** Creates CSS file for spritesheet usage on web platforms */
    function buildCss($sprites, $folderName) {
        $file = fopen($folderName . '/spritesheet.css', 'w');
        fwrite($file, ".sprite {background-image: url(spritesheet.png); display: inline-block;}");

        foreach($sprites as $sprite) {
            fwrite($file, "." . $sprite->name . " {background-position: " . $sprite->x . " " . $sprite->y . "; width: " . $sprite->width . "; height: " . $sprite->height . "}\r\n");
        }

        fclose($file);
    }

    function disable_ob() {
		// Turn off output buffering
		ini_set('output_buffering', 'off');
		// Turn off PHP output compression
		ini_set('zlib.output_compression', false);
		// Implicitly flush the buffer(s)
		ini_set('implicit_flush', true);
		ob_implicit_flush(true);
		// Clear, and turn off output buffering
		while (ob_get_level() > 0) {
			// Get the curent level
			$level = ob_get_level();
			// End the buffering
			ob_end_clean();
			// If the current level has not changed, abort
			if (ob_get_level() == $level) break;
		}
		// Disable apache output buffering/compression
		if (function_exists('apache_setenv')) {
			apache_setenv('no-gzip', '1');
			apache_setenv('dont-vary', '1');
		}
	}