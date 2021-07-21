# PHP Spritesheet Splitter
A rudimentary PHP tool to decompose PNG spritesheets into single transparent PNG sprite files.

### Disclaimer

This tool has been created for a specific need and therefore only works in some conditions :

- All sprites on the spritesheet must be on the same background color, top-left pixel's color begin used as reference for said background color
- Only supports a PNG source file as of now

### Using the spritesheet splitter

Simply store your spritesheet as `source.png` in the repository root folder, then call `index.php` from your browser or CGI client. All resulting sprites, as well as a copy of the original source, will be stored as individual PNG files in a timestamped folder, in the `output` directory.

### Using the spritesheet repacker

This project also includes a basic spritesheet repacker. The script simply adds sprites next to each other into a single file, along with a CSS file for web usage of the resulting spritesheet. No optimization is considered whatsoever and as such, some more dedicated tool such as TexturePacker is instead widely recommended for further needs.

To use, simply put all sprites PNG files into the `input` folder, then execute `repack.php` from your brower or CGI client. The resulting PNG and CSS files will be stored in the `output/_repacked` directory, in a timestamped folder.

### Known bugs

Some issues, such as sprites wrongly split or crashes may happen on some spritesheets. While these have been investigated, no fix have been found as of now.