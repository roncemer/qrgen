package main

import (
	"fmt"
	"io"
	"os"

	"qrgen/qrgen"
)

func run(args []string, stderr io.Writer) int {
	if len(args) != 2 {
		fmt.Fprintln(stderr, "Usage: makeqr <string> <output.png>")
		return 1
	}
	if args[1] == "" {
		fmt.Fprintln(stderr, "The output filename must not be empty.")
		return 1
	}
	matrix, err := qrgen.CreateQRCode([]byte(args[0]), 3)
	if err != nil {
		fmt.Fprintf(stderr, "Could not generate QR code: %v\n", err)
		return 1
	}
	if matrix == nil {
		fmt.Fprintln(stderr, "Could not generate QR code: input is too long.")
		return 1
	}
	data, err := qrgen.PNGImage(matrix)
	if err != nil {
		fmt.Fprintf(stderr, "Could not create PNG image: %v\n", err)
		return 1
	}
	if err := os.WriteFile(args[1], data, 0666); err != nil {
		fmt.Fprintf(stderr, "Could not write PNG image to %s: %v\n", args[1], err)
		return 1
	}
	return 0
}

func main() {
	os.Exit(run(os.Args[1:], os.Stderr))
}
