package generator

import (
	"encoding/json"
	"os"
	"os/exec"
	"path/filepath"
	"slices"
	"strings"
	"testing"

	"github.com/pb33f/go-yaml"
	"github.com/pb33f/libopenapi"
	v3 "github.com/pb33f/libopenapi/datamodel/high/v3"
	"github.com/pb33f/libopenapi/orderedmap"
)

func TestGeneratorSamples(t *testing.T) {
	t.Parallel()

	catalog, expectedSamples := testSampleCatalog(t)
	if catalog.SchemaVersion != 1 {
		t.Fatalf("SchemaVersion = %d, want 1", catalog.SchemaVersion)
	}
	if catalog.Language != "php" {
		t.Fatalf("Language = %q, want php", catalog.Language)
	}
	if catalog.SDK.Module != "sumup/sumup-php" {
		t.Fatalf("SDK.Module = %q", catalog.SDK.Module)
	}
	if catalog.OpenAPIVersion != "1.0.0" {
		t.Fatalf("OpenAPIVersion = %q, want 1.0.0", catalog.OpenAPIVersion)
	}
	if len(catalog.Samples) != expectedSamples {
		t.Fatalf("len(Samples) = %d, want %d", len(catalog.Samples), expectedSamples)
	}
	if !slices.IsSortedFunc(catalog.Samples, func(a, b Sample) int {
		return strings.Compare(a.ID, b.ID)
	}) {
		t.Fatal("samples are not sorted by ID")
	}

	seen := make(map[string]struct{}, len(catalog.Samples))
	for _, sample := range catalog.Samples {
		if _, ok := seen[sample.ID]; ok {
			t.Fatalf("duplicate sample ID %q", sample.ID)
		}
		seen[sample.ID] = struct{}{}
	}

	createCheckout := sampleByID(t, catalog.Samples, "CreateCheckout.HostedCheckout")
	if !strings.Contains(createCheckout.Source, "$sumup->checkouts()->create(") {
		t.Fatalf("CreateCheckout sample does not call the generated SDK method:\n%s", createCheckout.Source)
	}
	if !strings.Contains(createCheckout.Source, "'checkout_reference' => 'b50pr914-6k0e-3091-a592-890010285b3d'") {
		t.Fatalf("CreateCheckout sample does not use the OpenAPI example:\n%s", createCheckout.Source)
	}
	encodedSample, err := json.Marshal(createCheckout)
	if err != nil {
		t.Fatalf("marshal CreateCheckout sample: %v", err)
	}
	if !strings.Contains(string(encodedSample), `"sample":`) {
		t.Fatalf("sample JSON does not preserve the portal contract: %s", encodedSample)
	}
	if strings.Contains(string(encodedSample), `"source":`) {
		t.Fatalf("sample JSON contains internal source field name: %s", encodedSample)
	}

	php, err := exec.LookPath("php")
	if err != nil {
		t.Skip("php is not installed")
	}
	for _, sample := range catalog.Samples {
		filename := filepath.Join(t.TempDir(), "sample.php")
		if err := os.WriteFile(filename, []byte(sample.Source), 0o600); err != nil {
			t.Fatalf("write sample %q: %v", sample.ID, err)
		}
		command := exec.CommandContext(t.Context(), php, "-l", filename)
		if output, err := command.CombinedOutput(); err != nil {
			t.Errorf("lint sample %q: %v\n%s", sample.ID, err, output)
		}
	}
}

func TestGeneratorSamplesDeterministic(t *testing.T) {
	t.Parallel()

	firstCatalog, _ := testSampleCatalog(t)
	first, err := json.Marshal(firstCatalog)
	if err != nil {
		t.Fatalf("marshal first catalog: %v", err)
	}
	secondCatalog, _ := testSampleCatalog(t)
	second, err := json.Marshal(secondCatalog)
	if err != nil {
		t.Fatalf("marshal second catalog: %v", err)
	}
	if string(first) != string(second) {
		t.Fatal("sample generation is not deterministic")
	}
}

func TestRequestExamplesPreserveWholeRequestExample(t *testing.T) {
	t.Parallel()

	var node yaml.Node
	if err := node.Encode(map[string]any{"selected": "request-example"}); err != nil {
		t.Fatalf("encode request example: %v", err)
	}
	content := orderedmap.New[string, *v3.MediaType]()
	content.Set("application/json", &v3.MediaType{Example: &node})
	operation := &v3.Operation{RequestBody: &v3.RequestBody{Content: content}}

	examples := requestExamples(operation)
	if len(examples) != 1 || !examples[0].provided {
		t.Fatalf("requestExamples() = %#v", examples)
	}
	value, ok := examples[0].value.(map[string]any)
	if !ok || len(value) != 1 || value["selected"] != "request-example" {
		t.Fatalf("request example was expanded with schema values: %#v", examples[0].value)
	}
}

func testSampleCatalog(t *testing.T) (*SampleCatalog, int) {
	t.Helper()

	repositoryRoot, err := filepath.Abs(filepath.Join("..", "..", ".."))
	if err != nil {
		t.Fatalf("resolve repository root: %v", err)
	}
	spec, err := os.ReadFile(filepath.Join(repositoryRoot, "openapi.json"))
	if err != nil {
		t.Fatalf("read OpenAPI document: %v", err)
	}
	document, err := libopenapi.NewDocument(spec)
	if err != nil {
		t.Fatalf("load OpenAPI document: %v", err)
	}
	model, err := document.BuildV3Model()
	if err != nil {
		t.Fatalf("build OpenAPI model: %v", err)
	}

	g := New(Config{})
	if err := g.Load(&model.Model); err != nil {
		t.Fatalf("load generator: %v", err)
	}
	catalog, err := g.Samples("test")
	if err != nil {
		t.Fatalf("generate samples: %v", err)
	}
	expectedSamples := 0
	for _, pathItem := range model.Model.Paths.PathItems.FromOldest() {
		for _, operation := range pathItem.GetOperations().FromOldest() {
			expectedSamples += len(requestExamples(operation))
		}
	}
	return catalog, expectedSamples
}

func sampleByID(t *testing.T, samples []Sample, id string) Sample {
	t.Helper()
	for _, sample := range samples {
		if sample.ID == id {
			return sample
		}
	}
	t.Fatalf("sample %q not found", id)
	return Sample{}
}
