# QRGen

QRGen generates QR codes from byte-mode data, with implementations in PHP,
Python, and Go. Each supports QR versions 1–40 and error correction levels
L, M, Q, and H.

Each implementation includes a command-line tool that accepts a string and an
output filename and writes a 500×500 PNG with a quiet zone. The tools use
error correction level H.

## Implementations

Commands below run from the project root.

- **[PHP](php/README.md)** — requires PHP 7 or newer and the GD extension.
  ```sh
  php php/makeqr.php "text to encode" output.png
  ```
- **[Python](python/README.md)** — requires Python 3.9 or newer; no
  third-party dependencies.
  ```sh
  python3 python/makeqr.py "text to encode" output.png
  ```
- **[Go](go/README.md)** — requires Go 1.20 or newer; no third-party
  dependencies.
  ```sh
  ./go/compile
  ./go/bin/makeqr "text to encode" output.png
  ```

See each implementation's README for its library API and test commands.

## License

See [LICENSE](LICENSE).
