# Python QR generator

Python 3.9 or newer is required; there are no third-party dependencies.

```sh
python3 python/makeqr.py "text to encode" output.png
python3 -m unittest discover -s python/tests
```

The CLI accepts exactly two arguments and writes a 500×500 PNG with a quiet
zone, using error correction level H, like the PHP CLI.

For library use, `qrgen.create_qr_code(data, error_correction_level=3)` returns a square matrix
of booleans (black is `True`), without a quiet zone, or `None` if the data is
too long. Pass a `bytes` value for exact byte-mode data, or a `str` to encode
as UTF-8. Levels 0–3 correspond to L, M, Q and H. `qrgen.png_image(matrix)`
renders the matrix as PNG bytes.
