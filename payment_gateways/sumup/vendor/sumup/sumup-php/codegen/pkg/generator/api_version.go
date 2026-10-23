package generator

import (
	"fmt"
	"os"
	"path/filepath"
)

func (g *Generator) writeApiVersion() error {
	if g.spec == nil || g.spec.Info == nil {
		return fmt.Errorf("missing specs: API version unavailable")
	}

	version := g.spec.Info.Version
	if version == "" {
		return fmt.Errorf("missing specs: API version is empty")
	}

	filename := filepath.Join(g.cfg.Out, "ApiVersion.php")
	f, err := os.OpenFile(filename, os.O_CREATE|os.O_WRONLY|os.O_TRUNC, 0o644)
	if err != nil {
		return fmt.Errorf("open %q: %w", filename, err)
	}
	defer func() {
		_ = f.Close()
	}()

	content := fmt.Sprintf(`<?php

// File generated from our OpenAPI spec

namespace SumUp;

class ApiVersion
{
    const CURRENT = '%s';
}
`, version)

	if _, err := f.WriteString(content); err != nil {
		return fmt.Errorf("write file %q: %w", filename, err)
	}

	return nil
}
