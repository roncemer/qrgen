<?php
// QRGen.class.php
// Copyright (c) 2012 Ronald B. Cemer
// All rights reserved worldwide.
//
// This file is released under the BSD license.
class QRGen {
	// Number of bits used to encode the data length per version, sub-indexed by
	// encoding mode; where encoding mode 1 is Numeric, 2 is Alphanumeric, 4 is
	// Binary, and 8 is Kanji.
	private static $DATA_LENGTH_BITS_BY_VERSION_AND_ENCODING_MODE = array(
		null,								// Version 0 (nonexistent)
		array(1=>10, 2=>9, 4=>8, 8=>8),		// Version 1
		array(1=>10, 2=>9, 4=>8, 8=>8),		// Version 2
		array(1=>10, 2=>9, 4=>8, 8=>8),		// Version 3
		array(1=>10, 2=>9, 4=>8, 8=>8),		// Version 4
		array(1=>10, 2=>9, 4=>8, 8=>8),		// Version 5
		array(1=>10, 2=>9, 4=>8, 8=>8),		// Version 6
		array(1=>10, 2=>9, 4=>8, 8=>8),		// Version 7
		array(1=>10, 2=>9, 4=>8, 8=>8),		// Version 8
		array(1=>10, 2=>9, 4=>8, 8=>8),		// Version 9
		array(1=>12, 2=>11, 4=>16, 8=>10),	// Version 10
		array(1=>12, 2=>11, 4=>16, 8=>10),	// Version 11
		array(1=>12, 2=>11, 4=>16, 8=>10),	// Version 12
		array(1=>12, 2=>11, 4=>16, 8=>10),	// Version 13
		array(1=>12, 2=>11, 4=>16, 8=>10),	// Version 14
		array(1=>12, 2=>11, 4=>16, 8=>10),	// Version 15
		array(1=>12, 2=>11, 4=>16, 8=>10),	// Version 16
		array(1=>12, 2=>11, 4=>16, 8=>10),	// Version 17
		array(1=>12, 2=>11, 4=>16, 8=>10),	// Version 18
		array(1=>12, 2=>11, 4=>16, 8=>10),	// Version 19
		array(1=>12, 2=>11, 4=>16, 8=>10),	// Version 20
		array(1=>12, 2=>11, 4=>16, 8=>10),	// Version 21
		array(1=>12, 2=>11, 4=>16, 8=>10),	// Version 22
		array(1=>12, 2=>11, 4=>16, 8=>10),	// Version 23
		array(1=>12, 2=>11, 4=>16, 8=>10),	// Version 24
		array(1=>12, 2=>11, 4=>16, 8=>10),	// Version 25
		array(1=>12, 2=>11, 4=>16, 8=>10),	// Version 26
		array(1=>14, 2=>13, 4=>16, 8=>12),	// Version 27
		array(1=>14, 2=>13, 4=>16, 8=>12),	// Version 28
		array(1=>14, 2=>13, 4=>16, 8=>12),	// Version 29
		array(1=>14, 2=>13, 4=>16, 8=>12),	// Version 30
		array(1=>14, 2=>13, 4=>16, 8=>12),	// Version 31
		array(1=>14, 2=>13, 4=>16, 8=>12),	// Version 32
		array(1=>14, 2=>13, 4=>16, 8=>12),	// Version 33
		array(1=>14, 2=>13, 4=>16, 8=>12),	// Version 34
		array(1=>14, 2=>13, 4=>16, 8=>12),	// Version 35
		array(1=>14, 2=>13, 4=>16, 8=>12),	// Version 36
		array(1=>14, 2=>13, 4=>16, 8=>12),	// Version 37
		array(1=>14, 2=>13, 4=>16, 8=>12),	// Version 38
		array(1=>14, 2=>13, 4=>16, 8=>12),	// Version 39
		array(1=>14, 2=>13, 4=>16, 8=>12),	// Version 40
	);

	// Maximum number of data bits per version, sub-indexed by error correction
	// level; where EC level 0 is L, 1 is M, 2 is Q and 3 is H.
	// This data is from Table 7 -- Number of symbol characters and input data capacity
	// QR Code 2005 in ISO/IEC 18004_2006Cor 1_2009.pdf.
	private static $MAX_DATA_BITS_BY_VERSION_AND_EC_LEVEL = array(
		null,								// Version 0 (nonexistent)
		array(152, 128, 104, 72),			// Version 1
		array(272, 224, 176, 128),			// Version 2
		array(440, 352, 272, 208),			// Version 3
		array(640, 512, 384, 288),			// Version 4
		array(864, 688, 496, 368),			// Version 5
		array(1088, 864, 608, 480),			// Version 6
		array(1248, 992, 704, 528),			// Version 7
		array(1552, 1232, 880, 688),		// Version 8
		array(1856, 1456, 1056, 800),		// Version 9
		array(2192, 1728, 1232, 976),		// Version 10
		array(2592, 2032, 1440, 1120),		// Version 11
		array(2960, 2320, 1648, 1264),		// Version 12
		array(3424, 2672, 1952, 1440),		// Version 13
		array(3688, 2920, 2088, 1576),		// Version 14
		array(4184, 3320, 2360, 1784),		// Version 15
		array(4712, 3624, 2600, 2024),		// Version 16
		array(5176, 4056, 2936, 2264),		// Version 17
		array(5768, 4504, 3176, 2504),		// Version 18
		array(6360, 5016, 3560, 2728),		// Version 19
		array(6888, 5352, 3880, 3080),		// Version 20
		array(7456, 5712, 4096, 3248),		// Version 21
		array(8048, 6256, 4544, 3536),		// Version 22
		array(8752, 6880, 4912, 3712),		// Version 23
		array(9392, 7312, 5312, 4112),		// Version 24
		array(10208, 8000, 5744, 4304),		// Version 25
		array(10960, 8496, 6032, 4768),		// Version 26
		array(11744, 9024, 6464, 5024),		// Version 27
		array(12248, 9544, 6968, 5288),		// Version 28
		array(13048, 10136, 7288, 5608),	// Version 29
		array(13880, 10984, 7880, 5960),	// Version 30
		array(14744, 11640, 8264, 6344),	// Version 31
		array(15640, 12328, 8920, 6760),	// Version 32
		array(16568, 13048, 9368, 7208),	// Version 33
		array(17528, 13800, 9848, 7688),	// Version 34
		array(18448, 14496, 10288, 7888),	// Version 35
		array(19472, 15312, 10832, 8432),	// Version 36
		array(20528, 15936, 11408, 8768),	// Version 37
		array(21616, 16816, 12016, 9136),	// Version 38
		array(22496, 17728, 12656, 9776),	// Version 39
		array(23648, 18672, 13328, 10208),	// Version 40
	);

	// Remainder Bits by Version.
	// This data is from Table 7 -- Number of symbol characters and input data capacity
	// QR Code 2005 in ISO/IEC 18004_2006Cor 1_2009.pdf.
	private static $REMAINDER_BITS_BY_VERSION = array(
		null,
		0, 7, 7, 7, 7, 7, 0, 0, 0, 0, 0, 0, 0, 3, 3, 3, 3, 3, 3, 3, 4, 4, 4, 4, 4, 4, 4,
		3, 3, 3, 3, 3, 3, 3, 0, 0, 0, 0, 0, 0,
	);

	// Total number of codewords (bytes), including error correction codes, for each version.
	// This data is from Table 9 -- Error correction characteristics for QR Code 2005
	// in ISO/IEC 18004_2006Cor 1_2009.pdf.
	private static $TOTAL_CODEWORDS_BY_VERSION = array(
		0, 26, 44, 70, 100, 134, 172, 196, 242, 292, 346, 404, 466, 532, 581, 655, 733, 815,
		901, 991, 1085, 1156, 1258, 1364, 1474, 1588, 1706, 1828, 1921, 2051, 2185, 2323,
		2465, 2611, 2761, 2876, 3034, 3196, 3362, 3532, 3706
	);
	// Total number of error correction codewords (bytes), for each version and EC level.
	// This data is from Table 9 -- Error correction characteristics for QR Code 2005
	// in ISO/IEC 18004_2006Cor 1_2009.pdf.
	private static $ECC_BYTES_BY_VERSION_AND_EC_LEVEL = array(
		null,								// Version 0 (nonexistent)
		array(7, 10, 13, 17),				// Version 1
		array(10, 16, 22, 28),				// Version 2
		array(15, 26, 36, 44),				// Version 3
		array(20, 36, 52, 64),				// Version 4
		array(26, 48, 72, 88),				// Version 5
		array(36, 64, 96, 112),				// Version 6
		array(40, 72, 108, 130),			// Version 7
		array(48, 88, 132, 156),			// Version 8
		array(60, 110, 160, 192),			// Version 9
		array(72, 130, 192, 224),			// Version 10
		array(80, 150, 224, 264),			// Version 11
		array(96, 176, 260, 308),			// Version 12
		array(104, 198, 288, 352),			// Version 13
		array(120, 216, 320, 384),			// Version 14
		array(132, 240, 360, 432),			// Version 15
		array(144, 280, 408, 480),			// Version 16
		array(168, 308, 448, 532),			// Version 17
		array(180, 338, 504, 588),			// Version 18
		array(196, 364, 546, 650),			// Version 19
		array(224, 416, 600, 700),			// Version 20
		array(224, 442, 644, 750),			// Version 21
		array(252, 476, 690, 816),			// Version 22
		array(270, 504, 750, 900),			// Version 23
		array(300, 560, 810, 960),			// Version 24
		array(312, 588, 870, 1050),			// Version 25
		array(336, 644, 952, 1110),			// Version 26
		array(360, 700, 1020, 1200),		// Version 27
		array(390, 728, 1050, 1260),		// Version 28
		array(420, 784, 1140, 1350),		// Version 29
		array(450, 812, 1200, 1440),		// Version 30
		array(480, 868, 1290, 1530),		// Version 31
		array(510, 924, 1350, 1620),		// Version 32
		array(540, 980, 1440, 1710),		// Version 33
		array(570, 1036, 1530, 1800),		// Version 34
		array(570, 1064, 1590, 1890),		// Version 35
		array(600, 1120, 1680, 1980),		// Version 36
		array(630, 1204, 1770, 2100),		// Version 37
		array(660, 1260, 1860, 2220),		// Version 38
		array(720, 1316, 1950, 2310),		// Version 39
		array(750, 1372, 2040, 2430),		// Version 40
	);
	private static $BLOCKS_BY_VERSION_AND_EC_LEVEL = array(
		null,
		array(1, 1, 1, 1), array(1, 1, 1, 1), array(1, 1, 2, 2), array(1, 2, 2, 4),
		array(1, 2, 4, 4), array(2, 4, 4, 4), array(2, 4, 6, 5), array(2, 4, 6, 6),
		array(2, 5, 8, 8), array(4, 5, 8, 8), array(4, 5, 8, 11), array(4, 8, 10, 11),
		array(4, 9, 12, 16), array(4, 9, 16, 16), array(6, 10, 12, 18), array(6, 10, 17, 16),
		array(6, 11, 16, 19), array(6, 13, 18, 21), array(7, 14, 21, 25), array(8, 16, 20, 25),
		array(8, 17, 23, 25), array(9, 17, 23, 34), array(9, 18, 25, 30), array(10, 20, 27, 32),
		array(12, 21, 29, 35), array(12, 23, 34, 37), array(12, 25, 34, 40), array(13, 26, 35, 42),
		array(14, 28, 38, 45), array(15, 29, 40, 48), array(16, 31, 43, 51), array(17, 33, 45, 54),
		array(18, 35, 48, 57), array(19, 37, 51, 60), array(19, 38, 53, 63), array(20, 40, 56, 66),
		array(21, 43, 59, 70), array(22, 45, 62, 74), array(24, 47, 65, 77), array(25, 49, 68, 81)
	);

	// This data is from Table E.1 -- Row/column coordinates of center module of alignment patterns
	// in ISO/IEC 18004_2006Cor 1_2009.pdf.
	private static $ALIGNMENT_PATTERN_POSITIONS_BY_VERSION = array(
		null,
		array(),
		array(6, 18),
		array(6, 22),
		array(6, 26),
		array(6, 30),
		array(6, 34),
		array(6, 22, 38),
		array(6, 24, 42),
		array(6, 26, 46),
		array(6, 28, 50),
		array(6, 30, 54),
		array(6, 32, 58),
		array(6, 34, 62),
		array(6, 26, 46, 66),
		array(6, 26, 48, 70),
		array(6, 26, 50, 74),
		array(6, 30, 54, 78),
		array(6, 30, 56, 82),
		array(6, 30, 58, 86),
		array(6, 34, 62, 90),
		array(6, 28, 50, 72, 94),
		array(6, 26, 50, 74, 98),
		array(6, 30, 54, 78, 102),
		array(6, 28, 54, 80, 106),
		array(6, 32, 58, 84, 110),
		array(6, 30, 58, 86, 114),
		array(6, 34, 62, 90, 118),
		array(6, 26, 50, 74, 98, 122),
		array(6, 30, 54, 78, 102, 126),
		array(6, 26, 52, 78, 104, 130),
		array(6, 30, 56, 82, 108, 134),
		array(6, 34, 60, 86, 112, 138),
		array(6, 30, 58, 86, 114, 142),
		array(6, 34, 62, 90, 118, 146),
		array(6, 30, 54, 78, 102, 126, 150),
		array(6, 24, 50, 76, 102, 128, 154),
		array(6, 28, 54, 80, 106, 132, 158),
		array(6, 32, 58, 84, 110, 136, 162),
		array(6, 26, 54, 82, 110, 138, 166),
		array(6, 30, 58, 86, 114, 142, 170),
	);

	// Encodes a byte string at EC level 0 (L), 1 (M), 2 (Q), or 3 (H).
	// Returns a one-pixel-per-module GD image without a quiet zone, or false if
	// the data is too long. Add a four-module white border when displaying it.
	public static function createQRCode($data, $errorCorrectionLevel = 3) {
		$datalen = strlen($data);
		$errorCorrectionLevel = (int)max(0, min(3, (int)$errorCorrectionLevel));
		for ($version = 1; $version <= 40; $version++) {
			$lenbits = self::$DATA_LENGTH_BITS_BY_VERSION_AND_ENCODING_MODE[$version][4];
			$maxDataBits = self::$MAX_DATA_BITS_BY_VERSION_AND_EC_LEVEL[$version][$errorCorrectionLevel];
			if (($datalen <= ((1 << $lenbits)-1)) &&
				(4 + $lenbits + $datalen * 8 <= $maxDataBits)) break;
		}
		if ($version > 40) return false;

		$qrbits = new QRBits();
		$qrbits->shiftBitsIn(4, 4);
		$qrbits->shiftBitsIn($datalen, $lenbits);
		for ($i = 0; $i < $datalen; $i++) $qrbits->shiftBitsIn(ord($data[$i]), 8);
		$usedBits = 4 + $lenbits + $datalen * 8;
		$qrbits->shiftBitsIn(0, min(4, $maxDataBits - $usedBits));
		$qrbits->padLastByte();
		$encodedData = $qrbits->bytes;
		for ($i = 0; count($encodedData) < $maxDataBits / 8; $i++) {
			$encodedData[] = ($i % 2 == 0) ? 0xec : 0x11;
		}

		$codewords = self::addErrorCorrection($encodedData, $version, $errorCorrectionLevel);
		$size = $version * 4 + 17;
		$base = array_fill(0, $size, array_fill(0, $size, null));
		self::drawFunctionPatterns($base, $version);
		$best = null;
		$bestPenalty = PHP_INT_MAX;
		for ($mask = 0; $mask < 8; $mask++) {
			$matrix = $base;
			self::drawCodewords($matrix, $codewords, $mask);
			self::drawFormat($matrix, $errorCorrectionLevel, $mask);
			$penalty = self::penalty($matrix);
			if ($penalty < $bestPenalty) {
				$bestPenalty = $penalty;
				$best = $matrix;
			}
		}

		$img = imagecreate($size, $size);
		if ($img === false) return false;
		$white = imagecolorallocate($img, 255, 255, 255);
		$black = imagecolorallocate($img, 0, 0, 0);
		for ($y = 0; $y < $size; $y++) {
			for ($x = 0; $x < $size; $x++) {
				imagesetpixel($img, $x, $y, $best[$y][$x] ? $black : $white);
			}
		}
		return $img;
	}

	private static function multiply($x, $y) {
		$result = 0;
		for ($i = 7; $i >= 0; $i--) {
			$result = ($result << 1) ^ (($result >> 7) * 0x11d);
			$result ^= (($y >> $i) & 1) * $x;
		}
		return $result;
	}

	private static function addErrorCorrection($data, $version, $level) {
		$blocks = self::$BLOCKS_BY_VERSION_AND_EC_LEVEL[$version][$level];
		$eccLength = intdiv(self::$ECC_BYTES_BY_VERSION_AND_EC_LEVEL[$version][$level], $blocks);
		$shortLength = intdiv(count($data), $blocks);
		$shortBlocks = $blocks - count($data) % $blocks;
		$generator = array(1);
		$root = 1;
		for ($i = 0; $i < $eccLength; $i++) {
			$next = array_fill(0, count($generator) + 1, 0);
			foreach ($generator as $j => $coefficient) {
				$next[$j] ^= $coefficient;
				$next[$j + 1] ^= self::multiply($coefficient, $root);
			}
			$generator = $next;
			$root = self::multiply($root, 2);
		}
		$dataBlocks = array();
		$eccBlocks = array();
		$offset = 0;
		for ($i = 0; $i < $blocks; $i++) {
			$length = $shortLength + ($i >= $shortBlocks ? 1 : 0);
			$block = array_slice($data, $offset, $length);
			$offset += $length;
			$remainder = array_fill(0, $eccLength, 0);
			foreach ($block as $byte) {
				$factor = $byte ^ $remainder[0];
				array_shift($remainder);
				$remainder[] = 0;
				for ($j = 0; $j < $eccLength; $j++) {
					$remainder[$j] ^= self::multiply($generator[$j + 1], $factor);
				}
			}
			$dataBlocks[] = $block;
			$eccBlocks[] = $remainder;
		}
		$result = array();
		for ($i = 0; $i <= $shortLength; $i++) {
			foreach ($dataBlocks as $block) {
				if (isset($block[$i])) $result[] = $block[$i];
			}
		}
		for ($i = 0; $i < $eccLength; $i++) {
			foreach ($eccBlocks as $block) $result[] = $block[$i];
		}
		if (count($result) != self::$TOTAL_CODEWORDS_BY_VERSION[$version]) {
			throw new Exception('Internal error: incorrect number of QR codewords');
		}
		return $result;
	}

	private static function drawFunctionPatterns(&$matrix, $version) {
		$size = count($matrix);
		for ($i = 0; $i < $size; $i++) {
			$matrix[6][$i] = $matrix[$i][6] = ($i % 2 == 0);
		}
		foreach (array(array(3, 3), array($size - 4, 3), array(3, $size - 4)) as $center) {
			for ($dy = -4; $dy <= 4; $dy++) {
				for ($dx = -4; $dx <= 4; $dx++) {
					$x = $center[0] + $dx;
					$y = $center[1] + $dy;
					if ($x < 0 || $y < 0 || $x >= $size || $y >= $size) continue;
					$distance = max(abs($dx), abs($dy));
					$matrix[$y][$x] = ($distance != 2 && $distance != 4);
				}
			}
		}
		$positions = self::$ALIGNMENT_PATTERN_POSITIONS_BY_VERSION[$version];
		$last = count($positions) - 1;
		foreach ($positions as $yi => $y) {
			foreach ($positions as $xi => $x) {
				if (($xi == 0 && $yi == 0) || ($xi == 0 && $yi == $last) ||
					($xi == $last && $yi == 0)) continue;
				for ($dy = -2; $dy <= 2; $dy++) {
					for ($dx = -2; $dx <= 2; $dx++) {
						$matrix[$y + $dy][$x + $dx] = (max(abs($dx), abs($dy)) != 1);
					}
				}
			}
		}
		self::drawFormat($matrix, 0, 0);
		if ($version >= 7) {
			$remainder = $version;
			for ($i = 0; $i < 12; $i++) {
				$remainder = ($remainder << 1) ^ ((($remainder >> 11) & 1) * 0x1f25);
			}
			$bits = ($version << 12) | $remainder;
			for ($i = 0; $i < 18; $i++) {
				$a = $size - 11 + $i % 3;
				$b = intdiv($i, 3);
				$matrix[$b][$a] = $matrix[$a][$b] = (bool)(($bits >> $i) & 1);
			}
		}
	}

	private static function drawFormat(&$matrix, $level, $mask) {
		$size = count($matrix);
		$levelBits = array(1, 0, 3, 2);
		$data = ($levelBits[$level] << 3) | $mask;
		$remainder = $data;
		for ($i = 0; $i < 10; $i++) {
			$remainder = ($remainder << 1) ^ ((($remainder >> 9) & 1) * 0x537);
		}
		$bits = (($data << 10) | $remainder) ^ 0x5412;
		for ($i = 0; $i <= 5; $i++) $matrix[$i][8] = (bool)(($bits >> $i) & 1);
		$matrix[7][8] = (bool)(($bits >> 6) & 1);
		$matrix[8][8] = (bool)(($bits >> 7) & 1);
		$matrix[8][7] = (bool)(($bits >> 8) & 1);
		for ($i = 9; $i < 15; $i++) $matrix[8][14 - $i] = (bool)(($bits >> $i) & 1);
		for ($i = 0; $i < 8; $i++) $matrix[8][$size - 1 - $i] = (bool)(($bits >> $i) & 1);
		for ($i = 8; $i < 15; $i++) $matrix[$size - 15 + $i][8] = (bool)(($bits >> $i) & 1);
		$matrix[$size - 8][8] = true;
	}

	private static function drawCodewords(&$matrix, $codewords, $mask) {
		$size = count($matrix);
		$bit = 0;
		for ($right = $size - 1; $right >= 1; $right -= 2) {
			if ($right == 6) $right = 5;
			for ($vert = 0; $vert < $size; $vert++) {
				$y = (($right + 1) & 2) == 0 ? $size - 1 - $vert : $vert;
				for ($j = 0; $j < 2; $j++) {
					$x = $right - $j;
					if ($matrix[$y][$x] !== null) continue;
					$value = $bit < count($codewords) * 8
						? (($codewords[intdiv($bit, 8)] >> (7 - $bit % 8)) & 1) : 0;
					switch ($mask) {
					case 0: $invert = ($x + $y) % 2 == 0; break;
					case 1: $invert = $y % 2 == 0; break;
					case 2: $invert = $x % 3 == 0; break;
					case 3: $invert = ($x + $y) % 3 == 0; break;
					case 4: $invert = (intdiv($y, 2) + intdiv($x, 3)) % 2 == 0; break;
					case 5: $invert = ($x * $y % 2 + $x * $y % 3) == 0; break;
					case 6: $invert = ($x * $y % 2 + $x * $y % 3) % 2 == 0; break;
					default: $invert = (($x + $y) % 2 + $x * $y % 3) % 2 == 0; break;
					}
					$matrix[$y][$x] = (bool)($value ^ $invert);
					$bit++;
				}
			}
		}
		$version = intdiv($size - 17, 4);
		if ($bit != count($codewords) * 8 + self::$REMAINDER_BITS_BY_VERSION[$version]) {
			throw new Exception('Internal error: incorrect number of QR data modules');
		}
	}

	private static function penalty($matrix) {
		$size = count($matrix);
		$penalty = 0;
		$dark = 0;
		for ($y = 0; $y < $size; $y++) {
			for ($x = 0; $x < $size; $x++) {
				if ($matrix[$y][$x]) $dark++;
				if ($x > 0 && $y > 0 && $matrix[$y][$x] == $matrix[$y][$x - 1] &&
					$matrix[$y][$x] == $matrix[$y - 1][$x] &&
					$matrix[$y][$x] == $matrix[$y - 1][$x - 1]) $penalty += 3;
			}
		}
		for ($direction = 0; $direction < 2; $direction++) {
			for ($i = 0; $i < $size; $i++) {
				$line = '';
				$run = 0;
				$previous = null;
				for ($j = 0; $j < $size; $j++) {
					$value = $direction == 0 ? $matrix[$i][$j] : $matrix[$j][$i];
					$line .= $value ? '1' : '0';
					$run = ($value === $previous) ? $run + 1 : 1;
					if ($run == 5) $penalty += 3;
					else if ($run > 5) $penalty++;
					$previous = $value;
				}
				for ($j = 0; $j <= $size - 11; $j++) {
					$pattern = substr($line, $j, 11);
					if ($pattern == '00001011101' || $pattern == '10111010000') $penalty += 40;
				}
			}
		}
		return $penalty + intdiv(abs($dark * 20 - $size * $size * 10), $size * $size) * 10;
	}
}

class QRBits {
	public $bytes = array();
	public $bytei = 0;
	public $curnbits = 0;

	// Shift bits out of an integer into a byte array.
	// @param int $data An integer containing the bits to shift in.
	// @param int $nbits The number of bits to shift in (number of least significant bits in $data).
	public function shiftBitsIn($data, $nbits) {
		if ($nbits == 0) return;
		for ($mask = 1 << ($nbits-1); $mask != 0; $mask = $mask >> 1) {
			if ($this->curnbits == 0) {
				$this->bytes[$this->bytei] = (($data & $mask) != 0) ? 1 : 0;
			} else {
				$this->bytes[$this->bytei] = ($this->bytes[$this->bytei] << 1) | ((($data & $mask) != 0) ? 1 : 0);
			}
			$this->curnbits++;
			if ($this->curnbits >= 8) {
				$this->curnbits = 0;
				$this->bytei++;
			}
		}
	}

	public function padLastByte() {
		if ($this->curnbits != 0) {
			if (($this->curnbits > 0) && ($this->curnbits < 8)) {
				$this->bytes[$this->bytei] = $this->bytes[$this->bytei] << (8-$this->curnbits);
				$this->bytei++;
			}
			$this->curnbits = 0;
		}
	}
}
