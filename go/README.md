# Go QR generator

Go 1.20 or newer is required; there are no third-party dependencies.

```sh
./go/compile
./go/bin/makeqr "text to encode" output.png
(cd go && go test ./...)
```

The CLI accepts exactly two arguments and writes a 500×500 PNG with a quiet
zone, using error correction level H.

For library use, `qrgen.CreateQRCode(data, level)` takes a byte slice and
returns a square matrix of booleans (black is `true`), without a quiet zone,
and an error. If data is too long, the matrix and error are both `nil`.
Levels 0–3 correspond to L, M, Q and H; values outside that range are clamped.
`qrgen.PNGImage(matrix)` renders the matrix as PNG bytes.
