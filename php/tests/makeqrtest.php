<?php
require dirname(__DIR__).'/QRGen.class.php';

function runMakeQR($args) {
	$command = array_merge(array(PHP_BINARY, dirname(__DIR__).'/makeqr.php'), $args);
	$process = proc_open($command, array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes);
	if ($process === false) throw new Exception('Could not start makeqr.php');
	fclose($pipes[0]);
	$stdout = stream_get_contents($pipes[1]);
	$stderr = stream_get_contents($pipes[2]);
	fclose($pipes[1]);
	fclose($pipes[2]);
	return array(proc_close($process), $stdout, $stderr);
}

function check($condition, $message) {
	if (!$condition) throw new Exception($message);
}

$filename = tempnam(sys_get_temp_dir(), 'makeqr-');
if ($filename === false) throw new Exception('Could not create temporary file');
try {
	foreach (array(array(), array('data'), array('data', $filename, 'extra')) as $args) {
		list($status, $stdout, $stderr) = runMakeQR($args);
		check($status !== 0 && strpos($stderr, 'Usage:') !== false, 'Incorrect argument count must fail');
	}
	foreach (array('', 'Hello, QR!') as $data) {
		list($status, $stdout, $stderr) = runMakeQR(array($data, $filename));
		check($status === 0, 'PNG generation failed: '.$stderr);
		$image = imagecreatefrompng($filename);
		check($image !== false && imagesx($image) == 500 && imagesy($image) == 500, 'Invalid PNG image');
		$source = QRGen::createQRCode($data, 3);
		$scale = intdiv(500, imagesx($source) + 8);
		$offset = intdiv(500 - imagesx($source) * $scale, 2);
		$corner = imagecolorsforindex($image, imagecolorat($image, 0, 0));
		check($corner['red'] == 255 && $corner['green'] == 255 && $corner['blue'] == 255, 'Missing white quiet zone');
		for ($y = 0; $y < imagesy($source); $y++) {
			for ($x = 0; $x < imagesx($source); $x++) {
				$rgb = imagecolorsforindex($image, imagecolorat($image, $offset + $x * $scale, $offset + $y * $scale));
				$black = $rgb['red'] == 0 && $rgb['green'] == 0 && $rgb['blue'] == 0;
				check($black === (imagecolorat($source, $x, $y) == 1), 'QR code does not match input');
			}
		}
	}
	list($status, $stdout, $stderr) = runMakeQR(array(str_repeat('a', 1274), $filename));
	check($status !== 0 && strpos($stderr, 'too long') !== false, 'Oversized input must fail');
	list($status, $stdout, $stderr) = runMakeQR(array('data', ''));
	check($status !== 0 && strpos($stderr, 'filename') !== false, 'Empty filename must fail');
	list($status, $stdout, $stderr) = runMakeQR(array('data', $filename.'/output.png'));
	check($status !== 0 && strpos($stderr, 'Could not write PNG') !== false, 'Write failure must be reported');
} finally {
	unlink($filename);
}
echo "makeqr tests passed\n";
