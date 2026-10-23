package generator

import (
	"os"
	"path/filepath"
	"strings"
	"testing"

	"github.com/pb33f/libopenapi"
)

func TestGenerateAnnotatedSchemas(t *testing.T) {
	t.Parallel()
	spec, err := os.ReadFile("testdata/schema-annotations.json")
	if err != nil {
		t.Fatal(err)
	}
	document, err := libopenapi.NewDocument(spec)
	if err != nil {
		t.Fatal(err)
	}
	model, err := document.BuildV3Model()
	if err != nil {
		t.Fatal(err)
	}
	out := t.TempDir()
	generator := New(Config{Out: out})
	if err := generator.Load(&model.Model); err != nil {
		t.Fatal(err)
	}
	if err := generator.Build(); err != nil {
		t.Fatal(err)
	}
	checks := map[string][]string{
		"Address.php":         {"public string $country;"},
		"Merchant.php":        {"public Address $address;", "public string $phoneNumber;", "@var string[]|null"},
		"ExtendedAddress.php": {"public string $country;", "public ?string $label = null;"},
	}
	for file, snippets := range checks {
		data, err := os.ReadFile(filepath.Join(out, "Types", file))
		if err != nil {
			t.Fatal(err)
		}
		for _, snippet := range snippets {
			if !strings.Contains(string(data), snippet) {
				t.Errorf("%s missing %q", file, snippet)
			}
		}
	}
}
