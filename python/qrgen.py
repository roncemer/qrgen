"""Byte-mode QR code generation, corresponding to the PHP QRGen class."""

from __future__ import annotations

import struct
import zlib


_TOTAL_CODEWORDS = (
    0, 26, 44, 70, 100, 134, 172, 196, 242, 292, 346, 404, 466, 532, 581, 655,
    733, 815, 901, 991, 1085, 1156, 1258, 1364, 1474, 1588, 1706, 1828,
    1921, 2051, 2185, 2323, 2465, 2611, 2761, 2876, 3034, 3196, 3362, 3532, 3706,
)

_ECC_BYTES = (
    (),
    (7, 10, 13, 17), (10, 16, 22, 28), (15, 26, 36, 44), (20, 36, 52, 64),
    (26, 48, 72, 88), (36, 64, 96, 112), (40, 72, 108, 130), (48, 88, 132, 156),
    (60, 110, 160, 192), (72, 130, 192, 224), (80, 150, 224, 264), (96, 176, 260, 308),
    (104, 198, 288, 352), (120, 216, 320, 384), (132, 240, 360, 432),
    (144, 280, 408, 480), (168, 308, 448, 532), (180, 338, 504, 588),
    (196, 364, 546, 650), (224, 416, 600, 700), (224, 442, 644, 750),
    (252, 476, 690, 816), (270, 504, 750, 900), (300, 560, 810, 960),
    (312, 588, 870, 1050), (336, 644, 952, 1110), (360, 700, 1020, 1200),
    (390, 728, 1050, 1260), (420, 784, 1140, 1350), (450, 812, 1200, 1440),
    (480, 868, 1290, 1530), (510, 924, 1350, 1620), (540, 980, 1440, 1710),
    (570, 1036, 1530, 1800), (570, 1064, 1590, 1890), (600, 1120, 1680, 1980),
    (630, 1204, 1770, 2100), (660, 1260, 1860, 2220), (720, 1316, 1950, 2310),
    (750, 1372, 2040, 2430),
)

_BLOCKS = (
    (),
    (1, 1, 1, 1), (1, 1, 1, 1), (1, 1, 2, 2), (1, 2, 2, 4),
    (1, 2, 4, 4), (2, 4, 4, 4), (2, 4, 6, 5), (2, 4, 6, 6),
    (2, 5, 8, 8), (4, 5, 8, 8), (4, 5, 8, 11), (4, 8, 10, 11),
    (4, 9, 12, 16), (4, 9, 16, 16), (6, 10, 12, 18), (6, 10, 17, 16),
    (6, 11, 16, 19), (6, 13, 18, 21), (7, 14, 21, 25), (8, 16, 20, 25),
    (8, 17, 23, 25), (9, 17, 23, 34), (9, 18, 25, 30), (10, 20, 27, 32),
    (12, 21, 29, 35), (12, 23, 34, 37), (12, 25, 34, 40), (13, 26, 35, 42),
    (14, 28, 38, 45), (15, 29, 40, 48), (16, 31, 43, 51), (17, 33, 45, 54),
    (18, 35, 48, 57), (19, 37, 51, 60), (19, 38, 53, 63), (20, 40, 56, 66),
    (21, 43, 59, 70), (22, 45, 62, 74), (24, 47, 65, 77), (25, 49, 68, 81),
)

_ALIGNMENT = (
    (), (), (6, 18), (6, 22), (6, 26), (6, 30), (6, 34),
    (6, 22, 38), (6, 24, 42), (6, 26, 46), (6, 28, 50), (6, 30, 54),
    (6, 32, 58), (6, 34, 62), (6, 26, 46, 66), (6, 26, 48, 70),
    (6, 26, 50, 74), (6, 30, 54, 78), (6, 30, 56, 82), (6, 30, 58, 86),
    (6, 34, 62, 90), (6, 28, 50, 72, 94), (6, 26, 50, 74, 98),
    (6, 30, 54, 78, 102), (6, 28, 54, 80, 106), (6, 32, 58, 84, 110),
    (6, 30, 58, 86, 114), (6, 34, 62, 90, 118), (6, 26, 50, 74, 98, 122),
    (6, 30, 54, 78, 102, 126), (6, 26, 52, 78, 104, 130),
    (6, 30, 56, 82, 108, 134), (6, 34, 60, 86, 112, 138),
    (6, 30, 58, 86, 114, 142), (6, 34, 62, 90, 118, 146),
    (6, 30, 54, 78, 102, 126, 150), (6, 24, 50, 76, 102, 128, 154),
    (6, 28, 54, 80, 106, 132, 158), (6, 32, 58, 84, 110, 136, 162),
    (6, 26, 54, 82, 110, 138, 166), (6, 30, 58, 86, 114, 142, 170),
)

_REMAINDER_BITS = (
    0, 0, 7, 7, 7, 7, 7, 0, 0, 0, 0, 0, 0, 0, 3, 3, 3, 3, 3, 3, 3,
    4, 4, 4, 4, 4, 4, 4, 3, 3, 3, 3, 3, 3, 3, 0, 0, 0, 0, 0, 0,
)


def _multiply(x: int, y: int) -> int:
    result = 0
    for i in range(7, -1, -1):
        result = (result << 1) ^ ((result >> 7) * 0x11D)
        result ^= ((y >> i) & 1) * x
    return result


def _add_error_correction(data: list[int], version: int, level: int) -> list[int]:
    block_count = _BLOCKS[version][level]
    ecc_length = _ECC_BYTES[version][level] // block_count
    short_length, long_blocks = divmod(len(data), block_count)
    short_blocks = block_count - long_blocks
    generator = [1]
    root = 1
    for _ in range(ecc_length):
        next_generator = [0] * (len(generator) + 1)
        for i, coefficient in enumerate(generator):
            next_generator[i] ^= coefficient
            next_generator[i + 1] ^= _multiply(coefficient, root)
        generator = next_generator
        root = _multiply(root, 2)

    data_blocks = []
    ecc_blocks = []
    offset = 0
    for i in range(block_count):
        length = short_length + (i >= short_blocks)
        block = data[offset:offset + length]
        offset += length
        remainder = [0] * ecc_length
        for byte in block:
            factor = byte ^ remainder[0]
            remainder = remainder[1:] + [0]
            for j in range(ecc_length):
                remainder[j] ^= _multiply(generator[j + 1], factor)
        data_blocks.append(block)
        ecc_blocks.append(remainder)

    result = [
        block[i] for i in range(short_length + 1)
        for block in data_blocks if i < len(block)
    ]
    result.extend(block[i] for i in range(ecc_length) for block in ecc_blocks)
    if len(result) != _TOTAL_CODEWORDS[version]:
        raise RuntimeError("Internal error: incorrect number of QR codewords")
    return result


def _draw_format(matrix: list[list[int]], level: int, mask: int) -> None:
    size = len(matrix)
    data = ((1, 0, 3, 2)[level] << 3) | mask
    remainder = data
    for _ in range(10):
        remainder = (remainder << 1) ^ (((remainder >> 9) & 1) * 0x537)
    bits = ((data << 10) | remainder) ^ 0x5412
    for i in range(6):
        matrix[i][8] = (bits >> i) & 1
    matrix[7][8] = (bits >> 6) & 1
    matrix[8][8] = (bits >> 7) & 1
    matrix[8][7] = (bits >> 8) & 1
    for i in range(9, 15):
        matrix[8][14 - i] = (bits >> i) & 1
    for i in range(8):
        matrix[8][size - 1 - i] = (bits >> i) & 1
    for i in range(8, 15):
        matrix[size - 15 + i][8] = (bits >> i) & 1
    matrix[size - 8][8] = 1


def _draw_function_patterns(matrix: list[list[int]], version: int) -> None:
    size = len(matrix)
    for i in range(size):
        matrix[6][i] = matrix[i][6] = int(i % 2 == 0)
    for cx, cy in ((3, 3), (size - 4, 3), (3, size - 4)):
        for dy in range(-4, 5):
            for dx in range(-4, 5):
                x, y = cx + dx, cy + dy
                if 0 <= x < size and 0 <= y < size:
                    matrix[y][x] = int(max(abs(dx), abs(dy)) not in (2, 4))

    positions = _ALIGNMENT[version]
    last = len(positions) - 1
    for yi, y in enumerate(positions):
        for xi, x in enumerate(positions):
            if (xi, yi) in ((0, 0), (0, last), (last, 0)):
                continue
            for dy in range(-2, 3):
                for dx in range(-2, 3):
                    matrix[y + dy][x + dx] = int(max(abs(dx), abs(dy)) != 1)
    _draw_format(matrix, 0, 0)
    if version >= 7:
        remainder = version
        for _ in range(12):
            remainder = (remainder << 1) ^ (((remainder >> 11) & 1) * 0x1F25)
        bits = (version << 12) | remainder
        for i in range(18):
            a, b = size - 11 + i % 3, i // 3
            matrix[b][a] = matrix[a][b] = (bits >> i) & 1


def _mask_bit(mask: int, x: int, y: int) -> bool:
    if mask == 0:
        return (x + y) % 2 == 0
    if mask == 1:
        return y % 2 == 0
    if mask == 2:
        return x % 3 == 0
    if mask == 3:
        return (x + y) % 3 == 0
    if mask == 4:
        return (y // 2 + x // 3) % 2 == 0
    if mask == 5:
        return x * y % 2 + x * y % 3 == 0
    if mask == 6:
        return (x * y % 2 + x * y % 3) % 2 == 0
    return ((x + y) % 2 + x * y % 3) % 2 == 0


def _draw_codewords(matrix: list[list[int]], codewords: list[int], mask: int) -> None:
    size = len(matrix)
    bit = 0
    right = size - 1
    while right >= 1:
        if right == 6:
            right = 5
        for vert in range(size):
            y = size - 1 - vert if (right + 1) & 2 == 0 else vert
            for x in (right, right - 1):
                if matrix[y][x] != -1:
                    continue
                value = (codewords[bit // 8] >> (7 - bit % 8)) & 1 if bit < len(codewords) * 8 else 0
                matrix[y][x] = value ^ _mask_bit(mask, x, y)
                bit += 1
        right -= 2
    version = (size - 17) // 4
    if bit != len(codewords) * 8 + _REMAINDER_BITS[version]:
        raise RuntimeError("Internal error: incorrect number of QR data modules")


def _penalty(matrix: list[list[int]]) -> int:
    size = len(matrix)
    penalty = 0
    dark = sum(sum(row) for row in matrix)
    for y in range(1, size):
        for x in range(1, size):
            if matrix[y][x] == matrix[y][x - 1] == matrix[y - 1][x] == matrix[y - 1][x - 1]:
                penalty += 3
    for direction in range(2):
        for i in range(size):
            line = [matrix[i][j] if direction == 0 else matrix[j][i] for j in range(size)]
            run = 0
            previous = -1
            for value in line:
                run = run + 1 if value == previous else 1
                if run == 5:
                    penalty += 3
                elif run > 5:
                    penalty += 1
                previous = value
            line_text = "".join(str(int(value)) for value in line)
            for j in range(size - 10):
                if line_text[j:j + 11] in ("00001011101", "10111010000"):
                    penalty += 40
    return penalty + abs(dark * 20 - size * size * 10) // (size * size) * 10


def create_qr_code(data: bytes | str, error_correction_level: int = 3) -> list[list[bool]] | None:
    """Return a module matrix (True is black), or None if data is too long.

    Strings are encoded as UTF-8; bytes are encoded unchanged. Error correction
    levels 0 through 3 correspond to L, M, Q and H and are clamped as in PHP.
    The matrix has no quiet zone; add at least four white modules when rendering.
    """
    if isinstance(data, str):
        data = data.encode("utf-8")
    if not isinstance(data, bytes):
        raise TypeError("data must be bytes or str")
    level = max(0, min(3, int(error_correction_level)))
    for version in range(1, 41):
        length_bits = 8 if version < 10 else 16
        capacity = (_TOTAL_CODEWORDS[version] - _ECC_BYTES[version][level]) * 8
        if len(data) < 1 << length_bits and 4 + length_bits + len(data) * 8 <= capacity:
            break
    else:
        return None

    bits = [0, 1, 0, 0]
    bits.extend((len(data) >> i) & 1 for i in range(length_bits - 1, -1, -1))
    for byte in data:
        bits.extend((byte >> i) & 1 for i in range(7, -1, -1))
    bits.extend([0] * min(4, capacity - len(bits)))
    bits.extend([0] * ((-len(bits)) % 8))
    encoded = [sum(bits[i + j] << (7 - j) for j in range(8)) for i in range(0, len(bits), 8)]
    while len(encoded) < capacity // 8:
        encoded.append((0xEC, 0x11)[(len(encoded) - len(bits) // 8) % 2])
    codewords = _add_error_correction(encoded, version, level)

    size = version * 4 + 17
    base = [[-1] * size for _ in range(size)]
    _draw_function_patterns(base, version)
    best = None
    best_penalty = None
    for mask in range(8):
        matrix = [row[:] for row in base]
        _draw_codewords(matrix, codewords, mask)
        _draw_format(matrix, level, mask)
        penalty = _penalty(matrix)
        if best_penalty is None or penalty < best_penalty:
            best, best_penalty = matrix, penalty
    if best is None:
        raise RuntimeError("Internal error: no QR mask selected")
    return [[bool(value) for value in row] for row in best]


def png_image(matrix: list[list[bool]]) -> bytes:
    """Render a module matrix as a 500x500 PNG with a four-module quiet zone."""
    modules = len(matrix)
    if not modules or any(len(row) != modules for row in matrix) or modules + 8 > 500:
        raise ValueError("matrix must be square and fit within the image")
    scale = 500 // (modules + 8)
    offset = (500 - modules * scale) // 2
    white = b"\xff" * 500
    rows = [b"\0" + white] * offset
    for row in matrix:
        pixels = b"\xff" * offset + b"".join((b"\0" if value else b"\xff") * scale for value in row)
        pixels += b"\xff" * (500 - len(pixels))
        rows.extend([b"\0" + pixels] * scale)
    rows.extend([b"\0" + white] * (500 - len(rows)))

    def chunk(kind: bytes, payload: bytes) -> bytes:
        return struct.pack(">I", len(payload)) + kind + payload + struct.pack(">I", zlib.crc32(kind + payload))

    header = struct.pack(">IIBBBBB", 500, 500, 8, 0, 0, 0, 0)
    return b"\x89PNG\r\n\x1a\n" + chunk(b"IHDR", header) + chunk(b"IDAT", zlib.compress(b"".join(rows))) + chunk(b"IEND", b"")
