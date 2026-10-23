package generator

import (
	"bytes"
	"fmt"
	"log/slog"
	"os"
	"path/filepath"

	"github.com/pb33f/libopenapi/datamodel/high/base"
)

func (g *Generator) writeTypeModels(schemas []*base.SchemaProxy) error {
	enums := g.enumsByTag[typesTagKey]
	if len(schemas) == 0 && len(enums) == 0 {
		return nil
	}

	dir := filepath.Join(g.cfg.Out, typesTagDisplayName)
	if err := os.RemoveAll(dir); err != nil {
		return fmt.Errorf("wipe types directory: %w", err)
	}
	if err := os.MkdirAll(dir, os.ModePerm); err != nil {
		return fmt.Errorf("create types directory: %w", err)
	}

	enumCount := 0
	for _, enum := range enums {
		filename := filepath.Join(dir, fmt.Sprintf("%s.php", enum.Name))
		f, err := os.OpenFile(filename, os.O_CREATE|os.O_WRONLY|os.O_TRUNC, 0o644)
		if err != nil {
			return fmt.Errorf("open %q: %w", filename, err)
		}

		var buf bytes.Buffer
		buf.WriteString("<?php\n\ndeclare(strict_types=1);\n\n")
		fmt.Fprintf(&buf, "namespace %s;\n\n", typesNamespace)
		buf.WriteString(g.buildPHPEnum(enum))

		if _, err := f.Write(buf.Bytes()); err != nil {
			_ = f.Close()
			return fmt.Errorf("write file %q: %w", filename, err)
		}
		_ = f.Close()
		enumCount++
	}

	for _, schema := range schemas {
		className := g.classNameForSchema(schema)
		filename := filepath.Join(dir, fmt.Sprintf("%s.php", className))
		f, err := os.OpenFile(filename, os.O_CREATE|os.O_WRONLY|os.O_TRUNC, 0o644)
		if err != nil {
			return fmt.Errorf("open %q: %w", filename, err)
		}

		var buf bytes.Buffer
		buf.WriteString("<?php\n\ndeclare(strict_types=1);\n\n")
		fmt.Fprintf(&buf, "namespace %s;\n\n", typesNamespace)
		buf.WriteString(g.buildPHPClass(className, schema, typesNamespace))

		if _, err := f.Write(buf.Bytes()); err != nil {
			_ = f.Close()
			return fmt.Errorf("write file %q: %w", filename, err)
		}
		_ = f.Close()
	}

	slog.Info("generated types",
		slog.Int("classes", len(schemas)),
		slog.Int("enums", enumCount),
		slog.String("namespace", typesNamespace),
		slog.String("dir", dir),
	)

	return nil
}
