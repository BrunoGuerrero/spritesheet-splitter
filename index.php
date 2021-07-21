<?php

	disable_ob();

	set_time_limit(1800);

	if(!file_exists("source.png")) {
		println("Error - Could not open source file. Please ensure your spritesheet file is stored in the same directory as this script and is named \"source.png\"");
		if(php_sapi_name() === "cgi-fcgi") {
			readline("");
		}
		die();
	}

	$image = imagecreatefrompng("source.png");

	global $bimage;
	list($width, $height, $type, $attr) = getimagesize('source.png');
	$bimage = imagecreatefrompng("source.png");

	println("Image width : $width");
	println("Image height : $height");
	println("Image type : $type");
	br();

	$folderName = 'output/' . date('Y-m-d_His');

	if(mkdir($folderName, 0777, true)) {
		println("Folder " . $folderName . " created.");
	}

	if(imagepng($bimage, $folderName . "/_source.png")) {
		println("Source image copied into output folder as `_source.png`");
	}

	$i = 0;
	$j = 0;
	$ref = imagecolorat($image, 0, 0);		// Color used as reference for background color
	$pixel = imagecolorat($image, 0, 0);	

	$fullBuffer = [];	// Buffer for all already visited pixels in the image.
	$buffer = [];		// Temporary buffer used to store all visited pixels for a sprite.

	$it = 0;			// Total number of found sprites.

	for($y = 0; $y < $height; $y++) {
		for($x = 0; $x < $width; $x++) {
			$pixel = imagecolorat($image, $x, $y);

			// Triggers sprite exploration if encountered pixel is unvisited and different from background color
			if($ref !== imagecolorat($image, $x, $y) && !isset($fullBuffer[$x][$y])) {

				// Explores image starting from x,y pixel position, looking for all neighbours.
				// All pixels of said sprite are stored in $buffer.
				explore($image, $x, $y, $ref, $buffer);
				
				// Defines a rectangle from sprite boundaries
				$minX = PHP_INT_MAX;
				$maxX = PHP_INT_MIN;
				$minY = PHP_INT_MAX;
				$maxY = PHP_INT_MIN;
		
				foreach($buffer as $bx => $col) {
					if($bx < $minX) {
						$minX = $bx;
					}
					if($bx > $maxX) {
						$maxX = $bx;
					}
					foreach($col as $by => $val) {
						if($by < $minY) {
							$minY = $by;
						}
						if($by > $maxY) {
							$maxY = $by;
						}
					}
				}
		
				// Saves a copy of the sprite rectangle into its own file
				$newImage = imagecrop($image, ['x' => $minX, 'y' => $minY, 'width' => $maxX - $minX + 1, 'height' => $maxY - $minY + 1]);
				$filename = $folderName . "/sprite_" . $it . ".png";

				imagecolortransparent($newImage, $ref);

				if(imagepng($newImage, $filename)) {
					println($filename . " saved.");
					if(php_sapi_name() !== "cgi-fcgi") {
						println("<img src=\"$filename\">");
					}
					br();
					$it++;
				}

				// -- Saves buffer as PNG. Uncomment to allow for checking all visited pixels for debugging.
				//imagesetpixel($bimage, $x, $y, 0x00ff00);
				//@imagepng($bimage, "b_" . time() . ".png");

				// Adds all visited pixels into final buffer, and empties temporary buffer for next sprite.
				$fullBuffer = $buffer + $fullBuffer;
				$buffer = [];
			}
		}
	}

	echo "$it individual sprites found.";
	if(php_sapi_name() === "cgi-fcgi") {
		readline("");
	}

	/** Disables output buffering for instant debugging */
	function disable_ob() {
		ini_set('output_buffering', 'off');
		ini_set('zlib.output_compression', false);
		ini_set('implicit_flush', true);
		ob_implicit_flush(true);
		while (ob_get_level() > 0) {
			$level = ob_get_level();
			ob_end_clean();
			if (ob_get_level() == $level) break;
		}
		if (function_exists('apache_setenv')) {
			apache_setenv('no-gzip', '1');
			apache_setenv('dont-vary', '1');
		}
	}

	/** Prints line, depending on php client */
	function println($string) {
		if(php_sapi_name() === "cgi-fcgi") {
			print $string . "\r\n";
		} else {
			print $string . "<br>";
		}
	}

	/** Line break depending on php client */
	function br() {
		if(php_sapi_name() === "cgi-fcgi") {
			//print "\r\n";
		} else {
			print "<br>";
		}
	}

	/** Explores image starting from pixel at x,y position to find all continuous pixels */
	function explore($image, $x, $y, $ref, &$buffer) {

		addToBuffer($image, $x, $y, $buffer);
		
		foreach($buffer as $x => $col) {
			foreach($col as $y => $val) {
				$neighbor = findNeighbor($image, $x, $y, $ref, $buffer);
				$found = false;

				// Explores sprite until no more continuous pixel is found
				while($neighbor !== null) {
					addToBuffer($image, $neighbor[0], $neighbor[1], $buffer);
					$neighbor = findNeighbor($image, $neighbor[0], $neighbor[1], $ref, $buffer);
					$found = true;
				}		

				if($found) {
					explore($image, $x, $y, $ref, $buffer);
				}
			}
		}	

		return null;
	}

	/** Adds visited pixel to buffer to avoid pixel being visited twice */
	function addToBuffer($image, $x, $y, &$buffer) {	
		global $bimage;

		if(!array_key_exists($x, $buffer)) {
			$buffer[$x] = [];
			//println("Creating buffer for $x");
		}
		$buffer[$x][$y] = true;

		//imagesetpixel($bimage, $x, $y, 0x0000ff);
	}

	/** Finds next unvisited neighbor pixel */
	function findNeighbor($image, $x, $y, $ref, $buffer) {
		list($width, $height, $type, $attr) = getimagesize('source.png');

		$shifts = [[0, -1], [1, -1], [1, 0], [1, 1], [0, 1], [-1, 1], [-1, 0], [-1, -1]];
		foreach($shifts as $shift) {
			$eX = $x + $shift[0];
			$eY = $y + $shift[1];

			if($eX >= 0 && $eX < $width && $eY >= 0 && $eY < $height) {
				if($ref !== imagecolorat($image, $eX, $eY) && !isset($buffer[$eX][$eY])) {
					return [$eX, $eY];
				}
			}
		}

		return null;
	}