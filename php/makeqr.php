<?php
if ($argc != 3) {
	fwrite(STDERR, "Usage: php makeqr.php <string> <output.png>\n");
	exit(1);
}
if ($argv[2] === '') {
	fwrite(STDERR, "The output filename must not be empty.\n");
	exit(1);
}

require dirname(__FILE__).'/QRGen.class.php';

$img = QRGen::createQRCode($argv[1], 3);
if ($img === false) {
	fwrite(STDERR, "Could not generate QR code: input is too long or image creation failed.\n");
	exit(1);
}
$img2 = imagecreate(500, 500);
if ($img2 === false) {
	fwrite(STDERR, "Could not create output image.\n");
	exit(1);
}
imagecolorallocate($img2, 255, 255, 255);
$scale = intdiv(500, imagesx($img) + 8);
$size = imagesx($img) * $scale;
$offset = intdiv(500 - $size, 2);
imagecopyresized($img2, $img, $offset, $offset, 0, 0, $size, $size, imagesx($img), imagesy($img));
if (!imagepng($img2, $argv[2])) {
	fwrite(STDERR, "Could not write PNG image to ".$argv[2].".\n");
	exit(1);
}
