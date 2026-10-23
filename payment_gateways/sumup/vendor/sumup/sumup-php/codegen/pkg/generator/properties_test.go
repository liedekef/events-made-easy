package generator

import (
	"strings"
	"testing"

	"github.com/pb33f/libopenapi/datamodel/high/base"
)

func TestSchemaAllowsNull(t *testing.T) {
	t.Parallel()

	nullable := true
	tests := map[string]struct {
		schema *base.SchemaProxy
		want   bool
	}{
		"OpenAPI 3.0 nullable": {
			schema: base.CreateSchemaProxy(&base.Schema{
				Type:     []string{"string"},
				Nullable: &nullable,
			}),
			want: true,
		},
		"OpenAPI 3.1 null type": {
			schema: base.CreateSchemaProxy(&base.Schema{
				Type: []string{"string", "null"},
			}),
			want: true,
		},
		"non-nullable": {
			schema: base.CreateSchemaProxy(&base.Schema{
				Type: []string{"string"},
			}),
			want: false,
		},
		"nil": {
			want: false,
		},
	}

	for name, tt := range tests {
		t.Run(name, func(t *testing.T) {
			t.Parallel()

			if got := schemaAllowsNull(tt.schema); got != tt.want {
				t.Fatalf("schemaAllowsNull() = %t, want %t", got, tt.want)
			}
		})
	}
}

func TestRequiredNullableProperty(t *testing.T) {
	t.Parallel()

	generator := New(Config{})
	property := phpProperty{
		Name:     "value",
		Type:     "string",
		DocType:  "string",
		Nullable: true,
	}

	generated := generator.renderProperty(property)
	if !strings.Contains(generated, "@var string|null") {
		t.Fatalf("renderProperty() did not document nullability:\n%s", generated)
	}
	if !strings.Contains(generated, "public ?string $value;") {
		t.Fatalf("renderProperty() did not allow null:\n%s", generated)
	}
	if got, want := generator.constructorParamType(property), "?string"; got != want {
		t.Fatalf("constructorParamType() = %q, want %q", got, want)
	}
}
