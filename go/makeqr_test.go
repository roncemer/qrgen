package main

import (
	"bytes"
	"image/png"
	"os"
	"path/filepath"
	"strings"
	"testing"
)

func TestRun(t *testing.T) {
	filename := filepath.Join(t.TempDir(), "output.png")
	for _, args := range [][]string{nil, {"data"}, {"data", filename, "extra"}} {
		var stderr bytes.Buffer
		if run(args, &stderr) == 0 || !strings.Contains(stderr.String(), "Usage:") {
			t.Fatalf("wrong argument count: %q", stderr.String())
		}
	}
	for _, data := range []string{"", "Hello, QR! café"} {
		var stderr bytes.Buffer
		if status := run([]string{data, filename}, &stderr); status != 0 {
			t.Fatalf("status %d: %s", status, stderr.String())
		}
		file, err := os.Open(filename)
		if err != nil {
			t.Fatal(err)
		}
		img, err := png.Decode(file)
		closeErr := file.Close()
		if err != nil || closeErr != nil {
			t.Fatalf("PNG decode: %v; close: %v", err, closeErr)
		}
		if img.Bounds().Dx() != 500 || img.Bounds().Dy() != 500 {
			t.Fatalf("unexpected dimensions %v", img.Bounds())
		}
	}
	for _, test := range []struct {
		args    []string
		message string
	}{
		{[]string{strings.Repeat("a", 1274), filename}, "too long"},
		{[]string{"data", ""}, "filename"},
		{[]string{"data", filepath.Join(filename, "output.png")}, "Could not write PNG"},
	} {
		var stderr bytes.Buffer
		if run(test.args, &stderr) == 0 || !strings.Contains(stderr.String(), test.message) {
			t.Fatalf("expected %q, got %q", test.message, stderr.String())
		}
	}
}
