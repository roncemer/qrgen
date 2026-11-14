import hashlib
import os
from pathlib import Path
import struct
import subprocess
import sys
import tempfile
import unittest
import zlib

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))
from qrgen import create_qr_code


def matrix_hash(matrix):
    return hashlib.sha256(
        "".join("1" if value else "0" for row in matrix for value in row).encode()
    ).hexdigest()


def png_pixels(filename):
    image = filename.read_bytes()
    if image[:8] != b"\x89PNG\r\n\x1a\n":
        raise ValueError("not a PNG")
    offset = 8
    compressed = b""
    width = height = 0
    while offset < len(image):
        length = struct.unpack_from(">I", image, offset)[0]
        kind = image[offset + 4:offset + 8]
        payload = image[offset + 8:offset + 8 + length]
        if kind == b"IHDR":
            width, height, depth, colour, compression, filtering, interlace = struct.unpack(">IIBBBBB", payload)
            if (depth, colour, compression, filtering, interlace) != (8, 0, 0, 0, 0):
                raise ValueError("unexpected PNG format")
        elif kind == b"IDAT":
            compressed += payload
        elif kind == b"IEND":
            break
        offset += length + 12
    raw = zlib.decompress(compressed)
    if len(raw) != (width + 1) * height:
        raise ValueError("invalid PNG image size")
    rows = [raw[i * (width + 1):(i + 1) * (width + 1)] for i in range(height)]
    if any(row[0] != 0 for row in rows):
        raise ValueError("unexpected PNG filter")
    return width, height, [row[1:] for row in rows]


class QRGenTests(unittest.TestCase):
    def test_reference_matrices(self):
        # These byte-mode fixtures are shared with the PHP tests and were
        # independently generated with Python qrcode.
        fixtures = (
            (2, 0, 1, "8fd3566c525ca8ce2896e5e4f12d9bd794dea12aded5af7c864e74673bb0f7a1"),
            (3, 35, 5, "5848d152ffe0f065a6cf467e71b213f4954029bb5be5a94b9bd10f9950e05547"),
            (0, 135, 7, "1627cb178d795aa6488fc8748207505738a62f5ffc62ef65d5d55172890226d9"),
            (1, 181, 10, "1f96da67cfe8660d0f9fb035854da60a630fda55408fba305c7580d9ba19bf80"),
            (2, 752, 27, "9f4c019cca08dad576982a145abaf72982bba6db4485d6e2ed060e6b56424719"),
            (3, 1220, 40, "3be9d992af0472aebfed0b6d750daef109760fb7823e97bea59b6465ebf94f0f"),
        )
        for level, length, version, expected in fixtures:
            with self.subTest(level=level, version=version):
                data = bytes((i * 73 + 19) % 256 for i in range(length))
                matrix = create_qr_code(data, level)
                self.assertIsNotNone(matrix)
                self.assertEqual(len(matrix), 17 + 4 * version)
                self.assertEqual(matrix_hash(matrix), expected)

    def test_capacity_and_levels(self):
        for length, size in ((17, 21), (18, 25), (2953, 177)):
            with self.subTest(length=length):
                self.assertEqual(len(create_qr_code(b"a" * length, 0)), size)
        self.assertIsNone(create_qr_code(b"a" * 2954, 0))
        self.assertEqual(create_qr_code("test"), create_qr_code(b"test", 3))
        self.assertEqual(create_qr_code(b"test", -1), create_qr_code(b"test", 0))
        self.assertEqual(create_qr_code(b"test", 4), create_qr_code(b"test", 3))
        self.assertEqual(create_qr_code("café"), create_qr_code("café".encode("utf-8")))
        with self.assertRaises(TypeError):
            create_qr_code(123)

    def test_cli(self):
        script = Path(__file__).resolve().parents[1] / "makeqr.py"

        def run(*args):
            return subprocess.run([sys.executable, str(script), *args], capture_output=True)

        with tempfile.TemporaryDirectory() as directory:
            filename = Path(directory) / "output.png"
            for args in ((), ("data",), ("data", str(filename), "extra")):
                result = run(*args)
                self.assertNotEqual(result.returncode, 0)
                self.assertIn(b"Usage:", result.stderr)

            for data in ("", "Hello, QR! café"):
                with self.subTest(data=data):
                    result = run(data, str(filename))
                    self.assertEqual(result.returncode, 0, result.stderr)
                    width, height, rows = png_pixels(filename)
                    self.assertEqual((width, height), (500, 500))
                    self.assertEqual(rows[0][0], 255)
                    matrix = create_qr_code(os.fsencode(data))
                    scale = 500 // (len(matrix) + 8)
                    offset = (500 - len(matrix) * scale) // 2
                    for y, row in enumerate(matrix):
                        for x, black in enumerate(row):
                            self.assertEqual(rows[offset + y * scale][offset + x * scale], 0 if black else 255)

            for args, message in (
                (("a" * 1274, str(filename)), b"too long"),
                (("data", ""), b"filename"),
                (("data", str(filename / "output.png")), b"Could not write PNG"),
            ):
                result = run(*args)
                self.assertNotEqual(result.returncode, 0)
                self.assertIn(message, result.stderr)


if __name__ == "__main__":
    unittest.main()
