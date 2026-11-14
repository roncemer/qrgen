// Package qrgen implements byte-mode QR code generation.
package qrgen

import (
	"bytes"
	"errors"
	"image"
	"image/color"
	"image/png"
)

var totalCodewords = [...]int{
	0, 26, 44, 70, 100, 134, 172, 196, 242, 292, 346, 404, 466, 532, 581, 655,
	733, 815, 901, 991, 1085, 1156, 1258, 1364, 1474, 1588, 1706, 1828,
	1921, 2051, 2185, 2323, 2465, 2611, 2761, 2876, 3034, 3196, 3362, 3532, 3706,
}

var eccBytes = [...][4]int{
	{},
	{7, 10, 13, 17}, {10, 16, 22, 28}, {15, 26, 36, 44}, {20, 36, 52, 64},
	{26, 48, 72, 88}, {36, 64, 96, 112}, {40, 72, 108, 130}, {48, 88, 132, 156},
	{60, 110, 160, 192}, {72, 130, 192, 224}, {80, 150, 224, 264}, {96, 176, 260, 308},
	{104, 198, 288, 352}, {120, 216, 320, 384}, {132, 240, 360, 432},
	{144, 280, 408, 480}, {168, 308, 448, 532}, {180, 338, 504, 588},
	{196, 364, 546, 650}, {224, 416, 600, 700}, {224, 442, 644, 750},
	{252, 476, 690, 816}, {270, 504, 750, 900}, {300, 560, 810, 960},
	{312, 588, 870, 1050}, {336, 644, 952, 1110}, {360, 700, 1020, 1200},
	{390, 728, 1050, 1260}, {420, 784, 1140, 1350}, {450, 812, 1200, 1440},
	{480, 868, 1290, 1530}, {510, 924, 1350, 1620}, {540, 980, 1440, 1710},
	{570, 1036, 1530, 1800}, {570, 1064, 1590, 1890}, {600, 1120, 1680, 1980},
	{630, 1204, 1770, 2100}, {660, 1260, 1860, 2220}, {720, 1316, 1950, 2310},
	{750, 1372, 2040, 2430},
}

var blocks = [...][4]int{
	{},
	{1, 1, 1, 1}, {1, 1, 1, 1}, {1, 1, 2, 2}, {1, 2, 2, 4},
	{1, 2, 4, 4}, {2, 4, 4, 4}, {2, 4, 6, 5}, {2, 4, 6, 6},
	{2, 5, 8, 8}, {4, 5, 8, 8}, {4, 5, 8, 11}, {4, 8, 10, 11},
	{4, 9, 12, 16}, {4, 9, 16, 16}, {6, 10, 12, 18}, {6, 10, 17, 16},
	{6, 11, 16, 19}, {6, 13, 18, 21}, {7, 14, 21, 25}, {8, 16, 20, 25},
	{8, 17, 23, 25}, {9, 17, 23, 34}, {9, 18, 25, 30}, {10, 20, 27, 32},
	{12, 21, 29, 35}, {12, 23, 34, 37}, {12, 25, 34, 40}, {13, 26, 35, 42},
	{14, 28, 38, 45}, {15, 29, 40, 48}, {16, 31, 43, 51}, {17, 33, 45, 54},
	{18, 35, 48, 57}, {19, 37, 51, 60}, {19, 38, 53, 63}, {20, 40, 56, 66},
	{21, 43, 59, 70}, {22, 45, 62, 74}, {24, 47, 65, 77}, {25, 49, 68, 81},
}

var alignment = [...][]int{
	{}, {}, {6, 18}, {6, 22}, {6, 26}, {6, 30}, {6, 34},
	{6, 22, 38}, {6, 24, 42}, {6, 26, 46}, {6, 28, 50}, {6, 30, 54},
	{6, 32, 58}, {6, 34, 62}, {6, 26, 46, 66}, {6, 26, 48, 70},
	{6, 26, 50, 74}, {6, 30, 54, 78}, {6, 30, 56, 82}, {6, 30, 58, 86},
	{6, 34, 62, 90}, {6, 28, 50, 72, 94}, {6, 26, 50, 74, 98},
	{6, 30, 54, 78, 102}, {6, 28, 54, 80, 106}, {6, 32, 58, 84, 110},
	{6, 30, 58, 86, 114}, {6, 34, 62, 90, 118}, {6, 26, 50, 74, 98, 122},
	{6, 30, 54, 78, 102, 126}, {6, 26, 52, 78, 104, 130},
	{6, 30, 56, 82, 108, 134}, {6, 34, 60, 86, 112, 138},
	{6, 30, 58, 86, 114, 142}, {6, 34, 62, 90, 118, 146},
	{6, 30, 54, 78, 102, 126, 150}, {6, 24, 50, 76, 102, 128, 154},
	{6, 28, 54, 80, 106, 132, 158}, {6, 32, 58, 84, 110, 136, 162},
	{6, 26, 54, 82, 110, 138, 166}, {6, 30, 58, 86, 114, 142, 170},
}

var remainderBits = [...]int{
	0, 0, 7, 7, 7, 7, 7, 0, 0, 0, 0, 0, 0, 0, 3, 3, 3, 3, 3, 3, 3,
	4, 4, 4, 4, 4, 4, 4, 3, 3, 3, 3, 3, 3, 3, 0, 0, 0, 0, 0, 0,
}

func multiply(x, y int) int {
	result := 0
	for i := 7; i >= 0; i-- {
		result = (result << 1) ^ ((result >> 7) * 0x11d)
		result ^= ((y >> i) & 1) * x
	}
	return result
}

func addErrorCorrection(data []int, version, level int) ([]int, error) {
	count := blocks[version][level]
	eccLength := eccBytes[version][level] / count
	shortLength := len(data) / count
	shortBlocks := count - len(data)%count
	generator := []int{1}
	root := 1
	for i := 0; i < eccLength; i++ {
		next := make([]int, len(generator)+1)
		for j, coefficient := range generator {
			next[j] ^= coefficient
			next[j+1] ^= multiply(coefficient, root)
		}
		generator = next
		root = multiply(root, 2)
	}
	dataBlocks := make([][]int, 0, count)
	eccBlocks := make([][]int, 0, count)
	offset := 0
	for i := 0; i < count; i++ {
		length := shortLength
		if i >= shortBlocks {
			length++
		}
		block := data[offset : offset+length]
		offset += length
		remainder := make([]int, eccLength)
		for _, value := range block {
			factor := value ^ remainder[0]
			copy(remainder, remainder[1:])
			remainder[eccLength-1] = 0
			for j := 0; j < eccLength; j++ {
				remainder[j] ^= multiply(generator[j+1], factor)
			}
		}
		dataBlocks = append(dataBlocks, block)
		eccBlocks = append(eccBlocks, remainder)
	}
	result := make([]int, 0, totalCodewords[version])
	for i := 0; i <= shortLength; i++ {
		for _, block := range dataBlocks {
			if i < len(block) {
				result = append(result, block[i])
			}
		}
	}
	for i := 0; i < eccLength; i++ {
		for _, block := range eccBlocks {
			result = append(result, block[i])
		}
	}
	if len(result) != totalCodewords[version] {
		return nil, errors.New("internal error: incorrect number of QR codewords")
	}
	return result, nil
}

func drawFormat(matrix [][]int, level, mask int) {
	size := len(matrix)
	data := ([4]int{1, 0, 3, 2}[level] << 3) | mask
	remainder := data
	for i := 0; i < 10; i++ {
		remainder = (remainder << 1) ^ (((remainder >> 9) & 1) * 0x537)
	}
	bits := ((data << 10) | remainder) ^ 0x5412
	for i := 0; i < 6; i++ {
		matrix[i][8] = (bits >> i) & 1
	}
	matrix[7][8] = (bits >> 6) & 1
	matrix[8][8] = (bits >> 7) & 1
	matrix[8][7] = (bits >> 8) & 1
	for i := 9; i < 15; i++ {
		matrix[8][14-i] = (bits >> i) & 1
	}
	for i := 0; i < 8; i++ {
		matrix[8][size-1-i] = (bits >> i) & 1
	}
	for i := 8; i < 15; i++ {
		matrix[size-15+i][8] = (bits >> i) & 1
	}
	matrix[size-8][8] = 1
}

func abs(n int) int {
	if n < 0 {
		return -n
	}
	return n
}

func max(a, b int) int {
	if a > b {
		return a
	}
	return b
}

func drawFunctionPatterns(matrix [][]int, version int) {
	size := len(matrix)
	for i := 0; i < size; i++ {
		value := 0
		if i%2 == 0 {
			value = 1
		}
		matrix[6][i], matrix[i][6] = value, value
	}
	for _, center := range [][2]int{{3, 3}, {size - 4, 3}, {3, size - 4}} {
		for dy := -4; dy <= 4; dy++ {
			for dx := -4; dx <= 4; dx++ {
				x, y := center[0]+dx, center[1]+dy
				if x >= 0 && x < size && y >= 0 && y < size {
					distance := max(abs(dx), abs(dy))
					matrix[y][x] = 1
					if distance == 2 || distance == 4 {
						matrix[y][x] = 0
					}
				}
			}
		}
	}
	positions := alignment[version]
	last := len(positions) - 1
	for yi, y := range positions {
		for xi, x := range positions {
			if xi == 0 && yi == 0 || xi == 0 && yi == last || xi == last && yi == 0 {
				continue
			}
			for dy := -2; dy <= 2; dy++ {
				for dx := -2; dx <= 2; dx++ {
					matrix[y+dy][x+dx] = 1
					if max(abs(dx), abs(dy)) == 1 {
						matrix[y+dy][x+dx] = 0
					}
				}
			}
		}
	}
	drawFormat(matrix, 0, 0)
	if version >= 7 {
		remainder := version
		for i := 0; i < 12; i++ {
			remainder = (remainder << 1) ^ (((remainder >> 11) & 1) * 0x1f25)
		}
		bits := (version << 12) | remainder
		for i := 0; i < 18; i++ {
			a, b := size-11+i%3, i/3
			matrix[b][a] = (bits >> i) & 1
			matrix[a][b] = matrix[b][a]
		}
	}
}

func maskBit(mask, x, y int) bool {
	switch mask {
	case 0:
		return (x+y)%2 == 0
	case 1:
		return y%2 == 0
	case 2:
		return x%3 == 0
	case 3:
		return (x+y)%3 == 0
	case 4:
		return (y/2+x/3)%2 == 0
	case 5:
		return x*y%2+x*y%3 == 0
	case 6:
		return (x*y%2+x*y%3)%2 == 0
	default:
		return ((x+y)%2+x*y%3)%2 == 0
	}
}

func drawCodewords(matrix [][]int, codewords []int, mask int) error {
	size := len(matrix)
	bit := 0
	for right := size - 1; right >= 1; right -= 2 {
		if right == 6 {
			right = 5
		}
		for vert := 0; vert < size; vert++ {
			y := vert
			if (right+1)&2 == 0 {
				y = size - 1 - vert
			}
			for _, x := range [2]int{right, right - 1} {
				if matrix[y][x] != -1 {
					continue
				}
				value := 0
				if bit < len(codewords)*8 {
					value = (codewords[bit/8] >> (7 - bit%8)) & 1
				}
				if maskBit(mask, x, y) {
					value ^= 1
				}
				matrix[y][x] = value
				bit++
			}
		}
	}
	version := (size - 17) / 4
	if bit != len(codewords)*8+remainderBits[version] {
		return errors.New("internal error: incorrect number of QR data modules")
	}
	return nil
}

func penalty(matrix [][]int) int {
	size := len(matrix)
	score, dark := 0, 0
	for y := 0; y < size; y++ {
		for x := 0; x < size; x++ {
			dark += matrix[y][x]
			if x > 0 && y > 0 && matrix[y][x] == matrix[y][x-1] &&
				matrix[y][x] == matrix[y-1][x] && matrix[y][x] == matrix[y-1][x-1] {
				score += 3
			}
		}
	}
	for direction := 0; direction < 2; direction++ {
		for i := 0; i < size; i++ {
			line := make([]byte, size)
			run, previous := 0, -1
			for j := 0; j < size; j++ {
				value := matrix[i][j]
				if direction == 1 {
					value = matrix[j][i]
				}
				line[j] = byte('0' + value)
				if value == previous {
					run++
				} else {
					run = 1
				}
				if run == 5 {
					score += 3
				} else if run > 5 {
					score++
				}
				previous = value
			}
			for j := 0; j <= size-11; j++ {
				pattern := string(line[j : j+11])
				if pattern == "00001011101" || pattern == "10111010000" {
					score += 40
				}
			}
		}
	}
	return score + abs(dark*20-size*size*10)/(size*size)*10
}

// CreateQRCode encodes data in byte mode. Levels 0 through 3 are L, M, Q and H;
// values outside that range are clamped. It returns a square matrix where true
// means black, without a quiet zone. Oversized data returns nil and no error.
func CreateQRCode(data []byte, level int) ([][]bool, error) {
	if level < 0 {
		level = 0
	} else if level > 3 {
		level = 3
	}
	version, lengthBits, capacity := 1, 0, 0
	for ; version <= 40; version++ {
		lengthBits = 8
		if version >= 10 {
			lengthBits = 16
		}
		capacity = (totalCodewords[version] - eccBytes[version][level]) * 8
		if len(data) < 1<<lengthBits && 4+lengthBits+len(data)*8 <= capacity {
			break
		}
	}
	if version > 40 {
		return nil, nil
	}
	bits := []int{0, 1, 0, 0}
	for i := lengthBits - 1; i >= 0; i-- {
		bits = append(bits, (len(data)>>i)&1)
	}
	for _, value := range data {
		for i := 7; i >= 0; i-- {
			bits = append(bits, (int(value)>>i)&1)
		}
	}
	terminator := capacity - len(bits)
	if terminator > 4 {
		terminator = 4
	}
	for i := 0; i < terminator; i++ {
		bits = append(bits, 0)
	}
	for len(bits)%8 != 0 {
		bits = append(bits, 0)
	}
	encoded := make([]int, 0, capacity/8)
	for i := 0; i < len(bits); i += 8 {
		value := 0
		for _, bit := range bits[i : i+8] {
			value = (value << 1) | bit
		}
		encoded = append(encoded, value)
	}
	for i := 0; len(encoded) < capacity/8; i++ {
		encoded = append(encoded, [2]int{0xec, 0x11}[i%2])
	}
	codewords, err := addErrorCorrection(encoded, version, level)
	if err != nil {
		return nil, err
	}
	size := version*4 + 17
	base := make([][]int, size)
	for y := range base {
		base[y] = make([]int, size)
		for x := range base[y] {
			base[y][x] = -1
		}
	}
	drawFunctionPatterns(base, version)
	var best [][]int
	bestPenalty := 0
	for mask := 0; mask < 8; mask++ {
		matrix := make([][]int, size)
		for y := range base {
			matrix[y] = append([]int(nil), base[y]...)
		}
		if err := drawCodewords(matrix, codewords, mask); err != nil {
			return nil, err
		}
		drawFormat(matrix, level, mask)
		score := penalty(matrix)
		if best == nil || score < bestPenalty {
			best, bestPenalty = matrix, score
		}
	}
	result := make([][]bool, size)
	for y := range result {
		result[y] = make([]bool, size)
		for x := range result[y] {
			result[y][x] = best[y][x] != 0
		}
	}
	return result, nil
}

// PNGImage renders a square module matrix as a 500x500 PNG with a quiet zone
// at least four modules wide.
func PNGImage(matrix [][]bool) ([]byte, error) {
	modules := len(matrix)
	if modules == 0 || modules > 492 {
		return nil, errors.New("matrix must be square and fit within the image")
	}
	for _, row := range matrix {
		if len(row) != modules {
			return nil, errors.New("matrix must be square and fit within the image")
		}
	}
	scale := 500 / (modules + 8)
	offset := (500 - modules*scale) / 2
	img := image.NewGray(image.Rect(0, 0, 500, 500))
	for i := range img.Pix {
		img.Pix[i] = 255
	}
	for y, row := range matrix {
		for x, black := range row {
			if black {
				for dy := 0; dy < scale; dy++ {
					for dx := 0; dx < scale; dx++ {
						img.SetGray(offset+x*scale+dx, offset+y*scale+dy, color.Gray{Y: 0})
					}
				}
			}
		}
	}
	var result bytes.Buffer
	if err := png.Encode(&result, img); err != nil {
		return nil, err
	}
	return result.Bytes(), nil
}
