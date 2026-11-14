package qrgen

import (
	"bytes"
	"crypto/sha256"
	"fmt"
	"image/color"
	"image/png"
	"reflect"
	"testing"
)

func matrixHash(matrix [][]bool) string {
	var data []byte
	for _, row := range matrix {
		for _, black := range row {
			if black {
				data = append(data, '1')
			} else {
				data = append(data, '0')
			}
		}
	}
	return fmt.Sprintf("%x", sha256.Sum256(data))
}

func TestReferenceMatrices(t *testing.T) {
	fixtures := []struct {
		level, length, version int
		hash                   string
	}{
		{2, 0, 1, "8fd3566c525ca8ce2896e5e4f12d9bd794dea12aded5af7c864e74673bb0f7a1"},
		{3, 35, 5, "5848d152ffe0f065a6cf467e71b213f4954029bb5be5a94b9bd10f9950e05547"},
		{0, 135, 7, "1627cb178d795aa6488fc8748207505738a62f5ffc62ef65d5d55172890226d9"},
		{1, 181, 10, "1f96da67cfe8660d0f9fb035854da60a630fda55408fba305c7580d9ba19bf80"},
		{2, 752, 27, "9f4c019cca08dad576982a145abaf72982bba6db4485d6e2ed060e6b56424719"},
		{3, 1220, 40, "3be9d992af0472aebfed0b6d750daef109760fb7823e97bea59b6465ebf94f0f"},
	}
	for _, fixture := range fixtures {
		t.Run(fmt.Sprintf("level%d_version%d", fixture.level, fixture.version), func(t *testing.T) {
			data := make([]byte, fixture.length)
			for i := range data {
				data[i] = byte(i*73 + 19)
			}
			matrix, err := CreateQRCode(data, fixture.level)
			if err != nil {
				t.Fatal(err)
			}
			if len(matrix) != fixture.version*4+17 {
				t.Fatalf("got size %d", len(matrix))
			}
			if actual := matrixHash(matrix); actual != fixture.hash {
				t.Fatalf("matrix hash: got %s, want %s", actual, fixture.hash)
			}
		})
	}
}

func TestCapacityAndLevels(t *testing.T) {
	for _, test := range []struct{ length, size int }{{17, 21}, {18, 25}, {2953, 177}, {2954, 0}} {
		matrix, err := CreateQRCode(bytes.Repeat([]byte("a"), test.length), 0)
		if err != nil || len(matrix) != test.size {
			t.Fatalf("length %d: size %d, error %v", test.length, len(matrix), err)
		}
	}
	for _, levels := range [][2]int{{-1, 0}, {4, 3}} {
		a, err := CreateQRCode([]byte("test"), levels[0])
		if err != nil {
			t.Fatal(err)
		}
		b, err := CreateQRCode([]byte("test"), levels[1])
		if err != nil || !reflect.DeepEqual(a, b) {
			t.Fatalf("levels %v differ: %v", levels, err)
		}
	}
}

func TestPNGImage(t *testing.T) {
	for _, matrix := range [][][]bool{nil, {{true}, {false}}, make([][]bool, 493)} {
		if _, err := PNGImage(matrix); err == nil {
			t.Error("invalid matrix accepted")
		}
	}
	matrix, err := CreateQRCode([]byte("Hello, QR!"), 3)
	if err != nil {
		t.Fatal(err)
	}
	data, err := PNGImage(matrix)
	if err != nil {
		t.Fatal(err)
	}
	img, err := png.Decode(bytes.NewReader(data))
	if err != nil {
		t.Fatal(err)
	}
	if img.Bounds().Dx() != 500 || img.Bounds().Dy() != 500 {
		t.Fatalf("incorrect bounds: %v", img.Bounds())
	}
	if gray := color.GrayModel.Convert(img.At(0, 0)).(color.Gray); gray.Y != 255 {
		t.Fatal("missing quiet zone")
	}
	scale := 500 / (len(matrix) + 8)
	offset := (500 - len(matrix)*scale) / 2
	for y, row := range matrix {
		for x, black := range row {
			value := color.GrayModel.Convert(img.At(offset+x*scale, offset+y*scale)).(color.Gray).Y
			if (value == 0) != black {
				t.Fatalf("pixel (%d,%d) does not match matrix", x, y)
			}
		}
	}
}
