<?php
require dirname(__DIR__).'/QRGen.class.php';

function check($condition, $message) {
	if (!$condition) throw new Exception($message);
}

function payload($length) {
	$data = '';
	for ($i = 0; $i < $length; $i++) $data .= chr(($i * 73 + 19) % 256);
	return $data;
}

function matrixHash($image) {
	$bits = '';
	for ($y = 0; $y < imagesy($image); $y++) {
		for ($x = 0; $x < imagesx($image); $x++) {
			$bits .= imagecolorat($image, $x, $y) == 1 ? '1' : '0';
		}
	}
	return hash('sha256', $bits);
}

// Matrices independently generated with Python qrcode in byte mode.
$fixtures = array(
	array(2, 0, 1, '8fd3566c525ca8ce2896e5e4f12d9bd794dea12aded5af7c864e74673bb0f7a1'),
	array(3, 35, 5, '5848d152ffe0f065a6cf467e71b213f4954029bb5be5a94b9bd10f9950e05547'),
	array(0, 135, 7, '1627cb178d795aa6488fc8748207505738a62f5ffc62ef65d5d55172890226d9'),
	array(1, 181, 10, '1f96da67cfe8660d0f9fb035854da60a630fda55408fba305c7580d9ba19bf80'),
	array(2, 752, 27, '9f4c019cca08dad576982a145abaf72982bba6db4485d6e2ed060e6b56424719'),
	array(3, 1220, 40, '3be9d992af0472aebfed0b6d750daef109760fb7823e97bea59b6465ebf94f0f')
);
foreach ($fixtures as $fixture) {
	list($level, $length, $version, $expected) = $fixture;
	$image = QRGen::createQRCode(payload($length), $level);
	check($image !== false, "Generation failed for version $version level $level");
	check(imagesx($image) == 17 + 4 * $version && imagesy($image) == imagesx($image),
		"Incorrect dimensions for version $version level $level");
	check(matrixHash($image) === $expected, "Incorrect matrix for version $version level $level");
}

foreach (array(array(17, 21), array(18, 25), array(2953, 177)) as $case) {
	$image = QRGen::createQRCode(str_repeat('a', $case[0]), 0);
	check($image !== false && imagesx($image) == $case[1], "Incorrect capacity at length $case[0]");
}
check(QRGen::createQRCode(str_repeat('a', 2954), 0) === false, 'Oversized input must fail');
check(matrixHash(QRGen::createQRCode('test')) === matrixHash(QRGen::createQRCode('test', 3)),
	'The default correction level must be H');
check(matrixHash(QRGen::createQRCode('test', -1)) === matrixHash(QRGen::createQRCode('test', 0)),
	'The correction level must be clamped to L');
check(matrixHash(QRGen::createQRCode('test', 4)) === matrixHash(QRGen::createQRCode('test', 3)),
	'The correction level must be clamped to H');

echo "QRGen tests passed\n";
