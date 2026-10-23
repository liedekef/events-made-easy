package main

import (
	"encoding/json"
	"fmt"
	"io"
	"os"
	"path/filepath"

	"github.com/pb33f/libopenapi"
	"github.com/urfave/cli/v2"

	"github.com/sumup/sumup-php/codegen/pkg/generator"
)

func Samples() *cli.Command {
	var out string
	var sdkVersion string
	var sdkVersionFile string
	return &cli.Command{
		Name:  "samples",
		Usage: "Generate PHP code samples as a JSON catalog",
		Args:  true,
		Action: func(c *cli.Context) error {
			if !c.Args().Present() {
				return fmt.Errorf("empty argument, path to openapi specs expected")
			}
			if sdkVersion == "" && sdkVersionFile != "" {
				version, err := readSDKVersion(sdkVersionFile)
				if err != nil {
					return err
				}
				sdkVersion = version
			}
			if sdkVersion == "" {
				return fmt.Errorf("missing SDK version: set --sdk-version or --sdk-version-file")
			}

			spec, err := os.ReadFile(c.Args().First())
			if err != nil {
				return fmt.Errorf("read specs: %w", err)
			}
			document, err := libopenapi.NewDocument(spec)
			if err != nil {
				return fmt.Errorf("load openapi document: %w", err)
			}
			model, err := document.BuildV3Model()
			if err != nil {
				return fmt.Errorf("build openapi v3 model: %w", err)
			}

			g := generator.New(generator.Config{})
			if err := g.Load(&model.Model); err != nil {
				return fmt.Errorf("load specs: %w", err)
			}
			catalog, err := g.Samples(sdkVersion)
			if err != nil {
				return fmt.Errorf("generate samples: %w", err)
			}

			encoded, err := json.MarshalIndent(catalog, "", "  ")
			if err != nil {
				return fmt.Errorf("encode samples: %w", err)
			}
			encoded = append(encoded, '\n')

			stdout := c.App.Writer
			if stdout == nil {
				stdout = os.Stdout
			}
			return writeSamples(out, encoded, stdout)
		},
		Flags: []cli.Flag{
			&cli.StringFlag{
				Name:        "out",
				Aliases:     []string{"o"},
				Usage:       "path of the output JSON file (defaults to stdout)",
				Destination: &out,
			},
			&cli.StringFlag{
				Name:        "sdk-version",
				Usage:       "SumUp PHP SDK version represented by the samples",
				Destination: &sdkVersion,
			},
			&cli.PathFlag{
				Name:        "sdk-version-file",
				Usage:       "composer.json file containing the SDK version",
				Destination: &sdkVersionFile,
			},
		},
	}
}

func writeSamples(out string, encoded []byte, stdout io.Writer) error {
	if out == "" {
		if _, err := stdout.Write(encoded); err != nil {
			return fmt.Errorf("write samples: %w", err)
		}
		return nil
	}

	dir := filepath.Dir(out)
	if err := os.MkdirAll(dir, 0o755); err != nil {
		return fmt.Errorf("create output directory %q: %w", dir, err)
	}
	if err := os.WriteFile(out, encoded, 0o644); err != nil {
		return fmt.Errorf("write samples %q: %w", out, err)
	}
	return nil
}

func readSDKVersion(filename string) (string, error) {
	contents, err := os.ReadFile(filename)
	if err != nil {
		return "", fmt.Errorf("read SDK version file: %w", err)
	}
	var composer struct {
		Version string `json:"version"`
	}
	if err := json.Unmarshal(contents, &composer); err != nil {
		return "", fmt.Errorf("decode SDK version file: %w", err)
	}
	if composer.Version == "" {
		return "", fmt.Errorf("find SDK version in %q", filename)
	}
	return composer.Version, nil
}
