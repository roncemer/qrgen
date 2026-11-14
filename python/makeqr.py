"""Write a QR code PNG for a command-line string."""

import os
import sys

from qrgen import create_qr_code, png_image


def main(argv: list[str]) -> int:
    if len(argv) != 3:
        print("Usage: python makeqr.py <string> <output.png>", file=sys.stderr)
        return 1
    if not argv[2]:
        print("The output filename must not be empty.", file=sys.stderr)
        return 1
    matrix = create_qr_code(os.fsencode(argv[1]))
    if matrix is None:
        print("Could not generate QR code: input is too long.", file=sys.stderr)
        return 1
    try:
        with open(argv[2], "wb") as output:
            output.write(png_image(matrix))
    except OSError as error:
        print(f"Could not write PNG image to {argv[2]}: {error}", file=sys.stderr)
        return 1
    return 0


if __name__ == "__main__":
    sys.exit(main(sys.argv))
