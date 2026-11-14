# PHP QR generator

PHP 7 or newer with the GD extension is required.

```sh
php php/makeqr.php "text to encode" output.png
php php/tests/qrgentest.php
php php/tests/makeqrtest.php
```

The CLI accepts exactly two arguments and writes a 500×500 PNG with a quiet
zone, using error correction level H.

For library use, include `QRGen.class.php` and call
`QRGen::createQRCode($data, $errorCorrectionLevel = 3)`. It encodes the bytes
of the input string and returns a GD image with one pixel per module and no
quiet zone, or `false` if the data is too long or image creation fails. Add at
least four white modules around the image when displaying it. Levels 0–3
correspond to L, M, Q and H.
